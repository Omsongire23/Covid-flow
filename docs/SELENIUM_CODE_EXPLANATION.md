# Covid-flow — Selenium 4 Automation Code Explanation
## Software Testing and Quality Assurance (STQA) Practical Examination Guide

> **Project:** Covid-flow COVID-19 Health Intelligence Portal  
> **Course:** Software Testing and Quality Assurance (STQA)  
> **Framework:** Python 3, Selenium 4, pytest, pytest-html  
> **Pattern:** Page Object Model (POM) with Explicit Synchronization  

---

## 1. Selenium 4 & WebDriver Architecture Overview

### 1.1 What is Selenium?
Selenium is an open-source browser automation framework that drives web browsers natively just as a real user would. In this STQA project, Selenium 4 is used to perform automated End-to-End (E2E) functional testing against the live Flask application.

### 1.2 Selenium 4 W3C Standard Protocol
Unlike legacy Selenium 3 which relied on a JSON Wire Protocol encoding/decoding layer, **Selenium 4 communicates directly with browsers using the standardized W3C WebDriver specification**.
- **Benefits:** Eliminates encoding/decoding overhead, ensures predictable element interaction, and provides native browser compatibility.
- **Direct Chrome Integration:** Uses Google Chrome in modern headless mode (`--headless=new`) for Linux server-grade and CI/CD headless execution without requiring an X11 display server.

```text
+-------------------+      W3C WebDriver Protocol       +-------------------+      DevTools / Native API      +-------------------+
|   pytest Test     |  ==============================>  |   chromedriver    |  =============================> |   Google Chrome   |
| (Page Object Model)|     Direct HTTP REST calls       | (Browser Driver)  |      Native IPC Driver          | (Headless Engine) |
+-------------------+                                   +-------------------+                                 +-------------------+
```

---

## 2. Test Architecture & Pytest Fixture Lifecycle

The automation suite is housed under `tests/selenium/` and is orchestrated by `conftest.py`.

### 2.1 The `live_server` Fixture
```python
@pytest.fixture(scope="session")
def live_server():
    port = get_free_port()
    host = "127.0.0.1"
    test_db = str(BASE_DIR / "instance" / "selenium_test.sqlite3")
    ...
```
- **Execution:** Runs once per entire test session (`scope="session"`).
- **Mechanism:** Spins up a dedicated background `threading.Thread` serving the real Flask WSGI application using `werkzeug.serving.make_server`.
- **Database Isolation:** Creates an isolated SQLite database (`instance/selenium_test.sqlite3`), executes the schema initialization, seeds default test accounts (`testuser`, `admin`), and destroys the test database cleanly upon teardown.
- **Port Allocation:** Dynamically binds to a free OS port (`get_free_port()`), preventing "Address already in use" conflicts.

### 2.2 The `driver` Fixture
```python
@pytest.fixture
def driver():
    chrome_options = Options()
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--window-size=1920,1080")
    ...
    driver = webdriver.Chrome(options=chrome_options)
    yield driver
    driver.quit()
```
- **Lifecycle:** Re-created for every single test function (`scope="function"` default).
- **Isolation:** Ensures a pristine browser state (clean cookies, empty session storage, fresh DOM) for every scenario.
- **Clean Teardown:** `driver.quit()` guarantees browser processes are closed and memory is reclaimed.

### 2.3 Automated Failure Screenshot Hook
```python
@pytest.hookimpl(tryfirst=True, hookwrapper=True)
def pytest_runtest_makereport(item, call):
    outcome = yield
    report = outcome.get_result()
    if report.when == "call" and report.failed:
        driver = item.funcargs.get("driver")
        if driver:
            screenshot_path = ...
            driver.save_screenshot(str(screenshot_path))
```
- Hooks into pytest's reporting phase. If any assertion fails or an unexpected exception occurs, Selenium automatically saves a full-page PNG snapshot to `reports/screenshots/` with a timestamp and attaches it to `reports/selenium-report.html`.

---

## 3. Page Object Model (POM) Design Pattern

The test suite strictly implements the **Page Object Model (POM)**:
1. **Separation of Concerns:** Test scripts (`test_*.py`) contain only business workflows and assertions. Page interactions, selectors, and browser calls are encapsulated in Page Classes (`tests/selenium/pages/`).
2. **Maintenance Advantage:** If an element ID or layout changes, updates are made in a single page object file without modifying dozens of test cases.

### Class Hierarchy:
- **`BasePage` (`base_page.py`):** Common parent class encapsulating explicit synchronization and low-level WebDriver calls.
- **`HomePage` (`home_page.py`):** Encapsulates dashboard navigation, metric cards, and health shortcuts.
- **`StatisticsPage` (`statistics_page.py`):** Encapsulates Chart.js canvas visibility, metric switching, and country search.
- **`LoginPage` (`login_page.py`):** Encapsulates username/password inputs, login submission, and auth error messages.
- **`RegisterPage` (`register_page.py`):** Encapsulates account registration inputs, error labels, and submission.
- **`CommunityPage` (`community_page.py`):** Encapsulates comment textarea, post button, deletion, and comment stream.
- **`ProgrammePage` (`programme_page.py`):** Encapsulates multi-field registration, HTML5 date handling, consent checkbox, and confirmation reference card.
- **`QualityLabPage` (`quality_lab_page.py`):** Encapsulates category dropdown, live regex tester, automated test suite runner, and pass rate display.

---

## 4. Synchronization Strategy & Wait Mechanisms

### Why `time.sleep()` is an Anti-Pattern
Arbitrary hardcoded sleeps cause tests to either run excessively slowly or fail intermittently ("flaky tests") under varying CPU loads.

### Selenium 4 Explicit Waits
Covid-flow employs **Explicit Waits** through `WebDriverWait` and `expected_conditions (EC)`:
```python
WebDriverWait(self.driver, 10).until(
    EC.visibility_of_element_located((by, locator))
)
```
- **Polls the browser DOM every 500ms** until the condition evaluates to true or the 10-second threshold is exceeded.
- **Handles Dynamic AJAX Updates:** Used when Chart.js finishes rendering, when AJAX validation returns from `/api/quality-lab/validate`, or when the test suite runner completes 124 asynchronous checks.

### Resilient Click Handling (`ElementClickInterceptedException`)
When fixed navigation bars or sticky headers overlap elements, standard clicks can be intercepted. `BasePage.click()` overcomes this via:
```python
self.driver.execute_script("arguments[0].scrollIntoView({block: 'center', inline: 'nearest'});", element)
try:
    element.click()
except Exception:
    self.driver.execute_script("arguments[0].click();", element)
```

### Stale Element Protection (`StaleElementReferenceException`)
When a page redirects (such as posting a comment and redirecting to `/community`), existing DOM node references become stale. `get_comment_texts()` catches this exception gracefully and allows `WebDriverWait` to re-query the refreshed DOM.

---

## 5. Comprehensive Explanation of Selenium Test Suites

### 5.1 Dashboard & Navigation (`test_home.py`)

#### TC-SEL-01: Application Launch & Title Verification
- **Purpose:** Verifies that the Flask application web server is responsive, accepts HTTP connections, and serves the correct `<title>` tag.
- **Locators:** Uses browser window title via `driver.title` and `home.get_page_title()`.
- **Browser Actions:** Navigates to `/` using `driver.get()`.
- **Assertion:** `assert "COVID-19 Dashboard" in title or "Covid-flow" in title`.
- **PASS Criteria:** Page loads with HTTP 200 and document title contains expected branding.
- **FAIL Criteria:** Connection refused, HTTP 500, or title mismatch.

#### TC-SEL-02: Hero Section Heading Display
- **Purpose:** Verifies that the primary `<h1>` heading is rendered with clear clinical typography.
- **Locators:** `(By.ID, "mainDashboardHeading")`.
- **Browser Actions:** Explicit wait for visibility (`EC.visibility_of_element_located`).
- **Assertion:** `assert "COVID-19 Health Intelligence" in heading`.
- **PASS Criteria:** Heading element is visible and contains expected text.

#### TC-SEL-03: Dashboard Global Metrics Cards
- **Purpose:** Verifies that the core COVID-19 surveillance metrics (Confirmed, Active, Deaths) are displayed with real or fallback numbers, not empty strings or errors.
- **Locators:** `(By.ID, "valTotalCases")`, `(By.ID, "valActiveCases")`, `(By.ID, "valTotalDeaths")`.
- **Browser Actions:** Reads `.text` of metric elements after waiting for visibility.
- **Assertion:** Values are non-empty and formatted with commas (e.g. `704,753,890`).

#### TC-SEL-04: Header Navigation Bar Transitions
- **Purpose:** Verifies that primary navigation links (`navPrevention`, `navHelplines`, `navHome`) successfully transition browser state to corresponding pages.
- **Locators:** `(By.ID, "navPrevention")`, `(By.ID, "navHelplines")`, `(By.ID, "navHome")`.
- **Browser Actions:** Sequential `click()` actions and URL inspections.
- **Assertion:** Verified URL path contains `/prevention`, `/helplines`, and returns to home.

---

### 5.2 Statistics & Visualizations (`test_statistics.py`)

#### TC-SEL-05: Chart.js Trend Canvas Rendering
- **Purpose:** Verifies that the 30-day epidemiological line chart canvas is mounted in the DOM.
- **Locators:** `(By.ID, "covidTrendChart")`.
- **Browser Actions:** Navigates to `/statistics` and checks `is_displayed()`.
- **Assertion:** `assert stats_page.is_chart_rendered() is True`.
- **PASS Criteria:** `<canvas id="covidTrendChart">` element is present and visible.

#### TC-SEL-06: Country Search & Table Filter
- **Purpose:** Verifies that the search form submits a query parameter and filters the country table.
- **Locators:** `(By.ID, "countrySearchInput")`, `(By.ID, "countrySearchBtn")`, `(By.CLASS_NAME, "country-row")`.
- **Browser Actions:** `type_text("India")`, clicks search button, and waits for `q=India` in `driver.current_url`.
- **Assertion:** Result row count `>= 1` and first row text contains `"India"`.

---

### 5.3 User Authentication (`test_authentication.py`)

#### TC-SEL-07: Empty Registration Form Rejection
- **Purpose:** Verifies negative testing / boundary testing for required field enforcement.
- **Locators:** `(By.ID, "registerSubmitBtn")`, `(By.ID, "usernameError")`.
- **Browser Actions:** Submits registration form with empty inputs.
- **Assertion:** `usernameError` span is rendered containing `"Username is required"`.

#### TC-SEL-08: Invalid Email Format Rejection
- **Purpose:** Verifies regex pattern constraint enforcement on email inputs.
- **Locators:** `(By.ID, "registerEmailInput")`, `(By.ID, "emailError")`.
- **Browser Actions:** Enters `"not-a-valid-email"` and submits form.
- **Assertion:** `emailError` span displays `"valid email address"`.

#### TC-SEL-09: Valid Registration & Auto-Login Flow
- **Purpose:** Verifies positive user registration journey, session creation, and profile redirection.
- **Locators:** `registerUsernameInput`, `registerEmailInput`, `registerPasswordInput`, `registerConfirmPasswordInput`, `profileUsername`.
- **Browser Actions:** Enters valid credentials conforming to 4–20 char username and 8–64 char complex password rules, submits form.
- **Assertion:** Redirects to `/profile`, and `profileUsername` matches submitted username.

#### TC-SEL-10: Invalid Login Rejection
- **Purpose:** Verifies that invalid credentials trigger authentication failure warnings.
- **Locators:** `(By.ID, "usernameInput")`, `(By.ID, "passwordInput")`, `(By.ID, "loginAuthError")`.
- **Browser Actions:** Enters `"nonexistent_user"` and `"WrongPassword123#"`.
- **Assertion:** `loginAuthError` alert is visible with text `"Invalid username or password"`.

#### TC-SEL-11: Valid Login Workflow
- **Purpose:** Verifies successful login using seeded test credentials (`testuser` / `Test@123#`).
- **Locators:** `usernameInput`, `passwordInput`, `loginSubmitBtn`, `navUsernameBadge`.
- **Browser Actions:** Enters credentials, clicks Sign In.
- **Assertion:** User badge in navigation bar displays `"testuser"`.

#### TC-SEL-12: User Logout Workflow
- **Purpose:** Verifies session destruction and guest state restoration.
- **Locators:** `(By.ID, "navLogoutBtn")`, `(By.ID, "navLoginBtn")`.
- **Browser Actions:** Clicks Log Out button in header.
- **Assertion:** Header displays `navLoginBtn`, confirming session is terminated.

---

### 5.4 Community Discussion (`test_community.py`)

#### TC-SEL-13: Guest Visitor Restrictions
- **Purpose:** Verifies authorization guard preventing unauthenticated users from posting.
- **Locators:** `(By.ID, "loginToCommentNotice")`, `(By.ID, "commentInput")`.
- **Browser Actions:** Opens `/community` as guest.
- **Assertion:** `loginToCommentNotice` is visible and `commentInput` textarea is absent.

#### TC-SEL-14: Authenticated Comment Posting
- **Purpose:** Verifies end-to-end comment publication for authenticated users.
- **Locators:** `commentInput`, `postCommentBtn`, `(By.CLASS_NAME, "comment-text")`.
- **Browser Actions:** Logs in, navigates to `/community`, types unique timestamped note, clicks Post.
- **Assertion:** Explicit wait confirms note appears in rendered comment stream.

#### TC-SEL-15: HTML Tag / XSS Rejection in Comments
- **Purpose:** Verifies security validation against Cross-Site Scripting (XSS).
- **Locators:** `commentInput`, `postCommentBtn`, `(By.ID, "commentError")`.
- **Browser Actions:** Submits `<script>alert('xss')</script>`.
- **Assertion:** Validation error message displays `"no HTML tags"`.

#### TC-SEL-16: Deletion of Own Comment
- **Purpose:** Verifies data ownership and record removal.
- **Locators:** `(By.CLASS_NAME, "delete-comment-btn")`, `(By.CLASS_NAME, "alert-message")`.
- **Browser Actions:** Posts a comment, clicks Delete button, confirms browser dialog.
- **Assertion:** Flash message `"deleted successfully"` appears and comment is removed.

---

### 5.5 Programme Registration (`test_programme.py`)

#### TC-SEL-17: Programme Registration & Reference Generation
- **Purpose:** Verifies end-to-end enrollment workflow across multiple field types (text, email, tel, date, dropdowns, checkbox).
- **Locators:** `fullNameInput`, `emailInput`, `phoneInput`, `dobInput`, `genderSelect`, `pinCodeInput`, `stateSelect`, `serviceSelect`, `consentCheckbox`, `programmeConfirmationCard`, `programmeRefNumber`.
- **Browser Actions:** Fills all inputs (using JS event dispatch for date picker), selects options, checks consent, submits.
- **Assertion:** Confirmation card appears with a generated reference number matching regex `^COV-2026-[A-Z0-9]{5}$`.

---

### 5.6 STQA Quality Lab (`test_quality_lab.py`)

#### TC-SEL-18: Interactive Validation Lab & Automated Suite Run
- **Purpose:** Verifies the academic STQA interactive laboratory workbench.
- **Locators:** `tester-field`, `tester-input`, `tester-btn`, `tester-result`, `runSuiteBtn`, `suitePassRate`.
- **Browser Actions:**
  1. Selects Phone Number category from dropdown.
  2. Enters invalid phone number (`5876543210`) -> clicks Validate -> asserts `INVALID (FAIL)`.
  3. Enters valid phone number (`9876543210`) -> clicks Validate -> asserts `VALID (PASS)`.
  4. Clicks "Execute Test Suite" -> waits for asynchronous test execution of all 124 cases.
- **Assertion:** Pass rate displays `"100.0%"` and results table is populated.

---

## 6. Execution Commands & HTML Test Reports

### 6.1 Running Selenium Tests
```bash
# Activate environment
source .venv/bin/activate

# Execute all Selenium tests with HTML reporting
pytest tests/selenium -v --html=reports/selenium-report.html --self-contained-html
```

### 6.2 Convenience Shell Script
```bash
./scripts/run_selenium_tests.sh
```

### 6.3 Understanding Test Output
- **`PASSED`:** The scenario completed all steps, explicit waits resolved, and all assertions held true.
- **`FAILED`:** An assertion failed or an element was not found within timeout; a screenshot was saved to `reports/screenshots/`.
- **HTML Report (`reports/selenium-report.html`):** Interactive self-contained HTML report detailing execution duration, environment metadata, passed test counts, and failure traces.
