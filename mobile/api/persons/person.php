<?php
// mobile/api/persons/person.php -> root/api/persons/person.php
// Enhanced Proxy with Logging

$action = $_POST['action'] ?? 'none';
$id = $_POST['id'] ?? 'none';
$post_data = json_encode($_POST);
$log_entry = date("Y-m-d H:i:s") . " - Mobile API Hit - Action: $action - ID: $id - POST: $post_data\n";
@file_put_contents(__DIR__ . "/mobile_api.log", $log_entry, FILE_APPEND);

$target = __DIR__ . "/../../../api/persons/person.php";

if (!file_exists($target)) {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "API target not found"]);
    exit;
}

// Prepare to capture output from the required file
ob_start();

register_shutdown_function(function() {
    $output = ob_get_contents();
    @file_put_contents(__DIR__ . "/mobile_api.log", date("Y-m-d H:i:s") . " - Proxy Shutdown - Response length: " . strlen($output) . "\n", FILE_APPEND);
    if (strlen($output) > 0 && strlen($output) < 1000) {
        @file_put_contents(__DIR__ . "/mobile_api.log", date("Y-m-d H:i:s") . " - Proxy Shutdown - Response content: " . $output . "\n", FILE_APPEND);
    }
});

try {
    chdir(dirname($target));
    require $target; 
} catch (Throwable $e) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "Proxy Error: " . $e->getMessage()]);
}
