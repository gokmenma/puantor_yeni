<?php
// Mobil Bağımsız API Dosyası (Proxy Hatalarını Önlemek İçin)
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(dirname(__DIR__))));
}

require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/Model/ProjectIncomeExpense.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;
use App\Helper\Security;

header('Content-Type: application/json');

if (empty($_POST['action'])) {
    echo json_encode(["status" => "error", "message" => "Eksik parametre (action)"]);
    exit;
}

$Projects = new Projects();
$ProjectIncExp = new ProjectIncomeExpense();

if ($_POST['action'] == "saveProject") {
    $id = $_POST["id"] != 0 ? Security::decrypt($_POST["id"]) : 0;

    $data = [
        "id" => $id,
        "firm_id" => $_SESSION['firm_id'] ?? 0,
        "type" => $_POST['project_type'] ?? 1,
        'project_name' => Security::escape($_POST['project_name'] ?? ''),
        'start_date' => Security::escape($_POST['start_date'] ?? ''),
        'end_date' => Security::escape($_POST['end_date'] ?? ''),
        'city' => Security::escape($_POST['project_city'] ?? ''),
        'town' => Security::escape($_POST['project_town'] ?? ''),
        'status' => Security::escape($_POST['project_status'] ?? ''),
        'email' => Security::escape($_POST['email'] ?? ''),
        'phone' => Security::escape($_POST['phone'] ?? ''),
        'account_number' => Security::escape($_POST['account_number'] ?? ''),
        'address' => Security::escape($_POST['address'] ?? ''),
        'notes' => Security::escape($_POST['project'] ?? ''),
    ];

    if (isset($_POST["budget"])) {
        $data["budget"] = Helper::formattedMoneyToNumber($_POST["budget"]);
    }

    if (!empty($_POST['project_company'])) {
        $data['company_id'] = Security::decrypt($_POST['project_company']);
    }

    try {
        $lastInsertId = $Projects->saveWithAttr($data) ?? $_POST['id'];
        $status = "success";
        $message = ($id > 0) ? "Proje başarıyla güncellendi" : "Proje başarıyla eklendi";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    echo json_encode(['status' => $status, 'message' => $message, 'lastInsertId' => $lastInsertId ?? 0]);
    exit;
}

if ($_POST['action'] == "getProject") {
    $id = Security::decrypt($_POST['id']);
    
    if (!$id) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz ID veya yetki.']);
        exit;
    }
    
    $project = $Projects->find($id);
    
    if ($project) {
        $project->id = Security::encrypt($project->id);
        $project->company_id = Security::encrypt($project->company_id);
        
        require_once ROOT . "/App/Helper/cities.php";
        $cities = new Cities();
        $project->town_name = $cities->getTownName($project->town);
        
        echo json_encode(['status' => 'success', 'data' => $project]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Proje bulunamadı']);
    }
    exit;
}

echo json_encode(["status" => "error", "message" => "Geçersiz işlem"]);
exit;
