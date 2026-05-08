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

if ($_POST['action'] == 'add_expense') {
    $page = isset($_GET["p"]) ? $_GET["p"] : "project/list";
    $summary = null;
    $last_expense = null;


    $id = $_POST['expense_id'];
    $project_id = Security::decrypt($_POST['expense_project_id']);

    $data = [
        'id' => $id,
        'project_id' => $project_id,
        "case_id" => Security::decrypt($_POST['expense_cases']),
        'tarih' => Date::Ymd($_POST['expense_date']),
        'tutar' => Helper::formattedMoneyToNumber($_POST['expense_amount']),
        'kategori' => "Proje Masrafı",
        'turu' => 12,
        'aciklama' => $_POST['expense_description']
    ];

    try {
        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;


        //Projenin kendi sayfasında hakediş, kesinti ve Masraf bilgilerin göstermek için, 
        //projeler sayfasında gerek yok
        if ($page == 'project/manage') {
            $last_expense = $incexp->find(Security::decrypt($lastInsertId));
            //id'yi şifrele
            $last_expense->id = Security::encrypt($last_expense->id);
            $last_expense->tarih = Date::dmy($last_expense->tarih);
            $last_expense->tutar = Helper::formattedMoney($last_expense->tutar);
            $last_expense->turu = $financialHelper::getTransactionType($last_expense->turu);

            $summary = $incexp->sumAllIncomeExpense($project_id);

            // $summary->balance = Helper::formattedMoney($summary->hakedis - $summary->kesinti - $summary->odeme);
            // $summary->hakedis = Helper::formattedMoney($summary->hakedis);
            // $summary->kesinti = Helper::formattedMoney($summary->kesinti);
            // $summary->odeme = Helper::formattedMoney($summary->odeme);
        }

        $status = 'success';
        $message = 'Masraf başarı ile eklendi';
    } catch (PDOException $ex) {
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

    echo json_encode($res);
}
