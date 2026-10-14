<?php
declare(strict_types=1);

/**
 * helpers.php — Utility functions used across the application.
 */

/** HTML-escape a value for safe output in templates. */
function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirect to a URL and exit. */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** Emit a JSON response with the given status and exit. */
function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** Set a flash message (stored in session for one redirect). */
function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = compact('type', 'message');
}

/** Get and clear all flash messages. */
function flash_get(): array
{
    $msgs = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $msgs;
}

/** Get the client's real IP address (simple, non-spoofable for throttling). */
function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/** Require a POST request; send 405 otherwise. */
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('405 Method Not Allowed');
    }
}

/** Require a GET request; send 405 otherwise. */
function require_get(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        exit('405 Method Not Allowed');
    }
}

/** Format a timestamp as a human-readable relative string or full ISO date. */
function time_ago(string $datetime): string
{
    $ts   = strtotime($datetime);
    $diff = time() - $ts;

    if ($diff < 60)         return 'just now';
    if ($diff < 3600)       return floor($diff / 60) . 'm ago';
    if ($diff < 86400)      return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)     return floor($diff / 86400) . 'd ago';
    return date('M j, Y', $ts);
}

/** Format a large number with commas. */
function fmt_num(int|float $n): string
{
    return number_format((float)$n);
}

/** Return the path relative to the public directory for use in links. */
function base_url(string $path = ''): string
{
    $base = rtrim(APP_CFG['base_url'] ?? '', '/');
    return $base . '/' . ltrim($path, '/');
}

/** Check if the current page matches a nav path. */
function is_active(string $path): bool
{
    $current = $_SERVER['SCRIPT_NAME'] ?? '';
    $check   = '/' . ltrim($path, '/');
    return $current === $check || str_starts_with($current, rtrim($check, '.php'));
}

/** Generate a secure random token (hex). */
function random_token(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));
}
