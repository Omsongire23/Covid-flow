# SQA Defect Log & Remediation Register

**Project:** COVID-19 Information Portal  
**Specification Reference:** `CLAUDE.md` — Section 9 & Section 12.1  
**Date:** October 14, 2026  
**Document Version:** 1.0.0  

---

## 1. Summary of Defects

| Defect ID | Severity | Title | Affected Component | Status | Resolution |
|---|---|---|---|---|---|
| **DEF-01** | High (S2) | PCRE `$` anchor matches before trailing newline without `D` modifier | `includes/validate.php` | **Closed / Verified** | Enforced PCRE `~...~Du` modifiers globally. |
| **DEF-02** | High (S2) | Catastrophic ReDoS backtracking on unanchored hostile HTML tag check | `config/patterns.json` | **Closed / Verified** | Reordered regex lookaheads to evaluate length first. |
| **DEF-03** | Moderate (S3) | Valid leap day test case (`2024-02-29`) failed age $\ge 5$ semantic rule in 2026 | `tests/validator_test.php` | **Closed / Verified** | Updated leap day test input to `2020-02-29` (age 6 in 2026). |
| **DEF-04** | Moderate (S3) | Hardcoded host paths in `engine-parity.mjs` failed in container runtime | `tests/engine-parity.mjs` | **Closed / Verified** | Implemented dynamic directory discovery (`/var/www/html`, `/app`). |
| **DEF-05** | Critical (S1) | Uninitialized CSRF token allowed bypass via empty string equality | `includes/csrf.php` | **Closed / Verified** | Mandated non-empty validation in `csrf_verify()`. |
| **DEF-06** | Minor (S4) | DOM selector mismatch for theme toggle and mobile navigation | `public/assets/js/app.js` | **Closed / Verified** | Updated query selectors to match layout header/footer markup. |

---

## 2. Detailed Defect Reports

### DEF-01: PCRE `$` Anchor Matches Trailing Newline without `D` Modifier
- **Severity:** High (S2)
- **Component:** `includes/validate.php` & `config/patterns.json`
- **Preconditions:** PHP PCRE engine executing regex with standard delimiters `~...~u`.
- **Steps to Reproduce:**
  1. Submit `"abcd\n"` to `validate_field('username', "abcd\n")`.
  2. Evaluate using `preg_match('~^[A-Za-z][A-Za-z0-9_]{3,19}$~u', "abcd\n")`.
- **Expected Result:** `preg_match` returns `0` (false), as newline is an illegal username character.
- **Actual Result:** `preg_match` returned `1` (true). PCRE's `$` by default matches before a trailing newline character.
- **Root Cause:** Standard PCRE specification treats `\n` at the end of subject string as matched by `$`.
- **Remediation:** Appended PCRE `D` (PCRE_DOLLAR_ENDONLY) modifier: `~' . $pattern . '~Du`.
- **Verification:** Verified across all test cases with note `TC-REG-NEWLINE`. 100% agreement with JavaScript.

---

### DEF-02: Catastrophic ReDoS Backtracking in Comment Tag Pattern
- **Severity:** High (S2)
- **Component:** `config/patterns.json` (`comment` pattern)
- **Preconditions:** Large input containing repeating `<` characters without closing brackets.
- **Steps to Reproduce:**
  1. Pass hostile string of 100,000 `<` characters into initial pattern ordering: `^(?![\\s\\S]*<[^>]*>)(?=[\\s\\S]{3,500}$)...`.
- **Expected Result:** Validation terminates in linear time ($< 200\text{ ms}$).
- **Actual Result:** Backtracking caused quadratic CPU hang lasting $\approx 7.5\text{ seconds}$ in Node.js V8.
- **Root Cause:** Unbounded lookahead `(?![\\s\\S]*<[^>]*>)` searched the entire 100,000 characters before length constraint was verified.
- **Remediation:** Reordered pattern to evaluate length **first**: `^(?=[\\s\\S]{3,500}$)(?=[\\s\\S]*\\S)(?![\\s\\S]*<[^>]*>)[\\s\\S]*$`. Length failure triggers immediate early exit in $\approx 0.11\text{ ms}$.
- **Verification:** Benchmarked in `TC-REG-REDOS-COMMENT` taking $0.11\text{ ms}$ in Node.js and $0.01\text{ ms}$ in PHP.

---

### DEF-03: Leap Day Test Case Failed Age $\ge 5$ Semantic Rule in 2026
- **Severity:** Moderate (S3)
- **Component:** `tests/validator_test.php`
- **Preconditions:** System clock operating in current year 2026.
- **Steps to Reproduce:**
  1. Run `tests/validator_test.php`.
  2. Test `TC-SEM-DOB-03` calls `validate_dob('2024-02-29')`.
- **Expected Result:** Test returns `true` (valid calendar date).
- **Actual Result:** `validate_dob` returned string error: `"You must be at least 5 years old to register."`.
- **Root Cause:** In year 2026, an individual born on `2024-02-29` is 2 years old ($< 5$). The test intended to verify leap day calendar validation via `checkdate()`, but selected a year that failed the age constraint.
- **Remediation:** Updated test case in `tests/validator_test.php` to `2020-02-29` (age 6 in 2026, valid leap year and age-eligible).
- **Verification:** Re-ran `tests/validator_test.php`; all 18 test cases passed.

---

### DEF-04: Cross-Engine Parity Runner Path Resolution in Container Environment
- **Severity:** Moderate (S3)
- **Component:** `tests/engine-parity.mjs`
- **Preconditions:** Executing `node tests/engine-parity.mjs` on host machine without native `php` CLI installed.
- **Steps to Reproduce:**
  1. Run `node tests/engine-parity.mjs`.
  2. Script attempts fallback to Docker command: `docker run ... php -r "$phpCode"`.
- **Expected Result:** PHP executes inside container and returns JSON parity output.
- **Actual Result:** PHP threw warning: `Failed to open stream: No such file or directory` because host directory `/home/om/Downloads/covid19` does not exist inside container.
- **Root Cause:** Path interpolation `${rootDir}/config/patterns.json` used host path rather than container path `/var/www/html` or `/app`.
- **Remediation:** Updated PHP evaluation code to dynamically discover base directory (`/var/www/html`, `/app`, `${rootDir}`) and parse clean JSON output.
- **Verification:** Re-ran `node tests/engine-parity.mjs`; 124/124 cases verified in 100% agreement.

---

### DEF-05: Critical CSRF Token Bypass on Empty Session Initialization
- **Severity:** Critical (S1)
- **Component:** `includes/csrf.php`
- **Preconditions:** New HTTP client initiating a state-changing POST request without prior GET session initialisation.
- **Steps to Reproduce:**
  1. Dispatch `curl -X POST http://127.0.0.1:8000/api/comments.php -d '{"action":"create","body":"test"}'`.
  2. Inspect HTTP status code.
- **Expected Result:** HTTP 403 Forbidden.
- **Actual Result:** HTTP 401 Unauthorized (request reached authorization layer, bypassing CSRF verification).
- **Root Cause:** In `csrf_verify()`, `$_SESSION['_csrf']` was uninitialized (empty string `''`). The request sent no token (empty string `''`). The function executed `hash_equals('', '')`, which evaluated to `true`.
- **Remediation:** Added explicit non-empty checks:
  ```php
  if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
      http_response_code(403);
      exit;
  }
  ```
- **Verification:** Verified with curl; request without token now strictly returns HTTP 403 Forbidden (`CSRF_INVALID`).

---

### DEF-06: DOM Selector Mismatch for Theme Toggle and Mobile Navigation
- **Severity:** Minor (S4)
- **Component:** `public/assets/js/app.js`
- **Preconditions:** User clicking theme toggle button or mobile hamburger menu.
- **Steps to Reproduce:**
  1. Inspect `app.js` DOM queries: `document.getElementById('theme-toggle')`, `document.getElementById('nav-hamburger')`.
  2. Inspect `layout_header.php`: button has `id="themeToggleBtn"` and `id="hamburgerBtn"`.
- **Expected Result:** Clicking theme toggle toggles dark mode; clicking hamburger opens drawer.
- **Actual Result:** Event listeners were not bound due to element ID mismatch.
- **Root Cause:** Layout template used camelCase IDs while `app.js` referenced kebab-case IDs.
- **Remediation:** Updated `app.js` to check both conventions: `document.getElementById('themeToggleBtn') || document.getElementById('theme-toggle')`.
- **Verification:** Tested theme toggle and hamburger navigation; both respond immediately and smoothly.
