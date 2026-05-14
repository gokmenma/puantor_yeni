<?php
session_start();
define('ROOT', $_SERVER["DOCUMENT_ROOT"]);

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . "/Model/Bordro.php";
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$personObj = new Persons();
$bordroObj = new Bordro();

$firm_id = $_SESSION['firm_id'];
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$firstDayStr = Date::firstDay($month, $year);
$lastDayStr = Date::lastDay($month, $year);

$persons = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();

$header = ['İsim', 'TCKN', 'IBAN', 'Tutar', 'Açıklama'];
$activeWorksheet->fromArray($header, NULL, 'A1');

$row = 2;
foreach ($persons as $p_item) {
    $p = $personObj->find($p_item->id);
    $res = $bordroObj->getPersonSalaryAndWageCut($p->id, $firstDayStr, $lastDayStr);
    $netPay = ($res->gelir ?? 0) - ($res->odeme ?? 0);

    if ($netPay > 0) {
        $activeWorksheet->setCellValue('A' . $row, $p->full_name);
        $activeWorksheet->setCellValue('B' . $row, Security::safeDecrypt($p->kimlik_no ?? ''));
        $activeWorksheet->setCellValue('C' . $row, Security::safeDecrypt($p->iban_number ?? ''));
        $activeWorksheet->setCellValue('D' . $row, $netPay);
        $activeWorksheet->setCellValue('E' . $row, Date::monthName($month) . ' ' . $year . ' Maaş Ödemesi');
        $row++;
    }
}

foreach (range('A', 'E') as $columnID) {
    $activeWorksheet->getColumnDimension($columnID)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="banka-listesi-'.$month.'-'.$year.'.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
