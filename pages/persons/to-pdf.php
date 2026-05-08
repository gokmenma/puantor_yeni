<?php

session_start();
define('ROOT', dirname(__DIR__, 2));
set_include_path(ROOT);
require ROOT . '/vendor/autoload.php';
require_once 'Model/Persons.php';
require_once 'Model/Bordro.php';
require_once 'App/Helper/company.php';
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Helper\Helper;

if (!isset($_SESSION['firm_id'])) {
    die("Yetkisiz erişim.");
}

$firm_id = $_SESSION['firm_id'];
$personObj = new Persons();
$bordro = new Bordro();
$companyHelper = new CompanyHelper();

$persons = $personObj->getPersonsByFirm($firm_id);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Personel Listesi');

// Headers
$headers = [
    'A1' => 'Sıra',
    'B1' => 'Adı Soyadı',
    'C1' => 'Firma Adı',
    'D1' => 'Ücret Türü',
    'E1' => 'Sigorta No',
    'F1' => 'Telefon',
    'G1' => 'Adres',
    'H1' => 'Günlük/Aylık Ücreti',
    'I1' => 'Durumu',
    'J1' => 'Güncel Bakiyesi'
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
    $sheet->getStyle($cell)->getFont()->setBold(true);
    $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF206BC4');
    $sheet->getStyle($cell)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
}

$row = 2;
$i = 1;
foreach ($persons as $person) {
    $wage_type = $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka';
    $balance = $bordro->getBalance($person->id);
    $company_name = $companyHelper->getcompanyName($person->company_id) ?? '-';

    $sheet->setCellValue('A' . $row, $i++);
    $sheet->setCellValue('B' . $row, $person->full_name);
    $sheet->setCellValue('C' . $row, $company_name);
    $sheet->setCellValue('D' . $row, $wage_type);
    $sheet->setCellValue('E' . $row, $person->sigorta_no);
    $sheet->setCellValue('F' . $row, $person->phone);
    $sheet->setCellValue('G' . $row, $person->address);
    $sheet->setCellValue('H' . $row, Helper::formattedMoney($person->daily_wages));
    $sheet->setCellValue('I' . $row, $person->state);
    $sheet->setCellValue('J' . $row, Helper::formattedMoney($balance));
    
    $row++;
}

// Add borders to all data cells
$sheet->getStyle('A1:J' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// Auto size columns
foreach (range('A', 'J') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Set orientation to landscape for PDF
$spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

// Clear output buffer
if (ob_get_length()) ob_clean();

IOFactory::registerWriter('Pdf', \PhpOffice\PhpSpreadsheet\Writer\Pdf\Mpdf::class);

header('Content-Type: application/pdf');
header('Content-Disposition: attachment;filename="personel_listesi_' . date('Y-m-d') . '.pdf"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Pdf');
$writer->save('php://output');
exit;