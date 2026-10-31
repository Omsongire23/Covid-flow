"""
Selenium Browser Tests for Programme Registration Workflow:
TC-SEL-17.
"""

from tests.selenium.pages.programme_page import ProgrammePage


def test_tc_sel_17_programme_registration_flow(driver, live_server):
    """TC-SEL-17: Verify complete programme registration and reference confirmation."""
    prog_page = ProgrammePage(driver, live_server)
    prog_page.open()

    import time
    unique_suffix = int(time.time())

    registration_data = {
        "full_name": "Pooja Sharma",
        "email": f"pooja_{unique_suffix}@example.com",
        "phone": "9823055443",
        "dob": "1997-04-15",
        "gender": "female",
        "pin_code": "411004",
        "state": "Maharashtra",
        "service": "awareness_webinar",
        "consent": True
    }

    prog_page.fill_and_submit(registration_data)

    # Verify confirmation card and reference number
    assert prog_page.is_confirmed() is True
    ref_num = prog_page.get_reference_number()
    assert ref_num.startswith("COV-2026-")
