<?php


define('ROOT', $_SERVER["DOCUMENT_ROOT"]);

require ROOT . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

// $inputFileName = __DIR__ . '/payment-load.xlsx';

/** Load $inputFileName to a Spreadsheet object **/
// $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($inputFileName);
$spreadsheet = new Spreadsheet();

$activeWorksheet = $spreadsheet->getActiveSheet();

// Write Headers
$activeWorksheet->setCellValue('A1', 'Ad Soyad');
$activeWorksheet->setCellValue('B1', 'Tc Kimlik');
$activeWorksheet->setCellValue('C1', 'İşe Başlama Tarihi');
$activeWorksheet->setCellValue('D1', 'İban Numarası');
$activeWorksheet->setCellValue('E1', 'Telefon');
$activeWorksheet->setCellValue('F1', 'Email Adresi');
$activeWorksheet->setCellValue('G1', 'Günlük/Aylık Ücret');
$activeWorksheet->setCellValue('H1', 'Beyaz/Mavi Yaka');
$activeWorksheet->setCellValue('I1', 'Adresi');
$activeWorksheet->setCellValue('J1', 'Açıklama');


//kolonları otomatik genişlet
foreach (range('A', 'J') as $columnID) {
    $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
}


// Redirect output to a client’s web browser (Xls)
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="person-load.xls"');
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