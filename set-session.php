<?php

define('ROOT', __DIR__);

require_once ROOT . '/App/bootstrap.php';
require_once ROOT . '/App/Helper/session_security.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

puantorStartSecureSession();

if (empty($_SESSION['user'])) {
    header('Location: sign-in.php');
    exit;
}

$page = (string) ($_GET['p'] ?? 'home');
$encryptedFirmId = (string) ($_GET['firm_id'] ?? '');
$firmId = Security::decrypt($encryptedFirmId);

if ($firmId === false || !ctype_digit((string) $firmId) || (int) $firmId <= 0) {
    http_response_code(403);
    require ROOT . '/pages/unauthorized.php';
    exit;
}

$_SESSION['firm_id'] = (int) $firmId;

header('Location: index.php?' . http_build_query(['p' => $page]));
exit;
