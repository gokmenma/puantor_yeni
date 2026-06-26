<?php

ob_start();

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Wages.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/date.php';

use App\Helper\Helper;
use App\Helper\Date;

ob_end_clean();
header('Content-Type: application/json');

if (!isset($_SESSION['firm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit;
}

$firm_id   = $_SESSION['firm_id'];
$action    = $_POST['action'] ?? '';
$personObj = new Persons();
$wagesObj  = new Wages();

if ($action === 'getPersons') {
    $search   = trim($_POST['search'] ?? '');
    $page     = max(1, (int)($_POST['page'] ?? 1));
    $per_page = max(1, (int)($_POST['per_page'] ?? 100));
    $offset   = ($page - 1) * $per_page;

    $where  = "firm_id = ? AND deleted_at IS NULL";
    $params = [$firm_id];

    if ($search !== '') {
        $where   .= " AND (full_name LIKE ? OR job LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $countStmt = $personObj->getDb()->prepare("SELECT COUNT(*) FROM persons WHERE $where");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $stmt = $personObj->getDb()->prepare(
        "SELECT id, full_name, job, job_start_date, daily_wages FROM persons
         WHERE $where ORDER BY full_name ASC LIMIT $per_page OFFSET $offset"
    );
    $stmt->execute($params);
    $persons = $stmt->fetchAll(PDO::FETCH_OBJ);

    $data = [];
    foreach ($persons as $p) {
        $activeWage = $wagesObj->getCurrentWage($p->id);
        $current    = $activeWage ? (float)$activeWage->amount : (float)($p->daily_wages ?? 0);
        $data[] = [
            'id'               => (int)$p->id,
            'full_name'        => $p->full_name,
            'job'              => $p->job ?? '-',
            'job_start_date'   => $p->job_start_date ?? '-',
            'current_wage'     => $current,
            'current_wage_fmt' => Helper::formattedMoney($current),
        ];
    }

    echo json_encode(['status' => 'success', 'data' => $data, 'total' => $total, 'page' => $page, 'per_page' => $per_page]);
    exit;
}

if ($action === 'applyRaise') {
    $person_ids  = $_POST['person_ids'] ?? [];
    $raise_pct   = (float)str_replace(',', '.', $_POST['raise_percent'] ?? 0);
    $start_date  = Date::Ymd($_POST['start_date'] ?? '', 'Ymd');
    $end_date    = Date::Ymd($_POST['end_date']   ?? '', 'Ymd');
    $description = trim($_POST['description'] ?? '');
    $wage_name   = '%' . $raise_pct . ' Zam' . ($description ? ' - ' . $description : '');

    if (empty($person_ids) || $raise_pct <= 0 || !$start_date || !$end_date) {
        echo json_encode(['status' => 'error', 'message' => 'Eksik veya hatalı parametre.']);
        exit;
    }

    $records     = [];
    $error_count = 0;

    foreach ($person_ids as $pid) {
        $pid    = (int)$pid;
        $person = $personObj->find($pid);
        if (!$person || $person->firm_id != $firm_id) {
            $error_count++;
            continue;
        }
        $activeWage = $wagesObj->getCurrentWage($pid);
        $current    = $activeWage ? (float)$activeWage->amount : (float)($person->daily_wages ?? 0);
        $new_amount = round($current * (1 + $raise_pct / 100), 2);

        $records[] = [
            'person_id'   => $pid,
            'wage_name'   => $wage_name,
            'start_date'  => $start_date,
            'end_date'    => $end_date,
            'amount'      => $new_amount,
            'description' => $description,
        ];
    }

    $success_count = empty($records) ? 0 : $wagesObj->bulkInsertWages($records);

    echo json_encode([
        'status'        => $success_count > 0 ? 'success' : 'error',
        'message'       => $success_count > 0
            ? "$success_count personele %$raise_pct zam uygulandı." . ($error_count > 0 ? " ($error_count hata)" : "")
            : 'Güncelleme yapılamadı.',
        'success_count' => $success_count,
        'error_count'   => $error_count,
    ]);
    exit;
}

if ($action === 'setFixed') {
    $person_ids  = $_POST['person_ids'] ?? [];
    $amount      = (float)Helper::formattedMoneyToNumber($_POST['amount'] ?? 0);
    $start_date  = Date::Ymd($_POST['start_date'] ?? '', 'Ymd');
    $end_date    = Date::Ymd($_POST['end_date']   ?? '', 'Ymd');
    $description = trim($_POST['description'] ?? '');
    $wage_name   = 'Ücret Güncellemesi' . ($description ? ' - ' . $description : '');

    if (empty($person_ids) || $amount <= 0 || !$start_date || !$end_date) {
        echo json_encode(['status' => 'error', 'message' => 'Eksik veya hatalı parametre.']);
        exit;
    }

    $records     = [];
    $error_count = 0;

    foreach ($person_ids as $pid) {
        $pid    = (int)$pid;
        $person = $personObj->find($pid);
        if (!$person || $person->firm_id != $firm_id) {
            $error_count++;
            continue;
        }
        $records[] = [
            'person_id'   => $pid,
            'wage_name'   => $wage_name,
            'start_date'  => $start_date,
            'end_date'    => $end_date,
            'amount'      => $amount,
            'description' => $description,
        ];
    }

    $success_count = empty($records) ? 0 : $wagesObj->bulkInsertWages($records);

    echo json_encode([
        'status'        => $success_count > 0 ? 'success' : 'error',
        'message'       => $success_count > 0
            ? "$success_count personelin ücreti " . Helper::formattedMoney($amount) . " olarak güncellendi." . ($error_count > 0 ? " ($error_count hata)" : "")
            : 'Güncelleme yapılamadı.',
        'success_count' => $success_count,
        'error_count'   => $error_count,
    ]);
    exit;
}

if ($action === 'setIndividual') {
    $wages       = $_POST['wages'] ?? [];
    $start_date  = Date::Ymd($_POST['start_date'] ?? '', 'Ymd');
    $end_date    = Date::Ymd($_POST['end_date']   ?? '', 'Ymd');
    $description = trim($_POST['description'] ?? '');
    $wage_name   = 'Bireysel Ücret' . ($description ? ' - ' . $description : '');

    if (empty($wages) || !$start_date || !$end_date) {
        echo json_encode(['status' => 'error', 'message' => 'Eksik veya hatalı parametre.']);
        exit;
    }

    $records     = [];
    $error_count = 0;

    foreach ($wages as $pid => $raw_amount) {
        $pid    = (int)$pid;
        $amount = (float)Helper::formattedMoneyToNumber($raw_amount);
        if ($amount <= 0) {
            continue;
        }
        $person = $personObj->find($pid);
        if (!$person || $person->firm_id != $firm_id) {
            $error_count++;
            continue;
        }
        $records[] = [
            'person_id'   => $pid,
            'wage_name'   => $wage_name,
            'start_date'  => $start_date,
            'end_date'    => $end_date,
            'amount'      => $amount,
            'description' => $description,
        ];
    }

    $success_count = empty($records) ? 0 : $wagesObj->bulkInsertWages($records);

    echo json_encode([
        'status'        => $success_count > 0 ? 'success' : 'error',
        'message'       => $success_count > 0
            ? "$success_count personelin ücreti bireysel olarak güncellendi." . ($error_count > 0 ? " ($error_count hata)" : "")
            : 'Güncelleme yapılamadı.',
        'success_count' => $success_count,
        'error_count'   => $error_count,
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem.']);
