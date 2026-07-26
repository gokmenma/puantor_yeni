<?php
require_once dirname(__DIR__) . '/App/bootstrap.php';

$_envFile = dirname(__DIR__) . '/.env';
if (file_exists($_envFile)) {
    foreach (file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $_line) {
        if (strpos(trim($_line), '#') === 0) continue;
        if (strpos($_line, '=') !== false) {
            [$_k, $_v] = explode('=', $_line, 2);
            putenv(trim($_k) . '=' . trim($_v));
        }
    }
}
unset($_envFile, $_line, $_k, $_v);

require_once __DIR__ . "/db.php";
require_once dirname(__DIR__) . '/App/Helper/session_security.php';

use Database\Db;

$dbInstance = new Db(); // Db sınıfının bir örneğini oluşturuyoruz.
$db = $dbInstance->connect(); // Veritabanı bağlantısını alıyoruz.
puantorStartSecureSession();
puantorEnforceSessionTimeout();
// $user_id = $_SESSION['user_id']; // Session'dan user_id'yi alıyoruz.
