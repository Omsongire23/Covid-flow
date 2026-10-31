# Software Testing and Quality Assurance Test Plan

**Project:** COVID-19 Dynamic Information & Surveillance Portal  
**Course:** Software Testing and Quality Assurance (SQA) — Mini Project  
**Specification Reference:** `CLAUDE.md`  
**Date:** October 14, 2026  
**Document Version:** 1.0.0 (Final)  
**Status:** Approved & Executed  

---

## 1. Introduction and Objectives

The objective of this Software Quality Assurance test project is to verify and validate the functional correctness, regular expression robustness, security compliance, cross-engine parity, and data integrity of the **COVID-19 Information Portal**.

The system must satisfy all mandatory requirements specified in the project problem statement:
> *"Dynamic website of COVID-19 information using HTML, CSS, JavaScript and PHP. MySQL database used to store user account, comment, and registration form details. Regular expression test cases for testing purposes."*

### Key Test Objectives:
1. **Regular Expression Validation Rigor:** Verify that client and server input validation strictly adheres to the single source of truth (`config/patterns.json`).
2. **Dual-Engine Parity:** Guarantee that ECMAScript (`/u`) and PHP PCRE (`~...~Du`) regular expression engines evaluate to identical boolean decisions across all 124 input test cases.
3. **Catastrophic Backtracking (ReDoS) Defense:** Ensure all patterns execute in linear time ($< 200\text{ ms}$) even when subject to hostile $100,000$-character nested inputs.
4. **Data Minimisation & Persistence:** Validate MySQL schema constraints, transactional persistence for user accounts, community comments, and programme registrations.
5. **Defensive Security & Session Hardening:** Ensure SQL injection prevention via PDO prepared statements, XSS prevention via automatic output escaping (`e()`), CSRF token enforcement on all state-changing endpoints, and brute-force login throttling.
6. **Graceful Upstream Handling:** Ensure that failure of upstream third-party APIs (`disease.sh`) degrades gracefully to verified local cache or an honest empty state without fabricating numbers.

---

## 2. Scope

### In-Scope
- **Dynamic Surveillance:** Global summary, historical trends (30, 90, 180, 365, all days), and country surveillance table with search, continent filtering, and pagination.
- **Informational Guidance:** Prevention, Symptoms, Vaccination, Myths vs. Facts, and Helplines with authoritative WHO / MoHFW citations.
- **User Authentication & Authorization:** User signup, login, session timeout (30 min), brute-force lockout, profile management, and role-based access control (`user` vs `admin`).
- **Programme Registration:** Separate enrollment form with live regex validation, duplicate prevention, and zero-padded reference generation (`REG-XXXXXX`).
- **Community Forum:** Paginated comment feed, character counting, client & server sanitization, and owner comment deletion.
- **Quality Lab:** Interactive in-browser pattern tester, full automated suite runner, CSV/JSON report exports, and explainer cards.
- **Automated Test Runners:** Node.js runner (`tests/regex.test.mjs`), PHP PCRE runner (`tests/regex_test.php`), cross-engine parity validator (`tests/engine-parity.mjs`), semantic validator (`tests/validator_test.php`), and HTTP smoke runner (`tests/smoke.sh`).

### Out-of-Scope
- Commercial payment gateways.
- SMS / SMTP physical transmission.
- Medical appointment scheduling (all registrations are for informational webinars and guidance sessions).
- External framework build systems (Laravel, React, Tailwind, Bootstrap).

---

## 3. Test Levels and Methodologies

| Test Level | Scope & Techniques | Tooling Used |
|---|---|---|
| **Unit / Regex Testing** | Boundary value analysis, equivalence partitioning, trailing newline injections, Unicode character handling, and ReDoS benchmarks. | Node.js (`node:test`), PHP CLI (PCRE), in-browser JavaScript |
| **Semantic Validator Testing** | Calendar date integrity (`checkdate`), leap day acceptance, age boundary rules (5–120), phone normalisation, and allowlist membership. | PHP CLI (`tests/validator_test.php`) |
| **Integration & API Testing** | Verification of internal JSON API endpoints (`api/stats.php`, `api/comments.php`, `api/check-username.php`, `api/patterns.php`). | `curl`, Python `urllib`, PHP PDO |
| **Security & Penetration Testing** | CSRF token bypass attempts, SQL injection payloads (`' OR 1=1 --`), Stored XSS prevention (`<script>alert(1)</script>`), HTTP security headers inspection, and session cookie flag verification. | `curl`, automated bash scripts (`tests/smoke.sh`) |
| **UI & Accessibility Testing** | Keyboard navigation, focus rings, ARIA roles, color contrast ratios ($\ge 4.5:1$), responsive viewport adaptation (360px, 768px, 1280px), and theme persistence (`prefers-color-scheme`). | Headless HTTP verification, browser developer tools |

---

## 4. Test Environment and Infrastructure

All tests are verified against the active production-grade Docker container topology:

| Component | Platform / Version | Details / Config |
|---|---|---|
| **Host OS** | Linux x86_64 (Kernel 6.17) | Workspace environment |
| **Web Server** | Apache 2.4.68 (Debian) | Native mod_rewrite, `DocumentRoot /var/www/html/public` |
| **PHP Runtime** | PHP 8.3.35 | Extensions: `pdo`, `pdo_mysql`, `curl`, `mbstring`, `json` |
| **Database Server** | MariaDB 10.11.16 | InnoDB engine, `utf8mb4` character set, strict SQL mode |
| **JavaScript Engine** | Node.js v22.23.2 & V8 | Native ES Modules, RegExp `/u` Unicode mode |
| **Network Endpoint** | `http://127.0.0.1:8000` | Localhost containerized exposure |
| **Database Port** | `127.0.0.1:3307` | MariaDB bridge port |

---

## 5. Entry and Exit Criteria

### Entry Criteria
1. Database schema (`database/schema.sql`) imported successfully without errors.
2. Web server container (`covid_web`) and database container (`covid_db`) running and healthy.
3. Configuration files (`config/patterns.json`, `config/config.local.php`) present and accessible.
4. Test suites exist in `tests/` with non-empty test cases in `tests/regex-cases.json`.

### Exit Criteria
1. 100% of regex test cases pass on both Node.js and PHP PCRE engines.
2. Cross-engine parity report confirms zero discrepancies between JS and PHP.
3. 100% of semantic validator test cases pass.
4. 100% of automated HTTP smoke and security test cases pass.
5. Zero critical or high-severity unhandled defects remain open.
6. Complete traceability matrix mapping requirements FR-01 through FR-13 to automated test cases.

---

## 6. Defect Severity and Priority Scale

| Severity | Definition | Resolution Target |
|---|---|---|
| **Critical (S1)** | Security vulnerability (e.g. CSRF bypass, SQL injection, XSS), engine crash, or database corruption. | Immediate fix before any build release. |
| **Major (S2)** | Core feature failure (e.g. user cannot sign up, registration fails to persist, regex discrepancy). | Fix within the current iteration. |
| **Moderate (S3)** | Edge case validation failure (e.g. calendar leap day calculation defect, selector mismatch). | Fix and log in defect register. |
| **Minor (S4)** | Cosmetic or minor layout flaw that does not impede functional task completion. | Schedule for aesthetic polish. |

---

## 7. Quality Deliverables Summary

- `docs/TEST_PLAN.md`: Strategic SQA roadmap and methodology.
- `docs/TEST_CASES.md`: Full catalog of 145+ discrete test cases with prerequisites, inputs, and results.
- `docs/TRACEABILITY.md`: Requirement-to-Test Traceability Matrix (RTM).
- `docs/DEFECT_LOG.md`: Formal registry of genuine defects discovered, root causes, and verification notes.
- `docs/TEST_REPORT.md`: Comprehensive test execution log with empirical output summaries.
- `tests/run-all.sh`: Automated master test execution script.


## 10. Selenium Browser Automation (added)

A Python Selenium 4 suite is maintained in `tests/selenium/test_covid_flow.py`. It adds browser-level checks for signup validation, username boundaries, login errors, guest community behavior, comment length, statistics chart rendering, and programme-registration consent. Setup and execution instructions are in `docs/SELENIUM_TESTING.md`.

The suite complements the existing Node.js, PHP CLI, curl and Bash suites. Selenium execution results must be added to the final report only after an actual run. Payments and third-party API uptime remain out of scope.
