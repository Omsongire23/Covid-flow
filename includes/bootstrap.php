<?php
declare(strict_types=1);

/**
 * bootstrap.php — loaded by every public PHP file first.
 * Sets up config, session, security headers, error handling.
 */

// ── Error handling ─────────────────────────────────────────────────────────
$configPath = dirname(__DIR__) . '/config/config.local.php';
if (!file_exists($configPath)) {
    // Provide a clear error so the developer knows what to do
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Configuration file missing.\n";
    echo "Copy config/config.example.php to config/config.local.php and fill in your database credentials.\n";
    exit;
}

$cfg = require $configPath;

// Toggle error display by environment
if (($cfg['app_env'] ?? 'production') === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

$logDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/php_errors.log');

// ── Session ─────────────────────────────────────────────────────────────────
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Idle timeout ─────────────────────────────────────────────────────────────
$timeout = (int)($cfg['session_timeout'] ?? 1800);
if (isset($_SESSION['_last_activity'])) {
    if ((time() - $_SESSION['_last_activity']) > $timeout) {
        session_unset();
        session_destroy();
        session_start();
    }
}
$_SESSION['_last_activity'] = time();

// ── Security headers ─────────────────────────────────────────────────────────
$csp = implode('; ', [
    "default-src 'self'",
    "img-src 'self' data:",
    "script-src 'self'",
    "style-src 'self'",
    "object-src 'none'",
    "base-uri 'none'",
    "form-action 'self'",
    "frame-ancestors 'none'",
]);
header('Content-Security-Policy: ' . $csp);
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('X-Frame-Options: DENY');
header("Permissions-Policy: camera=(), microphone=(), geolocation=()");

// ── Global config ─────────────────────────────────────────────────────────────
define('APP_CFG',  $cfg);
define('APP_ROOT', dirname(__DIR__));
define('IS_HTTPS', $isHttps);

// ── Autoload includes ─────────────────────────────────────────────────────────
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/validate.php';
