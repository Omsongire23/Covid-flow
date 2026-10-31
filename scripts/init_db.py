#!/usr/bin/env python3
"""
Database Initialization and Seed Script for Covid-flow.
Initializes the SQLite schema and seeds test users and initial comments.
"""

import sys
from pathlib import Path
sys.path.insert(0, str(Path(__file__).resolve().parent.parent))

from werkzeug.security import generate_password_hash
from app import create_app
from app.database import get_db, init_db

def seed_database():
    app = create_app({"TESTING": False})
    with app.app_context():
        print("Initializing database tables...")
        init_db()

        db = get_db()
        # Seed Admin user: admin / Admin@123#
        admin_user = db.execute("SELECT id FROM users WHERE username = 'admin'").fetchone()
        if not admin_user:
            admin_pwd = generate_password_hash("Admin@123#")
            db.execute(
                "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
                ("admin", "admin@covidflow.org", admin_pwd, "admin")
            )
            print(" -> Created admin user: username='admin', password='Admin@123#'")

        # Seed Standard test user: testuser / Test@123#
        test_user = db.execute("SELECT id FROM users WHERE username = 'testuser'").fetchone()
        if not test_user:
            test_pwd = generate_password_hash("Test@123#")
            db.execute(
                "INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, ?)",
                ("testuser", "testuser@covidflow.org", test_pwd, "user")
            )
            print(" -> Created test user: username='testuser', password='Test@123#'")

        user = db.execute("SELECT id FROM users WHERE username = 'testuser'").fetchone()
        user_id = user["id"]

        # Seed initial comments if empty
        comment_count = db.execute("SELECT COUNT(*) as cnt FROM comments").fetchone()["cnt"]
        if comment_count == 0:
            db.execute(
                "INSERT INTO comments (user_id, body) VALUES (?, ?)",
                (user_id, "Welcome to the Covid-flow community portal! Remember to practice hand hygiene and mask up in crowded places.")
            )
            db.execute(
                "INSERT INTO comments (user_id, body) VALUES (?, ?)",
                (user_id, "Verified booster dose availability near Pune and Mumbai civic centers is active on the CoWIN portal.")
            )
            print(" -> Seeded initial community comments.")

        db.commit()
        print("Database initialized and seeded successfully!")

if __name__ == "__main__":
    seed_database()
