<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

// Bu dosya mobile subdomain üzerinde çalışırken ana API'ye erişim sağlar
// mobile/api/financial/transaction.php -> root/api/financial/transaction.php
$target = __DIR__ . "/../../../api/financial/transaction.php";

ob_start();
if (file_exists($target)) {
    require_once $target;
} else {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "API target not found"]);
    exit;
}
// If it doesn't exit, we flush the output here.
if (ob_get_length()) {
    ob_end_flush();
}
