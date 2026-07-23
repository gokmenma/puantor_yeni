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
require_once 'App/Helper/security.php';
require_once 'Model/Auths.php';
require_once 'Model/Projects.php';
require_once 'Model/ActivityLogModel.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Helper\Helper;
use App\Helper\Security;

if (
    !isset($_SESSION['firm_id'], $_SESSION['user'])
    || (int) ($_SESSION['user']->firm_id ?? 0) !== (int) $_SESSION['firm_id']
) {
    die("Yetkisiz erişim.");
}

$firm_id = $_SESSION['firm_id'];
$auths = new Auths();
if (!$auths->Authorize('personnel_page')) {
    http_response_code(403);
    die("Bu işlem için yetkiniz yok.");
}

$_GET['p'] = 'persons/list';
$personObj = new Persons();
$bordro = new Bordro();
$companyHelper = new CompanyHelper();
$projectsObj = new Projects();
$persons = $personObj->getPersonsByFirm($firm_id);
$personIds = array_map(static function ($person) {
    return (int) $person->id;
}, $persons);
$balances = $bordro->getBalances($personIds);
$projectNames = $projectsObj->getProjectNamesByPersonIds($personIds, (int) $firm_id);
$companyIds = array_map(static function ($person) {
    return (int) ($person->company_id ?? 0);
}, $persons);
$companyNames = $companyHelper->getCompanyNames($companyIds);
$firmName = $companyHelper->getFirmName($firm_id);

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
    $sheet->getStyle($cell)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('FF206BC4');
    $sheet->getStyle($cell)->getFont()->getColor()->setARGB(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_WHITE);
}

$row = 2;
$i = 1;
foreach ($persons as $person) {
    $wage_type = $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka';
    $balance = $balances[(int) $person->id] ?? 0;
    $company_name = $companyNames[(int) ($person->company_id ?? 0)] ?? $firmName;
    $projectsStr = !empty($projectNames[(int) $person->id])
        ? implode(', ', $projectNames[(int) $person->id])
        : '-';

    $sheet->setCellValue('A' . $row, $i++);
    $sheet->setCellValue('B' . $row, $person->full_name);
    $sheet->setCellValue('C' . $row, $company_name);
    $sheet->setCellValue('D' . $row, $wage_type);
    $sheet->setCellValue('E' . $row, $person->job_start_date ?? '-');
    $sheet->setCellValue('F' . $row, Security::safeDecrypt($person->phone ?? '') ?: '-');
    $sheet->setCellValue('G' . $row, $person->ekip ?: '-');
    $sheet->setCellValue('H' . $row, $projectsStr);
    $sheet->setCellValue('I' . $row, Helper::formattedMoney($person->daily_wages));
    $sheet->setCellValue('J' . $row, empty($person->job_end_date) ? 'Aktif' : 'Pasif');
    $sheet->setCellValue('K' . $row, Helper::formattedMoney($balance));
    
    $row++;
}

// Add borders to all data cells
$sheet->getStyle('A1:K' . ($row - 1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

// Auto size columns
foreach (range('A', 'K') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

ActivityLogModel::log('personnel', 'export', 'Personel listesi PDF olarak dışa aktarıldı.');

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
