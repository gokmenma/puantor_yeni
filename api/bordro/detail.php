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

    if (!function_exists('getPersonInitials')) {
        function getPersonInitials($name) {
            $words = preg_split('/\s+/', trim($name));
            $initials = "";
            foreach ($words as $w) {
                if (!empty($w)) {
                    $initials .= mb_substr($w, 0, 1, 'UTF-8');
                }
            }
            return mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
        }
    }

    $avatarColors = ['primary', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];
    $personColor = $avatarColors[$person->id % count($avatarColors)];
    $personInitials = getPersonInitials($person->full_name);

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
/* Modern Bordro Detail Styles */
.payroll-hero-card {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
}
.payroll-kpi-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
    position: relative;
    overflow: hidden;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.payroll-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
}
.payroll-kpi-card.kpi-income::before {
    background: #16a34a;
}
.payroll-kpi-card.kpi-expense::before {
    background: #dc2626;
}
.payroll-kpi-card.kpi-net::before {
    background: #2563eb;
}
.payroll-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(15, 23, 42, .08);
}
.payroll-section-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .04);
    overflow: hidden;
}
.payroll-section-card .card-header {
    background: #ffffff;
    border-bottom: 1px solid #f1f5f9;
    padding: 12px 18px;
}
.payroll-item-row {
    padding: 12px 18px;
    border-bottom: 1px solid #f1f5f9;
    transition: background-color 0.15s ease;
}
.payroll-item-row:hover {
    background-color: #f8fafc;
}
.payroll-item-row:last-child {
    border-bottom: none;
}
.puantaj-day-row {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 6px;
    transition: all 0.15s ease;
}
.puantaj-day-row:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
    box-shadow: 0 2px 6px rgba(15, 23, 42, .04);
}
.puantaj-day-row.is-weekend {
    background: #fffafa;
    border-color: #fee2e2;
}
.puantaj-day-row.is-weekend:hover {
    background: #fff5f5;
    border-color: #fca5a5;
}

/* 7-Kolon CSS Grid Takvim */
.puantaj-grid-calendar {
    display: grid !important;
    grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
    gap: 8px !important;
    width: 100% !important;
}
.puantaj-grid-head {
    text-align: center;
    font-weight: 700;
    font-size: 11.5px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 4px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
}
.puantaj-grid-head.is-weekend-head {
    background: #fef2f2;
    color: #dc2626;
    border-color: #fee2e2;
}
.puantaj-grid-cell {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 7px 8px;
    min-height: 74px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: all 0.15s ease;
}
.puantaj-grid-cell:hover {
    border-color: #94a3b8;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06);
}
.puantaj-grid-cell.is-weekend-cell {
    background: #fffafa;
    border-color: #fee2e2;
}
.puantaj-grid-cell.is-empty {
    background: #f8fafc;
    border: 1px dashed #e2e8f0;
    opacity: 0.45;
}

.puantaj-action-btn {
    width: 30px;
    height: 30px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    color: #94a3b8;
    border: 1px solid transparent;
    background: transparent;
    transition: all 0.15s ease;
}
.puantaj-action-btn:hover {
    color: #dc2626;
    background-color: rgba(220, 38, 38, 0.1);
    border-color: rgba(220, 38, 38, 0.2);
}

/* Dark theme overrides */
[data-bs-theme="dark"] .payroll-hero-card,
[data-bs-theme="dark"] .payroll-kpi-card,
[data-bs-theme="dark"] .payroll-section-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}
[data-bs-theme="dark"] .payroll-section-card .card-header {
    background: #182433 !important;
    border-bottom-color: #334155 !important;
}
[data-bs-theme="dark"] .payroll-item-row {
    border-bottom-color: #334155 !important;
}
[data-bs-theme="dark"] .payroll-item-row:hover {
    background-color: #1e293b !important;
}
[data-bs-theme="dark"] .puantaj-day-row {
    background: #1e293b !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] .puantaj-day-row:hover {
    background: #273548 !important;
    border-color: #475569 !important;
}
[data-bs-theme="dark"] .puantaj-day-row.is-weekend {
    background: #2a1b24 !important;
    border-color: #4c1d24 !important;
}
[data-bs-theme="dark"] .puantaj-grid-head {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #94a3b8 !important;
}
[data-bs-theme="dark"] .puantaj-grid-head.is-weekend-head {
    background-color: #2a1b24 !important;
    border-color: #4c1d24 !important;
    color: #f87171 !important;
}
[data-bs-theme="dark"] .puantaj-grid-cell {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] .puantaj-grid-cell.is-weekend-cell {
    background-color: #2a1b24 !important;
    border-color: #4c1d24 !important;
}
[data-bs-theme="dark"] .puantaj-grid-cell.is-empty {
    background-color: #0f172a !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #payroll-detail-content {
    background-color: #0f172a !important;
}
</style>

<div class="row g-3">
    <!-- Personel Bilgi Başlığı (Hero Card) -->
    <div class="col-12">
        <div class="payroll-hero-card p-3 p-md-3.5">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar avatar-md rounded-3 bg-<?= $personColor ?>-lt text-<?= $personColor ?> fw-bold fs-3 shadow-sm" style="width: 50px; height: 50px;">
                        <?= $personInitials ?>
                    </span>
                    <div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <h3 class="mb-0 fw-bold text-dark" style="font-size: 1.2rem; letter-spacing: -0.2px;">
                                <?= htmlspecialchars($person->full_name, ENT_QUOTES, 'UTF-8') ?>
                            </h3>
                            <span class="badge bg-secondary-lt text-secondary px-2 py-0.5 rounded-pill" style="font-size: 11px;">
                                <i class="ti ti-id me-1"></i>#<?= (int) $person->id ?>
                            </span>
                        </div>
                        <div class="text-secondary small mt-1 d-flex flex-wrap align-items-center gap-2 gap-md-3" style="font-size: 12.5px;">
                            <span><i class="ti ti-briefcase text-primary me-1"></i><?= htmlspecialchars($person->job ?: 'Personel', ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="text-muted">•</span>
                            <span><i class="ti ti-wallet text-primary me-1"></i><?= $person->wage_type == 1 ? 'Aylık Ücretli' : 'Günlük Ücretli' ?></span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <span class="badge bg-dark-lt text-dark border border-dark-subtle px-3 py-1.5 rounded-pill fw-semibold d-inline-flex align-items-center" style="font-size: 12.5px;">
                        <i class="ti ti-calendar-event text-primary me-1.5 fs-5"></i><?= htmlspecialchars(Date::monthName((int) $ay) . ' ' . $yil) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3'lü KPI Özet Kartları -->
    <div class="col-12 col-sm-4">
        <div class="card payroll-kpi-card kpi-income">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM GELİR</span>
                    <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                        <i class="ti ti-trending-up" style="font-size: 18px;"></i>
                    </div>
                </div>
                <div class="h1 mb-0 fw-bold text-success" id="modal-total-income" style="font-size: 1.4rem; letter-spacing: -0.3px; line-height: 1.25;">
                    ₺0,00
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card payroll-kpi-card kpi-expense">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM KESİNTİ</span>
                    <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                        <i class="ti ti-trending-down" style="font-size: 18px;"></i>
                    </div>
                </div>
                <div class="h1 mb-0 fw-bold text-danger" id="modal-total-expense" style="font-size: 1.4rem; letter-spacing: -0.3px; line-height: 1.25;">
                    ₺0,00
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-4">
        <div class="card payroll-kpi-card kpi-net">
            <div class="card-body p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">NET ÖDENECEK</span>
                    <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                        <i class="ti ti-wallet" style="font-size: 18px;"></i>
                    </div>
                </div>
                <div class="h1 mb-0 fw-bold text-dark" id="modal-net-payment" style="font-size: 1.4rem; letter-spacing: -0.3px; line-height: 1.25;">
                    ₺0,00
                </div>
            </div>
        </div>
    </div>

    <!-- Gelir & Kesintiler Listesi -->
    <div class="col-12">
        <div class="card payroll-section-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded-2 bg-primary-lt text-primary" style="width: 28px; height: 28px;">
                        <i class="ti ti-receipt-2 fs-4"></i>
                    </div>
                    <div>
                        <h4 class="card-title fw-bold text-dark mb-0" style="font-size: 0.95rem;">Gelir ve Kesintiler</h4>
                        <div class="text-secondary small" style="font-size: 11.5px;">Döneme ait hakediş, avans ve kesinti hareketleri</div>
                    </div>
                </div>
            </div>
            <div style="max-height: 260px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                <div class="list-group list-group-flush border-0">
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
                                $incomeDescriptionHtml = "<div class='text-muted small opacity-75 mt-0.5' style='font-size: 11.5px;'>" . htmlspecialchars($incomeDescription, ENT_QUOTES, 'UTF-8') . "</div>";
                            }
                            
                            $canDeleteIncome = $showTransactionActions && $canDeleteIncomeExpense
                                && ($income->tablename ?? '') === 'maas_gelir_kesinti'
                                && !in_array((int) ($income->kategori ?? 0), [14, 16, 17], true)
                                && !empty($income->id);

                            echo "<div class='payroll-item-row d-flex align-items-center justify-content-between'>
                                <div class='d-flex align-items-center gap-2.5' style='min-width: 0; flex: 1;'>
                                    <span class='avatar avatar-sm rounded-circle bg-success-lt text-success d-flex align-items-center justify-content-center flex-shrink-0' style='width: 32px; height: 32px;'>
                                        <i class='ti ti-plus fw-bold' style='font-size: 15px;'></i>
                                    </span>
                                    <div style='min-width: 0; flex: 1;'>
                                        <div class='fw-semibold text-dark text-truncate' style='font-size: 13.5px;'>{$income_name}</div>
                                        {$incomeDescriptionHtml}
                                    </div>
                                </div>
                                <div class='d-flex align-items-center gap-2 flex-shrink-0 ms-3 text-end'>
                                    <span class='fw-bold text-success' style='font-size: 14px;'>+₺" . Helper::formattedMoneyWithoutCurrency($income->tutar) . "</span>";
                                    if ($canDeleteIncome) {
                                        echo "<button type='button' class='puantaj-action-btn delete-payroll-transaction'
                                            data-id='" . htmlspecialchars(Security::encrypt($income->id), ENT_QUOTES, 'UTF-8') . "'
                                            data-source='maas_gelir_kesinti'
                                            data-month='" . (int) $ay . "'
                                            data-year='" . (int) $yil . "'
                                            data-label='{$income_name}'
                                            title='Geliri Sil' aria-label='Geliri sil'>
                                            <i class='ti ti-trash' style='font-size: 15px;'></i>
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
                                $descriptionHtml = "<div class='text-muted small opacity-75 mt-0.5' style='font-size: 11.5px;'>" . htmlspecialchars($description, ENT_QUOTES, 'UTF-8') . "</div>";
                            }
                            
                            $expenseCategory = (int) ($expense->kategori ?? 0);
                            $expenseSource = (string) ($expense->tablename ?? '');
                            $isSystemDeduction = $is_icra || in_array($expenseCategory, [14, 16, 17], true);
                            $hasDeletePermission = $expenseCategory === 7 ? $canDeletePayment : $canDeleteIncomeExpense;
                            $canDeleteExpense = $showTransactionActions && $hasDeletePermission
                                && in_array($expenseSource, ['maas_gelir_kesinti', 'case_transactions'], true)
                                && !$isSystemDeduction
                                && !empty($expense->id);

                            echo "<div class='payroll-item-row d-flex align-items-center justify-content-between'>
                                <div class='d-flex align-items-center gap-2.5' style='min-width: 0; flex: 1;'>
                                    <span class='avatar avatar-sm rounded-circle {$badgeClass} d-flex align-items-center justify-content-center flex-shrink-0' style='width: 32px; height: 32px;'>
                                        <i class='{$iconClass} fw-bold' style='font-size: 15px;'></i>
                                    </span>
                                    <div style='min-width: 0; flex: 1;'>
                                        <div class='fw-semibold text-dark text-truncate' style='font-size: 13.5px;'>{$name}</div>
                                        {$descriptionHtml}
                                    </div>
                                </div>
                                <div class='d-flex align-items-center gap-2 flex-shrink-0 ms-3 text-end'>
                                    <span class='fw-bold text-danger' style='font-size: 14px;'>-₺" . Helper::formattedMoneyWithoutCurrency($expense->tutar) . "</span>";
                                    if ($canDeleteExpense) {
                                        echo "<button type='button' class='puantaj-action-btn delete-payroll-transaction'
                                            data-id='" . htmlspecialchars(Security::encrypt($expense->id), ENT_QUOTES, 'UTF-8') . "'
                                            data-source='" . htmlspecialchars($expenseSource, ENT_QUOTES, 'UTF-8') . "'
                                            data-month='" . (int) $ay . "'
                                            data-year='" . (int) $yil . "'
                                            data-label='{$name}'
                                            title='Hareketi Sil' aria-label='Hareketi sil'>
                                            <i class='ti ti-trash' style='font-size: 15px;'></i>
                                        </button>";
                                    }
                            echo "</div>
                            </div>";
                        }
                    }

                    if (empty($incomes) && empty($expenses)) {
                        echo "<div class='text-center py-4 text-muted'><i class='ti ti-receipt-off d-block fs-1 mb-1 opacity-50'></i>Bu döneme ait hareket bulunamadı.</div>";
                    }
                    ?>
                </div>
            </div>
            <?php if (!empty($incomes) || !empty($expenses)): ?>
                <div class="px-3.5 py-2.5 bg-light border-top d-flex align-items-center justify-content-between">
                    <span class="fw-semibold text-secondary small" style="font-size: 12.5px;">NET KALAN TUTAR</span>
                    <span class="fw-bold text-success fs-3">₺<?= Helper::formattedMoneyWithoutCurrency(max(0, $total_income - $total_expense)) ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Günlük Puantaj Detayları -->
    <div class="col-12">
        <div class="card payroll-section-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-xs rounded-2 bg-primary-lt text-primary" style="width: 28px; height: 28px;">
                        <i class="ti ti-calendar-stats fs-4"></i>
                    </div>
                    <div>
                        <h4 class="card-title fw-bold text-dark mb-0" style="font-size: 0.95rem;">Günlük Puantaj</h4>
                        <div class="text-secondary small" style="font-size: 11.5px;">Ayın gün bazındaki çalışma ve hakediş dökümü</div>
                    </div>
                </div>
                <div class="btn-group btn-group-sm no-print" role="group" aria-label="Puantaj görünümü">
                    <button type="button" class="btn btn-outline-primary active py-1 px-3" id="btn-view-list" onclick="togglePuantajView('list')">
                        <i class="ti ti-list me-1"></i> Liste
                    </button>
                    <button type="button" class="btn btn-outline-primary py-1 px-3" id="btn-view-calendar" onclick="togglePuantajView('calendar')">
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
            
            <!-- LIST VIEW -->
            <div id="puantaj-list-view">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3.5 py-2 bg-light border-bottom no-print">
                    <span class="text-secondary small d-flex align-items-center" style="font-size: 11.5px;">
                        <i class="ti ti-info-circle text-primary me-1 fs-4"></i>Tutarın üzerine dokunarak hesaplama formülünü görebilirsiniz.
                    </span>
                    <label class="form-check form-switch mb-0 cursor-pointer">
                        <input class="form-check-input cursor-pointer" type="checkbox" id="show-recorded-days-only">
                        <span class="form-check-label text-secondary small fw-medium" style="font-size: 12px;">Yalnızca kayıtlı günler</span>
                    </label>
                </div>

                <!-- Dikey Kaydırılabilir Liste -->
                <div class="p-2.5" style="max-height: 380px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <div class="d-flex flex-column" id="puantaj-items-list">
                        <?php foreach ($dates as $dateStr): ?>
                            <?php
                            $pt = $puantaj_by_date[$dateStr] ?? null; 
                            $isWeekend = (date('N', strtotime($dateStr)) >= 6);
                            $dayNames = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
                            $dayName = $dayNames[(int) date('N', strtotime($dateStr))];
                            $dayNum = date('d', strtotime($dateStr));
                            $formattedDate = date('d.m.Y', strtotime($dateStr));
                            ?>
                            
                            <div class="puantaj-day-row d-flex align-items-center justify-content-between <?= $isWeekend ? 'is-weekend' : '' ?> <?= $pt ? 'has-puantaj-record' : 'empty-puantaj-record' ?>">
                                
                                <!-- Sol: Gün Rozeti + Tarih -->
                                <div class="d-flex align-items-center gap-2.5" style="min-width: 0; flex: 1;">
                                    <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 fw-bold shadow-xs" 
                                         style="width: 34px; height: 34px; font-size: 12px; background: <?= $isWeekend ? 'rgba(239, 68, 68, 0.12)' : 'rgba(32, 107, 196, 0.12)'; ?>; color: <?= $isWeekend ? '#dc2626' : '#206bc4'; ?>;">
                                        <?= $dayNum ?>
                                    </div>
                                    <div style="min-width: 0; flex: 1;">
                                        <div class="fw-semibold text-dark text-truncate" style="font-size: 13.5px; line-height: 1.2;">
                                            <?= htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                        <div class="text-secondary small mt-0.5" style="font-size: 11.5px;">
                                            <?= $formattedDate ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Sağ: Durum Rozeti + Saat & Tutar -->
                                <div class="text-end flex-shrink-0 ms-2">
                                    <div class="mb-0.5">
                                        <?php if ($pt): ?>
                                            <?php
                                            $bgColor = $pt['ArkaPlanRengi'] ?: '#dcfce7';
                                            $fontColor = $pt['FontRengi'] ?: '#166534';
                                            ?>
                                            <span class="badge rounded-pill fw-bold" style="background-color: <?php echo $bgColor; ?> !important; color: <?php echo $fontColor; ?> !important; font-size: 11px; padding: 3px 9px;">
                                                <?php echo htmlspecialchars($pt['PuantajKod'] ?: $pt['puantaj_adi'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php elseif ($isWeekend): ?>
                                            <span class="badge bg-danger-lt text-danger rounded-pill fw-semibold" style="font-size: 11px; padding: 3px 9px;">Hafta Tatili</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt text-secondary rounded-pill" style="font-size: 11px; padding: 3px 9px;">Kayıt Yok</span>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($pt): ?>
                                        <?php
                                        $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
                                        $tutarVal = floatval($pt['tutar']);
                                        ?>
                                        <div class="d-flex align-items-center justify-content-end gap-1.5" style="font-size: 12.5px;">
                                            <span class="text-secondary"><?= number_format($saatVal, 1, ',', '.') ?> s</span>
                                            <?php if ($tutarVal > 0): ?>
                                                <span class="text-muted opacity-40">•</span>
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
                    <i class="ti ti-calendar-off d-block fs-1 mb-2 opacity-50"></i>Kayıtlı puantaj günü bulunamadı.
                </div>
            </div>

            <!-- CALENDAR VIEW (Modern CSS Grid 7 Columns) -->
            <div class="p-3" id="puantaj-calendar-view" style="display: none;">
                <div class="puantaj-grid-calendar">
                    <!-- 7 Weekday Headers -->
                    <div class="puantaj-grid-head">Pzt</div>
                    <div class="puantaj-grid-head">Sal</div>
                    <div class="puantaj-grid-head">Çar</div>
                    <div class="puantaj-grid-head">Per</div>
                    <div class="puantaj-grid-head">Cum</div>
                    <div class="puantaj-grid-head is-weekend-head">Cmt</div>
                    <div class="puantaj-grid-head is-weekend-head">Paz</div>

                    <?php
                    $firstDayOfWeek = (int) date('N', strtotime($dates[0]));
                    // Leading empty cells
                    for ($i = 1; $i < $firstDayOfWeek; $i++) {
                        echo '<div class="puantaj-grid-cell is-empty"></div>';
                    }

                    // Days of month
                    foreach ($dates as $dateStr) {
                        $pt = $puantaj_by_date[$dateStr] ?? null;
                        $dayNum = (int) date('j', strtotime($dateStr));
                        $dayOfWeek = (int) date('N', strtotime($dateStr));
                        $isWeekend = ($dayOfWeek >= 6);
                        $cellClass = $isWeekend ? 'is-weekend-cell' : '';

                        echo '<div class="puantaj-grid-cell ' . $cellClass . '">';
                        echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                        echo '<span class="fw-bold ' . ($isWeekend ? 'text-danger' : 'text-dark') . '" style="font-size: 12px;">' . $dayNum . '</span>';

                        if ($pt) {
                            $bgColor = $pt['ArkaPlanRengi'] ?: '#dcfce7';
                            $fontColor = $pt['FontRengi'] ?: '#166534';
                            echo '<span class="badge rounded-pill fw-bold" style="font-size: 10px; padding: 2px 6px; background-color: ' . $bgColor . ' !important; color: ' . $fontColor . ' !important;">' . htmlspecialchars($pt['PuantajKod'] ?: $pt['puantaj_adi'], ENT_QUOTES, 'UTF-8') . '</span>';
                        } elseif ($isWeekend) {
                            echo '<span class="badge bg-danger-lt text-danger rounded-pill" style="font-size: 10px; padding: 2px 6px;">HT</span>';
                        }

                        echo '</div>';
                        echo '<div class="text-end mt-auto">';

                        if ($pt) {
                            $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
                            if (floatval($saatVal) > 0) {
                                echo '<span class="text-secondary d-block" style="font-size: 11px;">' . number_format($saatVal, 1, ',', '.') . ' Sa</span>';
                            }
                            if (floatval($pt['tutar']) > 0) {
                                $popoverContent = $buildPopoverContent($pt, $saatVal);
                                echo '<span class="fw-bold text-primary d-block cursor-pointer text-decoration-underline" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-html="true" data-bs-title="Tutar Hesaplaması" data-bs-content="' . htmlspecialchars($popoverContent) . '" style="font-size: 11.5px;">₺' . Helper::formattedMoneyWithoutCurrency($pt['tutar']) . '</span>';
                            }
                        }

                        echo '</div>';
                        echo '</div>';
                    }

                    // Trailing empty cells to fill the row
                    $lastDayOfWeek = (int) date('N', strtotime(end($dates)));
                    if ($lastDayOfWeek < 7) {
                        for ($i = $lastDayOfWeek + 1; $i <= 7; $i++) {
                            echo '<div class="puantaj-grid-cell is-empty"></div>';
                        }
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
        var $items = $('#puantaj-items-list .puantaj-day-row');
        
        if (recordedOnly) {
            $items.hide().filter('.has-puantaj-record').show();
        } else {
            $items.show();
        }

        var hasVisibleItem = $items.filter(':visible').length > 0;
        $('#puantaj-items-list').parent().toggle(hasVisibleItem);
        $('#puantaj-filter-empty').toggleClass('d-none', hasVisibleItem);
    });

    $('#payroll-detail-content [data-bs-toggle="popover"]').each(function() {
        bootstrap.Popover.getOrCreateInstance(this, {
            trigger: 'hover focus',
            container: 'body'
        });
    });
</script>
