<?php

require_once '../../Database/require.php';
require_once '../../Model/Projects.php';
require_once '../../Model/ProjectIncomeExpense.php';
require_once '../../App/Helper/helper.php';
require_once '../../App/Helper/date.php';
require_once '../../App/Helper/financial.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$project = new Projects();
$incexp = new ProjectIncomeExpense();
$financialHelper = new Financial();

if ($_POST['action'] == 'add_deduction') {
    $page = isset($_POST["page"]) ? $_POST["page"] : "project/list";
    $summary = null;
    $last_deduction = null;


    $id = $_POST['deduction_id'];
    $project_id = Security::decrypt($_POST['deduction_project_id']);

    $data = [
        'id' => $id,
        'project_id' => $project_id,
        "case_id" => Security::decrypt($_POST['deduction_cases']),
        'tarih' => Date::Ymd($_POST['deduction_date']),
        'tutar' => Helper::formattedMoneyToNumber($_POST['deduction_amount']),
        'kategori' => "Proje Kesinti",
        'turu' => 12,
        'aciklama' => $_POST['deduction_description']
    ];

    try {
        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;


        //Projenin kendi sayfasında hakediş, kesinti ve Masraf bilgilerin göstermek için, 
        //projeler sayfasında gerek yok
        if ($page == 'projects/manage') {
            $last_deduction = $incexp->find(Security::decrypt($lastInsertId));
            //id'yi şifrele
            $last_deduction->id = Security::encrypt($last_deduction->id);
            $last_deduction->tarih = Date::dmy($last_deduction->tarih);
            $last_deduction->tutar = Helper::formattedMoney($last_deduction->tutar);
            $last_deduction->turu = Helper::getIconWithColorByType($last_deduction->turu) . $financialHelper::getTransactionType($last_deduction->turu);

            $summary = $incexp->sumAllIncomeExpense($project_id);

            $summary->balance = Helper::formattedMoney($incexp->getBalance($project_id));
            $summary->kesinti = Helper::formattedMoney($summary->kesinti);
        }

        $status = 'success';
        $message = 'Kesinti başarı ile eklendi';
    } catch (PDOException $ex) {
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

    echo json_encode($res);
}
