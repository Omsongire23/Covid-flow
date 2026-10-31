# Testing Patterns

**Analysis Date:** 2026-10-31

## Test Framework

**Runner:**
- Node.js built-in ES module scripts (`tests/regex.test.mjs`, `tests/engine-parity.mjs`); no third-party runner or package manifest detected.
- PHP CLI scripts (`tests/regex_test.php`, `tests/validator_test.php`); no PHPUnit configuration detected.
- Bash smoke runner (`tests/run-all.sh`) orchestrates five suites.

**Assertion Library:**
- Native boolean conditions and explicit counters. PHP uses a local `test()` collector; Node records failure objects and exits with status 1.

**Run Commands:**
```bash
bash tests/run-all.sh       # Run all five suites
node tests/regex.test.mjs   # JavaScript regex and ReDoS checks
php tests/validator_test.php # Semantic validator checks
bash tests/smoke.sh         # HTTP/security smoke checks against port 8000
```

## Test File Organization

**Location:**
- Tests are centralized under `tests/`; cases and fixtures are separate JSON files (`tests/regex-cases.json`).

**Naming:**
- Test-case IDs are stable requirement-oriented IDs such as `TC-SEM-DOB-01`, `TC-REG-EMAIL-01`, and `TC-SEC-04`.

**Structure:**
```text
tests/
├── regex-cases.json
├── regex.test.mjs
├── regex_test.php
├── engine-parity.mjs
├── validator_test.php
├── smoke.sh
└── run-all.sh
```

## Test Structure

**Suite Organization:**
```php
test('TC-SEM-DOB-01', 'Valid calendar date and age', validate_dob('2000-01-31') === true);
```

**Patterns:**
- Tests execute at top level, print each pass/failure, then print total/passed/failed counts.
- The master runner uses `set -e`; each suite must return zero to continue.
- Regex tests load the shared `config/patterns.json` and cases, then test valid/invalid inputs plus generated hostile strings.
- Parity tests execute equivalent JavaScript `/u` and PHP PCRE `~...~Du` evaluations and compare every case.

## Mocking

**Framework:** None detected.

**Patterns:**
- `tests/engine-parity.mjs` uses `spawnSync` to invoke local PHP, Docker Compose, or a PHP container as an execution fallback.
- `tests/smoke.sh` uses `TEST_BASE_URL` to point curl checks at a running server; it does not mock HTTP responses.

**What to Mock:**
- No mock layer exists. For new external-data tests, use deterministic fixtures such as `data/covid-fallback.json` or a test endpoint rather than live upstream data.

**What NOT to Mock:**
- Keep regex parity and HTTP security checks against the real shared configuration and running application paths.

## Fixtures and Factories

**Test Data:**
- Regex cases live in `tests/regex-cases.json`, with literal `in` values or generated `{prefix, repeat, times, suffix}` values.
- Semantic tests construct boundary dates at runtime with `DateTimeImmutable` and use direct representative values.

**Location:**
- Static fixtures are in `tests/`; application fallback data is in `data/covid-fallback.json`.

## Coverage

**Requirements:** No coverage target or coverage tool is enforced.

**View Coverage:** Not applicable; use `bash tests/run-all.sh` and inspect suite summaries.

## Test Types

**Unit Tests:**
- Regex behavior and semantic validators are tested directly through Node/PHP CLI scripts.

**Integration Tests:**
- `tests/engine-parity.mjs` integrates both regex engines and shared fixtures. Database integration is exercised indirectly by HTTP endpoints when the stack is running.

**E2E Tests:**
- `tests/smoke.sh` provides curl-based endpoint, authentication, CSRF, headers, cookie, and public-page checks. No browser automation framework is used.

## Common Patterns

**Async Testing:**
- No async test framework; Node tests are synchronous scripts. Browser behavior is not covered by the CLI suite.

**Error Testing:**
- Negative cases assert exact validation errors or HTTP status codes, including invalid dates, hostile regex input, missing CSRF, unauthenticated access, and invalid query parameters.

---

*Testing analysis: 2026-10-31*
