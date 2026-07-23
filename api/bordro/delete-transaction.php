<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

header('Content-Type: application/json; charset=UTF-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Geçersiz istek.');
    }

    $Auths = new Auths();
    $Auths->checkFirmReturn();

    $id = (int) Security::safeDecrypt($_POST['id'] ?? '');
    $source = (string) ($_POST['source'] ?? '');
    $month = (int) ($_POST['month'] ?? 0);
    $year = (int) ($_POST['year'] ?? 0);
    $firmId = (int) ($_SESSION['firm_id'] ?? 0);

    if ($id <= 0 || !in_array($source, ['maas_gelir_kesinti', 'case_transactions'], true)) {
        throw new Exception('Silinecek hareket bulunamadı.');
    }
    if ($month < 1 || $month > 12 || $year < 2000 || $firmId <= 0) {
        throw new Exception('Geçersiz bordro dönemi.');
    }

    if ($source === 'maas_gelir_kesinti') {
        $recordSql = $db->prepare(
            'SELECT m.id, m.kategori, m.turu, m.tutar, m.person_id
               FROM maas_gelir_kesinti m
               INNER JOIN persons p ON p.id = m.person_id
              WHERE m.id = :id
                AND m.ay = :month
                AND m.yil = :year
                AND p.firm_id = :firm_id
              LIMIT 1'
        );
    } else {
        $recordSql = $db->prepare(
            'SELECT c.id, c.users_type_id AS kategori, "Personel Ödemesi" AS turu, c.amount AS tutar, c.person_id
               FROM case_transactions c
               INNER JOIN persons p ON p.id = c.person_id
              WHERE c.id = :id
                AND MONTH(c.date) = :month
                AND YEAR(c.date) = :year
                AND p.firm_id = :firm_id
              LIMIT 1'
        );
    }

    $recordSql->execute([
        ':id' => $id,
        ':month' => $month,
        ':year' => $year,
        ':firm_id' => $firmId
    ]);
    $record = $recordSql->fetch(PDO::FETCH_OBJ);

    if (!$record) {
        throw new Exception('Hareket bulunamadı veya bu firmaya ait değil.');
    }

    $category = (int) $record->kategori;
    $typeName = trim((string) $record->turu);
    $amount = (float) $record->tutar;

    if (in_array($category, [14, 16, 17], true) || mb_stripos($typeName, 'İcra') !== false) {
        throw new Exception('Sistem tarafından oluşturulan bordro kalemleri buradan silinemez.');
    }

    if ($category === 7) {
        $Auths->hasPermissionReturn('delete_staff_payment');
    } else {
        $Auths->hasPermissionReturn('delete_income_expense');
    }

    $transactionKind = 'income';
    if (in_array($category, [7, 15], true)) {
        $transactionKind = 'expense';
    } else {
        $typeSql = $db->prepare('SELECT type_id FROM defines WHERE id = ? LIMIT 1');
        $typeSql->execute([$category]);
        if ((int) $typeSql->fetchColumn() === 2) {
            $transactionKind = 'expense';
        }
    }

    $db->beginTransaction();
    $deleteSql = $db->prepare("DELETE FROM {$source} WHERE id = ?");
    $deleteSql->execute([$id]);

    if ($deleteSql->rowCount() !== 1) {
        throw new Exception('Hareket silinemedi.');
    }

    $db->commit();

    ActivityLogModel::log(
        'payroll',
        'transaction_delete',
        sprintf(
            '%s bordro hareketi silindi. Personel: %d, dönem: %04d-%02d, kayıt: %d',
            $typeName !== '' ? $typeName : 'Gelir/kesinti',
            (int) $record->person_id,
            $year,
            $month,
            $id
        )
    );

    echo json_encode([
        'status' => 'success',
        'message' => 'Bordro hareketi silindi.',
        'transaction_kind' => $transactionKind,
        'amount' => $amount
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
