<?php
/**
 * Mobile Cari API Proxy
 * Standardized proxy matching the persons/person.php pattern
 */

$action = $_POST['action'] ?? $_GET['action'] ?? 'none';
$id = $_POST['id'] ?? 'none';

$target = __DIR__ . "/../../api/cari/cari.php";

if (!file_exists($target)) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "API target not found"]);
    exit;
}

// Set correct working directory and include target
try {
    chdir(dirname($target));
    require $target; 
} catch (Throwable $e) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Proxy Error: " . $e->getMessage()]);
}
