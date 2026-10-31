# Coding Conventions

**Analysis Date:** 2026-10-31

## Naming Patterns

**Files:**
- PHP files use lowercase snake-free descriptive names such as `program-registration.php`, `covid_client.php`, and `layout_header.php`; JavaScript modules use lowercase names such as `validate.js` and `theme-init.js`.
- Test files use either the subject suffix (`validator_test.php`, `regex_test.php`) or the runner style (`regex.test.mjs`).

**Functions:**
- PHP functions use `snake_case` (`validate_registration`, `json_response`, `db_try`). JavaScript functions use `camelCase` (`expandInput`, `renderResults`, `loadStats`).

**Variables:**
- PHP locals and array keys use `snake_case`; JavaScript locals use `camelCase`. Constants are uppercase (`APP_ROOT`, `APP_CFG`, `API_ENDPOINT`).

**Types:**
- PHP uses strict scalar/union return types and `declare(strict_types=1)` in application and test files. Configuration and JSON data are represented as associative arrays.

## Code Style

**Formatting:**
- No formatter configuration was detected. PHP uses four-space indentation, braces on the same line, aligned assignments in configuration blocks, and one statement per line. JavaScript uses two-space indentation and semicolons.

**Linting:**
- No ESLint, PHP_CodeSniffer, Prettier, or Biome configuration was detected. Syntax/runtime checks are supplied by the test scripts.

## Import Organization

**Order:**
1. PHP files load shared modules with `require_once` from `includes/bootstrap.php`.
2. JavaScript uses static ES module imports from `node:` or relative paths.
3. Test scripts import Node built-ins first, then resolve project paths.

**Path Aliases:**
- None detected. PHP uses `APP_ROOT` and `dirname(__DIR__)`; JavaScript resolves paths from `import.meta.url`.

## Error Handling

**Patterns:**
- HTTP helpers terminate after setting status and headers (`json_response`, `require_post`, `require_get` in `includes/helpers.php`).
- Database failures are logged privately and rendered as a generic 503 response in `includes/db.php`; `db_try()` converts unavailable database errors to `null` for fallback paths.
- Validation returns booleans, a message string, or structured `['ok'=>..., 'error'=>...]` arrays depending on the validator (`includes/validate.php`).
- Test runners collect failures, print a summary, and exit non-zero when any assertion fails.

## Logging

**Framework:** PHP `error_log`, configured by `includes/bootstrap.php`.

**Patterns:**
- Operational errors are written to `storage/logs/php_errors.log` or `/tmp/php_errors.log`; user-facing responses do not expose database exception text. Test scripts print human-readable suite progress to stdout.

## Comments

**When to Comment:**
- Comments explain security rationale, environment behavior, or a non-obvious normalization rule. Section banners group bootstrap responsibilities.

**JSDoc/TSDoc:**
- PHP public helpers have concise docblocks with input/output intent. JavaScript modules use file-level comments; inline documentation is limited.

## Function Design

**Size:** Keep shared helpers focused on one operation. Larger request handlers in `public/*.php` coordinate input, validation, persistence, and rendering.

**Parameters:** Prefer typed scalar parameters and arrays in PHP. JavaScript functions accept plain objects and use destructuring/defaults where useful.

**Return Values:** Prefer explicit typed returns in PHP. Request/response helpers that send output use `never`; validators return predictable boolean/string/array unions.

## Module Design

**Exports:** JavaScript modules use named exports for reusable utilities and validators; page scripts import only needed functions. PHP files expose global functions after bootstrap.

**Barrel Files:** No barrel modules detected. `includes/bootstrap.php` is the shared PHP composition point.

---

*Convention analysis: 2026-10-31*
