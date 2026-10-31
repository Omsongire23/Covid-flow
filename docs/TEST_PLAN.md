# Software Testing and Quality Assurance Test Plan
## Covid-flow — COVID-19 Health Intelligence & Testing Portal

**Project:** Covid-flow COVID-19 Information Portal  
**Course:** Software Testing and Quality Assurance (STQA) — Mini Project  
**Specification Reference:** `CLAUDE.md`  
**Document Version:** 2.0.0 (Python + Selenium 4 Edition)  
**Status:** Approved & 100% Executed  

---

## 1. Introduction and Objectives

The objective of this Software Quality Assurance test project is to verify and validate the functional correctness, regular expression robustness, security compliance, and end-to-end user workflows of the **Covid-flow Portal**.

The system satisfies all mandatory requirements specified in the STQA project specification:
> *"Develop a dynamic website providing COVID-19 information using a clean web interface, a Python backend, and an SQLite database. Implement Selenium 4 browser automation, centralized regular expression validation, test case design, defect tracking, and requirements traceability."*

### Key Test Objectives:
1. **Regular Expression Validation Rigor:** Verify that client and server input validation strictly adheres to the single source of truth (`config/patterns.json`) across all 9 validation categories.
2. **Centralized Python Validation Engine:** Guarantee that the Python `regex` module correctly enforces boundary values, Unicode characters, and prevents trailing newline injections (`\Z`).
3. **Automated Selenium 4 E2E Testing:** Execute real browser journeys across all critical features (Dashboard, Statistics, Auth, Community, Programme Registration, Quality Lab) using the Page Object Model (POM).
4. **Data Integrity & SQLite Persistence:** Validate SQLite schema constraints, parameterized queries (`?`), transactional boundaries, and foreign key enforcement (`PRAGMA foreign_keys = ON`).
5. **Defensive Security & Session Hardening:** Ensure SQL injection prevention, XSS sanitization (Jinja2 escaping + regex tag rejection), CSRF token enforcement on all state-changing endpoints, and brute-force login throttling.
6. **Graceful Upstream Handling:** Ensure that failure of upstream third-party APIs (`disease.sh`) degrades gracefully to verified local cache or an honest offline archive without fabricating numbers.

---

## 2. Scope

### In-Scope
- **Dynamic Surveillance:** Global summary, 30-day historical trends (cases vs deaths with Chart.js), and country surveillance table with search and filtering.
- **Informational Guidance:** Prevention, Symptoms, Vaccination, Myths vs. Facts, and Helplines with authoritative WHO / MoHFW citations.
- **User Authentication & Authorization:** User registration, login, session management, brute-force lockout, profile management, and password change.
- **Programme Registration:** Enrollment form with live regex validation, duplicate prevention, and unique reference generation (`COV-2026-XXXXX`).
- **Community Forum:** Paginated comment feed, character counting, client & server validation, and owner comment deletion.
- **Quality Lab:** Interactive in-browser pattern tester, full automated suite runner, CSV/JSON report exports, and STQA explainer cards.
- **Automated Test Suites:** Pytest unit tests (`tests/unit/`), integration tests (`tests/integration/`), and Selenium 4 headless Chrome browser tests (`tests/selenium/`).

### Out-of-Scope
- Commercial payment gateways.
- SMS / SMTP physical transmission.
- Medical appointment scheduling (all registrations are for informational webinars and guidance sessions).
- Heavy enterprise frameworks (React, Next.js, Node.js, PHP, Docker).

---

## 3. Test Levels and Methodologies

| Test Level | Scope & Techniques | Tooling Used |
|---|---|---|
| **Unit / Regex Testing** | Boundary value analysis, equivalence partitioning, trailing newline injections, Unicode character handling, and ReDoS defense. | Python `regex`, `pytest` |
| **Semantic Validator Testing** | Calendar date integrity (`datetime.date`), leap day acceptance, past date rules, phone normalization, and PIN code checks. | Python standard library, `pytest` |
| **Integration & Route Testing** | Verification of internal Flask routes, SQLite database operations, session cookies, CSRF protection, and JSON API endpoints. | Flask test client, `pytest` |
| **Selenium 4 E2E Testing** | Real browser rendering, user navigation, form validation, dynamic Chart.js rendering, asynchronous Quality Lab test execution, and screenshot capture. | Selenium 4, ChromeDriver, Google Chrome (`--headless=new`), `pytest-html` |
| **Security & Penetration Testing** | CSRF token bypass attempts, SQL injection payloads (`' OR 1=1 --`), Stored XSS prevention (`<script>alert(1)</script>`), and session fixation guards. | Flask test client, Selenium assertions |

---

## 4. Test Environment and Infrastructure

- **Operating System:** Linux (Ubuntu 24.04 LTS / generic x86_64)
- **Programming Language:** Python 3.13.9
- **Web Framework:** Flask 3.1.3 + Jinja2 + Werkzeug
- **Database Engine:** SQLite 3 with foreign key enforcement
- **Browser Automation:** Selenium 4.52.0 + Google Chrome 154.0.8037.57
- **Test Runner:** pytest 9.2.0 + pytest-html 4.2.0
- **Test Server:** Live background Werkzeug test server on dynamic TCP port
