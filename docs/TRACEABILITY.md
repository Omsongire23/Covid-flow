# Requirements Traceability Matrix (RTM)
## Covid-flow — Software Testing & Quality Assurance (STQA)

**Analysis Date:** 2026-10-31  
**Document Version:** 2.0.0 (Python + Selenium 4)  
**Status:** Complete (100% Traceability Verified)  

---

### Traceability Mapping: Requirements &rarr; Architecture &rarr; Automated Tests

| Req ID | Requirement Description | Implementation Components | Database / Services | Automated Test Cases | Verification Status |
|---|---|---|---|---|---|
| **FR-01** | **Home Dashboard**<br>Display global cases, active, recovered, deaths, daily change, timestamp, and source. | `app/routes.py` (`index()`)<br>`app/templates/index.html`<br>`app/templates/base.html` | `app/covid_api.py`<br>`covid_cache` table<br>`data/covid-fallback.json` | `TC-SEL-01`<br>`TC-SEL-02`<br>`TC-SEL-03`<br>`TC-INT-18`<br>`TC-UNIT-18` | **VERIFIED (PASS)** |
| **FR-02** | **Statistics & Visualizations**<br>Interactive Chart.js trends, country table, search, and metric toggles. | `app/routes.py` (`statistics()`)<br>`app/templates/statistics.html`<br>`app/static/vendor/chart.umd.min.js` | `app/covid_api.py` (`get_historical_stats`, `get_country_stats`) | `TC-SEL-05`<br>`TC-SEL-06`<br>`TC-INT-19`<br>`TC-INT-20`<br>`TC-UNIT-19`<br>`TC-UNIT-21` | **VERIFIED (PASS)** |
| **FR-03** | **Health Information Pages**<br>Prevention, Symptoms, Vaccination, Myths vs Facts, Helplines with citations. | `app/routes.py`<br>`app/templates/prevention.html`<br>`app/templates/symptoms.html`<br>`app/templates/vaccination.html`<br>`app/templates/myths.html`<br>`app/templates/helplines.html` | `app/content.py`<br>`data/content.json` | `TC-SEL-04`<br>`TC-INT-21`<br>`TC-INT-22`<br>`TC-INT-23`<br>`TC-INT-24`<br>`TC-INT-25` | **VERIFIED (PASS)** |
| **FR-04** | **User Registration**<br>Form validation, duplicate checks, password hashing, inline errors. | `app/auth.py` (`register()`)<br>`app/templates/register.html`<br>`app/validators.py` | `users` table<br>`werkzeug.security` (`generate_password_hash`) | `TC-SEL-07`<br>`TC-SEL-08`<br>`TC-SEL-09`<br>`TC-INT-01`<br>`TC-INT-02`<br>`TC-INT-03` | **VERIFIED (PASS)** |
| **FR-05** | **Authentication & Sessions**<br>Login, logout, throttling, session regeneration, password check. | `app/auth.py` (`login()`, `logout()`)<br>`app/templates/login.html` | `login_attempts` table<br>`check_login_throttle`<br>`werkzeug.security` | `TC-SEL-10`<br>`TC-SEL-11`<br>`TC-SEL-12`<br>`TC-INT-04`<br>`TC-INT-05`<br>`TC-INT-06` | **VERIFIED (PASS)** |
| **FR-06** | **User Profile**<br>User details, comment history, programme registrations, password change. | `app/auth.py` (`profile()`)<br>`app/templates/profile.html` | `users`, `comments`, `registrations` tables | `TC-SEL-09`<br>`TC-INT-07`<br>`TC-INT-08` | **VERIFIED (PASS)** |
| **FR-07** | **Community Forum**<br>Public view, authenticated posting, 500-char limit, XSS protection, own deletion. | `app/routes.py` (`community()`, `delete_comment()`)<br>`app/templates/community.html` | `comments` table with foreign key to `users` | `TC-SEL-13`<br>`TC-SEL-14`<br>`TC-SEL-15`<br>`TC-SEL-16`<br>`TC-INT-09`<br>`TC-INT-11`<br>`TC-INT-12`<br>`TC-INT-13` | **VERIFIED (PASS)** |
| **FR-08** | **Programme Registration**<br>Multi-field enrollment form, duplicate check, unique reference generation. | `app/routes.py` (`programme()`)<br>`app/templates/programme.html` | `registrations` table<br>`ref_number` generation (`COV-2026-XXXXX`) | `TC-SEL-17`<br>`TC-INT-14`<br>`TC-INT-15`<br>`TC-INT-16`<br>`TC-INT-17` | **VERIFIED (PASS)** |
| **FR-09** | **Centralized Validation**<br>All 9 regular-expression categories, semantic checks, boundary rules. | `app/validators.py`<br>`config/patterns.json` | Python `regex` module with strict end-of-string anchors (`\Z`) | `TC-UNIT-01` to `TC-UNIT-16`<br>`Quality Lab Suite (124 cases)` | **VERIFIED (PASS)** |
| **FR-10** | **Quality Engineering Lab**<br>Interactive pattern tester, automated suite execution, CSV/JSON export. | `app/routes.py` (`quality_lab()`, API endpoints)<br>`app/templates/quality_lab.html` | `tests/regex-cases.json`<br>`api/quality-lab/run-suite` | `TC-SEL-18`<br>`Quality Lab Suite (124 cases)` | **VERIFIED (PASS)** |
| **FR-11** | **UI/UX & Accessibility**<br>Clinical blue/teal design, responsive layout, dark/light theme, focus states. | `app/static/css/tokens.css`<br>`app/static/css/layout.css`<br>`app/static/css/components.css`<br>`app/templates/base.html` | Vanilla CSS tokens & semantic HTML5 elements | `TC-SEL-01`<br>`TC-SEL-04`<br>`TC-SEL-11` | **VERIFIED (PASS)** |
| **FR-12** | **COVID Data Resilience**<br>Honest fallback data labelling, timeouts, network error recovery. | `app/covid_api.py`<br>`data/covid-fallback.json` | `covid_cache` SQLite table with provenance metadata | `TC-UNIT-17`<br>`TC-UNIT-18`<br>`TC-SEL-03` | **VERIFIED (PASS)** |
