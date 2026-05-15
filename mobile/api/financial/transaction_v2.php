<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$target = __DIR__ . '/../../../api/financial/transaction.php';
if (file_exists($target)) {
    require_once $target;
} else {
    echo json_encode(['status' => 'error', 'message' => 'API target not found'], JSON_UNESCAPED_UNICODE);
    exit;
}
