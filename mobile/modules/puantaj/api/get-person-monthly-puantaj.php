<?php
// Puantor Mobil - Personel Aylık Puantaj Verisi API
header('Content-Type: application/json');

try {
    define("ROOT", dirname(dirname(dirname(dirname(__DIR__)))));
    require_once ROOT . "/Database/require.php";
    require_once ROOT . "/Model/Puantaj.php";
    require_once ROOT . "/App/Helper/security.php";

    if (!isset($_SESSION['user'])) {
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
        exit();
    }

    $person_id = intval($_GET['person_id'] ?? 0);
    $year = $_GET['year'] ?? date('Y');
    $month = $_GET['month'] ?? date('m');

    if (!$person_id) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz personel ID']);
        exit;
    }

    $puantajModel = new Puantaj();
    $start_date = "$year-$month-01";
    $end_date = date("Y-m-t", strtotime($start_date));

    // Puantaj türlerini çek
    $puantajTypes = $puantajModel->getAllPuantajTurleri();

    // Personelin o aydaki puantajlarını çek
    $attendance = $puantajModel->getPuantajByPersonAndDate($person_id, $start_date, $end_date);

    $indexedAttendance = [];
    foreach ($attendance as $row) {
        $day = (int)date('j', strtotime($row->gun));
        $type = $puantajTypes[$row->puantaj_id] ?? null;
        $indexedAttendance[$day] = [
            'id' => $row->puantaj_id,
            'code' => $type ? $type->PuantajKod : '?',
            'bg' => $type ? $type->ArkaPlanRengi : '#ccc',
            'color' => $type ? $type->FontRengi : '#fff'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'data' => $indexedAttendance,
        'month' => $month,
        'year' => $year,
        'days_in_month' => (int)date('t', strtotime($start_date))
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
