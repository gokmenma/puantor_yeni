<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database/require.php';
require_once __DIR__ . '/../../Model/Persons.php';
require_once __DIR__ . '/../../Model/Bordro.php';
require_once __DIR__ . '/../../App/Helper/helper.php';

use App\Helper\Helper;

$person_id = $_GET['person_id'] ?? 0;
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

if (!$person_id) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz personel.']);
    exit;
}

$Bordro = new Bordro();
$Persons = new Persons();
require_once __DIR__ . '/../../Model/Puantaj.php';
$PuantajModel = new Puantaj();

$balance = $Bordro->sumAllIncomeExpenseFormatted($person_id);
$recent_work = $Bordro->getPersonWorkTransactions($person_id);

// Get monthly detailed attendance
$start_day = $year . str_pad($month, 2, "0", STR_PAD_LEFT) . "01";
$end_day = $year . str_pad($month, 2, "0", STR_PAD_LEFT) . "31";

// From maas_gelir_kesinti (Advances, extra income etc)
$query = $Bordro->getDb()->prepare("SELECT * FROM maas_gelir_kesinti WHERE person_id = ? AND ay = ? AND yil = ? ORDER BY gun ASC");
$query->execute([$person_id, (int)$month, (int)$year]);
$financial_data = $query->fetchAll(PDO::FETCH_OBJ);

// From puantaj (Working hours)
$attendance_data = $PuantajModel->getPuantajByPersonAndDate($person_id, $start_day, $end_day);

// Calculate monthly totals
$total_hours = 0;
$overtime = 0;
foreach ($attendance_data as $record) {
    if (isset($record->saat)) {
        $total_hours += (float)$record->saat;
    }
}

// Calculate monthly financial totals
$monthly_advance = 0;
foreach ($financial_data as $item) {
    // Categories like 7 (Payment/Advance) or others based on business logic
    if (in_array($item->kategori, [2, 7])) { // 2: Kesinti, 7: Ödeme/Advance
        $monthly_advance += (float)$item->tutar;
    }
}

// Calculate remaining leave
$person = $Persons->find($person_id);
$kalan_izin = $person->remaining_leave ?? 0;

if (isset($balance)) {
    $balance->total_hours = $total_hours;
    $balance->overtime = $overtime;
    $balance->kalan_izin = $kalan_izin;
    $balance->advance = $monthly_advance;
}

// Merge them for the calendar
$merged_data = array_merge($financial_data, $attendance_data);

echo json_encode([
    'status' => 'success',
    'summary' => $balance,
    'recent' => array_slice($recent_work, 0, 10),
    'monthly' => $merged_data,
    'current_month' => (int)$month,
    'current_year' => (int)$year
]);
