<?php
require_once __DIR__ . "/db.php";

use Database\Db;

$dbInstance = new Db(); // Db sınıfının bir örneğini oluşturuyoruz.
$db = $dbInstance->connect(); // Veritabanı bağlantısını alıyoruz.
if (session_status() === PHP_SESSION_NONE) {
    session_start(); // Session'ı başlatıyoruz.
}
// $user_id = $_SESSION['user_id']; // Session'dan user_id'yi alıyoruz.