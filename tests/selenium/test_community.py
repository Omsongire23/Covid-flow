"""
Selenium Browser Tests for Community Discussion:
TC-SEL-13 to TC-SEL-16.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.community_page import CommunityPage
from tests.selenium.pages.login_page import LoginPage


def test_tc_sel_13_guest_community_restrictions(driver, live_server):
    """TC-SEL-13: Verify unauthenticated guests cannot post comments and see login prompt."""
    comm_page = CommunityPage(driver, live_server)
    comm_page.open()

    assert comm_page.is_guest_login_notice_visible() is True
    # Verify comment text input is NOT present for guest
    assert not comm_page.is_visible(By.ID, "commentInput")


def test_tc_sel_14_authenticated_comment_creation(driver, live_server):
    """TC-SEL-14: Verify logged in user can successfully submit a community note."""
    login_page = LoginPage(driver, live_server)
    login_page.open()
    login_page.login("testuser", "Test@123#")

    comm_page = CommunityPage(driver, live_server)
    comm_page.open()

    import time
    from selenium.webdriver.support.ui import WebDriverWait
    comment_text = f"Automated test comment posted at timestamp {int(time.time())}"
    comm_page.post_comment(comment_text)

    # Wait for comment text to appear in comments list
    WebDriverWait(driver, 10).until(
        lambda d: any(comment_text in c for c in comm_page.get_comment_texts())
    )
    comments = comm_page.get_comment_texts()
    assert any(comment_text in c for c in comments)


def test_tc_sel_15_comment_xss_validation(driver, live_server):
    """TC-SEL-15: Verify comment with HTML tags is rejected."""
    login_page = LoginPage(driver, live_server)
    login_page.open()
    login_page.login("testuser", "Test@123#")

    comm_page = CommunityPage(driver, live_server)
    comm_page.open()

    comm_page.post_comment("<script>alert('xss')</script>")

    assert comm_page.is_visible(By.ID, "commentError")
    err_text = comm_page.get_text(By.ID, "commentError")
    assert "no HTML tags" in err_text


def test_tc_sel_16_delete_own_comment(driver, live_server):
    """TC-SEL-16: Verify user can delete their own comment."""
    login_page = LoginPage(driver, live_server)
    login_page.open()
    login_page.login("testuser", "Test@123#")

    comm_page = CommunityPage(driver, live_server)
    comm_page.open()

    import time
    from selenium.webdriver.support.ui import WebDriverWait
    unique_to_delete = f"DeleteMeComment_{int(time.time())}"
    comm_page.post_comment(unique_to_delete)

    # Wait for the comment to be rendered before deleting
    WebDriverWait(driver, 10).until(
        lambda d: any(unique_to_delete in c for c in comm_page.get_comment_texts())
    )

    # Locate and delete
    comm_page.delete_first_comment()

    # Verify flash message or removal
    alert_box = comm_page.get_text(By.CLASS_NAME, "alert-message")
    assert "deleted successfully" in alert_box.lower()
