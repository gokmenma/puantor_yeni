<?php
// Puantor Mobil - Toplu Puantaj Kaydetme API
header('Content-Type: application/json');

use App\Helper\Security;
use App\Helper\Date;

try {
    define("ROOT", dirname(dirname(dirname(dirname(__DIR__)))));
    require_once ROOT . "/Database/require.php";
    require_once ROOT . "/Model/Puantaj.php";
    require_once ROOT . "/Model/Persons.php";
    require_once ROOT . "/Model/Wages.php";
    require_once ROOT . "/Model/SettingsModel.php";
    require_once ROOT . "/App/Helper/security.php";
    require_once ROOT . "/App/Helper/date.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user'])) {
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
        exit();
    }

    $action = $_POST['action'] ?? '';
    if ($action !== 'savePuantaj') {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);
        exit();
    }

    $json_data = json_decode($_POST['data'] ?? '[]', true);
    if (empty($json_data)) {
        echo json_encode(['status' => 'info', 'message' => 'Kaydedilecek veri bulunamadı']);
        exit();
    }

    $puantajObj = new Puantaj();
    $personModel = new Persons();
    $wagesModel = new Wages();
    $settingsModel = new SettingsModel();

    $work_hour = $settingsModel->getSettings("work_hour")->set_value ?? 8;
    $work_hour = floatval(str_replace(',', '.', $work_hour));
    if ($work_hour <= 0) $work_hour = 8;

    $save_count = 0;
    $firm_id = $_SESSION['firm_id'] ?? 0;

    foreach ($json_data as $person_key => $person_item) {
        $person_id = Security::decrypt($person_key);
        if (!$person_id) continue;

        $person_info = $personModel->find($person_id);
        $daily_wage_obj = $personModel->getDailyWages($person_id);
        $effective_daily = (($person_info->wage_type ?? 0) == 1) ? (floatval($daily_wage_obj->daily_wages ?? 0) / 30) : floatval($daily_wage_obj->daily_wages ?? 0);
        $ucret_base = $effective_daily / $work_hour;

        foreach ($person_item as $date => $puantaj_item) {
            $type_id = intval($puantaj_item['puantajId'] ?? 0);
            $project_id = intval($puantaj_item['project_id'] ?? 0);

            if (!$type_id) continue;

            $id = $puantajObj->getPuantajId($person_id, $date, $project_id);

            $defined_wage = $wagesModel->getWageByPersonIdAndDate($person_id, $date)->amount ?? 0;
            if ($defined_wage > 0) {
                $effective_defined = (($person_info->wage_type ?? 0) == 1) ? (floatval($defined_wage) / 30) : floatval($defined_wage);
                $hourly_wage = $effective_defined / $work_hour;
            } else {
                $hourly_wage = $ucret_base;
            }

            $puantaj_turu = $puantajObj->getPuantajTuruById($type_id);
            if ($puantaj_turu->Turu != 'Saatlik') {
                $saat = $puantajObj->getPuantajSaatiByfirm($type_id);
            } else {
                $saat = $puantaj_turu->PuantajSaati;
            }
            $tutar = floatval($saat) * $hourly_wage;

            $data = [
                'id' => $id,
                'company_id' => $firm_id,
                'person' => $person_id,
                'project_id' => $project_id,
                'puantaj_id' => $type_id,
                'gun' => $date,
                'saat' => $saat,
                'tutar' => $tutar,
                "description" => "Mobil Toplu Giriş",
                "updated_at" => date('Y-m-d H:i:s')
            ];

            $puantajObj->saveWithAttr($data);
            $save_count++;
        }
    }

    echo json_encode(['status' => 'success', 'message' => "$save_count adet puantaj başarıyla kaydedildi"]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
