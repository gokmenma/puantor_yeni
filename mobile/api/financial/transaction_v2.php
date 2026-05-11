<?php
session_start();
 = __DIR__ . '/../../../api/financial/transaction.php';
if (file_exists($target)) {
    require_once $target;
} else {
    echo json_encode(['status' => 'error', 'message' => 'API target not found']);
}
