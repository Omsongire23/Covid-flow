"""
Login Page Object for Selenium 4.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.base_page import BasePage


class LoginPage(BasePage):
    """Page object for user authentication and login throttling verification."""

    USERNAME_INPUT = (By.ID, "usernameInput")
    PASSWORD_INPUT = (By.ID, "passwordInput")
    SUBMIT_BTN = (By.ID, "loginSubmitBtn")
    AUTH_ERROR = (By.ID, "loginAuthError")
    USERNAME_ERROR = (By.ID, "usernameError")
    PASSWORD_ERROR = (By.ID, "passwordError")
    TO_REGISTER_LINK = (By.ID, "toRegisterLink")

    def open(self):
        return self.navigate_to("/login")

    def login(self, username: str, password: str):
        """Perform login action."""
        self.type_text(*self.USERNAME_INPUT, username)
        self.type_text(*self.PASSWORD_INPUT, password)
        self.click(*self.SUBMIT_BTN)

    def get_auth_error_message(self) -> str:
        """Get text of authentication error alert."""
        return self.get_text(*self.AUTH_ERROR)
