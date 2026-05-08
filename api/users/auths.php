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
        $auths = [];
        // auth_ids arrayini döngüye alıp virgülle birleştirme
        foreach ($auth_ids as $auth_id) {
            $auths[] = $auth_id;
        }
        $auths = implode(",", $auths);

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
        $copied_role_auths = $roleAuths->getAuthsByRoleId($role_to_copy)->auth_ids;

        $data = [
            "id" => $id,
            "role_id" => $copy_role_id,
            "auth_ids" => $copied_role_auths
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