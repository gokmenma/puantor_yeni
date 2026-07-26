<?php
require_once dirname(__DIR__) . '/App/bootstrap.php';

session_start();
header('Content-Type: application/json');

if (isset($_GET['theme'])) {
    $theme = $_GET['theme'] === 'dark' ? 'dark' : 'light';
    $_SESSION['theme'] = $theme;
    echo json_encode(['status' => 'success', 'theme' => $theme]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz parametre']);
}
