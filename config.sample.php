<?php

function fastnetpay_env($key, $default = null)
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ||
             (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? 'https://' : 'http://';

$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
$scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
$baseDir = rtrim(dirname($scriptName), '/\\');
$baseDir = $baseDir === '.' ? '' : $baseDir;
define('APP_URL', rtrim(fastnetpay_env('APP_URL', $protocol . $host . $baseDir), '/'));

// Live, Dev, Demo. Use Live in production to suppress browser error output.
$_app_stage = fastnetpay_env('APP_STAGE', 'Dev');

// Database PHPNuxBill / FASTNETPAY.
// Production should use a dedicated non-root DB user and a strong password.
$db_host = fastnetpay_env('DB_HOST', 'fastnetpay_db');
$db_port = fastnetpay_env('DB_PORT', '3306');
$db_user = fastnetpay_env('DB_USER', 'root');
$db_pass = fastnetpay_env('DB_PASSWORD', 'change_me_for_non_local_use');
$db_password = $db_pass;
$db_name = fastnetpay_env('DB_NAME', 'fast_pay_net');

// Optional Radius database values. Defaults mirror the main DB for local testing.
$radius_host = fastnetpay_env('RADIUS_DB_HOST', $db_host);
$radius_user = fastnetpay_env('RADIUS_DB_USER', $db_user);
$radius_pass = fastnetpay_env('RADIUS_DB_PASSWORD', $db_pass);
$radius_name = fastnetpay_env('RADIUS_DB_NAME', $db_name);

error_reporting(E_ERROR);
$displayErrors = fastnetpay_env('APP_DISPLAY_ERRORS', $_app_stage !== 'Live' ? '1' : '0') === '1';
ini_set('display_errors', $displayErrors ? '1' : '0');
ini_set('display_startup_errors', $displayErrors ? '1' : '0');
ini_set('log_errors', '1');

$sessionName = preg_replace('/[^A-Za-z0-9_]/', '', (string) fastnetpay_env('SESSION_NAME', 'FASTNETPAYSESSID'));
if ($sessionName !== '') {
    session_name($sessionName);
}
