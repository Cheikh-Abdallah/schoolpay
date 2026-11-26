<?php

declare(strict_types=1);

// Chargement et analyse du fichier .env
$envFile = dirname(__DIR__) . '/.env';
$env = [];

if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            // Supprimer les guillemets éventuels
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            $env[$key] = $value;
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Constantes globales
if (!defined('APP_NAME')) {
    define('APP_NAME', $env['APP_NAME'] ?? 'SchoolPay');
}
if (!defined('APP_ENV')) {
    define('APP_ENV', $env['APP_ENV'] ?? 'production');
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', filter_var($env['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));
}
if (!defined('APP_KEY')) {
    define('APP_KEY', $env['APP_KEY'] ?? '');
}
if (!defined('APP_URL')) {
    define('APP_URL', rtrim($env['APP_URL'] ?? 'http://localhost/schoolpay/public', '/'));
}
if (!defined('APP_TIMEZONE')) {
    define('APP_TIMEZONE', $env['APP_TIMEZONE'] ?? 'Africa/Dakar');
}
if (!defined('APP_CURRENCY')) {
    define('APP_CURRENCY', $env['APP_CURRENCY'] ?? 'XOF');
}

$saspayEnvironment = strtolower($env['SASPAY_ENV'] ?? 'sandbox');
if (!in_array($saspayEnvironment, ['sandbox', 'live'], true)) {
    throw new RuntimeException('SASPAY_ENV doit valoir sandbox ou live.');
}

date_default_timezone_set(APP_TIMEZONE);

return [
    'name' => APP_NAME,
    'env' => APP_ENV,
    'debug' => APP_DEBUG,
    'key' => APP_KEY,
    'url' => APP_URL,
    'timezone' => APP_TIMEZONE,
    'currency' => APP_CURRENCY,
    'session_lifetime' => (int) ($env['SESSION_LIFETIME'] ?? 7200),
    'trial_hours' => (int) ($env['TRIAL_DURATION_HOURS'] ?? 72),
    'trial_sms' => (int) ($env['TRIAL_INITIAL_SMS'] ?? 10),
    'sendtext' => [
        'base_url' => $env['SENDTEXT_BASE_URL'] ?? 'https://api.sendtext.sn/v1',
        'api_key' => $env['SENDTEXT_API_KEY'] ?? '',
        'api_secret' => $env['SENDTEXT_API_SECRET'] ?? '',
        'sender_name' => $env['SENDTEXT_SENDER_NAME'] ?? 'SCHOOLPAY',
    ],
    'saspay' => [
        'environment' => $saspayEnvironment,
        'api_key' => $env['SASPAY_API_KEY'] ?? '',
        'base_url' => $saspayEnvironment === 'live'
            ? 'https://api.saspay.me/api/v1'
            : 'https://sandbox-api.saspay.me/api/v1',
    ],
];
