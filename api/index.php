<?php
declare(strict_types=1);

/**
 * api/index.php — Vercel Serverless Function Front Controller & Router.
 * Bridges incoming Vercel requests to the appropriate PHP script or public asset.
 */

// Determine the requested path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';

// Normalize path: strip leading / and optional public/ prefix
$relPath = ltrim($path, '/');
if (str_starts_with($relPath, 'public/')) {
    $relPath = substr($relPath, 7);
}

// Security: Prevent directory traversal or access to hidden/sensitive paths
if (str_contains($relPath, '..') || str_starts_with($relPath, '.')) {
    http_response_code(403);
    exit('403 Forbidden');
}

// Block direct access to non-public directories
$forbiddenPrefixes = ['config', 'database', 'data', 'includes', 'storage', 'tests', 'docs', '.git', '.env'];
foreach ($forbiddenPrefixes as $prefix) {
    if ($relPath === $prefix || str_starts_with($relPath, $prefix . '/')) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}

$publicDir = dirname(__DIR__) . '/public';

// Route root or empty to index.php
if ($relPath === '' || $relPath === 'index.php') {
    $targetFile = $publicDir . '/index.php';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    $_SERVER['PHP_SELF'] = '/index.php';
    chdir($publicDir);
    require $targetFile;
    exit;
}

$candidate = $publicDir . '/' . $relPath;

// Case 1: Direct PHP file match (e.g. /statistics.php, /api/stats.php, /admin/comments.php)
if (is_file($candidate) && str_ends_with($candidate, '.php')) {
    $_SERVER['SCRIPT_NAME'] = '/' . $relPath;
    $_SERVER['SCRIPT_FILENAME'] = $candidate;
    $_SERVER['PHP_SELF'] = '/' . $relPath;
    chdir(dirname($candidate));
    require $candidate;
    exit;
}

// Case 2: Clean URL without .php (e.g. /statistics -> /public/statistics.php)
if (is_file($candidate . '.php')) {
    $targetFile = $candidate . '.php';
    $scriptName = '/' . $relPath . '.php';
    $_SERVER['SCRIPT_NAME'] = $scriptName;
    $_SERVER['SCRIPT_FILENAME'] = $targetFile;
    $_SERVER['PHP_SELF'] = $scriptName;
    chdir(dirname($targetFile));
    require $targetFile;
    exit;
}

// Case 3: Static asset fallback (if not caught by Vercel CDN routing)
$ext = strtolower(pathinfo($candidate, PATHINFO_EXTENSION));
$mimeMap = [
    'css'   => 'text/css; charset=UTF-8',
    'js'    => 'application/javascript; charset=UTF-8',
    'mjs'   => 'application/javascript; charset=UTF-8',
    'json'  => 'application/json; charset=UTF-8',
    'svg'   => 'image/svg+xml',
    'png'   => 'image/png',
    'jpg'   => 'image/jpeg',
    'jpeg'  => 'image/jpeg',
    'gif'   => 'image/gif',
    'ico'   => 'image/x-icon',
    'woff'  => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf'   => 'font/ttf',
    'map'   => 'application/json',
];

if (isset($mimeMap[$ext]) && is_file($candidate)) {
    header('Content-Type: ' . $mimeMap[$ext]);
    header('Cache-Control: public, max-age=31536000, immutable');
    readfile($candidate);
    exit;
}

// 404 Not Found
http_response_code(404);
header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>404 Not Found | COVID-19 Portal</title></head><body style="font-family: sans-serif; text-align: center; padding: 50px;"><h1>404 Not Found</h1><p>The requested page was not found.</p><p><a href="/">Return to Home</a></p></body></html>';
exit;
