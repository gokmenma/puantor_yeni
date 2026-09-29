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

if (isset($_POST['action']) && $_POST['action'] == 'add_deduction') {
    $page = isset($_POST["page"]) ? $_POST["page"] : "project/list";
    $summary = null;
    $last_deduction = null;

    $id = !empty($_POST['deduction_id']) ? (int)Security::safeDecrypt($_POST['deduction_id']) : 0;
    $project_id = (int)Security::safeDecrypt($_POST['deduction_project_id'] ?? '');

    $data = [
        'id' => $id,
        'project_id' => $project_id,
        'firm_id' => $_SESSION['firm_id'] ?? 0,
        "case_id" => (int)Security::safeDecrypt($_POST['deduction_cases'] ?? 0),
        'tarih' => Date::Ymd($_POST['deduction_date'] ?? ''),
        'tutar' => Helper::formattedMoneyToNumber($_POST['deduction_amount'] ?? 0),
        'kategori' => 0,
        'turu' => 12,
        'aciklama' => Security::escape($_POST['deduction_description'] ?? '')
    ];

    try {
        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;

        $logAction = ($id > 0) ? 'update_deduction' : 'add_deduction';
        $logText = ($id > 0) ? "güncellendi" : "kaydedildi";
        ActivityLogModel::log('project', $logAction, "Proje ID: {$project_id} için " . Helper::formattedMoney($data['tutar']) . " kesinti {$logText}.");

        //Projenin kendi sayfasında hakediş, kesinti ve Masraf bilgilerin göstermek için, 
        //projeler sayfasında gerek yok
        if ($page == 'projects/manage') {
            $recordId = ($id > 0) ? $id : (is_numeric($lastInsertId) ? (int)$lastInsertId : (int)Security::safeDecrypt($lastInsertId));
            $last_deduction = $incexp->find($recordId);
            if ($last_deduction) {
                //id'yi şifrele
                $last_deduction->id = Security::encrypt($last_deduction->id);
                $last_deduction->tarih = Date::dmy($last_deduction->tarih);
                $last_deduction->tutar = Helper::formattedMoney($last_deduction->tutar);
                $last_deduction->turu = Helper::getIconWithColorByType($last_deduction->turu) . $financialHelper::getTransactionType($last_deduction->turu);
            }

            $summary = $incexp->sumAllIncomeExpense($project_id);

            $summary->balance = Helper::formattedMoney($incexp->getBalance($project_id));
            $summary->kesinti = Helper::formattedMoney($summary->kesinti);
        }

        $status = 'success';
        $message = 'Kesinti başarı ile eklendi';
    } catch (\Throwable $ex) {
        $status = 'error';
        $message = $ex->getMessage();
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'last_deduction' => $last_deduction,
        'summary' => $summary,
        "id" => Security::decrypt($lastInsertId)

    ];

    header('Content-Type: application/json');
    echo json_encode($res);
}

