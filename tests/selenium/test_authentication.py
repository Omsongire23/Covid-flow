"""
Selenium Browser Tests for Authentication and Registration Workflows:
TC-SEL-07 to TC-SEL-12.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.login_page import LoginPage
from tests.selenium.pages.register_page import RegisterPage


def test_tc_sel_07_empty_registration_rejected(driver, live_server):
    """TC-SEL-07: Verify empty registration submission is rejected with validation error."""
    register_page = RegisterPage(driver, live_server)
    register_page.open()

    register_page.submit_empty()

    # Verify field error message is displayed
    assert register_page.is_visible(By.ID, "usernameError")
    err_text = register_page.get_text(By.ID, "usernameError")
    assert "Username is required" in err_text or "4–20" in err_text


def test_tc_sel_08_invalid_email_rejected(driver, live_server):
    """TC-SEL-08: Verify invalid email address is rejected."""
    register_page = RegisterPage(driver, live_server)
    register_page.open()

    register_page.register(
        username="TestUserValid",
        email="not-a-valid-email",
        password="ValidPassword1#",
        confirm_password="ValidPassword1#"
    )

    assert register_page.is_visible(By.ID, "emailError")
    err_text = register_page.get_text(By.ID, "emailError")
    assert "valid email address" in err_text


def test_tc_sel_09_valid_registration_flow(driver, live_server):
    """TC-SEL-09: Verify valid user registration logs in and opens profile."""
    register_page = RegisterPage(driver, live_server)
    register_page.open()

    import time
    unique_user = f"SelUser_{int(time.time()) % 100000}"
    register_page.register(
        username=unique_user,
        email=f"{unique_user.lower()}@example.com",
        password="StrongPassword123#",
        confirm_password="StrongPassword123#"
    )

    # After registration, wait for redirect to profile
    from selenium.webdriver.support.ui import WebDriverWait
    WebDriverWait(driver, 10).until(lambda d: "/profile" in d.current_url)
    username_display = driver.find_element(By.ID, "profileUsername").text
    assert unique_user in username_display


def test_tc_sel_10_invalid_login_rejected(driver, live_server):
    """TC-SEL-10: Verify incorrect credentials show authentication error."""
    login_page = LoginPage(driver, live_server)
    login_page.open()

    login_page.login("nonexistent_user", "WrongPassword123#")

    assert login_page.is_visible(By.ID, "loginAuthError")
    error_msg = login_page.get_auth_error_message()
    assert "Invalid username or password" in error_msg


def test_tc_sel_11_valid_login_flow(driver, live_server):
    """TC-SEL-11: Verify valid login succeeds and shows username badge in header."""
    login_page = LoginPage(driver, live_server)
    login_page.open()

    login_page.login("testuser", "Test@123#")

    from selenium.webdriver.support.ui import WebDriverWait
    WebDriverWait(driver, 10).until(lambda d: "/profile" in d.current_url)
    badge_text = driver.find_element(By.ID, "navUsernameBadge").text
    assert "testuser" in badge_text


def test_tc_sel_12_logout_flow(driver, live_server):
    """TC-SEL-12: Verify user logout clears session."""
    login_page = LoginPage(driver, live_server)
    login_page.open()
    login_page.login("testuser", "Test@123#")

    # Click logout button in header
    logout_btn = driver.find_element(By.ID, "navLogoutBtn")
    logout_btn.click()

    assert driver.find_element(By.ID, "navLoginBtn").is_displayed()
