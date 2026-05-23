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
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Helper\Helper;

if (!isset($_SESSION['firm_id'])) {
    die("Yetkisiz erişim.");
}

$firm_id = $_SESSION['firm_id'];
$personObj = new Persons();
$bordro = new Bordro();
$companyHelper = new CompanyHelper();

require_once 'Model/Projects.php';
$projectsObj = new Projects();
$allProjects = $projectsObj->getProjectsByFirm($firm_id);
$projectMap = [];
foreach ($allProjects as $proj) {
    $projectMap[$proj->id] = $proj->project_name;
}

$allAssignments = $projectsObj->getDb()->query("
    SELECT pp.person_id, pp.project_id 
    FROM project_person pp 
    JOIN projects p ON pp.project_id = p.id 
    WHERE p.firm_id = " . intval($firm_id)
)->fetchAll(PDO::FETCH_OBJ);

$personProjectsMap = [];
foreach ($allAssignments as $assign) {
    $personProjectsMap[$assign->person_id][] = $assign->project_id;
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Personel Listesi');

// Headers
$headers = [
    'A1' => 'Sıra',
    'B1' => 'Adı Soyadı',
    'C1' => 'Firma Adı',
    'D1' => 'Ücret Türü',
    'E1' => 'İşe Giriş Tarihi',
    'F1' => 'Telefon',
    'G1' => 'Ekip',
    'H1' => 'Proje',
    'I1' => 'Günlük/Aylık Ücreti',
    'J1' => 'Durumu',
    'K1' => 'Güncel Bakiyesi'
];

foreach ($headers as $cell => $text) {
    $sheet->setCellValue($cell, $text);
    $sheet->getStyle($cell)->getFont()->setBold(true);
}

$row = 2;
$i = 1;
foreach ($persons as $person) {
    $wage_type = $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka';
    $balance = $bordro->getBalance($person->id);
    $compName = $companyHelper->getCompanyName($person->company_id);
    $company_name = ($compName !== 'bilinmiyor' && $compName !== '') ? $compName : $companyHelper->getFirmName($person->firm_id);

    $assignedProjs = [];
    if (isset($personProjectsMap[$person->id])) {
        foreach ($personProjectsMap[$person->id] as $projId) {
            if (isset($projectMap[$projId])) {
                $assignedProjs[] = $projectMap[$projId];
            }
        }
    }
    $projectsStr = !empty($assignedProjs) ? implode(', ', $assignedProjs) : '-';

    $sheet->setCellValue('A' . $row, $i++);
    $sheet->setCellValue('B' . $row, $person->full_name);
    $sheet->setCellValue('C' . $row, $company_name);
    $sheet->setCellValue('D' . $row, $wage_type);
    $sheet->setCellValue('E' . $row, $person->job_start_date ?? '-');
    $sheet->setCellValue('F' . $row, $person->phone);
    $sheet->setCellValue('G' . $row, $person->ekip ?: '-');
    $sheet->setCellValue('H' . $row, $projectsStr);
    $sheet->setCellValue('I' . $row, $person->daily_wages);
    $sheet->setCellValue('J' . $row, empty($person->job_end_date) ? 'Aktif' : 'Pasif');
    $sheet->setCellValue('K' . $row, $balance);
    
    // Format money columns
    $sheet->getStyle('I' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
    $sheet->getStyle('K' . $row)->getNumberFormat()->setFormatCode('#,##0.00" ₺"');
    
    $row++;
}

// Auto filter and auto size columns
$sheet->setAutoFilter('A1:K1');
foreach (range('A', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Clear output buffer
if (ob_get_length()) ob_clean();

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="personel_listesi_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;