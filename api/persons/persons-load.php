<?php
define('ROOT', $_SERVER["DOCUMENT_ROOT"]);

require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Database/require.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/security.php';
require ROOT . '/vendor/autoload.php';


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

                //Kuralları geçen kayıtların eklenmesi sağlanır
                //full_name en az 3 karakter olmalı
                $fullName = trim($row["A"]);
                if (strlen($fullName) < 3 || strlen($fullName) > 50) {
                    $row_errors[] = "Ad Soyad 3-50 karakter arasında olmalıdır.";
                }

                //kimlik_no 11 karakter olmalı
                $tcNo = str_replace(' ', '', trim((string)$row["B"]));
                if (strlen($tcNo) != 11 || !is_numeric($tcNo)) {
                    $row_errors[] = "Kimlik No 11 haneli ve sayısal olmalıdır. (Girilen: $tcNo)";
                }

                //job_start_date tarih formatında olmalı
                if (!Date::isDate($row["C"])) {
                    $row_errors[] = "İşe Başlama Tarihi tarih formatında olmalıdır. (Girilen: ".$row["C"].")";
                }

                //iban_number 26 karakter olmalı
                $iban = str_replace(' ', '', trim((string)$row["D"]));
                if (strlen($iban) != 26) {
                    $row_errors[] = "Iban Numarası TR dahil 26 karakter olmalıdır. (Girilen: $iban)";
                }

                //daily_wages sayısal olmalı
                require_once ROOT . '/App/Helper/helper.php';
                $wage = \App\Helper\Helper::standardizeWage($row["E"]);
                if ($wage == 0 && trim((string)$row["E"]) !== '0' && trim((string)$row["E"]) !== '') {
                    $row_errors[] = "Günlük/Aylık Ücret sayısal olmalıdır. (Girilen: ".$row["E"].")";
                }

                // Eğer bu satırda hata varsa, hataları ana listeye ekle ve atla
                if (!empty($row_errors)) {
                    $all_errors[] = "Satır $key: " . implode(" ", $row_errors);
                    continue;
                }

                // Mevcut personel kontrolü (TC Kimlik ile)
                $existingPerson = $Persons->getPersonByKimlikNo($tcNo);
                $personId = $existingPerson ? $existingPerson->id : 0;

                $data = [
                    "id" => $personId,
                    "full_name" => Security::escape($row["A"]),
                    "kimlik_no" => Security::encrypt($tcNo),
                    "job_start_date" => Date::dmY($row["C"], "d.m.Y"),
                    "iban_number" => Security::encrypt($iban),
                    "daily_wages" => Security::escape($wage),
                    "phone" => Security::escape($row["F"]),
                    "email" => Security::escape($row["G"]),
                    "wage_type" => Security::escape($row["H"]),
                    "address" => Security::escape($row["I"]),
                    "description" => Security::escape($row["J"]),
                    "ekip" => isset($row["L"]) ? Security::escape(trim($row["L"])) : null,
                    "team_id" => isset($row["L"]) ? Security::escape(trim($row["L"])) : null,
                    "job" => isset($row["M"]) ? Security::escape(trim($row["M"])) : null,
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