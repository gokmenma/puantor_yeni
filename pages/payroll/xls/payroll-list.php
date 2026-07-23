<?php

session_start();

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 3));
}

require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/Projects.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Security;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$firmId = (int) ($_SESSION['firm_id'] ?? 0);
$user = $_SESSION['user'] ?? null;
$auths = new Auths();
if (
    $firmId <= 0
    || !$user
    || (int) ($user->firm_id ?? 0) !== $firmId
    || !$auths->Authorize('payroll_page')
    || !$auths->hasPermission('payroll_export_excel')
) {
    http_response_code(403);
    exit('Bu işlem için yetkiniz yok.');
}

$year = (int) ($_GET['year'] ?? date('Y'));
$month = (int) ($_GET['month'] ?? date('n'));
$projectId = max(0, (int) ($_GET['project_id'] ?? 0));
$teamId = trim((string) ($_GET['team_id'] ?? ''));
if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
    http_response_code(422);
    exit('Geçersiz bordro dönemi.');
}

$_GET['p'] = 'payroll/list';
$personsModel = new Persons();
$bordro = new Bordro();
$projectsModel = new Projects();
$firstDay = Date::firstDay($month, $year);
$lastDay = Date::Ymd(Date::lastDay($month, $year));

if ($projectId > 0) {
    $personRows = $projectsModel->getPersonIdByFromProjectCurrentMonth(
        $projectId,
        $firstDay,
        $lastDay,
        0,
        $teamId,
        true
    );
} else {
    $personRows = $personsModel->getPersonIdByFirmCurrentMonth(
        $firmId,
        $firstDay,
        $lastDay,
        false,
        $teamId
    );
}

$personIds = array_values(array_unique(array_map(static function ($row) {
    return (int) $row->id;
}, $personRows)));
$persons = $personsModel->getPersonsByIds($personIds);
$persons = array_values(array_filter($persons, static function ($person) use ($firstDay) {
    return empty($person->job_end_date) || Date::Ymd($person->job_end_date) >= $firstDay;
}));
usort($persons, static function ($left, $right) {
    return strnatcasecmp((string) $left->full_name, (string) $right->full_name);
});

$eligibleIds = array_map(static function ($person) {
    return (int) $person->id;
}, $persons);
$financials = $bordro->getPersonsSalaryAndWageCut(
    $eligibleIds,
    $firstDay,
    Date::lastDay($month, $year)
);
$icraAmounts = $bordro->getIcraAmounts($eligibleIds, $month, $year);
$projectNames = $projectsModel->getProjectNamesByPersonIds($eligibleIds, $firmId);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Bordro');
$headers = [
    'Sıra', 'Personel Adı', 'Ücret Türü', 'Görevi', 'Ekip', 'Proje', 'IBAN',
    'İşe Başlama Tarihi', 'Brüt Ücret', 'İcra Kesintisi', 'Ödenen/Kesinti', 'Ödenecek',
];
$sheet->fromArray($headers, null, 'A1');
$sheet->getStyle('A1:L1')->getFont()->setBold(true);

$rowNumber = 2;
foreach ($persons as $index => $person) {
    $personId = (int) $person->id;
    $financial = $financials[$personId] ?? (object) ['gelir' => null, 'odeme' => 0];
    $gelir = (float) ($financial->gelir ?? 0);
    $odeme = (float) ($financial->odeme ?? 0);
    $icra = (float) ($icraAmounts[$personId] ?? 0);

    $sheet->setCellValue('A' . $rowNumber, $index + 1);
    $sheet->setCellValue('B' . $rowNumber, $person->full_name ?? '');
    $sheet->setCellValue('C' . $rowNumber, (int) ($person->wage_type ?? 0) === 1 ? 'Beyaz Yaka' : 'Mavi Yaka');
    $sheet->setCellValue('D' . $rowNumber, $person->job ?: '-');
    $sheet->setCellValue('E' . $rowNumber, $person->ekip ?: '-');
    $sheet->setCellValue('F' . $rowNumber, !empty($projectNames[$personId]) ? implode(', ', $projectNames[$personId]) : '-');
    $sheet->setCellValueExplicit(
        'G' . $rowNumber,
        Security::safeDecrypt($person->iban_number ?? '') ?: '-',
        DataType::TYPE_STRING
    );
    $sheet->setCellValue('H' . $rowNumber, $person->job_start_date ?: '-');
    $sheet->setCellValue('I' . $rowNumber, $gelir);
    $sheet->setCellValue('J' . $rowNumber, $icra);
    $sheet->setCellValue('K' . $rowNumber, max(0, $odeme - $icra));
    $sheet->setCellValue('L' . $rowNumber, $gelir - $odeme);
    $sheet->getStyle('I' . $rowNumber . ':L' . $rowNumber)
        ->getNumberFormat()
        ->setFormatCode('#,##0.00" ₺"');
    $rowNumber++;
}

$sheet->setAutoFilter('A1:L1');
foreach (range('A', 'L') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

ActivityLogModel::log(
    'payroll',
    'export',
    sprintf('%02d.%d dönemi bordro listesi Excel olarak dışa aktarıldı.', $month, $year)
);

if (ob_get_length()) {
    ob_clean();
}
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header(
    'Content-Disposition: attachment;filename="bordro_'
    . $year . '_' . sprintf('%02d', $month) . '.xlsx"'
);
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
