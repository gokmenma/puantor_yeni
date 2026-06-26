<?php

// Output buffering başlatılır
session_start();

define('ROOT', $_SERVER["DOCUMENT_ROOT"]);
$firm_id = $_SESSION['firm_id'];

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;
$personObj = new Persons();

$persons = $personObj->getPersonsByFirm($firm_id);


use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// $inputFileName = __DIR__ . '/payment-load.xlsx';

/** Load $inputFileName to a Spreadsheet object **/
// $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
$spreadsheet = new Spreadsheet();

$activeWorksheet = $spreadsheet->getActiveSheet();

// Write Headers
$activeWorksheet->setCellValue('A1', 'Sıra');
$activeWorksheet->setCellValue('B1', 'TC Kimlik');
$activeWorksheet->setCellValue('C1', 'Ad Soyad');
$activeWorksheet->setCellValue('D1', 'Ödeme Günü');
$activeWorksheet->setCellValue('E1', 'Tutar');


$rowNum = 2;
$idx = 1;
foreach ($persons as $person) {
    $decryptedKimlik = Security::safeDecrypt($person->kimlik_no);
    $activeWorksheet->setCellValue('A' . $rowNum, $idx);
    $activeWorksheet->setCellValueExplicit('B' . $rowNum, $decryptedKimlik, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
    $activeWorksheet->setCellValue('C' . $rowNum, $person->full_name);
    $activeWorksheet->setCellValue('D' . $rowNum, date('d.m.Y'));
    $activeWorksheet->setCellValue('E' . $rowNum, 0);
    $rowNum++;
    $idx++;
}

//kolonları otomatik genişlet
foreach (range('A', 'E') as $columnID) {
    $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
}


// Redirect output to a client’s web browser (Xls)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="payment-load.xls"');
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