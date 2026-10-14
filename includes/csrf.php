<?php
declare(strict_types=1);

/**
 * csrf.php — Per-session CSRF token: generation and verification.
 */

/** Return (and create if needed) the session CSRF token. */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

/**
 * Verify CSRF token from POST body OR X-CSRF-Token header.
 * Exits with 403 on failure.
 */
function csrf_verify(): void
{
    $expected = (string)($_SESSION['_csrf'] ?? '');
    $provided = (string)($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

    if ($expected === '' || $provided === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        if (defined('API_ENDPOINT') && API_ENDPOINT) {
            json_response(['ok' => false, 'error' => ['code' => 'CSRF_INVALID', 'message' => 'Invalid or missing CSRF token.']], 403);
        } else {
            exit('403 Forbidden – invalid CSRF token.');
        }
    }
}

/** Emit a hidden CSRF form field. */
function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}
