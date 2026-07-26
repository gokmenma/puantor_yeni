<?php
define('ROOT', dirname(__DIR__));
require_once ROOT . '/App/Helper/session_security.php';
require_once ROOT . '/Model/UserModel.php';
puantorStartSecureSession();

if (!empty($_SESSION['user']->id)) {
    try {
        (new UserModel())->setMobileToken((int) $_SESSION['user']->id, bin2hex(random_bytes(32)));
    } catch (Throwable $e) {
        system_log_exception($e, ['operation' => 'mobile_logout_token_rotation']);
    }
}

$_SESSION = [];
puantorExpireCookie('remember_me_mobile');
puantorExpireCookie('remember_me');
puantorExpireCookie(session_name());
session_destroy();
header("Location: sign-in.php");
exit();
