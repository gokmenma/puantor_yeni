<?php
require_once dirname(__DIR__) . '/App/bootstrap.php';

require_once __DIR__ . "/db.php";
require_once dirname(__DIR__) . '/App/Helper/session_security.php';

use Database\Db;

$dbInstance = new Db(); // Db sınıfının bir örneğini oluşturuyoruz.
$db = $dbInstance->connect(); // Veritabanı bağlantısını alıyoruz.
puantorStartSecureSession();
puantorEnforceSessionTimeout();
// $user_id = $_SESSION['user_id']; // Session'dan user_id'yi alıyoruz.
