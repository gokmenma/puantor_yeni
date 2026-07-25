<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/MyFirmModel.php';
require_once ROOT . '/Model/DefinesModel.php';
require_once ROOT . '/Model/Puantaj.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/App/Helper/security.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/Model/SettingsModel.php';

use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

try {
    $Persons = new Persons();
    $Bordro = new Bordro();
    $MyFirm = new MyFirmModel();
    $Defines = new DefinesModel();
    $PuantajModel = new Puantaj();
    $SettingsModel = new SettingsModel();
    $Auths = new Auths();

    $overtime_rate = floatval($SettingsModel->getSettings("overtime_rate")->set_value ?? 50);
    if ($overtime_rate < 50) { $overtime_rate = 50; }
    $overtime_multiplier = 1 + ($overtime_rate / 100);

    $firm_id = $_SESSION['firm_id'] ?? 0;
    
    $id_raw = $_POST['id'] ?? '';
    $personel_id = Security::decrypt($id_raw);
    $ay = $_POST['month'] ?? date('m');
    $yil = $_POST['year'] ?? date('Y');

    if (!$personel_id) {
        throw new Exception("Geçersiz personel kimliği.");
    }

    $person = $Persons->find($personel_id);
    if (!$person) {
        throw new Exception("Personel bulunamadı.");
    }

    $buildPopoverContent = function($pt, $saatVal) use ($person, $SettingsModel, $overtime_rate, $overtime_multiplier) {
        if (!$pt || floatval($pt['tutar']) <= 0) return '';

        $wh = floatval($SettingsModel->getSettings("work_hour")->set_value ?? 8);
        $saatNum = floatval($saatVal);
        $tutarNum = floatval($pt['tutar']);

        $isOvertime = ($pt['pt_turu'] == 'Fazla Çalışma');
        if ($isOvertime) {
            $extraHours = max(0, $saatNum - $wh);
            if ($extraHours <= 0 && !empty($pt['EklenecekSaat'])) {
                $extraHours = floatval($pt['EklenecekSaat']);
            }

            $effectiveMultiplierHours = $wh + ($extraHours * $overtime_multiplier);
            $baseHourly = ($effectiveMultiplierHours > 0) ? ($tutarNum / $effectiveMultiplierHours) : 0;

            $whDisp = (floor($wh) == $wh) ? number_format($wh, 0, '.', '') : number_format($wh, 1, '.', '');
            $baseHourlyDisp = number_format($baseHourly, 2, '.', '');
            $normalPay = $baseHourly * $wh;
            $normalPayDisp = number_format($normalPay, 2, '.', '');

            $otPay = $extraHours * $baseHourly * $overtime_multiplier;
            $otPayDisp = number_format($otPay, 2, '.', '');
            $extraHoursDisp = (floor($extraHours) == $extraHours) ? number_format($extraHours, 0, '.', '') : number_format($extraHours, 1, '.', '');
            $otRateDisp = number_format($overtime_rate, 0, '.', '');

            if (($person->wage_type ?? 0) == 1) { // Beyaz Yaka
                return "{$baseHourlyDisp} * %{$otRateDisp} * {$extraHoursDisp} = {$otPayDisp} TL";
            } else { // Mavi Yaka
                return "{$baseHourlyDisp} * {$whDisp} = {$normalPayDisp}<br>" .
                       "{$baseHourlyDisp} * %{$otRateDisp} * {$extraHoursDisp} = {$otPayDisp}";
            }
        } else {
            $unitRate = $saatNum > 0 ? ($tutarNum / $saatNum) : 0;
            $saatDisp = (floor($saatNum) == $saatNum) ? number_format($saatNum, 0, '.', '') : number_format($saatNum, 1, '.', '');
            $unitRateDisp = number_format($unitRate, 2, '.', '');
            $tutarDisp = number_format($tutarNum, 2, '.', '');
            $isFullDay = ($saatNum == $wh);
            return "{$unitRateDisp} * {$saatDisp} = {$tutarDisp} TL" . ($isFullDay ? " (1 Günlük Ücret)" : "");
        }
    };

    // Personel Gelir Bilgileri
    $incomes = $Bordro->getPersonIncomeDetails($personel_id, $ay, $yil);

    // Personel Gider Bilgileri
    $expenses = $Bordro->getPersonExpenseDetails($personel_id, $ay, $yil);
    $canDeletePayment = $Auths->hasPermission('delete_staff_payment');
    $canDeleteIncomeExpense = $Auths->hasPermission('delete_income_expense');
    $showTransactionActions = $canDeletePayment || $canDeleteIncomeExpense;

    // Personel Puantaj Detayları (Günlük)
    $firstDay = Date::firstDay($ay, $yil);
    $lastDay = Date::lastDay($ay, $yil);

    $sql_pt = "SELECT pt.*, tr.PuantajAdi as puantaj_adi, tr.PuantajKod, tr.Turu as pt_turu, tr.ArkaPlanRengi, tr.FontRengi
               FROM puantaj pt 
               LEFT JOIN puantajturu tr ON tr.id = pt.puantaj_id 
               WHERE pt.person = :person_id 
               AND CAST(REPLACE(pt.gun, '-', '') AS UNSIGNED) >= :start_date 
               AND CAST(REPLACE(pt.gun, '-', '') AS UNSIGNED) <= :end_date 
               ORDER BY CAST(REPLACE(pt.gun, '-', '') AS UNSIGNED) ASC";
    $stmt_pt = $db->prepare($sql_pt);
    $stmt_pt->execute([
        ':person_id' => $personel_id,
        ':start_date' => $firstDay,
        ':end_date' => $lastDay
    ]);
    $puantaj_details = $stmt_pt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    ob_clean();
    echo '<div class="alert alert-danger p-3 mb-0">' . $e->getMessage() . '</div>';
    exit;
}
?>

<style>
.payroll-detail-modal-content {
    border: 0;
    border-radius: 16px;
    overflow: hidden;
}
.payroll-detail-hero {
    border: 1px solid rgba(var(--tblr-primary-rgb), 0.16);
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(var(--tblr-primary-rgb), 0.1), rgba(var(--tblr-primary-rgb), 0.025));
}
.payroll-detail-summary {
    height: 100%;
    border: 1px solid var(--tblr-border-color-translucent);
    border-radius: 12px;
    box-shadow: none;
}
.payroll-detail-summary .summary-icon {
    display: inline-flex;
    width: 34px;
    height: 34px;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 1.15rem;
}
.payroll-detail-card {
    border: 1px solid var(--tblr-border-color-translucent) !important;
    border-radius: 14px !important;
    box-shadow: none !important;
}
.payroll-detail-card .card-header {
    min-height: 58px;
    background: var(--tblr-bg-surface);
}
.payroll-finance-table th,
.payroll-attendance-table th {
    padding-top: 0.7rem;
    padding-bottom: 0.7rem;
    color: var(--tblr-secondary);
    font-size: 0.7rem;
    letter-spacing: 0.045em;
    text-transform: uppercase;
    white-space: nowrap;
}
.payroll-finance-table td,
.payroll-attendance-table td {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
.payroll-finance-table {
    min-width: 440px;
}
.payroll-attendance-table {
    min-width: 620px;
}
.payroll-finance-table tbody tr:hover,
.payroll-attendance-table tbody tr:hover {
    background: rgba(var(--tblr-primary-rgb), 0.035);
}
.payroll-attendance-scroll {
    max-height: 360px;
    overflow: auto;
}
.payroll-attendance-scroll thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: var(--tblr-bg-surface-secondary, var(--tblr-bg-surface));
    box-shadow: inset 0 -1px var(--tblr-border-color);
}
.payroll-day-number {
    display: inline-flex;
    width: 32px;
    height: 32px;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: var(--tblr-bg-surface-secondary);
    color: var(--tblr-body-color);
    font-weight: 700;
}
.payroll-weekend-row .payroll-day-number {
    background: rgba(var(--tblr-secondary-rgb), 0.1);
    color: var(--tblr-secondary);
}
.puantaj-calendar {
    display: table;
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
    table-layout: fixed;
}
.puantaj-calendar-row {
    display: table-row;
}
.puantaj-calendar-cell {
    display: table-cell;
    width: 14.28%;
    border: 1px solid var(--tblr-border-color);
    padding: 6px;
    vertical-align: top;
    height: 65px;
    background-color: var(--tblr-bg-surface);
}
.puantaj-calendar-header {
    font-weight: bold;
    text-align: center;
    background-color: var(--tblr-bg-light);
    height: auto !important;
    padding: 8px 4px;
}
.bg-light-lt {
    background-color: rgba(var(--tblr-light-rgb), 0.4) !important;
}
@media print {
    .no-print {
        display: none !important;
    }
    .payroll-attendance-scroll {
        max-height: none !important;
        overflow: visible !important;
    }
    .payroll-detail-card {
        break-inside: avoid;
    }
}
@media (max-width: 575.98px) {
    .payroll-detail-hero .avatar {
        width: 42px !important;
        height: 42px !important;
    }
    .payroll-detail-card .card-header {
        align-items: flex-start !important;
        flex-direction: column;
        gap: 0.75rem;
    }
    .payroll-detail-card .card-header .btn-group {
        width: 100%;
    }
    .payroll-detail-card .card-header .btn-group .btn {
        flex: 1;
    }
}
</style>

<div class="row g-3">
    <!-- Personel Bilgi Başlığı -->
    <div class="col-12">
        <div class="payroll-detail-hero">
            <div class="p-3 p-md-4 d-flex align-items-center">
                <span class="avatar avatar-md bg-primary-lt me-3 fw-bold" style="border-radius: 8px; width: 45px; height: 45px;">
                    <?= htmlspecialchars(mb_substr($person->full_name, 0, 2, 'UTF-8')) ?>
                </span>
                <div class="min-w-0">
                    <h3 class="mb-0 fw-bold text-dark"><?= htmlspecialchars($person->full_name) ?></h3>
                    <div class="text-muted small mt-1 d-flex flex-wrap gap-2 gap-md-3">
                        <span><i class="ti ti-briefcase me-1"></i><?= htmlspecialchars($person->job ?: 'Personel') ?></span>
                        <span><i class="ti ti-wallet me-1"></i><?= $person->wage_type == 1 ? 'Aylık ücret' : 'Günlük ücret' ?></span>
                    </div>
                </div>
                <span class="badge bg-primary-lt text-primary border border-primary-subtle ms-auto d-none d-sm-inline-flex align-items-center fs-6 px-3 py-2">
                    <i class="ti ti-calendar-month me-2"></i><?= htmlspecialchars(Date::monthName((int) $ay) . ' ' . $yil) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Özet Kartları -->
    <div class="col-12 col-sm-4">
        <div class="card payroll-detail-summary">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="summary-icon bg-primary-lt text-primary"><i class="ti ti-trending-up"></i></span>
                <div><div class="text-muted small mb-1">Toplam gelir</div><div class="h2 mb-0 text-primary fw-bold" id="modal-total-income">...</div></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card payroll-detail-summary">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="summary-icon bg-danger-lt text-danger"><i class="ti ti-trending-down"></i></span>
                <div><div class="text-muted small mb-1">Toplam kesinti</div><div class="h2 mb-0 text-danger fw-bold" id="modal-total-expense">...</div></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card payroll-detail-summary">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <span class="summary-icon bg-success-lt text-success"><i class="ti ti-cash"></i></span>
                <div><div class="text-muted small mb-1">Net ödenecek</div><div class="h2 mb-0 text-success fw-bold" id="modal-net-payment">...</div></div>
            </div>
        </div>
    </div>

    <!-- Gelir & Gider Listesi -->
    <div class="col-12">
        <div class="card payroll-detail-card overflow-hidden">
            <div class="card-header px-3 px-md-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-1"><i class="ti ti-receipt-2 text-primary me-2"></i>Gelir ve Kesintiler</h4>
                    <div class="text-muted small">Döneme ait bordro hareketleri</div>
                </div>
            </div>
            <div class="payroll-finance-scroll" style="max-height: 260px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                <div class="list-group list-group-mobile border-0">
                    <?php
                    $total_income = 0;
                    $total_expense = 0;
                    
                    // Gelirleri Listele
                    if (!empty($incomes)) {
                        foreach ($incomes as $income) {
                            $total_income += $income->tutar;
                            $incomeNameRaw = (string) ($income->turu ?: 'Gelir');
                            $income_name = htmlspecialchars($incomeNameRaw, ENT_QUOTES, 'UTF-8');
                            $incomeDescription = trim((string) ($income->aciklama ?? ''));
                            $incomeDescriptionHtml = '';
                            if ($incomeDescription !== '' && $incomeDescription !== $incomeNameRaw) {
                                $incomeDescriptionHtml = "<div class='text-muted text-xs opacity-75 mt-0.5'>" . htmlspecialchars($incomeDescription, ENT_QUOTES, 'UTF-8') . "</div>";
                            }
                            
                            $canDeleteIncome = $showTransactionActions && $canDeleteIncomeExpense
                                && ($income->tablename ?? '') === 'maas_gelir_kesinti'
                                && !in_array((int) ($income->kategori ?? 0), [14, 16, 17], true)
                                && !empty($income->id);

                            echo "<div class='list-group-item px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between'>
                                <div class='d-flex align-items-center gap-2.5' style='min-width: 0; flex: 1;'>
                                    <span class='badge bg-success-lt text-success rounded-circle p-1 d-flex align-items-center justify-content-center flex-shrink-0' style='width: 32px; height: 32px;'>
                                        <i class='ti ti-plus' style='font-size: 0.9rem;'></i>
                                    </span>
                                    <div style='min-width: 0; flex: 1;'>
                                        <div class='fw-bold text-dark text-truncate' style='font-size: 0.85rem;'>{$income_name}</div>
                                        {$incomeDescriptionHtml}
                                    </div>
                                </div>
                                <div class='d-flex align-items-center gap-2 flex-shrink-0 ms-2 text-end'>
                                    <span class='fw-bold text-success' style='font-size: 0.9rem;'>+₺" . Helper::formattedMoneyWithoutCurrency($income->tutar) . "</span>";
                                    if ($canDeleteIncome) {
                                        echo "<button type='button' class='btn btn-sm btn-ghost-danger btn-icon delete-payroll-transaction'
                                            data-id='" . htmlspecialchars(Security::encrypt($income->id), ENT_QUOTES, 'UTF-8') . "'
                                            data-source='maas_gelir_kesinti'
                                            data-month='" . (int) $ay . "'
                                            data-year='" . (int) $yil . "'
                                            data-label='{$income_name}'
                                            title='Geliri sil' aria-label='Geliri sil'>
                                            <i class='ti ti-trash'></i>
                                        </button>";
                                    }
                            echo "</div>
                            </div>";
                        }
                    }

                    // Giderleri Listele
                    if (!empty($expenses)) {
                        foreach ($expenses as $expense) {
                            $total_expense += $expense->tutar;
                            $is_icra = (!empty($expense->turu) && strpos($expense->turu, 'İcra') !== false);
                            if ($is_icra) {
                                $name = $expense->turu;
                                $iconClass = 'ti ti-scale';
                                $badgeClass = 'bg-purple-lt text-purple';
                            } else {
                                $name = $expense->turu ?: $Defines->getTypeNameById($expense->kategori ?? 0);
                                $iconClass = 'ti ti-minus';
                                $badgeClass = 'bg-danger-lt text-danger';
                            }
                            $name = htmlspecialchars((string) ($name ?: 'Kesinti'), ENT_QUOTES, 'UTF-8');
                            $description = trim((string) ($expense->aciklama ?? ''));
                            $descriptionHtml = '';
                            if ($description !== '' && $description !== html_entity_decode($name, ENT_QUOTES, 'UTF-8')) {
                                $descriptionHtml = "<div class='text-muted text-xs opacity-75 mt-0.5'>" . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "</div>";
                            }
                            
                            $expenseCategory = (int) ($expense->kategori ?? 0);
                            $expenseSource = (string) ($expense->tablename ?? '');
                            $isSystemDeduction = $is_icra || in_array($expenseCategory, [14, 16, 17], true);
                            $hasDeletePermission = $expenseCategory === 7 ? $canDeletePayment : $canDeleteIncomeExpense;
                            $canDeleteExpense = $showTransactionActions && $hasDeletePermission
                                && in_array($expenseSource, ['maas_gelir_kesinti', 'case_transactions'], true)
                                && !$isSystemDeduction
                                && !empty($expense->id);

                            echo "<div class='list-group-item px-3 py-2.5 border-bottom d-flex align-items-center justify-content-between'>
                                <div class='d-flex align-items-center gap-2.5' style='min-width: 0; flex: 1;'>
                                    <span class='badge {$badgeClass} rounded-circle p-1 d-flex align-items-center justify-content-center flex-shrink-0' style='width: 32px; height: 32px;'>
                                        <i class='{$iconClass}' style='font-size: 0.9rem;'></i>
                                    </span>
                                    <div style='min-width: 0; flex: 1;'>
                                        <div class='fw-bold text-dark text-truncate' style='font-size: 0.85rem;'>{$name}</div>
                                        {$descriptionHtml}
                                    </div>
                                </div>
                                <div class='d-flex align-items-center gap-2 flex-shrink-0 ms-2 text-end'>
                                    <span class='fw-bold text-danger' style='font-size: 0.9rem;'>-₺" . Helper::formattedMoneyWithoutCurrency($expense->tutar) . "</span>";
                                    if ($canDeleteExpense) {
                                        echo "<button type='button' class='btn btn-sm btn-ghost-danger btn-icon delete-payroll-transaction'
                                            data-id='" . htmlspecialchars(Security::encrypt($expense->id), ENT_QUOTES, 'UTF-8') . "'
                                            data-source='" . htmlspecialchars($expenseSource, ENT_QUOTES, 'UTF-8') . "'
                                            data-month='" . (int) $ay . "'
                                            data-year='" . (int) $yil . "'
                                            data-label='{$name}'
                                            title='Hareketi sil' aria-label='Hareketi sil'>
                                            <i class='ti ti-trash'></i>
                                        </button>";
                                    }
                            echo "</div>
                            </div>";
                        }
                    }

                    if (empty($incomes) && empty($expenses)) {
                        echo "<div class='text-center py-4 text-muted'><i class='ti ti-receipt-off d-block fs-1 mb-1'></i>Bu döneme ait hareket bulunamadı.</div>";
                    }
                    ?>
                </div>
            </div>
            <?php if (!empty($incomes) || !empty($expenses)): ?>
                <div class="px-3 py-2.5 bg-light-lt border-top d-flex align-items-center justify-content-between">
                    <span class="fw-semibold text-muted" style="font-size: 0.85rem;">Net tutar</span>
                    <span class="fw-bold text-success" style="font-size: 1rem;">₺<?= Helper::formattedMoneyWithoutCurrency(max(0, $total_income - $total_expense)) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Günlük Puantaj Detayları -->
    <div class="col-12">
        <div class="card payroll-detail-card overflow-hidden">
            <div class="card-header px-3 px-md-4 d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-1"><i class="ti ti-calendar-stats text-primary me-2"></i>Günlük Puantaj</h4>
                    <div class="text-muted small">Ayın gün bazındaki çalışma ve hakediş dökümü</div>
                </div>
                <div class="btn-group btn-group-sm no-print" role="group" aria-label="Puantaj görünümü">
                    <button type="button" class="btn btn-outline-primary active" id="btn-view-list" onclick="togglePuantajView('list')">
                        <i class="ti ti-list me-1"></i> Liste
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="btn-view-calendar" onclick="togglePuantajView('calendar')">
                        <i class="ti ti-calendar me-1"></i> Takvim
                    </button>
                </div>
            </div>
            
            <?php
            // Generate dates array for all days of the month
            $days_in_month = Date::daysInMonth($ay, $yil);
            $dates = [];
            for ($d = 1; $d <= $days_in_month; $d++) {
                $dates[] = sprintf('%04d-%02d-%02d', $yil, $ay, $d);
            }
            
            // Group puantaj records by normalized date
            $puantaj_by_date = [];
            if (!empty($puantaj_details)) {
                foreach ($puantaj_details as $pt) {
                    $dateYmd = date('Y-m-d', strtotime($pt['gun']));
                    $puantaj_by_date[$dateYmd] = $pt;
                }
            }
            ?>
            
            <!-- LIST VIEW (Mobil Uyumlu Liste Elemanları) -->
            <div id="puantaj-list-view">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 border-bottom no-print">
                    <span class="text-muted text-xs"><i class="ti ti-info-circle me-1"></i>Tutarın üzerine dokunarak hesaplamayı görebilirsiniz.</span>
                    <label class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="show-recorded-days-only">
                        <span class="form-check-label text-xs">Yalnızca kayıtlı günler</span>
                    </label>
                </div>

                <!-- Dikey Kaydırılabilir Mobil Liste Container -->
                <div class="payroll-attendance-scroll p-2" style="max-height: 380px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <div class="list-group list-group-mobile gap-1.5" id="puantaj-items-list">
                        <?php foreach ($dates as $dateStr): ?>
                            <?php
                            $pt = $puantaj_by_date[$dateStr] ?? null; 
                            $isWeekend = (date('N', strtotime($dateStr)) >= 6);
                            $dayNames = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
                            $dayName = $dayNames[(int) date('N', strtotime($dateStr))];
                            $dayNum = date('d', strtotime($dateStr));
                            $formattedDate = date('d.m.Y', strtotime($dateStr));
                            ?>
                            
                            <div class="list-group-item mobile-card p-2.5 mb-0 border-0 shadow-xs rounded-3 d-flex align-items-center justify-content-between <?= $isWeekend ? 'bg-light-lt' : '' ?> <?= $pt ? 'has-puantaj-record' : 'empty-puantaj-record' ?>">
                                
                                <!-- Sol Taraf: Gün Rozeti + Tarih & Gün İsmi -->
                                <div class="d-flex align-items-center gap-2.5" style="min-width: 0; flex: 1;">
                                    <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold" 
                                         style="width: 36px; height: 36px; font-size: 0.85rem; background: <?= $isWeekend ? 'rgba(214, 63, 63, 0.08)' : 'rgba(32, 107, 196, 0.08)'; ?>; color: <?= $isWeekend ? '#d63f3f' : '#206bc4'; ?>;">
                                        <?= $dayNum ?>
                                    </div>
                                    <div style="min-width: 0; flex: 1;">
                                        <div class="fw-bold text-dark text-truncate" style="font-size: 0.85rem; line-height: 1.2;">
                                            <?= htmlspecialchars($dayName) ?>
                                        </div>
                                        <div class="text-muted text-xs opacity-75" style="font-size: 0.7rem;">
                                            <?= $formattedDate ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sağ Taraf: Durum Rozeti + Saat & Tutar -->
                                <div class="text-end flex-shrink-0 ms-2" style="min-width: fit-content;">
                                    <div class="mb-0.5">
                                        <?php if ($pt): ?>
                                            <?php
                                            $bgColor = $pt['ArkaPlanRengi'] ?: '#d4edda';
                                            $fontColor = $pt['FontRengi'] ?: '#155724';
                                            ?>
                                            <span class="badge" style="background-color: <?php echo $bgColor; ?> !important; color: <?php echo $fontColor; ?> !important; font-size: 0.7rem; padding: 3px 7px;">
                                                <?php echo htmlspecialchars($pt['PuantajKod'] ?: $pt['puantaj_adi']); ?>
                                            </span>
                                        <?php elseif ($isWeekend): ?>
                                            <span class="badge bg-secondary-lt text-secondary" style="font-size: 0.7rem; padding: 3px 7px;">Hafta tatili</span>
                                        <?php else: ?>
                                            <span class="text-muted text-xs" style="font-size: 0.72rem;">Kayıt yok</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($pt): ?>
                                        <?php
                                        $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
                                        $tutarVal = floatval($pt['tutar']);
                                        ?>
                                        <div class="d-flex align-items-center justify-content-end gap-1.5" style="font-size: 0.75rem;">
                                            <span class="text-muted opacity-75"><?= number_format($saatVal, 1, ',', '.') ?> s</span>
                                            <?php if ($tutarVal > 0): ?>
                                                <span class="opacity-40">•</span>
                                                <?php $popoverContent = $buildPopoverContent($pt, $saatVal); ?>
                                                <span class="fw-bold text-primary cursor-pointer text-decoration-underline" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-html="true" data-bs-title="Tutar Hesaplaması" data-bs-content="<?= htmlspecialchars($popoverContent) ?>">
                                                    ₺<?= Helper::formattedMoneyWithoutCurrency($tutarVal) ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">₺0,00</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="d-none text-center py-5 text-muted" id="puantaj-filter-empty">
                    <i class="ti ti-calendar-off d-block fs-1 mb-2"></i>Kayıtlı puantaj günü bulunamadı.
                </div>
            </div>

            <!-- CALENDAR VIEW -->
            <div class="table-responsive p-2" id="puantaj-calendar-view" style="display: none;">
                <div class="puantaj-calendar mb-0">
                    <!-- Weekday headers -->
                    <div class="puantaj-calendar-row bg-light font-weight-bold text-center">
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Pzt</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Sal</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Çar</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Per</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Cum</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Cmt</div>
                        <div class="puantaj-calendar-cell puantaj-calendar-header">Paz</div>
                    </div>
                    
                    <?php
                    $firstDayOfWeek = (int) date('N', strtotime($dates[0]));
                    $weeks = [];
                    $currentWeek = array_fill(1, 7, null);
                    
                    for ($i = 1; $i < $firstDayOfWeek; $i++) {
                        $currentWeek[$i] = null;
                    }
                    
                    $dayIndex = $firstDayOfWeek;
                    foreach ($dates as $dateStr) {
                        $currentWeek[$dayIndex] = $dateStr;
                        if ($dayIndex == 7) {
                            $weeks[] = $currentWeek;
                            $currentWeek = array_fill(1, 7, null);
                            $dayIndex = 1;
                        } else {
                            $dayIndex++;
                        }
                    }
                    if ($dayIndex > 1) {
                        $weeks[] = $currentWeek;
                    }
                    
                    foreach ($weeks as $week) {
                        echo '<div class="puantaj-calendar-row">';
                        for ($i = 1; $i <= 7; $i++) {
                            $dateStr = $week[$i];
                            if ($dateStr) {
                                $pt = $puantaj_by_date[$dateStr] ?? null;
                                $dayNum = date('j', strtotime($dateStr));
                                $isWeekend = ($i >= 6);
                                $cellBg = $isWeekend ? 'background-color: rgba(var(--tblr-danger-rgb), 0.02);' : '';
                                
                                echo '<div class="puantaj-calendar-cell" style="' . $cellBg . '">';
                                echo '<div class="d-flex flex-column justify-content-between h-100">';
                                echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                                echo '<span class="fw-bold text-muted" style="font-size: 0.7rem;">' . $dayNum . '</span>';
                                
                                if ($pt) {
                                    $bgColor = $pt['ArkaPlanRengi'] ?: '#d4edda';
                                    $fontColor = $pt['FontRengi'] ?: '#155724';
                                    echo '<span class="badge" style="font-size: 0.65rem; padding: 2px 4px; background-color: ' . $bgColor . ' !important; color: ' . $fontColor . ' !important;">' . htmlspecialchars($pt['PuantajKod'] ?: $pt['puantaj_adi']) . '</span>';
                                } elseif ($isWeekend) {
                                    echo '<span class="badge bg-light text-muted" style="font-size: 0.65rem; padding: 2px 4px;">HT</span>';
                                }
                                
                                echo '</div>';
                                echo '<div class="text-end mt-auto">';
                                
                                if ($pt) {
                                    $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
                                    if (floatval($saatVal) > 0) {
                                        echo '<span class="text-secondary d-block" style="font-size: 0.65rem;">' . number_format($saatVal, 1, ',', '.') . ' Sa</span>';
                                    }
                                }
                                if ($pt && floatval($pt['tutar']) > 0) {
                                    $popoverContent = $buildPopoverContent($pt, $saatVal);
                                    echo '<span class="fw-bold text-success d-block cursor-pointer" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-html="true" data-bs-title="Tutar Hesaplaması" data-bs-content="' . htmlspecialchars($popoverContent) . '" style="font-size: 0.65rem;">₺' . Helper::formattedMoneyWithoutCurrency($pt['tutar']) . '</span>';
                                }
                                
                                echo '</div>';
                                echo '</div>';
                                echo '</div>';
                            } else {
                                echo '<div class="puantaj-calendar-cell bg-light-lt"></div>';
                            }
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $('#modal-total-income').text('₺<?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?>');
    $('#modal-total-expense').text('₺<?php echo Helper::formattedMoneyWithoutCurrency($total_expense); ?>');
    $('#modal-net-payment').text('₺<?php echo Helper::formattedMoneyWithoutCurrency(max(0, $total_income - $total_expense)); ?>');
    $('#payroll-detail-period').text(<?= json_encode(Date::monthName((int) $ay) . ' ' . $yil . ' dönemi', JSON_UNESCAPED_UNICODE) ?>);
    
    window.togglePuantajView = function(view) {
        if (view === 'list') {
            $('#puantaj-list-view').show();
            $('#puantaj-calendar-view').hide();
            $('#btn-view-list').addClass('active');
            $('#btn-view-calendar').removeClass('active');
        } else {
            $('#puantaj-list-view').hide();
            $('#puantaj-calendar-view').show();
            $('#btn-view-list').removeClass('active');
            $('#btn-view-calendar').addClass('active');
        }
    };

    $('#show-recorded-days-only').on('change', function() {
        var recordedOnly = this.checked;
        var $items = $('#puantaj-items-list .list-group-item');
        
        if (recordedOnly) {
            $items.hide().filter('.has-puantaj-record').show();
        } else {
            $items.show();
        }

        var hasVisibleItem = $items.filter(':visible').length > 0;
        $('.payroll-attendance-scroll').toggle(hasVisibleItem);
        $('#puantaj-filter-empty').toggleClass('d-none', hasVisibleItem);
    });

    $('#payroll-detail-content [data-bs-toggle="popover"]').each(function() {
        bootstrap.Popover.getOrCreateInstance(this, {
            trigger: 'hover focus',
            container: 'body'
        });
    });
</script>
