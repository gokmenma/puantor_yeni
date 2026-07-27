<?php
require_once dirname(__DIR__, 2) . '/App/bootstrap.php';

require_once "../../Database/db.php";
require_once "../../Model/Company.php";
require_once "../../Model/UserModel.php";
require_once "../../App/Helper/helper.php";

use App\Helper\Security;
use App\Helper\Helper;
use Database\Db;
session_start();

$dbInstance = new Db();
$User = new UserModel();
$db = $dbInstance->connect();

$company = new Company();

if (isset($_POST["action"]) && $_POST["action"] == "saveMyCompany") {
    $id = Security::decrypt($_POST["id"]);

    $parent_id = $_SESSION["user"]->parent_id == 0 ? $_SESSION["user"]->id : $_SESSION["user"]->parent_id;

    if ($id == 0) {
        $subDetails = $User->getActiveSubscriptionDetails($parent_id);
        $current_firm_count = $company->countMyFirms($parent_id);
        $isSuperadmin = ($_SESSION["user"]->superadmin ?? 0) == 1;

        if (!$isSuperadmin && $current_firm_count >= $subDetails['firma_hakki']) {
            echo json_encode([
                "status" => "error",
                "message" => "Paketinizin firma limiti dolduğu için yeni firma eklenemez!"
            ]);
            exit;
        }
    }

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
        'start_budget' => Helper::formattedMoneyToNumber($_POST['start_budget'] ?? 0),
        'yetkili_adi' => $_POST['yetkili_adi'],
    ];

    $brand_logo = $_FILES["brand_logo"] ?? null;
    if ($brand_logo && !empty($brand_logo["tmp_name"])) {
        $file_path = $brand_logo["tmp_name"];
        $path = "../../uploads/";
        $file_name = uniqid() . $brand_logo["name"];

        if (move_uploaded_file($file_path, $path . $file_name)) {
            // Onceki yüklenen dosyayı bul 
            $old_brand_logo = $company->findMyFirmLogoName($id);

            if ($old_brand_logo) {
                // Dosya yolunu oluştur
                $old_brand_logo_file = $path . $old_brand_logo->brand_logo;
                // Eğer dosya varsa ve bir dosya ise
                if (is_file($old_brand_logo_file)) {
                    // Dosyayı silmeyi dene
                    if (!unlink($old_brand_logo_file)) {
                        // Hata yönetimi: Dosya silinemedi
                        system_log_error('Eski firma logosu silinemedi.', ['operation' => 'company_logo_delete', 'file' => $old_brand_logo_file]);
                    }
                }
            }

            $data["brand_logo"] = $file_name;
        }
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
            
            $authsIds = $Auths->getNonSuperadminAuthIds();
            $authsIdsString = implode(',', $authsIds);
            
            $roleAuthData = [
                "role_id" => Security::decrypt($lastInsertRoleId),
                "auth_ids" => $authsIdsString
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
    exit;
}

if (isset($_POST["action"]) && $_POST["action"] == "getMyFirmDetails") {
    $id = isset($_POST["id"]) ? Security::decrypt($_POST["id"]) : 0;
    $myfirm = $company->findMyFirm($id);
    if ($myfirm) {
        $res = [
            "status" => "success",
            "myfirm" => $myfirm
        ];
    } else {
        $res = ["status" => "error", "message" => "Firma bulunamadı."];
    }
    echo json_encode($res);
    exit;
}

if (isset($_POST["action"]) && $_POST["action"] == "deleteMyCompany") {
    $user_id = $_SESSION["user"]->id;
    $id = $_POST["id"];
    $password = $_POST["password"] ?? '';

    // parent_id = 0 ise sil
    if ($_SESSION["user"]->parent_id != 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Sadece ana kullanıcılar firma silebilir!",
        ]);
        exit;
    }

    // Şifre boş mu kontrolü
    if (empty($password)) {
        echo json_encode([
            "status" => "error",
            "message" => "Lütfen şifrenizi giriniz!"
        ]);
        exit;
    }

    // Kullanıcının güncel şifresini veritabanından çek ve doğrula
    $userRecord = $User->find($user_id);
    if (!$userRecord || !Security::passwordControl($password, $userRecord->password)) {
        echo json_encode([
            "status" => "error",
            "message" => "Girilen şifre hatalı!"
        ]);
        exit;
    }

    // eğer sadece bir aktif firma varsa silmeyi engelle
    if ($company->countMyFirms($user_id) <= 1) {
        echo json_encode([
            "status" => "error",
            "message" => "Sistemde en az bir aktif firma kalmalıdır."
        ]);
        exit;
    }

    try {
        $company->deleteMyFirm($id);
        $status = "success";
        $message = "Firma ve ilişkili tüm veriler başarıyla silindi (soft-delete).";
    } catch (\Exception $e) {
        $status = "error";
        $message = $e->getMessage();
    }

    echo json_encode([
        "status" => $status,
        "message" => $message,
    ]);
    exit;
}

if (isset($_POST["action"]) && $_POST["action"] == "setDefaultCompany") {
    if (empty($_SESSION["user"])) {
        echo json_encode(["status" => "error", "message" => "Oturum süreniz dolmuş."]);
        exit;
    }

    $raw_id = $_POST["id"] ?? '';
    $id = 0;
    if ($raw_id !== '' && $raw_id !== '0') {
        $decrypted = Security::decrypt($raw_id);
        $id = $decrypted !== false ? (int)$decrypted : (int)$raw_id;
    }

    require_once "../../Model/MyFirmModel.php";
    $myFirmModel = new MyFirmModel();
    $authorizedFirms = $myFirmModel->getMyFirmByUserId();
    $authorizedIds = array_map(function($f) { return (int)$f->id; }, $authorizedFirms);

    if ($id > 0 && !in_array($id, $authorizedIds, true)) {
        echo json_encode(["status" => "error", "message" => "Bu firmayı varsayılan olarak seçme yetkiniz bulunmuyor."]);
        exit;
    }

    try {
        $user_id = (int)$_SESSION["user"]->id;
        $user_email = $_SESSION["user"]->email ?? null;

        $User->setDefaultFirm($user_id, $id, $user_email);
        $_SESSION["user"]->default_firm_id = $id;

        require_once "../../Model/ActivityLogModel.php";
        if ($id > 0) {
            $firm_name = '';
            foreach ($authorizedFirms as $af) {
                if ((int)$af->id === $id) {
                    $firm_name = $af->firm_name ?? '';
                    break;
                }
            }
            ActivityLogModel::log('mycompany', 'update', "Varsayılan firma seçildi: {$firm_name} (ID: {$id})");
        } else {
            ActivityLogModel::log('mycompany', 'update', "Varsayılan firma seçimi kaldırıldı");
        }

        echo json_encode([
            "status" => "success",
            "message" => $id > 0 ? "Varsayılan firma başarıyla güncellendi." : "Varsayılan firma tercihi kaldırıldı."
        ]);
    } catch (\Exception $e) {
        echo json_encode(["status" => "error", "message" => "İşlem sırasında bir hata oluştu: " . $e->getMessage()]);
    }
    exit;
}
