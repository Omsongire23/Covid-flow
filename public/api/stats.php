<?php
declare(strict_types=1);

define('API_ENDPOINT', true);
require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/covid_client.php';

require_get();

$scope = $_GET['scope'] ?? 'global';

if ($scope === 'global') {
    $res = CovidClient::getGlobal();
    json_response($res, $res['ok'] ? 200 : 503);
}

if ($scope === 'countries') {
    $res = CovidClient::getCountries();
    json_response($res, $res['ok'] ? 200 : 503);
}

if ($scope === 'history') {
    $days = (string)($_GET['days'] ?? '30');
    if (!in_array($days, ['30', '90', '180', '365', 'all'], true)) {
        json_response([
            'ok' => false,
            'error' => [
                'code' => 'INVALID_PARAMETER',
                'message' => 'Parameter "days" must be one of: 30, 90, 180, 365, all'
            ]
        ], 400);
    }
    $res = CovidClient::getHistorical($days);
    json_response($res, $res['ok'] ? 200 : 503);
}

json_response([
    'ok' => false,
    'error' => [
        'code' => 'INVALID_SCOPE',
        'message' => 'Valid scopes are: global, countries, history'
    ]
], 400);
