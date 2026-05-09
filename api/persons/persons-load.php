<?php
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Database/require.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/security.php';

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


$firm_id = $_SESSION['firm_id'];

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Helper\Date;
use App\Helper\Security;
$Persons = new Persons();

if ($_POST["action"] == "persons-load-from-xls") {

    $file = $_FILES["persons-load-file"];
    $file_name = $file["name"];
    $file_tmp = $file["tmp_name"];
    $file_size = $file["size"];
    $file_error = $file["error"];
    $file_ext = explode(".", $file_name);
    $file_ext = strtolower(end($file_ext));
    $allowed = ["xls", "xlsx"];

    $lastInsertedId = 0;
    if (in_array($file_ext, $allowed)) {
        try {
            //excel dosyasını okuma
            $spreadsheet = IOFactory::load($file_tmp);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $data = [];
            //hata mesajlarını bir değişkene ata ve sonuç olarak döndür
            $all_errors = [];
            foreach ($sheetData as $key => $row) {
                if ($key == 1) {
                    continue;
                }
                
                $row_errors = [];

                //Kuralları geçen kayıtl                //kimlik_no 11 karakter olmalı
                $tcNo = str_replace(' ', '', trim((string)$row["B"]));
                if (strlen($tcNo) != 11 || !is_numeric($tcNo)) {
                    $row_errors[] = "Kimlik No 11 haneli ve sayısal olmalıdır. (Girilen: $tcNo)";
                }

                // Mevcut personel kontrolü (TC Kimlik ile)
                $existingPerson = null;
                $personId = 0;
                if (strlen($tcNo) == 11 && is_numeric($tcNo)) {
                    $existingPerson = $Persons->getPersonByKimlikNo($tcNo);
                    $personId = $existingPerson ? $existingPerson->id : 0;
                }

                //full_name kontrolü: yeni kayıtsa zorunlu, mevcutsa boş bırakılabilir
                $fullName = trim($row["A"]);
                if (empty($fullName) && !$existingPerson) {
                    $row_errors[] = "Yeni personel eklemek için Ad Soyad alanı zorunludur.";
                } else if (!empty($fullName) && (strlen($fullName) < 3 || strlen($fullName) > 50)) {
                    $row_errors[] = "Ad Soyad 3-50 karakter arasında olmalıdır.";
                }

                //job_start_date kontrolü: yeni kayıtsa zorunlu, mevcutsa boş bırakılabilir
                $job_start = trim((string)$row["C"]);
                if (empty($job_start) && !$existingPerson) {
                    $row_errors[] = "Yeni personel eklemek için İşe Başlama Tarihi zorunludur.";
                } else if (!empty($job_start) && !Date::isDate($job_start)) {
                    $row_errors[] = "İşe Başlama Tarihi tarih formatında olmalıdır. (Girilen: ".$job_start.")";
                }

                //iban_number kontrolü: TR dahil 26 karakter olmalı (boş bırakılırsa mevcut olan korunur)
                $iban = str_replace(' ', '', trim((string)$row["D"]));
                if (!empty($iban) && strlen($iban) != 26) {
                    $row_errors[] = "Iban Numarası TR dahil 26 karakter olmalıdır. (Girilen: $iban)";
                }

                //daily_wages kontrolü: yeni kayıtsa zorunlu, mevcutsa boş bırakılabilir
                require_once ROOT . '/App/Helper/helper.php';
                $wage = \App\Helper\Helper::standardizeWage($row["E"]);
                if (empty(trim((string)$row["E"])) && !$existingPerson) {
                    $row_errors[] = "Yeni personel eklemek için Günlük/Aylık Ücret alanı zorunludur.";
                } else if (!empty(trim((string)$row["E"])) && $wage == 0 && trim((string)$row["E"]) !== '0') {
                    $row_errors[] = "Günlük/Aylık Ücret sayısal olmalıdır. (Girilen: ".$row["E"].")";
                }

                // Eğer bu satırda hata varsa, hataları ana listeye ekle ve atla
                if (!empty($row_errors)) {
                    $all_errors[] = "Satır $key: " . implode(" ", $row_errors);
                    continue;
                }

                $raw_wage_type = isset($row["H"]) ? trim((string)$row["H"]) : '';
                $wage_type_val = 2; // Default to Mavi Yaka (2)
                if (!empty($raw_wage_type)) {
                    if (stripos($raw_wage_type, 'beyaz') !== false || $raw_wage_type === '1') {
                        $wage_type_val = 1;
                    } else if (stripos($raw_wage_type, 'mavi') !== false || $raw_wage_type === '2') {
                        $wage_type_val = 2;
                    }
                }

                $data = [
                    "id" => $personId,
                    "full_name" => (isset($row["A"]) && trim((string)$row["A"]) !== "") ? Security::escape($row["A"]) : ($existingPerson ? $existingPerson->full_name : ""),
                    "kimlik_no" => Security::encrypt($tcNo),
                    "job_start_date" => (isset($row["C"]) && trim((string)$row["C"]) !== "") ? Date::dmY($row["C"], "d.m.Y") : ($existingPerson ? $existingPerson->job_start_date : ""),
                    "iban_number" => (isset($row["D"]) && trim((string)$row["D"]) !== "") ? Security::encrypt($iban) : ($existingPerson ? $existingPerson->iban_number : ""),
                    "daily_wages" => (isset($row["E"]) && trim((string)$row["E"]) !== "") ? Security::escape($wage) : ($existingPerson ? $existingPerson->daily_wages : 0),
                    "phone" => (isset($row["F"]) && trim((string)$row["F"]) !== "") ? Security::escape($row["F"]) : ($existingPerson ? $existingPerson->phone : ""),
                    "email" => (isset($row["G"]) && trim((string)$row["G"]) !== "") ? Security::escape($row["G"]) : ($existingPerson ? $existingPerson->email : ""),
                    "wage_type" => !empty($raw_wage_type) ? $wage_type_val : ($existingPerson ? $existingPerson->wage_type : 2),
                    "address" => (isset($row["I"]) && trim((string)$row["I"]) !== "") ? Security::escape($row["I"]) : ($existingPerson ? $existingPerson->address : ""),
                    "description" => (isset($row["J"]) && trim((string)$row["J"]) !== "") ? Security::escape($row["J"]) : ($existingPerson ? $existingPerson->description : ""),
                    "ekip" => (isset($row["L"]) && trim((string)$row["L"]) !== "") ? Security::escape(trim($row["L"])) : ($existingPerson ? $existingPerson->ekip : null),
                    "job" => (isset($row["M"]) && trim((string)$row["M"]) !== "") ? Security::escape(trim($row["M"])) : ($existingPerson ? $existingPerson->job : null),
                    "firm_id" => $firm_id,
                ];

                // Şifreli ID döner (yeni kayıtsa) veya mevcut ID döner
                $encryptedId = $Persons->saveWithAttr($data);
                $decryptedId = Security::decrypt($encryptedId);
                $lastInsertedId = $decryptedId;

                // Projeleri işle (Kolon K)
                $projectNamesStr = isset($row["K"]) ? trim($row["K"]) : "";
                if (!empty($projectNamesStr)) {
                    require_once ROOT . '/Model/Projects.php';
                    $Projects = new Projects();
                    $projectNames = explode(",", $projectNamesStr);
                    $projectIds = [];

                    foreach ($projectNames as $pName) {
                        $pName = trim($pName);
                        if (empty($pName)) continue;

                        // Projeyi ara
                        $db = $Projects->getDb();
                        $stmt = $db->prepare("SELECT id FROM projects WHERE firm_id = ? AND project_name = ?");
                        $stmt->execute([$firm_id, $pName]);
                        $project = $stmt->fetch(PDO::FETCH_OBJ);

                        if ($project) {
                            $projectIds[] = $project->id;
                        } else {
                            // Proje yoksa ekle
                            $stmt = $db->prepare("INSERT INTO projects (firm_id, project_name) VALUES (?, ?)");
                            $stmt->execute([$firm_id, $pName]);
                            $projectIds[] = $db->lastInsertId();
                        }
                    }

                    if (!empty($projectIds)) {
                        // Personelin project_id alanını güncelle (virgüllü olarak)
                        $projectIdsStr = implode(",", $projectIds);
                        $stmt = $db->prepare("UPDATE persons SET project_id = ? WHERE id = ?");
                        $stmt->execute([$projectIdsStr, $decryptedId]);

                        // project_person tablosunu güncelle (uyumluluk için)
                        $Projects->savePersonProjects($decryptedId, $projectIds);
                    }
                }
            }

            //en az bir satır eklendiyse başarılı mesajı ver
            if ($lastInsertedId > 0) {
                $status = "success";
                $message = "Personeller başarıyla yüklendi";
            } else {
                $status = "error";
                $message = "Personeller yüklenemedi. Hiçbir geçerli kayıt bulunamadı.";
            }
        } catch (Exception $ex) {
            $status = "error";
            $message = $ex->getMessage();
        }

    } else {
        $status = "error";
        $message = "Dosya uzantısı uygun değil";
    }

    if (!empty($all_errors)) {
        if ($status == "success") {
            $message .= "<br><br><b>Bazı satırlar hatalı olduğu için atlandı:</b>";
        } else {
            $message = "<b>Yükleme sırasında hatalar oluştu:</b>";
        }
        foreach ($all_errors as $error) {
            $message .= "<br>" . $error;
        }
    }

    $res = [
        "status" => $status,
        "message" => $message,
        "data" => $data,
        "error_message" => $all_errors
    ];

    echo json_encode($res);
}