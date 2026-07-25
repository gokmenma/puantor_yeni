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
$req_month = (int)($_GET['month'] ?? date('m'));
$req_year = (int)($_GET['year'] ?? date('Y'));
$preserve_requested_period = (int)($_GET['preserve_period'] ?? 0) === 1;

if (!$person_id) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz personel.']);
    exit;
}

$Bordro = new Bordro();
$Persons = new Persons();
require_once __DIR__ . '/../../Model/Puantaj.php';
$PuantajModel = new Puantaj();

$person = $Persons->find($person_id);
$firm_id = $person ? (int)$person->firm_id : 0;

// Görünürlük kontrolü (Personeller Görsün)
$is_visible = $Bordro->getPeriodVisibility($firm_id, $req_year, $req_month);
$month = $req_month;
$year = $req_year;
$notice = null;
$latest = null;

if ($is_visible !== 1) {
    if ($preserve_requested_period) {
        // Takvim oklarıyla seçilen ay ekranda kalır; kapalı döneme ait veri dönülmez.
        $notice = "Seçilen dönemin ({$req_month}/{$req_year}) bordrosu henüz personellere açık değildir.";
    } else {
        // İlk açılışta en son görünür dönemi göster.
        $latest = $Bordro->getLatestVisiblePeriod($firm_id);
        if ($latest) {
            $year = (int)$latest['yil'];
            $month = (int)$latest['ay'];
            $notice = "Seçilen dönemin ({$req_month}/{$req_year}) bordrosu henüz personellere açık değildir. En son onaylı açık dönem ({$month}/{$year}) gösterilmektedir.";
        } else {
            // Hiç açık dönem yok
            $notice = "Bordro henüz personellerin erişimine açılmamıştır.";
        }
    }
}

$balance = $Bordro->sumAllIncomeExpenseFormatted($person_id);
$recent_work = $Bordro->getPersonWorkTransactions($person_id);

if ($is_visible !== 1 && !$latest) {
    // Hiç açık dönem yoksa aylık verileri boş dön
    $financial_data = [];
    $attendance_data = [];
    $total_hours = 0;
    $overtime = 0;
    $total_work_days = 0;
    $monthly_advance = 0;
} else {
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
    $total_work_days = 0;
    foreach ($attendance_data as $record) {
        if (isset($record->saat)) {
            $total_hours += (float)$record->saat;
        }
        if (isset($record->attendance_type)) {
            if ($record->attendance_type !== 'Ücretsiz') {
                $total_work_days++;
            }
            if ($record->attendance_type === 'Fazla Çalışma') {
                $overtime += (float)($record->EklenecekSaat ?? 0);
            }
        }
    }

    // Calculate monthly financial totals
    $monthly_advance = 0;
    foreach ($financial_data as $item) {
        if (in_array($item->kategori, [2, 7])) {
            $monthly_advance += (float)$item->tutar;
        }
    }
}

$kalan_izin = $person->remaining_leave ?? 0;

if (isset($balance)) {
    $balance->total_hours = $total_hours;
    $balance->total_work_days = $total_work_days;
    $balance->overtime = $overtime;
    $balance->kalan_izin = $kalan_izin;
    $balance->advance = $monthly_advance;
}

$merged_data = array_merge($financial_data, $attendance_data);

echo json_encode([
    'status' => 'success',
    'summary' => $balance,
    'recent' => array_slice($recent_work, 0, 10),
    'monthly' => $merged_data,
    'current_month' => (int)$month,
    'current_year' => (int)$year,
    'requested_month' => (int)$req_month,
    'requested_year' => (int)$req_year,
    'is_open' => ($is_visible === 1),
    'notice' => $notice
]);
