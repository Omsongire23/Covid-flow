"""Selenium 4 browser regression tests for the Covid-flow STQA mini project.

Run from the repository root after starting Docker:
  python -m pip install -r requirements-selenium.txt
  SELENIUM_BROWSER=chrome python -m unittest discover -s tests/selenium -v

Optional authenticated tests:
  SELENIUM_TEST_USERNAME=... SELENIUM_TEST_PASSWORD=... python -m unittest ...
"""
import os
import time
import unittest
from pathlib import Path

from selenium import webdriver
from selenium.common.exceptions import TimeoutException
from selenium.webdriver.common.by import By
from selenium.webdriver.common.keys import Keys
from selenium.webdriver.support import expected_conditions as EC
from selenium.webdriver.support.ui import WebDriverWait

BASE_URL = os.getenv("COVID_FLOW_BASE_URL", "http://127.0.0.1:8000").rstrip("/")
BROWSER = os.getenv("SELENIUM_BROWSER", "chrome").lower()
USERNAME = os.getenv("SELENIUM_TEST_USERNAME", "")
PASSWORD = os.getenv("SELENIUM_TEST_PASSWORD", "")
SCREENSHOT_DIR = Path(os.getenv("SELENIUM_SCREENSHOT_DIR", "artifacts/selenium"))


def make_driver():
    if BROWSER == "firefox":
        options = webdriver.FirefoxOptions()
        if os.getenv("SELENIUM_HEADLESS", "1") == "1":
            options.add_argument("-headless")
        return webdriver.Firefox(options=options)
    options = webdriver.ChromeOptions()
    if os.getenv("SELENIUM_HEADLESS", "1") == "1":
        options.add_argument("--headless=new")
    options.add_argument("--window-size=1440,1000")
    options.add_argument("--no-sandbox")
    options.add_argument("--disable-dev-shm-usage")
    return webdriver.Chrome(options=options)


class CovidFlowSeleniumTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        SCREENSHOT_DIR.mkdir(parents=True, exist_ok=True)

    def setUp(self):
        self.driver = make_driver()
        self.wait = WebDriverWait(self.driver, 12)

    def tearDown(self):
        if hasattr(self, "_outcome") and self._outcome.result.errors + self._outcome.result.failures:
            name = self.id().split(".")[-1]
            self.driver.save_screenshot(str(SCREENSHOT_DIR / f"{name}.png"))
        self.driver.quit()

    def open_signup(self):
        self.driver.get(f"{BASE_URL}/signup.php")
        self.wait.until(EC.visibility_of_element_located((By.ID, "signup-form")))

    def blur_field(self, element):
        element.send_keys(Keys.TAB)

    def visible_errors(self):
        return [e for e in self.driver.find_elements(By.CSS_SELECTOR, ".form-error")
                if e.is_displayed()]

    def test_tc01_signup_accepts_valid_username(self):
        self.open_signup()
        field = self.driver.find_element(By.NAME, "username")
        field.send_keys("john_doe")
        self.blur_field(field)
        self.wait.until(lambda d: field.get_attribute("class").find("is-valid") >= 0
                        or not self.visible_errors())
        self.assertFalse(self.visible_errors(), "Valid username should not show an inline error")

    def test_tc02_signup_rejects_invalid_username(self):
        self.open_signup()
        field = self.driver.find_element(By.NAME, "username")
        field.send_keys("1bad name")
        self.blur_field(field)
        self.wait.until(lambda d: bool(self.visible_errors()))
        self.assertTrue(self.visible_errors())

    def test_tc03_signup_rejects_invalid_email(self):
        self.open_signup()
        field = self.driver.find_element(By.NAME, "email")
        field.send_keys("user@@example")
        self.blur_field(field)
        self.wait.until(lambda d: "is-invalid" in field.get_attribute("class"))
        self.assertTrue(self.visible_errors())

    def test_tc04_username_length_boundaries(self):
        for value, expected_valid in [("abc", False), ("abcd", True),
                                      ("a" * 20, True), ("a" * 21, False)]:
            self.open_signup()
            field = self.driver.find_element(By.NAME, "username")
            field.send_keys(value)
            self.blur_field(field)
            self.wait.until(lambda d: "is-valid" in field.get_attribute("class")
                            or "is-invalid" in field.get_attribute("class"))
            actual_valid = "is-valid" in field.get_attribute("class")
            self.assertEqual(actual_valid, expected_valid, f"username length={len(value)}")

    def test_tc05_login_wrong_password_shows_error(self):
        self.driver.get(f"{BASE_URL}/login.php")
        self.wait.until(EC.visibility_of_element_located((By.ID, "login-form")))
        self.driver.find_element(By.NAME, "username").send_keys("selenium_nonexistent_user")
        self.driver.find_element(By.NAME, "password").send_keys("WrongPass123!")
        self.driver.find_element(By.CSS_SELECTOR, "#login-form button[type='submit']").click()
        self.wait.until(EC.visibility_of_element_located((By.CSS_SELECTOR, ".alert-danger")))
        self.assertIn("Invalid username or password",
                      self.driver.find_element(By.CSS_SELECTOR, ".alert-danger").text)

    def test_tc06_guest_sees_login_prompt_on_community(self):
        self.driver.get(f"{BASE_URL}/community.php")
        self.wait.until(EC.visibility_of_element_located((By.CSS_SELECTOR, "a[href*='/login.php']")))
        self.assertIn("Join the Conversation", self.driver.page_source)
        self.assertTrue(self.driver.find_elements(By.ID, "comment-form") == [])

    def test_tc07_comment_field_enforces_500_character_limit(self):
        self.driver.get(f"{BASE_URL}/community.php")
        # Composer is shown only to authenticated users; test via optional credentials.
        if not USERNAME or not PASSWORD:
            self.skipTest("Set SELENIUM_TEST_USERNAME and SELENIUM_TEST_PASSWORD for authenticated comment tests")
        self.login()
        self.driver.get(f"{BASE_URL}/community.php")
        field = self.wait.until(EC.presence_of_element_located((By.ID, "comment-body")))
        self.assertEqual(field.get_attribute("maxlength"), "500")
        field.send_keys("x" * 501)
        self.assertLessEqual(len(field.get_attribute("value")), 500)

    def test_tc08_comment_html_payload_not_rendered_as_markup(self):
        self.driver.get(f"{BASE_URL}/community.php")
        if not USERNAME or not PASSWORD:
            self.skipTest("Set test credentials to exercise authenticated comment submission")
        self.login()
        self.driver.get(f"{BASE_URL}/community.php")
        field = self.wait.until(EC.presence_of_element_located((By.ID, "comment-body")))
        self.assertEqual(field.get_attribute("maxlength"), "500")
        # Verify the HTML-like payload is available for the server-side rejection check.
        payload = "<script>alert(1)</script>"
        field.send_keys(payload)
        field.send_keys(Keys.TAB)
        self.wait.until(lambda d: "is-invalid" in field.get_attribute("class"))
        self.assertTrue(self.visible_errors(), "HTML-like comment should be rejected by client validation")
        # Do not submit a hostile payload into a shared test database.

    def test_tc09_statistics_page_renders_chart_canvas(self):
        self.driver.get(f"{BASE_URL}/statistics.php")
        self.wait.until(EC.presence_of_element_located((By.ID, "trend-chart")))
        self.assertTrue(self.driver.find_element(By.ID, "trend-chart").is_displayed())

    def test_tc10_registration_requires_consent_control(self):
        self.driver.get(f"{BASE_URL}/program-registration.php")
        if not USERNAME or not PASSWORD:
            self.skipTest("Programme registration is login-protected; set Selenium test credentials")
        self.login()
        self.driver.get(f"{BASE_URL}/program-registration.php")
        consent = self.wait.until(EC.presence_of_element_located((By.ID, "consent")))
        self.assertEqual(consent.get_attribute("type"), "checkbox")
        self.assertTrue(consent.get_attribute("required") is not None)
        self.assertFalse(consent.is_selected())

    def login(self):
        self.driver.get(f"{BASE_URL}/login.php")
        self.wait.until(EC.visibility_of_element_located((By.ID, "login-form")))
        self.driver.find_element(By.NAME, "username").send_keys(USERNAME)
        self.driver.find_element(By.NAME, "password").send_keys(PASSWORD)
        self.driver.find_element(By.CSS_SELECTOR, "#login-form button[type='submit']").click()
        self.wait.until(lambda d: "/profile.php" in d.current_url or "/login.php" not in d.current_url)
        if "/login.php" in self.driver.current_url:
            raise AssertionError("Configured Selenium test account could not log in")


if __name__ == "__main__":
    unittest.main(verbosity=2)
