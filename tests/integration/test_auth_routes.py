"""
Integration Tests for Authentication Routes:
Registration, Login, Throttling, Logout, and User Profile.
"""

def test_register_page_renders(client):
    response = client.get("/register")
    assert response.status_code == 200
    assert b"Create Covid-flow Account" in response.data

def test_register_successful_redirects_and_creates_user(client, app):
    response = client.post(
        "/register",
        data={
            "username": "NewUser2026",
            "email": "newuser@example.com",
            "password": "SecurePassword1!",
            "confirm_password": "SecurePassword1!"
        },
        follow_redirects=True
    )
    assert response.status_code == 200
    assert b"Registration successful!" in response.data

    with app.app_context():
        from app.database import get_db
        user = get_db().execute("SELECT * FROM users WHERE username = 'NewUser2026'").fetchone()
        assert user is not None
        assert user["email"] == "newuser@example.com"

def test_register_rejects_duplicate_username(client):
    # 'testuser' is seeded in conftest
    response = client.post(
        "/register",
        data={
            "username": "testuser",
            "email": "another@example.com",
            "password": "Password123#",
            "confirm_password": "Password123#"
        }
    )
    assert response.status_code == 200
    assert b"This username is already taken" in response.data

def test_register_rejects_password_mismatch(client):
    response = client.post(
        "/register",
        data={
            "username": "ValidUser1",
            "email": "valid@example.com",
            "password": "Password123#",
            "confirm_password": "Password999#"
        }
    )
    assert response.status_code == 200
    assert b"Passwords do not match" in response.data

def test_login_successful(client):
    response = client.post(
        "/login",
        data={"username": "testuser", "password": "Test@123#"},
        follow_redirects=True
    )
    assert response.status_code == 200
    assert b"Welcome back, testuser!" in response.data

def test_login_invalid_password(client):
    response = client.post(
        "/login",
        data={"username": "testuser", "password": "WrongPassword1!"}
    )
    assert response.status_code == 200
    assert b"Invalid username or password" in response.data

def test_logout_clears_session(auth_client):
    response = auth_client.get("/logout", follow_redirects=True)
    assert response.status_code == 200
    assert b"You have been logged out safely." in response.data

def test_profile_requires_login(client):
    response = client.get("/profile", follow_redirects=True)
    assert response.status_code == 200
    assert b"Please log in to access this page." in response.data
    assert b"Sign In to Covid-flow" in response.data

def test_profile_accessible_when_logged_in(auth_client):
    response = auth_client.get("/profile")
    assert response.status_code == 200
    assert b"Welcome, testuser" in response.data
    assert b"testuser@covidflow.org" in response.data
