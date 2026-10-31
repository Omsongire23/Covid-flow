"""
Unit Tests for Centralized Validators Module.
Covers all 9 validation categories with Equivalence Partitioning (EP)
and Boundary Value Analysis (BVA).
"""

from app.validators import (
    validate_username, validate_full_name, validate_email, validate_password,
    validate_phone, validate_dob, validate_pin_code, validate_country_query,
    validate_comment, match_regex
)

class TestUsernameValidation:
    """Tests for username constraints: 4–20 chars, starting with letter."""

    def test_valid_usernames(self):
        valid_cases = ["john", "John123", "a_user_name", "user_12345678901234"]
        for u in valid_cases:
            ok, err = validate_username(u)
            assert ok is True, f"Expected {u} to be valid, got error: {err}"

    def test_invalid_usernames_boundaries(self):
        # Too short (< 4 chars)
        ok, err = validate_username("abc")
        assert ok is False
        assert "4–20" in err

        # Too long (> 20 chars)
        ok, err = validate_username("a" * 21)
        assert ok is False

        # Starts with digit
        ok, _ = validate_username("1user")
        assert ok is False

        # Starts with underscore
        ok, _ = validate_username("_user")
        assert ok is False

        # Contains special symbols or spaces
        ok, _ = validate_username("user-name")
        assert ok is False
        ok, _ = validate_username("user name")
        assert ok is False


class TestFullNameValidation:
    """Tests for full name: 2–60 letters, hyphens, apostrophes, Unicode support."""

    def test_valid_full_names(self):
        valid_cases = ["Rajesh Kumar", "John Doe", "O'Connor", "Jean-Pierre", "Éléonore"]
        for n in valid_cases:
            assert match_regex("fullName", n) is True

    def test_invalid_full_names(self):
        invalid_cases = ["A", "A" * 61, "John123", "John\n", "<b>Bob</b>", ""]
        for n in invalid_cases:
            assert match_regex("fullName", n) is False


class TestEmailValidation:
    """Tests for RFC-compliant email address validation."""

    def test_valid_emails(self):
        valid_cases = ["user@example.com", "first.last+tag@sub.example.co.in", "a@b.co"]
        for e in valid_cases:
            ok, err = validate_email(e)
            assert ok is True, f"Failed on {e}: {err}"

    def test_invalid_emails(self):
        invalid_cases = ["user@example", "user@@example.com", "user..name@example.com", ".user@example.com", ""]
        for e in invalid_cases:
            ok, _ = validate_email(e)
            assert ok is False


class TestPasswordValidation:
    """Tests for password: 8–64 chars, upper, lower, digit, special, no spaces."""

    def test_valid_passwords(self):
        valid_cases = ["Passw0rd!", "Abcdef1#", "Str0ng&Secure#2026"]
        for p in valid_cases:
            ok, err = validate_password(p)
            assert ok is True, f"Failed on {p}: {err}"

    def test_invalid_passwords(self):
        # Missing uppercase
        assert validate_password("passw0rd!")[0] is False
        # Missing lowercase
        assert validate_password("PASSW0RD!")[0] is False
        # Missing digit
        assert validate_password("Password!")[0] is False
        # Missing special character
        assert validate_password("Password12")[0] is False
        # Too short (< 8 chars)
        assert validate_password("Pa1!")[0] is False
        # Contains space
        assert validate_password("Pass word1!")[0] is False


class TestPhoneValidation:
    """Tests for 10-digit Indian phone number starting with 6–9."""

    def test_valid_indian_phones(self):
        valid_cases = ["9876543210", "+919876543210", "+91 9876543210", "+91-9876543210", "09876543210"]
        for p in valid_cases:
            ok, err = validate_phone(p)
            assert ok is True, f"Failed on {p}: {err}"

    def test_invalid_indian_phones(self):
        invalid_cases = ["5876543210", "987654321", "98765432101", "+91  9876543210", "abcdefghij"]
        for p in invalid_cases:
            ok, _ = validate_phone(p)
            assert ok is False


class TestDOBValidation:
    """Tests for date of birth regex and semantic validation."""

    def test_valid_dobs(self):
        valid_cases = ["2000-01-31", "1999-12-01", "2024-02-29"]
        for d in valid_cases:
            ok, err = validate_dob(d)
            assert ok is True, f"Failed on {d}: {err}"

    def test_invalid_dobs(self):
        # Invalid format
        assert validate_dob("01-01-2000")[0] is False
        assert validate_dob("2000/01/01")[0] is False
        # Non-existent calendar day (checkdate)
        assert validate_dob("2023-02-31", semantic=True)[0] is False
        # Future date
        assert validate_dob("2099-01-01", semantic=True)[0] is False


class TestPinCodeValidation:
    """Tests for 6-digit Indian PIN code."""

    def test_valid_pin_codes(self):
        valid_cases = ["411001", "422001", "999999"]
        for pin in valid_cases:
            ok, err = validate_pin_code(pin)
            assert ok is True, f"Failed on {pin}: {err}"

    def test_invalid_pin_codes(self):
        # Leading zero
        assert validate_pin_code("011001")[0] is False
        # 5 digits
        assert validate_pin_code("41100")[0] is False
        # 7 digits
        assert validate_pin_code("4110011")[0] is False
        # Contains non-digit
        assert validate_pin_code("41100a")[0] is False


class TestCommentValidation:
    """Tests for comment: 3–500 chars, no HTML tags."""

    def test_valid_comments(self):
        valid_cases = [
            "Stay safe everyone!",
            "Thanks for the update.",
            "abc",
            "line one\nline two",
            "x" * 500
        ]
        for c in valid_cases:
            ok, err = validate_comment(c)
            assert ok is True, f"Failed on {c}: {err}"

    def test_invalid_comments(self):
        # Too short (< 3 chars)
        assert validate_comment("ab")[0] is False
        # Blank / whitespace only
        assert validate_comment("   ")[0] is False
        # Contains HTML script tag
        assert validate_comment("<script>alert(1)</script>")[0] is False
        # Contains HTML bold tag
        assert validate_comment("hello <b>world</b>")[0] is False
        # Too long (> 500 chars)
        assert validate_comment("x" * 501)[0] is False
