<?php

require_once '../../Database/require.php';
require_once '../../Model/Projects.php';
require_once '../../Model/ProjectIncomeExpense.php';
require_once '../../Model/ActivityLogModel.php';
require_once '../../App/Helper/helper.php';
require_once '../../App/Helper/date.php';
require_once '../../App/Helper/financial.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$project = new Projects();
$incexp = new ProjectIncomeExpense();
$financialHelper = new Financial();

if (isset($_POST['action']) && $_POST['action'] == 'add_payment') {
    $page = isset($_POST["page"]) ? $_POST["page"] : "projects/list";
    $summary = null;
    $last_payment = null;

    $id = !empty($_POST['payment_id']) ? (int)Security::safeDecrypt($_POST['payment_id']) : 0;
    $project_id = (int)Security::safeDecrypt($_POST['payment_project_id'] ?? '');

    $data = [
        'id' => $id,
        'project_id' => $project_id,
        'firm_id' => $_SESSION['firm_id'] ?? 0,
        "case_id" => (int)Security::safeDecrypt($_POST['payment_cases'] ?? 0),
        'tarih' => Date::Ymd($_POST['payment_date'] ?? ''),
        'tutar' => Helper::formattedMoneyToNumber($_POST['payment_amount'] ?? 0),
        'kategori' => 0,
        'turu' => 5,
        'aciklama' => Security::escape($_POST['payment_description'] ?? '')
    ];

    try {
        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;

        $logAction = ($id > 0) ? 'update_payment' : 'add_payment';
        $logText = ($id > 0) ? "güncellendi" : "kaydedildi";
        ActivityLogModel::log('project', $logAction, "Proje ID: {$project_id} için " . Helper::formattedMoney($data['tutar']) . " ödeme {$logText}.");

        //Projenin kendi sayfasında hakediş, kesinti ve ödeme bilgilerin göstermek için, 
        //projeler sayfasında gerek yok
        if ($page == 'projects/manage') {
            $recordId = ($id > 0) ? $id : (is_numeric($lastInsertId) ? (int)$lastInsertId : (int)Security::safeDecrypt($lastInsertId));
            $last_payment = $incexp->find($recordId);
            if ($last_payment) {
                //id'yi şifrele
                $last_payment->id = Security::encrypt($last_payment->id);
                $last_payment->tarih = Date::dmy($last_payment->tarih);
                $last_payment->tutar = Helper::formattedMoney($last_payment->tutar);
                $last_payment->turu = Helper::getIconWithColorByType($last_payment->turu) . $financialHelper::getTransactionType($last_payment->turu);
            }

            $summary = $incexp->sumAllIncomeExpense($project_id);

            //Bakiyeyi ve Ödeme toplamlarını güncellemek için
            $summary->balance = Helper::formattedMoney($incexp->getBalance($project_id));
            $summary->gelir = Helper::formattedMoney($summary->gelir);
        }

        $status = 'success';
        $message = 'Ödeme başarı ile eklendi';
    } catch (\Throwable $ex) {
        $status = 'error';
        $message = $ex->getMessage();
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'last_payment' => $last_payment,
        'summary' => $summary,
        "page" => $page

    ];

    header('Content-Type: application/json');
    echo json_encode($res);
}

