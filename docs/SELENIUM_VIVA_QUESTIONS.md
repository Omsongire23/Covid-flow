# Covid-flow — Selenium 4 Viva Questions & Answers
## Software Testing & Quality Assurance (STQA) External Examination Preparation

> **Course:** Software Testing and Quality Assurance (STQA)  
> **Topic:** Selenium 4 Browser Automation & Web Quality Assurance  
> **Project Implementation:** Python 3, Flask, SQLite, Selenium 4, pytest  

---

### Q1: What is Selenium, and what is its role in this STQA project?
**Answer:** Selenium is an open-source browser automation tool that executes real user interactions inside a web browser. In Covid-flow, Selenium 4 automates functional regression testing for critical workflows: user registration, authentication, live COVID statistics search, community comment posting, programme registration, and interactive regex validation in the Quality Lab.

### Q2: What are the key differences between Selenium 3 and Selenium 4?
**Answer:** 
1. **W3C Standardization:** Selenium 4 uses pure W3C WebDriver protocol directly, removing the legacy JSON Wire Protocol encoding layer used in Selenium 3.
2. **Native DevTools Support:** Selenium 4 has native Chrome DevTools Protocol (CDP) support for network emulation and performance metrics.
3. **Relative Locators:** Introduces `with_tag_name()` relative locators (`above`, `below`, `to_left_of`, `to_right_of`).
4. **Improved Headless Chrome:** Supports modern `--headless=new` engine instead of the older experimental headless mode.

### Q3: What is WebDriver and how does it interact with the browser?
**Answer:** WebDriver is a browser automation interface that accepts commands via standard HTTP requests conforming to the W3C protocol. It sends these commands to a browser-specific binary driver (`chromedriver`), which executes native platform actions directly inside the Google Chrome browser process.

### Q4: What is the Page Object Model (POM) and why did you use it?
**Answer:** Page Object Model is a design pattern where each web page or major UI component is represented as an independent Python class (e.g., `HomePage`, `LoginPage`, `CommunityPage`). It separates element locators and browser interactions from test assertions. If a locator or UI design changes, only the corresponding Page Object needs updating, preventing test script duplication across the suite.

### Q5: How is the Page Object Model structured in your project?
**Answer:** All page objects inherit from a parent `BasePage` in `tests/selenium/pages/base_page.py`. `BasePage` encapsulates core synchronization (`WebDriverWait`), click handling with automatic scrolling, and text input. Subclasses like `RegisterPage` or `QualityLabPage` expose clean domain-specific methods such as `register()` or `execute_test_suite()`.

### Q6: What is an Explicit Wait, and why is it preferred over `time.sleep()`?
**Answer:** An Explicit Wait pauses test execution until a specific condition (e.g. `visibility_of_element_located` or `element_to_be_clickable`) evaluates to true or a maximum timeout expires. `time.sleep()` halts the thread blindly regardless of element readiness, causing unnecessary test slowdowns or flaky failures. Explicit waits poll every 500ms and proceed immediately when ready.

### Q7: What is the difference between an Implicit Wait and an Explicit Wait?
**Answer:** 
- **Implicit Wait:** Globally configures the WebDriver instance to wait a specified duration when searching for *any* element before throwing `NoSuchElementException`.
- **Explicit Wait:** Target-specific synchronization applied to individual elements for distinct states (visible, clickable, text present, URL changed). Our project uses a minimal 1s implicit wait and relies primarily on explicit `WebDriverWait` in page objects.

### Q8: What element locators are used in your Selenium tests, and which is the fastest?
**Answer:** We prioritize **ID** locators (e.g., `By.ID, "usernameInput"`), followed by **Class Name** (`By.CLASS_NAME, "comment-item"`), **Tag Name**, and **CSS Selectors**. ID is the fastest and most stable locator because browsers utilize optimized internal hash-map lookups for unique element IDs.

### Q9: What is `StaleElementReferenceException` and how did you resolve it?
**Answer:** It occurs when an element located previously is no longer attached to the DOM (for example, when a form submits and the server redirects, reloading the HTML page). In `CommunityPage.get_comment_texts()`, we catch `StaleElementReferenceException` gracefully inside a `try-except` block, allowing `WebDriverWait` to re-query the refreshed DOM.

### Q10: What is `ElementClickInterceptedException` and how is it handled in your framework?
**Answer:** It happens when another element (such as a sticky navigation header or floating alert) obscures the target element during a click. In `BasePage.click()`, we first scroll the target into view (`scrollIntoView({block: 'center'})`) and fall back to JavaScript execution (`driver.execute_script("arguments[0].click();", element)`) if standard native click is intercepted.

### Q11: How does your test suite launch the Flask application for automated tests?
**Answer:** The `live_server` fixture in `tests/selenium/conftest.py` spins up a background Python daemon thread running Werkzeug's `make_server` on a dynamically assigned free TCP port. It creates an isolated temporary SQLite database (`selenium_test.sqlite3`), seeds test accounts, runs all tests, and terminates cleanly when tests finish.

### Q12: Why run Chrome in `--headless=new` mode?
**Answer:** Headless mode executes Google Chrome without opening a visible desktop GUI window. This allows automated tests to run on headless Linux servers, CI/CD pipelines, and background terminal processes, while consuming fewer CPU and RAM resources and running significantly faster.

### Q13: How do your tests verify user registration (TC-SEL-07 to TC-SEL-09)?
**Answer:**
- **TC-SEL-07:** Submits an empty registration form and asserts that client/server validation errors (`usernameError`) are displayed.
- **TC-SEL-08:** Submits an invalid email (`not-a-valid-email`) and asserts rejection.
- **TC-SEL-09:** Submits valid, uniquely generated credentials, verifies automatic redirect to `/profile`, and asserts that the profile username matches.

### Q14: How is HTML5 `<input type="date">` handled in Selenium for the date of birth field?
**Answer:** Directly typing into a native HTML5 date picker using `send_keys` can fail or cause locale mismatch (e.g. `DD-MM-YYYY` vs `YYYY-MM-DD`). In `ProgrammePage`, we use JavaScript execution: `self.driver.execute_script("arguments[0].value = arguments[1]; arguments[0].dispatchEvent(new Event('change'));", dob_el, data["dob"])` to set the ISO date string reliably.

### Q15: How does your test suite test dropdown `<select>` elements?
**Answer:** We use Selenium's built-in `Select` support class: `Select(driver.find_element(By.ID, "genderSelect")).select_by_value("female")`. It automatically validates that the `<option>` exists and triggers native selection events.

### Q16: How does your test suite test checkbox inputs?
**Answer:** In `ProgrammePage`, we locate the consent checkbox, inspect its current state via `checkbox.is_selected()`, and invoke `click()` only if it is not already selected.

### Q17: How is Cross-Site Scripting (XSS) prevention tested in Selenium (TC-SEL-15)?
**Answer:** The test attempts to submit an active script payload: `<script>alert('xss')</script>` in the community comment form. The test asserts that the submission is rejected by the server regex validator and displays `"no HTML tags"`, ensuring no raw script tags are stored or executed in the DOM.

### Q18: How does your framework capture screenshots on failure?
**Answer:** In `conftest.py`, the `pytest_runtest_makereport` hook monitors test outcomes. If `report.failed` is true during the call phase, it retrieves the active `driver` instance, takes a screenshot via `driver.save_screenshot()`, and stores a timestamped PNG in `reports/screenshots/`.

### Q19: How is the HTML test report generated?
**Answer:** Using the `pytest-html` plugin with the command:
`pytest tests/selenium -v --html=reports/selenium-report.html --self-contained-html`.
This generates a single standalone HTML document containing test durations, passed/failed statuses, environment metadata, and inline failure diagnostics.

### Q20: What is the difference between `find_element()` and `find_elements()`?
**Answer:**
- `find_element()` returns the first matching `WebElement`. If no element matches, it throws `NoSuchElementException`.
- `find_elements()` returns a Python `list` of all matching `WebElements`. If no elements match, it returns an empty list `[]` without raising an exception.

### Q21: What is the difference between `driver.close()` and `driver.quit()`?
**Answer:**
- `driver.close()` closes the current focused browser window/tab.
- `driver.quit()` terminates the entire browser process, kills the `chromedriver` server session, and releases system memory. Our fixture always calls `driver.quit()`.

### Q22: What are Expected Conditions in Selenium?
**Answer:** `expected_conditions` (aliased as `EC`) is a collection of predefined predicates used with `WebDriverWait`. Common examples in our project include: `EC.visibility_of_element_located`, `EC.presence_of_element_located`, `EC.element_to_be_clickable`, and custom lambdas for URL/text changes.

### Q23: How does TC-SEL-05 verify that Chart.js has rendered the epidemiological chart?
**Answer:** It navigates to `/statistics` and checks `stats_page.is_chart_rendered()`, which verifies that the `<canvas id="covidTrendChart">` element is present and rendered visible by the browser engine.

### Q24: How does TC-SEL-06 verify country search functionality?
**Answer:** It types `"India"` into `countrySearchInput`, clicks `countrySearchBtn`, waits for the URL to update with `q=India`, queries matching `.country-row` elements in the table, and asserts that the first row contains `"India"`.

### Q25: What is the difference between Black-Box Testing and White-Box Testing in this project?
**Answer:**
- **Black-Box Testing:** Selenium automated browser tests interact with the application solely through the GUI as external end-users, without accessing internal code or SQLite tables directly.
- **White-Box / Grey-Box Testing:** Unit and integration tests (`tests/unit/`, `tests/integration/`) directly inspect internal validator functions, SQL queries, and session state.

### Q26: What is Boundary Value Analysis (BVA) and how is it tested?
**Answer:** BVA tests values at the minimum, maximum, and extreme boundaries of an input class. For example, comments allow 3 to 500 characters:
- 2 chars: Invalid (rejected)
- 3 chars: Valid boundary (accepted)
- 500 chars: Valid boundary (accepted)
- 501 chars: Invalid boundary (rejected)
These boundary cases are verified both in `test_validators.py` and via Selenium in `test_quality_lab.py`.

### Q27: What is Equivalence Partitioning (EP)?
**Answer:** EP divides input data into valid and invalid partitions such that all members of a partition are processed identically. For instance, in Indian PIN codes, all 6-digit numbers [100000–999999] form the valid partition, while numbers starting with 0 or strings with letters form invalid partitions.

### Q28: How does the Quality Lab demonstrate automated testing (TC-SEL-18)?
**Answer:** In TC-SEL-18, Selenium opens `/quality-lab`, validates single phone inputs to test dynamic feedback, and clicks the "Execute Test Suite" button. Selenium asserts that all 124 regression test cases execute asynchronously against the backend API and report a `100.0%` pass rate.

### Q29: What is regression testing and how does your Selenium suite support it?
**Answer:** Regression testing verifies that recent code changes or bug fixes have not broken existing functionality. Our automated suite of 18 Selenium scenarios runs in under 50 seconds, allowing full functional regression verification on every build.

### Q30: What is the difference between Verification and Validation in Software QA?
**Answer:**
- **Verification:** "Are we building the product right?" Ensuring the code adheres to architectural rules, coding conventions, regex schemas, and unit test contracts.
- **Validation:** "Are we building the right product?" Ensuring the running software satisfies actual user requirements and public health workflows through automated Selenium browser testing.

### Q31: How do you verify that session authentication works and persists across pages?
**Answer:** After submitting credentials in `test_tc_sel_11_valid_login_flow`, the test inspects the navigation bar for `#navUsernameBadge` displaying `"testuser"`, navigates to protected `/profile`, and verifies that account details are accessible without redirecting to `/login`.

### Q32: What defect did you encounter during test development and how did you resolve it?
**Answer:** During initial Selenium execution, `test_tc_sel_17_programme_registration_flow` failed because the test generated a name containing numbers (`Pooja Sharma 456`). The centralized `fullName` validator correctly rejected digits based on the rule `2–60 letters only`. We updated the test fixture to supply a valid name and verified that the form successfully generated reference `COV-2026-XXXXX`.
