<?php
require_once '../../Model/Bordro.php';
require_once '../../Database/require.php';
require_once '../../App/Helper/date.php';
require_once '../../App/Helper/helper.php';


use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$income = new Bordro();

if ($_POST['action'] == 'saveIncome') {
    $id = $_POST['id'];
    $person_id = Security::decrypt($_POST['person_id_income']);
    $month = $_POST['income_month'];
    $year = $_POST['income_year'];

    $page = $_POST['page'];
    $incomeData = [];
    $income_expense = [];

    // Sayıları birleştirerek string oluşturun
    $dateString = sprintf('%2d%02d15', $year, $month);

    $data = [
        'id' => $id,
        'user_id' => $_SESSION['user']->id,
        'person_id' => $person_id,
        'gun' => (int) $dateString,
        'ay' => $month,
        'yil' => $year,
        'kategori' => 1,
        'turu' => $_POST['income_type'],
        'tutar' => Helper::formattedMoneyToNumber($_POST['income_amount']),
        'aciklama' => $_POST['income_description'],
    ];

    $lastInsertId = $income->saveWithAttr($data);


    if ($page == 'persons/manage') {
        // Son eklenen kaydın bilgileri formatlanır
        $incomeData = $income->getPersonIncomeExpensePayment(Security::decrypt($lastInsertId));

        // Kaydedilen verinin türü getirilir(Ödeme, Kesinti, Gelir) (Gelir)
        $incomeData->kategori = Helper::getIncomeExpenseType($incomeData->kategori);

        // Tutar formatlanır
        $incomeData->tutar = Helper::formattedMoney($incomeData->tutar);

        //Personelin toplam gelir ve gider ve bakiyesi getirilir
        $income_expense = $income->sumAllIncomeExpenseFormatted($person_id);
    }


    $status = 'success';
    $message = 'Başarıyla eklendi';

    $res = [
        'status' => $status,
        'message' => $message,
        'income_data' => $incomeData,
        'income_expense' => $income_expense
    ];

    echo json_encode($res);
}
