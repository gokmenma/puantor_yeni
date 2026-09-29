<?php

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Cases.php';
require_once ROOT . '/Model/CaseTransactions.php';
require_once ROOT . '/Model/DefinesModel.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/financial.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Date;
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
    if (!$auths->Authorize('income_expense_operations')) {
        throw new RuntimeException('Bu sayfayı görüntüleme yetkiniz yok.');
    }

    $casesModel = new Cases();
    $ctModel = new CaseTransactions();
    $defineModel = new DefinesModel();
    $financialHelper = new \Financial();

    $canAddUpdate = $auths->hasPermission('income_expense_add_update');
    $canDelete = $auths->hasPermission('delete_income_expense');

    $is_main_user = ($user->parent_id == 0 || (isset($user->is_main_user) && $user->is_main_user == 1));
    if ($is_main_user) {
        $firmCases = $casesModel->allCaseWithFirmId();
    } else {
        $firmCases = $casesModel->getCasesByUserIds();
    }

    $caseMap = [];
    $authorizedCaseIds = [];
    foreach ($firmCases as $fc) {
        $caseMap[(int)$fc->id] = $fc->case_name;
        $authorizedCaseIds[] = (int)$fc->id;
    }

    // Filter by selected case
    $selectedCaseParam = $_POST['case_id'] ?? '';
    $selectedCaseId = 0;
    if ($selectedCaseParam !== '' && $selectedCaseParam !== '0') {
        $decrypted = Security::decrypt($selectedCaseParam);
        $selectedCaseId = $decrypted ? (int)$decrypted : (int)$selectedCaseParam;
    }

    $targetCaseIds = [];
    if ($selectedCaseId > 0 && in_array($selectedCaseId, $authorizedCaseIds, true)) {
        $targetCaseIds = [$selectedCaseId];
    } else {
        $targetCaseIds = $authorizedCaseIds;
    }

    // Filter by transaction type (Gelir=1, Gider=2, or all)
    $typeFilterParam = trim((string)($_POST['transaction_type'] ?? ''));
    $typeIdFilter = null;
    if ($typeFilterParam === '1' || strtolower($typeFilterParam) === 'gelir') {
        $typeIdFilter = 1;
    } elseif ($typeFilterParam === '2' || strtolower($typeFilterParam) === 'gider') {
        $typeIdFilter = 2;
    }

    // Preload defines for fast subtype resolution
    $allDefines = $defineModel->all() ?: [];
    $definesMap = [];
    foreach ($allDefines as $def) {
        $definesMap[(int)$def->id] = $def->name;
    }

    $draw = max(0, (int) ($_POST['draw'] ?? 0));
    $start = max(0, (int) ($_POST['start'] ?? 0));
    $length = max(10, min(100, (int) ($_POST['length'] ?? 25)));
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
            if ($op === '=') return abs($currentVal - $targetVal) < 0.01;
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

    $orderColumn = (int) ($_POST['order'][0]['column'] ?? 2);
    $orderDirection = strtolower((string) ($_POST['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
    $orderFields = [
        0 => 'id',
        1 => 'case_id',
        2 => 'date',
        3 => 'type_id',
        4 => 'account_name',
        5 => 'amount',
        6 => 'description',
    ];
    $orderField = $orderFields[$orderColumn] ?? 'id';

    // Summary KPIs
    $summaryStats = $ctModel->getTransactionsSummaryStats($targetCaseIds, $typeIdFilter);

    $counts = $ctModel->getTransactionsServerSideCounts($targetCaseIds, $typeIdFilter);
    $useCompatibilitySearch = $search !== '' || !empty($columnSearches);

    $resolveSubTypeName = static function ($transaction) use ($definesMap, $financialHelper): string {
        $users_type_id = (int)($transaction->users_type_id ?? 0);
        $sub_type = $transaction->sub_type ?? 0;
        if ($users_type_id > 0) {
            return $definesMap[$users_type_id] ?? $financialHelper->getUsersTransactionType($users_type_id) ?? '';
        }
        return $financialHelper->getTransactionType($sub_type) ?? '';
    };

    if ($useCompatibilitySearch) {
        $allTransactions = $ctModel->getAllTransactionsForCases($targetCaseIds, $typeIdFilter);
        $filteredTransactions = [];

        foreach ($allTransactions as $t) {
            $caseName = $caseMap[(int)$t->case_id] ?? '-';
            $subTypeName = $resolveSubTypeName($t);
            $isInc = ((int)$t->type_id === 1);
            $typeLabel = $isInc ? 'Gelir' : 'Gider';
            $typeFull = $typeLabel . ' ' . $subTypeName;
            $amt = (float)($t->amount ?? 0);
            $moneyUnit = $t->amount_money ?? 1;
            $formattedAmt = Helper::formattedMoney($amt, $moneyUnit);
            $dateStr = Date::dmY($t->date);

            $searchable = [
                1 => $caseName,
                2 => $dateStr,
                3 => $typeFull,
                4 => $t->account_name ?? '',
                5 => ($isInc ? '+' : '-') . $formattedAmt . ' ' . $amt,
                6 => $t->description ?? '',
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
                $filteredTransactions[] = $t;
            }
        }

        usort($filteredTransactions, static function ($left, $right) use ($orderField, $orderDirection, $caseMap, $resolveSubTypeName) {
            if ($orderField === 'case_id') {
                $valLeft = $caseMap[(int)$left->case_id] ?? '';
                $valRight = $caseMap[(int)$right->case_id] ?? '';
            } elseif ($orderField === 'date') {
                $valLeft = strtotime($left->date ?? '') ?: 0;
                $valRight = strtotime($right->date ?? '') ?: 0;
                $res = $valLeft <=> $valRight;
                return $orderDirection === 'desc' ? -$res : $res;
            } elseif ($orderField === 'amount') {
                $valLeft = (float)($left->amount ?? 0);
                $valRight = (float)($right->amount ?? 0);
                $res = $valLeft <=> $valRight;
                return $orderDirection === 'desc' ? -$res : $res;
            } else {
                $valLeft = (string)($left->$orderField ?? '');
                $valRight = (string)($right->$orderField ?? '');
            }

            $comparison = strnatcasecmp($valLeft, $valRight);
            return $orderDirection === 'desc' ? -$comparison : $comparison;
        });

        $counts['filtered'] = count($filteredTransactions);
        $transactions = array_slice($filteredTransactions, $start, $length);
    } else {
        $transactions = $ctModel->getTransactionsServerSidePage(
            $targetCaseIds,
            $start,
            $length,
            $typeIdFilter,
            $orderField,
            $orderDirection
        );
    }

    $data = [];
    foreach ($transactions as $offset => $t) {
        $encryptedId = Security::encrypt($t->id);
        $type = (int)($t->type_id ?? 0);
        $isIncome = ($type === 1);
        $caseName = $caseMap[(int)$t->case_id] ?? '-';
        $subTypeName = $resolveSubTypeName($t);
        $moneyUnit = $t->amount_money ?? 1;
        $amount = (float)($t->amount ?? 0);
        $formattedAmount = ($isIncome ? '+' : '-') . Helper::formattedMoney($amount, $moneyUnit);
        $formattedDate = Date::dmY($t->date);

        $typeBadge = $isIncome
            ? '<span class="badge bg-success-lt text-success fw-semibold" style="font-size: 11px;"><i class="ti ti-arrow-up-right me-0.5"></i>Gelir</span>'
            : '<span class="badge bg-danger-lt text-danger fw-semibold" style="font-size: 11px;"><i class="ti ti-arrow-down-left me-0.5"></i>Gider</span>';

        $actionButtons = '<div class="d-inline-flex align-items-center justify-content-end gap-1">';
        if ($canAddUpdate) {
            $actionButtons .= '<button type="button" class="btn btn-sm btn-icon btn-outline-secondary edit-transactions" data-id="' . $encryptedId . '" data-sub-type-name="' . htmlspecialchars($subTypeName ?: '', ENT_QUOTES, 'UTF-8') . '" title="Düzenle / Detay" aria-label="Düzenle / Detay"><i class="ti ti-pencil"></i></button>';
        }
        if ($canDelete) {
            $actionButtons .= '<button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-transaction" data-id="' . $encryptedId . '" data-type="' . htmlspecialchars($t->sub_type ?? '', ENT_QUOTES, 'UTF-8') . '" title="Sil" aria-label="Sil"><i class="ti ti-trash"></i></button>';
        }
        $actionButtons .= '</div>';

        $rowItem = [
            '<span class="text-muted fw-medium">' . ($start + $offset + 1) . '</span>',
            '<span class="d-inline-flex align-items-center fw-medium text-dark"><i class="ti ti-wallet text-muted me-1.5" style="font-size: 15px;"></i>' . htmlspecialchars($caseName, ENT_QUOTES, 'UTF-8') . '</span>',
            '<span class="badge bg-secondary-lt text-secondary fw-medium" style="font-size: 11px;"><i class="ti ti-calendar me-1"></i>' . htmlspecialchars($formattedDate, ENT_QUOTES, 'UTF-8') . '</span>',
            '<div class="d-flex align-items-center flex-wrap gap-1">' . $typeBadge . '<span class="text-muted small sub-type-name">' . htmlspecialchars($subTypeName ?: '-', ENT_QUOTES, 'UTF-8') . '</span></div>',
            '<span class="fw-medium text-dark">' . htmlspecialchars($t->account_name ?: '-', ENT_QUOTES, 'UTF-8') . '</span>',
            '<span class="fw-bold ' . ($isIncome ? 'text-success' : 'text-danger') . '" style="font-size: 13.5px;">' . $formattedAmount . '</span>',
            '<span class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="' . htmlspecialchars($t->description ?? '', ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($t->description ?: '-', ENT_QUOTES, 'UTF-8') . '</span>',
            $actionButtons,
        ];

        $rowItem['DT_RowAttr'] = [
            'data-id' => $encryptedId,
            'data-type-id' => $type
        ];

        $data[] = $rowItem;
    }

    if (ob_get_length()) {
        ob_clean();
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $counts['total'],
        'recordsFiltered' => $counts['filtered'],
        'data' => $data,
        'stats' => [
            'total_count' => $summaryStats['total_count'],
            'income_count' => $summaryStats['income_count'],
            'expense_count' => $summaryStats['expense_count'],
            'total_income' => $summaryStats['total_income'],
            'total_expense' => $summaryStats['total_expense'],
            'net_balance' => $summaryStats['net_balance'],
            'formatted_total_count' => number_format($summaryStats['total_count'], 0, ',', '.'),
            'formatted_total_income' => Helper::formattedMoney($summaryStats['total_income'], 1),
            'formatted_total_expense' => Helper::formattedMoney($summaryStats['total_expense'], 1),
            'formatted_net_balance' => Helper::formattedMoney($summaryStats['net_balance'], 1)
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if (ob_get_length()) {
        ob_clean();
    }
    error_log('Transaction List Error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'draw' => (int) ($_POST['draw'] ?? 0),
        'recordsTotal' => 0,
        'recordsFiltered' => 0,
        'data' => [],
        'error' => 'Kasa hareketleri yüklenirken bir hata oluştu.'
    ], JSON_UNESCAPED_UNICODE);
}
