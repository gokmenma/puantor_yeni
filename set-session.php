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

$encryptedFirmId = (string) ($_GET['firm_id'] ?? '');
$firmId = Security::decrypt($encryptedFirmId);

if ($firmId === false || !ctype_digit((string) $firmId) || (int) $firmId <= 0) {
    http_response_code(403);
    require ROOT . '/pages/unauthorized.php';
    exit;
}

$_SESSION['firm_id'] = (int) $firmId;

$redirectParams = [];
foreach ($_GET as $k => $v) {
    if ($k === 'firm_id' || is_array($v)) {
        continue;
    }
    $redirectParams[$k] = (string) $v;
}
if (empty($redirectParams['p'])) {
    $redirectParams['p'] = 'home';
}

$page = $redirectParams['p'] ?? 'home';
unset($redirectParams['p']);
$path = \App\Routing\Router::pathForPage($page, $redirectParams);
if ($path !== null) {
    $queryString = !empty($redirectParams) ? '?' . http_build_query($redirectParams) : '';
    header('Location: ' . $path . $queryString);
    exit;
}

header('Location: index.php?' . http_build_query(array_merge(['p' => $page], $redirectParams)));
exit;

