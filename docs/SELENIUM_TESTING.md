# Selenium Browser Testing — Covid-flow STQA

## Purpose
This suite adds real-browser functional/regression checks to the existing Node.js, PHP CLI, curl and Bash test layers. It uses Selenium WebDriver 4 with Python's unittest runner. Selenium Manager resolves the browser driver automatically when a compatible browser is installed.

## Scope and test-case mapping

| ID | Scenario | Expected result | Automation |
|---|---|---|---|
| TC01 | Signup with valid username `john_doe` | No inline validation error | Automated |
| TC02 | Signup with invalid username `1bad name` | Inline validation error | Automated |
| TC03 | Signup with invalid email `user@@example` | Inline validation error | Automated |
| TC04 | Username lengths 3, 4, 20, 21 | Reject 3/21; accept 4/20 | Automated |
| TC05 | Login with unknown user / wrong password | Generic login error is shown | Automated |
| TC06 | Guest opens community page | Guest login prompt is shown; page itself remains public | Automated |
| TC07 | Comment length boundary | Composer enforces maxlength 500 | Automated with test account |
| TC08 | HTML-like comment payload | Payload is prepared but not submitted to shared DB; server-side rejection remains covered by existing validator tests | Automated partial check |
| TC09 | Statistics page | Trend chart canvas is present | Automated |
| TC10 | Programme registration consent | Consent checkbox is required and initially unchecked | Automated with test account |

**Project-specific corrections:** `community.php` is public and renders a guest login prompt; it does not redirect guests. Programme registration is protected by `require_login()`. The comment textarea has a native `maxlength="500"` limit. These tests assert the behavior implemented by the repository rather than the assumptions in the initial draft.

## Files
- `tests/selenium/test_covid_flow.py` — Selenium test suite.
- `requirements-selenium.txt` — Python test dependency.
- Screenshots on test failure are saved under `artifacts/selenium/` (ignored from version control if the repository has an ignore rule; otherwise add that directory to `.gitignore`).

## Environment

- Linux (Ubuntu), Docker Compose application at `http://127.0.0.1:8000`.
- Python 3.10+ recommended.
- Chrome/Chromium or Firefox installed.
- Docker web and database containers running; database schema initialized.
- For authenticated cases, create a dedicated test account and provide its credentials through environment variables. Do not commit credentials.

## Run

```bash
docker compose up -d
python3 -m venv .venv
source .venv/bin/activate
python -m pip install -r requirements-selenium.txt
python -m unittest discover -s tests/selenium -v
```

Headless Chrome is the default. To run visible Chrome:

```bash
SELENIUM_HEADLESS=0 python -m unittest discover -s tests/selenium -v
```

Firefox:

```bash
SELENIUM_BROWSER=firefox python -m unittest discover -s tests/selenium -v
```

Authenticated tests:

```bash
export SELENIUM_TEST_USERNAME='your-dedicated-test-user'
export SELENIUM_TEST_PASSWORD='your-test-password'
python -m unittest discover -s tests/selenium -v
```

Optional target URL:

```bash
COVID_FLOW_BASE_URL=http://127.0.0.1:8000 python -m unittest discover -s tests/selenium -v
```

## Evidence and reporting

- Each failed test saves a PNG screenshot in `artifacts/selenium/`.
- Record the actual terminal summary, browser/version, date/time, and screenshots after execution.
- Do not report a test as passed until it has been run against the current build.
- If a test fails, record test-case ID, expected result, actual result, status, defect ID, screenshot path and retest result.
- The HTML-like comment case intentionally does not submit hostile content into a shared database. The repository's PHP semantic validator already tests rejection of HTML tags; a separate disposable test database can be used for a full browser/API rejection test.

## Limitations
- This suite does not test payments or third-party disease.sh uptime.
- Chrome and Firefox are supported, but both browsers must be installed and run separately.
- Selenium is a browser automation layer; it complements rather than replaces existing regex, PHP, API, security and parity tests.
- Selenium test results are not yet recorded here. Populate the execution report only after running the suite locally.
