<?php
/**
 * Mobile Financial API Proxy
 */
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$target = __DIR__ . '/../../api/financial/transaction.php';

if (file_exists($target)) {
    if (!defined('ROOT')) {
        define("ROOT", dirname(dirname(__DIR__)));
    }
    require_once $target;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Financial API target not found']);
    exit;
}
