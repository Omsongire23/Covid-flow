<?php
declare(strict_types=1);

define('API_ENDPOINT', true);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=3600');

$casesPath = APP_ROOT . '/tests/regex-cases.json';
if (!file_exists($casesPath)) {
    json_response(['ok' => false, 'error' => 'Regex cases file missing.'], 500);
}

readfile($casesPath);
exit;
