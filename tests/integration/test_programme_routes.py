"""
Integration Tests for Programme Registration Workflow.
Validates multi-field constraints, duplicate prevention, and reference generation.
"""

def test_programme_page_renders_form(client):
    response = client.get("/programme")
    assert response.status_code == 200
    assert b"Health Awareness &amp; Programme Registration" in response.data
    assert b"fullNameInput" in response.data

def test_programme_registration_success(client, app):
    response = client.post(
        "/programme",
        data={
            "full_name": "Rohan Deshmukh",
            "email": "rohan.deshmukh@example.com",
            "phone": "9823012345",
            "dob": "1995-08-14",
            "gender": "male",
            "pin_code": "411038",
            "state": "Maharashtra",
            "service": "vaccination_info",
            "consent": "on"
        },
        follow_redirects=True
    )
    assert response.status_code == 200
    assert b"Registration Confirmed!" in response.data
    assert b"COV-2026-" in response.data

    with app.app_context():
        from app.database import get_db
        reg = get_db().execute("SELECT * FROM registrations WHERE email = 'rohan.deshmukh@example.com'").fetchone()
        assert reg is not None
        assert reg["ref_number"].startswith("COV-2026-")

def test_programme_registration_rejects_duplicate_service(client):
    data = {
        "full_name": "Sunita Patil",
        "email": "sunita@example.com",
        "phone": "9823098765",
        "dob": "1992-03-20",
        "gender": "female",
        "pin_code": "411001",
        "state": "Maharashtra",
        "service": "awareness_webinar",
        "consent": "on"
    }
    # First registration
    client.post("/programme", data=data)

    # Second registration with same email & service
    response = client.post("/programme", data=data)
    assert response.status_code == 200
    assert b"already exists" in response.data

def test_programme_registration_missing_consent(client):
    response = client.post(
        "/programme",
        data={
            "full_name": "Aarav Gupta",
            "email": "aarav@example.com",
            "phone": "9811122233",
            "dob": "1990-01-01",
            "gender": "male",
            "pin_code": "110001",
            "state": "Delhi",
            "service": "volunteer_support"
            # Missing consent
        }
    )
    assert response.status_code == 200
    assert b"provide consent" in response.data
