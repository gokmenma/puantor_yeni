<?php
// Mobil Bağımsız API Dosyası - Proje Gelir/Gider İşlemleri
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/ProjectIncomeExpense.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\Date;

header('Content-Type: application/json');

if (empty($_POST['action'])) {
    echo json_encode(["status" => "error", "message" => "Eksik parametre (action)"]);
    exit;
}

$ProjectIncExp = new ProjectIncomeExpense();

if ($_POST['action'] == "saveTransaction") {
    $id = isset($_POST["id"]) && $_POST["id"] != "0" && $_POST["id"] != "" ? Security::decrypt($_POST["id"]) : 0;
    $project_id = isset($_POST["project_id"]) ? Security::decrypt($_POST["project_id"]) : 0;

    if (!$project_id) {
        echo json_encode(["status" => "error", "message" => "Proje ID bulunamadı"]);
        exit;
    }

    $turu = intval($_POST['type']); // 5: Gelir, 12: Masraf, 14: Puantaj Çalışması
    $kategori = "";
    if ($turu == 5) $kategori = "Proje Alınan Ödeme";
    else if ($turu == 12) $kategori = "Proje Masrafı";
    else if ($turu == 14) $kategori = "Puantaj Çalışması";
    else if ($turu == 6) $kategori = "Projeye Yapılan Ödeme";
    else $kategori = "Diğer";

    $data = [
        "id" => $id,
        "project_id" => $project_id,
        "case_id" => isset($_POST['case_id']) ? Security::decrypt($_POST['case_id']) : 0,
        "tarih" => Date::Ymd($_POST['date']),
        "tutar" => Helper::formattedMoneyToNumber($_POST['amount']),
        "kategori" => $kategori,
        "turu" => $turu,
        "aciklama" => Security::escape($_POST['description'] ?? '')
    ];

    try {
        $lastInsertId = $ProjectIncExp->saveWithAttr($data) ?? $id;
        $status = "success";
        $message = ($id > 0) ? "İşlem başarıyla güncellendi" : "İşlem başarıyla eklendi";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    echo json_encode(['status' => $status, 'message' => $message, 'lastInsertId' => $lastInsertId ?? 0]);
    exit;
}

if ($_POST['action'] == "getTransaction") {
    $id = Security::decrypt($_POST['id']);
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz ID']);
        exit;
    }
    
    $transaction = $ProjectIncExp->find($id);
    
    if ($transaction) {
        $transaction->id = Security::encrypt($transaction->id);
        $transaction->case_id = Security::encrypt($transaction->case_id);
        $transaction->tarih = Date::dmy($transaction->tarih);
        $transaction->tutar = Helper::formattedMoneyWithoutCurrency($transaction->tutar);
        
        echo json_encode(['status' => 'success', 'data' => $transaction]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Kayıt bulunamadı']);
    }
    exit;
}

echo json_encode(["status" => "error", "message" => "Geçersiz işlem"]);
exit;
