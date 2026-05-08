<?php
// mobile/api/il-ilce.php -> root/api/il-ilce.php
$target = __DIR__ . "/../../api/il-ilce.php";

if (file_exists($target)) {
    chdir(dirname($target));
    require_once basename($target);
} else {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "API target not found"]);
}
