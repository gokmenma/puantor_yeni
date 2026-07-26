<?php


$host = getenv('DB_HOST') ?: "localhost";
$user = getenv('DB_USER') ?: "root";
$pass = getenv('DB_PASS') ?: "";
$db = getenv('DB_NAME') ?: "mbeyazil_puantoryeni";
// $host = "localhost";
// $user = "root";
// $pass = "";
// $db = "puantor";

try {
    $db = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
     //echo "Connected successfully";
} catch (PDOException $e) {
    system_log_exception($e, ['operation' => 'database_connect']);
}

date_default_timezone_set('Europe/Istanbul');
