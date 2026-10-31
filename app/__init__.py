"""
Application Factory for Covid-flow.
Initializes Flask app, registers blueprints, configures SQLite database,
and exposes context helpers and error handlers.
"""

import os
from pathlib import Path
from flask import Flask, render_template
from dotenv import load_dotenv

# Load .env if present
load_dotenv()


def create_app(test_config=None) -> Flask:
    """Create and configure the Flask application instance."""
    app = Flask(__name__, instance_relative_config=True)

    # Determine configuration mode
    env_name = os.environ.get("FLASK_ENV", "development").lower()
    from app.config import config_by_name
    app.config.from_object(config_by_name.get(env_name, config_by_name["default"]))

    # Apply test config overrides if provided
    if test_config is not None:
        app.config.update(test_config)

    # Ensure instance folder exists
    try:
        Path(app.instance_path).mkdir(parents=True, exist_ok=True)
    except OSError:
        pass

    # Initialize SQLite database
    from app import database
    database.init_app(app)

    # Register Blueprints
    from app import auth, routes
    app.register_blueprint(auth.bp)
    app.register_blueprint(routes.bp)

    # Context processors
    @app.context_processor
    def inject_helpers():
        from app.auth import generate_csrf_token
        def format_number(val):
            if val is None or val == "":
                return "N/A"
            try:
                return f"{int(val):,}"
            except (ValueError, TypeError):
                return str(val)

        return {
            "csrf_token": generate_csrf_token,
            "format_number": format_number,
            "current_year": 2026,
            "app_name": "Covid-flow",
            "app_version": "2.0.0"
        }

    # Custom Error Handlers
    @app.errorhandler(404)
    def page_not_found(e):
        return render_template("404.html"), 404

    @app.errorhandler(500)
    def internal_server_error(e):
        return render_template("500.html"), 500

    @app.errorhandler(429)
    def too_many_requests(e):
        return render_template("500.html", error_title="Too Many Requests", error_message="Rate limit exceeded. Please wait a few minutes before trying again."), 429

    return app
