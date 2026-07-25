<?php

define('ROOT', dirname(__DIR__, 2));
require_once ROOT . '/App/Helper/session_security.php';

puantorStartSecureSession();
puantorEnforceSessionTimeout();

if ((int) ($_SESSION['user']->superadmin ?? 0) !== 1) {
    http_response_code(403);
    exit;
}

$logoPath = ROOT . '/static/png/puantor-email-logo.jpg';
if (!is_file($logoPath)) {
    http_response_code(404);
    exit;
}

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($logoPath));
header('Cache-Control: private, max-age=3600');
header('X-Content-Type-Options: nosniff');
readfile($logoPath);
exit;
