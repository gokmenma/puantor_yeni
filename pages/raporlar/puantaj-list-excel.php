<?php
session_start();
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
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

$firm_id = $_SESSION['firm_id'];
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$firstDayStr = Date::firstDay($month, $year);
$lastDayStr = Date::lastDay($month, $year);

// DB compatibility formats
$startDate = date('Y-m-d', strtotime($firstDayStr));
$endDate = date('Y-m-d', strtotime($lastDayStr));

$start_dash = $startDate;
$end_dash = $endDate;
$start_nodash = str_replace('-', '', $startDate);
$end_nodash = str_replace('-', '', $endDate);

$db = $personObj->connect();
$queryStr = "
SELECT 
    p.id, 
    p.full_name, 
    p.job,
    p.kimlik_no,
    p.iban_number,
    p.job_start_date,
    p.job_end_date,
    p.ekip as team_name,
    pr.project_name,
    SUM(CASE WHEN pt.Turu = 'Normal Çalışma' THEN 1 ELSE 0 END) as n_calisma,
    SUM(CASE WHEN pt.Turu = 'Saatlik' THEN pua.saat ELSE 0 END) as s_calisma,
    SUM(CASE WHEN pt.Turu = 'Fazla Çalışma' THEN pua.saat ELSE 0 END) as f_mesai,
    SUM(CASE WHEN pt.Turu = 'Ücretli İzin' THEN 1 ELSE 0 END) as u_izin,
    SUM(CASE WHEN pt.PuantajKod = 'Uİ' THEN 1 ELSE 0 END) as ucr_izin,
    SUM(CASE WHEN pt.PuantajKod = 'DVZ' THEN 1 ELSE 0 END) as dvz,
    SUM(CASE WHEN pt.PuantajKod IN ('R', 'R-', 'R+') THEN 1 ELSE 0 END) as rapor
FROM persons p
LEFT JOIN projects pr ON p.project_id = pr.id
LEFT JOIN puantaj pua ON p.id = pua.person AND ((pua.gun >= ? AND pua.gun <= ?) OR (pua.gun >= ? AND pua.gun <= ?))
LEFT JOIN puantajturu pt ON pua.puantaj_id = pt.id
WHERE p.firm_id = ? AND p.deleted_at IS NULL
GROUP BY p.id
ORDER BY p.full_name ASC
";
$stmt = $db->prepare($queryStr);
$stmt->execute([$start_dash, $end_dash, $start_nodash, $end_nodash, $firm_id]);
$raporData = $stmt->fetchAll(PDO::FETCH_OBJ);

$spreadsheet = new Spreadsheet();
$activeWorksheet = $spreadsheet->getActiveSheet();

$header = [
    'Personel Adı', 
    'TC Kimlik No', 
    'IBAN No', 
    'İşe Giriş', 
    'İşten Çıkış', 
    'Ekip', 
    'Proje', 
    'Ünvan / Meslek', 
    'Çalışma (Gün)', 
    'Saatlik Çal. (Saat)', 
    'Fazla Mesai (Saat)', 
    'Ücretli İzin', 
    'Ücretsiz İzin', 
    'Rapor', 
    'Devamsız'
];
$activeWorksheet->fromArray($header, NULL, 'A1');

$row = 2;
foreach ($raporData as $r) {
    $activeWorksheet->setCellValue('A' . $row, $r->full_name);
    $activeWorksheet->setCellValue('B' . $row, Security::safeDecrypt($r->kimlik_no ?? ''));
    $activeWorksheet->setCellValue('C' . $row, Security::safeDecrypt($r->iban_number ?? ''));
    $activeWorksheet->setCellValue('D' . $row, $r->job_start_date ?? '-');
    $activeWorksheet->setCellValue('E' . $row, $r->job_end_date ?? '-');
    $activeWorksheet->setCellValue('F' . $row, $r->team_name ?? '-');
    $activeWorksheet->setCellValue('G' . $row, $r->project_name ?? '-');
    $activeWorksheet->setCellValue('H' . $row, $r->job ?? '-');
    $activeWorksheet->setCellValue('I' . $row, (float)$r->n_calisma);
    $activeWorksheet->setCellValue('J' . $row, (float)$r->s_calisma);
    $activeWorksheet->setCellValue('K' . $row, (float)$r->f_mesai);
    $activeWorksheet->setCellValue('L' . $row, (float)$r->u_izin);
    $activeWorksheet->setCellValue('M' . $row, (float)$r->ucr_izin);
    $activeWorksheet->setCellValue('N' . $row, (float)$r->rapor);
    $activeWorksheet->setCellValue('O' . $row, (float)$r->dvz);
    $row++;
}

foreach (range('A', 'O') as $columnID) {
    $activeWorksheet->getColumnDimension($columnID)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="puantaj-raporu-'.$month.'-'.$year.'.xlsx"');
header('Cache-Control: max-age=0');

$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
$writer->save('php://output');
exit;
