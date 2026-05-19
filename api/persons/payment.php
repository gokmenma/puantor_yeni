<?php
require_once '../../Model/Bordro.php';
require_once '../../Model/Puantaj.php';
require_once '../../Database/require.php';
require_once '../../App/Helper/date.php';
require_once '../../App/Helper/helper.php';
require_once '../../App/Helper/security.php';
require_once "../../Model/Auths.php";
require_once "../../App/Helper/financial.php";

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;
$Auths = new Auths();

$payment = new Bordro();
$puantaj = new Puantaj();
$financialHelper = new Financial();


$Auths->checkFirmReturn();

if ($_POST['action'] == 'savePayment') {
    //Ödeme Ekleme yetkisi var mı kontrol et
    $Auths->hasPermissionReturn("make_staff_payment");

    $id = !empty($_POST['id']) ? Security::safeDecrypt($_POST['id']) : 0;
    $person_id = Security::decrypt($_POST['person_id_payment']);
    $month = $_POST['payment_month'];
    $year = $_POST['payment_year'];

    $page = $_POST['page'];
    $last_payment = [];
    $income_expense = [];


    // Sayıları birleştirerek string oluşturun
    $dateString = sprintf('%2d%02d15', $year, $month);
    $tablename = !empty($_POST['tablename']) ? $_POST['tablename'] : 'maas_gelir_kesinti';
    $case_id = !empty($_POST['payment_cases']) ? Security::decrypt($_POST['payment_cases']) : 0;

    try {
        if ($tablename === 'case_transactions') {
            require_once '../../Model/CaseTransactions.php';
            $ct = new CaseTransactions();
            
            $ct_data = [
                'id' => $id,
                'case_id' => $case_id,
                'amount' => Helper::formattedMoneyToNumber($_POST['payment_amount']),
                'description' => $_POST['payment_description'],
                'date' => sprintf('%04d-%02d-15', $year, $month),
                'type_id' => 2, // Gider/Ödeme
                'users_type_id' => 7, // Personel Ödemesi
                'person_id' => $person_id,
            ];
            $lastInsertId = $ct->saveWithAttr($ct_data);
        } else {
            $data = [
                'id' => $id,
                'user_id' => $_SESSION['user']->id,
                'person_id' => $person_id,
                'gun' => (int) $dateString,
                'ay' => $month,
                'yil' => $year,
                'kategori' => 7,  // Personel Ödemesi
                'case_id' => $case_id,
                'turu' => $_POST['payment_type'],
                'tutar' => Helper::formattedMoneyToNumber($_POST['payment_amount']),
                'aciklama' => $_POST['payment_description'],
            ];
            $lastInsertId = $payment->saveWithAttr($data);
        }

        //Personel düzenleme sayfasında, ödeme eklendiği zaman, sayfa yenilenmeden kayıtları göstermek için
        if ($page == 'persons/manage') {
            if ($tablename === 'case_transactions') {
                $last_payment = (object)[
                    'kategori' => 'Personel Ödemesi',
                    'gun' => sprintf('%02d-%02d-%04d', 15, $month, $year),
                    'tutar' => Helper::formattedMoney(Helper::formattedMoneyToNumber($_POST['payment_amount']))
                ];
            } else {
                // Son eklenen kaydın bilgilerini getirir
                $last_payment = $payment->getPersonIncomeExpensePayment(Security::decrypt($lastInsertId));
                if ($last_payment) {
                    $last_payment->kategori = $financialHelper->getTransactionType($last_payment->kategori);
                    $last_payment->gun = Date::dmY($last_payment->gun);
                    $last_payment->tutar = Helper::formattedMoney($last_payment->tutar);
                }
            }

            // Personelin, maas_gelir_kesinti tablosundaki ödeme, kesinti ve gelir toplamlarını getirir
            $income_expense = $payment->sumAllIncomeExpense($person_id);

            $income_expense->total_income = Helper::formattedMoney($income_expense->total_income ?? 0);  // Toplam gelir
            $income_expense->total_expense = Helper::formattedMoney($income_expense->total_expense ?? 0);  // Toplam gider
            $income_expense->balance = Helper::formattedMoney($payment->getBalance($person_id));  // Bakiye
        }

        $status = 'success';
        $message = $id > 0 ? 'Başarıyla güncellendi' : 'Başarıyla eklendi';
    } catch (PDOException $e) {
        $status = 'error';
        $message = $e->getMessage();
    } catch (Exception $e) {
        $status = 'error';
        $message = $e->getMessage();
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'payment' => $last_payment ,
        'income_expense' => $income_expense,
    ];

    echo json_encode($res);
}


