<?php

require_once "../Database/db.php";
require_once "../Model/DefinesModel.php";

use Database\Db;


$dbInstance = new Db(); // Db sınıfının bir örneğini oluşturuyoruz.
$db = $dbInstance->connect(); // Veritabanı bağlantısını alıyoruz.

$define = new DefinesModel();

//Servis Konusu Tanımlama
if ($_POST["action"] == "saveServiceHead") {
    $id = $_POST["id"];

    try {
        $data = [
            "id" => $id,
            "title" => $_POST["service_head"],
            "regdate" => date("Y-m-d H:i:s"),
            "statu" => 1, // Diğer tanımlamalarla karışmaması için 
            "description" => $_POST["description"],
        ];
        $lastInsertId = $define->saveWithAttr($data);

        $status = "success";
        if ($id == 0) {
            $message = "Servis Konusu başarıyla kaydedildi.";
        } else {
            $message = "Servis Konusu başarıyla güncellendi.";
        }
        //code...
    } catch (PDOException $ex) {
        $status = "error";
        $message = "Servis Konusu kaydedilirken bir hata oluştu.";
    }

    $res = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($res);
}

//Servis Konusu Silme
if ($_POST["action"] == "deleteServiceHead") {
    $id = $_POST["id"];
    try {
        $define->delete($id);
        $status = "success";
        $message = "Servis Konusu başarıyla silindi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = "Servis Konusu silinirken bir hata oluştu.";
    }

    $res = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($res);
}
