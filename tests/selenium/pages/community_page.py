"""
Community Page Object for Selenium 4.
"""

from selenium.webdriver.common.by import By
from tests.selenium.pages.base_page import BasePage


class CommunityPage(BasePage):
    """Page object for Community Discussions, comment posting, and deletion."""

    COMMENT_INPUT = (By.ID, "commentInput")
    POST_BTN = (By.ID, "postCommentBtn")
    LOGIN_NOTICE = (By.ID, "loginToCommentNotice")
    COMMENTS_LIST = (By.ID, "commentsList")
    COMMENT_ITEMS = (By.CLASS_NAME, "comment-item")

    def open(self):
        return self.navigate_to("/community")

    def post_comment(self, text: str):
        """Enter and submit new comment."""
        self.type_text(*self.COMMENT_INPUT, text)
        self.click(*self.POST_BTN)

    def is_guest_login_notice_visible(self) -> bool:
        """Check if login prompt is displayed to guests."""
        return self.is_visible(*self.LOGIN_NOTICE)

    def get_comment_texts(self) -> list[str]:
        """Return list of text content for all comments currently rendered."""
        from selenium.common.exceptions import StaleElementReferenceException
        try:
            elements = self.find_all(By.CLASS_NAME, "comment-text")
            return [el.text.strip() for el in elements if el]
        except StaleElementReferenceException:
            return []

    def delete_first_comment(self):
        """Click the first available delete comment button and confirm."""
        import time
        from selenium.webdriver.support.ui import WebDriverWait
        from selenium.webdriver.support import expected_conditions as EC
        from selenium.common.exceptions import StaleElementReferenceException, TimeoutException

        # Wait for comments to be present
        WebDriverWait(self.driver, 10).until(
            EC.presence_of_element_located((By.CLASS_NAME, "comment-item"))
        )
        for _ in range(3):
            try:
                btn = WebDriverWait(self.driver, 5).until(
                    EC.element_to_be_clickable((By.CLASS_NAME, "delete-comment-btn"))
                )
                self.driver.execute_script("arguments[0].scrollIntoView({block: 'center', inline: 'nearest'});", btn)
                btn.click()
                try:
                    alert = WebDriverWait(self.driver, 3).until(EC.alert_is_present())
                    alert.accept()
                except Exception:
                    pass
                return
            except (StaleElementReferenceException, TimeoutException):
                time.sleep(0.5)
