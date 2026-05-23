<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']);
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/App/Helper/helper.php";


use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;


$User = new UserModel();
$Settings = new SettingsModel();

if ($_POST["action"] == "userSave") {
    $id = $_SESSION["user"]->id;
    $lastInsertId = 0;

    try {
        $currentUser = $User->find($id);
        if (!$currentUser) {
            throw new Exception("Kullanıcı bulunamadı.");
        }

        $data = [
            "id" => $id
        ];

        if (isset($_POST["full_name"])) {
            $data["full_name"] = trim($_POST["full_name"]);
        }

        if (isset($_POST["username"])) {
            $username = trim($_POST["username"]);
            if (!empty($username)) {
                if ($User->isUsernameExists($username, $id)) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Bu kullanıcı adı zaten kullanılmaktadır. Lütfen başka bir kullanıcı adı seçin."
                    ]);
                    exit();
                }
                $data["username"] = $username;
            } else {
                $data["username"] = null;
            }
        }

        if (isset($_POST["phone"])) {
            $data["phone"] = trim($_POST["phone"]);
        }

        if (isset($_POST["job"])) {
            $data["job"] = trim($_POST["job"]);
        }

        if (isset($_POST["user_roles"])) {
            $data["user_roles"] = $_POST["user_roles"];
        }

        if (isset($_POST["password"]) && !empty($_POST["password"])) {
            $data["password"] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $lastInsertId = $User->saveWithAttr($data) ?? $id;
        
        // Güncellenmiş kullanıcı bilgisini oturuma kaydet
        $_SESSION["user"] = $User->find($id);
        
        $status = "success";
        $message = "Profil Bilgileriniz başarıyla güncellendi.";
    } catch (Exception $e) {
        $status = "error";
        if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
            $message = 'Bu e-posta veya kullanıcı adı zaten kayıtlı.';
        } else {
            $message = $e->getMessage();
        }
    }
    $res = [
        "status" => $status,
        "message" => $message,
        "lastid" => $lastInsertId
    ];
    echo json_encode($res);
    exit();
}

//Kullanıcı girişinde mail göndermek için
if ($_POST["action"] == "send_email_on_login") {
    //Eğer birden fazla kayıt varsa son kayıt üzerinden işlem yapılır,diğerleri silinir
    $record = $Settings->getSettingIdByUserAndActionAll($_SESSION["user"]->id, "loginde_mail_gonder");
    if (count($record) > 1) {
        foreach ($record as $key => $value) {
            if ($key != 0) {
                $Settings->deleteByUserAndAction($value->user_id, $value->set_name);
            }
        }
    }

    //Kayıt yoksa yeni kayıt oluşturulur
    $id = $Settings->getSettingIdByUserAndAction($_SESSION["user"]->id, "loginde_mail_gonder")->id ?? 0;

    $input_val = isset($_POST["send_email_on_login"]) ? 1 : 0;
    $data = [
        "id" => $id,
        "firm_id" => $_SESSION["firm_id"],
        "user_id" => $_SESSION["user"]->id,
        "set_name" => "loginde_mail_gonder",
        "set_value" => $input_val
    ];
    try {
        $lastInsertId = $Settings->saveWithAttr($data) ?? $id;
        $status = "success";
        $message = "Ayarlar başarıyla tamamlandı.";
    } catch (PDOException $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
}

//Genel ayarlar
if ($_POST["action"] == "homeSettings") {

    $work_hour = $_POST["work_hour"];
    $show_white_collar = isset($_POST["show_white_collar_in_puantaj"]) ? 1 : 0;

    try {
        $Settings->upsertSetting("work_hour", $work_hour);
        $Settings->upsertSetting("show_white_collar_in_puantaj", $show_white_collar);
        
        $status = "success";
        $message = "Ayarlar başarıyla tamamlandı.";
    } catch (PDOException $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
}

//Genel ayarlar
if ($_POST["action"] == "financialSettings") {

    $sub_limit = $_POST["sub_limit"];
    $personnel_advance_request_visible = isset($_POST["personnel_advance_request_visible"]) ? 1 : 0;

    try {
        $Settings->upsertSetting("cases_sub_limit", Helper::formattedMoneyToNumber($sub_limit));
        $Settings->upsertSetting("personnel_advance_request_visible", $personnel_advance_request_visible);
        $status = "success";
        $message = "Ayarlar başarıyla tamamlandı.";
    } catch (PDOException $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
}

// Hesap Silme
if ($_POST["action"] == "deleteAccount") {
    $id = $_SESSION["user"]->id;
    try {
        $User->softDelete(Security::encrypt($id));
        $status = "success";
        $message = "Hesabınız başarıyla silindi.";
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit();
}