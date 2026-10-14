<?php
declare(strict_types=1);

define('API_ENDPOINT', true);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$patternsPath = APP_ROOT . '/config/patterns.json';
if (!file_exists($patternsPath)) {
    json_response(['ok' => false, 'error' => 'Patterns configuration file missing.'], 500);
}

readfile($patternsPath);
exit;
