<?php

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'development' ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'Europe/Istanbul');

require_once __DIR__ . '/Logging/SystemLogger.php';

\App\Logging\SystemLogger::register();

if (!function_exists('system_log_error')) {
    function system_log_error(string $message, array $context = []): void
    {
        \App\Logging\SystemLogger::error($message, $context);
    }
}

if (!function_exists('system_log_exception')) {
    function system_log_exception(\Throwable $exception, array $context = []): void
    {
        \App\Logging\SystemLogger::exception($exception, $context);
    }
}
