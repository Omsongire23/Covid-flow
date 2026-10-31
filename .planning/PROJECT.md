# Covid-flow

## What This Is

Covid-flow is an educational, public-health information portal built for an STQA mini project. It gives visitors trustworthy COVID-19 guidance and clearly sourced statistics, while giving students a simple Flask codebase and Selenium 4 suite they can demonstrate and explain in a viva.

## Core Value

Provide reliable, clearly labelled COVID-19 information through a secure, understandable Flask application whose testing evidence is easy for a student to run and explain.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Replace the legacy PHP/MySQL/Docker application with a small Flask + SQLite application.
- [ ] Provide sourced COVID-19 statistics, health guidance, authentication, community comments, programme registration, and a focused Quality Lab.
- [ ] Deliver accessible, responsive public-health UI with honest loading, fallback, and error states.
- [ ] Build unit, integration, and Selenium 4 tests with page objects, reports, screenshots, and viva-ready explanations.
- [ ] Preserve the original project in Git and document the rebuild, architecture, security, test evidence, and limitations.

### Out of Scope

- Medical diagnosis, treatment, appointments, or patient records — Covid-flow is informational only.
- Payments, SMS/email delivery, microservices, Docker, MySQL, PHP, Node.js, React, and Next.js — they add complexity or violate the rebuild constraints.
- Fabricated live statistics or fabricated test results — data and reports must disclose their source and execution status.

## Context

The legacy PHP implementation is intentionally absent from the working tree but remains recoverable at `main` and `origin/main` commit `be9d7af`. Its PRD and STQA documentation identify useful product scope: dashboard statistics, guidance, authentication, comments, programme registration, validation, quality demonstrations, and moderation. The replacement must keep the important learning objectives while reducing technology and operational complexity.

## Constraints

- **Tech stack**: Python 3, Flask, SQLite, Jinja2, HTML/CSS/vanilla JavaScript, Chart.js, pytest, pytest-html, and Selenium 4 — required by the academic brief.
- **Simplicity**: Clear modules and standard-library `sqlite3` preferred — the application must be explainable during a practical examination.
- **Data integrity**: Use server-side data retrieval, labelled local fallback data, and no invented live figures — public-health information must be honest.
- **Security**: CSRF protection, password hashing, parameterized SQL, ownership checks, safe output, and basic login throttling — user data and demonstrations must be safe.
- **Safety**: Do not perform irreversible deletion, database destruction, force-pushes, or major architectural changes without explicit user approval — user instruction.
- **Verification**: Browser tests must use Selenium 4 explicit waits and actual reports must distinguish executed from unavailable infrastructure — user instruction.

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Flask with SQLite replaces PHP/MySQL/Docker | Meets the mandatory stack and keeps the application easy to demonstrate. | — Pending |
| Preserve original via `main` and `origin/main`; rebuild on `rebuild/python-selenium` | Keeps the legacy implementation recoverable while allowing a clean working tree. | ✓ Good |
| Standard GSD phases, sequential dependencies, parallel independent work | Balances auditability with speed. | — Pending |
| Minimal, accessible public-health design | Supports trust, readability, and maintainability. | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `$gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `$gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-10-31 after rebuild initialization*
