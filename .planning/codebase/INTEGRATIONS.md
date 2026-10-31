# External Integrations

**Analysis Date:** 2026-10-31

## APIs & External Services

**COVID statistics:**
- disease.sh v3 COVID-19 API - global totals, country breakdowns, and historical series (`includes/covid_client.php`)
  - SDK/Client: native PHP cURL; no third-party SDK
  - Auth: none documented; base URL is configurable through `COVID_API_BASE`
  - Requests use JSON Accept header, TLS certificate/host verification, 5-second connect and 10-second total timeouts, and a 10 MB response guard

**Browser API surface:**
- Same-origin PHP endpoints under `public/api/` (`stats.php`, `comments.php`, `check-username.php`, `patterns.php`, `regex-cases.php`)
  - Client code calls these endpoints; the browser does not call disease.sh directly

## Data Storage

**Databases:**
- MariaDB 10.11 Docker service or MySQL 8/MariaDB deployment
  - Connection: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`/`DB_PASSWORD`, or `DATABASE_URL`/`MYSQL_URL`
  - Client: PHP PDO with `PDO::ATTR_ERRMODE = EXCEPTION`, associative fetches, and emulated prepares disabled (`includes/db.php`)
  - Tables: users, registrations, comments, login_attempts, and covid_cache (`database/schema.sql`)

**File Storage:**
- Local project files for static content and offline COVID historical snapshot (`data/content.json`, `data/covid-fallback.json`)
- Local `storage/logs/php_errors.log` when writable; `/tmp/php_errors.log` in serverless/read-only environments

**Caching:**
- MySQL `covid_cache` table keyed by global/countries/historical request, with configurable 1-hour global and 6-hour historical TTLs
- On live failure, stale database cache is served; historical data can then fall back to `data/covid-fallback.json`

## Authentication & Identity

**Auth Provider:**
- Custom native PHP session authentication (`includes/auth.php`)
  - User records and password hashes are stored in MySQL `users`
  - Password hashing uses PHP application logic; session cookies are HttpOnly, SameSite=Lax, and secure when HTTPS is detected
  - CSRF protection is implemented locally in `includes/csrf.php`
  - Login throttling uses `login_attempts` with a configured limit of 5 attempts per 15 minutes

## Monitoring & Observability

**Error Tracking:**
- None detected; failures are written through PHP `error_log`

**Logs:**
- PHP errors go to `storage/logs/php_errors.log` when writable, otherwise `/tmp/php_errors.log` in Vercel/container-safe paths
- Upstream request and cache failures are logged without exposing database errors to users

## CI/CD & Deployment

**Hosting:**
- Local Docker Compose (`docker-compose.yml`) or Apache/PHP native hosting
- Optional Vercel deployment using `vercel.json` and `vercel-php@0.7.4`

**CI Pipeline:**
- No CI workflow detected; tests are run locally via `tests/run-all.sh`, PHP/Node scripts, and smoke tests

## Environment Configuration

**Required env vars:**
- Local native setup requires `config/config.local.php` or equivalent database variables
- Container setup supplies `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS`; cloud deployments can use `DATABASE_URL`/`MYSQL_URL`
- `COVID_API_BASE` is optional and defaults to disease.sh

**Secrets location:**
- Local credentials belong in git-ignored `config/config.local.php` or deployment environment variables. Secret file contents were not read.

## Webhooks & Callbacks

**Incoming:**
- None detected. Vercel routes HTTP requests to `api/index.php`; this is application routing, not a webhook.

**Outgoing:**
- Outbound HTTPS GET requests to disease.sh only; no webhook registration or callback endpoint detected.

---

*Integration audit: 2026-10-31*
