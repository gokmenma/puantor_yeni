<?php
$log = "--- Request at " . date('Y-m-d H:i:s') . " ---\n";
$log .= "POST DATA:\n" . print_r($_POST, true) . "\n";
$log .= "SESSION DATA:\n" . print_r($_SESSION, true) . "\n";
file_put_contents(__DIR__ . "/../../debug_transaction_calls.txt", $log, FILE_APPEND);
