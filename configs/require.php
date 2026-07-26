<?php
require_once __DIR__ . '/../App/bootstrap.php';
require_once __DIR__ . '/../App/Helper/session_security.php';
puantorStartSecureSession();

require_once 'configs/connect.php';
require_once 'configs/functions.php';


$user_id = isset($_SESSION['user']->id) ? $_SESSION['user']->id : 0;
$user_name = isset($_SESSION['user']->full_name) ? $_SESSION['user']->full_name : '';
