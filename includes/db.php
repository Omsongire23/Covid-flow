<?php
declare(strict_types=1);

/**
 * db.php — PDO factory. Returns a singleton connection.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = APP_CFG;
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $cfg['db_host'] ?? '127.0.0.1',
        (int)($cfg['db_port'] ?? 3306),
        $cfg['db_name'] ?? 'covid_portal',
        $cfg['db_charset'] ?? 'utf8mb4'
    );

    try {
        $pdo = new PDO($dsn, $cfg['db_user'] ?? '', $cfg['db_pass'] ?? '', [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        // Log the real error but never expose it to the user
        error_log('DB connection failed: ' . $e->getMessage());
        http_response_code(503);
        if (defined('API_ENDPOINT') && API_ENDPOINT) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode(['ok' => false, 'error' => ['code' => 'DB_UNAVAILABLE', 'message' => 'Database unavailable.']]);
        } else {
            include APP_ROOT . '/public/error_503.php';
        }
        exit;
    }

    return $pdo;
}

/**
 * Attempt to get the db — returns null if unavailable (used in graceful fallback paths).
 */
function db_try(): ?PDO
{
    try {
        return db();
    } catch (Throwable) {
        return null;
    }
}
