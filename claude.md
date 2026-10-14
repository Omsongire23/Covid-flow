# CLAUDE.md — COVID-19 Information Portal (SQA Mini Project)

> Build specification and working rules for the coding agent.
> Course: Software Testing and Quality Assurance — Mini Project.
> Stack: HTML5, CSS3, JavaScript (ES modules), PHP 8.1+, MySQL / MariaDB.
> Read this whole file before writing any code.

---

## 0. Problem statement and how to use this file

**Problem statement (verbatim):**
"Software Testing and Quality Assurance Mini Project: Dynamic website of COVID-19 information using HTML, CSS, JavaScript and PHP. MySQL database used to store user account, comment, and registration form details. Regular expression test cases for testing purposes."

This file is the single source of truth. Build the application it describes, **test it**, and report real results. The project has two equally weighted halves:

1. **The product** — a professional, interactive, dynamic COVID-19 information website.
2. **The quality evidence** — regular-expression validation with documented test cases, automated test runners, a test plan, a traceability matrix, a defect log, and a test report. This is an SQA course project; the testing artifacts are graded deliverables, not extras.

Rules for the whole build:
- Never fake data, statistics, test results, charts, or buttons. Every control must work.
- Never leave `TODO`, placeholder text, lorem ipsum, or dead links.
- Do not add frameworks (no Laravel, no React, no Bootstrap build step). Plain PHP + PDO, vanilla JS, hand-written CSS.
- Do not stop at a plan. Implement, run the checks in Section 12, fix failures, and report honestly (`passed` / `failed (fixed)` / `not run (reason)`).

---

## 1. Scope and assumptions

The statement is short, so these interpretations are fixed. If something conflicts with the course rubric, change the assumption and note it in the README.

| Statement phrase | Interpretation used |
|---|---|
| "Dynamic website of COVID-19 information" | Pages whose numbers come from a live data source via PHP (cached in MySQL), plus curated informational pages (prevention, symptoms, vaccination, myths vs facts, helplines). |
| "user account" | Sign up, log in, log out, profile page. Passwords hashed. |
| "comment" | Logged-in users post comments on a Community page; everyone can read them. |
| "registration form details" | A separate **Programme Registration** form (vaccination-information session, awareness webinar, volunteering, helpline call-back) saved to MySQL, linked to the account. This is *not* the same as account sign-up. |
| "Regular expression test cases" | One shared set of validation patterns used by both JavaScript (client) and PHP (server), a large table of valid/invalid test inputs, automated runners in Node and PHP, and an interactive in-browser **Quality Lab** page. |

Out of scope: payments, email sending, real appointment booking, medical advice, social login, any framework migration.

### Functional requirements (used for traceability)

| ID | Requirement |
|---|---|
| FR-01 | Home page shows global COVID-19 statistics (cases, deaths, recovered if reported, active, today's changes) with source and last-updated time. |
| FR-02 | Statistics page: trend chart with range and metric controls; searchable, sortable, filterable, paginated country table. |
| FR-03 | Informational pages: Prevention, Symptoms, Vaccination, Myths vs Facts, Helplines — each with sources and a "last reviewed" date. |
| FR-04 | Account sign-up with server- and client-side validation. |
| FR-05 | Login / logout with session security and brute-force throttling. |
| FR-06 | Profile page: own account details, own registrations, own comments. |
| FR-07 | Community page: post, list (paginated), and delete-own comments. |
| FR-08 | Programme Registration form with live validation; stored in MySQL; duplicate prevention; confirmation with reference number. |
| FR-09 | Shared regex validation (JS + PHP) for username, full name, email, password, phone, date of birth, PIN code, country search, comment. |
| FR-10 | Quality Lab page: live pattern tester and full test-suite runner with pass/fail table and CSV/JSON export. |
| FR-11 | Light/dark theme, responsive layout, keyboard accessible. |
| FR-12 | Data-source failure handled gracefully (cached data, stale banner, or clear empty state — never invented numbers). |
| FR-13 | (Stretch) Admin comment moderation. |

---

## 2. Architecture decisions (ADR format)

### ADR-001: Plain PHP 8.1+ with PDO, no framework
**Status:** Accepted

**Context.** Course project, graders expect to read the code, typical lab setup is XAMPP/WAMP/LAMP, short timeline.

| Dimension | Plain PHP + PDO | Laravel | Procedural `mysqli` |
|---|---|---|---|
| Complexity | Low | High (Composer, artisan, migrations) | Low |
| Security by default | Manual but explicit | Strong | Easy to misuse |
| Readability for grading | High | Medium | Medium |
| Setup on lab PCs | Trivial | Hard | Trivial |

**Decision.** Plain PHP with PDO prepared statements and small include files (`bootstrap`, `db`, `auth`, `csrf`, `validate`, `helpers`).
**Consequences.** Easier to review and test; security must be applied deliberately (Section 8 lists every rule). No ORM or router.

### ADR-002: COVID data fetched server-side, cached in MySQL, never invented
**Status:** Accepted

**Context.** The site must be dynamic. Browsers calling a third-party API directly would leak visitor IPs, hit CORS/rate limits, and make the site depend on that API's availability.

| Option | Pros | Cons |
|---|---|---|
| A. PHP fetches API, caches in MySQL, serves own JSON endpoint | One cache, same-origin JS, offline-tolerant, testable | Needs cURL, a cache table |
| B. Browser calls the public API | Simplest code | CORS/privacy/availability risk, hard to test |
| C. Static JSON file only | No dependencies | Not dynamic |

**Decision.** Option A, using the public `disease.sh` v3 API. Fallback chain: fresh cache → live fetch → stale cache with a visible banner → a real snapshot file `data/covid-fallback.json` created at build time **only if the API was reachable** → clear empty state. Section 11 has the details.
**Consequences.** `api/stats.php` is the only data endpoint the front end uses. The agent must check the API at build time; if unreachable, ship the empty-state path and say so — never fabricate numbers.

### ADR-003: Native PHP sessions + CSRF tokens + throttling
**Status:** Accepted

**Decision.** `$_SESSION` with hardened cookie flags, `session_regenerate_id(true)` on login, per-session CSRF token on every state-changing request, `password_hash()` / `password_verify()`, and a `login_attempts` table for throttling. No JWT, no third-party auth.
**Consequences.** Simple and gradeable; requires HTTPS in production for the `Secure` flag (the code sets it automatically when the request is HTTPS).

### ADR-004: One regex source of truth, two engines
**Status:** Accepted

**Context.** JavaScript and PHP (PCRE) regex engines differ in small but dangerous ways. Validating only on the client is insecure; validating differently on each side causes inconsistent behaviour.

**Decision.** All patterns live in `config/patterns.json`. PHP loads the file for server validation; `api/patterns.php` serves the same JSON to the browser. Patterns are compiled in JS with the `u` flag and in PHP with delimiter `~` and modifiers `Du` (see Section 9 for why `D` is mandatory). Both engines run the same `tests/regex-cases.json`.
**Consequences.** Patterns must avoid constructs that behave differently across engines and must contain neither `~` nor `/`. A test fails the build if the two engines ever disagree.

### ADR-005: Deployment is local/shared PHP hosting — not Vercel
**Status:** Accepted

**Context.** The statement requires PHP and MySQL. Vercel does not run PHP + MySQL as a native app.
**Decision.** Document XAMPP/WAMP (demo) and generic shared-hosting steps. Do not claim any deployment happened unless it actually did.

---

## 3. Technology and constraints

| Concern | Decision |
|---|---|
| Server language | PHP 8.1+ (`declare(strict_types=1);` in every file) |
| Database | MySQL 8 or MariaDB 10.5+, InnoDB, `utf8mb4` |
| DB access | PDO with prepared statements only (`ATTR_EMULATE_PREPARES => false`) |
| Front end | HTML5, hand-written CSS (custom properties), vanilla JS ES modules |
| Charts | Chart.js 4.x, **vendored** in `public/assets/vendor/` (no CDN). Record the version in `public/assets/vendor/VERSIONS.md` |
| Icons | Inline SVG sprite (no icon font, no CDN) |
| Fonts | System font stack, no external requests |
| External requests | Only server-side cURL to `disease.sh`. The browser makes **no** third-party requests |
| Test tooling | Node 18+ (`node --test` or a plain script) for the JS regex runner, PHP CLI for the PHP runners, `curl` for smoke tests. Playwright optional |
| Secrets | None in the repo. DB credentials in `config/config.local.php` (git-ignored); commit `config/config.example.php` only |

---

## 4. Repository structure

```
covid19-portal/
├── public/                          # web root (point Apache/php -S here)
│   ├── index.php                    # Home
│   ├── statistics.php
│   ├── prevention.php
│   ├── symptoms.php
│   ├── vaccination.php
│   ├── myths.php
│   ├── helplines.php
│   ├── community.php
│   ├── program-registration.php
│   ├── signup.php
│   ├── login.php
│   ├── logout.php                   # POST only
│   ├── profile.php
│   ├── quality-lab.php
│   ├── admin/
│   │   └── comments.php             # stretch (FR-13)
│   ├── api/
│   │   ├── stats.php                # global | countries | history
│   │   ├── comments.php             # GET list, POST create, POST delete
│   │   ├── check-username.php       # availability (rate-limited)
│   │   ├── patterns.php             # serves config/patterns.json
│   │   └── regex-cases.php          # serves tests/regex-cases.json
│   └── assets/
│       ├── css/  tokens.css  base.css  layout.css  components.css  pages.css
│       ├── js/   app.js  validate.js  forms.js  stats.js  charts.js  comments.js  lab.js  utils.js
│       ├── img/  logo.svg  sprite.svg
│       └── vendor/  chart.umd.min.js  VERSIONS.md
├── includes/
│   ├── bootstrap.php                # strict types, config, session, headers, autoload of helpers
│   ├── db.php                       # PDO factory
│   ├── auth.php                     # current_user(), require_login(), login attempts
│   ├── csrf.php                     # csrf_token(), csrf_verify()
│   ├── validate.php                 # pattern loading + validate_field() + semantic checks
│   ├── helpers.php                  # e(), redirect(), json_response(), flash()
│   ├── covid_client.php             # fetch + cache + fallback
│   ├── layout_header.php
│   └── layout_footer.php
├── config/
│   ├── config.example.php
│   └── patterns.json                # single source of truth for regexes (Section 9)
├── data/
│   ├── content.json                 # informational page content with sources + lastReviewed
│   └── covid-fallback.json          # real snapshot, created at build time only if API reachable
├── database/
│   ├── schema.sql
│   └── create_admin.php             # CLI: prompts for admin credentials; no default password
├── tests/
│   ├── regex-cases.json             # Section 10
│   ├── regex.test.mjs               # Node runner (JS engine)
│   ├── regex_test.php               # PHP runner (PCRE engine)
│   ├── engine-parity.mjs            # runs both, fails if results differ
│   ├── validator_test.php           # semantic validator tests (checkdate, age range, etc.)
│   ├── smoke.sh                     # curl-based HTTP smoke + security tests
│   └── run-all.sh
├── docs/
│   ├── TEST_PLAN.md
│   ├── TEST_CASES.md                # generated/maintained table with IDs (Section 12)
│   ├── TRACEABILITY.md
│   ├── DEFECT_LOG.md
│   └── TEST_REPORT.md               # real results only
├── .gitignore
├── .htaccess                        # deny direct access to non-public dirs if served from project root
├── README.md
└── CLAUDE.md
```

---

## 5. Database design (`database/schema.sql`)

Create exactly this schema (adjust only for syntax errors found while importing; note any change in the README).

```sql
CREATE DATABASE IF NOT EXISTS covid_portal
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE covid_portal;

CREATE TABLE users (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(20)  NOT NULL,
  email         VARCHAR(254) NOT NULL,              -- stored lowercase
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

CREATE TABLE registrations (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id     INT UNSIGNED NOT NULL,
  full_name   VARCHAR(60)  NOT NULL,
  email       VARCHAR(254) NOT NULL,
  phone       VARCHAR(16)  NOT NULL,               -- normalised: digits with optional leading +91
  dob         DATE         NOT NULL,
  gender      ENUM('female','male','other','prefer_not_to_say') NOT NULL,
  pin_code    CHAR(6)      NOT NULL,
  state       VARCHAR(40)  NOT NULL,
  service     ENUM('vaccination_info','awareness_webinar','volunteer','helpline_callback') NOT NULL,
  consent     TINYINT(1)   NOT NULL,
  status      ENUM('active','cancelled') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reg_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT chk_reg_consent CHECK (consent = 1),
  UNIQUE KEY uq_reg_user_service (user_id, service)
) ENGINE=InnoDB;

CREATE TABLE comments (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  body       VARCHAR(2000) NOT NULL,               -- 500 characters max enforced in code; room for multibyte
  status     ENUM('visible','hidden','deleted') NOT NULL DEFAULT 'visible',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  KEY idx_comments_status_created (status, created_at)
) ENGINE=InnoDB;

CREATE TABLE login_attempts (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  username     VARCHAR(20) NOT NULL,
  ip           VARCHAR(45) NOT NULL,
  attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_attempts_lookup (username, ip, attempted_at)
) ENGINE=InnoDB;

CREATE TABLE covid_cache (
  cache_key  VARCHAR(80) NOT NULL PRIMARY KEY,
  payload    MEDIUMTEXT  NOT NULL,
  fetched_at TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
```

Rules:
- Data minimisation: the registration form collects **no** medical history. Do not add health-condition fields.
- No default admin account in SQL. `database/create_admin.php` is a CLI script that prompts for a username/email/password (validated with the same patterns) and inserts an admin.
- Delete old `login_attempts` rows (older than 24 h) opportunistically on login.

---

## 6. Pages and behaviour

| Route | Auth | What it does |
|---|---|---|
| `/` | public | Hero, global stat cards (count-up animation), 30-day trend preview, "Stay informed" cards linking to info pages, data source + last-updated line. |
| `/statistics.php` | public | Trend chart (range 30/90/180/365/all; metric Cases/Deaths/Recovered — hide Recovered with an explanatory note if the source reports none), country table (search, continent filter, sort, pagination, "per page" selector), URL query sync (`?q=&continent=&sort=&page=`). |
| `/prevention.php` `/symptoms.php` `/vaccination.php` `/myths.php` `/helplines.php` | public | Content from `data/content.json`; accordions; "Last reviewed" date; source links; medical disclaimer. Myths page has a filter and a "Fact check" accordion. |
| `/community.php` | read public, post needs login | Comment composer (live char counter, validation) + paginated list. Posting uses `fetch` (JSON, CSRF header) with graceful non-JS fallback (normal POST). Own comments show a Delete button. |
| `/program-registration.php` | login required | Registration form (Section 7.4). On success shows a confirmation card with reference `REG-000123` (zero-padded id). Duplicate service → friendly error. |
| `/signup.php` `/login.php` | guest only | Forms with live validation; password show/hide + strength meter; username availability hint. |
| `/logout.php` | POST only | Destroys session, redirects to home with toast. |
| `/profile.php` | login required | Account info, own registrations (cancel button → status `cancelled`), own comments. |
| `/quality-lab.php` | public | Section 10.3. |
| `/admin/comments.php` | admin only (stretch) | List all comments, hide/unhide/delete, filter by status. |

Navigation: Home, Statistics, Learn (dropdown: Prevention, Symptoms, Vaccination, Myths, Helplines), Community, Quality Lab; right side: theme toggle, Login/Sign up or user menu (Profile, Logout). Active link has `aria-current="page"`.

---

## 7. UI / UX specification

### 7.1 Visual direction
Calm, trustworthy public-health look — not alarmist. Clean white/near-white default theme, deep teal + blue accents, generous whitespace but no empty gaps, card-based layout, soft shadows, rounded corners. Dark theme available. Avoid red-heavy "panic" styling: use red only for destructive actions and validation errors.

```css
:root {
  --bg: #f6f9fc;  --surface: #ffffff;  --surface-2: #eef3f8;  --border: #d9e2ec;
  --text: #0f1b2d;  --text-muted: #52637a;
  --primary: #0b7285;  --primary-strong: #095c6b;  --primary-contrast: #ffffff;
  --accent: #2563eb;
  --ok: #15803d;  --warn: #b45309;  --danger: #b91c1c;  --info: #1d4ed8;
  --radius: 14px;  --radius-sm: 8px;
  --shadow: 0 1px 2px rgba(15,27,45,.06), 0 6px 20px rgba(15,27,45,.06);
  --font: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
}
:root[data-theme="dark"] {
  --bg: #0b1220;  --surface: #111a2e;  --surface-2: #16213a;  --border: #24324f;
  --text: #e6ecf8;  --text-muted: #9aa8c4;
  --primary: #38bdf8;  --primary-strong: #7dd3fc;  --primary-contrast: #06202b;
  --accent: #60a5fa;  --ok: #4ade80;  --warn: #fbbf24;  --danger: #f87171;  --info: #93c5fd;
  --shadow: 0 1px 2px rgba(0,0,0,.4), 0 6px 20px rgba(0,0,0,.35);
}
```
- Theme: respect `prefers-color-scheme` on first visit, then persist the choice in `localStorage` (wrap in try/catch). Set `data-theme` before first paint with a tiny inline-free approach (an external `theme-init.js` loaded in `<head>` without `defer`).
- Text contrast ≥ 4.5:1 in both themes (verify the primary-on-surface and muted-on-bg pairs).
- Body 16px, line-height 1.6; headings scale 1.25 steps; max content width 1200px.

### 7.2 Interactive behaviour (all must be real)
- **Stat cards:** count-up animation on first view (disabled under `prefers-reduced-motion`), "+N today" delta chip, tooltip with the exact figure.
- **Trend chart:** range buttons and metric toggle update the chart without a reload; hover tooltip; keyboard-focusable buttons; accessible text summary under the chart (latest value, change over the range) computed from the data.
- **Country table:** debounced search (250 ms) validated with the `countryQuery` pattern (invalid input shows an inline message, no request); continent filter; sortable columns with `aria-sort`; pagination; row click expands a details row (tests, population, per-million figures if present); "Copy row" optional.
- **Accordions:** buttons with `aria-expanded`/`aria-controls`; one-at-a-time optional "Expand all".
- **Forms:** validate on `blur` and on `input` after the first blur; show per-field message (`aria-describedby`), a ✓/✕ icon plus text (never colour alone); submit disabled only while submitting; focus moves to the first invalid field on submit.
- **Password:** show/hide toggle; strength meter computed from the rules (length, upper, lower, digit, special) labelled Weak / Fair / Strong with text.
- **Username availability:** debounced `fetch` to `api/check-username.php` after the pattern passes; shows "Available" / "Already taken" (no email availability check — avoids account enumeration).
- **Comments:** live character counter (`n / 500`), optimistic append on success, rollback + error toast on failure, Delete with confirmation dialog (`<dialog>`), "Load more".
- **Toasts:** non-blocking, `role="status"`, auto-dismiss 4 s, dismiss button.
- **Loading states:** skeleton placeholders while data loads; real error state with a Retry button on failure.
- **Misc:** back-to-top button, sticky header with shadow on scroll, mobile hamburger menu (focus-trapped, Esc closes), print stylesheet for the Prevention checklist.

### 7.3 Layout and responsiveness
- Desktop ≥ 1024px: multi-column grids (stat cards 4-up, content 2-column where useful).
- Tablet 640–1023px: 2-up grids, collapsible nav.
- Mobile < 640px: single column, hamburger nav, tables become stacked cards (label/value rows), touch targets ≥ 44px, no horizontal page scroll at 360px.
- Skip link, landmarks (`header`, `nav`, `main`, `footer`), one `h1` per page, logical heading order, visible `:focus-visible` rings, `lang="en"`.

### 7.4 Programme Registration form fields

| Field | Control | Rule |
|---|---|---|
| Full name | text | `fullName` pattern |
| Email | email | `email` pattern (prefilled from account, editable) |
| Phone | tel | `phoneIN` pattern; server normalises (strip spaces/hyphens, keep optional `+91`) |
| Date of birth | date (+ text fallback) | `dob` pattern **and** semantic checks: real calendar date, not in the future, age 5–120 |
| Gender | select | enum; include "Prefer not to say" |
| PIN code | text | `pinCode` pattern |
| State / UT | select | fixed list of Indian states and UTs defined in `data/content.json`; server verifies membership |
| Service | radio cards | enum (4 values) |
| Consent | checkbox | required: "I agree that my details are stored to contact me about this programme." |

Success: confirmation card with reference number and summary; the page never claims an appointment was booked.

---

## 8. Backend rules (security and quality — each one is also a test)

1. **SQL:** PDO prepared statements only; no string-built SQL anywhere. `PDO::ATTR_ERRMODE => EXCEPTION`, `ATTR_EMULATE_PREPARES => false`.
2. **Output escaping:** every dynamic value printed into HTML goes through `e()` (`htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`). Comments are stored raw and escaped on output; newlines rendered with `nl2br(e($body))`. No HTML or Markdown in comments.
3. **Passwords:** `password_hash($pw, PASSWORD_DEFAULT)`; `password_verify`; `password_needs_rehash` on login. Never log or echo passwords.
4. **Sessions:** `session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>$isHttps])`, `session.use_strict_mode=1`, `session_regenerate_id(true)` on login, 30-minute idle timeout, full destroy on logout.
5. **CSRF:** random 32-byte token per session; required on every POST (hidden field or `X-CSRF-Token` header); compare with `hash_equals`. Failure → HTTP 403.
6. **Login throttling:** after 5 failed attempts per (username, IP) in 15 minutes, reject further attempts with a generic message and HTTP 429. Error text is always "Invalid username or password" (no user enumeration). Add a small constant-time-ish behaviour (run `password_verify` against a dummy hash when the user does not exist).
7. **Validation flow:** client validation is a convenience; **the server always re-validates** every field with the same patterns plus semantic checks, trims input, and rejects on failure with field-level errors (JSON for `fetch`, re-rendered form with preserved safe values for normal POST).
8. **Method checks:** state-changing endpoints accept POST only (405 otherwise). `logout.php` is POST + CSRF.
9. **Authorization:** `require_login()` on profile/registration/comment-create; delete only own comment (or admin); admin pages check `role === 'admin'`. IDs from the client are never trusted for ownership.
10. **Headers** (set in `bootstrap.php`): `X-Content-Type-Options: nosniff`, `Referrer-Policy: no-referrer`, `X-Frame-Options: DENY`, `Permissions-Policy: camera=(), microphone=(), geolocation=()`, and a CSP such as `default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'`. No inline scripts or styles (this is why `theme-init.js` is an external file).
11. **Errors:** `display_errors` off outside development; log to a file; users see a friendly error page with no stack traces or SQL.
12. **Config:** `config/config.local.php` is git-ignored; `config.example.php` documents every key (DB host/name/user/pass, `app_env`, cache TTL, base URL).
13. **Rate limiting:** `check-username.php` and `api/comments.php` POST are limited (e.g. 30/min per session) using a simple session counter.
14. **Uploads:** none. Do not add file uploads.

### 8.1 Internal API contracts (JSON, UTF-8, `Cache-Control: no-store` for user data)

`GET api/stats.php?scope=global`
```json
{ "ok": true, "source": "disease.sh", "updatedAt": "2026-10-14T08:00:00Z", "stale": false,
  "data": { "cases": 0, "deaths": 0, "recovered": 0, "active": 0, "todayCases": 0, "todayDeaths": 0 } }
```
`GET api/stats.php?scope=countries` → `data` is an array of `{country, iso2, continent, cases, deaths, recovered, active, todayCases, todayDeaths, tests, population}`.
`GET api/stats.php?scope=history&days=30|90|180|365|all` → `data` is `{cases:{"M/D/YY":n,…}, deaths:{…}, recovered:{…}}` (normalise dates to ISO `YYYY-MM-DD`).
(The zeros above are shape examples only — real responses carry real values.)

Errors: `{ "ok": false, "error": { "code": "UPSTREAM_UNAVAILABLE", "message": "…" } }` with an appropriate HTTP status.

`GET api/comments.php?page=1` → `{ok, data:{items:[{id,username,body,createdAt,mine}], page, pages}}` (body returned raw; the client inserts it with `textContent`).
`POST api/comments.php` `{action:"create", body}` / `{action:"delete", id}` with CSRF header.
`GET api/check-username.php?u=name` → `{ok:true, valid:true|false, available:true|false|null}`.
`GET api/patterns.php` → contents of `config/patterns.json`. `GET api/regex-cases.php` → contents of `tests/regex-cases.json`.

---

## 9. Regular-expression validation (core SQA deliverable)

### 9.1 Single source of truth — `config/patterns.json`

Use this file exactly. (Backslashes are JSON-escaped.)

```json patterns.json
{
  "username": {
    "pattern": "^[A-Za-z][A-Za-z0-9_]{3,19}$",
    "message": "4–20 characters; start with a letter; letters, numbers and underscore only."
  },
  "fullName": {
    "pattern": "^(?=.{2,60}$)[\\p{L}\\p{M}]+(?:[ '\\-][\\p{L}\\p{M}]+)*$",
    "message": "2–60 letters; single spaces, apostrophes or hyphens between words."
  },
  "email": {
    "pattern": "^(?=.{6,254}$)(?!.*\\.\\.)[A-Za-z0-9](?:[A-Za-z0-9._%+\\-]*[A-Za-z0-9])?@(?:[A-Za-z0-9](?:[A-Za-z0-9\\-]{0,61}[A-Za-z0-9])?\\.)+[A-Za-z]{2,24}$",
    "message": "Enter a valid email address, for example name@example.com."
  },
  "password": {
    "pattern": "^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9\\s])\\S{8,64}$",
    "message": "8–64 characters with an uppercase letter, a lowercase letter, a digit and a special character; no spaces."
  },
  "phoneIN": {
    "pattern": "^(?:\\+91[\\- ]?|0)?[6-9]\\d{9}$",
    "message": "Enter a 10-digit Indian mobile number starting with 6–9 (optional +91 or 0 prefix)."
  },
  "dob": {
    "pattern": "^\\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\\d|3[01])$",
    "message": "Use the format YYYY-MM-DD."
  },
  "pinCode": {
    "pattern": "^[1-9]\\d{5}$",
    "message": "Enter a 6-digit PIN code that does not start with 0."
  },
  "countryQuery": {
    "pattern": "^[\\p{L}\\p{M}][\\p{L}\\p{M} .'\\-]{1,55}$",
    "message": "Search with 2–56 letters; spaces, dots, apostrophes and hyphens allowed."
  },
  "comment": {
    "pattern": "^(?=[\\s\\S]{3,500}$)(?=[\\s\\S]*\\S)(?![\\s\\S]*<[^>]*>)[\\s\\S]*$",
    "message": "3–500 characters, not blank, and no HTML tags."
  }
}
```

What each pattern guarantees:

| Pattern | Accepts | Rejects |
|---|---|---|
| `username` | `john_doe`, `Om123` | starts with digit/underscore, spaces, hyphens, < 4 or > 20 chars |
| `fullName` | `Om Patil`, `Mary-Jane O'Neil`, `José`, `सुरेश पाटिल` | digits, double spaces, leading/trailing hyphen, > 60 chars |
| `email` | `first.last+tag@sub.example.co.in` | `..`, leading/trailing dot in local part, missing TLD, one-letter TLD, spaces |
| `password` | `Passw0rd!` | missing a class, spaces, < 8 or > 64 chars |
| `phoneIN` | `9876543210`, `+91 9876543210`, `09876543210` | first digit 0–5, wrong length, inner spaces |
| `dob` | `2000-01-31` (shape only) | wrong order/separators, month 00/13, day 00/32 |
| `pinCode` | `411001` | leading 0, non-digits, wrong length |
| `countryQuery` | `India`, `Côte d'Ivoire`, `St. Lucia` | digits, `;`, `/`, `..` |
| `comment` | text up to 500 chars (any language) | blank/whitespace only, HTML-tag-like text (`<b>x</b>`), > 500 chars |

### 9.2 How the patterns must be used (cross-engine rules)

- **JavaScript:** `new RegExp(pattern, 'u')`. Always call `.test(value.trim())` only where trimming is intended (passwords are **not** trimmed).
- **PHP:** `preg_match('~' . $pattern . '~Du', $value)`. Treat a return of `false` (e.g. invalid UTF-8) as **invalid**.
  - **`D` is mandatory.** Without it PCRE's `$` matches *before a trailing newline*, so `"abcd\n"` would be accepted as a valid username. This was reproduced while writing this spec (PHP 8.3): of the 124 cases below, 5 trailing-newline cases failed without `D` and 0 failed with it. Cases whose note says `TC-REG-NEWLINE` guard this.
  - **`u` is mandatory too.** It makes `{3,500}` count characters instead of bytes and enables `\p{L}`/`\p{M}`. Without it, 5 cases failed (accented and Devanagari names, `Côte d'Ivoire`, `Türkiye`, and the 500-character comment containing `é`).
  - With `Du`, PHP and JavaScript gave identical results on all 124 cases (Node 22, PHP 8.3).
- **ReDoS guard:** the `comment` pattern deliberately checks length **first** (`(?=[\s\S]{3,500}$)`), then blankness, then tags. The earlier ordering (tag check first) took **≈ 7.5 s** on a 100,000-character string of `<` in Node because of quadratic backtracking; the final ordering takes about 1 ms on the same input (worst case across all nine patterns and five hostile inputs: 1.3 ms in Node, 0.5 ms in PHP). Also cap request body size and `strlen()` before any regex on the server. `TC-REG-REDOS-*` tests assert each pattern finishes a 100 000-character hostile input in under 200 ms.
- The regex layer checks **shape only**. Semantic rules live in `includes/validate.php`:
  - `dob`: `checkdate()` (so `2023-02-31` is rejected), not in the future, age between 5 and 120.
  - `email`: lowercase before storing; `mb_strlen` ≤ 254.
  - `phoneIN`: normalise to digits (keep a leading `+91` if given).
  - `comment`: `trim()`, normalise CRLF to LF, `mb_strlen` ≤ 500 (belt-and-braces with the regex).
  - `state`, `service`, `gender`: membership in fixed allow-lists.
- Known limitations to document in the README: usernames are ASCII-only by design; email pattern is intentionally stricter than RFC 5322 (no quoted local parts, no IP-literal domains, no internationalised domains); `dob` regex alone does not know about leap years.

---

## 10. Regex test cases and the Quality Lab

### 10.1 Test data — `tests/regex-cases.json`

Format: each case is `{ "in": "<string>", "ok": true|false }`, or a generated input
`{ "gen": { "prefix": "", "repeat": "x", "times": 3, "suffix": "" }, "ok": … }` meaning `prefix + repeat×times + suffix`. Optional `"note"` explains the case. Runners expand `gen` before testing. IDs are assigned by the runners as `TC-REG-<FIELD>-<NN>` in file order (two digits, 1-based).

```json regex-cases.json
{
  "username": [
    { "in": "john_doe", "ok": true },
    { "in": "Om123", "ok": true },
    { "in": "abcd", "ok": true, "note": "minimum length 4" },
    { "gen": { "prefix": "A", "repeat": "b", "times": 19 }, "ok": true, "note": "maximum length 20" },
    { "in": "abc", "ok": false, "note": "too short" },
    { "gen": { "prefix": "A", "repeat": "b", "times": 20 }, "ok": false, "note": "21 characters" },
    { "in": "1abcd", "ok": false, "note": "starts with digit" },
    { "in": "_abcd", "ok": false, "note": "starts with underscore" },
    { "in": "john doe", "ok": false },
    { "in": "john-doe", "ok": false },
    { "in": "", "ok": false },
    { "in": "abcd\n", "ok": false, "note": "TC-REG-NEWLINE: trailing newline must be rejected (PCRE D flag)" },
    { "in": "admin<script>", "ok": false },
    { "in": "bob'; DROP TABLE users;--", "ok": false, "note": "SQL injection attempt" }
  ],
  "fullName": [
    { "in": "Om Patil", "ok": true },
    { "in": "Mary-Jane O'Neil", "ok": true },
    { "in": "Al", "ok": true, "note": "minimum length 2" },
    { "in": "José", "ok": true, "note": "accented letter" },
    { "in": "सुरेश पाटिल", "ok": true, "note": "Devanagari with vowel signs (\\p{M})" },
    { "in": "Anna Maria Lopez", "ok": true },
    { "in": "A", "ok": false },
    { "in": "John3", "ok": false },
    { "in": "John  Doe", "ok": false, "note": "double space" },
    { "in": "-John", "ok": false },
    { "in": "John-", "ok": false },
    { "gen": { "repeat": "A", "times": 61 }, "ok": false },
    { "in": "", "ok": false },
    { "in": "John\n", "ok": false, "note": "TC-REG-NEWLINE" },
    { "in": "<b>Bob</b>", "ok": false }
  ],
  "email": [
    { "in": "user@example.com", "ok": true },
    { "in": "first.last+tag@sub.example.co.in", "ok": true },
    { "in": "a@b.co", "ok": true, "note": "shortest realistic address" },
    { "in": "user@example", "ok": false, "note": "no TLD" },
    { "in": "user@@example.com", "ok": false },
    { "in": "user@.com", "ok": false },
    { "in": "user..name@example.com", "ok": false, "note": "consecutive dots" },
    { "in": "user.@example.com", "ok": false },
    { "in": ".user@example.com", "ok": false },
    { "in": "user name@example.com", "ok": false },
    { "in": "user@exam_ple.com", "ok": false },
    { "in": "user@example.c", "ok": false, "note": "one-letter TLD" },
    { "in": "a@b.c", "ok": false },
    { "in": "", "ok": false },
    { "in": "user@example.com\n", "ok": false, "note": "TC-REG-NEWLINE" }
  ],
  "password": [
    { "in": "Passw0rd!", "ok": true },
    { "in": "Abcdef1#", "ok": true, "note": "minimum length 8" },
    { "in": "Str0ng&Secure#2026", "ok": true },
    { "gen": { "repeat": "P", "times": 30, "suffix": "a1!" }, "ok": true, "note": "33 characters" },
    { "in": "password", "ok": false, "note": "only lowercase" },
    { "in": "PASSWORD1!", "ok": false, "note": "no lowercase" },
    { "in": "Password!", "ok": false, "note": "no digit" },
    { "in": "Password1", "ok": false, "note": "no special" },
    { "in": "Pa1!", "ok": false, "note": "too short" },
    { "in": "Pass word1!", "ok": false, "note": "contains space" },
    { "gen": { "prefix": "Aa1!", "repeat": "x", "times": 61 }, "ok": false, "note": "65 characters" },
    { "in": "", "ok": false }
  ],
  "phoneIN": [
    { "in": "9876543210", "ok": true },
    { "in": "+919876543210", "ok": true },
    { "in": "+91 9876543210", "ok": true },
    { "in": "+91-9876543210", "ok": true },
    { "in": "09876543210", "ok": true },
    { "in": "5876543210", "ok": false, "note": "first digit must be 6-9" },
    { "in": "987654321", "ok": false, "note": "9 digits" },
    { "in": "98765432101", "ok": false, "note": "11 digits without prefix" },
    { "in": "+91  9876543210", "ok": false, "note": "two spaces" },
    { "in": "98765 43210", "ok": false, "note": "inner space" },
    { "in": "abcdefghij", "ok": false },
    { "in": "+91\n9876543210", "ok": false },
    { "in": "9876543210\n", "ok": false, "note": "TC-REG-NEWLINE" },
    { "in": "", "ok": false }
  ],
  "dob": [
    { "in": "2000-01-31", "ok": true },
    { "in": "1999-12-01", "ok": true },
    { "in": "2024-02-29", "ok": true, "note": "valid leap day" },
    { "in": "2023-02-31", "ok": true, "note": "shape-valid only; the validator must reject it with checkdate() (see validator_test.php)" },
    { "in": "2000-13-01", "ok": false },
    { "in": "2000-00-10", "ok": false },
    { "in": "2000-01-32", "ok": false },
    { "in": "2000-1-1", "ok": false },
    { "in": "01-01-2000", "ok": false },
    { "in": "2000/01/01", "ok": false },
    { "in": "2000-01-01\n", "ok": false, "note": "TC-REG-NEWLINE" },
    { "in": "", "ok": false }
  ],
  "pinCode": [
    { "in": "411001", "ok": true },
    { "in": "422001", "ok": true },
    { "in": "999999", "ok": true },
    { "in": "011001", "ok": false, "note": "leading zero" },
    { "in": "41100", "ok": false },
    { "in": "4110011", "ok": false },
    { "in": "41100a", "ok": false },
    { "in": "411 001", "ok": false },
    { "in": "", "ok": false }
  ],
  "countryQuery": [
    { "in": "India", "ok": true },
    { "in": "United States", "ok": true },
    { "in": "Cote d'Ivoire", "ok": true },
    { "in": "Côte d'Ivoire", "ok": true, "note": "accented" },
    { "in": "Türkiye", "ok": true },
    { "in": "Guinea-Bissau", "ok": true },
    { "in": "St. Lucia", "ok": true },
    { "in": "Us", "ok": true, "note": "minimum length 2" },
    { "gen": { "repeat": "A", "times": 56 }, "ok": true, "note": "maximum length 56" },
    { "in": "I", "ok": false },
    { "in": "1India", "ok": false },
    { "in": "India123", "ok": false },
    { "in": "India;DROP TABLE", "ok": false },
    { "in": "../etc/passwd", "ok": false },
    { "gen": { "repeat": "A", "times": 57 }, "ok": false },
    { "in": "", "ok": false }
  ],
  "comment": [
    { "in": "Stay safe everyone!", "ok": true },
    { "in": "Thanks for the update.", "ok": true },
    { "in": "abc", "ok": true, "note": "minimum length 3" },
    { "in": "नमस्ते, सुरक्षित रहें", "ok": true, "note": "non-Latin text" },
    { "in": "I like a<b", "ok": true, "note": "lone < is allowed; only tag-like <...> is rejected" },
    { "in": "line one\nline two", "ok": true, "note": "newlines allowed" },
    { "gen": { "repeat": "x", "times": 500 }, "ok": true, "note": "exactly 500" },
    { "gen": { "repeat": "x", "times": 499, "suffix": "é" }, "ok": true, "note": "500 characters including a multibyte character (requires u flag in PHP)" },
    { "in": "ab", "ok": false },
    { "in": "   ", "ok": false, "note": "whitespace only" },
    { "in": "<script>alert(1)</script>", "ok": false },
    { "in": "hello <b>world</b>", "ok": false },
    { "in": "line one\nline <img src=x>", "ok": false, "note": "tag on second line" },
    { "in": "5 < 6 and 7 > 3", "ok": false, "note": "documented false positive: looks like a tag to the pattern" },
    { "gen": { "repeat": "x", "times": 501 }, "ok": false },
    { "gen": { "repeat": "x", "times": 499, "suffix": "éx" }, "ok": false, "note": "501 characters" },
    { "in": "", "ok": false }
  ]
}
```

### 10.2 Automated runners (must all exist and pass)

- `tests/regex.test.mjs` — loads both JSON files, expands `gen`, runs every case against the JS engine (`'u'` flag), prints a table, exits non-zero on any mismatch.
- `tests/regex_test.php` — same for PHP (`~…~Du`), including the "false means invalid" rule.
- `tests/engine-parity.mjs` — runs both runners (spawn `php`), compares the per-case results, and fails if the two engines ever disagree. If `php` is not on `PATH`, report **not run (reason)**.
- **Performance tests** (inside both runners): for every pattern, time a 100 000-character hostile input (`"<"×100000`, `"a."×50000`, `"a "×50000 + "1"`, `"A"×100000`); each must complete in < 200 ms. IDs `TC-REG-REDOS-<FIELD>`.
- **Property checks** (small, deterministic loop with a seeded PRNG): for random strings of printable ASCII up to length 100, JS and PHP results agree.
- `tests/validator_test.php` — semantic layer: `2023-02-31` rejected; future DOB rejected; age < 5 and > 120 rejected; `+91 98765-43210`-style inputs normalise correctly; unknown `state`/`service` rejected; comment CRLF normalisation.
- `tests/run-all.sh` — runs everything, prints a summary, exit code 0 only if all pass.

### 10.3 Quality Lab page (`/quality-lab.php`, FR-10)

An interactive showcase of the testing work:
1. **Live pattern tester:** choose a field (dropdown), see its pattern and message, type input, and get an instant ✓/✕ with the engine noted (browser JS). Include quick buttons "Insert a valid sample" / "Insert an invalid sample" taken from the case file.
2. **Suite runner:** "Run all tests" executes every case in the browser against `api/patterns.php` + `api/regex-cases.php`, shows a progress-free summary (total / passed / failed) and a filterable table (ID, field, input preview with control characters made visible, expected, actual, result). Filter chips: All / Passed / Failed / per field.
3. **Export:** download results as CSV and JSON (real files generated client-side).
4. **Explainer cards:** short notes on the `D` flag, the ReDoS ordering fix, and the shape-vs-semantic split — written from the real findings in Section 9.
5. It runs the JS engine only and says so; PHP results come from `tests/regex_test.php` and are documented in `docs/TEST_REPORT.md`.

No server secrets or user data are exposed on this page.

---

## 11. COVID data handling (`includes/covid_client.php`)

- Base URL `https://disease.sh/v3/covid-19/`: `all`, `countries`, `historical/all?lastdays=<N|all>`.
- **Verify at build time** (curl from the workspace) that these endpoints respond and match the shapes in Section 8.1. If an endpoint changed or is unavailable, adapt the client to what actually responds, or ship the empty-state path and state the limitation in the README. Do not assume the API is available or unchanged.
- cURL options: 5 s connect timeout, 10 s total, `CURLOPT_SSL_VERIFYPEER = true`, follow redirects off, max response size guard. Validate the decoded JSON shape before caching.
- Cache in `covid_cache`: TTL 1 hour for `all`/`countries`, 6 hours for `historical`. Serve cache while fresh; on upstream failure serve stale cache with `"stale": true` and a visible banner "Showing data last updated <time>".
- `data/covid-fallback.json`: created by a one-off script **only from a real response**, stamped with its fetch time. If it cannot be created, omit the file and rely on the empty state.
- Display the source name and the `updatedAt` value exactly as received. COVID-19 reporting has changed over time; some series (for example recovered counts) may be missing or frozen — detect that from the data and label it ("not reported by the source"), never fill gaps.
- Never call the API from the browser. Never expose server errors to users.

### Informational content (`data/content.json`)
- Every page's content is written from official public-health guidance (WHO and the Indian Ministry of Health and Family Welfare), cited with link, and carries a `lastReviewed` date. Verify each link loads when you build.
- Do not invent statistics, helpline numbers, or medical claims. For helplines, record each number with its official source URL and a `lastVerified` date; if you cannot verify a number, leave it out.
- Every informational page and the registration form show: "This website is for general information and does not replace professional medical advice. In an emergency, contact your local emergency services."

---

## 12. Testing and QA deliverables

### 12.1 Documents (real content, no placeholders)

- `docs/TEST_PLAN.md` — objectives, scope (in/out), test levels (unit/regex, validator, integration/API, UI, security, usability), approach, environment (OS, PHP, MySQL, browser versions actually used), entry/exit criteria, risks, roles, schedule, defect severity/priority scale, deliverables.
- `docs/TEST_CASES.md` — table with: ID, requirement ID, title, preconditions, steps, test data, expected result, actual result, status. Regex rows come from the case file (one row per case, same IDs as the runners); the rest are listed below.
- `docs/TRACEABILITY.md` — matrix FR-01…FR-13 × test case IDs; every requirement must have at least one test.
- `docs/DEFECT_LOG.md` — defects actually found while building/testing (ID, title, severity, steps, expected, actual, status, fix commit/note). Include the two real findings from Section 9 (ReDoS ordering, PCRE `$` newline) as the first entries with their resolutions. Add any further real ones; do not invent defects.
- `docs/TEST_REPORT.md` — summary table (executed / passed / failed / not run), per-suite results with the actual command output summaries, environment, known issues, conclusion. Only real results.

### 12.2 Required non-regex test cases (minimum set; add more as needed)

| ID | Req | Test | Expected |
|---|---|---|---|
| TC-AUTH-01 | FR-04 | Sign up with valid data | Account created, redirected to login/profile, password stored as hash (not plain text) |
| TC-AUTH-02 | FR-04 | Sign up with duplicate username (different case) | Rejected with field error; no second row |
| TC-AUTH-03 | FR-04 | Sign up with duplicate email | Rejected; no second row |
| TC-AUTH-04 | FR-04 | Sign up with each invalid field (loop over invalid regex cases) | Field-level error, no row inserted |
| TC-AUTH-05 | FR-05 | Login valid | Session started, session id changed from pre-login id |
| TC-AUTH-06 | FR-05 | Login wrong password vs unknown user | Identical generic message and similar status |
| TC-AUTH-07 | FR-05 | 6th failed attempt within 15 min | HTTP 429, lockout message; correct password during lockout also rejected |
| TC-AUTH-08 | FR-05 | Logout via GET | Not allowed (405); POST with CSRF works and destroys session |
| TC-AUTH-09 | FR-05 | Idle > 30 min (simulate by editing session timestamp) | Redirected to login |
| TC-CMT-01 | FR-07 | Post valid comment while logged in | Appears at top; stored once |
| TC-CMT-02 | FR-07 | Post while logged out | 401/redirect; nothing stored |
| TC-CMT-03 | FR-07 | Post `<script>alert(1)</script>` directly to the API (bypassing the client) | Rejected by server validation; if present in the DB by any other route, rendered escaped |
| TC-CMT-04 | FR-07 | Comment 500 vs 501 characters | 500 accepted, 501 rejected (client and server) |
| TC-CMT-05 | FR-07 | Delete another user's comment by forged id | 403, row unchanged |
| TC-FRM-01 | FR-08 | Submit valid registration | Row saved, reference number shown |
| TC-FRM-02 | FR-08 | Same service twice | Friendly duplicate error; one row |
| TC-FRM-03 | FR-08 | Each invalid field (loop) | Field-level error; no row |
| TC-FRM-04 | FR-08 | DOB `2023-02-31`, future DOB, age 4 and 121 | Rejected by semantic validation |
| TC-FRM-05 | FR-08 | Consent unchecked / tampered state or service value | Rejected |
| TC-FRM-06 | FR-08 | Not logged in | Redirect to login |
| TC-SEC-01 | FR-04..08 | SQL injection strings (`' OR 1=1 --`, `"; DROP TABLE users;--`) in every text field and in `api/comments.php` | No SQL error, no data leak, tables intact |
| TC-SEC-02 | FR-07 | Stored XSS payload via every route | Always escaped on output; CSP present |
| TC-SEC-03 | all POST | POST without/with wrong CSRF token | 403 |
| TC-SEC-04 | all | Response headers | All headers in Section 8 item 10 present |
| TC-SEC-05 | FR-06 | Access profile/registration pages logged out; access admin as normal user | Redirect / 403 |
| TC-SEC-06 | all | Cookie flags | `HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS |
| TC-API-01 | FR-01/02 | `api/stats.php` each scope | Valid JSON in the documented shape |
| TC-API-02 | FR-12 | Simulate upstream failure (invalid base URL via config) | Stale cache with banner, or empty state; never fake numbers; no PHP warnings |
| TC-API-03 | FR-02 | `scope=history&days=abc` and other malformed input | 400 with JSON error |
| TC-UI-01 | FR-02 | Country search, sort, filter, pagination | Table updates correctly; URL reflects state |
| TC-UI-02 | FR-02 | Chart range / metric controls | Chart data changes; summary text updates |
| TC-UI-03 | FR-11 | Theme toggle persists after reload | Yes |
| TC-UI-04 | FR-11 | 360 / 768 / 1280 px | No horizontal scroll; nav collapses; tables stack |
| TC-UI-05 | FR-11 | Keyboard-only walkthrough (nav, tabs/accordions, forms, dialogs) | Everything reachable; focus visible; Esc closes menu/dialog |
| TC-UI-06 | FR-10 | Quality Lab run-all and CSV/JSON export | Counts match the case file; files valid |
| TC-DB-01 | all | Import `schema.sql` on a clean server | No errors; all five tables exist |
| TC-DB-02 | FR-08 | Constraint checks (insert consent = 0, duplicate user+service, orphan FK) | DB rejects |

### 12.3 Execution rules
- Automate everything that can be automated with `tests/run-all.sh` and `tests/smoke.sh` (use `php -S 127.0.0.1:8000 -t public` plus a MySQL/MariaDB instance). For browser-level checks use Playwright if it installs; otherwise document manual steps and mark them **not run**.
- If MySQL/MariaDB cannot be installed or started in the workspace, say so; run the regex, validator-without-DB and static checks, and mark every DB-dependent test **not run (no database available)**. Do not simulate DB results.
- Re-run a test after fixing a defect and record both results in the defect log.
- Never invent a result. The final summary must separate **run and passed**, **run and failed**, and **not run**.

---

## 13. Local setup (document these exact steps in the README)

1. Install PHP 8.1+ and MySQL 8 / MariaDB (or XAMPP/WAMP).
2. Create the database: `mysql -u root -p < database/schema.sql`.
3. Copy `config/config.example.php` to `config/config.local.php` and set the DB credentials.
4. (Optional) `php database/create_admin.php` to create an admin interactively.
5. Run: `php -S 127.0.0.1:8000 -t public` and open `http://127.0.0.1:8000`.
6. XAMPP: place the project in `htdocs`, point an Apache VirtualHost/Alias at `public/`, enable `mod_rewrite` only if used, make sure `curl` and `pdo_mysql` extensions are enabled in `php.ini`.
7. Run the tests: `bash tests/run-all.sh`.

Deployment: shared PHP hosting steps (upload, set document root to `public/`, import schema, create `config.local.php`, enable HTTPS). Vercel is not suitable (ADR-005). State what was and was not actually deployed.

---

## 14. README requirements
Overview, screenshots (only if captured), feature list mapped to FR IDs, architecture + ADR summary, setup (Section 13), project structure, regex patterns with a short explanation and the cross-engine rules, how to run each test suite, latest **actual** test results, security measures, known limitations (from Section 9 and the data source), data source attribution, and the medical disclaimer.

---

## 15. Code quality rules
- `declare(strict_types=1)`; small single-purpose functions; clear names; comments only for non-obvious logic (regex flags, ReDoS ordering, throttling).
- No inline JS/CSS in PHP templates; no `eval`; no global mutable state in JS outside module scope.
- One place per concern: validation in `validate.php`/`validate.js`, queries in small functions, output escaping via `e()`.
- Every `fetch` and PDO call has error handling with a user-friendly message.
- No dead code, commented-out blocks, or unused files. Keep dependencies to Chart.js only.

---

## 16. Required workflow

1. **Inspect** — read this file; check the workspace (PHP, MySQL/MariaDB, Node, curl availability); verify the data API responds.
2. **Plan** — a short plan (≤ 15 lines) listing files to create. Proceed without waiting unless a real blocker needs input.
3. **Implement in this order:** schema + bootstrap/db/helpers → validation (`patterns.json`, `validate.php`, `validate.js`) → regex test data + runners (run them early) → auth → pages and UI → COVID client + API → comments → registration → Quality Lab → admin (stretch).
4. **Verify** — run Section 12 checks; fix failures; re-run.
5. **Document** — README and all `docs/*` with real results.
6. **Summarize** — files created, features delivered, test results table (run/passed/failed/not run), defects found and fixed, known limitations, setup steps.

---

## 17. Definition of done

- All pages in Section 6 work end to end against MySQL; accounts, comments, and registrations persist.
- Validation is enforced on both client and server using the shared patterns; the two regex engines agree on every case in `regex-cases.json`.
- Security rules in Section 8 are implemented and covered by tests.
- The UI is responsive, themed, keyboard accessible, and interactive as specified, with no dead controls.
- COVID numbers are real, labelled with source and update time; failures degrade gracefully without invented data.
- Quality Lab runs the suite in the browser and exports results.
- `docs/` contains a real test plan, test cases, traceability matrix, defect log, and test report.
- README is accurate; anything not run or not verified is explicitly listed as such.