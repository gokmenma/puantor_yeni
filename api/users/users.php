<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']);
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/Model/RolesModel.php";
require_once ROOT . "/App/Helper/date.php";


use App\Helper\Date;


$User = new UserModel();
$Roles = new Roles();

if ($_POST["action"] == "userSave") {
    $id = $_POST["id"];
    //Eğer kayıt yapan kullanıcı ana kullanıcı ise kend id'si, değilse parent_id'si alınır.
    $parent_id = $_SESSION["user"]->parent_id == 0 ? $_SESSION["user"]->id : $_SESSION["user"]->parent_id;
    $lastInsertId = 0;

    try {
        //Email adresi ile kayıtlı ana kullanıcı varsa kayıt yapılmaz
        $user = $User->getUserByEmail($_POST["email"]);
        
        if ($user && $user->parent_id == 0) {
            $status = "error";
            $message = "Bu e-posta adresi ile zaten kayıtlı.";
            $res = [
                "status" => $status,
                "message" => $message,
            ];
            echo json_encode($res);
            exit;
        }

        $data = [
            "id" => $id,
            "user_type" => $_SESSION["user"]->user_type,
            "parent_id" => $parent_id,
            "firm_id" => $_SESSION["firm_id"],
            "full_name" => $_POST["full_name"],
            "email" => $_POST["email"],
            "password" => password_hash($_POST['password'], PASSWORD_DEFAULT),
            "user_roles" => $_POST["user_roles"],
           "phone" => $_POST["phone"],
            "job" => $_POST["job"],
            "status" => 1,
        ];
  

        $lastInsertId = $User->saveWithAttr($data) ?? $id;
        $status = "success";
        if ($id == 0) {
            $message = "Kullanıcı başarıyla kaydedildi.";
        } else {
            $message = "Kullanıcı başarıyla güncellendi.";
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


if ($_POST["action"] == "deleteUser") {
    $id = $_POST["id"];
    try {
        $user->delete($id);
        $status = "success";
        $message = "Kullanıcı başarıyla silindi.";
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


if ($_POST["action"] == "isThereUserRoleGroup") {
    $firm_id = $_POST["firm_id"];
    $roles = $Roles->countRolesByFirm();
    $res = [
        "status" => "success",
        "roles" => $roles->total
    ];
    echo json_encode($res);
}