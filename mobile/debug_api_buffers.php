<?php
// Exact simulation of execution environment to catch output buffering ghosts
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_POST = ["action" => "getSubTypes", "type" => "2"];
session_start();
$_SESSION['user'] = (object)['id' => 1, 'firm_id' => 1, 'user_roles' => 1, 'is_main_user' => 1];
$_SESSION['firm_id'] = 1;

// This represents calling the wrapper endpoint mobile/api/financial/transaction.php
$wrapper_target = __DIR__ . "/api/financial/transaction.php";

// Capture all output
ob_start();
include($wrapper_target);
$output = ob_get_clean();

ob_end_clean(); // Clean the very first outer mock buffer

echo "OUTPUT LEN: " . strlen($output) . "\n";
echo "OUTPUT HEX: " . bin2hex(substr($output, 0, 20)) . "...\n";
echo "FULL OUTPUT RAW:\n";
echo $output;
echo "\n---END---\n";

// Try to parse JSON
$json = json_decode($output);
if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON ERROR: " . json_last_error_msg() . "\n";
} else {
    echo "JSON OK!\n";
}
