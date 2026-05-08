<?php 
session_start();

require_once 'configs/connect.php';
require_once 'configs/functions.php';


$user_id = isset($_SESSION['user']->id) ? $_SESSION['user']->id : 0;
$user_name = isset($_SESSION['user']->full_name) ? $_SESSION['user']->full_name : '';
