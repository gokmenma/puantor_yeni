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

if (isset($_POST['action']) && $_POST['action'] == 'add_progress_payment') {
    $page = $_POST['page'] ?? '';
    $summary = null;
    $last_progress_payment = null;
    $progress_range = null;

    $id = !empty($_POST['progress_payment_id']) ? (int)Security::safeDecrypt($_POST['progress_payment_id']) : 0;
    $project_id = (int)Security::safeDecrypt($_POST['progress_payment_project_id'] ?? '');

    $data = [
        'id' => $id,
        'project_id' => $project_id,
        'firm_id' => $_SESSION['firm_id'] ?? 0,
        'case_id' => (int)Security::safeDecrypt($_POST['progress_payment_cases'] ?? 0),
        'tarih' => Date::Ymd($_POST['progress_payment_date'] ?? ''),
        'tutar' => Helper::formattedMoneyToNumber($_POST['progress_payment_amount'] ?? 0),
        'turu' => 10, //app/Helper/financial.php'de tanımlı olan 10 numaralı hakediş türü
        'kategori' => 0,
        'aciklama' => Security::escape($_POST['progress_payment_description'] ?? '')
    ];

    try {
        $lastInsertId = $incexp->saveWithAttr($data) ?? $id;

        $logAction = ($id > 0) ? 'update_progress_payment' : 'add_progress_payment';
        $logText = ($id > 0) ? "güncellendi" : "kaydedildi";
        ActivityLogModel::log('project', $logAction, "Proje ID: {$project_id} için " . Helper::formattedMoney($data['tutar']) . " hakediş {$logText}.");

        //Projenin kendi sayfasında hakediş, kesinti ve ödeme bilgilerin göstermek için,
        //projeler sayfasında gerek yok
        if ($page == 'projects/manage') {
            $recordId = ($id > 0) ? $id : (is_numeric($lastInsertId) ? (int)$lastInsertId : (int)Security::safeDecrypt($lastInsertId));
            $last_progress_payment = $incexp->find($recordId);
            if ($last_progress_payment) {
                //id'yi şifrele
                $last_progress_payment->id = Security::encrypt($last_progress_payment->id);
                $last_progress_payment->tarih = Date::dmy($last_progress_payment->tarih);
                $last_progress_payment->tutar = Helper::formattedMoney($last_progress_payment->tutar);
                $last_progress_payment->turu = Helper::getIconWithColorByType($last_progress_payment->turu) . $financialHelper::getTransactionType($last_progress_payment->turu);
            }

            //Özet Gösterge için
            $summary = $incexp->sumAllIncomeExpense($project_id);
            
            //Bakiyeyi ve Hakediş toplamlarını güncellemek için
            $summary->balance = Helper::formattedMoney($incexp->getBalance($project_id));
            $summary->hakedis = Helper::formattedMoney($summary->hakedis);

            //Projenin hakediş tamanlanma durumunu güncelle
            $progress_range = $incexp->getProgressPaymentRange($project_id);
        }

        $status = 'success';
        $message = 'Hakediş başarı ile eklendi';
    } catch (\Throwable $ex) {
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

    header('Content-Type: application/json');
    echo json_encode($res);
}

