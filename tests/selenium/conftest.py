"""
Selenium 4 Pytest Configuration and Fixtures for Covid-flow.
Manages headless Chrome WebDriver initialization, background live test server,
automatic failure screenshots, and pytest-html reporting integration.
"""

from datetime import datetime
import os
from pathlib import Path
import socket
import sys
import threading
import time
import pytest
from werkzeug.serving import make_server
from selenium import webdriver
from selenium.webdriver.chrome.options import Options

# Ensure project root is in sys.path
BASE_DIR = Path(__file__).resolve().parent.parent.parent
sys.path.insert(0, str(BASE_DIR))

from app import create_app
from app.database import init_db, get_db
from werkzeug.security import generate_password_hash


def get_free_port() -> int:
    """Find and return an unused local TCP port."""
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        s.bind(("", 0))
        return s.getsockname()[1]


class ServerThread(threading.Thread):
    """Thread running the Werkzeug WSGI server for Selenium testing."""
    def __init__(self, app, host, port):
        super().__init__()
        self.server = make_server(host, port, app)
        self.ctx = app.app_context()
        self.ctx.push()

    def run(self):
        self.server.serve_forever()

    def shutdown(self):
        self.server.shutdown()


@pytest.fixture(scope="session")
def live_server():
    """Start live background Flask web server on a free port with clean test database."""
    port = get_free_port()
    host = "127.0.0.1"
    test_db = str(BASE_DIR / "instance" / "selenium_test.sqlite3")

    app = create_app({
        "TESTING": True,
        "DATABASE": test_db,
        "SECRET_KEY": "selenium-test-secret-key-stqa-2026",
        "CACHE_TTL_SECONDS": 3600
    })

    with app.app_context():
        init_db()
        db = get_db()
        # Seed standard test user: testuser / Test@123#
        test_pwd = generate_password_hash("Test@123#")
        db.execute(
            "INSERT OR REPLACE INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
            ("testuser", "testuser@covidflow.org", test_pwd, "user")
        )
        # Seed admin user: admin / Admin@123#
        admin_pwd = generate_password_hash("Admin@123#")
        db.execute(
            "INSERT OR REPLACE INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
            ("admin", "admin@covidflow.org", admin_pwd, "admin")
        )
        # Seed initial comments
        db.execute(
            "INSERT INTO comments (user_id, body) VALUES (?, ?)",
            (1, "Verified booster doses are now available across regional clinics.")
        )
        db.commit()

    server = ServerThread(app, host, port)
    server.daemon = True
    server.start()

    # Wait for server to begin listening
    base_url = f"http://{host}:{port}"
    for _ in range(30):
        try:
            with socket.create_connection((host, port), timeout=0.5):
                break
        except OSError:
            time.sleep(0.1)

    yield base_url

    server.shutdown()
    if os.path.exists(test_db):
        try:
            os.unlink(test_db)
        except OSError:
            pass


@pytest.fixture
def driver():
    """Initialize and configure Chrome WebDriver in headless mode for Linux test runs."""
    chrome_options = Options()
    chrome_options.add_argument("--headless=new")
    chrome_options.add_argument("--no-sandbox")
    chrome_options.add_argument("--disable-dev-shm-usage")
    chrome_options.add_argument("--disable-gpu")
    chrome_options.add_argument("--window-size=1920,1080")
    chrome_options.add_argument("--disable-notifications")

    driver = webdriver.Chrome(options=chrome_options)
    driver.implicitly_wait(1)  # Rely primarily on explicit waits in Page Objects

    yield driver

    driver.quit()


@pytest.hookimpl(tryfirst=True, hookwrapper=True)
def pytest_runtest_makereport(item, call):
    """Capture failure screenshots and attach to pytest-html report."""
    outcome = yield
    report = outcome.get_result()

    if report.when == "call" and report.failed:
        driver = item.funcargs.get("driver")
        if driver:
            screenshots_dir = BASE_DIR / "reports" / "screenshots"
            screenshots_dir.mkdir(parents=True, exist_ok=True)
            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            screenshot_name = f"{item.name}_{timestamp}.png"
            screenshot_path = screenshots_dir / screenshot_name
            try:
                driver.save_screenshot(str(screenshot_path))
            except Exception as e:
                print(f"Failed to capture screenshot: {e}")
