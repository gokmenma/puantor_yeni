<?php
session_start();
session_destroy();
if (isset($_COOKIE['remember_me_mobile'])) {
    setcookie('remember_me_mobile', '', time() - 3600, '/');
}
if (isset($_COOKIE['remember_me'])) {
    setcookie('remember_me', '', time() - 3600, '/');
}
header("Location: sign-in.php");
exit();
