<?php
// mobile/api/projects/projects.php -> root/api/projects/projects.php
$target = __DIR__ . "/../../../api/projects/projects.php";

if (file_exists($target)) {
    chdir(dirname($target));
    require_once basename($target);
} else {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "API target not found"]);
}
