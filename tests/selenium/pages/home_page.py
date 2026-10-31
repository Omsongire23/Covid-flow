"""
Home / Dashboard Page Object for Selenium 4.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.base_page import BasePage


class HomePage(BasePage):
    """Page object representing the COVID-19 Home Dashboard."""

    # Locators
    HEADING = (By.ID, "mainDashboardHeading")
    TOTAL_CASES_CARD = (By.ID, "cardTotalCases")
    TOTAL_CASES_VALUE = (By.ID, "valTotalCases")
    ACTIVE_CASES_VALUE = (By.ID, "valActiveCases")
    TOTAL_DEATHS_VALUE = (By.ID, "valTotalDeaths")
    DATA_SOURCE_LABEL = (By.ID, "heroDataSourceLabel")
    NAV_STATS = (By.ID, "navStats")
    NAV_QUALITY_LAB = (By.ID, "navQualityLab")
    HERO_EXPLORE_STATS_BTN = (By.ID, "heroExploreStatsBtn")
    COUNTRIES_TABLE = (By.ID, "dashboardCountriesTable")

    def open(self):
        """Navigate to home dashboard."""
        return self.navigate_to("/")

    def get_main_heading_text(self) -> str:
        """Get hero heading text."""
        return self.get_text(*self.HEADING)

    def get_total_cases(self) -> str:
        """Get formatted total confirmed cases value."""
        return self.get_text(*self.TOTAL_CASES_VALUE)

    def get_active_cases(self) -> str:
        """Get formatted active cases value."""
        return self.get_text(*self.ACTIVE_CASES_VALUE)

    def get_total_deaths(self) -> str:
        """Get formatted deaths value."""
        return self.get_text(*self.TOTAL_DEATHS_VALUE)

    def click_explore_statistics(self):
        """Click hero explore statistics button."""
        self.click(*self.HERO_EXPLORE_STATS_BTN)
