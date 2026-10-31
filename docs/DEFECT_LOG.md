# STQA Defect Log & Triage Report
## Covid-flow — Software Testing & Quality Assurance

**Project:** Covid-flow COVID-19 Health Intelligence Portal  
**Course:** Software Testing and Quality Assurance (STQA)  
**Status:** All Identified Defects Logged, Analyzed, Fixed, and Re-Tested  

---

### Defect Summary Table

| Defect ID | Severity | Category | Description | Root Cause | Resolution | Retest Status |
|---|---|---|---|---|---|---|
| **DEF-01** | Medium | Test Fixture / Validation | `test_tc_sel_17_programme_registration_flow` failed registration confirmation check. | Test data generated a name containing numeric suffix (`Pooja Sharma 456`). The `fullName` regex rule `^(?=.{2,60}$)[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$` correctly rejected numbers. | Updated test data to supply letters-only full name (`Pooja Sharma`) while keeping unique numbers in the email. | **CLOSED (PASS)** |
| **DEF-02** | High | Test Data / Boundary | `test_tc_sel_09_valid_registration_flow` rejected valid registration with username length error. | Generated username `SeleniumUser_1761933900` had 22 characters, exceeding the approved 4–20 character constraint `{3,19}`. | Formatted test username with modulo timestamp (`SelUser_XXXXX`, 13 chars), well within bounds. | **CLOSED (PASS)** |
| **DEF-03** | Medium | Browser Automation / HTML5 | `ProgrammePage` date of birth input occasionally entered malformed date in headless Chrome. | Calling `send_keys` on native HTML5 `<input type="date">` triggered localized browser date format masks. | Implemented JavaScript value assignment and event dispatching (`arguments[0].value = ...`) in `ProgrammePage`. | **CLOSED (PASS)** |
| **DEF-04** | High | Selenium Synchronization | `ElementClickInterceptedException` on submit buttons in `ProgrammePage` and `QualityLabPage`. | Fixed navigation header overlapped buttons positioned low in the headless viewport before scrolling. | Enhanced `BasePage.click()` with `scrollIntoView({block: 'center'})` and JavaScript click fallback. | **CLOSED (PASS)** |
| **DEF-05** | Medium | Selenium Synchronization | `StaleElementReferenceException` in `CommunityPage.get_comment_texts()`. | Form submission redirected (HTTP 302 -> 200), replacing DOM elements during list comprehension. | Added `try-except StaleElementReferenceException` in `get_comment_texts()` to allow `WebDriverWait` to re-fetch refreshed elements. | **CLOSED (PASS)** |
| **DEF-06** | High | Test Configuration | Pytest failed collection with `attempted relative import with no known parent package`. | Test files used relative imports (`from .pages...`) without package execution context. | Added `tests/__init__.py` markers, configured `pytest.ini` with `pythonpath = .`, and used absolute imports. | **CLOSED (PASS)** |

---

### Defect Verification Outcome
All 6 defects encountered during the development of the test suites were triaged, fixed in the codebase, and verified through automated re-execution. As of the final test run, **zero open defects remain** across the entire 67-test suite.
