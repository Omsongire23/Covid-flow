# Covid-flow

## Dynamic COVID-19 Information & Surveillance Portal (SQA Mini Project)

> **Software Testing and Quality Assurance (SQA) Mini Project**  
> **Course Stack:** HTML5, CSS3, Vanilla JavaScript (ES Modules), PHP 8.1+ (PDO), MySQL / MariaDB  
> **Repository Root:** `/home/om/Downloads/covid19`  
> **Live Local Endpoint:** [http://127.0.0.1:8000](http://127.0.0.1:8000)  

---

## 1. Problem Statement & Mandatory Requirements

> *"Software Testing and Quality Assurance Mini Project: Dynamic website of COVID-19 information using HTML, CSS, JavaScript and PHP. MySQL database used to store user account, comment, and registration form details. Regular expression test cases for testing purposes."*

This repository delivers both halves of the required SQA mini project deliverables:
1. **The Product:** A responsive, accessible, dynamic COVID-19 public health website with live data feeds, user authentication, community discussions, and separate programme registration.
2. **The Quality Evidence:** Single-source regular expression validation, dual-engine parity testing (ECMAScript `/u` vs PHP PCRE `~...~Du`), linear-time ReDoS benchmarks ($< 2\text{ ms}$), automated test suites, an interactive Quality Lab, and verified SQA documentation.

---

## 2. Feature Matrix (Mapped to Requirements)

| Requirement ID | Feature Description | Status | Verification Reference |
|---|---|---|---|
| **FR-01** | Home page live global surveillance metrics (confirmed, active, recovered, deaths, daily deltas, timestamp). | **Delivered** | `TC-API-01a`, `TC-UI-01` |
| **FR-02** | Statistics page: historical trajectory chart (30D/90D/180D/1Y/All) and searchable, sortable country table. | **Delivered** | `TC-API-01b`, `TC-UI-02` |
| **FR-03** | Curated guidance pages: Prevention, Symptoms, Vaccination, Myths vs Facts, Helplines with WHO/MoHFW citations. | **Delivered** | `TC-UI-01` |
| **FR-04** | User account signup with client & server regex validation, password hashing, and duplicate account rejection. | **Delivered** | `TC-AUTH-01..04` |
| **FR-05** | Login & logout with session hardening, session ID regeneration, and 5-attempt/15-min brute-force lockout. | **Delivered** | `TC-AUTH-05..09` |
| **FR-06** | User profile page: account metadata, own registrations with cancellation action, and own comments. | **Delivered** | `TC-FRM-01`, `TC-CMT-01` |
| **FR-07** | Community discussion board: paginated feed, 500-char counter, client & server regex sanitization, own comment deletion. | **Delivered** | `TC-CMT-01..05` |
| **FR-08** | Programme Registration form: separate enrollment form, MySQL persistence, duplicate prevention, reference numbers (`REG-XXXXXX`). | **Delivered** | `TC-FRM-01..06` |
| **FR-09** | Shared regular expression validation (JS & PHP) across 9 distinct fields with cross-engine parity. | **Delivered** | 127 regex cases & benchmarks |
| **FR-10** | In-browser Quality Lab: live pattern tester, automated test suite runner, CSV/JSON export, and SQA explainer cards. | **Delivered** | `TC-UI-06`, `/quality-lab.php` |
| **FR-11** | Professional UI: light/dark theme toggle, WCAG AA contrast, responsive grids, keyboard accessibility. | **Delivered** | `TC-UI-03..05` |
| **FR-12** | Resilient upstream data handling: MySQL cache (`covid_cache`), stale banner on failure, honest empty state (never fake data). | **Delivered** | `TC-API-01`, `TC-API-02` |
| **FR-13** | Admin comment moderation console (Stretch requirement): view, filter, hide, restore, and soft-delete comments. | **Delivered** | `TC-SEC-05a`, `/admin/comments.php` |

---

## 3. Architecture & Key Decisions (ADRs)

- **ADR-001: Plain PHP 8.1+ with PDO (No Frameworks):** Code is transparent and readable for academic grading. Uses explicit PDO prepared statements with `PDO::ATTR_EMULATE_PREPARES => false`.
- **ADR-002: Server-Side COVID-19 Telemetry with MySQL Caching:** Telemetry is fetched server-side from `disease.sh` (cURL), cached in MariaDB (`covid_cache`), and served same-origin via `api/stats.php`. Browsers never call external third-party APIs directly.
- **ADR-003: Native Hardened PHP Sessions & CSRF Protection:** Strict cookie flags (`HttpOnly`, `SameSite=Lax`, `Secure` when HTTPS), session regeneration on authentication, 30-minute idle expiration, and 32-byte cryptographic CSRF tokens on every state-changing request.
- **ADR-004: Single Source of Truth for Regular Expressions:** All regex patterns reside in `config/patterns.json`. Executed in JavaScript with `new RegExp(p, 'u')` and in PHP with `preg_match('~' . $p . '~Du', $v)`. Modifiers `D` (dollar-endonly) and `u` (Unicode) are mandatory to eliminate discrepancies.
- **ADR-005: Localhost / Container Deployment:** Multi-container Docker topology (`covid_web` + `covid_db`) ensuring predictable execution and verification on any host machine without dependency drift.

---

## 4. Regular Expression Validation Specification

All patterns are loaded from `config/patterns.json`:

| Target Field | Pattern Definition | What It Guarantees |
|---|---|---|
| `username` | `^[A-Za-z][A-Za-z0-9_]{3,19}$` | 4–20 characters; begins with letter; letters, digits, underscore only. |
| `fullName` | `^(?=.{2,60}$)[\p{L}\p{M}]+(?:[ '\-][\p{L}\p{M}]+)*$` | 2–60 letters; Unicode support (\p{L}\p{M}); single spaces, apostrophes, or hyphens between words. |
| `email` | `^(?=.{6,254}$)(?!.*\\.\\.)[A-Za-z0-9](?:[A-Za-z0-9._%+\\-]*[A-Za-z0-9])?@(?:[A-Za-z0-9](?:[A-Za-z0-9\\-]{0,61}[A-Za-z0-9])?\\.)+[A-Za-z]{2,24}$` | 6–254 chars; RFC 5322 modern subset; disallows consecutive dots, leading/trailing dots, single-letter TLDs. |
| `password` | `^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d)(?=.*[^A-Za-z0-9\\s])\\S{8,64}$` | 8–64 characters; requires lowercase, uppercase, digit, and special symbol; disallows whitespace. |
| `phoneIN` | `^(?:\\+91[\\- ]?|0)?[6-9]\\d{9}$` | 10-digit Indian mobile number starting 6–9; optional `+91` or `0` prefix. |
| `dob` | `^\\d{4}-(?:0[1-9]|1[0-2])-(?:0[1-9]|[12]\\d|3[01])$` | ISO `YYYY-MM-DD` syntax; months 01–12; days 01–31. Semantic checkdate & age rules follow. |
| `pinCode` | `^[1-9]\\d{5}$` | 6-digit Indian Postal Index Number; first digit 1–9 (no leading zero). |
| `countryQuery` | `^[\\p{L}\\p{M}][\\p{L}\\p{M} .'\\-]{1,55}$` | 2–56 letters; spaces, dots, apostrophes, and hyphens allowed; rejects SQL injection and paths. |
| `comment` | `^(?=[\\s\\S]{3,500}$)(?=[\\s\\S]*\\S)(?![\\s\\S]*<[^>]*>)[\\s\\S]*$` | 3–500 chars; non-blank; rejects HTML-tag-like content; linear-time ReDoS safe. |

### Cross-Engine Rules
1. **Mandatory PCRE `D` modifier:** Without `D`, PCRE's `$` matches *before* a trailing newline (accepting `"abcd\n"`). With `D`, PHP and JavaScript agree 100%.
2. **Mandatory `u` modifier:** Enables multibyte Unicode support (`\p{L}`, `\p{M}`) and counts characters instead of bytes.
3. **Linear-Time Lookahead Ordering:** The `comment` pattern enforces length constraint (`(?=[\s\S]{3,500}$)`) *before* the tag inspection lookahead, eliminating catastrophic backtracking.

---

## 5. Local Setup & Execution Guide

### Option A: Running via Docker (Recommended & Active)

The project is currently configured and running via Docker Compose:

1. **Start all services:**
   ```bash
   docker compose up -d
   ```
2. **Access the Application:**
   Open your browser to: [http://127.0.0.1:8000](http://127.0.0.1:8000)
3. **MariaDB Database Access:**
   Port: `3307` | Database: `covid_portal` | User: `covid_user` | Password: `covid_pass`
4. **Provision an Administrator Account (CLI):**
   ```bash
   docker compose exec -it web php database/create_admin.php
   ```

### Option B: Native Host Setup (Apache / PHP 8.1+ / MySQL)

1. **Install Prerequisites (Debian/Ubuntu):**
   ```bash
   sudo apt update
   sudo apt install -y php8.3 php8.3-cli php8.3-mysql php8.3-curl php8.3-mbstring mariadb-server
   ```
2. **Import Database Schema:**
   ```bash
   mysql -u root -p < database/schema.sql
   ```
3. **Configure Environment:**
   Copy `config/config.example.php` to `config/config.local.php` and configure DB credentials.
4. **Run Native Development Server:**
   ```bash
   php -S 127.0.0.1:8000 -t public
   ```

---

## 6. How to Run the Automated Test Suites

The project provides individual and unified test runners:

### Master Test Runner (Runs All 5 Suites)
```bash
bash tests/run-all.sh
```

### Individual Test Runners
1. **Node.js RegExp Suite (/u):**
   ```bash
   node tests/regex.test.mjs
   ```
2. **PHP PCRE Suite (~...~Du):**
   ```bash
   docker compose exec web php tests/regex_test.php
   ```
3. **Cross-Engine Parity Check:**
   ```bash
   node tests/engine-parity.mjs
   ```
4. **Semantic Validator Suite:**
   ```bash
   docker compose exec web php tests/validator_test.php
   ```
5. **HTTP Smoke & Security Test Suite:**
   ```bash
   bash tests/smoke.sh
   ```

---

## 7. Quality Assurance Deliverables Directory

All course deliverables are located in `docs/`:

- [`docs/TEST_PLAN.md`](docs/TEST_PLAN.md): Formal test strategy, scope, entry/exit criteria, and environment specifications.
- [`docs/TEST_CASES.md`](docs/TEST_CASES.md): Complete catalog of 153 system, security, API, and regex test cases.
- [`docs/TRACEABILITY.md`](docs/TRACEABILITY.md): Requirements Traceability Matrix linking FR-01 through FR-13 to tests.
- [`docs/DEFECT_LOG.md`](docs/DEFECT_LOG.md): Defect register documenting real findings, root causes, and remediation notes.
- [`docs/TEST_REPORT.md`](docs/TEST_REPORT.md): Final execution report with empirical command output logs and 100% pass rates.

---

## 8. Security Measures Summary

1. **SQL Injection:** PDO prepared statements with strict parameter binding (`PDO::ATTR_EMULATE_PREPARES => false`).
2. **Cross-Site Scripting (XSS):** Every dynamic variable is sanitized via `e()` (`htmlspecialchars(ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')`).
3. **Content Security Policy (CSP):** Zero inline scripts or styles. Headers enforce:
   `default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'`
4. **Cross-Site Request Forgery (CSRF):** Cryptographic 32-byte tokens per session enforced on all state-changing endpoints.
5. **Brute-Force Throttling:** 5 failed attempts per (username, IP) in 15 minutes triggers an HTTP 429 lockout.
6. **Timing Attack Resistance:** Dummy password verification hashes executed when usernames do not exist.
7. **Session Hardening:** Cookie attributes `HttpOnly`, `SameSite=Lax`, and 30-minute idle expiration.

---

## 9. Data Attribution & Medical Disclaimer

- **Data Attribution:** COVID-19 epidemiological statistics are retrieved from the open-source [disease.sh](https://disease.sh) surveillance repository.
- **Guidance Citations:** Clinical protocols and public health recommendations are cited from the World Health Organization (WHO) and the Ministry of Health and Family Welfare (MoHFW), Government of India.
- **Medical Disclaimer:** *This website is developed for academic Software Testing and Quality Assurance educational purposes. It does not replace professional medical consultation, diagnosis, or clinical advice. In an emergency, contact your local emergency services or national helpline **1075** immediately.*
