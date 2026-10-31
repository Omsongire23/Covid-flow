import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent.parent


class Config:
    SECRET_KEY = os.environ.get("SECRET_KEY")

    if not SECRET_KEY:
        raise RuntimeError(
            "SECRET_KEY environment variable is missing. "
            "Configure it before starting the application."
        )

    DATABASE = os.environ.get(
        "DATABASE",
        str(BASE_DIR / "instance" / "covid_flow.sqlite3")
    )

    COVID_API_BASE = os.environ.get(
        "COVID_API_BASE",
        "https://disease.sh/v3/covid-19"
    )

    CACHE_TTL_SECONDS = int(
        os.environ.get("CACHE_TTL_SECONDS", "3600")
    )

    SESSION_COOKIE_HTTPONLY = True
    SESSION_COOKIE_SAMESITE = "Lax"

    MAX_CONTENT_LENGTH = 16 * 1024 * 1024
    TESTING = False


class DevelopmentConfig(Config):
    DEBUG = True


class TestingConfig(Config):
    TESTING = True
    DATABASE = ":memory:"
    SECRET_KEY = "test-secret-key-for-testing-only"
    CACHE_TTL_SECONDS = 0


class ProductionConfig(Config):
    DEBUG = False
    SESSION_COOKIE_SECURE = True


config_by_name = {
    "development": DevelopmentConfig,
    "testing": TestingConfig,
    "production": ProductionConfig,
    "default": DevelopmentConfig,
}
