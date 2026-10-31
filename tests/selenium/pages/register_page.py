"""
Registration Page Object for Selenium 4.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.base_page import BasePage


class RegisterPage(BasePage):
    """Page object for user registration workflows and validation testing."""

    USERNAME_INPUT = (By.ID, "registerUsernameInput")
    EMAIL_INPUT = (By.ID, "registerEmailInput")
    PASSWORD_INPUT = (By.ID, "registerPasswordInput")
    CONFIRM_PASSWORD_INPUT = (By.ID, "registerConfirmPasswordInput")
    SUBMIT_BTN = (By.ID, "registerSubmitBtn")

    USERNAME_ERROR = (By.ID, "usernameError")
    EMAIL_ERROR = (By.ID, "emailError")
    PASSWORD_ERROR = (By.ID, "passwordError")
    CONFIRM_ERROR = (By.ID, "confirmPasswordError")

    def open(self):
        return self.navigate_to("/register")

    def register(self, username: str, email: str, password: str, confirm_password: str):
        """Fill registration form and submit."""
        self.type_text(*self.USERNAME_INPUT, username)
        self.type_text(*self.EMAIL_INPUT, email)
        self.type_text(*self.PASSWORD_INPUT, password)
        self.type_text(*self.CONFIRM_PASSWORD_INPUT, confirm_password)
        self.click(*self.SUBMIT_BTN)

    def submit_empty(self):
        """Click register button without filling inputs."""
        self.click(*self.SUBMIT_BTN)
