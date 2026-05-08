<?php

use App\Helper\Security;

require_once "../../Database/require.php";
require_once "../../Model/DefinesModel.php";
require_once "../../Model/Auths.php";

$Auths = new Auths();
$Defines = new DefinesModel();
if ($_POST['action'] == "saveProjectStatus") {
    $id = $_POST['id'] != 0 ? Security::decrypt($_POST['id']) : 0;


    try {
        $data = [
            'id' => $id,
            'firm_id' => $_SESSION['firm_id'],
            "type_id"  => 5,
            'name' => $_POST['statu_name'],
            'description' => $_POST['description']

        ];
        $lastInsertId = $Defines->saveWithAttr($data) ?? $_POST['id'];
        $message = $id == 0 ? "Proje Durumu başarıyla eklendi" : "Proje Durumu başarı ile güncellendi";
        $status = "success";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        'status' => $status,
        'message' => $message,
        'id' => $lastInsertId
    ];

    echo json_encode($res);
}

if ($_POST['action'] == "deleteProjectStatus") {


    $Auths->hasPermissionReturn("project_status_delete");

    $id = $_POST['id'];
    try {
        $Defines->delete($id);
        $status = "success";
        $message = "Proje Durumu başarıyla silindi";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        'status' => $status,
        'message' => $message
    ];
    echo json_encode($res);
}
