<?php
require_once "../../Database/db.php";
require_once "../../Model/Company.php";
require_once "../../Model/UserModel.php";

use App\Helper\Security;
use Database\Db;
session_start();

$dbInstance = new Db();
$User = new UserModel();
$db = $dbInstance->connect();

$company = new Company();

if ($_POST["action"] == "saveMyCompany") {
    $id = Security::decrypt($_POST["id"]);



    $parent_id = $_SESSION["user"]->parent_id == 0 ? $_SESSION["user"]->id : $_SESSION["user"]->parent_id;
    $data = [
        "id" => $id,
        "user_id" => $parent_id,
        "firm_name" => $_POST["firm_name"],
        "phone" => $_POST["phone"],
        "email" => $_POST["email"],
        "description" => $_POST["description"],
        "creator" => $_SESSION["user"]->id,
        'tax_number' => $_POST['vergi_no'],
        'tax_office' => $_POST['vergi_dairesi'],
        'start_budget' => $_POST['start_budget'],
        'yetkili_adi' => $_POST['yetkili_adi'],
    ];

    $brand_logo = $_FILES["brand_logo"];
    $file_path = $brand_logo["tmp_name"];
    $path = "../../uploads/";
    $file_name = uniqid() . $brand_logo["name"];

    if (move_uploaded_file($file_path, $path . $file_name)) {
        //Onceki yüklenen dosyayı bul 
        $old_brand_logo = $company->findMyFirmLogoName($id);

        if ($old_brand_logo) {
            // Dosya yolunu oluştur
            $old_brand_logo_file = $path . $old_brand_logo->brand_logo;
            // Eğer dosya varsa ve bir dosya ise
            if (is_file($old_brand_logo_file)) {
                // Dosyayı silmeyi dene
                if (!unlink($old_brand_logo_file)) {
                    // Hata yönetimi: Dosya silinemedi
                    error_log("Dosya silinemedi: $old_brand_logo_file");
                }
            }
        }

        $data["brand_logo"] = $file_name;
    }

    try {
        $lastInsertId = $company->saveMyFirms($data);
        $status = "success";
        if ($id == 0) {
            $message = "Firma başarıyla kaydedildi.";
            
            // Automatically create a default Admin role and assign all permissions
            require_once "../../Model/RolesModel.php";
            require_once "../../Model/Auths.php";
            require_once "../../Model/RoleAuthsModel.php";
            
            $Roles = new Roles();
            $Auths = new Auths();
            $RoleAuths = new RoleAuthsModel();
            
            $decryptedFirmId = Security::decrypt($lastInsertId);
            
            $roleData = [
                "id" => 0,
                "firm_id" => $decryptedFirmId,
                "roleName" => 'Admin',
                "main_role" => 1
            ];
            $lastInsertRoleId = $Roles->saveWithAttr($roleData);
            
            $authsList = $Auths->all();
            $authsIds = implode(',', array_column($authsList, 'id'));
            
            $roleAuthData = [
                "role_id" => Security::decrypt($lastInsertRoleId),
                "auth_ids" => $authsIds
            ];
            $RoleAuths->saveWithAttr($roleAuthData);
        } else {
            $message = "Firma başarıyla güncellendi.";
        }
    } catch (PDOException $e) {
        $status = "error";
        $message = $e->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message,
    ];

    echo json_encode($res);
}

if ($_POST["action"] == "deleteMyCompany") {
    $user_id = $_SESSION["user"]->id;
    $id = $_POST["id"];


    //parent_id = 0 ise sil
    if ($_SESSION["user"]->parent_id != 0) {
        $res = [
            "status" => "error",
            "message" => "Sadece ana kullanıcılar firma silebilir!",
        ];
        echo json_encode($res);
        exit;
    }

    //eğer sadece bir firma varsa silmeyi engelle
    if ($company->countMyFirms($user_id) == 1) {
        $res = [
            "status" => "error",
            "message" => "Silmek için en az bir firma olmalıdır." ,
        ];
        echo json_encode($res);
        exit;
    }
    $old_brand_logo = $company->findMyFirmLogoName($id);

    if ($old_brand_logo) {
        $path = "../../uploads/";
        $old_brand_logo_file = $path . $old_brand_logo->brand_logo;
        if (is_file($old_brand_logo_file)) {
            if (!unlink($old_brand_logo_file)) {
                error_log("Dosya silinemedi: $old_brand_logo_file");
            }
        }
    }

    try {
        $company->deleteMyFirm($id);
        $status = "success";
        $message = "Firma başarıyla silindi.!!!";
    } catch (PDOException $e) {
        $status = "error";
        $message = $e->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message,
    ];

    echo json_encode($res);
}
