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
    require_once ROOT . "/Model/ActivityLogModel.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user'])) {
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
        exit();
    }

    $person_id = intval($_POST['person_id'] ?? 0);
    $date = $_POST['date'] ?? ''; // Gelen format: 2026-05-08
    $type_id = intval($_POST['type_id'] ?? 0);
    $project_id = intval($_POST['project_id'] ?? 0);

    if (!$person_id || !$date || !$type_id) {
        echo json_encode(['status' => 'error', 'message' => 'Eksik parametreler']);
        exit();
    }

    require_once ROOT . "/Model/IzinTalep.php";
    $izinModel = new IzinTalep();
    $onayliIzinler = $izinModel->getOnayliIzinGunleriToplu([$person_id], $date, $date);
    if (!empty($onayliIzinler[$person_id][$date])) {
        echo json_encode(['status' => 'error', 'message' => 'Bu tarihte onaylı bir izin talebi bulunmaktadır. Değişiklik yapılamaz.']);
        exit();
    }

    $puantajObj = new Puantaj();
    $personModel = new Persons();
    $wagesModel = new Wages();
    $settingsModel = new SettingsModel();

    // Veritabanında mevcut kaydı bulmak için merkezi modeli kullan
    $id = $puantajObj->getPuantajId($person_id, $date, $project_id);

    $work_hour = $settingsModel->getSettings("work_hour")->set_value ?? 8;
    $work_hour = floatval(str_replace(',', '.', $work_hour));
    if ($work_hour <= 0) $work_hour = 8;

    $overtime_rate = floatval($settingsModel->getSettings("overtime_rate")->set_value ?? 50);
    if ($overtime_rate < 50) { $overtime_rate = 50; }
    $overtime_multiplier = 1 + ($overtime_rate / 100);

    $daily_wage_obj = $personModel->getDailyWages($person_id);
    $ucret = floatval(($daily_wage_obj->daily_wages ?? 0)) / $work_hour;

    $defined_wage = $wagesModel->getWageByPersonIdAndDate($person_id, $date)->amount ?? 0;
    $daily_wages = (($defined_wage > 0) ? ($defined_wage / $work_hour) : $ucret);

    $puantaj_turu = $puantajObj->getPuantajTuruById($type_id);
    if ($puantaj_turu->Turu != 'Saatlik') {
        $saat = $puantajObj->getPuantajSaatiByfirm($type_id);
    } else {
        $saat = $puantaj_turu->PuantajSaati;
    }
    
    $is_overtime = $puantaj_turu && $puantaj_turu->Turu == 'Fazla Çalışma';
    if ($is_overtime) {
        if (!empty($puantaj_turu->EklenecekSaat)) {
            if (($puantaj_turu->operant ?? '+') == '+') {
                $extra_hours = floatval($puantaj_turu->EklenecekSaat);
            } elseif (($puantaj_turu->operant ?? '+') == '*') {
                $extra_hours = max(0, (floatval($puantaj_turu->EklenecekSaat) - 1) * floatval($work_hour));
            } else {
                $extra_hours = floatval($puantaj_turu->EklenecekSaat);
            }
        } else {
            $extra_hours = max(0, floatval($saat) - floatval($work_hour));
        }
    } else {
        $extra_hours = 0;
    }

    $person_info = $personModel->find($person_id);
    if (($person_info->wage_type ?? 0) == 1) {
        $is_extra_pay = $puantaj_turu && in_array($puantaj_turu->Turu, ['Fazla Çalışma', 'Saatlik']);
        if ($is_extra_pay) {
            if ($is_overtime) {
                $tutar = round($extra_hours * $daily_wages * $overtime_multiplier, 2);
            } else {
                $tutar = round(floatval($saat) * $daily_wages, 2);
            }
        } else {
            $tutar = 0;
        }
    } else {
        if ($is_overtime) {
            $normal_pay = floatval($work_hour) * $daily_wages;
            $overtime_pay = $extra_hours * $daily_wages * $overtime_multiplier;
            $tutar = round($normal_pay + $overtime_pay, 2);
        } else {
            $tutar = round(floatval($saat) * $daily_wages, 2);
        }
    }

    $firm_id = $_SESSION['firm_id'] ?? 0;

    $data = [
        'id' => $id,
        'company_id' => $firm_id,
        'person' => $person_id,
        'project_id' => $project_id,
        'puantaj_id' => $type_id,
        'gun' => $date, // Standart tireli formatta kaydediyoruz
        'saat' => $saat,
        'tutar' => $tutar,
        "description" => "Mobil Hızlı Giriş",
        "updated_at" => date('Y-m-d H:i:s')
    ];

    $puantajObj->saveWithAttr($data);

    echo json_encode(['status' => 'success', 'message' => 'Puantaj başarıyla kaydedildi']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
