<?php
require_once dirname(__DIR__) . '/App/bootstrap.php';

ob_start();
header('Content-Type: application/json; charset=utf-8');

!defined('ROOT') ? define('ROOT', dirname(__DIR__)) : '';
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Puantaj.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Wages.php';
require_once ROOT . '/Database/db.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/Model/SettingsModel.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/App/Helper/ErrorMail.php';

use App\Helper\Date;
use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\ErrorMail;

$Settings = new SettingsModel();
$puantajObj = new Puantaj();
$person = new Persons();
$wages = new Wages();

$action = $_POST['action'] ?? ($_REQUEST['action'] ?? '');

if ($action == 'savePuantaj') {
    $status = 'info';
    $message = '';
    $save_count = 0;
    $error_count = 0;

    require_once ROOT . '/Model/Bordro.php';
    $bordro_check = new Bordro();
    $firm_id = (int)($_SESSION['firm_id'] ?? 0);

    $json_data = json_decode($_POST['data'] ?? '[]', true);
    if (!empty($json_data)) {
        // Dönem kilit denetimi
        foreach ($json_data as $pk => $pi) {
            foreach ($pi as $day_key => $pval) {
                $d_time = strtotime($day_key);
                if ($d_time) {
                    $chk_year = date('Y', $d_time);
                    $chk_month = date('m', $d_time);
                    if ($bordro_check->getPeriodVisibility($firm_id, $chk_year, $chk_month) == 1) {
                        if (ob_get_length()) ob_clean();
                        echo json_encode([
                            'status' => 'error',
                            'message' => "{$chk_month}/{$chk_year} döneminin bordrosu kapatıldığı için puantaj verisi değiştirilemez!"
                        ]);
                        exit;
                    }
                }
                break 2;
            }
        }
    }
    $error_wages = [];

    if (!empty($json_data)) {
        //Günlük calisma saatini getir
        $work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
        $work_hour = floatval(str_replace(',', '.', $work_hour));
        $overtime_rate = floatval($Settings->getSettings("overtime_rate")->set_value ?? 50);
        if ($overtime_rate < 50) { $overtime_rate = 50; }
        $overtime_multiplier = 1 + ($overtime_rate / 100);

        foreach ($json_data as $person_key => $person_item) {
            $person_id = Security::decrypt($person_key);
            if (!$person_id) {
                // Eğer şifre çözülemediyse düz integer dene
                $person_id = is_numeric($person_key) ? (int)$person_key : 0;
            }
            if (!$person_id) continue;

            $person_data = $person->getDailyWages($person_id);
            if (!$person_data) {
                $error_wages[] = $person->getPersonByField($person_id, 'full_name') ?: "Bilinmeyen Personel";
                continue;
            }

            $person_info = $person->find($person_id);
            $start_date_ymd = ($person_info && !empty($person_info->job_start_date)) ? Date::Ymd($person_info->job_start_date) : '';
            $end_date_ymd = ($person_info && !empty($person_info->job_end_date)) ? Date::Ymd($person_info->job_end_date) : '';

            $effective_daily = (($person_info->wage_type ?? 0) == 1) ? (floatval($person_data->daily_wages ?? 0) / 30) : floatval($person_data->daily_wages ?? 0);
            $ucret_base = $effective_daily / ($work_hour > 0 ? $work_hour : 8);

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

                if (isset($puantaj_item['puantajId']) && $puantaj_item['puantajId'] == 0) {
                    if ($id == 0) {
                        $id = $puantajObj->getPuantajId($person_id, $puantaj_key, -1);
                    }
                    if ($id > 0) {
                        $puantajObj->deletePuantajGun($id);
                        $save_count++;
                    }
                } else if (!empty($puantaj_item['puantajId'])) {
                    // Özel ücret kontrolü
                    $wage_obj = $wages->getWageByPersonIdAndDate($person_id, $puantaj_key);
                    $defined_wage = $wage_obj->amount ?? 0;
                    if ($defined_wage > 0) {
                        $effective_defined = (($person_info->wage_type ?? 0) == 1) ? (floatval($defined_wage) / 30) : floatval($defined_wage);
                        $hourly_wage = $effective_defined / ($work_hour > 0 ? $work_hour : 8);
                    } else {
                        $hourly_wage = $ucret_base;
                    }

                    $puantaj_turu = $puantajObj->getPuantajTuruById($puantaj_item['puantajId']);
                    if ($puantaj_turu && $puantaj_turu->Turu != 'Saatlik') {
                        $saat = $puantajObj->getPuantajSaatiByfirm($puantaj_item['puantajId']);
                    } else {
                        $saat = $puantaj_turu->PuantajSaati ?? 0;
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

                    // Beyaz Yaka için sadece Fazla Çalışma ve Saatlik türler için tutar hesaplanır, normal günler için 0'dır
                    if (($person_info->wage_type ?? 0) == 1) {
                        $is_extra_pay = $puantaj_turu && in_array($puantaj_turu->Turu, ['Fazla Çalışma', 'Saatlik']);
                        if ($is_extra_pay) {
                            if ($is_overtime) {
                                $tutar = round($extra_hours * $hourly_wage * $overtime_multiplier, 2);
                            } else {
                                $tutar = round(floatval($saat) * $hourly_wage, 2);
                            }
                        } else {
                            $tutar = 0;
                        }
                    } else {
                        if ($is_overtime) {
                            $normal_pay = floatval($work_hour) * $hourly_wage;
                            $overtime_pay = $extra_hours * $hourly_wage * $overtime_multiplier;
                            $tutar = round($normal_pay + $overtime_pay, 2);
                        } else {
                            $tutar = round(floatval($saat) * $hourly_wage, 2);
                        }
                    }

                    $data = [
                        'id' => $id,
                        'company_id' => $person_info->firm_id ?? 0,
                        'person' => $person_id,
                        'project_id' => $current_p_id,
                        'puantaj_id' => $puantaj_item['puantajId'],
                        'gun' => $puantaj_key,
                        'saat' => $saat,
                        'tutar' => $tutar,
                        'description' => "Puantaj Çalışma",
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    try {
                        $puantajObj->saveWithAttr($data);
                        $save_count++;
                    } catch (\Throwable $e) {
                        $error_count++;
                        $message .= "<br>Hata: " . $e->getMessage();
                        ErrorMail::notifySuperadmins('Puantaj Kaydı', $e->getMessage(), $e);
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

    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => $status, 'message' => $message]);
} else {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem talebi.']);
}
