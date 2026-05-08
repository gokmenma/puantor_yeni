<?php

// Output buffering başlatılır
session_start();

define('ROOT', $_SERVER["DOCUMENT_ROOT"]);
$firm_id = $_SESSION['firm_id'];

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . "/Model/Bordro.php";
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';


use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;


$personObj = new Persons();
$bordro = new Bordro();

$persons = $personObj->getPersonsByFirm($firm_id);


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// $inputFileName = __DIR__ . '/payment-load.xlsx';

/** Load $inputFileName to a Spreadsheet object **/
// $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
$spreadsheet = new Spreadsheet();

$activeWorksheet = $spreadsheet->getActiveSheet();


$header = ['İsim', 'TCKN (Opsiyonel)', 'Banka Kodu', 'Şube Kodu', 'Hesap', 'IBAN (Boşluksuz 26 Karakter)', 'Tutar', 'Açıklama'];
//Başlıkları A1'den itibaren yazdır
$activeWorksheet->fromArray($header, NULL, 'A1');
//yarının tarihi 20241111 formatında yaz
$start_date = Date::getTomorrowDate();

foreach ($persons as $key => $person) {
    $bakiye = $bordro->getBalance($person->id) ?? 0;

    if ($bakiye > 0) {

        $activeWorksheet->setCellValue('A' . ($key + 2), $person->full_name);
        $activeWorksheet->setCellValue('B' . ($key + 2), '');
        $activeWorksheet->setCellValue('C' . ($key + 2), '');
        $activeWorksheet->setCellValue('D' . ($key + 2), '');
        $activeWorksheet->setCellValue('E' . ($key + 2), '');
        $activeWorksheet->setCellValue('F' . ($key + 2), Security::safeDecrypt($person->iban_number));
        $activeWorksheet->setCellValue('G' . ($key + 2), Helper::formattedMoneyWithoutCurrency($bakiye));
        $activeWorksheet->setCellValue('H' . ($key + 2), '');
    }
}

//kolonları otomatik genişlet
foreach (range('A', 'Z') as $columnID) {
    $activeWorksheet->getColumnDimension($columnID)->setAutoSize(true);
}



// Redirect output to a client’s web browser (Xls)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="bank-list-for-payments.xls"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$writer = IOFactory::createWriter($spreadsheet, 'Xls');
$writer->save('php://output');
exit;