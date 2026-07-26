<?php
require_once dirname(__DIR__) . '/App/bootstrap.php';

if (php_sapi_name() !== 'cli') {
    die("Bu betik yalnızca komut satırından (CLI) çalıştırılabilir.\n");
}

if (!defined('ROOT')) define('ROOT', dirname(__DIR__));

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/ActivityLogModel.php';

use Database\Db;

$dbObj = new Db();
$db    = $dbObj->connect();

$SAKLA_YIL = 10;
$esik_tarih = date('d.m.Y', strtotime("-{$SAKLA_YIL} years"));
$esik_tarih_Y = date('Y-m-d', strtotime("-{$SAKLA_YIL} years"));

$stmt = $db->prepare("
    SELECT id, full_name, firm_id
    FROM persons
    WHERE deleted_at IS NOT NULL
      AND (
        STR_TO_DATE(job_end_date, '%d.%m.%Y') <= ?
        OR (job_end_date IS NULL AND deleted_at <= ?)
      )
      AND (kimlik_no IS NOT NULL OR phone IS NOT NULL OR email IS NOT NULL OR iban_number IS NOT NULL OR address IS NOT NULL)
");
$stmt->execute([$esik_tarih_Y, $esik_tarih_Y]);
$adaylar = $stmt->fetchAll(PDO::FETCH_OBJ);

if (empty($adaylar)) {
    echo "Anonimleştirilecek kayıt bulunamadı.\n";
    exit(0);
}

$upd = $db->prepare("
    UPDATE persons SET
        kimlik_no    = NULL,
        sigorta_no   = NULL,
        phone        = NULL,
        email        = NULL,
        iban_number  = NULL,
        address      = NULL,
        birth_date   = NULL,
        password     = NULL,
        session_token = NULL
    WHERE id = ?
");

$sayac = 0;
foreach ($adaylar as $p) {
    $upd->execute([$p->id]);
    $sayac++;
    echo "Anonimleştirildi: ID={$p->id} - {$p->full_name}\n";

    $_SESSION['firm_id'] = $p->firm_id;
    $_SESSION['user'] = (object)['id' => 0];
    ActivityLogModel::log('kvkk', 'anonymize', "KVKK saklama süresi doldu, kişisel veriler anonimleştirildi: ID={$p->id}");
}

echo "\nTamamlandı. Anonimleştirilen kayıt sayısı: {$sayac}\n";
