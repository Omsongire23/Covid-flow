# Requirements Traceability Matrix (RTM)

**Project:** COVID-19 Information Portal  
**Specification Reference:** `CLAUDE.md` — Section 1 & Section 12  
**Date:** October 14, 2026  
**Status:** 100% Coverage Verified  

---

## 1. Traceability Matrix Table

| Req ID | Requirement Description | Implementation Components | Associated Test Cases | Verification Method | Status |
|---|---|---|---|---|---|
| **FR-01** | Home page displays real-time global COVID-19 statistics (cases, deaths, recovered, active, today changes, source, last updated). | `public/index.php`<br>`includes/covid_client.php`<br>`public/api/stats.php`<br>`public/assets/js/stats.js` | `TC-API-01a`<br>`TC-UI-01`<br>`TC-API-02` | Automated API fetch, DOM count-up inspection | **Covered & Passed** |
| **FR-02** | Statistics page: historical trend chart with time-horizon & metric controls; searchable, sortable, filterable country table. | `public/statistics.php`<br>`public/assets/js/charts.js`<br>`public/assets/vendor/chart.umd.min.js` | `TC-API-01b`<br>`TC-API-01c`<br>`TC-API-03`<br>`TC-UI-01`<br>`TC-UI-02` | Chart.js instance re-render, table query sync | **Covered & Passed** |
| **FR-03** | Curated informational guidance pages: Prevention, Symptoms, Vaccination, Myths vs Facts, Helplines with citations & lastReviewed date. | `public/prevention.php`<br>`public/symptoms.php`<br>`public/vaccination.php`<br>`public/myths.php`<br>`public/helplines.php`<br>`data/content.json` | `TC-UI-01` | Content JSON load, accordion toggle, HTTP 200 checks | **Covered & Passed** |
| **FR-04** | Account signup with server & client-side regex validation, password hashing, and duplicate account rejection. | `public/signup.php`<br>`includes/auth.php`<br>`includes/validate.php`<br>`public/assets/js/forms.js` | `TC-AUTH-01`<br>`TC-AUTH-02`<br>`TC-AUTH-03`<br>`TC-AUTH-04`<br>`TC-SEC-01`<br>`TC-API-04` | Automated signup HTTP script, DB hash inspection | **Covered & Passed** |
| **FR-05** | Login & logout with session hardening, session ID regeneration, and 5-attempt / 15-min brute-force lockout. | `public/login.php`<br>`public/logout.php`<br>`includes/auth.php`<br>`database/schema.sql` (`login_attempts`) | `TC-AUTH-05`<br>`TC-AUTH-06`<br>`TC-AUTH-07`<br>`TC-AUTH-08`<br>`TC-AUTH-09`<br>`TC-SEC-06` | Multi-attempt curl script, HTTP 429 & 405 checks | **Covered & Passed** |
| **FR-06** | User profile page: account metadata, list of own registrations with cancellation action, and own comments. | `public/profile.php`<br>`includes/auth.php` | `TC-SEC-05b`<br>`TC-FRM-01`<br>`TC-CMT-01` | Authenticated session profile query & cancellation | **Covered & Passed** |
| **FR-07** | Community page: paginated comment feed, live character counter (500 max), regex sanitization, and owner comment deletion. | `public/community.php`<br>`public/api/comments.php`<br>`public/assets/js/comments.js` | `TC-CMT-01`<br>`TC-CMT-02`<br>`TC-CMT-03`<br>`TC-CMT-04`<br>`TC-CMT-05`<br>`TC-SEC-02` | API JSON post, ownership verification, XSS tests | **Covered & Passed** |
| **FR-08** | Programme Registration form: live client & server validation, MySQL persistence, duplicate prevention, zero-padded reference. | `public/program-registration.php`<br>`includes/validate.php`<br>`database/schema.sql` (`registrations`) | `TC-FRM-01`<br>`TC-FRM-02`<br>`TC-FRM-03`<br>`TC-FRM-04`<br>`TC-FRM-05`<br>`TC-FRM-06`<br>`TC-DB-02` | End-to-end enrollment script, reference regex match | **Covered & Passed** |
| **FR-09** | Shared regular expression validation (JS & PHP) for 9 fields: username, fullName, email, password, phoneIN, dob, pinCode, countryQuery, comment. | `config/patterns.json`<br>`includes/validate.php`<br>`public/assets/js/validate.js`<br>`public/api/patterns.php` | `TC-REG-USERNAME-*`<br>`TC-REG-FULLNAME-*`<br>`TC-REG-EMAIL-*`<br>`TC-REG-PASSWORD-*`<br>`TC-REG-PHONEIN-*`<br>`TC-REG-DOB-*`<br>`TC-REG-PINCODE-*`<br>`TC-REG-COUNTRYQUERY-*`<br>`TC-REG-COMMENT-*`<br>`TC-REG-REDOS-*` | Node runner, PHP PCRE runner, parity script | **Covered & Passed** |
| **FR-10** | Quality Lab page: interactive live pattern tester, full automated suite runner, CSV/JSON export, and SQA explainer cards. | `public/quality-lab.php`<br>`public/assets/js/lab.js`<br>`public/api/regex-cases.php` | `TC-UI-06`<br>`TC-REG-*` | Interactive browser execution & export validation | **Covered & Passed** |
| **FR-11** | Professional UI: light/dark theme toggle, system font stack, WCAG AA contrast, responsive grids, keyboard accessibility. | `public/assets/css/*.css`<br>`public/assets/js/theme-init.js`<br>`public/assets/js/app.js` | `TC-UI-03`<br>`TC-UI-04`<br>`TC-UI-05` | Viewport scaling (360px–1280px), theme reload test | **Covered & Passed** |
| **FR-12** | Resilient upstream data handling: caching in MySQL (`covid_cache`), stale banner on upstream fault, clear empty state (no fake data). | `includes/covid_client.php`<br>`public/api/stats.php` | `TC-API-01`<br>`TC-API-02` | Live upstream reachability check, cache inspection | **Covered & Passed** |
| **FR-13** | Admin comment moderation console (Stretch requirement): view all comments, filter by status, hide, restore, and delete comments. | `public/admin/comments.php`<br>`includes/auth.php` | `TC-SEC-05a` | Admin role verification, status mutation tests | **Covered & Passed** |

---

## 2. Requirement Coverage Summary

- **Total Functional Requirements:** 13
- **Fully Covered Requirements:** 13 (100%)
- **Total Linked Test Cases:** 153 discrete test cases
- **Defects Discovered During Traceability Execution:** 3 (all fixed and verified in `docs/DEFECT_LOG.md`)
