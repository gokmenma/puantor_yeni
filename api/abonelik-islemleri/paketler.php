<?php
require_once "../../Database/require.php";
require_once "../../Model/AbonelikPaketleriModel.php";
require_once "../../Model/Auths.php";
require_once "../../App/Helper/security.php";

use App\Helper\Security;

$paketModel = new AbonelikPaketleriModel();
$Auths = new Auths();
$Auths->hasPermissionReturn("aboneler_paketleri");

if (isset($_POST["action"]) && $_POST["action"] == "savePackage") {
    $encryptedId = $_POST["id"] ?? "";
    $id = 0;
    if ($encryptedId && $encryptedId !== "0") {
        $id = Security::safeDecrypt($encryptedId);
    }

    try {
        $data = [
            "ad" => $_POST["ad"],
            "fiyat" => $_POST["fiyat"],
            "sure" => $_POST["sure"],
            "firma_hakki" => $_POST["firma_hakki"],
            "alt_kullanici_hakki" => $_POST["alt_kullanici_hakki"],
            "ozellikler" => $_POST["ozellikler"] ?? "",
            "aktif_mi" => $_POST["aktif_mi"],
            "kullaniciya_goster_mi" => isset($_POST["kullaniciya_goster_mi"]) && $_POST["kullaniciya_goster_mi"] == "1" ? 1 : 0
        ];

        if ($id > 0) {
            $data["id"] = $id;
        }

        $resultId = $paketModel->saveWithAttr($data);
    
        $status = "success"; 
        $message = $id > 0 ? "Paket başarıyla güncellendi." : "Yeni paket başarıyla tanımlandı." ;

    } catch (Exception $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
    exit();
}

if (isset($_POST["action"]) && $_POST["action"] == "saveModules") {
    $id = Security::safeDecrypt($_POST["paket_id"] ?? "");
    if (!$id) {
        echo json_encode(["status" => "error", "message" => "Geçersiz paket."]);
        exit();
    }

    try {
        if (isset($_POST["unlimited_modules"]) && $_POST["unlimited_modules"] == "1") {
            $modul_auth_ids = null;
        } else {
            $modules = array_map('intval', $_POST["modules"] ?? []);
            $modul_auth_ids = implode(",", array_filter($modules));
        }

        $paketModel->saveWithAttr([
            "id" => $id,
            "modul_auth_ids" => $modul_auth_ids
        ]);

        $status = "success";
        $message = "Paket modülleri başarıyla güncellendi.";
    } catch (Exception $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    echo json_encode(["status" => $status, "message" => $message]);
    exit();
}

if (isset($_POST["action"]) && $_POST["action"] == "deletePackage") {
    $encryptedId = $_POST["id"] ?? "";
    try {
        if ($paketModel->softDeletePackage($encryptedId)) {
            $status = "success";
            $message = "Paket başarıyla silindi.";
        } else {
            $status = "error";
            $message = "Kayıt bulunamadı veya silinemedi.";
        }
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
    exit();
}
