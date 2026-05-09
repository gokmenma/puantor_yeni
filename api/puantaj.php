<?php
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

!defined('ROOT') ? define('ROOT', $_SERVER["DOCUMENT_ROOT"]) : '';
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Puantaj.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Wages.php';
require_once ROOT . '/Database/db.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/Model/SettingsModel.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/Model/ActivityLogModel.php';

use App\Helper\Date;
use App\Helper\Security;
use App\Helper\Helper;

$Settings = new SettingsModel();
$puantajObj = new Puantaj();
$person = new Persons();
$wages = new Wages();

if ($_POST['action'] == 'savePuantaj') {
    $status = 'info';
    $message = '';
    $save_count = 0;
    $error_count = 0;

    //Günlük calisma saatini getir
    $work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
    $work_hour = floatval(str_replace(',', '.', $work_hour));

    $json_data = json_decode($_POST['data'], true);
    $error_wages = [];

    if (!empty($json_data)) {
        foreach ($json_data as $person_key => $person_item) {
            $person_id = Security::decrypt($person_key);
            $person_data = $person->getDailyWages($person_id);
            
            if (!$person_data) {
                $error_wages[] = $person->getPersonByField($person_id, 'full_name') ?: "Bilinmeyen Personel";
                continue;
            }

            $person_info = $person->find($person_id);
            $start_date_ymd = ($person_info && !empty($person_info->job_start_date)) ? Date::Ymd($person_info->job_start_date) : '';
            $end_date_ymd = ($person_info && !empty($person_info->job_end_date)) ? Date::Ymd($person_info->job_end_date) : '';

            $ucret_base = ($person_data->daily_wages ?? 0) / $work_hour;

            foreach ($person_item as $puantaj_key => $puantaj_item) {
                // Arka plan kontrolü: İşe giriş tarihinden önce veya işten ayrılış tarihinden sonra ise işlem yapma
                $current_day_ymd = Date::Ymd($puantaj_key);
                if (!empty($start_date_ymd) && $current_day_ymd < $start_date_ymd) {
                    continue;
                }
                if (!empty($end_date_ymd) && $current_day_ymd > $end_date_ymd) {
                    continue;
                }

                $current_p_id = ($puantaj_item['project_id'] !== "" && $puantaj_item['project_id'] !== null) ? $puantaj_item['project_id'] : null;
                $id = $puantajObj->getPuantajId($person_id, $puantaj_key, $current_p_id);

                if ($puantaj_item['puantajId'] == 0) {
                    if ($id > 0) {
                        $puantajObj->deletePuantajGun($id);
                        $save_count++;
                    }
                } else if (!empty($puantaj_item['puantajId'])) {
                    // Özel ücret kontrolü
                    $defined_wage = $wages->getWageByPersonIdAndDate($person_id, $puantaj_key)->amount ?? 0;
                    $hourly_wage = ($defined_wage > 0) ? ($defined_wage / $work_hour) : $ucret_base;

                    $puantaj_turu = $puantajObj->getPuantajTuruById($puantaj_item['puantajId']);
                    if ($puantaj_turu && $puantaj_turu->Turu != 'Saatlik') {
                        $saat = $puantajObj->getPuantajSaatiByfirm($puantaj_item['puantajId']);
                    } else {
                        $saat = $puantaj_turu->PuantajSaati ?? 0;
                    }
                    
                    $tutar = floatval($saat) * $hourly_wage;

                    $data = [
                        'id' => $id,
                        'person' => $person_id,
                        'project_id' => $current_p_id,
                        'puantaj_id' => $puantaj_item['puantajId'],
                        'gun' => $puantaj_key,
                        'saat' => $saat,
                        'tutar' => $tutar,
                        'description' => "Puantaj Çalışma"
                    ];

                    try {
                        $puantajObj->saveWithAttr($data);
                        $save_count++;
                    } catch (\Exception $e) {
                        $error_count++;
                        $message .= "<br>Hata: " . $e->getMessage();
                    }
                }
            }
        }
    }

    if ($error_count > 0) {
        $status = 'error';
        $message = "İşlem tamamlandı fakat $error_count hata oluştu." . $message;
    } else if ($save_count > 0) {
        $status = 'success';
        $message = "$save_count değişiklik başarıyla kaydedildi.";
    } else {
        $status = 'info';
        $message = "Herhangi bir değişiklik yapılmadı veya kaydedilecek veri bulunamadı.";
    }

    echo json_encode(['status' => $status, 'message' => $message]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem talebi.']);
}
