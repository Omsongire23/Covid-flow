<?php
declare(strict_types=1);

define('API_ENDPOINT', true);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

require_get();

// Rate limiting (30 requests per minute per session)
$maxRequests = (int)(APP_CFG['rate_limit_username'] ?? 30);
$now = time();
$_SESSION['_rl_user'] = $_SESSION['_rl_user'] ?? ['count' => 0, 'reset' => $now + 60];

if ($now > $_SESSION['_rl_user']['reset']) {
    $_SESSION['_rl_user'] = ['count' => 1, 'reset' => $now + 60];
} else {
    $_SESSION['_rl_user']['count']++;
    if ($_SESSION['_rl_user']['count'] > $maxRequests) {
        json_response([
            'ok' => false,
            'error' => ['code' => 'RATE_LIMITED', 'message' => 'Rate limit exceeded. Please wait a moment.']
        ], 429);
    }
}

$u = trim((string)($_GET['u'] ?? ''));
if ($u === '') {
    json_response(['ok' => true, 'valid' => false, 'available' => null]);
}

$valid = validate_field('username', $u);
if (!$valid) {
    json_response(['ok' => true, 'valid' => false, 'available' => null]);
}

$pdo = db_try();
if (!$pdo) {
    json_response(['ok' => true, 'valid' => true, 'available' => null]);
}

$stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$u]);
$exists = (bool)$stmt->fetchColumn();

json_response([
    'ok' => true,
    'valid' => true,
    'available' => !$exists
]);
