<?php
// mobile/api/persons/person.php -> root/api/persons/person.php
file_put_contents(__DIR__ . "/mobile_api.log", date("Y-m-d H:i:s") . " - Mobile API Hit\n", FILE_APPEND);

$target = __DIR__ . "/../../../api/persons/person.php";

if (file_exists($target)) {
    chdir(dirname($target));
    require_once basename($target);
} else {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "API target not found"]);
}
