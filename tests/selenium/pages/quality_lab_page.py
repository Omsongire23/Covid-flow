"""
Quality Lab Page Object for Selenium 4.
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from tests.selenium.pages.base_page import BasePage


class QualityLabPage(BasePage):
    """Page object for STQA Quality Engineering Lab."""

    HEADING = (By.ID, "qualityLabHeading")
    FIELD_SELECT = (By.ID, "tester-field")
    PATTERN_DISPLAY = (By.ID, "tester-pattern")
    INPUT_FIELD = (By.ID, "tester-input")
    VALIDATE_BTN = (By.ID, "tester-btn")
    RESULT_BADGE = (By.ID, "tester-result")
    BTN_VALID_EXAMPLE = (By.ID, "btnValidExample")
    BTN_INVALID_EXAMPLE = (By.ID, "btnInvalidExample")

    RUN_SUITE_BTN = (By.ID, "runSuiteBtn")
    SUITE_METRICS_GRID = (By.ID, "suiteMetricsGrid")
    TOTAL_TESTS_VAL = (By.ID, "suiteTotalTests")
    PASSED_TESTS_VAL = (By.ID, "suitePassedTests")
    PASS_RATE_VAL = (By.ID, "suitePassRate")
    RESULTS_TABLE = (By.ID, "suiteResultsTable")

    def open(self):
        return self.navigate_to("/quality-lab")

    def select_field(self, field_name: str):
        """Select a validation category from dropdown."""
        Select(self.find(*self.FIELD_SELECT)).select_by_value(field_name)

    def test_input_value(self, value: str):
        """Type test value and click Validate Input."""
        self.type_text(*self.INPUT_FIELD, value)
        self.click(*self.VALIDATE_BTN)

    def get_result_badge_text(self) -> str:
        """Get the text of the PASS/FAIL result badge."""
        return self.get_text(*self.RESULT_BADGE)

    def execute_test_suite(self):
        """Click the Execute Test Suite button."""
        self.click(*self.RUN_SUITE_BTN)

    def get_suite_pass_rate(self) -> str:
        """Get pass rate text from summary metrics."""
        return self.get_text(*self.PASS_RATE_VAL)
