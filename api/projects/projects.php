<?php
require_once "../../Database/require.php";
require_once "../../Model/Projects.php";
require_once "../../Model/ProjectIncomeExpense.php";
require_once "../../App/Helper/security.php";
require_once "../../App/Helper/helper.php";

use App\Helper\Helper;
use App\Helper\Security;

$Projects = new Projects();
$ProjectIncExp = new ProjectIncomeExpense();

if ($_POST['action'] == "saveProject") {
    $id = $_POST["id"] != 0 ? Security::decrypt($_POST["id"]) : 0;

    $data = [
        "id" => $id,
        "firm_id" => $_SESSION['firm_id'],
        "type" => $_POST['project_type'],
        'project_name' => Security::escape($_POST['project_name']),
        'start_date' => Security::escape($_POST['start_date']),
        'end_date' => Security::escape($_POST['end_date']),
        'city' => Security::escape($_POST['project_city']),
        'town' => Security::escape($_POST['project_town']),
        'status' => Security::escape($_POST['project_status']),
        'email' => Security::escape($_POST['email']),
        'phone' => Security::escape($_POST['phone']),
        'account_number' => Security::escape($_POST['account_number']),
        'address' => Security::escape($_POST['address']),
    ];
    //Yeni kayıt esnasında başlangıç bütçesi alınır
    if (isset($_POST["budget"])) {
        $data["budget"] = Helper::formattedMoneyToNumber($_POST["budget"]);
    }
    ;

    //firma adı boş değilse
    if (!empty($_POST['project_company'])) {
        $data['company_id'] = Security::decrypt($_POST['project_company']);
    }




    try {
        $lastInsertId = $Projects->saveWithAttr($data) ?? $_POST['id'];
        $status = "success";
        if ($id > 0) {
            $message = "Proje başarıyla güncellendi";
        } else {
            $message = "Proje başarıyla eklendi";
        }
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'lastInsertId' => $lastInsertId ?? 0
    ];
    echo json_encode($res);
}

if ($_POST['action'] == "deleteProject") {
    $id = Security::decrypt($_POST['id']);
    //projede kayıtlı çalışma var mı kontrol et
    $isExistPuantaj = $Projects->isExistPuantaj($id);
    if ($isExistPuantaj) {
        $status = "error";
        $message = "Projede kayıtlı çalışma var. Projeyi silemezsiniz.";
        $res = [
            'status' => $status,
            'message' => $message,
        ];
        echo json_encode($res);
        exit;
    }
    try {
        $db->beginTransaction();
        $Projects->delete($_POST['id']);
        $status = "success";
        $message = "Proje başarıyla silindi";
        $db->commit();
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
        $db->rollBack();
    }

    $res = [
        'status' => $status,
        'message' => $message,
    ];
    echo json_encode($res);
}

if ($_POST['action'] == "deleteProjectAction") {
    //BaseModeldeki delete fonksiyonunda id decrypt edildiği için burada decrypt etmeye gerek yok
    $id = Security::decrypt($_POST['id']);
    $project_id = Security::decrypt($_GET['project_id']);

    try {
        $ProjectIncExp->delete($_POST['id']);
        $status = "success";
        $message = "Proje Hareketi silindi";
        
        //Formatlanmış gelir gider - bakiye bilgileri
        $summary = $ProjectIncExp->sumAllIncomeExpenseFormatted($project_id);

         //Projenin hakediş tamanlanma durumunu güncelle
         $progress_range= $ProjectIncExp->getProgressPaymentRange($project_id);
      
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'summary' => $summary,
        "progress" => $progress_range,

    ];
    echo json_encode($res);
}