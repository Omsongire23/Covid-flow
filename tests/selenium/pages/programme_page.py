"""
Programme Registration Page Object for Selenium 4.
"""

from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
from tests.selenium.pages.base_page import BasePage


class ProgrammePage(BasePage):
    """Page object for Programme Registration and Reference Confirmation."""

    FULL_NAME_INPUT = (By.ID, "fullNameInput")
    EMAIL_INPUT = (By.ID, "emailInput")
    PHONE_INPUT = (By.ID, "phoneInput")
    DOB_INPUT = (By.ID, "dobInput")
    GENDER_SELECT = (By.ID, "genderSelect")
    PIN_CODE_INPUT = (By.ID, "pinCodeInput")
    STATE_SELECT = (By.ID, "stateSelect")
    SERVICE_SELECT = (By.ID, "serviceSelect")
    CONSENT_CHECKBOX = (By.ID, "consentCheckbox")
    SUBMIT_BTN = (By.ID, "programmeSubmitBtn")

    CONFIRMATION_CARD = (By.ID, "programmeConfirmationCard")
    REF_NUMBER = (By.ID, "programmeRefNumber")

    def open(self):
        return self.navigate_to("/programme")

    def fill_and_submit(self, data: dict):
        """Fill all form fields and submit."""
        if "full_name" in data:
            self.type_text(*self.FULL_NAME_INPUT, data["full_name"])
        if "email" in data:
            self.type_text(*self.EMAIL_INPUT, data["email"])
        if "phone" in data:
            self.type_text(*self.PHONE_INPUT, data["phone"])
        if "dob" in data:
            dob_el = self.find(*self.DOB_INPUT)
            self.driver.execute_script("arguments[0].value = arguments[1]; arguments[0].dispatchEvent(new Event('change'));", dob_el, data["dob"])
        if "gender" in data:
            Select(self.find(*self.GENDER_SELECT)).select_by_value(data["gender"])
        if "pin_code" in data:
            self.type_text(*self.PIN_CODE_INPUT, data["pin_code"])
        if "state" in data:
            Select(self.find(*self.STATE_SELECT)).select_by_value(data["state"])
        if "service" in data:
            Select(self.find(*self.SERVICE_SELECT)).select_by_value(data["service"])
        if data.get("consent", False):
            checkbox = self.find(*self.CONSENT_CHECKBOX)
            if not checkbox.is_selected():
                self.click(*self.CONSENT_CHECKBOX)

        self.click(*self.SUBMIT_BTN)

    def is_confirmed(self) -> bool:
        """Check if confirmation card is rendered."""
        return self.is_visible(*self.CONFIRMATION_CARD)

    def get_reference_number(self) -> str:
        """Return the unique reference number from confirmation card."""
        return self.get_text(*self.REF_NUMBER)
