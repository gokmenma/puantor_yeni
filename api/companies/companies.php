<?php
require_once "../../Database/require.php";
require_once "../../Model/Company.php";


use App\Helper\Security;


$company = new Company();

if ($_POST["action"] == "saveCompany") {
    $id = $_POST["id"] != 0 ? Security::decrypt($_POST["id"]) : 0;

    $data = [
        "id" => $id,
        "user_id" => $_SESSION["user"]->id,
        "company_name" => $_POST["company_name"],
        "yetkili" => $_POST["yetkili"],
        "tax_office" => $_POST["tax_office"],
        "tax_number" => $_POST["tax_number"],
        "account_number" => $_POST["account_number"],
        "phone" => $_POST["phone"],
        "email" => $_POST["email"],
        "city" => $_POST["firm_cities"],
        "town" => $_POST["firm_towns"],
        "address" => $_POST["address"],

        "description" => $_POST["description"],
    ];


    try {
        $lastInsertId = $company->saveWithAttr($data);
        $status = "success";
        if ($id == 0) {
            $message = "Firma başarıyla kaydedildi.";
        } else {
            $message = "Firma başarıyla güncellendi.";
        }
    } catch (PDOException $e) {
        $status = "error";
        $message =  $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($res);
}

if ($_POST["action"] == "deleteCompany") {
    $id = $_POST["id"];
    try {
        $company->delete($id);
        $status = "success";
        $message = "Firma başarıyla silindi.";
    } catch (PDOException $e) {
        $status = "error";
        $message =  $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($res);
}
