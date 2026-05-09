<?php
session_start();
define('ROOT', $_SERVER["DOCUMENT_ROOT"]);

$firm_id = $_SESSION['firm_id'] ?? null;
if (!$firm_id) {
    die("Oturum süresi dolmuş veya firma bilgisi bulunamadı.");
}

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Projects.php';
require_once ROOT . '/App/Helper/date.php';

use App\Helper\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

$personObj = new Persons();
$projects = new Projects();

$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

$last_day = Date::Ymd(Date::lastDay($month, $year));
$firstDay = Date::firstDay($month, $year);

if ($project_id > 0) {
    $persons = $projects->getPersonIdByFromProjectCurrentMonth($project_id, $last_day);
} else {
    $persons = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDay, $last_day, false);
}

$type = $_GET['type'] ?? 'income';
$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();

// Write Headers
$activeWorksheet->setCellValue('A1', 'id');
$activeWorksheet->setCellValue('B1', 'Ad Soyad');
if ($type == 'income') {
    $activeWorksheet->setCellValue('C1', 'Gelir Türü');
    $activeWorksheet->setCellValue('D1', 'Tutar');
    $activeWorksheet->setCellValue('E1', 'Açıklama');
} else {
    $activeWorksheet->setCellValue('C1', 'Kesinti Türü');
    $activeWorksheet->setCellValue('D1', 'Tutar');
    $activeWorksheet->setCellValue('E1', 'Açıklama');
}

$rowNum = 2;
foreach ($persons as $p_item) {
    $person = $personObj->find($p_item->id);
    if (!$person) {
        continue;
    }
    
    $activeWorksheet->setCellValue('A' . $rowNum, $person->id);
    $activeWorksheet->setCellValue('B' . $rowNum, $person->full_name);
    if ($type == 'income') {
        $activeWorksheet->setCellValue('C' . $rowNum, 'Prim');
    } else {
        $activeWorksheet->setCellValue('C' . $rowNum, 'Avans');
    }
    $activeWorksheet->setCellValue('D' . $rowNum, 0);
    $activeWorksheet->setCellValue('E' . $rowNum, '');
    $rowNum++;
}

// Kolonları otomatik genişlet
foreach (range('A', 'E') as $columnID) {
    $spreadsheet->getActiveSheet()->getColumnDimension($columnID)->setAutoSize(true);
}

$filename = ($type == 'income') ? "toplu-gelir-sablonu.xls" : "toplu-kesinti-sablonu.xls";

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Cache-Control: max-age=1');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: cache, must-revalidate');
header('Pragma: public');

$writer = IOFactory::createWriter($spreadsheet, 'Xls');
$writer->save('php://output');
exit;
