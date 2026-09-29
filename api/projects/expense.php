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

if (isset($_POST['action']) && $_POST['action'] == 'add_expense') {
    $page = isset($_POST["page"]) ? $_POST["page"] : (isset($_GET["p"]) ? $_GET["p"] : "project/list");
    $summary = null;
    $last_expense = null;

    $id = !empty($_POST['expense_id']) ? (int)Security::safeDecrypt($_POST['expense_id']) : 0;
    $project_id = (int)Security::safeDecrypt($_POST['expense_project_id'] ?? '');

    $data = [
        'id' => $id,
        'project_id' => $project_id,
        'firm_id' => $_SESSION['firm_id'] ?? 0,
        "case_id" => (int)Security::safeDecrypt($_POST['expense_cases'] ?? 0),
        'tarih' => Date::Ymd($_POST['expense_date'] ?? ''),
        'tutar' => Helper::formattedMoneyToNumber($_POST['expense_amount'] ?? 0),
        'kategori' => 0,
        'turu' => 11,
        'aciklama' => Security::escape($_POST['expense_description'] ?? '')
    ];

    try {
        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;

        $logAction = ($id > 0) ? 'update_expense' : 'add_expense';
        $logText = ($id > 0) ? "güncellendi" : "kaydedildi";
        ActivityLogModel::log('project', $logAction, "Proje ID: {$project_id} için " . Helper::formattedMoney($data['tutar']) . " masraf {$logText}.");

        //Projenin kendi sayfasında hakediş, kesinti ve Masraf bilgilerin göstermek için, 
        //projeler sayfasında gerek yok
        if ($page == 'projects/manage' || $page == 'project/manage') {
            $recordId = ($id > 0) ? $id : (is_numeric($lastInsertId) ? (int)$lastInsertId : (int)Security::safeDecrypt($lastInsertId));
            $last_expense = $incexp->find($recordId);
            if ($last_expense) {
                //id'yi şifrele
                $last_expense->id = Security::encrypt($last_expense->id);
                $last_expense->tarih = Date::dmy($last_expense->tarih);
                $last_expense->tutar = Helper::formattedMoney($last_expense->tutar);
                $last_expense->turu = Helper::getIconWithColorByType($last_expense->turu) . $financialHelper::getTransactionType($last_expense->turu);
            }

            $summary = $incexp->sumAllIncomeExpense($project_id);

            $summary->balance = Helper::formattedMoney($incexp->getBalance($project_id));
            $summary->kesinti = Helper::formattedMoney($summary->kesinti);
        }

        $status = 'success';
        $message = 'Masraf başarı ile eklendi';
    } catch (\Throwable $ex) {
        $status = 'error';
        $message = $ex->getMessage();
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'last_expense' => $last_expense,
        'summary' => $summary,
        "page" => $page

    ];

    header('Content-Type: application/json');
    echo json_encode($res);
}

