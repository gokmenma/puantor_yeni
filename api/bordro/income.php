<?php
require_once '../../Model/Bordro.php';
require_once '../../Database/require.php';
require_once '../../App/Helper/date.php';


use App\Helper\Date;
use App\Helper\Helper;

$income = new Bordro();

if ($_POST['action'] == 'saveIncome') {
    $id = $_POST['id'];
    $month = $_POST['income_month'];
    $year = $_POST['income_year'];

    // Sayıları birleştirerek string oluşturun
    $dateString = sprintf('%2d%02d15',  $year,$month);

    $data = [
        'id' => $id,
        "user_id" => $_SESSION['user']->id,
        'person_id' => $_POST['person_id_income'],
        'gun' => (int)$dateString,
        "ay" => $month,
        "yil" => $year,
        "kategori" => 1,//Gelir
        'turu' => $_POST['income_type'],
        'tutar' => Helper::formattedMoneyToNumber($_POST['income_amount']),
        'aciklama' => $_POST['income_description'],
    ];

    $income->saveWithAttr($data);

    $status = 'success';
    $message = 'Başarıyla eklendi';

    $res = [
        'status' => $status,
        'message' => $message,
    ];

    echo json_encode($res);
}