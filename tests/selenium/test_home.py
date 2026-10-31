"""
Selenium Browser Tests for Home Dashboard and Navigation:
TC-SEL-01 to TC-SEL-04.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.home_page import HomePage


def test_tc_sel_01_application_opens(driver, live_server):
    """TC-SEL-01: Verify application loads and returns correct title."""
    home = HomePage(driver, live_server)
    home.open()

    title = home.get_page_title()
    assert "COVID-19 Dashboard" in title or "Covid-flow" in title
    assert live_server in home.get_current_url()


def test_tc_sel_02_heading_displayed(driver, live_server):
    """TC-SEL-02: Verify home page hero heading is visible."""
    home = HomePage(driver, live_server)
    home.open()

    heading = home.get_main_heading_text()
    assert "COVID-19 Health Intelligence" in heading


def test_tc_sel_03_dashboard_metrics_displayed(driver, live_server):
    """TC-SEL-03: Verify dashboard displays non-empty global statistics."""
    home = HomePage(driver, live_server)
    home.open()

    total_cases = home.get_total_cases()
    active_cases = home.get_active_cases()
    total_deaths = home.get_total_deaths()

    assert total_cases != "" and total_cases != "N/A"
    assert active_cases != ""
    assert total_deaths != "" and total_deaths != "N/A"


def test_tc_sel_04_navigation_links(driver, live_server):
    """TC-SEL-04: Verify header navigation links work properly."""
    home = HomePage(driver, live_server)
    home.open()

    # Navigate to Prevention
    home.click(By.ID, "navPrevention")
    assert "/prevention" in home.get_current_url()
    assert "Prevention" in home.get_page_title()

    # Navigate to Helplines
    home.click(By.ID, "navHelplines")
    assert "/helplines" in home.get_current_url()
    assert "Helpline" in home.get_page_title()

    # Navigate back to Dashboard
    home.click(By.ID, "navHome")
    curr_url = home.get_current_url().rstrip("/")
    assert curr_url.endswith("/index") or curr_url == live_server.rstrip("/")
