<?php
/**
 * COVID-19 Portal — Configuration Template
 * Copy to config/config.local.php and fill in real values.
 * config.local.php is git-ignored; NEVER commit real credentials.
 */

return [
    // Database
    'db_host'     => '127.0.0.1',
    'db_port'     => 3306,
    'db_name'     => 'covid_portal',
    'db_user'     => 'YOUR_DB_USER',
    'db_pass'     => 'YOUR_DB_PASSWORD',
    'db_charset'  => 'utf8mb4',

    // Application
    'app_env'     => 'development',   // 'development' | 'production'
    'app_name'    => 'COVID-19 Portal',
    'base_url'    => 'http://127.0.0.1:8000',

    // Cache TTLs (seconds)
    'cache_ttl_global'    => 3600,    // 1 hour  for all / countries
    'cache_ttl_history'   => 21600,   // 6 hours for historical

    // Login throttling
    'login_max_attempts'  => 5,
    'login_window_sec'    => 900,     // 15 minutes

    // Rate limits (requests per minute per session)
    'rate_limit_username' => 30,
    'rate_limit_comment'  => 30,

    // Session idle timeout (seconds)
    'session_timeout'     => 1800,    // 30 minutes

    // disease.sh base URL
    'covid_api_base'      => 'https://disease.sh/v3/covid-19/',
];
