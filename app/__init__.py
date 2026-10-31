"""
Application Factory for Covid-flow.
Initializes Flask, registers blueprints, configures SQLite,
and handles application errors.
"""

import os
import logging
from pathlib import Path

from flask import Flask, render_template
from dotenv import load_dotenv

# Load local environment variables if available
BASE_DIR = Path(__file__).resolve().parent.parent
load_dotenv(BASE_DIR / ".env")


def create_app(test_config=None) -> Flask:
    """Create and configure the Flask application."""

    app = Flask(__name__, instance_relative_config=True)

    # Load environment-specific configuration
    env_name = os.environ.get(
        "FLASK_ENV", "development"
    ).lower()

    from app.config import config_by_name

    config_class = config_by_name.get(
        env_name, config_by_name["default"]
    )

    app.config.from_object(config_class)

    # Apply test configuration overrides
    if test_config is not None:
        app.config.update(test_config)

    # Validate production security configuration
    if env_name == "production" and not app.config.get("SECRET_KEY"):
        raise RuntimeError(
            "SECRET_KEY environment variable is required in production."
        )

    # Configure logging
    logging.basicConfig(level=logging.INFO)

    # Ensure local instance directory exists
    try:
        Path(app.instance_path).mkdir(
            parents=True, exist_ok=True
        )
    except OSError:
        app.logger.exception(
            "Unable to create Flask instance directory."
        )
        raise

    # Initialize SQLite database connection handling
    from app import database
    database.init_app(app)

    # Register application blueprints
    from app import auth, routes

    app.register_blueprint(auth.bp)
    app.register_blueprint(routes.bp)

    # Template context processors
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

    # Custom error handlers
    @app.errorhandler(404)
    def page_not_found(error):
        return render_template("404.html"), 404

    @app.errorhandler(500)
    def internal_server_error(error):
        app.logger.exception("Internal server error")
        return render_template("500.html"), 500

    @app.errorhandler(429)
    def too_many_requests(error):
        return render_template(
            "500.html",
            error_title="Too Many Requests",
            error_message=(
                "Rate limit exceeded. "
                "Please wait before trying again."
            )
        ), 429

    app.logger.info(
        "Covid-flow initialized successfully in %s mode.",
        env_name
    )

    return app
