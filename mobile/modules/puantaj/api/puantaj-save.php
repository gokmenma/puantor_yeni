<?php
// Puantor Mobil - Hızlı Puantaj Kaydetme API
header('Content-Type: application/json');

try {
    define("ROOT", dirname(dirname(dirname(dirname(__DIR__)))));
    require_once ROOT . "/Database/require.php";
    require_once ROOT . "/Model/Puantaj.php";
    require_once ROOT . "/Model/Persons.php";
    require_once ROOT . "/Model/Wages.php";
    require_once ROOT . "/Model/SettingsModel.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user'])) {
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
        exit();
    }

    $person_id = intval($_POST['person_id'] ?? 0);
    $date = $_POST['date'] ?? '';
    $date = str_replace('-', '', $date); // Strip hyphens to match desktop database format (e.g. 20260506)
    $type_id = intval($_POST['type_id'] ?? 0); // 1: G, 2: X, 3: İ, 4: R

    if (!$person_id || !$date || !$type_id) {
        echo json_encode(['status' => 'error', 'message' => 'Eksik parametreler']);
        exit();
    }

    $puantajObj = new Puantaj();
    $personModel = new Persons();
    $wagesModel = new Wages();
    $settingsModel = new SettingsModel();

    $work_hour = $settingsModel->getSettings("work_hour")->set_value ?? 8;
    $work_hour = floatval(str_replace(',', '.', $work_hour));
    if ($work_hour <= 0) $work_hour = 8;

    $daily_wage_obj = $personModel->getDailyWages($person_id);
    $ucret = floatval(($daily_wage_obj->daily_wages ?? 0)) / $work_hour;

    $id = $puantajObj->getPuantajId($person_id, $date);

    $defined_wage = $wagesModel->getWageByPersonIdAndDate($person_id, $date)->amount ?? 0;
    $daily_wages = (($defined_wage > 0) ? ($defined_wage / $work_hour) : $ucret);

    $puantaj_turu = $puantajObj->getPuantajTuruById($type_id);
    if ($puantaj_turu->Turu != 'Saatlik') {
        $saat = $puantajObj->getPuantajSaatiByfirm($type_id);
        $tutar = floatval($saat) * $daily_wages;
    } else {
        $saat = $puantaj_turu->PuantajSaati;
        $tutar = floatval($saat) * $daily_wages;
    }

    // Varsayılan Proje tayini
    $project_id = intval($_POST['project_id'] ?? 0);
    if (!$project_id) {
        $project_id = $puantajObj->getPuantajProjectId($person_id, $date);
    }
    if (!$project_id) {
        $project_id = intval($personModel->getPersonByField($person_id, 'project_id') ?? 0);
    }

    $firm_id = $_SESSION['firm_id'] ?? 0;

    $data = [
        'id' => $id,
        'company_id' => $firm_id,
        'person' => $person_id,
        'project_id' => $project_id,
        'puantaj_id' => $type_id,
        'gun' => $date,
        'saat' => $saat,
        'tutar' => $tutar,
        "description" => "Mobil Hızlı Giriş",
        "updated_at" => date('Y-m-d H:i:s')
    ];

    $puantajObj->saveWithAttr($data);
    echo json_encode(['status' => 'success', 'message' => 'Puantaj başarıyla kaydedildi']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
