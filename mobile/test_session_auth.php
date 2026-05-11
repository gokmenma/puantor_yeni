<?php
session_start();
header('Content-Type: application/json');
echo json_encode([
    "session_firm_id" => $_SESSION['firm_id'] ?? "NOT SET",
    "user_firm_id" => isset($_SESSION['user']) ? $_SESSION['user']->firm_id : "NO USER",
    "is_main_user" => isset($_SESSION['user']) ? ($_SESSION['user']->is_main_user ?? "NOT SET") : "NO USER",
    "match" => (isset($_SESSION['user']) && isset($_SESSION['firm_id']) && $_SESSION['user']->firm_id == $_SESSION['firm_id'])
], JSON_PRETTY_PRINT);
