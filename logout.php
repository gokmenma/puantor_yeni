<?php 
session_start();
require_once "Model/UserModel.php";

$Users = new UserModel();

$log_id= $_SESSION["log_id"];
$Users->logoutLog($log_id);
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}
session_destroy();
header("Location: sign-in.php");

?>