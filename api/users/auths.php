<?php
require_once '../../Database/require.php';
require_once '../../App/Helper/date.php';
require_once '../../Model/RoleAuthsModel.php';
require_once '../../App/Helper/security.php';
require_once '../../Model/Auths.php';

use App\Helper\Security;
use App\Helper\Date;
$Auths = new Auths();


$roleAuths = new RoleAuthsModel();

if ($_POST["action"] == "saveAuths") {


    try {

        //Yetki Kontrolü yapılır
        $Auths->hasPermissionReturn("transaction_permissions");

        $id = Security::decrypt($_POST["auth_id"]);
        $auth_ids = $_POST["auths"] ?? [];
        $posted_auths = array_map('intval', $auth_ids);

        $is_superadmin = (isset($_SESSION['user']->superadmin) && $_SESSION['user']->superadmin == 1);

        if (!$is_superadmin) {
            $superadmin_auth_ids = $Auths->getSuperadminAuthIds();
            
            // Mevcut yetkileri al
            $role_id = $_POST["role_id"];
            $existing_role_auths = $roleAuths->getAuthsByRoleId($role_id);
            if ($existing_role_auths) {
                $existing_auth_ids = array_filter(explode(',', $existing_role_auths->auth_ids));
                $existing_auth_ids = array_map('intval', $existing_auth_ids);
                
                // Rolün halihazırda sahip olduğu süperadmin yetkilerini koru
                $existing_superadmin_auths = array_intersect($existing_auth_ids, $superadmin_auth_ids);
                
                // Gönderilen yetkilerden süperadmin olanları temizle (güvenlik için)
                $posted_non_superadmin_auths = array_diff($posted_auths, $superadmin_auth_ids);
                
                $final_auths = array_merge($existing_superadmin_auths, $posted_non_superadmin_auths);
            } else {
                $final_auths = array_diff($posted_auths, $superadmin_auth_ids);
            }
        } else {
            $final_auths = $posted_auths;
        }

        $auths = implode(",", $final_auths);

        $data = [
            "id" => $id,
            "role_id" => $_POST["role_id"],
            "auth_ids" => $auths
        ];
        $lastInsertId = $roleAuths->saveWithAttr($data) ?? $_POST["auth_id"];
        $status = "success";
        $message = $id == 0 ? "Yetkiler başarıyla kaydedildi." : "Yetkiler başarıyla güncellendi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message,
        "id" => $lastInsertId
    ];
    echo json_encode($res);
}





if ($_POST["action"] == "copyRolesModal") {
    $copy_role_id = $_POST["copy_role_id"];
    $role_to_copy = $_POST["role_to_copy"];
    try {
        //tabloda bu role ait kayıt var mı kontrol et
        $id = $roleAuths->getAuthsByRoleId($copy_role_id)->id ?? 0;

        //Kopyalanacak rolün yetkilerini al
        $copied_role_auths = $roleAuths->getAuthsByRoleId($role_to_copy)->auth_ids ?? '';
        $copied_auth_ids = array_filter(explode(',', $copied_role_auths));
        $copied_auth_ids = array_map('intval', $copied_auth_ids);

        $is_superadmin = (isset($_SESSION['user']->superadmin) && $_SESSION['user']->superadmin == 1);

        if (!$is_superadmin) {
            $superadmin_auth_ids = $Auths->getSuperadminAuthIds();
            
            // Hedef rolün mevcut yetkilerini al
            $target_existing_auths = $roleAuths->getAuthsByRoleId($copy_role_id);
            if ($target_existing_auths) {
                $target_auth_ids = array_filter(explode(',', $target_existing_auths->auth_ids));
                $target_auth_ids = array_map('intval', $target_auth_ids);
                
                // Hedef rolün mevcut süperadmin yetkilerini koru
                $existing_superadmin_auths = array_intersect($target_auth_ids, $superadmin_auth_ids);
            } else {
                $existing_superadmin_auths = [];
            }
            
            // Kopyalanacak rolden sadece süperadmin olmayan yetkileri al
            $copied_non_superadmin_auths = array_diff($copied_auth_ids, $superadmin_auth_ids);
            
            $final_auths = array_merge($existing_superadmin_auths, $copied_non_superadmin_auths);
        } else {
            $final_auths = $copied_auth_ids;
        }

        $copied_role_auths_str = implode(",", $final_auths);

        $data = [
            "id" => $id,
            "role_id" => $copy_role_id,
            "auth_ids" => $copied_role_auths_str
        ];
        $lastInsertId = $roleAuths->saveWithAttr($data) ?? $id;
        $status = "success";
        $message = $id == 0 ? "Rol başarıyla kopyalandı." : "Rol başarıyla güncellendi.";
    } catch (PDOException $e) {
        $status = "error";
        $message = $e->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message,
        "roles" => $lastInsertId
    ];

    echo json_encode($res);
}