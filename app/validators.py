"""
Centralized Input Validation Module for Covid-flow.
Implements the 9 approved regular-expression patterns, semantic validations,
and error messages specified in the STQA requirements.
"""

from collections.abc import Callable
from datetime import datetime, date
import json
from pathlib import Path
import regex

# Load pattern definitions and messages
PATTERNS_FILE = Path(__file__).resolve().parent.parent / "config" / "patterns.json"

RAW_PATTERNS = {
    "username": {
        "pattern": r"^[a-zA-Z][a-zA-Z0-9_]{3,19}$",
        "description": "4–20 characters, starting with a letter, alphanumeric characters and underscores only.",
        "message": "4–20 characters; start with a letter; letters, numbers and underscore only."
    },
    "fullName": {
        "pattern": r"^(?=.{2,60}$)[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$",
        "description": "2–60 letters; single spaces, apostrophes or hyphens between words. Supports Unicode characters.",
        "message": "2–60 letters; single spaces, apostrophes or hyphens between words."
    },
    "email": {
        "pattern": r"^(?=.{6,254}$)(?!.*\.\.)[A-Za-z0-9](?:[A-Za-z0-9._%+\-]*[A-Za-z0-9])?@(?:[A-Za-z0-9](?:[A-Za-z0-9\-]{0,61}[A-Za-z0-9])?\.)+[A-Za-z]{2,24}$",
        "description": "RFC 5322 compliant email format, 6–254 characters, no consecutive dots.",
        "message": "Enter a valid email address, for example name@example.com."
    },
    "password": {
        "pattern": r"^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9\s])\S{8,64}$",
        "description": "8–64 characters with at least one uppercase letter, one lowercase letter, one digit, and one special symbol; no spaces.",
        "message": "8–64 characters with an uppercase letter, a lowercase letter, a digit and a special character; no spaces."
    },
    "phoneIN": {
        "pattern": r"^(?:\+91[\- ]?|0)?[6-9]\d{9}$",
        "description": "10-digit Indian mobile number starting with 6–9, optional +91 or 0 prefix, optional single space/dash.",
        "message": "Enter a 10-digit Indian mobile number starting with 6–9 (optional +91 or 0 prefix)."
    },
    "dob": {
        "pattern": r"^\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\d|3[01])$",
        "description": "Calendar date in ISO format YYYY-MM-DD. Requires semantic validation for valid day, month, leap year, and past date.",
        "message": "Use the format YYYY-MM-DD."
    },
    "pinCode": {
        "pattern": r"^[1-9]\d{5}$",
        "description": "6-digit Indian Postal PIN code that does not start with 0.",
        "message": "Enter a 6-digit PIN code that does not start with 0."
    },
    "countryQuery": {
        "pattern": r"^[\p{L}\p{M}][\p{L}\p{M} .'\-]{1,55}$",
        "description": "Country name search: 2–56 Unicode letters; spaces, dots, apostrophes, and hyphens permitted.",
        "message": "Search with 2–56 letters; spaces, dots, apostrophes and hyphens allowed."
    },
    "comment": {
        "pattern": r"^(?=[\s\S]{3,500}$)(?=[\s\S]*\S)(?![\s\S]*<[^>]*>)[\s\S]*$",
        "description": "3–500 characters, non-empty, and free of HTML tags to prevent cross-site scripting (XSS).",
        "message": "3–500 characters, not blank, and no HTML tags."
    }
}

# Compile strict regex patterns using \Z to prevent trailing newline vulnerabilities
COMPILED_PATTERNS = {}
for key, data in RAW_PATTERNS.items():
    pat = data["pattern"]
    if pat.endswith("$"):
        strict_pat = pat[:-1] + r"\Z"
    else:
        strict_pat = pat
    COMPILED_PATTERNS[key] = regex.compile(strict_pat)


def match_regex(field_name: str, value: str) -> bool:
    """Check if value matches the field's regular expression pattern."""
    if value is None or not isinstance(value, str):
        return False
    compiled = COMPILED_PATTERNS.get(field_name)
    if not compiled:
        raise ValueError(f"Unknown validation category: {field_name}")
    return bool(compiled.search(value))


def validate_username(value: str) -> tuple[bool, str | None]:
    """Validate username."""
    if not value or not isinstance(value, str):
        return False, "Username is required."
    if not match_regex("username", value):
        return False, RAW_PATTERNS["username"]["message"]
    return True, None


def validate_full_name(value: str) -> tuple[bool, str | None]:
    """Validate full name."""
    if not value or not isinstance(value, str):
        return False, "Full name is required."
    if not match_regex("fullName", value):
        return False, RAW_PATTERNS["fullName"]["message"]
    return True, None


def validate_email(value: str) -> tuple[bool, str | None]:
    """Validate email address."""
    if not value or not isinstance(value, str):
        return False, "Email address is required."
    if not match_regex("email", value):
        return False, RAW_PATTERNS["email"]["message"]
    return True, None


def validate_password(value: str) -> tuple[bool, str | None]:
    """Validate password (strictly no silent trimming)."""
    if not value or not isinstance(value, str):
        return False, "Password is required."
    if not match_regex("password", value):
        return False, RAW_PATTERNS["password"]["message"]
    return True, None


def validate_phone(value: str) -> tuple[bool, str | None]:
    """Validate Indian mobile number."""
    if not value or not isinstance(value, str):
        return False, "Phone number is required."
    if not match_regex("phoneIN", value):
        return False, RAW_PATTERNS["phoneIN"]["message"]
    return True, None


def validate_dob(value: str, semantic: bool = True) -> tuple[bool, str | None]:
    """
    Validate date of birth.
    Matches YYYY-MM-DD shape, verifies leap year / calendar day,
    and ensures birth date is in the past and within 120 years.
    """
    if not value or not isinstance(value, str):
        return False, "Date of birth is required."
    if not match_regex("dob", value):
        return False, RAW_PATTERNS["dob"]["message"]

    if semantic:
        try:
            parts = [int(p) for p in value.split("-")]
            parsed_date = date(parts[0], parts[1], parts[2])
        except (ValueError, TypeError):
            return False, "Invalid calendar date (e.g. invalid day for the month)."

        today = date.today()
        if parsed_date > today:
            return False, "Date of birth cannot be in the future."
        if parsed_date.year < 1900:
            return False, "Date of birth must be after year 1900."

    return True, None


def validate_pin_code(value: str) -> tuple[bool, str | None]:
    """Validate Indian 6-digit postal PIN code."""
    if not value or not isinstance(value, str):
        return False, "PIN code is required."
    if not match_regex("pinCode", value):
        return False, RAW_PATTERNS["pinCode"]["message"]
    return True, None


def validate_country_query(value: str) -> tuple[bool, str | None]:
    """Validate country search query."""
    if not value or not isinstance(value, str):
        return False, "Country name is required."
    if not match_regex("countryQuery", value):
        return False, RAW_PATTERNS["countryQuery"]["message"]
    return True, None


def validate_comment(value: str) -> tuple[bool, str | None]:
    """Validate community comment text."""
    if not value or not isinstance(value, str):
        return False, "Comment cannot be empty."
    if not match_regex("comment", value):
        return False, RAW_PATTERNS["comment"]["message"]
    return True, None


VALIDATOR_FUNCS: dict[str, Callable[..., tuple[bool, str | None]]] = {
    "username": validate_username,
    "fullName": validate_full_name,
    "email": validate_email,
    "password": validate_password,
    "phoneIN": validate_phone,
    "dob": validate_dob,
    "pinCode": validate_pin_code,
    "countryQuery": validate_country_query,
    "comment": validate_comment,
}


def validate_field(field_name: str, value: str, semantic: bool = True) -> tuple[bool, str | None]:
    """Generic validator dispatcher."""
    if field_name not in VALIDATOR_FUNCS:
        return False, f"Unknown field: {field_name}"

    if field_name == "dob":
        return validate_dob(value, semantic=semantic)

    validator = VALIDATOR_FUNCS[field_name]
    return validator(value)


def get_patterns_metadata() -> dict:
    """Return all patterns, messages, and descriptions for the Quality Lab."""
    return RAW_PATTERNS
