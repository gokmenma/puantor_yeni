<?php

use App\Helper\Helper;
use App\Helper\Security;

require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/MyFirmModel.php';
require_once ROOT . '/Model/Persons.php';


$Persons = new Persons();
$Bordro = new Bordro();
$MyFirm = new MyFirmModel();

$firm_id = $_SESSION['firm_id'];

$firm = $MyFirm->find($firm_id);
$firm_name = $firm->firm_name;
$firm_email = $firm->email;

$personel_id = Security::decrypt($_GET['id']);
$ay = Security::decrypt($_GET['month']);
$yil = Security::decrypt($_GET['year']);
$person = $Persons->find($personel_id);


//Personel Gelir Bilgileri
$incomes = $Bordro->getPersonIncome($personel_id, $ay, $yil);

//Personel Gider Bilgileri
$expenses = $Bordro->getPersonExpense($personel_id, $ay, $yil);

ob_end_clean();
$mpdf = new \Mpdf\Mpdf([
    'margin_left' => 0,
    'margin_right' => 0,
    'margin_top' => 10,

]);


// Sayfa genişliğini al
$pageWidth = $mpdf->w;
//Sayfayı yatay yap
$mpdf->AddPage('P');

ob_start();
include(ROOT . '/pages/payroll/bordro.php');
$html = ob_get_clean();


$mpdf->WriteHTML($html);

// Set a simple Footer including the page number
$mpdf->setFooter('{DATE j-m-Y H:i:s}' . ' - ' . 'Sayfa {PAGENO}');
$mpdf->Output();