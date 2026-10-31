# AGENTS.md — COVID-FLOW
## Guidelines for Coding Agents and Automated Tools

> **Project:** Covid-flow COVID-19 Information Portal  
> **Course:** Software Testing and Quality Assurance (STQA)  
> **Tech Stack:** Python 3, Flask, SQLite, Vanilla CSS/JS, Chart.js, Selenium 4, pytest  

---

## 1. Core Directives

1. **Approved Stack Exclusively:**
   - Use Python 3, Flask, SQLite 3, Vanilla HTML5/CSS3/JavaScript, Chart.js, Selenium 4, pytest, and pytest-html.
   - **Never** introduce PHP, MySQL/MariaDB, Docker, React, Next.js, or Node.js.
2. **Never Fabricate Statistics or Test Results:**
   - Real API statistics are sourced from `disease.sh` or local archive fallback (`data/covid-fallback.json`).
   - All test reports must represent actual pytest / Selenium executions.
3. **Preserve Planning & Research Work:**
   - Do not delete or overwrite `.planning/` or `.agents/`.
4. **Maintain Synchronization:**
   - Keep `README.md`, `CLAUDE.md`, and `docs/` aligned with code changes.

---

## 2. Directory Layout & Key Seams

```text
STQA/
├── app/
│   ├── __init__.py           # create_app factory, helpers, error handlers
│   ├── config.py             # Config, DevelopmentConfig, TestingConfig
│   ├── database.py           # SQLite get_db(), close_db(), init_db()
│   ├── schema.sql            # SQLite schema (users, comments, registrations, etc.)
│   ├── auth.py               # Auth routes, session, login throttling, CSRF
│   ├── routes.py             # Public routes, stats, community, programme, lab
│   ├── validators.py         # 9 centralized regex & semantic validators
│   ├── covid_api.py          # disease.sh API client with cache & fallback
│   ├── content.py            # Verified WHO / MoHFW health content loader
│   ├── static/               # CSS tokens/layout/components, JS, Chart.js, images
│   └── templates/            # Jinja2 templates (base, index, stats, etc.)
├── data/
│   ├── content.json          # Curated public health information
│   └── covid-fallback.json   # Real historical COVID-19 fallback archive
├── config/
│   └── patterns.json         # 9 validation regex definitions and error messages
├── tests/
│   ├── conftest.py           # Pytest fixtures (app, client, runner, auth)
│   ├── regex-cases.json      # 124 test cases for validation test battery
│   ├── unit/                 # Unit tests (validators, covid_api, models)
│   ├── integration/          # Integration tests (auth, community, programme, routes)
│   └── selenium/             # Selenium 4 browser automation suite
│       ├── conftest.py       # Headless Chrome driver & live_server fixtures
│       ├── pages/            # Page Object Model classes
│       └── test_*.py         # Test cases TC-SEL-01 to TC-SEL-18
├── docs/                     # STQA documentation, viva Q&A, test plans, reports
├── reports/                  # Generated selenium-report.html and screenshots
├── scripts/
│   ├── init_db.py            # SQLite schema setup & seeding
│   └── run_selenium_tests.sh # Selenium test runner script
├── requirements.txt
├── requirements-dev.txt
├── run.py                    # Local Flask runner
└── pytest.ini
```

---

## 3. Testing Commands

```bash
# Run unit and integration tests
pytest tests/unit tests/integration -v

# Run Selenium 4 tests with HTML report
pytest tests/selenium -v --html=reports/selenium-report.html --self-contained-html

# Run all tests
pytest tests -v
```
