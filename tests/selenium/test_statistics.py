"""
Selenium Browser Tests for Statistics and Country Search:
TC-SEL-05 and TC-SEL-06.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.statistics_page import StatisticsPage


def test_tc_sel_05_statistics_page_and_chart(driver, live_server):
    """TC-SEL-05: Verify statistics page renders Chart.js canvas."""
    stats_page = StatisticsPage(driver, live_server)
    stats_page.open()

    title = stats_page.get_page_title()
    assert "Statistics" in title

    # Verify canvas element is visible
    assert stats_page.is_chart_rendered() is True


def test_tc_sel_06_country_search(driver, live_server):
    """TC-SEL-06: Verify country search filters the country statistics table."""
    stats_page = StatisticsPage(driver, live_server)
    stats_page.open()

    # Search for India
    stats_page.search_country("India")

    # Wait for navigation and table update
    from selenium.webdriver.support.ui import WebDriverWait
    WebDriverWait(driver, 10).until(lambda d: "q=India" in d.current_url)

    # Verify filtered results contain India
    country_rows = stats_page.find_all(By.CLASS_NAME, "country-row")
    assert len(country_rows) >= 1

    first_row_text = country_rows[0].text
    assert "India" in first_row_text
