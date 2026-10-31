"""
Selenium Browser Tests for STQA Quality Engineering Lab:
TC-SEL-18.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.quality_lab_page import QualityLabPage


def test_tc_sel_18_quality_lab_interactive_testing(driver, live_server):
    """TC-SEL-18: Verify Quality Lab input tester and automated suite execution."""
    lab = QualityLabPage(driver, live_server)
    lab.open()

    title = lab.get_page_title()
    assert "Quality" in title

    # Select Phone category and test invalid input
    lab.select_field("phoneIN")
    lab.test_input_value("5876543210")  # starts with 5 (invalid)

    # Wait for badge text
    import time
    time.sleep(1)
    badge = lab.get_result_badge_text()
    assert "INVALID" in badge or "FAIL" in badge

    # Test valid phone
    lab.test_input_value("9876543210")
    time.sleep(1)
    badge = lab.get_result_badge_text()
    assert "VALID" in badge or "PASS" in badge

    # Click run suite button
    lab.execute_test_suite()
    from selenium.webdriver.support.ui import WebDriverWait
    WebDriverWait(driver, 10).until(
        lambda d: "%" in lab.get_suite_pass_rate()
    )

    pass_rate = lab.get_suite_pass_rate()
    assert "100" in pass_rate
