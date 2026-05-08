<?php
require_once '../../Model/Bordro.php';
require_once '../../Database/require.php';
require_once '../../App/Helper/date.php';
require_once '../../App/Helper/helper.php';
require_once '../../App/Helper/security.php';
require_once '../../Model/Auths.php';
require_once '../../App/Helper/financial.php';


use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$Auths = new Auths();
$wagecut = new Bordro();
$financialHelper = new Financial();

if ($_POST['action'] == 'saveWageCut') {



    //Kesinti ekleme yetkisi var mı kontrol et
    $Auths->hasPermissionReturn("income_expense_add_update");

    $id = $_POST['wage_cut_id'] != 0 ? Security::decrypt($_POST['wage_cut_id']) : 0;
    $person_id = Security::decrypt($_POST['person_id_wage_cut']);
    $month = $_POST['wage_cut_month'];
    $year = $_POST['wage_cut_year'];
    $page = $_POST['page'];
    $wagecutData = [];
    $income_expense = [];

    // Sayıları birleştirerek string oluşturun
    $dateString = sprintf('%2d%02d15', $year, $month);

    try {
        $data = [
            'id' => $id,
            "user_id" => $_SESSION['user']->id,
            'person_id' => $person_id,
            'gun' => (int) $dateString,
            "ay" => $month,
            "yil" => $year,
            "kategori" => 15,
            'turu' => $_POST['wage_cut_type'],
            'tutar' => Helper::formattedMoneyToNumber($_POST['wage_cut_amount']),
            'aciklama' => $_POST['wage_cut_description'],
        ];

        $lastInsertId = $wagecut->saveWithAttr($data) ?? $id;

        //Eğer Personel detay sayfasında ise
        if ($page == 'persons/manage') {
            // Son eklenen kaydın bilgileri formatlanır
            $wagecutData = $wagecut->find(Security::decrypt($lastInsertId));

            // Kaydedilen verinin türü getirilir(Ödeme, Kesinti, Gelir) (Gelir)
            $wagecutData->kategori = $financialHelper->getTransactionType($wagecutData->kategori);

            //Tutar formatlanır
            $wagecutData->tutar = Helper::formattedMoney($wagecutData->tutar);

            //Formatlanmış Özet bilgileri getirilir
            $income_expense = $wagecut->sumAllIncomeExpenseFormatted($person_id);

        }

        $status = 'success';
        $message = 'Başarıyla eklendi';

    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $res = [
        'status' => $status,
        'message' => $message,
        'wagecut_data' => $wagecutData,
        'income_expense' => $income_expense
    ];

    echo json_encode($res);
}