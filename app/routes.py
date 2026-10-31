"""
Core Application Routes for Covid-flow.
Implements the Dashboard, Statistics, Health Information, Community Comments,
Programme Registration, and Interactive Quality Lab.
"""

from datetime import datetime
import json
from pathlib import Path
import random
import string
from flask import (
    Blueprint, flash, g, jsonify, redirect, render_template, request, url_for, current_app, abort
)
from app.database import get_db
from app.auth import login_required, validate_csrf_token
from app.validators import (
    validate_field, validate_full_name, validate_email, validate_phone,
    validate_dob, validate_pin_code, validate_country_query, validate_comment,
    get_patterns_metadata, RAW_PATTERNS
)
from app.covid_api import get_global_stats, get_historical_stats, get_country_stats
from app.content import get_health_content

bp = Blueprint("public", __name__)


# -------------------------------------------------------------------------
# HOME DASHBOARD
# -------------------------------------------------------------------------
@bp.route("/")
@bp.route("/index")
def index():
    """Home Dashboard view with summary cards and real COVID-19 data."""
    stats = get_global_stats()
    content = get_health_content()
    # Provide top 5 countries for the dashboard highlight
    top_countries = get_country_stats()[:5]

    return render_template(
        "index.html",
        stats=stats,
        content=content,
        top_countries=top_countries
    )


# -------------------------------------------------------------------------
# STATISTICS & VISUALIZATIONS
# -------------------------------------------------------------------------
@bp.route("/statistics")
def statistics():
    """Statistics view with country-wise data, search, and Chart.js trends."""
    query = request.args.get("q", "").strip()
    stats = get_global_stats()
    historical = get_historical_stats(days=30)
    countries = get_country_stats(search_query=query if query else None)

    return render_template(
        "statistics.html",
        stats=stats,
        historical=historical,
        countries=countries,
        query=query
    )


# -------------------------------------------------------------------------
# HEALTH INFORMATION PAGES
# -------------------------------------------------------------------------
@bp.route("/prevention")
def prevention():
    """Prevention guidance page."""
    content = get_health_content()
    return render_template("prevention.html", content=content)


@bp.route("/symptoms")
def symptoms():
    """Symptoms and warning signs page."""
    content = get_health_content()
    return render_template("symptoms.html", content=content)


@bp.route("/vaccination")
def vaccination():
    """Vaccine information and facts page."""
    content = get_health_content()
    return render_template("vaccination.html", content=content)


@bp.route("/myths")
def myths():
    """Myths vs Facts public health page."""
    content = get_health_content()
    return render_template("myths.html", content=content)


@bp.route("/helplines")
def helplines():
    """National and regional helplines directory."""
    content = get_health_content()
    return render_template("helplines.html", content=content)


# -------------------------------------------------------------------------
# COMMUNITY COMMENTS
# -------------------------------------------------------------------------
@bp.route("/community", methods=("GET", "POST"))
def community():
    """Public community board with authenticated posting and owner deletion."""
    db = get_db()
    errors = {}
    form_data = {"comment": ""}

    if request.method == "POST":
        if not g.user:
            flash("You must be logged in to post a comment.", "warning")
            return redirect(url_for("auth.login", next=url_for("public.community")))

        csrf_token = request.form.get("csrf_token", "")
        if not current_app.config.get("TESTING") and not validate_csrf_token(csrf_token):
            flash("Invalid security token. Please refresh and try again.", "danger")
            return redirect(url_for("public.community"))

        body = request.form.get("comment", "").strip()
        form_data["comment"] = body

        c_ok, c_msg = validate_comment(body)
        if not c_ok:
            errors["comment"] = c_msg
        else:
            db.execute(
                "INSERT INTO comments (user_id, body) VALUES (?, ?)",
                (g.user["id"], body)
            )
            db.commit()
            flash("Your comment has been posted successfully!", "success")
            return redirect(url_for("public.community"))

    # Pagination: 10 comments per page
    page = request.args.get("page", 1, type=int)
    per_page = 10
    offset = (page - 1) * per_page

    total_comments = db.execute("SELECT COUNT(*) as cnt FROM comments WHERE status = 'published'").fetchone()["cnt"]
    total_pages = max(1, (total_comments + per_page - 1) // per_page)

    comments = db.execute(
        """SELECT c.id, c.body, c.created_at, c.user_id, u.username, u.role
           FROM comments c
           JOIN users u ON c.user_id = u.id
           WHERE c.status = 'published'
           ORDER BY c.created_at DESC
           LIMIT ? OFFSET ?""",
        (per_page, offset)
    ).fetchall()

    return render_template(
        "community.html",
        comments=comments,
        page=page,
        total_pages=total_pages,
        total_comments=total_comments,
        errors=errors,
        form_data=form_data
    )


@bp.route("/community/delete/<int:comment_id>", methods=("POST",))
@login_required
def delete_comment(comment_id):
    """Delete comment if current user is the author or admin."""
    csrf_token = request.form.get("csrf_token", "")
    if not current_app.config.get("TESTING") and not validate_csrf_token(csrf_token):
        flash("Invalid security token.", "danger")
        return redirect(url_for("public.community"))

    db = get_db()
    comment = db.execute("SELECT id, user_id FROM comments WHERE id = ?", (comment_id,)).fetchone()
    if not comment:
        abort(404)

    # Ownership check
    if comment["user_id"] != g.user["id"] and g.user["role"] != "admin":
        flash("You are not authorized to delete this comment.", "danger")
        return redirect(url_for("public.community"))

    db.execute("DELETE FROM comments WHERE id = ?", (comment_id,))
    db.commit()
    flash("Comment deleted successfully.", "info")
    return redirect(url_for("public.community"))


# -------------------------------------------------------------------------
# PROGRAMME REGISTRATION
# -------------------------------------------------------------------------
@bp.route("/programme", methods=("GET", "POST"))
def programme():
    """Public health awareness and community programme registration."""
    errors = {}
    form_data = {
        "full_name": "",
        "email": "",
        "phone": "",
        "dob": "",
        "gender": "",
        "pin_code": "",
        "state": "",
        "service": "",
        "consent": False
    }
    confirmation = None

    if request.method == "POST":
        csrf_token = request.form.get("csrf_token", "")
        if not current_app.config.get("TESTING") and not validate_csrf_token(csrf_token):
            flash("Invalid security token. Please try again.", "danger")
            return render_template("programme.html", errors=errors, form_data=form_data), 400

        full_name = request.form.get("full_name", "").strip()
        email = request.form.get("email", "").strip()
        phone = request.form.get("phone", "").strip()
        dob = request.form.get("dob", "").strip()
        gender = request.form.get("gender", "").strip()
        pin_code = request.form.get("pin_code", "").strip()
        state = request.form.get("state", "").strip()
        service = request.form.get("service", "").strip()
        consent = request.form.get("consent") == "on" or request.form.get("consent") == "1"

        form_data.update({
            "full_name": full_name,
            "email": email,
            "phone": phone,
            "dob": dob,
            "gender": gender,
            "pin_code": pin_code,
            "state": state,
            "service": service,
            "consent": consent
        })

        # Field validations
        fn_ok, fn_msg = validate_full_name(full_name)
        if not fn_ok:
            errors["full_name"] = fn_msg

        em_ok, em_msg = validate_email(email)
        if not em_ok:
            errors["email"] = em_msg

        ph_ok, ph_msg = validate_phone(phone)
        if not ph_ok:
            errors["phone"] = ph_msg

        dob_ok, dob_msg = validate_dob(dob)
        if not dob_ok:
            errors["dob"] = dob_msg

        if not gender or gender not in ("male", "female", "other", "prefer_not_to_say"):
            errors["gender"] = "Please select a valid gender option."

        pin_ok, pin_msg = validate_pin_code(pin_code)
        if not pin_ok:
            errors["pin_code"] = pin_msg

        if not state:
            errors["state"] = "State is required."

        valid_services = [
            "vaccination_info",
            "awareness_webinar",
            "volunteer_support",
            "helpline_callback"
        ]
        if not service or service not in valid_services:
            errors["service"] = "Please select a valid programme service."

        if not consent:
            errors["consent"] = "You must provide consent to process your registration."

        db = get_db()
        # Duplicate registration check (same email and service)
        if not errors.get("email") and not errors.get("service"):
            existing = db.execute(
                "SELECT id, ref_number FROM registrations WHERE email = ? AND service = ?",
                (email, service)
            ).fetchone()
            if existing:
                errors["service"] = f"A registration for this service with email {email} already exists (Ref: {existing['ref_number']})."

        if not errors:
            # Generate unique reference number: COV-2026-XXXXX
            random_suffix = "".join(random.choices(string.ascii_uppercase + string.digits, k=5))
            ref_number = f"COV-2026-{random_suffix}"
            user_id = g.user["id"] if g.user else None

            db.execute(
                """INSERT INTO registrations
                   (user_id, ref_number, full_name, email, phone, dob, gender, pin_code, state, service, consent)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)""",
                (user_id, ref_number, full_name, email, phone, dob, gender, pin_code, state, service, 1 if consent else 0)
            )
            db.commit()

            confirmation = {
                "ref_number": ref_number,
                "full_name": full_name,
                "email": email,
                "service": service,
                "state": state
            }
            flash(f"Registration successful! Your reference number is {ref_number}.", "success")

    return render_template(
        "programme.html",
        errors=errors,
        form_data=form_data,
        confirmation=confirmation
    )


# -------------------------------------------------------------------------
# INTERACTIVE QUALITY LAB (STQA Demonstration)
# -------------------------------------------------------------------------
@bp.route("/quality-lab")
def quality_lab():
    """Interactive Quality Lab demonstrating regular expression testing, boundary values, and STQA concepts."""
    metadata = get_patterns_metadata()
    return render_template("quality_lab.html", patterns=metadata)


@bp.route("/api/quality-lab/validate", methods=("POST",))
def api_quality_lab_validate():
    """JSON API endpoint to validate single input against selected category."""
    data = request.get_json() or {}
    field = data.get("field", "")
    value = data.get("value", "")

    if field not in RAW_PATTERNS:
        return jsonify({"valid": False, "error": f"Unknown category: {field}"}), 400

    valid, error = validate_field(field, value, semantic=True)
    pattern_info = RAW_PATTERNS[field]

    return jsonify({
        "valid": valid,
        "error": error,
        "field": field,
        "value": value,
        "pattern": pattern_info["pattern"],
        "description": pattern_info["description"]
    })


@bp.route("/api/quality-lab/cases")
def api_quality_lab_cases():
    """Return predefined test cases from tests/regex-cases.json."""
    cases_path = Path(__file__).resolve().parent.parent / "tests" / "regex-cases.json"
    if cases_path.exists():
        with open(cases_path, "r", encoding="utf-8") as f:
            cases = json.load(f)
        return jsonify(cases)
    return jsonify({})


@bp.route("/api/quality-lab/run-suite", methods=("POST",))
def api_quality_lab_run_suite():
    """Execute all test cases in the test suite and return detailed results."""
    cases_path = Path(__file__).resolve().parent.parent / "tests" / "regex-cases.json"
    if not cases_path.exists():
        return jsonify({"error": "Test cases file not found"}), 404

    with open(cases_path, "r", encoding="utf-8") as f:
        cases_dict = json.load(f)

    results = []
    total_passed = 0
    total_tests = 0

    for field, field_cases in cases_dict.items():
        for case in field_cases:
            total_tests += 1
            if "in" in case:
                val = case["in"]
            else:
                g_spec = case["gen"]
                val = g_spec.get("prefix", "") + g_spec["repeat"] * g_spec["times"] + g_spec.get("suffix", "")

            expected = case["ok"]
            note = case.get("note", "")

            # For regex shape matching
            from app.validators import match_regex
            actual = match_regex(field, val)
            passed = (actual == expected)
            if passed:
                total_passed += 1

            results.append({
                "field": field,
                "input": val if len(val) <= 40 else val[:37] + "...",
                "expected": "PASS" if expected else "FAIL",
                "actual": "PASS" if actual else "FAIL",
                "status": "PASS" if passed else "FAIL",
                "note": note
            })

    return jsonify({
        "total": total_tests,
        "passed": total_passed,
        "failed": total_tests - total_passed,
        "pass_rate": f"{(total_passed / total_tests) * 100:.1f}%",
        "results": results
    })


# -------------------------------------------------------------------------
# UTILITY APIS
# -------------------------------------------------------------------------
@bp.route("/api/stats")
def api_stats():
    """Return JSON global COVID-19 stats."""
    return jsonify(get_global_stats())


@bp.route("/api/check-username")
def check_username():
    """Live AJAX check for username availability."""
    username = request.args.get("username", "").strip()
    u_ok, u_msg = validate_field("username", username)
    if not u_ok:
        return jsonify({"available": False, "message": u_msg})

    db = get_db()
    existing = db.execute("SELECT id FROM users WHERE username = ?", (username,)).fetchone()
    if existing:
        return jsonify({"available": False, "message": "Username is already taken."})
    return jsonify({"available": True, "message": "Username is available!"})
