<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']);
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/App/Helper/helper.php";


use App\Helper\Date;
use App\Helper\Helper;


$User = new UserModel();
$Settings = new SettingsModel();

if ($_POST["action"] == "userSave") {
    $id = $_SESSION["user"]->id;
    $lastInsertId = 0;

    try {
        //Email adresi ile kayıtlı ana kullanıcı varsa kayıt yapılmaz
        $data = [
            "id" => $id,
            "full_name" => $_POST["full_name"],
            "password" => password_hash($_POST['password'], PASSWORD_DEFAULT),
            "phone" => $_POST["phone"],
            "user_roles" => $_POST["user_roles"],
            "job" => $_POST["job"],


        ];

        $lastInsertId = $User->saveWithAttr($data) ?? $id;
        $status = "success";
        if ($id == 0) {
            $message = "Profil Bilgileriniz başarıyla kaydedildi.";
        } else {
            $message = "Profil Bilgileriniz başarıyla güncellendi.";
        }
    } catch (PDOException $e) {
        $status = "error";
        if ($e->errorInfo[1] == 1062) {
            $message = 'Bu e-posta adresi zaten kayıtlı.';
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
    //$id = $Settings->getSettingIdByUserAndAction($_SESSION["user"]->id, "work_hour")->id ?? 0;
    $id = $Settings->getSettings("work_hour")->id ?? 0;

    $data = [
        "id" => $id,
        "firm_id" => $_SESSION["firm_id"],
        "user_id" => $_SESSION["user"]->id,
        "set_name" => "work_hour",
        "set_value" => $work_hour
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
if ($_POST["action"] == "financialSettings") {

    $sub_limit = $_POST["sub_limit"];
    //$id = $Settings->getSettingIdByUserAndAction($_SESSION["user"]->id, "work_hour")->id ?? 0;
    $id = $Settings->getSettings("cases_sub_limit")->id ?? 0;

    $data = [
        "id" => $id,
        "firm_id" => $_SESSION["firm_id"],
        "user_id" => $_SESSION["user"]->id,
        "set_name" => "cases_sub_limit",
        "set_value" =>Helper::formattedMoneyToNumber($sub_limit)
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