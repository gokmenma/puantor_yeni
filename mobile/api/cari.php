<?php
/**
 * Mobile Cari API Proxy
 */
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$target = __DIR__ . "/../../api/cari/cari.php";

if (!file_exists($target)) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "API target not found"]);
    exit;
}

try {
    chdir(dirname($target));
    require $target; 
} catch (Throwable $e) {
    if (ob_get_length()) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Proxy Error: " . $e->getMessage()]);
    exit;
}
