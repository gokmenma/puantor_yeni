<?php
require_once __DIR__ . '/../App/bootstrap.php';
require_once __DIR__ . '/../App/Helper/session_security.php';
puantorStartSecureSession();

$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}
unset($envFile, $line, $key, $value);

require_once 'configs/connect.php';
require_once 'configs/functions.php';


$user_id = isset($_SESSION['user']->id) ? $_SESSION['user']->id : 0;
$user_name = isset($_SESSION['user']->full_name) ? $_SESSION['user']->full_name : '';
