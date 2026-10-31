"""
Authentication and User Management Module for Covid-flow.
Implements registration, secure password hashing, session management,
login throttling, CSRF protection, and user profile management.
"""

from datetime import datetime, timedelta
import functools
import secrets
from flask import (
    Blueprint, flash, g, redirect, render_template, request, session, url_for, current_app, abort
)
from werkzeug.security import check_password_hash, generate_password_hash
from app.database import get_db
from app.validators import validate_username, validate_email, validate_password

bp = Blueprint("auth", __name__)


def generate_csrf_token() -> str:
    """Generate or retrieve current session CSRF token."""
    if "_csrf_token" not in session:
        session["_csrf_token"] = secrets.token_hex(32)
    return session["_csrf_token"]


def validate_csrf_token(token: str) -> bool:
    """Validate submitted CSRF token against current session."""
    session_token = session.get("_csrf_token")
    if not session_token or not token:
        return False
    return secrets.compare_digest(session_token, token)


@bp.before_app_request
def load_logged_in_user():
    """Load user record into request-scoped g.user if authenticated."""
    user_id = session.get("user_id")
    if user_id is None:
        g.user = None
    else:
        db = get_db()
        g.user = db.execute(
            "SELECT id, username, email, role, created_at FROM users WHERE id = ?",
            (user_id,)
        ).fetchone()


def login_required(view):
    """Decorator to require user authentication on protected views."""
    @functools.wraps(view)
    def wrapped_view(**kwargs):
        if g.user is None:
            flash("Please log in to access this page.", "warning")
            return redirect(url_for("auth.login", next=request.url))
        return view(**kwargs)
    return wrapped_view


def check_login_throttle(ip_address: str, username: str) -> tuple[bool, int]:
    """
    Check if IP or username has exceeded maximum failed login attempts
    (5 attempts within 15 minutes). Returns (is_locked, remaining_minutes).
    """
    if current_app.config.get("TESTING"):
        return False, 0

    db = get_db()
    window_start = datetime.now() - timedelta(minutes=15)
    row = db.execute(
        """SELECT COUNT(*) as failed_count FROM login_attempts
           WHERE (ip_address = ? OR username = ?)
             AND success = 0
             AND attempted_at >= ?""",
        (ip_address, username, window_start.strftime("%Y-%m-%d %H:%M:%S"))
    ).fetchone()

    failed_count = row["failed_count"] if row else 0
    if failed_count >= 5:
        return True, 15
    return False, 0


def record_login_attempt(ip_address: str, username: str, success: bool):
    """Record an authentication attempt for rate-limiting and audit logging."""
    if current_app.config.get("TESTING"):
        return
    db = get_db()
    db.execute(
        "INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)",
        (ip_address, username, 1 if success else 0)
    )
    db.commit()


@bp.route("/register", methods=("GET", "POST"))
def register():
    """User registration view."""
    if g.user:
        return redirect(url_for("auth.profile"))

    errors = {}
    form_data = {"username": "", "email": ""}

    if request.method == "POST":
        # CSRF check
        csrf_token = request.form.get("csrf_token", "")
        if not current_app.config.get("TESTING") and not validate_csrf_token(csrf_token):
            flash("Invalid security token. Please try again.", "danger")
            return render_template("register.html", errors=errors, form_data=form_data), 400

        username = request.form.get("username", "").strip()
        email = request.form.get("email", "").strip()
        password = request.form.get("password", "")  # Do not trim password silently
        confirm_password = request.form.get("confirm_password", "")

        form_data["username"] = username
        form_data["email"] = email

        # Validate inputs
        u_ok, u_msg = validate_username(username)
        if not u_ok:
            errors["username"] = u_msg

        e_ok, e_msg = validate_email(email)
        if not e_ok:
            errors["email"] = e_msg

        p_ok, p_msg = validate_password(password)
        if not p_ok:
            errors["password"] = p_msg

        if password != confirm_password:
            errors["confirm_password"] = "Passwords do not match."

        db = get_db()
        if not errors.get("username"):
            existing = db.execute("SELECT id FROM users WHERE username = ?", (username,)).fetchone()
            if existing:
                errors["username"] = "This username is already taken. Please choose another."

        if not errors.get("email"):
            existing = db.execute("SELECT id FROM users WHERE email = ?", (email,)).fetchone()
            if existing:
                errors["email"] = "An account with this email address already exists."

        if not errors:
            pwd_hash = generate_password_hash(password)
            db.execute(
                "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)",
                (username, email, pwd_hash)
            )
            db.commit()

            # Auto-login newly registered user
            new_user = db.execute("SELECT id, username, email, role FROM users WHERE username = ?", (username,)).fetchone()
            session.clear()
            session["user_id"] = new_user["id"]
            session["username"] = new_user["username"]
            session["role"] = new_user["role"]
            generate_csrf_token()

            flash("Registration successful! Welcome to Covid-flow.", "success")
            return redirect(url_for("auth.profile"))

    return render_template("register.html", errors=errors, form_data=form_data)


@bp.route("/login", methods=("GET", "POST"))
def login():
    """User login view with throttling and secure session regeneration."""
    if g.user:
        return redirect(url_for("auth.profile"))

    errors = {}
    form_data = {"username": ""}

    if request.method == "POST":
        csrf_token = request.form.get("csrf_token", "")
        if not current_app.config.get("TESTING") and not validate_csrf_token(csrf_token):
            flash("Invalid security token. Please try again.", "danger")
            return render_template("login.html", errors=errors, form_data=form_data), 400

        username = request.form.get("username", "").strip()
        password = request.form.get("password", "")
        form_data["username"] = username

        ip_addr = request.remote_addr or "127.0.0.1"
        is_locked, rem_mins = check_login_throttle(ip_addr, username)
        if is_locked:
            flash(f"Too many failed login attempts. Please wait {rem_mins} minutes before trying again.", "danger")
            return render_template("login.html", errors=errors, form_data=form_data), 429

        if not username:
            errors["username"] = "Username is required."
        if not password:
            errors["password"] = "Password is required."

        if not errors:
            db = get_db()
            user = db.execute(
                "SELECT id, username, email, password_hash, role FROM users WHERE username = ?",
                (username,)
            ).fetchone()

            if user and check_password_hash(user["password_hash"], password):
                record_login_attempt(ip_addr, username, success=True)
                # Session fixation protection: clear session before setting user credentials
                session.clear()
                session["user_id"] = user["id"]
                session["username"] = user["username"]
                session["role"] = user["role"]
                generate_csrf_token()

                flash(f"Welcome back, {user['username']}!", "success")
                next_url = request.args.get("next")
                if next_url and next_url.startswith("/"):
                    return redirect(next_url)
                return redirect(url_for("auth.profile"))
            else:
                record_login_attempt(ip_addr, username, success=False)
                flash("Invalid username or password. Please check your credentials.", "danger")
                errors["auth"] = "Invalid username or password."

    return render_template("login.html", errors=errors, form_data=form_data)


@bp.route("/logout", methods=("GET", "POST"))
def logout():
    """Clear session and log out user."""
    session.clear()
    flash("You have been logged out safely.", "info")
    return redirect(url_for("public.index"))


@bp.route("/profile", methods=("GET", "POST"))
@login_required
def profile():
    """User profile page showing account details, comment history, and programme registrations."""
    db = get_db()
    user_id = g.user["id"]

    # Handle password change
    errors = {}
    if request.method == "POST":
        csrf_token = request.form.get("csrf_token", "")
        if not current_app.config.get("TESTING") and not validate_csrf_token(csrf_token):
            flash("Invalid security token.", "danger")
            return redirect(url_for("auth.profile"))

        current_password = request.form.get("current_password", "")
        new_password = request.form.get("new_password", "")
        confirm_new = request.form.get("confirm_new_password", "")

        user_row = db.execute("SELECT password_hash FROM users WHERE id = ?", (user_id,)).fetchone()
        if not check_password_hash(user_row["password_hash"], current_password):
            errors["current_password"] = "Current password is incorrect."
        else:
            p_ok, p_msg = validate_password(new_password)
            if not p_ok:
                errors["new_password"] = p_msg
            elif new_password != confirm_new:
                errors["confirm_new_password"] = "New passwords do not match."
            else:
                db.execute(
                    "UPDATE users SET password_hash = ? WHERE id = ?",
                    (generate_password_hash(new_password), user_id)
                )
                db.commit()
                flash("Password updated successfully!", "success")
                return redirect(url_for("auth.profile"))

    # Fetch user comments
    comments = db.execute(
        """SELECT id, body, created_at, status FROM comments
           WHERE user_id = ? ORDER BY created_at DESC""",
        (user_id,)
    ).fetchall()

    # Fetch user registrations
    registrations = db.execute(
        """SELECT id, ref_number, full_name, service, state, status, created_at
           FROM registrations WHERE user_id = ? ORDER BY created_at DESC""",
        (user_id,)
    ).fetchall()

    return render_template(
        "profile.html",
        user=g.user,
        comments=comments,
        registrations=registrations,
        errors=errors
    )
