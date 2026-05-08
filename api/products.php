<?php

require_once "../Database/db.php";
require_once "../Model/Products.php";

use Database\Db;


$dbInstance = new Db(); // Db sınıfının bir örneğini oluşturuyoruz.
$db = $dbInstance->connect(); // Veritabanı bağlantısını alıyoruz.

$product = new Product();

if ($_POST["action"] == "getProductInfo") {
    $id = $_POST["id"];

    $query = $db->prepare("SELECT * FROM products WHERE id = ?");
    $query->execute([$id]);
    $product = $query->fetch(PDO::FETCH_OBJ);

    echo json_encode($product);
}

if ($_POST["action"] == "saveProduct") {
    $id = $_POST["id"];
    $data = [
        "id" => $id,
        "urun_adi" => $_POST["urun_adi"],
        "stok_kodu" => $_POST["stok_kodu"],
        "birimi" => $_POST["birimi"],
        "alis_fiyati" => $_POST["alis_fiyati"],
        "alis_para_birimi" => $_POST["alis_para_birimi"],
        "satis_fiyati" => $_POST["satis_fiyati"],
        "satis_para_birimi" => $_POST["satis_para_birimi"],
        "aciklama" => $_POST["aciklama"],
    ];

    try {
        $lastInsertId = $product->saveWithAttr($data) ;
        $status = "success";
        if ($id == 0) {
            $message = "Ürün başarıyla kaydedildi.". $lastInsertId;
        } else {
            $message = "Ürün başarıyla güncellendi.";
        }
    } catch (PDOException $e) {
        $status = "error";
        $message =  $e->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message,
        "lastid" => $lastInsertId ?? 0
    ];

    echo json_encode($res);
}

if ($_POST["action"] == "deleteProduct") {
    $id = $_POST["id"];
    try {
        $product->delete($id);
        $status = "success";
        $message = "Ürün başarıyla silindi.";
    } catch (PDOException $e) {
        $status = "error";
        $message =  $e->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message
    ];

    echo json_encode($res);
}