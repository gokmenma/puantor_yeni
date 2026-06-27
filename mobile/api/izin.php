<?php
// Puantor Mobile - Leave Request API Proxy
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$apiPath = dirname(__DIR__, 2) . '/api/izin/talep.php';
if (file_exists($apiPath)) {
    require_once $apiPath;
} else {
    echo json_encode(['status' => 'error', 'message' => 'API dosyası bulunamadı.']);
    exit;
}
