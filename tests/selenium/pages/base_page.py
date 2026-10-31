"""
Base Page Object for Selenium 4 Test Automation Suite.
Provides core browser interaction methods, explicit waits,
and resilient locator strategies.
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
from selenium.common.exceptions import TimeoutException


class BasePage:
    """Base class for all Page Objects in the Covid-flow POM hierarchy."""

    def __init__(self, driver, base_url="http://127.0.0.1:5000"):
        self.driver = driver
        self.base_url = base_url.rstrip("/")
        self.timeout = 10

    def navigate_to(self, relative_path=""):
        """Navigate to relative or absolute page path."""
        target_url = f"{self.base_url}/{relative_path.lstrip('/')}"
        self.driver.get(target_url)
        return self

    def find(self, by: str, locator: str, timeout: int | None = None):
        """Wait for an element to be present in DOM and return it."""
        t = timeout or self.timeout
        return WebDriverWait(self.driver, t).until(
            EC.presence_of_element_located((by, locator))
        )

    def find_visible(self, by: str, locator: str, timeout: int | None = None):
        """Wait for an element to be visible in viewport and return it."""
        t = timeout or self.timeout
        return WebDriverWait(self.driver, t).until(
            EC.visibility_of_element_located((by, locator))
        )

    def find_all(self, by: str, locator: str):
        """Find all matching elements present."""
        return self.driver.find_elements(by, locator)

    def click(self, by: str, locator: str, timeout: int | None = None):
        """Wait for element to be clickable, scroll into view, and click it."""
        t = timeout or self.timeout
        element = WebDriverWait(self.driver, t).until(
            EC.element_to_be_clickable((by, locator))
        )
        self.driver.execute_script("arguments[0].scrollIntoView({block: 'center', inline: 'nearest'});", element)
        try:
            element.click()
        except Exception:
            self.driver.execute_script("arguments[0].click();", element)

    def type_text(self, by: str, locator: str, text: str, clear_first: bool = True):
        """Send keys to input element after waiting for visibility."""
        el = self.find_visible(by, locator)
        if clear_first:
            el.clear()
        el.send_keys(text)

    def get_text(self, by: str, locator: str) -> str:
        """Get visible text content of element."""
        return self.find_visible(by, locator).text.strip()

    def get_attribute(self, by: str, locator: str, attribute: str) -> str | None:
        """Get attribute value from element."""
        return self.find(by, locator).get_attribute(attribute)

    def is_visible(self, by: str, locator: str, timeout: int = 3) -> bool:
        """Check if an element is visible within a short timeout."""
        try:
            WebDriverWait(self.driver, timeout).until(
                EC.visibility_of_element_located((by, locator))
            )
            return True
        except TimeoutException:
            return False

    def get_page_title(self) -> str:
        """Return the browser window document title."""
        return self.driver.title

    def get_current_url(self) -> str:
        """Return current browser URL."""
        return self.driver.current_url
