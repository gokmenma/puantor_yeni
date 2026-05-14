<?php
session_start();
define('ROOT', $_SERVER["DOCUMENT_ROOT"]);

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . "/Model/Bordro.php";
require_once ROOT . '/Model/DefinesModel.php';
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
$definesObj = new DefinesModel();

$firm_id = $_SESSION['firm_id'];
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$firstDayStr = Date::firstDay($month, $year);
$lastDayStr = Date::lastDay($month, $year);

$db = $personObj->connect();
$kesinti_ids = $definesObj->getExpenseTypes(2); // Get deduction IDs

$queryStr = "
SELECT 
    p.full_name,
    mgk.turu,
    mgk.tutar,
    mgk.gun,
    mgk.aciklama,
    dt.name as kategori_adi
FROM maas_gelir_kesinti mgk
JOIN persons p ON mgk.person_id = p.id
LEFT JOIN defines dt ON mgk.kategori = dt.id
WHERE p.firm_id = ? 
  AND mgk.kategori IN ($kesinti_ids)
  AND CAST(REPLACE(mgk.gun, '-', '') AS UNSIGNED) >= ? 
  AND CAST(REPLACE(mgk.gun, '-', '') AS UNSIGNED) <= ?
ORDER BY mgk.gun DESC
";
$stmt = $db->prepare($queryStr);
$stmt->execute([$firm_id, $firstDayStr, $lastDayStr]);
$kesintiData = $stmt->fetchAll(PDO::FETCH_OBJ);

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();

$header = ['Personel Adı', 'Tarih', 'Kategori', 'Kesinti Türü', 'Açıklama', 'Tutar'];
$activeWorksheet->fromArray($header, NULL, 'A1');

$row = 2;
foreach ($kesintiData as $k) {
    $activeWorksheet->setCellValue('A' . $row, $k->full_name);
    $activeWorksheet->setCellValue('B' . $row, date('d.m.Y', strtotime($k->gun)));
    $activeWorksheet->setCellValue('C' . $row, $k->kategori_adi ?? 'Diğer');
    $activeWorksheet->setCellValue('D' . $row, $k->turu);
    $activeWorksheet->setCellValue('E' . $row, $k->aciklama ?? '');
    $activeWorksheet->setCellValue('F' . $row, $k->tutar);
    $row++;
}

foreach (range('A', 'F') as $columnID) {
    $activeWorksheet->getColumnDimension($columnID)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="kesinti-listesi-'.$month.'-'.$year.'.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
