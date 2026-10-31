# STQA Test Cases Specification & Catalog
## Covid-flow — Python Flask & Selenium 4 Automation Suite

**Project:** Covid-flow COVID-19 Health Intelligence Portal  
**Course:** Software Testing and Quality Assurance (STQA)  
**Specification Reference:** `CLAUDE.md`  
**Total Documented Automated Test Cases:** 191 (49 pytest unit/integration + 18 Selenium 4 E2E + 124 Quality Lab regex suite)  
**Status:** 100% Passed  

---

## 1. Selenium 4 Automated Browser Test Cases (`tests/selenium/`)

| Test ID | Requirement | Scenario / Title | Locators | Test Steps | Expected Outcome | Actual Outcome | Status |
|---|---|---|---|---|---|---|---|
| **TC-SEL-01** | FR-01 | Application Launch & Title | `driver.title` | 1. Navigate to `/`<br>2. Read title | Page loads, title contains "COVID-19 Dashboard" or "Covid-flow" | Title verified: `COVID-19 Dashboard \| Covid-flow` | **PASS** |
| **TC-SEL-02** | FR-01 | Home Page Hero Heading | `id="mainDashboardHeading"` | 1. Navigate to `/`<br>2. Wait for `h1` | Primary heading visible: "Real-Time COVID-19 Health Intelligence" | Heading text verified visible | **PASS** |
| **TC-SEL-03** | FR-01 | Dashboard Global Statistics Cards | `id="valTotalCases"`, `id="valActiveCases"`, `id="valTotalDeaths"` | 1. Open `/`<br>2. Wait for cards<br>3. Inspect text | Cards display formatted non-empty numbers | Values verified (e.g. `704,753,890`) | **PASS** |
| **TC-SEL-04** | FR-01/11 | Header Navigation Bar Links | `id="navPrevention"`, `id="navHelplines"`, `id="navHome"` | 1. Click Prevention link<br>2. Click Helplines link<br>3. Click Home link | Browser transitions to respective routes with correct titles | Transitions verified: `/prevention`, `/helplines`, `/` | **PASS** |
| **TC-SEL-05** | FR-02 | Statistics Page & Chart Rendering | `id="statsPageHeading"`, `id="covidTrendChart"` | 1. Navigate to `/statistics`<br>2. Wait for chart canvas | Statistics page loads; `<canvas>` mounted in DOM | Canvas present and displayed | **PASS** |
| **TC-SEL-06** | FR-02 | Country Search & Table Filter | `id="countrySearchInput"`, `id="countrySearchBtn"`, `.country-row` | 1. Type "India" in search<br>2. Click Search<br>3. Inspect rows | Table filters to matching country records | India row displayed with metrics | **PASS** |
| **TC-SEL-07** | FR-04/09 | Empty Registration Rejection | `id="registerSubmitBtn"`, `id="usernameError"` | 1. Open `/register`<br>2. Click submit empty | Form rejected; inline error span displayed | "Username is required" displayed | **PASS** |
| **TC-SEL-08** | FR-04/09 | Invalid Email Format Rejection | `id="registerEmailInput"`, `id="emailError"` | 1. Type "not-a-valid-email"<br>2. Submit form | Email validator rejects; inline error displayed | "valid email address" displayed | **PASS** |
| **TC-SEL-09** | FR-04/05 | Valid Registration & Auto-Login | `registerUsernameInput`, `registerEmailInput`, `profileUsername` | 1. Fill valid form<br>2. Submit registration | Account created; redirects to `/profile` with username | Redirected to `/profile`; user badge active | **PASS** |
| **TC-SEL-10** | FR-05 | Invalid Login Rejection | `usernameInput`, `passwordInput`, `id="loginAuthError"` | 1. Submit invalid credentials on `/login` | Authentication failure alert displayed | "Invalid username or password" displayed | **PASS** |
| **TC-SEL-11** | FR-05 | Valid User Login Flow | `usernameInput`, `passwordInput`, `id="navUsernameBadge"` | 1. Login with `testuser` / `Test@123#` | Session established; header displays user badge | User badge displays "testuser" | **PASS** |
| **TC-SEL-12** | FR-05 | User Logout Workflow | `id="navLogoutBtn"`, `id="navLoginBtn"` | 1. Log in<br>2. Click Log Out in header | Session cleared; header displays "Log In" button | Log In button visible; session ended | **PASS** |
| **TC-SEL-13** | FR-07 | Guest Community Posting Guard | `id="loginToCommentNotice"` | 1. Visit `/community` as guest | Comment input absent; login notice displayed | Login notice visible; textarea hidden | **PASS** |
| **TC-SEL-14** | FR-07 | Authenticated Comment Creation | `id="commentInput"`, `id="postCommentBtn"` | 1. Log in<br>2. Post timestamped note | Comment saved and appended to stream | Comment rendered in discussion stream | **PASS** |
| **TC-SEL-15** | FR-07/09 | Comment XSS Script Rejection | `id="commentInput"`, `id="commentError"` | 1. Submit `<script>alert(1)</script>` | Server regex rejects HTML tags; inline error shown | "no HTML tags" error displayed | **PASS** |
| **TC-SEL-16** | FR-07 | Deletion of Own Comment | `.delete-comment-btn`, `.alert-message` | 1. Post note<br>2. Click Delete button | Note removed from stream; confirmation alert shown | "deleted successfully" flash message | **PASS** |
| **TC-SEL-17** | FR-08/09 | Programme Registration Workflow | `fullNameInput`, `dobInput`, `id="programmeConfirmationCard"` | 1. Fill all fields<br>2. Check consent<br>3. Submit | Confirmation card with reference number generated | `COV-2026-XXXXX` reference displayed | **PASS** |
| **TC-SEL-18** | FR-10 | Quality Lab Live & Suite Testing | `tester-field`, `tester-btn`, `runSuiteBtn`, `suitePassRate` | 1. Test single input<br>2. Run automated test suite | Pass/Fail badge updates; suite runs all 124 cases | Badge works; suite pass rate displays 100.0% | **PASS** |

---

## 2. Integration & Route Test Cases (`tests/integration/`)

| Test ID | Module | Scenario | Expected Result | Status |
|---|---|---|---|---|
| **TC-INT-01** | `test_auth_routes` | Register page renders | HTTP 200, contains registration form | **PASS** |
| **TC-INT-02** | `test_auth_routes` | Duplicate username rejection | Field error "username is already taken" | **PASS** |
| **TC-INT-03** | `test_auth_routes` | Password mismatch rejection | Field error "Passwords do not match" | **PASS** |
| **TC-INT-04** | `test_auth_routes` | Login successful session | HTTP 302/200, session contains user_id | **PASS** |
| **TC-INT-05** | `test_auth_routes` | Login invalid password | HTTP 200, error "Invalid username or password" | **PASS** |
| **TC-INT-06** | `test_auth_routes` | Logout clears session | Session empty, flash message | **PASS** |
| **TC-INT-07** | `test_auth_routes` | Protected profile route guard | Redirects to `/login` when unauthenticated | **PASS** |
| **TC-INT-08** | `test_auth_routes` | Profile view when authenticated | Displays username, email, registrations, comments | **PASS** |
| **TC-INT-09** | `test_community_routes` | Guest community view | Public comments visible, login prompt shown | **PASS** |
| **TC-INT-10** | `test_community_routes` | Guest POST rejected | Redirects with warning flash | **PASS** |
| **TC-INT-11** | `test_community_routes` | Authenticated comment post | Database row inserted, rendered on feed | **PASS** |
| **TC-INT-12** | `test_community_routes` | XSS rejection | HTML tag pattern rejected | **PASS** |
| **TC-INT-13** | `test_community_routes` | Delete own comment | Row deleted from comments table | **PASS** |
| **TC-INT-14** | `test_programme_routes` | Programme form renders | HTTP 200 with all input fields | **PASS** |
| **TC-INT-15** | `test_programme_routes` | Valid programme registration | Row inserted with `COV-2026-` ref number | **PASS** |
| **TC-INT-16** | `test_programme_routes` | Duplicate service registration | Rejected with existing reference note | **PASS** |
| **TC-INT-17** | `test_programme_routes` | Missing consent rejected | Error "must provide consent" | **PASS** |
| **TC-INT-18** | `test_public_routes` | Dashboard metrics rendering | Global cases, deaths, and active cases rendered | **PASS** |
| **TC-INT-19** | `test_public_routes` | Statistics page & chart | Chart canvas and table rendered | **PASS** |
| **TC-INT-20** | `test_public_routes` | Statistics country query filter | Query filter matches country | **PASS** |
| **TC-INT-21** | `test_public_routes` | Prevention page content | Verified WHO / MoHFW guidance rendered | **PASS** |
| **TC-INT-22** | `test_public_routes` | Symptoms page & warnings | Emergency warning signs alert rendered | **PASS** |
| **TC-INT-23** | `test_public_routes` | Vaccination page content | Vaccine efficacy and dose facts rendered | **PASS** |
| **TC-INT-24** | `test_public_routes` | Myths vs Facts content | Scientific debunking cards rendered | **PASS** |
| **TC-INT-25** | `test_public_routes` | Helplines directory | 1075, 112, 1800-599-0019 rendered with call links | **PASS** |

---

## 3. Unit Test Cases (`tests/unit/`)

| Test ID | Module | Scenario / Function | Expected Result | Status |
|---|---|---|---|---|
| **TC-UNIT-01** | `test_validators` | Username valid cases | `validate_username` returns `(True, None)` | **PASS** |
| **TC-UNIT-02** | `test_validators` | Username boundary (<4, >20 chars, starting with digit/underscore) | Returns `(False, error_msg)` | **PASS** |
| **TC-UNIT-03** | `test_validators` | Full Name valid Unicode names | Matches regex pattern | **PASS** |
| **TC-UNIT-04** | `test_validators` | Full Name invalid cases (digits, HTML tags, newlines) | Rejection confirmed | **PASS** |
| **TC-UNIT-05** | `test_validators` | Email valid RFC addresses | Returns `(True, None)` | **PASS** |
| **TC-UNIT-06** | `test_validators` | Email invalid syntax (double dots, no TLD) | Returns `(False, error_msg)` | **PASS** |
| **TC-UNIT-07** | `test_validators` | Password valid complex passwords | Returns `(True, None)` | **PASS** |
| **TC-UNIT-08** | `test_validators` | Password missing upper/lower/digit/symbol, spaces | Returns `(False, error_msg)` | **PASS** |
| **TC-UNIT-09** | `test_validators` | Indian phone valid 10-digit mobile (+91/0 prefix) | Returns `(True, None)` | **PASS** |
| **TC-UNIT-10** | `test_validators` | Indian phone invalid prefix (<6, 9 digits, alpha) | Returns `(False, error_msg)` | **PASS** |
| **TC-UNIT-11** | `test_validators` | Date of birth valid calendar dates | Returns `(True, None)` | **PASS** |
| **TC-UNIT-12** | `test_validators` | Date of birth leap year, future date, invalid day | Semantic rejection confirmed | **PASS** |
| **TC-UNIT-13** | `test_validators` | PIN code valid 6 digits | Returns `(True, None)` | **PASS** |
| **TC-UNIT-14** | `test_validators` | PIN code leading zero, 5 digits, letters | Returns `(False, error_msg)` | **PASS** |
| **TC-UNIT-15** | `test_validators` | Comment valid 3–500 characters | Returns `(True, None)` | **PASS** |
| **TC-UNIT-16** | `test_validators` | Comment <3 chars, whitespace only, HTML tags | Returns `(False, error_msg)` | **PASS** |
| **TC-UNIT-17** | `test_covid_api` | Offline fallback file loads | Cases and deaths dictionaries parsed | **PASS** |
| **TC-UNIT-18** | `test_covid_api` | Global stats structure | Provenance label and numbers verified | **PASS** |
| **TC-UNIT-19** | `test_covid_api` | Historical stats timeseries | 30-day dates and trend arrays verified | **PASS** |
| **TC-UNIT-20** | `test_covid_api` | Country stats listing | Structured country objects returned | **PASS** |
| **TC-UNIT-21** | `test_covid_api` | Country search filtering | Filter returns matching country | **PASS** |
| **TC-UNIT-22** | `test_models` | Database tables exist | `users`, `comments`, `registrations`, etc. exist | **PASS** |
| **TC-UNIT-23** | `test_models` | Foreign keys enabled | `PRAGMA foreign_keys` returns 1 | **PASS** |

---

## 4. Regular Expression Suite Summary (`tests/regex-cases.json`)
- **Total Test Cases in Suite:** 124
- **Passed:** 124 (100.0%)
- **Failed:** 0
- **Categories Covered:** Username (14), Full Name (15), Email (15), Password (12), Phone IN (14), Date of Birth (12), PIN Code (9), Country Query (16), Community Comment (17).
