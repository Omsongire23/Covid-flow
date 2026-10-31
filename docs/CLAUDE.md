# CLAUDE.md — COVID-FLOW
## COVID-19 Information Portal | Software Testing and Quality Assurance Mini Project

> **Purpose:** Master instructions for AI coding agents working on Covid-flow.
>
> **Course:** Software Testing and Quality Assurance (STQA)
>
> **Project type:** Academic mini-project
>
> **Primary implementation:** Python, Flask, SQLite, HTML, CSS, JavaScript
>
> **Browser automation:** Selenium 4 with Python and pytest
>
> **Important:** Read this entire file and the existing project documentation before modifying code.

---

# 1. PROJECT OVERVIEW

## 1.1 Problem Statement

Develop a dynamic website providing COVID-19 information using a clean web interface, a Python backend, and a database.

The application must demonstrate Software Testing and Quality Assurance concepts, particularly:

- Functional testing.
- Input validation.
- Regular-expression testing.
- Unit testing.
- Integration testing.
- Selenium 4 browser automation.
- Test case design.
- Defect identification and reporting.
- Requirements traceability.

The project has two equally important deliverables:

1. **Application:** A functional and professional COVID-19 information portal.
2. **Quality evidence:** Automated tests, Selenium demonstrations, test cases, test reports, and documentation.

The project must remain understandable and easy to demonstrate during an external practical examination.

## 1.2 Project Objectives

- Display COVID-19 information using a dynamic dashboard.
- Provide country-wise and historical statistics.
- Present verified public-health information.
- Implement user registration and authentication.
- Allow authenticated users to participate in a community.
- Provide a programme registration form.
- Demonstrate centralized input validation.
- Build a Quality Lab for validation demonstrations.
- Automate browser testing using Selenium 4.
- Maintain proper software testing documentation.

---

# 2. EXISTING PROJECT DOCUMENTATION

Planning and research files have already been created.

Before implementation, inspect the actual workspace and read the relevant files.

Expected existing structure:

```text
STQA/
│
├── .agents/
│
├── .planning/
│   ├── codebase/
│   │   ├── CONVENTIONS.md
│   │   ├── INTEGRATIONS.md
│   │   ├── STACK.md
│   │   └── TESTING.md
│   │
│   ├── research/
│   │   ├── ARCHITECTURE.md
│   │   └── FEATURES.md
│   │
│   └── other planning files
│
├── CLAUDE.md
└── other project files
```

The actual workspace may contain additional files.

### Mandatory review

Read:

- `CLAUDE.md`
- `.planning/codebase/CONVENTIONS.md`
- `.planning/codebase/INTEGRATIONS.md`
- `.planning/codebase/STACK.md`
- `.planning/codebase/TESTING.md`
- `.planning/research/ARCHITECTURE.md`
- `.planning/research/FEATURES.md`
- Other relevant `.planning/` files.
- Existing `.agents/` instructions and skills.

### Documentation rules

- Existing planning documents are the reference for approved project decisions.
- Do not regenerate completed research.
- Do not recreate existing PRDs, SRS documents, architecture documents, or plans unnecessarily.
- Do not delete `.planning/` or `.agents/`.
- Update existing documents when implementation decisions change.
- Keep documentation consistent with the actual application.
- Record significant architectural changes.

---

# 3. TECHNOLOGY STACK

The final approved implementation stack is:

| Component | Technology |
|---|---|
| Programming language | Python 3 |
| Backend | Flask |
| Database | SQLite |
| Frontend | HTML5 |
| Styling | CSS3 |
| Client-side scripting | Vanilla JavaScript |
| Template engine | Jinja2 |
| Charts | Chart.js |
| Browser automation | Selenium 4 |
| Test framework | pytest |
| HTML test reports | pytest-html |
| Version control | Git |

## 3.1 Technology Restrictions

Do not introduce:

- PHP.
- MySQL or MariaDB.
- Docker or Docker Compose.
- React.
- Next.js.
- Node.js.
- Laravel.
- Unnecessary frontend frameworks.
- Unnecessary backend frameworks.
- Unnecessary infrastructure.

Use a local Python environment and SQLite database.

## 3.2 Python Dependencies

Use only dependencies that serve an actual project requirement.

Suggested dependencies:

```text
Flask
python-dotenv
pytest
pytest-html
selenium
webdriver-manager
Flask-WTF
```

Use Flask's built-in facilities and Python standard libraries wherever practical.

Keep production and development dependencies separate where useful.

Maintain accurate dependency versions in the requirements files.

---

# 4. APPLICATION REQUIREMENTS

The application must preserve the original project scope.

## FR-01: Home Dashboard

Display:

- Global confirmed cases.
- Active cases.
- Recovered cases, when reported.
- Deaths.
- Daily changes, when available.
- Data source.
- Last-updated timestamp.

Use real API data or clearly identified cached data.

Never invent statistics.

## FR-02: Statistics

Provide:

- Historical trend charts.
- Metric selection.
- Date-range selection.
- Country-wise statistics.
- Search.
- Sorting.
- Filtering.
- Pagination.

Chart data must come from actual available data.

## FR-03: Health Information

Provide the following pages:

- Prevention.
- Symptoms.
- Vaccination.
- Myths versus Facts.
- Helplines.

Content must use credible public-health sources.

Display source references and review dates.

Do not invent medical claims or helpline numbers.

Include an appropriate medical disclaimer.

## FR-04: User Registration

Provide:

- Registration form.
- Username.
- Email.
- Password.
- Client-side validation.
- Server-side validation.
- Duplicate-account prevention.
- Clear error messages.

## FR-05: Authentication

Implement:

- Login.
- Logout.
- Secure password hashing.
- Session management.
- Login throttling.
- Authentication error handling.

## FR-06: User Profile

Display:

- User account information.
- Registration history.
- User's own comments.

Users must only access their own private information.

## FR-07: Community

Implement:

- Public comment viewing.
- Authenticated comment creation.
- Comment pagination.
- 500-character limit.
- Deletion of own comments.
- Secure comment rendering.

## FR-08: Programme Registration

Implement a separate programme registration form.

Suggested services:

- Vaccination information.
- Awareness webinar.
- Volunteering.
- Helpline callback.

Provide:

- Full name.
- Email.
- Phone.
- Date of birth.
- Gender.
- PIN code.
- State.
- Service.
- Consent.

Validate all submitted fields.

Prevent duplicate registration for the same user and service.

Generate a unique registration reference.

The confirmation must not claim that an actual appointment has been booked.

## FR-09: Regular Expression Validation

Implement a centralized Python validation module.

Preserve the nine original validation categories:

1. Username.
2. Full name.
3. Email.
4. Password.
5. Indian phone number.
6. Date of birth.
7. PIN code.
8. Country search.
9. Comment.

Requirements:

- Client-side validation.
- Server-side validation.
- Consistent validation messages.
- Valid and invalid test inputs.
- Boundary-value testing.
- Unicode support where appropriate.
- Semantic validation for dates.
- No silent password trimming.

The server must always validate incoming data independently.

## FR-10: Quality Lab

Create an interactive validation demonstration page.

Include:

- Field selector.
- Regex pattern display.
- Input tester.
- Valid and invalid examples.
- Pass/fail indication.
- Test-suite execution.
- Results table.
- CSV/JSON export where practical.
- Short explanations of validation concepts.

The Quality Lab must execute real validation logic, not display hardcoded test results.

## FR-11: User Interface and Accessibility

Provide:

- Responsive layout.
- Consistent navigation.
- Professional typography.
- Accessible forms.
- Keyboard navigation.
- Visible focus states.
- Appropriate color contrast.
- Light/dark theme if implemented.

## FR-12: COVID Data Failure Handling

Handle:

- API failure.
- Invalid API responses.
- Missing values.
- Stale cached data.
- Network timeouts.

Show a clear error, stale-data warning, or empty state.

Never fabricate statistics.

## FR-13: Admin Moderation

This is a stretch requirement.

If implemented, provide:

- Admin authentication.
- Comment listing.
- Hide/unhide.
- Delete.
- Authorization checks.

Do not compromise the core project to implement this optional feature.

---

# 5. APPLICATION ARCHITECTURE

Use a simple Flask application structure.

Suggested architecture:

```text
STQA/
│
├── .agents/
├── .planning/
│
├── app/
│   ├── __init__.py
│   ├── routes.py
│   ├── database.py
│   ├── models.py
│   ├── validators.py
│   ├── auth.py
│   ├── covid_api.py
│   │
│   ├── templates/
│   │   ├── base.html
│   │   ├── index.html
│   │   ├── statistics.html
│   │   ├── prevention.html
│   │   ├── symptoms.html
│   │   ├── vaccination.html
│   │   ├── myths.html
│   │   ├── helplines.html
│   │   ├── community.html
│   │   ├── login.html
│   │   ├── register.html
│   │   └── profile.html
│   │
│   └── static/
│       ├── css/
│       ├── js/
│       └── images/
│
├── tests/
│   ├── unit/
│   ├── integration/
│   └── selenium/
│       ├── pages/
│       ├── conftest.py
│       ├── test_home.py
│       ├── test_authentication.py
│       ├── test_statistics.py
│       └── test_community.py
│
├── docs/
├── scripts/
├── reports/
│
├── requirements.txt
├── requirements-dev.txt
├── .env.example
├── .gitignore
├── run.py
├── AGENTS.md
├── CLAUDE.md
└── README.md
```

This is a suggested structure.

Create files only when they have a genuine implementation purpose.

Prefer small, understandable Python modules and functions.

Avoid unnecessary abstraction.

---

# 6. DATABASE REQUIREMENTS

Use SQLite.

Create a database initialization script.

Suggested tables:

### Users

- id.
- username.
- email.
- password_hash.
- role, if admin functionality is implemented.
- created_at.

### Comments

- id.
- user_id.
- body.
- status, if moderation is implemented.
- created_at.

### Registrations

- id.
- user_id.
- full_name.
- email.
- phone.
- dob.
- gender.
- pin_code.
- state.
- service.
- consent.
- status.
- created_at.

### Login Attempts

Create this table if required for login throttling.

### COVID Cache

Create this table if required for storing API responses.

Database requirements:

- Use parameterized SQL queries.
- Add appropriate primary keys.
- Add foreign keys.
- Enforce uniqueness where required.
- Use transactions for multi-step database operations.
- Handle database errors.
- Do not store plaintext passwords.
- Do not store unnecessary personal information.

---

# 7. SECURITY REQUIREMENTS

Implement appropriate security protections.

1. Password hashing using a secure password-hashing mechanism.
2. Parameterized SQL queries.
3. Server-side input validation.
4. Safe HTML rendering.
5. CSRF protection for state-changing requests.
6. Secure session configuration.
7. Session regeneration after authentication.
8. Login throttling.
9. Authorization checks.
10. Ownership checks for user comments and registrations.
11. Environment-based secret management.
12. Friendly error pages.
13. No passwords or secrets in logs.
14. No unnecessary collection of sensitive information.

Never trust values received from the browser.

Never rely exclusively on client-side validation.

---

# 8. UI/UX DESIGN REQUIREMENTS

Use the existing UI/UX research and UI UX Pro Max skill where available.

The application must have a professional public-health design.

## Visual direction

- Clinical blue and teal.
- Clean white and neutral surfaces.
- Clear typography.
- Consistent spacing.
- Professional cards.
- Subtle shadows.
- Clear information hierarchy.
- Consistent navigation.
- Responsive components.

## Dashboard

Include:

- Hero section.
- Statistics cards.
- COVID trend visualization.
- Country highlights.
- Health information shortcuts.
- Data source and update information.

## Forms

Provide:

- Clear labels.
- Helpful placeholders.
- Inline validation.
- Field-level error messages.
- Accessible focus states.
- Success and error feedback.

## Responsive behaviour

The application must work on:

- Desktop.
- Tablet.
- Mobile.

Avoid horizontal page scrolling.

Do not create decorative buttons that have no functionality.

Every visible control must perform its intended action.

---

# 9. SELENIUM 4 AUTOMATION — MANDATORY

Selenium is a major academic deliverable.

The external examiner may ask the student to:

- Open Selenium source files.
- Explain the code.
- Run the tests.
- Show browser automation.
- Demonstrate test results.
- Explain PASS and FAIL outcomes.

Therefore, Selenium implementation must be simple, readable, and properly documented.

## 9.1 Technology

Use:

- Python.
- Selenium 4.
- pytest.
- pytest-html.
- Explicit waits.
- Stable element locators.

Use Page Object Model where it improves readability.

Avoid overengineering.

## 9.2 Suggested Selenium structure

```text
tests/
└── selenium/
    ├── pages/
    │   ├── base_page.py
    │   ├── home_page.py
    │   ├── login_page.py
    │   ├── register_page.py
    │   ├── statistics_page.py
    │   └── community_page.py
    │
    ├── conftest.py
    ├── test_home.py
    ├── test_authentication.py
    ├── test_statistics.py
    └── test_community.py
```

Do not create unused page objects.

## 9.3 Required browser tests

Implement tests corresponding to actual application features.

Minimum scenarios:

| Test ID | Scenario |
|---|---|
| TC-SEL-01 | Application opens successfully |
| TC-SEL-02 | Home page heading is displayed |
| TC-SEL-03 | Dashboard statistics are displayed |
| TC-SEL-04 | Navigation works |
| TC-SEL-05 | Statistics page opens |
| TC-SEL-06 | Country search works |
| TC-SEL-07 | Empty registration form is rejected |
| TC-SEL-08 | Invalid email is rejected |
| TC-SEL-09 | Valid user registration works |
| TC-SEL-10 | Invalid login is rejected |
| TC-SEL-11 | Valid login works |
| TC-SEL-12 | Logout works |
| TC-SEL-13 | Guest community restrictions work |
| TC-SEL-14 | Authenticated comment creation works |
| TC-SEL-15 | Comment length limit is enforced |
| TC-SEL-16 | Own-comment deletion works |
| TC-SEL-17 | Theme switching works, if implemented |

Add further scenarios when useful.

## 9.4 Selenium implementation rules

- Use explicit waits instead of arbitrary sleeps.
- Use meaningful element locators.
- Keep test methods short.
- Use pytest fixtures for browser setup and cleanup.
- Use test data that does not interfere with real user data.
- Capture screenshots on failures where practical.
- Use a clean test database.
- Ensure tests are repeatable.
- Do not claim tests passed unless they actually executed.

## 9.5 Selenium test report

Generate an HTML report using pytest-html.

Example:

```bash
pytest tests/selenium -v \
  --html=reports/selenium-report.html \
  --self-contained-html
```

Store screenshots and reports in the appropriate reports directory.

Do not commit generated temporary browser profiles or unnecessary test artifacts.

---

# 10. SELENIUM CODE EXPLANATION AND VIVA

Create:

`docs/SELENIUM_CODE_EXPLANATION.md`

This document must explain the actual implementation.

Cover:

1. What Selenium is.
2. What WebDriver does.
3. Selenium 4 basics.
4. Browser initialization.
5. Browser options.
6. Locators.
7. `find_element()`.
8. `click()`.
9. `send_keys()`.
10. Explicit waits.
11. Expected Conditions.
12. Assertions.
13. pytest fixtures.
14. Page Object Model.
15. Screenshot capture.
16. HTML reports.
17. Running the tests.
18. Understanding test output.

For each implemented test file, explain:

- Purpose.
- Preconditions.
- Steps.
- Test data.
- Locator strategy.
- Expected result.
- Assertions.
- What PASS means.
- What FAIL means.

Keep the explanations suitable for a student to present to an external examiner.

Create:

`docs/SELENIUM_VIVA_QUESTIONS.md`

Include at least 30 relevant questions and simple answers based on the actual Selenium implementation.

Do not describe features or code that do not exist.

---

# 11. TESTING STRATEGY

Maintain separate testing levels.

## Unit Testing

Test:

- Validation functions.
- Date validation.
- Password validation.
- Comment validation.
- Helper functions.
- Data transformation.

## Integration Testing

Test:

- Flask routes.
- SQLite database operations.
- Authentication workflows.
- Registration workflows.
- Comment workflows.
- COVID API error handling.

## Selenium Testing

Test:

- Actual browser rendering.
- Navigation.
- Forms.
- Authentication.
- Statistics interactions.
- Community interactions.

## Testing documentation

Maintain:

- `docs/TEST_PLAN.md`
- `docs/TEST_CASES.md`
- `docs/TRACEABILITY.md`
- `docs/DEFECT_LOG.md`
- `docs/TEST_REPORT.md`
- `docs/SELENIUM_CODE_EXPLANATION.md`
- `docs/SELENIUM_VIVA_QUESTIONS.md`

Reuse existing documents when present.

Do not generate duplicate documents.

Only report real test results.

Use these status categories:

- PASS.
- FAIL.
- FAILED — FIXED.
- NOT RUN — reason.

After fixing a defect, rerun the relevant test.

---

# 12. COVID-19 DATA SOURCE

Use a public COVID-19 API only after verifying that it is available and its response format is valid.

The original project specification references:

`https://disease.sh/v3/covid-19/`

Verify the required endpoints before depending on them.

Requirements:

- Fetch data server-side where practical.
- Validate response structures.
- Use timeouts.
- Handle network errors.
- Cache responses if appropriate.
- Display source attribution.
- Display update timestamps.
- Show stale-data information when using cached responses.
- Handle missing values honestly.

Never invent statistics.

If the API is unavailable, implement and demonstrate a clear empty/error state.

---

# 13. ENVIRONMENT AND EXECUTION

The application should run locally without Docker.

Use a Python virtual environment.

Suggested setup:

```bash
python3 -m venv .venv
source .venv/bin/activate
```

Install dependencies:

```bash
pip install -r requirements.txt
pip install -r requirements-dev.txt
```

Initialize the database:

```bash
python scripts/init_db.py
```

Run the application:

```bash
python run.py
```

Run unit and integration tests:

```bash
pytest tests/unit tests/integration -v
```

Run Selenium tests:

```bash
pytest tests/selenium -v
```

Generate Selenium HTML report:

```bash
pytest tests/selenium -v \
  --html=reports/selenium-report.html \
  --self-contained-html
```

Update commands if the final project structure differs.

Document actual environment requirements.

---

# 14. CODE QUALITY RULES

- Use clear Python naming conventions.
- Use small, single-purpose functions.
- Add comments for non-obvious logic.
- Avoid duplicate code.
- Handle exceptions appropriately.
- Keep routes understandable.
- Keep validation centralized.
- Use parameterized database operations.
- Do not expose stack traces to normal users.
- Do not hardcode secrets.
- Do not create unnecessary dependencies.
- Remove unused implementation code.
- Do not leave TODOs or fake functionality.
- Do not use placeholder test results.
- Keep code easy to explain during practical examinations.

Prefer understandable code over clever or unnecessarily complex solutions.

---

# 15. SKILLS AND AGENT INSTRUCTIONS

The project may use these installed skills:

### UI UX Pro Max

For design systems, UI/UX implementation, responsive layouts, and accessibility.

### Ponytail

For code review, quality improvements, simplification, and maintainability.

### GSD

For existing planning phases, task execution, progress tracking, and verification.

### Codex

Primary coding agent responsible for implementation and testing.

Before using a skill:

1. Check whether it is installed.
2. Read its instructions.
3. Follow its documented usage.
4. Use it only when relevant.
5. Do not claim that an unavailable skill was executed.

Maintain consistent instructions across `CLAUDE.md`, `AGENTS.md`, and relevant `.agents/` files.

Do not create unnecessary agents or duplicate skill files.

---

# 16. FILE MANAGEMENT AND DESTRUCTIVE ACTIONS

The existing project contains valuable planning and research work.

Never automatically delete:

- `.planning/`
- `.agents/`
- Existing research documents.
- Existing project specifications.
- Existing test documentation.

Before replacing or deleting old implementation files:

1. Inspect their purpose.
2. Identify dependencies.
3. Verify that a backup exists.
4. Confirm that the files are obsolete.
5. Obtain approval for irreversible deletion.

Do not:

- Delete all existing work blindly.
- Reset Git history.
- Force-push.
- Delete branches.
- Destroy databases containing user data.
- Overwrite completed planning documents.

YOLO mode does not authorize irreversible destructive operations.

Keep Git changes organized and reviewable.

Do not commit or push without authorization.

---

# 17. IMPLEMENTATION WORKFLOW

Follow the existing GSD implementation phase.

Do not restart project planning.

### Stage 1: Review

- Inspect existing files.
- Read project instructions.
- Review GSD progress.
- Confirm architecture and features.
- Resolve obsolete PHP/MySQL instructions.

### Stage 2: Foundation

- Configure Flask.
- Configure SQLite.
- Create application entry point.
- Configure environment variables.
- Verify the application starts.

### Stage 3: UI

- Implement shared layout.
- Implement navigation.
- Implement responsive design.
- Implement dashboard components.

### Stage 4: Features

Implement the required pages and workflows.

### Stage 5: Validation and security

- Implement centralized validation.
- Implement database constraints.
- Implement authentication security.
- Verify authorization.

### Stage 6: Selenium

- Configure Selenium 4.
- Create fixtures.
- Implement browser tests.
- Generate reports.
- Document the actual code.

### Stage 7: Verification

- Run unit tests.
- Run integration tests.
- Run Selenium tests.
- Fix failures.
- Rerun affected tests.

### Stage 8: Documentation

- Update README.
- Update CLAUDE.md.
- Update AGENTS.md.
- Update existing QA documents.
- Update GSD progress.
- Prepare final implementation summary.

Use parallel work only for genuinely independent tasks. Execute dependent implementation tasks in the correct order.

---

# 18. DEFINITION OF DONE

The project is considered complete when:

- Flask application starts successfully.
- SQLite database initializes successfully.
- Required application features work.
- Authentication works.
- Registration and comments persist correctly.
- Validation works on both client and server.
- The Quality Lab demonstrates real validation.
- COVID statistics are real or a clear unavailable state is displayed.
- UI is responsive and accessible.
- Security protections are implemented.
- Selenium 4 browser tests are implemented.
- Selenium tests execute against the actual application.
- HTML test reports are generated.
- Selenium source code is explained.
- Viva questions are documented.
- Test plan and test cases are maintained.
- Traceability is documented.
- Defects and test results are recorded honestly.
- README is accurate.
- CLAUDE.md and AGENTS.md match the final implementation.
- Existing planning and research work is preserved.

---

# 19. FINAL AGENT INSTRUCTIONS

You are implementing an academic Software Testing and Quality Assurance project.

Prioritize:

1. Functional correctness.
2. Simple Python implementation.
3. Professional UI/UX.
4. Database correctness.
5. Input validation.
6. Security.
7. Selenium 4 browser automation.
8. Easy-to-understand source code.
9. Clear testing documentation.
10. Practical examination readiness.

**Do not restart planning.**

**Do not discard existing project research.**

**Do not use PHP, MySQL, or Docker.**

**Do not fabricate test results.**

Read the existing project files, follow the approved requirements, implement the application, execute real tests, and document the actual outcome.

The final goal is a clean, professional, working Covid-flow application that the student can confidently demonstrate and explain to an external examiner.