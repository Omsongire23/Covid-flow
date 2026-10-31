# Technology Stack

**Analysis Date:** 2026-10-31

## Languages

**Primary:**
- PHP 8.1+ (runtime requirement; Docker uses PHP 8.3) - server-rendered pages, API endpoints, authentication, validation, database access
- JavaScript ES modules - browser UI, charts, validation parity, API calls, and Node-based test scripts

**Secondary:**
- HTML5 and CSS3 - page markup, responsive layout, custom-property design tokens
- SQL - MySQL/MariaDB schema and queries in `database/schema.sql` and PHP services
- Bash - test runners such as `tests/run-all.sh` and `tests/smoke.sh`

## Runtime

**Environment:**
- PHP 8.1+ with `pdo_mysql`, `curl`, and `mbstring`; Docker images use PHP 8.3 Apache/CLI
- Node.js 18+ for JavaScript parity and test scripts
- MySQL 8 or MariaDB 10.11 with InnoDB and `utf8mb4`

**Package Manager:**
- No PHP or JavaScript package manifest detected; dependencies are supplied by PHP extensions, Docker images, and vendored browser assets
- Lockfile: not detected

## Frameworks

**Core:**
- Plain PHP with PDO, no application framework - server pages, APIs, sessions, and persistence
- Vanilla browser JavaScript ES modules - frontend behavior

**Testing:**
- Node.js test scripts (`tests/*.mjs`, `tests/regex.test.mjs`)
- PHP CLI scripts (`tests/*.php`)
- Shell/cURL smoke tests (`tests/smoke.sh`)

**Build/Dev:**
- Docker Compose - reproducible MariaDB, Apache PHP web, and PHP CLI services (`docker-compose.yml`)
- Vercel PHP runtime `vercel-php@0.7.4` - optional serverless deployment (`vercel.json`)

## Key Dependencies

**Critical:**
- PHP PDO / `pdo_mysql` - prepared SQL queries and MySQL connectivity (`includes/db.php`)
- PHP cURL - server-side disease.sh requests (`includes/covid_client.php`)
- PHP `mbstring` - required by documented native setup and validation paths
- Chart.js, vendored in `public/assets/vendor/chart.umd.min.js` - statistics charts without CDN dependency

**Infrastructure:**
- MariaDB 10.11 Docker image - local relational database
- Apache in `php:8.3-apache` - local web serving and rewrite support

## Configuration

**Environment:**
- `config/config.example.php` defines database, app, cache, login/rate-limit, session, and disease.sh defaults
- `config/config.local.php` is loaded when present and should hold local credentials; its contents were not inspected
- Environment variables override config, including `DATABASE_URL`, `MYSQL_URL`, `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`/`DB_PASSWORD`, `DB_CHARSET`, `APP_ENV`, `BASE_URL`, and `COVID_API_BASE` (`includes/bootstrap.php`)

**Build:**
- `docker-compose.yml` installs/uses PHP extensions at container start and configures Apache document root to `public/`
- `vercel.json` routes requests through `api/index.php` and maps the PHP runtime
- `public/.htaccess` is present for Apache routing rules

## Platform Requirements

**Development:**
- Docker Compose, or PHP 8.1+ with `pdo_mysql`, `curl`, `mbstring`, MySQL/MariaDB, and Node.js 18+

**Production:**
- Apache/PHP with writable session/log fallback locations and a reachable MySQL-compatible database; alternatively Vercel PHP with database URL environment configuration

---

*Stack analysis: 2026-10-31*
