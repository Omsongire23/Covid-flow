"""
Pytest Fixtures for Covid-flow Testing Suite.
Provides isolated Flask application, test client, runner,
and temporary SQLite database for unit and integration testing.
"""

import os
import sys
from pathlib import Path
import tempfile
import pytest

# Ensure project root is in Python path
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from werkzeug.security import generate_password_hash
from app import create_app
from app.database import init_db, get_db


@pytest.fixture
def app():
    """Create and configure a clean Flask application instance for each test."""
    db_fd, db_path = tempfile.mkstemp(suffix=".sqlite3")
    
    app = create_app({
        "TESTING": True,
        "DATABASE": db_path,
        "SECRET_KEY": "test-secret-key-stqa-suite",
        "CACHE_TTL_SECONDS": 0
    })

    with app.app_context():
        init_db()
        # Seed test user and admin
        db = get_db()
        db.execute(
            "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
            ("testuser", "testuser@covidflow.org", generate_password_hash("Test@123#"), "user")
        )
        db.execute(
            "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
            ("admin", "admin@covidflow.org", generate_password_hash("Admin@123#"), "admin")
        )
        db.commit()

    yield app

    # Cleanup temporary database file
    os.close(db_fd)
    if os.path.exists(db_path):
        os.unlink(db_path)


@pytest.fixture
def client(app):
    """Flask test client."""
    return app.test_client()


@pytest.fixture
def runner(app):
    """Flask CLI runner."""
    return app.test_cli_runner()


class AuthActions:
    """Helper class for authenticating within tests."""
    def __init__(self, client):
        self._client = client

    def login(self, username="testuser", password="Test@123#"):
        return self._client.post(
            "/login",
            data={"username": username, "password": password},
            follow_redirects=True
        )

    def logout(self):
        return self._client.get("/logout", follow_redirects=True)


@pytest.fixture
def auth(client):
    """Authentication helper fixture."""
    return AuthActions(client)


@pytest.fixture
def auth_client(client, auth):
    """Test client pre-authenticated as standard user 'testuser'."""
    auth.login()
    return client
