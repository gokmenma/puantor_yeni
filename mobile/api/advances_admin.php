<?php
ob_start();

$apiPath = dirname(__DIR__, 2) . '/api/advances/advances.php';
if (file_exists($apiPath)) {
    require_once $apiPath;
} else {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'API dosyasi bulunamadi.']);
    exit;
}
