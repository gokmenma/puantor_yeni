<?php

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/Projects.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

header('Content-Type: application/json; charset=utf-8');

try {
    $firmId = (int) ($_SESSION['firm_id'] ?? 0);
    $user = $_SESSION['user'] ?? null;
    if ($firmId <= 0 || !$user) {
        throw new RuntimeException('Yetkisiz erişim.');
    }

    $auths = new Auths();
    if (!$auths->Authorize('payroll_page')) {
        throw new RuntimeException('Bu sayfayı görüntüleme yetkiniz yok.');
    }

    $_GET['p'] = 'payroll/list';
    $personsModel = new Persons();
    $bordro = new Bordro();
    $projectsModel = new Projects();

    $year = (int) ($_POST['year'] ?? date('Y'));
    $month = (int) ($_POST['month'] ?? date('n'));
    if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
        throw new InvalidArgumentException('Geçersiz bordro dönemi.');
    }

    $projectId = max(0, (int) ($_POST['project_id'] ?? 0));
    $teamId = trim((string) ($_POST['team_id'] ?? ''));
    $firstDay = Date::firstDay($month, $year);
    $lastDay = Date::Ymd(Date::lastDay($month, $year));

    if ($projectId > 0) {
        $authorizedRows = $projectsModel->getPersonIdByFromProjectCurrentMonth(
            $projectId,
            $firstDay,
            $lastDay,
            0,
            $teamId,
            true
        );
    } else {
        $authorizedRows = $personsModel->getPersonIdByFirmCurrentMonth(
            $firmId,
            $firstDay,
            $lastDay,
            false,
            $teamId
        );
    }
    $authorizedIds = array_values(array_unique(array_map(static function ($row) {
        return (int) $row->id;
    }, $authorizedRows)));

    $draw = max(0, (int) ($_POST['draw'] ?? 0));
    $start = max(0, (int) ($_POST['start'] ?? 0));
    $length = max(10, min(100, (int) ($_POST['length'] ?? 25)));
    $search = trim((string) ($_POST['search']['value'] ?? ''));
    $columnSearches = [];
    foreach (($_POST['columns'] ?? []) as $index => $column) {
        $value = trim((string) ($column['search']['value'] ?? ''));
        if ($value !== '') {
            $columnSearches[(int) $index] = $value;
        }
    }

    $orderColumn = (int) ($_POST['order'][0]['column'] ?? 1);
    $orderDirection = strtolower((string) ($_POST['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
    $nativeOrderFields = [
        0 => 'id',
        1 => 'full_name',
        2 => 'wage_type',
        3 => 'job',
        4 => 'ekip',
        7 => 'job_start_date',
    ];
    $orderField = $nativeOrderFields[$orderColumn] ?? 'full_name';
    $total = $personsModel->getPayrollPersonsServerSideCount($firmId, $authorizedIds, $firstDay);
    $compatibilityMode = $search !== ''
        || !empty($columnSearches)
        || !array_key_exists($orderColumn, $nativeOrderFields);

    if ($compatibilityMode) {
        $allPersons = $personsModel->getPersonsByIds($authorizedIds);
        $eligiblePersons = [];
        foreach ($allPersons as $person) {
            if (!empty($person->job_end_date) && Date::Ymd($person->job_end_date) < $firstDay) {
                continue;
            }
            $eligiblePersons[] = $person;
        }

        $eligibleIds = array_map(static function ($person) {
            return (int) $person->id;
        }, $eligiblePersons);
        $allFinancials = $bordro->getPersonsSalaryAndWageCut(
            $eligibleIds,
            $firstDay,
            Date::lastDay($month, $year)
        );
        $allIcra = $bordro->getIcraAmounts($eligibleIds, $month, $year);
        $allProjectNames = $projectsModel->getProjectNamesByPersonIds($eligibleIds, $firmId);
        $filteredPersons = [];

        foreach ($eligiblePersons as $person) {
            $personId = (int) $person->id;
            $financial = $allFinancials[$personId] ?? (object) ['gelir' => null, 'odeme' => 0];
            $gelir = (float) ($financial->gelir ?? 0);
            $odeme = (float) ($financial->odeme ?? 0);
            $icra = (float) ($allIcra[$personId] ?? 0);
            $searchable = [
                0 => $personId,
                1 => $person->full_name ?? '',
                2 => (int) ($person->wage_type ?? 0) === 1 ? 'Beyaz Yaka' : 'Mavi Yaka',
                3 => $person->job ?? '',
                4 => $person->ekip ?? '',
                5 => !empty($allProjectNames[$personId]) ? implode(', ', $allProjectNames[$personId]) : '',
                6 => Security::safeDecrypt($person->iban_number ?? ''),
                7 => $person->job_start_date ?? '',
                8 => $gelir . ' ' . Helper::formattedMoney($gelir),
                9 => $icra . ' ' . Helper::formattedMoney($icra),
                10 => max(0, $odeme - $icra) . ' ' . Helper::formattedMoney(max(0, $odeme - $icra)),
                11 => ($gelir - $odeme) . ' ' . Helper::formattedMoney($gelir - $odeme),
            ];
            $sortable = [
                8 => $gelir,
                9 => $icra,
                10 => max(0, $odeme - $icra),
                11 => $gelir - $odeme,
            ];

            $matches = true;
            if ($search !== '') {
                $matches = false;
                foreach ($searchable as $value) {
                    if (Helper::searchContains($value, $search)) {
                        $matches = true;
                        break;
                    }
                }
            }
            if (!$matches) {
                continue;
            }

            foreach ($columnSearches as $index => $value) {
                if (!Helper::searchContains($searchable[$index] ?? '', $value)) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $person->_payroll_sort = $sortable[$orderColumn] ?? ($searchable[$orderColumn] ?? '');
                $filteredPersons[] = $person;
            }
        }

        usort($filteredPersons, static function ($left, $right) use ($orderDirection) {
            $comparison = strnatcasecmp(
                (string) ($left->_payroll_sort ?? ''),
                (string) ($right->_payroll_sort ?? '')
            );
            return $orderDirection === 'desc' ? -$comparison : $comparison;
        });
        $filtered = count($filteredPersons);
        $persons = array_slice($filteredPersons, $start, $length);
    } else {
        $filtered = $total;
        $persons = $personsModel->getPayrollPersonsServerSidePage(
            $firmId,
            $authorizedIds,
            $firstDay,
            $start,
            $length,
            $orderField,
            $orderDirection
        );
    }

    $pageIds = array_map(static function ($person) {
        return (int) $person->id;
    }, $persons);
    $financials = $bordro->getPersonsSalaryAndWageCut(
        $pageIds,
        $firstDay,
        Date::lastDay($month, $year)
    );
    $icraAmounts = $bordro->getIcraAmounts($pageIds, $month, $year);
    $projectNames = $projectsModel->getProjectNamesByPersonIds($pageIds, $firmId);
    $canMakePayment = $auths->hasPermission('make_staff_payment');
    $canEditIncomeExpense = $auths->hasPermission('income_expense_add_update');
    $encryptedMonth = Security::encrypt($month);
    $encryptedYear = Security::encrypt($year);

    $data = [];
    foreach ($persons as $offset => $person) {
        $personId = (int) $person->id;
        $encryptedId = Security::encrypt($personId);
        $financial = $financials[$personId] ?? (object) ['gelir' => null, 'odeme' => 0];
        $gelir = (float) ($financial->gelir ?? 0);
        $odeme = (float) ($financial->odeme ?? 0);
        $icra = (float) ($icraAmounts[$personId] ?? 0);
        $odemeHaricIcra = max(0, $odeme - $icra);
        $kalan = $gelir - $odeme;
        $wageTypeText = (int) ($person->wage_type ?? 0) === 1 ? 'Aylık' : 'Günlük';

        if ((int) ($person->wage_type ?? 0) === 1) {
            $monthlyWage = (float) ($person->daily_wages ?? 0);
            $monthlyWageText = Helper::formattedMoney($monthlyWage);
            $dailyWageText = Helper::formattedMoney($monthlyWage / 30);
        } else {
            $monthlyWageText = '-';
            $dailyWageText = Helper::formattedMoney((float) ($person->daily_wages ?? 0));
        }

        $popoverContent = "<div class='p-1'>
            <div class='mb-2 pb-1 border-bottom d-flex justify-content-between align-items-center gap-4'>
                <span class='text-secondary small font-weight-medium'>Ücret Türü</span>
                <span class='badge bg-blue-lite text-blue'>" . htmlspecialchars($wageTypeText, ENT_QUOTES, 'UTF-8') . "</span>
            </div>
            <div class='d-flex justify-content-between py-1 gap-4'>
                <span class='text-secondary'>Aylık Ücret:</span>
                <span class='font-weight-bold text-dark'>" . htmlspecialchars($monthlyWageText, ENT_QUOTES, 'UTF-8') . "</span>
            </div>
            <div class='d-flex justify-content-between py-1 gap-4'>
                <span class='text-secondary'>Günlük Ücret:</span>
                <span class='font-weight-bold text-dark'>" . htmlspecialchars($dailyWageText, ENT_QUOTES, 'UTF-8') . "</span>
            </div>
        </div>";

        $safeBalance = htmlspecialchars(Helper::formattedMoney($kalan), ENT_QUOTES, 'UTF-8');
        $actions = '';
        if ($canMakePayment) {
            $actions .= '<a class="dropdown-item add-payment" data-id="' . $encryptedId
                . '" data-balance="' . $safeBalance . '" href="#" data-bs-toggle="modal" data-bs-target="#payment-modal">
                <i class="ti ti-cash-register icon me-3"></i> Ödeme Yap
            </a>';
        }
        if ($canEditIncomeExpense) {
            $actions .= '<a class="dropdown-item add-wage-cut" data-id="' . $encryptedId
                . '" data-balance="' . $safeBalance . '" data-tooltip="Avans,Ceza veya Bes gibi" data-tooltip-location="left" href="#">
                <i class="ti ti-cut icon me-3"></i> Kesinti Ekle
            </a>
            <a class="dropdown-item add-income" data-id="' . $encryptedId
                . '" data-balance="' . $safeBalance . '" data-tooltip="Prim,İkramiye veya Ödül gibi" data-tooltip-location="left" href="#" data-bs-toggle="modal" data-bs-target="#income_modal">
                <i class="ti ti-download icon me-3"></i> Gelir Ekle
            </a>';
        }
        $actions .= '<a class="dropdown-item" target="_blank" href="index.php?p=payroll/pay-slip&id='
            . $encryptedId . '&month=' . $encryptedMonth . '&year=' . $encryptedYear . '">
                <i class="ti ti-file-dollar icon me-3"></i> Bordro Göster
            </a>
            <div class="dropdown-divider"></div>
            <a class="dropdown-item delete-monthly-payroll text-danger" data-id="' . $encryptedId
            . '" data-month="' . $month . '" data-year="' . $year . '" data-project-id="' . $projectId . '" href="#">
                <i class="ti ti-trash icon me-3"></i> Sil
            </a>';

        $detailAttributes = ' data-id="' . $encryptedId . '" data-month="' . $month
            . '" data-year="' . $year . '" role="button" tabindex="0" title="Bordro detayını görüntüle"'
            . ' style="cursor: pointer;" data-bs-toggle="modal" data-bs-target="#payroll-detail-modal"';
        $safeName = htmlspecialchars((string) ($person->full_name ?? ''), ENT_QUOTES, 'UTF-8');

        $data[] = [
            $start + $offset + 1,
            '<a href="#" data-tooltip="Detay/Güncelle" data-page="persons/manage&id=' . $encryptedId
                . '" class="nav-item route-link">' . $safeName . '</a>',
            (int) ($person->wage_type ?? 0) === 1 ? 'Beyaz Yaka' : 'Mavi Yaka',
            htmlspecialchars((string) ($person->job ?: '-'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($person->ekip ?: '-'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(!empty($projectNames[$personId]) ? implode(', ', $projectNames[$personId]) : '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '') ?: '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($person->job_start_date ?: '-'), ENT_QUOTES, 'UTF-8'),
            '<span class="gross-salary-popover" data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true" data-bs-placement="top" title="Ücret Bilgileri" data-bs-content="'
                . htmlspecialchars($popoverContent, ENT_QUOTES, 'UTF-8') . '" style="cursor: pointer;">'
                . Helper::formattedMoney($gelir) . ' <i class="ti ti-download icon text-green"></i></span>',
            '<span class="text-purple fw-semibold">' . ($icra > 0 ? Helper::formattedMoney($icra) : '0,00 ₺') . '</span>',
            '<span class="view-payroll-detail"' . $detailAttributes . '>'
                . Helper::formattedMoney($odemeHaricIcra) . ' <i class="ti ti-cash-register icon color-green"></i></span>',
            '<span class="payroll-balance ' . Helper::balanceColor($kalan) . ' view-payroll-detail"' . $detailAttributes . '>'
                . Helper::formattedMoney($kalan) . ' <i class="ti ti-credit-card-pay icon"></i></span>',
            '<div class="dropdown">
                <button class="btn dropdown-toggle" data-bs-toggle="dropdown">İşlem</button>
                <div class="dropdown-menu dropdown-menu-end">' . $actions . '</div>
            </div>',
        ];
    }

    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total,
        'recordsFiltered' => $filtered,
        'data' => $data,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code(500);
    echo json_encode([
        'draw' => (int) ($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => $exception->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
