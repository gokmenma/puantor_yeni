<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Bu script sadece CLI üzerinden çalıştırılabilir.');
}

define('ROOT', dirname(__DIR__));
require_once ROOT . '/Database/require.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;
use Database\Db;

$dbObj = new Db();
$db = $dbObj->connect();

$stmt = $db->query("SELECT id, phone, email FROM persons WHERE deleted_at IS NULL");
$persons = $stmt->fetchAll(PDO::FETCH_OBJ);

$updated = 0;
$skipped = 0;

foreach ($persons as $p) {
    $fields = [];
    $params = [];

    $phone = $p->phone ?? '';
    if (!empty($phone)) {
        $decrypted = Security::decrypt($phone);
        if ($decrypted === false) {
            $fields[] = "phone = ?";
            $params[] = Security::encrypt($phone);
        }
    }

    $email = $p->email ?? '';
    if (!empty($email)) {
        $decrypted = Security::decrypt($email);
        if ($decrypted === false) {
            $fields[] = "email = ?";
            $params[] = Security::encrypt($email);
        }
    }

    if (empty($fields)) {
        $skipped++;
        continue;
    }

    $params[] = $p->id;
    $upd = $db->prepare("UPDATE persons SET " . implode(', ', $fields) . " WHERE id = ?");
    $upd->execute($params);
    $updated++;
    echo "Güncellendi: ID={$p->id}\n";
}

echo "\nTamamlandı. Güncellenen: $updated, Atlanan (zaten şifreli): $skipped\n";
