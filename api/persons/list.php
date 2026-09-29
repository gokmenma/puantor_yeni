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
require_once ROOT . '/App/Helper/company.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Helper;
use App\Helper\Security;

header('Content-Type: application/json; charset=utf-8');

try {
    $firm_id = (int) ($_SESSION['firm_id'] ?? 0);
    $user = $_SESSION['user'] ?? null;
    if ($firm_id <= 0 || !$user || (int) ($user->firm_id ?? 0) !== $firm_id) {
        throw new RuntimeException('Yetkisiz erişim.');
    }

    $auths = new Auths();
    if (!$auths->Authorize('personnel_page')) {
        throw new RuntimeException('Bu sayfayı görüntüleme yetkiniz yok.');
    }

    $_GET['p'] = 'persons/list';
    $personsModel = new Persons();
    $bordro = new Bordro();
    $projects = new Projects();
    $company = new CompanyHelper();

    $authorizedRows = $personsModel->getPersonIdByFirm($firm_id);
    $authorizedIds = array_map(static function ($row) {
        return (int) $row->id;
    }, $authorizedRows);

    $draw = max(0, (int) ($_POST['draw'] ?? 0));
    $start = max(0, (int) ($_POST['start'] ?? 0));
    $length = max(10, min(100, (int) ($_POST['length'] ?? 25)));
    $status = $_POST['status'] ?? 'active';
    $search = trim((string) ($_POST['search']['value'] ?? ''));
    $columnSearches = [];
    foreach (($_POST['columns'] ?? []) as $index => $column) {
        $value = trim((string) ($column['search']['value'] ?? ''));
        $isRegex = filter_var($column['search']['regex'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($value !== '') {
            $columnSearches[(int) $index] = ['value' => $value, 'regex' => $isRegex];
        }
    }

    $matchColumnFilter = static function ($cellValue, $searchValue, $isRegex = false): bool {
        $searchValue = trim((string) $searchValue);
        if ($searchValue === '') {
            return true;
        }
        $cellValueStr = trim((string) $cellValue);

        // Check if JSON multi-rule definition
        if (str_starts_with($searchValue, '{') && str_ends_with($searchValue, '}')) {
            $parsedJson = json_decode($searchValue, true);
            if (is_array($parsedJson)) {
                $type = $parsedJson['type'] ?? 'text';
                $logic = $parsedJson['logic'] ?? ($type === 'text' ? 'or' : 'and');
                $rules = $parsedJson['rules'] ?? [];

                if (!empty($rules)) {
                    $results = [];
                    foreach ($rules as $r) {
                        $op = $r['operator'] ?? 'contains';
                        $val = trim((string) ($r['value'] ?? ''));
                        
                        $passed = false;
                        if ($op === 'empty') {
                            $passed = ($cellValueStr === '' || $cellValueStr === '-');
                        } elseif ($op === 'not_empty') {
                            $passed = ($cellValueStr !== '' && $cellValueStr !== '-');
                        } elseif ($type === 'number') {
                            $cellNum = Helper::standardizeWage($cellValueStr);
                            $targetNum = Helper::standardizeWage($val);
                            if ($op === 'equals') $passed = abs($cellNum - $targetNum) < 0.01;
                            elseif ($op === 'gt') $passed = $cellNum > $targetNum;
                            elseif ($op === 'lt') $passed = $cellNum < $targetNum;
                            elseif ($op === 'gte') $passed = $cellNum >= $targetNum;
                            elseif ($op === 'lte') $passed = $cellNum <= $targetNum;
                            elseif ($op === 'contains') $passed = Helper::searchContains($cellValueStr, $val);
                        } elseif ($type === 'date') {
                            $cellTimestamp = strtotime(str_replace('/', '-', $cellValueStr));
                            $targetTimestamp = strtotime(str_replace('/', '-', $val));
                            if ($cellTimestamp !== false && $targetTimestamp !== false) {
                                $dCell = strtotime(date('Y-m-d', $cellTimestamp));
                                $dTarget = strtotime(date('Y-m-d', $targetTimestamp));
                                if ($op === 'equals') $passed = ($dCell === $dTarget);
                                elseif ($op === 'after' || $op === 'gt') $passed = ($dCell > $dTarget);
                                elseif ($op === 'before' || $op === 'lt') $passed = ($dCell < $dTarget);
                                elseif ($op === 'gte') $passed = ($dCell >= $dTarget);
                                elseif ($op === 'lte') $passed = ($dCell <= $dTarget);
                            }
                        } else {
                            if ($op === 'equals') $passed = (Helper::normalizeSearchText($cellValueStr) === Helper::normalizeSearchText($val));
                            elseif ($op === 'starts') $passed = str_starts_with(Helper::normalizeSearchText($cellValueStr), Helper::normalizeSearchText($val));
                            elseif ($op === 'ends') $passed = str_ends_with(Helper::normalizeSearchText($cellValueStr), Helper::normalizeSearchText($val));
                            elseif ($op === 'not_contains') $passed = !Helper::searchContains($cellValueStr, $val);
                            else $passed = Helper::searchContains($cellValueStr, $val);
                        }
                        $results[] = $passed;
                    }

                    if ($logic === 'or') {
                        return in_array(true, $results, true);
                    } else {
                        return !in_array(false, $results, true);
                    }
                }
            }
        }

        // Empty / Not Empty
        if ($searchValue === '^$') {
            return $cellValueStr === '' || $cellValueStr === '-';
        }
        if ($searchValue === '^(?!$).+' || $searchValue === '^(?!$).*') {
            return $cellValueStr !== '' && $cellValueStr !== '-';
        }

        // Date comparisons (> 01.01.2023, < 01.01.2023, >= 01.01.2023, <= 01.01.2023)
        if (preg_match('/^(>=|<=|>|<|=)\s*(\d{1,4}[.\-\/]\d{1,2}[.\-\/]\d{1,4})$/', $searchValue, $dateMatches)) {
            $op = $dateMatches[1];
            $cellTimestamp = strtotime(str_replace('/', '-', $cellValueStr));
            $targetTimestamp = strtotime(str_replace('/', '-', $dateMatches[2]));
            if ($cellTimestamp !== false && $targetTimestamp !== false) {
                $dCell = strtotime(date('Y-m-d', $cellTimestamp));
                $dTarget = strtotime(date('Y-m-d', $targetTimestamp));
                if ($op === '>') return $dCell > $dTarget;
                if ($op === '<') return $dCell < $dTarget;
                if ($op === '>=') return $dCell >= $dTarget;
                if ($op === '<=') return $dCell <= $dTarget;
                if ($op === '=') return $dCell === $dTarget;
            }
        }

        // Numeric / Money comparisons (>, <, >=, <=, =)
        if (preg_match('/^(>=|<=|>|<|=)\s*(.+)$/', $searchValue, $opMatches)) {
            $op = $opMatches[1];
            $targetVal = Helper::standardizeWage($opMatches[2]);
            $currentVal = Helper::standardizeWage($cellValueStr);
            if ($op === '>') return $currentVal > $targetVal;
            if ($op === '<') return $currentVal < $targetVal;
            if ($op === '>=') return $currentVal >= $targetVal;
            if ($op === '<=') return $currentVal <= $targetVal;
            if ($op === '=') return $currentVal == $targetVal;
        }

        // Regex / String conditions
        $isRegexPattern = $isRegex || str_starts_with($searchValue, '^') || str_ends_with($searchValue, '$');
        if ($isRegexPattern) {
            $cleanSearch = $searchValue;
            $isExact = false;
            $isStart = false;
            $isEnd = false;
            if (str_starts_with($cleanSearch, '^') && str_ends_with($cleanSearch, '$')) {
                $cleanSearch = substr($cleanSearch, 1, -1);
                $isExact = true;
            } elseif (str_starts_with($cleanSearch, '^')) {
                $cleanSearch = substr($cleanSearch, 1);
                $isStart = true;
            } elseif (str_ends_with($cleanSearch, '$')) {
                $cleanSearch = substr($cleanSearch, 0, -1);
                $isEnd = true;
            }

            // Remove any regex escaping backslashes if present
            $cleanSearch = stripslashes($cleanSearch);

            $normCell = Helper::normalizeSearchText($cellValueStr);
            $normSearch = Helper::normalizeSearchText($cleanSearch);

            if ($isExact) {
                return $normCell === $normSearch;
            }
            if ($isStart) {
                return str_starts_with($normCell, $normSearch);
            }
            if ($isEnd) {
                return str_ends_with($normCell, $normSearch);
            }
        }

        return Helper::searchContains($cellValueStr, $searchValue);
    };

    $orderColumn = (int) ($_POST['order'][0]['column'] ?? 1);
    $orderDirection = strtolower((string) ($_POST['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';
    $orderFields = [
        1 => 'id',
        2 => 'full_name',
        5 => 'wage_type',
        6 => 'job_start_date',
        7 => 'job_end_date',
        11 => 'job_group',
        12 => 'job',
        13 => 'ekip',
        18 => 'address',
        19 => 'description',
    ];
    $orderField = $orderFields[$orderColumn] ?? 'id';

    $counts = $personsModel->getPersonsServerSideCounts($firm_id, $authorizedIds, $status);
    $useCompatibilitySearch = $search !== '' || !empty($columnSearches);

    if ($useCompatibilitySearch) {
        $allPersons = $personsModel->getPersonsByIds($authorizedIds);
        $allBalances = $bordro->getBalances($authorizedIds);
        $allProjectNames = $projects->getProjectNamesByPersonIds($authorizedIds, $firm_id);
        $allCompanyIds = array_map(static function ($person) {
            return (int) ($person->company_id ?? 0);
        }, $allPersons);
        $allCompanyNames = $company->getCompanyNames($allCompanyIds);
        $searchFirmName = $company->getFirmName($firm_id);
        $filteredPersons = [];
        foreach ($allPersons as $person) {
            $isActive = empty($person->job_end_date);
            if (($status === 'active' && !$isActive) || ($status === 'passive' && $isActive)) {
                continue;
            }

            $searchable = [
                2 => $person->full_name ?? '',
                3 => Security::safeDecrypt($person->kimlik_no ?? ''),
                4 => $allCompanyNames[(int) ($person->company_id ?? 0)] ?? $searchFirmName,
                5 => ((int) ($person->wage_type ?? 0) === 1 ? 'Beyaz Yaka' : 'Mavi Yaka'),
                6 => $person->job_start_date ?? '',
                7 => $person->job_end_date ?? '',
                8 => Security::safeDecrypt($person->phone ?? ''),
                9 => Security::safeDecrypt($person->email ?? ''),
                10 => Security::safeDecrypt($person->iban_number ?? ''),
                11 => $person->job_group ?? '',
                12 => $person->job ?? '',
                13 => $person->ekip ?? '',
                14 => !empty($allProjectNames[(int) $person->id]) ? implode(', ', $allProjectNames[(int) $person->id]) : '',
                15 => ($person->daily_wages ?? 0) . ' ' . Helper::formattedMoney($person->daily_wages ?? 0),
                16 => $isActive ? 'Aktif' : 'Pasif',
                17 => ($allBalances[(int) $person->id] ?? 0) . ' '
                    . Helper::formattedMoney($allBalances[(int) $person->id] ?? 0),
                18 => $person->address ?? '',
                19 => ($person->description ?? '') ?: ($person->aciklama ?? ''),
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

            foreach ($columnSearches as $index => $filterInfo) {
                if (!$matchColumnFilter($searchable[$index] ?? '', $filterInfo['value'], $filterInfo['regex'])) {
                    $matches = false;
                    break;
                }
            }
            if ($matches) {
                $filteredPersons[] = $person;
            }
        }

        usort($filteredPersons, static function ($left, $right) use ($orderField, $orderDirection) {
            $comparison = strnatcasecmp((string) ($left->$orderField ?? ''), (string) ($right->$orderField ?? ''));
            return $orderDirection === 'desc' ? -$comparison : $comparison;
        });
        $counts['filtered'] = count($filteredPersons);
        $persons = array_slice($filteredPersons, $start, $length);
    } else {
        $persons = $personsModel->getPersonsServerSidePage(
            $firm_id,
            $authorizedIds,
            $start,
            $length,
            $status,
            $orderField,
            $orderDirection
        );
    }

    $pageIds = array_map(static function ($person) {
        return (int) $person->id;
    }, $persons);
    $balances = $bordro->getBalances($pageIds);
    $projectNames = $projects->getProjectNamesByPersonIds($pageIds, $firm_id);
    $companyIds = array_map(static function ($person) {
        return (int) ($person->company_id ?? 0);
    }, $persons);
    $companyNames = $company->getCompanyNames($companyIds);
    $firmName = $company->getFirmName($firm_id);

    $data = [];
    foreach ($persons as $offset => $person) {
        $encryptedId = Security::encrypt($person->id);
        $balance = $balances[(int) $person->id] ?? 0;
        $isActive = empty($person->job_end_date);
        $wageTypeBadge = (int) ($person->wage_type ?? 0) === 1
            ? '<span class="badge bg-purple-lt fw-semibold px-2 py-1" style="font-size: 11px;">Beyaz Yaka</span>'
            : '<span class="badge bg-blue-lt fw-semibold px-2 py-1" style="font-size: 11px;">Mavi Yaka</span>';
        $personName = htmlspecialchars((string) ($person->full_name ?? ''), ENT_QUOTES, 'UTF-8');
        $editPage = 'persons/manage&id=' . $encryptedId;

        $data[] = [
            '<input type="checkbox" class="form-check-input person-checkbox" value="' . $encryptedId . '">',
            $start + $offset + 1,
            '<a href="#" data-tooltip="Detay/Güncelle" data-page="' . $editPage . '" class="nav-item route-link fw-semibold text-dark">' . $personName . '</a>',
            htmlspecialchars(Security::safeDecrypt($person->kimlik_no ?? '') ?: '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($companyNames[(int) ($person->company_id ?? 0)] ?? $firmName, ENT_QUOTES, 'UTF-8'),
            $wageTypeBadge,
            htmlspecialchars((string) ($person->job_start_date ?: '-'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($person->job_end_date ?: '-'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(Security::safeDecrypt($person->phone ?? '') ?: '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(Security::safeDecrypt($person->email ?? '') ?: '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '') ?: '-', ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($person->job_group ?? '-'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($person->job ?? '-'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) ($person->ekip ?: '-'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(!empty($projectNames[(int) $person->id]) ? implode(', ', $projectNames[(int) $person->id]) : '-', ENT_QUOTES, 'UTF-8'),
            '<span class="fw-semibold">' . Helper::formattedMoney($person->daily_wages ?? 0) . '</span>',
            $isActive
                ? '<span class="badge bg-success-lt fw-semibold px-2 py-1" style="font-size: 11px;"><i class="ti ti-check me-1"></i>Aktif</span>'
                : '<span class="badge bg-danger-lt fw-semibold px-2 py-1" style="font-size: 11px;"><i class="ti ti-x me-1"></i>Pasif</span>',
            '<span class="fw-semibold ' . Helper::balanceColor($balance) . '">' . Helper::formattedMoney($balance) . '</span>',
            htmlspecialchars((string) ($person->address ?: '-'), ENT_QUOTES, 'UTF-8'),
            htmlspecialchars((string) (($person->description ?? '') ?: (($person->aciklama ?? '') ?: '-')), ENT_QUOTES, 'UTF-8'),
            '<div class="d-inline-flex align-items-center justify-content-end gap-1">
                <a href="#" class="btn btn-sm btn-icon btn-outline-primary route-link" data-page="' . $editPage . '" title="Düzenle">
                    <i class="ti ti-edit icon"></i>
                </a>
                <a href="#" class="btn btn-sm btn-icon btn-outline-danger delete-person" data-id="' . $encryptedId . '" title="Sil">
                    <i class="ti ti-trash icon"></i>
                </a>
                <div class="dropdown">
                    <button class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle dropdown-toggle-no-caret" data-bs-toggle="dropdown" title="Diğer İşlemler">
                        <i class="ti ti-dots-vertical icon"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item route-link" href="#" data-page="persons/statement&id=' . $encryptedId . '">
                            <i class="ti ti-receipt icon me-2 text-primary"></i> Hesap Ekstresi
                        </a>
                        <a class="dropdown-item route-link" href="#" data-page="puantaj/list">
                            <i class="ti ti-calendar icon me-2 text-info"></i> Puantaj Sayfası
                        </a>
                    </div>
                </div>
            </div>',
        ];
    }

    if (ob_get_length()) {
        ob_clean();
    }
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $counts['total'],
        'recordsFiltered' => $counts['filtered'],
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
