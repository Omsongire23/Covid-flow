"""
Unit Tests for SQLite Database Models and Connection Lifecycle.
"""

from app.database import get_db

def test_database_tables_exist(app):
    with app.app_context():
        db = get_db()
        tables = db.execute(
            "SELECT name FROM sqlite_master WHERE type='table' ORDER BY name"
        ).fetchall()
        table_names = [t["name"] for t in tables]

        assert "users" in table_names
        assert "comments" in table_names
        assert "registrations" in table_names
        assert "login_attempts" in table_names
        assert "covid_cache" in table_names

def test_foreign_keys_pragmas_enabled(app):
    with app.app_context():
        db = get_db()
        fk = db.execute("PRAGMA foreign_keys").fetchone()[0]
        assert fk == 1
