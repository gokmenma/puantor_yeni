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

if ($_POST['action'] == 'add_payment') {
    $page = isset($_POST["page"]) ? $_POST["page"] : "projects/list";
    $summary = null;
    $last_payment = null;


    $id = $_POST['payment_id'];
    $project_id = Security::decrypt($_POST['payment_project_id']);

    $data = [
        'id' => $id,
        'project_id' => $project_id,
        "case_id" => Security::decrypt($_POST['payment_cases']),
        'tarih' => Date::Ymd($_POST['payment_date']),
        'tutar' => Helper::formattedMoneyToNumber($_POST['payment_amount']),
        'kategori' => "Proje Alınan Ödeme",
        'turu' => 5,
        'aciklama' => $_POST['payment_description']
    ];

    try {
        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;


        //Projenin kendi sayfasında hakediş, kesinti ve ödeme bilgilerin göstermek için, 
        //projeler sayfasında gerek yok
        if ($page == 'projects/manage') {
            $last_payment = $incexp->find(Security::decrypt($lastInsertId));
            //id'yi şifrele
            $last_payment->id = Security::encrypt($last_payment->id);
            $last_payment->tarih = Date::dmy($last_payment->tarih);
            $last_payment->tutar = Helper::formattedMoney($last_payment->tutar);
            $last_payment->turu = Helper::getIconWithColorByType($last_payment->turu) . $financialHelper::getTransactionType($last_payment->turu);

            $summary = $incexp->sumAllIncomeExpense($project_id);

            //Bakiyeyi ve Ödeme toplamlarını güncellemek için
            $summary->balance = Helper::formattedMoney($incexp->getBalance($project_id));
            $summary->gelir = Helper::formattedMoney($summary->gelir);
        }

        $status = 'success';
        $message = 'Ödeme başarı ile eklendi';
    } catch (PDOException $ex) {
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

    echo json_encode($res);
}
