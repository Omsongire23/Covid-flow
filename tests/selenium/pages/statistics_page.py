"""
Statistics Page Object for Selenium 4.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.base_page import BasePage


class StatisticsPage(BasePage):
    """Page object for COVID-19 Statistics, Chart, and Country Table."""

    HEADING = (By.ID, "statsPageHeading")
    CHART_CANVAS = (By.ID, "covidTrendChart")
    BTN_CASES = (By.ID, "btnChartMetricCases")
    BTN_DEATHS = (By.ID, "btnChartMetricDeaths")
    SEARCH_INPUT = (By.ID, "countrySearchInput")
    SEARCH_BTN = (By.ID, "countrySearchBtn")
    COUNTRIES_TABLE = (By.ID, "countriesStatsTable")
    COUNTRY_ROWS = (By.CLASS_NAME, "country-row")

    def open(self):
        return self.navigate_to("/statistics")

    def search_country(self, country_name: str):
        """Enter search query and submit search."""
        self.type_text(*self.SEARCH_INPUT, country_name)
        self.click(*self.SEARCH_BTN)

    def is_chart_rendered(self) -> bool:
        """Check if Chart.js canvas is visible in DOM."""
        return self.is_visible(*self.CHART_CANVAS)

    def get_visible_countries_count(self) -> int:
        """Return count of visible rows in countries table."""
        rows = self.find_all(*self.COUNTRY_ROWS)
        return len([r for r in rows if r.is_displayed()])
