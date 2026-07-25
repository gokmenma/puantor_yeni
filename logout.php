<?php
require_once __DIR__ . '/App/Helper/session_security.php';
puantorStartSecureSession();
require_once "Model/UserModel.php";

$Users = new UserModel();

$currentUserId = (int) ($_SESSION['user']->id ?? 0);
if ($currentUserId > 0) {
    $Users->setToken($currentUserId, bin2hex(random_bytes(32)));
}
$log_id = (int) ($_SESSION["log_id"] ?? 0);
if ($log_id > 0) $Users->logoutLog($log_id);
if (isset($_COOKIE['remember_me'])) {
    puantorExpireCookie('remember_me');
}
$_SESSION = [];
puantorExpireCookie(session_name());
session_destroy();
header("Location: sign-in.php");

?>
