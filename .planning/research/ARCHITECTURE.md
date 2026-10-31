# Architecture Patterns

**Domain:** Educational COVID-19 public-health information portal
**Researched:** 2026-10-31
**Confidence:** MEDIUM — recommendations are cross-checked against current primary Flask, Python, Selenium, pytest, and Chart.js documentation. Context7 and the configured Brave provider were unavailable, so no claim is treated as higher than MEDIUM.

## Recommended Architecture

Build Covid-flow as one small server-rendered Flask application package. Use an application factory to construct independently configured development, test, and production apps; register focused blueprints there; and retain standard-library `sqlite3` instead of introducing an ORM. Keep request handlers thin: validate input and authorize, invoke a service or repository helper, then render or redirect. This is the right level of structure for a viva-ready project: responsibilities are visible without a fake enterprise layer.

```
Browser
  -> Flask application factory
      -> public / auth / community / programme / quality blueprints
          -> auth, CSRF, validation, ownership checks
          -> services (statistics, content, registration, comments)
              -> repository helpers / sqlite3
                  -> instance/covid_flow.sqlite3
      -> Jinja templates + static CSS / vanilla JS / Chart.js
```

Suggested package layout:

```
covid_flow/
  __init__.py             # create_app(config=None), configuration, blueprint registration
  db.py                   # get_db(), close_db(), init-db CLI, schema migration/bootstrap
  security.py             # CSRF token issue/validate, login_required, ownership helpers
  repositories/           # narrow SQL functions grouped by table/aggregate
  services/
    statistics.py         # remote fetch, normalization, cache/fallback labelling
    content.py             # curated guidance and source metadata
    community.py           # comment and moderation business rules
  blueprints/
    public.py              # home, guidance, statistics
    auth.py                # register, login, logout
    community.py           # comments and moderation views
    programme.py           # registration workflow
    quality.py             # test/validation demonstrations
  templates/               # base.html, partials/, per-blueprint templates
  static/                  # css/, js/, images/, vendor/chart.umd.js
  schema.sql
instance/                  # environment-specific SQLite database, never committed
tests/
  conftest.py              # app/client/runner/database fixtures
  unit/ integration/
  selenium/pages/          # Page Objects and reusable components only
  selenium/test_*.py       # user-journey assertions
```

### Component Boundaries

| Component | Responsibility | Communicates With |
|---|---|---|
| `create_app` factory | Load defaults and test overrides; establish instance path; initialise DB hooks; register blueprints and error handlers. | `db`, blueprints, config |
| Blueprints/routes | HTTP method, query/form extraction, response status, template selection, redirect/flash. No SQL and no outbound statistics calls. | security helpers, services, templates |
| `security.py` | Session-derived current user, password-hash use, login guard, CSRF validation on every state-changing form, owner/moderator authorization, login-throttle policy. | Flask `session`, `g`, auth repository |
| Services | Domain decisions and transformations: normalise statistics, choose fresh source vs local fallback, apply comment/moderation and programme rules. | repositories, source/fallback files |
| Statistics service | Fetch server-side with short timeout; validate the response; return a typed view model containing values, source URL, retrieval time, and `live`/`fallback`/`unavailable` state. A fallback is data, never a disguised live response. | outbound client, curated local fallback, stats repository |
| Repositories | Parameterized SQL and transaction boundaries only; return `sqlite3.Row` or small domain dictionaries. | request-scoped `sqlite3` connection |
| `db.py` | One connection per request in Flask `g`, `sqlite3.Row` row factory, foreign keys enabled, teardown close, schema-init CLI. | `current_app`, `g`, SQLite |
| Jinja templates | Accessible semantic HTML and escaped presentation; inherited layout, flashes, form CSRF field, source/fallback status and no business logic. | route view models, static assets |
| Static JS + Chart.js | Progressively enhance server-rendered statistic table into a chart. Read only a small JSON payload or `data-*` values supplied by the template. | DOM, Chart.js |
| pytest fixtures | Produce an isolated app and temporary SQLite file, initialise deterministic data, expose Flask test client and CLI runner. | factory, `db.py` |
| Selenium page objects | Encapsulate stable locators and user actions; page components model shared navigation, alerts, and statistics table/chart. Tests own assertions. | running application through WebDriver |

### Data Flow

1. The factory receives configuration. Tests inject `TESTING`, a temporary database path, and controlled statistics-client settings; non-test runs read secrets and database location from environment/instance configuration.
2. Before protected views, an auth hook reads the small `user_id` session value and loads the current user into request-local `g.user`. The session must never carry password hashes, roles as an authority source, personal data, or cached statistics.
3. A write form carries a CSRF token. The route validates method, token, field constraints, authentication, and ownership before it calls a service. The repository then issues only bound-parameter SQL and commits its short transaction.
4. The statistics route calls `StatisticsService.get_snapshot()`. The service first obtains and validates a server-side source response; on source/validation failure, it returns versioned local fallback values with an explicit label and source date; if neither is suitable, it returns an unavailable state. The route renders this same status in the HTML table, accessible text, and chart payload.
5. Jinja renders the canonical content. Optional JavaScript turns supplied statistics into Chart.js configuration. The values and textual table remain available without JavaScript; the canvas gets an accessible name and nearby source/fallback text.
6. Unit tests exercise services, validation, and repository behavior. Integration tests use the factory plus Flask client. Selenium tests start a known application/fixture dataset, execute complete browser journeys through page objects, use explicit waits for actual state changes, and write reports/screenshots only when run.

## Build Order

1. **Foundation:** package, `create_app`, configuration classes, base template/static layout, request-scoped DB helper, schema and `init-db` CLI.
2. **Security spine:** registration/login/logout, password hashing, `g.user`, login guard, CSRF helper/template field, cookie settings, validation and authorization/ownership utility.
3. **Public information:** curated guidance, source metadata, server-side statistics service, labelled fallback/unavailable view models, accessible statistics table, then Chart.js enhancement.
4. **Authenticated workflows:** programme registration and community comments using the shared validation, service, repository, CSRF, and ownership paths; add narrow moderation capability only where needed.
5. **Quality Lab and evidence:** explicit demonstrable validation/security cases, pytest fixture hierarchy, integration suite, Selenium page objects and reports/screenshots.

This sequencing is intentional: every later workflow depends on the same factory, database lifetime, security, and view-model contracts. Charting follows—not precedes—the trustworthy data state model.

## Patterns to Follow

### Pattern 1: Factory + focused blueprints

**What:** Construct the Flask app in `create_app`, then register blueprints whose URLs and templates share a user-facing capability. Keep at most five or six: public, auth, community, programme, quality, and optionally admin/moderation.

**When:** Always in this rebuild. It gives each test an independent configured app and prevents circular imports/global application state.

**Example:**

```python
# covid_flow/__init__.py
from flask import Flask

def create_app(test_config=None):
    app = Flask(__name__, instance_relative_config=True)
    app.config.from_mapping(
        SECRET_KEY="replace-from-environment",
        DATABASE="instance/covid_flow.sqlite3",
        SESSION_COOKIE_HTTPONLY=True,
        SESSION_COOKIE_SAMESITE="Lax",
    )
    if test_config is not None:
        app.config.update(test_config)

    from . import db
    db.init_app(app)
    from .blueprints import auth, public, community, programme, quality
    for blueprint in (public.bp, auth.bp, community.bp, programme.bp, quality.bp):
        app.register_blueprint(blueprint)
    return app
```

Flask documents factories as the place to configure an application and register blueprints; a blueprint groups related views before the factory registers it. [Flask application factories](https://flask.palletsprojects.com/en/stable/patterns/appfactories/) and [blueprints/views](https://flask.palletsprojects.com/en/stable/tutorial/views/) — MEDIUM confidence.

### Pattern 2: Request-scoped `sqlite3` with repository seams

**What:** Lazily create a connection once per Flask request, set a row factory and pragmas, close at app-context teardown, and expose narrow repository functions. Use `?`/named bindings, never interpolated SQL. Each command handler owns a brief transaction.

**When:** For all persisted users, comments, registrations, roles, throttle records, audit metadata, and optional cached source metadata. Use local JSON/CSV only for immutable seeded fallback data where a source date is part of the content.

**Example:**

```python
# covid_flow/db.py
import sqlite3
from flask import current_app, g

def get_db():
    if "db" not in g:
        db = sqlite3.connect(current_app.config["DATABASE"])
        db.row_factory = sqlite3.Row
        db.execute("PRAGMA foreign_keys = ON")
        g.db = db
    return g.db

def find_user_by_username(username):
    return get_db().execute(
        "SELECT id, username, password_hash, role FROM users WHERE username = ?",
        (username,),
    ).fetchone()
```

Flask’s SQLite tutorial models one connection stored in `g` and closed on teardown; Python’s `sqlite3` documentation requires DB-API parameter substitution for values. [Flask database pattern](https://flask.palletsprojects.com/en/stable/tutorial/database/) and [Python `sqlite3`](https://docs.python.org/3/library/sqlite3.html) — MEDIUM confidence.

### Pattern 3: Labelled statistics view model

**What:** Return a single `StatisticsSnapshot`/dictionary contract to every consumer: `metrics`, `source_name`, `source_url`, `observed_at`, `retrieved_at`, `state`, and `notice`. `state` is an enum-like `live`, `fallback`, or `unavailable`; it controls copy and chart availability.

**When:** Every dashboard/statistics request and test. It makes a transient source failure visible, testable, and impossible to accidentally misrepresent as current data.

**Example:**

```python
def get_snapshot(client, fallback):
    try:
        payload = client.fetch(timeout=3)
        metrics = validate_metrics(payload)
        return {"state": "live", "metrics": metrics,
                "source_url": payload["source_url"], "notice": None}
    except SourceError:
        return {"state": "fallback", "metrics": fallback.metrics,
                "source_url": fallback.source_url,
                "notice": "Showing dated local fallback data."}
```

### Pattern 4: Server-rendered baseline with optional chart

**What:** Render an accessible HTML data table and source/fallback notice first. Supply only normalized values to a small page-specific JS module that builds a Chart.js chart. Give canvas an accessible name and preserve the table as the durable textual equivalent.

**When:** Statistics/dashboard only. Do not make health guidance, registration, comments, or auth depend on JavaScript.

Chart.js states that canvas graphics need an accessible name or fallback content; its label array must align with the largest dataset. [Chart.js accessibility](https://www.chartjs.org/docs/latest/general/accessibility.html) and [data structures](https://www.chartjs.org/docs/latest/general/data-structures.html) — MEDIUM confidence.

### Pattern 5: Layered test fixtures and page objects

**What:** Use pytest fixtures to create a temporary, initialized database per test, then add Flask client/CLI and authenticated-user helpers. Keep Selenium-specific fixtures separate: base URL, browser lifecycle, artifact directory. Page objects expose journeys such as `login_as`, `submit_comment`, and `register_for_programme`; their constructors may check readiness, but assertions stay in tests.

**When:** From the first protected workflow onward. It prevents state leakage and makes each Selenium failure map to a user task rather than a selector scatter.

Flask demonstrates a factory-backed temporary database fixture; Selenium recommends page objects to centralize locators and interactions, while tests retain assertions. [Flask testing tutorial](https://flask.palletsprojects.com/en/stable/tutorial/tests/) and [Selenium page objects](https://www.selenium.dev/documentation/test_practices/encouraged/page_object_models/) — MEDIUM confidence.

## Anti-Patterns to Avoid

### Anti-Pattern 1: A single giant `app.py` or database-aware templates

**What:** Routes contain SQL, remote fetches, auth checks, HTML construction, and chart JSON; templates query or branch on data-state rules.

**Why bad:** It hides security and source-truth decisions, makes tests require browser-level coverage for basic rules, and destroys the simple seams needed for a viva.

**Instead:** Thin route -> security/validation -> service -> repository -> view model -> template.

### Anti-Pattern 2: Client-side live-statistics fetching or silent fallback

**What:** Browser JS calls an untrusted public endpoint directly, or source errors quietly show bundled values under a “live” heading.

**Why bad:** Source provenance, validation, timeout behavior, CORS, and fallback truthfulness become inconsistent and difficult to test; the project violates its no-fabrication constraint.

**Instead:** One server-side statistics service returns a labelled state and canonical source metadata to all displays.

### Anti-Pattern 3: Global SQLite connection or raw string SQL

**What:** A module-level connection survives requests/tests, or SQL is built with f-strings/formatting.

**Why bad:** Cross-request state and lock failures become intermittent; string construction permits injection.

**Instead:** Request-scoped connection in `g`, teardown close, short writes, schema constraints, and parameter binding.

### Anti-Pattern 4: Session as a user record or CSRF by HTTP method alone

**What:** Put role/profile data or passwords in the signed cookie, trust `POST` to be safe, or exclude programme/comment/moderation forms from CSRF validation.

**Why bad:** Cookie-backed authentication creates implicit state and every mutation becomes cross-site requestable without a verified token. Signed session data is integrity-protected, not a place for confidential or authoritative mutable state.

**Instead:** Store only an identifier in session, reload user/role server-side, validate a per-session CSRF token on every state change, and verify record ownership/role at the service boundary. Flask explicitly describes CSRF token comparison for cookie-authenticated mutation and recommends secure cookie flags. [Flask security considerations](https://flask.palletsprojects.com/en/stable/web-security/) — MEDIUM confidence.

### Anti-Pattern 5: Selenium tests with sleeps, shared drivers, or locators in tests

**What:** `time.sleep`, implicit-plus-explicit waits, a global browser, and duplicated absolute XPath/CSS locators.

**Why bad:** Tests become flaky, order-dependent, and costly to update after harmless UI changes.

**Instead:** A fresh driver per test, teardown `quit()`, stable `id`/`name`/`data-test` locators owned by page objects, and condition-specific explicit waits. Selenium warns that mixing implicit and explicit waits produces unpredictable timeouts. [Selenium waiting strategies](https://www.selenium.dev/documentation/webdriver/waits/) — MEDIUM confidence.

## Scalability Considerations

| Concern | At 100 users | At 10K users | At 1M users |
|---|---|---|---|
| Reads and guidance | SQLite plus server-rendered pages is sufficient. | Add HTTP caching/CDN for static assets and review read load. | Move to managed relational DB and horizontally scalable app service; out of scope for this rebuild. |
| SQLite writes | Short transactions and basic registration/comment workloads are fine. | Serialized writes become visible under bursts; measure before change. | Replace SQLite with a server database and revisit queueing; do not introduce this early. |
| Statistics source | Per-request fetch with short timeout and labelled fallback is adequate for demos. | Cache validated snapshots with expiry and show retrieval time. | Background refresh, persistent cache, observability, and source agreements; out of scope. |
| Authentication | Session cookie plus rate limiting and secure configuration. | Centralize logs/throttle state if multiple app processes are added. | Dedicated identity system, stronger monitoring and recovery controls; out of scope. |
| Browser tests | One local browser per test, page objects, screenshots on failure. | Parallelize only after each test controls data and server state. | Keep a small smoke journey suite; rely chiefly on unit/integration tests. |

## Sources

- [Flask application factories](https://flask.palletsprojects.com/en/stable/patterns/appfactories/) — MEDIUM (official documentation; Context7 unavailable)
- [Flask blueprints and views](https://flask.palletsprojects.com/en/stable/tutorial/views/) — MEDIUM
- [Flask SQLite database pattern](https://flask.palletsprojects.com/en/stable/tutorial/database/) — MEDIUM
- [Flask security considerations](https://flask.palletsprojects.com/en/stable/web-security/) — MEDIUM
- [Python `sqlite3`](https://docs.python.org/3/library/sqlite3.html) — MEDIUM
- [Flask testing tutorial](https://flask.palletsprojects.com/en/stable/tutorial/tests/) — MEDIUM
- [Selenium page object models](https://www.selenium.dev/documentation/test_practices/encouraged/page_object_models/) and [waiting strategies](https://www.selenium.dev/documentation/webdriver/waits/) — MEDIUM
- [Chart.js accessibility](https://www.chartjs.org/docs/latest/general/accessibility.html) and [data structures](https://www.chartjs.org/docs/latest/general/data-structures.html) — MEDIUM

## Research Gaps

- The eventual statistics source and its response/attribution/licensing requirements are not selected; research that source during the statistics phase before writing its client.
- The desired moderation policy and retention rules need a product decision before designing roles/audit tables.
- SQLite is appropriate for the stated educational scope but write-load behavior must be measured before any wider deployment claim.
