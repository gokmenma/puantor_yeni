<?php
require_once '../../Model/Bordro.php';
require_once '../../Model/Persons.php';
require_once '../../Database/require.php';
require_once '../../App/Helper/date.php';
require_once '../../App/Helper/helper.php';
require_once '../../Model/Auths.php';

$firm_id = $_SESSION['firm_id'];

$autoload_path = ROOT . '/vendor/autoload.php';
if (!file_exists($autoload_path)) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Sunucuda gerekli kütüphaneler (vendor/autoload.php) bulunamadı. Lütfen sunucuda 'composer install' komutunu çalıştırın veya yereldeki 'vendor' klasörünü sunucuya yükleyin."
    ]);
    exit;
}
require $autoload_path;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$Auths = new Auths();


//Giriş yapan kullanıcı ile kullanıcının firmasını kontrol et
$Auths->checkFirmReturn();

//Kullanıcının yetkisini kontrol et
$Auths->hasPermissionReturn('upload_payment_permission');


$bordro = new Bordro();
$personObj = new Persons();

if ($_POST["action"] == "payment-load-from-xls") {
    $month = $_POST["months"];
    $year = $_POST["year"];
    $project_id = $_POST["projects"];
    $type = $_POST["inc_exp_type"];
    $file = $_FILES["payment-load-file"];
    $file_name = $file["name"];
    $file_tmp = $file["tmp_name"];
    $file_size = $file["size"];
    $file_error = $file["error"];
    $file_ext = explode(".", $file_name);
    $file_ext = strtolower(end($file_ext));
    $allowed = ["xls", "xlsx"];

    if (in_array($file_ext, $allowed)) {
        try {
            //excel dosyasını okuma
            $spreadsheet = IOFactory::load($file_tmp);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $data = [];
            
            $success_count = 0;
            $not_found_count = 0;
            $not_found_names = [];
            
            foreach ($sheetData as $key => $row) {
                if ($key == 1) {
                    continue;
                }
                
                $tc_kimlik = trim($row["B"] ?? '');
                $ad_soyad = trim($row["C"] ?? '');
                $gun = trim($row["D"] ?? '');
                $tutar_raw = trim($row["E"] ?? '0');
                
                if (empty($tc_kimlik)) {
                    continue;
                }
                
                $person = $personObj->getPersonByKimlikNoAndFirm($tc_kimlik, $firm_id);
                if (!$person) {
                    $not_found_count++;
                    if (!empty($ad_soyad)) {
                        $not_found_names[] = $ad_soyad;
                    } else {
                        $not_found_names[] = $tc_kimlik;
                    }
                    continue;
                }
                
                $tutar = Helper::formattedMoneyToNumber($tutar_raw);
                if ($tutar <= 0) {
                    continue;
                }
                
                $data = [
                    "id" => 0,
                    "person_id" => $person->id,
                    "gun" => Date::Ymd($gun),
                    "tutar" => $tutar,
                    "ay" => $month,
                    "yil" => $year,
                    "kategori" => 7,
                    "turu" => $type,
                    "aciklama" => "Excel yükleme",
                ];
                $lastInsertedId = $bordro->saveWithAttr($data) ?? 0;
                $success_count++;
            }

            $status = "success";
            if ($not_found_count > 0) {
                $failed_list = implode(', ', array_slice($not_found_names, 0, 5));
                if (count($not_found_names) > 5) {
                    $failed_list .= '...';
                }
                $message = "Dosya yüklendi. {$success_count} adet ödeme başarıyla kaydedildi. {$not_found_count} personel sistemde bulunamadı ({$failed_list}).";
            } else {
                $message = "Dosya başarıyla yüklendi. {$success_count} adet ödeme kaydedildi.";
            }
            
            require_once '../../Model/ActivityLogModel.php';
            \ActivityLogModel::log('payroll', 'upload_payment', "Excel'den toplu ödeme yüklendi. Yıl: {$year}, Ay: {$month}, Proje ID: {$project_id}, Tür: {$type}, Başarılı: {$success_count}, Başarısız: {$not_found_count}");
        } catch (PDOException $ex) {
            $status = "error";
            $message = $ex->getMessage();
        }

    } else {
        $status = "error";
        $message = "Dosya uzantısı uygun değil";
    }

    $res = [
        "status" => $status,
        "message" => $message,
    ];

    echo json_encode($res);
}