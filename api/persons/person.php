<?php
ob_start();
!defined("ROOT") ? define("ROOT", dirname(dirname(__DIR__))) : null;
require_once "../../Database/require.php";
require_once "../../Model/Persons.php";
require_once ROOT . "/Model/Bordro.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/Model/Puantaj.php";
require_once "../../Model/Auths.php";
require_once "../../App/Helper/helper.php";
require_once "../../Model/Projects.php";

use App\Helper\Security;
use App\Helper\Helper;

// Silently log or ignore if log fails
@file_put_contents(ROOT . "/debug_api.log", date("Y-m-d H:i:s") . " - Action: " . ($_POST["action"] ?? "none") . " - User: " . ($_SESSION["user"]->id ?? "none") . "\n", FILE_APPEND);

$Puantaj = new Puantaj();
$Bordro = new Bordro();
$Persons = new Persons();
$Auths = new Auths();
$Projects = new Projects();


if ($_POST["action"] == "savePerson") {
    //personel kaydetme yetkisi var mı kontrol et
    $Auths->hasPermissionReturn('personnel_add_update');

    //gelen id 0 dan farklı ise şifreyi çöz, değilse 0 ata
    $id = $_POST["id"] != 0 ? Security::decrypt($_POST["id"]) : 0;

    if ($id > 0) {
        //personelin göreve başlama tarihinden önceki tüm maaşları sil
        //$Bordro->deleteAllSalaries($id, $_POST["job_start_date"]);

        //personelin göreve başlama tarihinden önceki ve işten ayrılma tarihinden sonraki tüm puantajları sil
        //$Puantaj->deletePastAttendanceRecords($id, $_POST["job_start_date"], $_POST["job_end_date"]);
    }
    $job_group = $_POST["job_groups"];
    // Eğer iş grubu sayısal değilse (yeni bir tag girilmişse) yeni grup oluştur
    if (!empty($job_group) && !is_numeric($job_group)) {
        $db = $Persons->getDb();
        $stmt = $db->prepare("INSERT INTO job_groups (firm_id, group_name) VALUES (?, ?)");
        $stmt->execute([$_SESSION["firm_id"], $job_group]);
        $job_group = $db->lastInsertId();
    }

    $team_val = !empty($_POST["team_id"]) ? $_POST["team_id"] : null;

    $data = [
        "id" => $id,
        "full_name" => $_POST["full_name"],
        "kimlik_no" => Security::encrypt($_POST["kimlik_no"]),
        "email" => filter_var($_POST["email"], FILTER_VALIDATE_EMAIL) ? $_POST["email"] : null,
        "phone" => $_POST["phone"],
        "address" => Security::escape($_POST["address"]),
        "job" => $_POST["job"],
        "job_group" => $job_group,
        "team_id" => $team_val,
        "ekip" => $team_val,
        "firm_id" => $_SESSION["firm_id"],
        "wage_type" => $_POST["wage_type"],
        "iban_number" => Security::encrypt($_POST["iban_number"]),
        "description" => $_POST["aciklama"] ?? '',
        // "salary" => $_POST["salary"],
        "daily_wages" => Helper::formattedMoneyToNumber($_POST["daily_wages"]),
        "job_start_date" => $_POST["job_start_date"],
        "job_end_date" => $_POST["job_end_date"],
        "password" => !empty($_POST["password"]) ? password_hash($_POST["password"], PASSWORD_DEFAULT) : null,

        // "status" => $_POST["status"],
    ];

    // If password is empty, don't update it (keep existing)
    if (empty($_POST["password"])) {
        unset($data["password"]);
    }



    try {
        //Yeni kayıt ise geriye şifreli id döner, güncelleme ise Post ile gelen şifreli id döner
        $lastInsertId = $Persons->saveWithAttr($data) ?? $_POST["id"];
        $status = "success";
        if ($id == 0) {
            $message = "Personel başarıyla kaydedildi.";
        } else {
            $message = "Personel başarıyla güncellendi.";
        }


        //Personelin çalıştığı projeleri kaydet
        if (isset($_POST["person_project"])) {
            $Projects->savePersonProjects(Security::decrypt($lastInsertId), $_POST["person_project"]);
        }

    } catch (PDOException $e) {
        $status = "error";
        if ($e->errorInfo[1] == 1062) {
            // Hata mesajından ihlal edilen benzersiz kısıtın adını çıkar
            preg_match('/Duplicate entry .* for key \'(.*)\'/', $e->getMessage(), $matches);
            $violatedField = $matches[1] ?? 'Bilinmeyen alan';
            if ($violatedField == 'kimlik_no') {
                $message = "Bu kimlik numarası zaten kayıtlı.";
            } elseif ($violatedField == 'phone') {
                $message = "Bu telefon numarası zaten kayıtlı.";
            } else {
                $message = $e->getMessage();
            }
        } else {
            $message = $e->getMessage();
        }
    }
    $res = [
        "status" => $status,
        "message" => $message,
        "lastid" => $lastInsertId,
    ];
    echo json_encode($res);
}

if ($_POST["action"] == "deletePerson") {
    ob_clean();
    $log_file = __DIR__ . "/delete_debug.log";
    @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Delete started - ID: " . ($_POST["id"] ?? "MISSING") . "\n", FILE_APPEND);
    
    try {
        //personel silme yetkisi var mı kontrol et
        $Auths->hasPermissionReturn('personnel_delete');
        @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Permission OK\n", FILE_APPEND);

        $id = $_POST["id"];
        $decrypted_id = Security::decrypt($id);
        @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Decrypted ID: " . ($decrypted_id ?: "FAILED") . "\n", FILE_APPEND);
        
        if (!$decrypted_id) {
             throw new Exception("Geçersiz personel ID.");
        }

        $person = $Persons->find($decrypted_id);
        @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Person found: " . ($person ? "YES" : "NO") . "\n", FILE_APPEND);
        
        if (!$person) {
            throw new Exception("Personel bulunamadı.");
        }

        //İşlem yapan kullanıcı ile personelin firm id'si aynı olmalı
        $session_firm_id = $_SESSION['firm_id'] ?? ($_SESSION['user']->firm_id ?? 0);
        
        if ($person->firm_id != $session_firm_id) {
             @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Firm mismatch: Person Firm: " . $person->firm_id . " vs Session Firm: " . $session_firm_id . " - UserID: " . ($_SESSION['user']->id ?? 'unknown') . "\n", FILE_APPEND);
            throw new Exception("Bu personeli silme yetkiniz yok. (Firma Uyuşmazlığı)");
        }

        $Persons->softDelete($id);
        @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Soft delete OK\n", FILE_APPEND);
        
        $status = "success";
        $message = "Personel başarıyla silindi.";
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
        @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Error: " . $message . "\n", FILE_APPEND);
    }
    
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    ob_end_flush();
    exit;
}

if ($_POST["action"] == "bulkDeletePersons") {
    ob_clean();
    $log_file = __DIR__ . "/delete_debug.log";
    @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Bulk delete started\n", FILE_APPEND);
    
    try {
        //personel silme yetkisi var mı kontrol et
        $Auths->hasPermissionReturn('personnel_delete');
        @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Permission OK\n", FILE_APPEND);

        $ids = $_POST["ids"] ?? [];
        if (empty($ids) || !is_array($ids)) {
            throw new Exception("Lütfen silmek istediğiniz personelleri seçin.");
        }

        $session_firm_id = $_SESSION['firm_id'] ?? ($_SESSION['user']->firm_id ?? 0);
        $deleted_count = 0;

        foreach ($ids as $id) {
            $decrypted_id = Security::decrypt($id);
            if (!$decrypted_id) {
                continue;
            }

            $person = $Persons->find($decrypted_id);
            if (!$person) {
                continue;
            }

            //İşlem yapan kullanıcı ile personelin firm id'si aynı olmalı
            if ($person->firm_id != $session_firm_id) {
                continue;
            }

            $Persons->softDelete($id);
            $deleted_count++;
        }

        $status = "success";
        $message = "Seçilen {$deleted_count} personel başarıyla silindi.";
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
        @file_put_contents($log_file, date("Y-m-d H:i:s") . " - Bulk Error: " . $message . "\n", FILE_APPEND);
    }
    
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    ob_end_flush();
    exit;
}




//Gelir gider bilgilerindeki kayıtları silmek için
if ($_POST['action'] == 'deletePayment') {

    //Ödeme Silme Yetkisi var mı kontrol et
    $Auths->hasPermissionReturn("delete_staff_payment");

    $id = $_POST['id'];
    $person_id = Security::decrypt($_GET['person_id']);
    $income_expense = '';
    $status = 'success';
    $message = $id;

    $type = $_GET["type"];

    try {
        $db->beginTransaction();

        //Gelen type değerine göre silme işlemi yapılır
        if ($type === 'Puantaj Çalışma') {
            $Puantaj->delete($id);
        } else {
            $Bordro->delete($id);
        }

        // Personelin, maas_gelir_kesinti tablosundaki ödeme, kesinti ve gelir toplamlarını getirir
        $income_expense = $Bordro->sumAllIncomeExpense($person_id);

        //Bakiye hesaplanır
        $balance = Helper::formattedMoney($income_expense->total_income - $income_expense->total_payment - $income_expense->total_expense);  // Bakiye

        //Toplam Gelir
        $income_expense->total_income = Helper::formattedMoney($income_expense->total_income ?? 0);

        // Toplam ödeme 
        $income_expense->total_payment = Helper::formattedMoney($income_expense->total_payment ?? 0);

        // Toplam gider
        $income_expense->total_expense = Helper::formattedMoney($income_expense->total_expense ?? 0);

        // Bakiye, değişkenine atama yapılır
        $income_expense->balance = $balance;


        $message = "İşlem başarılı bir şekilde gerçekleşti.";


        $db->commit();
    } catch (PDOException $e) {
        $db->rollBack();
        $status = 'error';
        $message = $e->getMessage();
    }
    $res = [
        'status' => $status,
        'message' => $message,
        'income_expense' => $income_expense,
    ];
    echo json_encode($res);
}