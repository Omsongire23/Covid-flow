"""
SQLite Database Management for Covid-flow.

Supports local development and Render persistent disk storage.
Uses request-scoped connections and safe database initialization.
"""

import sqlite3
from pathlib import Path

import click
from flask import current_app, g


def get_db():
    """Get or establish a request-scoped SQLite connection."""

    if "db" not in g:
        db_path = current_app.config["DATABASE"]

        if db_path != ":memory:":
            db_path = Path(db_path).expanduser()
            db_path.parent.mkdir(parents=True, exist_ok=True)

        g.db = sqlite3.connect(
            str(db_path),
            detect_types=(
                sqlite3.PARSE_DECLTYPES |
                sqlite3.PARSE_COLNAMES
            ),
            timeout=30
        )

        g.db.row_factory = sqlite3.Row

        # Enforce database integrity
        g.db.execute("PRAGMA foreign_keys = ON;")

        # Wait for temporary database locks
        g.db.execute("PRAGMA busy_timeout = 30000;")

        # Improve reliability for concurrent readers and writers
        if db_path != ":memory:":
            g.db.execute("PRAGMA journal_mode = WAL;")

    return g.db


def close_db(e=None):
    """Close request-scoped database connection."""

    db = g.pop("db", None)

    if db is not None:
        db.close()


def init_db(schema_path=None):
    """
    Initialize database schema.

    Run only during initial setup or controlled migrations.
    Do not use this to reset an existing production database.
    """

    db = get_db()

    if schema_path is None:
        schema_path = (
            Path(__file__).resolve().parent / "schema.sql"
        )

    schema_path = Path(schema_path)

    if not schema_path.exists():
        raise FileNotFoundError(
            f"Database schema not found: {schema_path}"
        )

    with schema_path.open("r", encoding="utf-8") as file:
        db.executescript(file.read())

    db.commit()


@click.command("init-db")
def init_db_command():
    """Initialize database tables using schema.sql."""

    init_db()

    click.echo(
        "Database schema initialization completed successfully."
    )


def init_app(app):
    """Register database lifecycle functions with Flask."""

    app.teardown_appcontext(close_db)
    app.cli.add_command(init_db_command)
