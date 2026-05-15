<?php
/**
 * Mobile Financial API Proxy
 * Avoids WAF/reCAPTCHA issues and path resolution problems
 */
header('Content-Type: application/json');
$func = $_REQUEST['func'] ?? '';

$target = __DIR__ . '/../../api/financial/transaction.php';

if (file_exists($target)) {
    // Ensure ROOT is defined for the target
    if (!defined('ROOT')) {
        define("ROOT", dirname(dirname(__DIR__)));
    }
    require_once $target;
} else {
    echo json_encode(['status' => 'error', 'message' => 'Financial API target not found']);
}
