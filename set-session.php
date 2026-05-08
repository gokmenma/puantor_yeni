<?php
require_once "App/Helper/security.php";

use App\Helper\Security;

session_start();
$page = $_GET["p"];
$user_id = $_SESSION['user']->id;
$user_role = $_SESSION['user']->user_roles;
//get ile gelen firm_id değeri sessiona atanır
$firm_id = Security::decrypt($_GET['firm_id']);
if ($firm_id == null) {
    include_once "pages/unauthorized.php";
}
$_SESSION['firm_id'] = $firm_id;

header("Location: index.php?p=$page");
