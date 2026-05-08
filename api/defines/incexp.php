<?php
require_once "../../Database/require.php";
require_once "../../Model/DefinesModel.php";
$incexp = new DefinesModel();

if ($_POST["action"] == "saveIncExpType") {
    $id = $_POST["id"];

    try {
        $data = [
            "id" => $id,
            "name" => $_POST["incexp_name"],
            "user_id" => $_SESSION["user"]->id,
            "firm_id" => $_SESSION["firm_id"],
            "type_id" => $_POST["incexp_type"],
            "description" => $_POST["description"]
        ];

        

        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;
    
        $status = "success"; 
        $message = $id > 0 ? "Güncelleme Başarılı" : "Kayıt Başarılı!!" ;

    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
}


if ($_POST["action"] == "deleteIncExpType") {
    $id = $_POST["id"];
    try {
        $incexp->delete($id);
        $status = "success";
        $message = "Gelir/Gider tanımı başarıyla silindi." ;
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