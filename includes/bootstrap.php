<?php
declare(strict_types=1);

/**
 * bootstrap.php — loaded by every public PHP file first.
 * Sets up config, session, security headers, error handling.
 */

// ── Configuration loading ──────────────────────────────────────────────────
$exampleConfigPath = dirname(__DIR__) . '/config/config.example.php';
$exampleConfig     = file_exists($exampleConfigPath) ? (require $exampleConfigPath) : [];

$localConfigPath   = dirname(__DIR__) . '/config/config.local.php';
$localConfig       = file_exists($localConfigPath) ? (require $localConfigPath) : [];

$isVercel          = !empty(getenv('VERCEL'));
$hasEnvConfig      = !empty(getenv('DB_HOST')) || !empty(getenv('DATABASE_URL')) || !empty(getenv('MYSQL_URL'));

// If config.local.php is missing AND not running in Vercel/container with env variables:
if (!file_exists($localConfigPath) && !$isVercel && !$hasEnvConfig) {
    // Provide a clear error so the developer knows what to do
    http_response_code(503);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Configuration file missing.\n";
    echo "Copy config/config.example.php to config/config.local.php and fill in your database credentials.\n";
    exit;
}

$cfg = array_merge($exampleConfig, $localConfig);

// Support single connection string: DATABASE_URL / MYSQL_URL (common in cloud MySQL providers)
$dbUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL');
if ($dbUrl) {
    $parts = parse_url($dbUrl);
    if ($parts) {
        if (!empty($parts['host'])) $cfg['db_host']    = $parts['host'];
        if (!empty($parts['port'])) $cfg['db_port']    = (int)$parts['port'];
        if (!empty($parts['user'])) $cfg['db_user']    = urldecode($parts['user']);
        if (isset($parts['pass']))  $cfg['db_pass']    = urldecode($parts['pass']);
        if (!empty($parts['path'])) $cfg['db_name']    = ltrim($parts['path'], '/');
    }
}

// Support individual environment variables (overrides defaults and config.local.php)
if (getenv('DB_HOST'))                  $cfg['db_host']            = getenv('DB_HOST');
if (getenv('DB_PORT'))                  $cfg['db_port']            = (int)getenv('DB_PORT');
if (getenv('DB_NAME'))                  $cfg['db_name']            = getenv('DB_NAME');
if (getenv('DB_USER'))                  $cfg['db_user']            = getenv('DB_USER');
if (getenv('DB_PASS') !== false)        $cfg['db_pass']            = getenv('DB_PASS');
if (getenv('DB_PASSWORD') !== false)    $cfg['db_pass']            = getenv('DB_PASSWORD');
if (getenv('DB_CHARSET'))               $cfg['db_charset']         = getenv('DB_CHARSET');

if (getenv('APP_ENV'))                  $cfg['app_env']            = getenv('APP_ENV');
if (getenv('APP_NAME'))                 $cfg['app_name']           = getenv('APP_NAME');
if (getenv('BASE_URL'))                 $cfg['base_url']           = getenv('BASE_URL');
if (getenv('COVID_API_BASE'))           $cfg['covid_api_base']     = getenv('COVID_API_BASE');
if (getenv('SESSION_TIMEOUT'))          $cfg['session_timeout']    = (int)getenv('SESSION_TIMEOUT');

// Toggle error display by environment
if (($cfg['app_env'] ?? 'production') === 'development') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

// Storage and log path safety for serverless (read-only filesystem)
$logDir = dirname(__DIR__) . '/storage/logs';
if (!is_dir($logDir) && !$isVercel) {
    @mkdir($logDir, 0755, true);
}
if (is_dir($logDir) && is_writable($logDir)) {
    ini_set('error_log', $logDir . '/php_errors.log');
} elseif ($isVercel || is_writable('/tmp')) {
    ini_set('error_log', '/tmp/php_errors.log');
}

// ── Session ─────────────────────────────────────────────────────────────────
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443);

if (session_status() === PHP_SESSION_NONE) {
    if ($isVercel || (!is_writable(session_save_path() ?: '/tmp') && is_writable('/tmp'))) {
        session_save_path('/tmp');
    }
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
