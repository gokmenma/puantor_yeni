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

if ($_POST['action'] == 'add_progress_payment') {
    $page = $_POST['page'];
    $summary = null;
    $last_progress_payment = null;
    $progress_range=null;

    //$id = Security::decrypt($_POST['progress_payment_id']);
    $id = $_POST['progress_payment_id'];
    $project_id = Security::decrypt($_POST['progress_payment_project_id']);

    $data = [
        'id' => $id,
        'project_id' => $project_id,
        'firm_id' => $_SESSION['firm_id'],
        'case_id' => Security::decrypt($_POST['progress_payment_cases']),
        'tarih' => Date::Ymd($_POST['progress_payment_date']),
        'tutar' => Helper::formattedMoneyToNumber($_POST['progress_payment_amount']),
        'turu' => 10, //app/Helper/financial.php'de tanımlı olan 10 numaralı hakediş türü
        'kategori' => 'Proje Hakediş',
        'aciklama' => $_POST['progress_payment_description']
    ];

    try {
        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;


        //Projenin kendi sayfasında hakediş, kesinti ve ödeme bilgilerin göstermek için,
        //projeler sayfasında gerek yok
        if ($page == 'projects/manage') {
            $last_progress_payment = $incexp->find(Security::decrypt($lastInsertId));
            //id'yi şifrele
            $last_progress_payment->id = Security::encrypt($last_progress_payment->id);
            $last_progress_payment->tarih = Date::dmy($last_progress_payment->tarih);
            $last_progress_payment->tutar = Helper::formattedMoney($last_progress_payment->tutar);
            $last_progress_payment->turu = Helper::getIconWithColorByType($last_progress_payment->turu) . $financialHelper::getTransactionType($last_progress_payment->turu);

            //Özet Gösterge için
            $summary = $incexp->sumAllIncomeExpense($project_id);
            
            //Bakiyeyi ve Hakediş toplamlarını güncellemek için
            $summary->balance = Helper::formattedMoney($incexp->getBalance($project_id));
            $summary->hakedis = Helper::formattedMoney($summary->hakedis);

            //Projenin hakediş tamanlanma durumunu güncelle
           $progress_range= $incexp->getProgressPaymentRange($project_id);


        }

        $status = 'success';
        $message = 'Hakediş başarı ile eklendi';
    } catch (PDOException $ex) {
        $status = 'error';
        $message = $ex->getMessage();
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'progress_payment' => $last_progress_payment,
        'summary' => $summary,
        "progress" => $progress_range,
    ];

    echo json_encode($res);
}
