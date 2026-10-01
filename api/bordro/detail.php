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

    // Personel Gelir Bilgileri
    $incomes = $Bordro->getPersonIncomeDetails($personel_id, $ay, $yil);

    // Personel Gider Bilgileri
    $expenses = $Bordro->getPersonExpenseDetails($personel_id, $ay, $yil);
    $canDeletePayment = $Auths->hasPermission('delete_staff_payment');
    $canDeleteIncomeExpense = $Auths->hasPermission('delete_income_expense');
    $showTransactionActions = $canDeletePayment || $canDeleteIncomeExpense;

    $getPuantajCalcDetails = function($pt, $saatVal, $isWeekend = false) use ($person, $incomes, $SettingsModel, $overtime_rate, $overtime_multiplier) {
        $wh = floatval($SettingsModel->getSettings("work_hour")->set_value ?? 8);
        $saatNum = floatval($saatVal);
        $tutarNum = $pt ? floatval($pt['tutar']) : 0;
        $wageType = (int) ($person->wage_type ?? 1); // 1: Aylık Maaş, 2: Günlük Ücret

        $salary = floatval($person->daily_wages ?? 0);
        if ($salary <= 0 && !empty($incomes)) {
            foreach ($incomes as $inc) {
                if (strpos((string)($inc->turu ?? ''), 'Maaş') !== false || (int)($inc->kategori ?? 0) === 1) {
                    $salary = floatval($inc->tutar ?? 0);
                    break;
                }
            }
        }
        $dailyWage = floatval($person->daily_wages ?? 0);

        // Birim günlük ve saatlik ücret hesabı
        if ($wageType === 1) { // Aylık Maaş
            $baseDaily = ($salary > 0) ? ($salary / 30) : 0;
            $baseHourly = ($baseDaily > 0 && $wh > 0) ? ($baseDaily / $wh) : 0;
        } else { // Günlük Ücret
            $baseDaily = ($dailyWage > 0) ? $dailyWage : (($salary > 0) ? ($salary / 30) : 0);
            $baseHourly = ($baseDaily > 0 && $wh > 0) ? ($baseDaily / $wh) : 0;
        }

        if (!$pt) {
            if ($isWeekend) {
                return [
                    'title' => 'Hafta Tatili',
                    'badge' => 'Hafta Tatili',
                    'is_overtime' => false,
                    'formula' => ($wageType === 1) 
                        ? "Aylık Sabit Maaş (₺" . number_format($salary, 2, ',', '.') . " / 30 Gün) = ₺" . number_format($baseDaily, 2, ',', '.') . " / Gün"
                        : "Hafta Tatili Günü (Fiili çalışma bulunmamaktadır)",
                    'hourly_rate' => $baseHourly,
                    'daily_rate' => $baseDaily,
                    'hours' => 0,
                    'total_amount' => 0,
                    'note' => ($wageType === 1) ? 'Hafta tatili hakedişi aylık net maaşa dahildir.' : 'Hafta tatili süresince fiili mesai kaydı girilmemiştir.'
                ];
            } else {
                return [
                    'title' => 'Kayıt Yok',
                    'badge' => 'Kayıt Yok',
                    'is_overtime' => false,
                    'formula' => "Bu tarih için herhangi bir çalışma kaydı girilmemiştir.",
                    'hourly_rate' => $baseHourly,
                    'daily_rate' => $baseDaily,
                    'hours' => 0,
                    'total_amount' => 0,
                    'note' => 'Puantaj girişi bulunmamaktadır.'
                ];
            }
        }

        $isOvertime = ($pt['pt_turu'] == 'Fazla Çalışma');

        if ($isOvertime) {
            $extraHours = max(0, $saatNum - $wh);
            if ($extraHours <= 0 && !empty($pt['EklenecekSaat'])) {
                $extraHours = floatval($pt['EklenecekSaat']);
            }

            $effectiveMultiplierHours = $wh + ($extraHours * $overtime_multiplier);
            $calcBaseHourly = ($tutarNum > 0 && $effectiveMultiplierHours > 0) ? ($tutarNum / $effectiveMultiplierHours) : $baseHourly;

            $normalPay = $calcBaseHourly * $wh;
            $otPay = $extraHours * $calcBaseHourly * $overtime_multiplier;
            $calcTotal = ($tutarNum > 0) ? $tutarNum : ($normalPay + $otPay);

            if ($wageType === 1) { // Beyaz Yaka
                $formula = number_format($calcBaseHourly, 2, ',', '.') . " ₺/Sa × %" . number_format($overtime_rate, 0) . " (Mesai Çarpanı) × " . number_format($extraHours, 1, ',', '.') . " Sa = " . number_format($otPay, 2, ',', '.') . " ₺";
            } else { // Mavi Yaka
                $formula = number_format($calcBaseHourly, 2, ',', '.') . " ₺/Sa × " . number_format($wh, 1, ',', '.') . " Sa = " . number_format($normalPay, 2, ',', '.') . " ₺ (Normal Mesai)<br>" .
                           number_format($calcBaseHourly, 2, ',', '.') . " ₺/Sa × %" . number_format($overtime_rate, 0) . " × " . number_format($extraHours, 1, ',', '.') . " Sa = " . number_format($otPay, 2, ',', '.') . " ₺ (Fazla Mesai)";
            }

            return [
                'title' => $pt['puantaj_adi'] ?: 'Fazla Çalışma',
                'badge' => $pt['PuantajKod'] ?: 'FM',
                'is_overtime' => true,
                'formula' => $formula,
                'hourly_rate' => $calcBaseHourly,
                'daily_rate' => $baseDaily,
                'hours' => $saatNum,
                'extra_hours' => $extraHours,
                'overtime_rate' => $overtime_rate,
                'total_amount' => $calcTotal,
                'normal_pay' => $normalPay,
                'ot_pay' => $otPay,
                'note' => 'Mesai çarpanı (' . number_format($overtime_multiplier, 2, ',', '.') . 'x) uygulanarak hesaplanmıştır.'
            ];
        } else {
            if ($tutarNum > 0) {
                $unitRate = $saatNum > 0 ? ($tutarNum / $saatNum) : $baseHourly;
                $formula = number_format($unitRate, 2, ',', '.') . " ₺/Sa × " . number_format($saatNum, 1, ',', '.') . " Sa = " . number_format($tutarNum, 2, ',', '.') . " ₺";
                $note = ($saatNum >= $wh) ? "Tam gün standart çalışma mesaisi tamamlanmıştır." : "Kısmi çalışma süresi.";
            } else {
                $unitRate = $baseHourly;
                $calculatedEquiv = $baseHourly * $saatNum;
                if ($wageType === 1) {
                    $formula = "Aylık Sabit Maaş (₺" . number_format($salary, 2, ',', '.') . " / 30 Gün) = ₺" . number_format($baseDaily, 2, ',', '.') . " / Gün<br>" .
                               "Saatlik Karşılık: ₺" . number_format($baseHourly, 2, ',', '.') . " / Sa × " . number_format($saatNum, 1, ',', '.') . " Sa (Maaşa Dahil)";
                    $note = "Personel aylık sabit maaşlıdır. Günlük fiili mesai hakedişi aylık net maaşa yansıtılmıştır.";
                } else {
                    $formula = "Günlük Ücret: ₺" . number_format($baseDaily, 2, ',', '.') . " (₺" . number_format($baseHourly, 2, ',', '.') . " / Sa) × " . number_format($saatNum, 1, ',', '.') . " Sa = ₺" . number_format($calculatedEquiv, 2, ',', '.');
                    $note = "Günlük puantaj tarifesine göre hesaplanmıştır.";
                }
            }

            return [
                'title' => $pt['puantaj_adi'] ?: 'Normal Çalışma',
                'badge' => $pt['PuantajKod'] ?: 'X',
                'is_overtime' => false,
                'formula' => $formula,
                'hourly_rate' => $unitRate,
                'daily_rate' => $baseDaily,
                'hours' => $saatNum,
                'total_amount' => $tutarNum,
                'note' => $note
            ];
        }
    };

    $buildPopoverContent = function($pt, $saatVal, $isWeekend = false) use ($getPuantajCalcDetails) {
        $calc = $getPuantajCalcDetails($pt, $saatVal, $isWeekend);
        return $calc ? $calc['formula'] : '';
    };

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

    // Ayın günleri ve puantaj haritası
    $days_in_month = Date::daysInMonth($ay, $yil);
    $dates = [];
    for ($d = 1; $d <= $days_in_month; $d++) {
        $dates[] = sprintf('%04d-%02d-%02d', $yil, $ay, $d);
    }
    
    $puantaj_by_date = [];
    if (!empty($puantaj_details)) {
        foreach ($puantaj_details as $pt) {
            $dateYmd = date('Y-m-d', strtotime($pt['gun']));
            $puantaj_by_date[$dateYmd] = $pt;
        }
    }

    // Puantaj özet istatistikleri
    $pt_work_days = 0;
    $pt_weekend_days = 0;
    $pt_total_hours = 0;
    $pt_total_amount = 0;

    foreach ($dates as $dateStr) {
        $pt = $puantaj_by_date[$dateStr] ?? null;
        $isWeekend = (date('N', strtotime($dateStr)) >= 6);
        if ($isWeekend) {
            $pt_weekend_days++;
        }
        if ($pt) {
            $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
            $tutarVal = floatval($pt['tutar']);
            if (floatval($saatVal) > 0 || $tutarVal > 0) {
                $pt_work_days++;
            }
            $pt_total_hours += floatval($saatVal);
            $pt_total_amount += $tutarVal;
        }
    }

} catch (Exception $e) {
    ob_clean();
    echo '<div class="alert alert-danger p-3 mb-0">' . $e->getMessage() . '</div>';
    exit;
}
?>

<style>
/* Tabler ERP Design System Compliant Modal Styles */
.modal-person-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .06) !important;
}

.modal-kpi-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .06) !important;
}

.modal-section-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, .06) !important;
    overflow: hidden;
}

.modal-section-card .card-header {
    background: #ffffff !important;
    border-bottom: 1px solid #eef2f6 !important;
    padding: 14px 20px !important;
    min-height: 56px;
}

.puantaj-stat-bar {
    background: #f8fafc;
    border-bottom: 1px solid #eef2f6;
    padding: 12px 20px;
}

.puantaj-stat-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 12px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    font-size: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.transaction-sub-panel {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
}

.transaction-sub-panel-head {
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.transaction-sub-item {
    padding: 12px 16px;
    border-bottom: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    transition: background-color 0.15s ease;
}

.transaction-sub-item:hover {
    background-color: #f8fafc;
}

.transaction-sub-item:last-child {
    border-bottom: none;
}

/* Puantaj Günlük Accordion Kartı */
.puantaj-day-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 12px 16px;
    margin-bottom: 8px;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.puantaj-day-card.is-clickable {
    cursor: pointer;
}

.puantaj-day-card.is-clickable:hover {
    border-color: #94a3b8;
    background-color: #f8fafc;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.05);
}

.puantaj-day-card.is-active,
.puantaj-day-card[aria-expanded="true"] {
    border-color: #206bc4 !important;
    background-color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(32, 107, 196, 0.09) !important;
}

.puantaj-day-card.is-weekend {
    background: #fffcfc;
    border-color: #fee2e2;
}

.puantaj-day-card.is-weekend.is-clickable:hover {
    background: #fff5f5;
    border-color: #fca5a5;
}

.puantaj-chevron i {
    transition: transform 0.25s ease, color 0.2s ease;
    display: inline-block;
}

.puantaj-day-card[aria-expanded="true"] .puantaj-chevron i {
    transform: rotate(180deg);
    color: #206bc4 !important;
}

.puantaj-calc-panel {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    padding: 16px 18px;
    margin-top: 14px;
}

.puantaj-calc-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 10px 14px;
    transition: all 0.15s ease;
}

.puantaj-calc-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
}

/* 7-Kolon CSS Grid Takvim */
.puantaj-grid-container {
    display: grid !important;
    grid-template-columns: repeat(7, minmax(0, 1fr)) !important;
    gap: 8px !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

.puantaj-grid-head {
    text-align: center;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 8px 4px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 8px;
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
    padding: 8px 10px;
    min-height: 78px;
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
    background: #fffcfc;
    border-color: #fee2e2;
}

.puantaj-grid-cell.is-empty {
    background: #f8fafc;
    border: 1px dashed #e2e8f0;
    opacity: 0.4;
}

.modal-action-btn {
    width: 26px;
    height: 26px;
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

.modal-action-btn:hover {
    color: #dc2626;
    background-color: rgba(220, 38, 38, 0.1);
    border-color: rgba(220, 38, 38, 0.2);
}

/* Koyu Tema Desteği */
[data-bs-theme="dark"] .modal-person-card,
[data-bs-theme="dark"] .modal-kpi-card,
[data-bs-theme="dark"] .modal-section-card,
[data-bs-theme="dark"] .transaction-sub-panel {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

[data-bs-theme="dark"] .modal-section-card .card-header,
[data-bs-theme="dark"] .transaction-sub-panel-head {
    background: #182433 !important;
    border-bottom-color: #334155 !important;
}

[data-bs-theme="dark"] .puantaj-stat-bar {
    background-color: #141f2d !important;
    border-bottom-color: #334155 !important;
}

[data-bs-theme="dark"] .puantaj-stat-chip {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}

[data-bs-theme="dark"] .transaction-sub-item {
    border-bottom-color: #334155 !important;
}

[data-bs-theme="dark"] .transaction-sub-item:hover {
    background-color: #1e293b !important;
}

[data-bs-theme="dark"] .puantaj-day-card {
    background: #1e293b !important;
    border-color: #334155 !important;
}

[data-bs-theme="dark"] .puantaj-day-card.is-clickable:hover {
    background: #273548 !important;
    border-color: #475569 !important;
}

[data-bs-theme="dark"] .puantaj-day-card.is-weekend {
    background: #2a1b24 !important;
    border-color: #4c1d24 !important;
}

[data-bs-theme="dark"] .puantaj-calc-panel {
    background-color: #0f172a !important;
    border-color: #334155 !important;
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

<div class="row row-cards g-3">
    <!-- 1. Personel Başlık Kartı -->
    <div class="col-12">
        <div class="card modal-person-card">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md rounded-3 bg-<?= $personColor ?>-lt text-<?= $personColor ?> fw-bold fs-3 shadow-sm" style="width: 44px; height: 44px;">
                            <?= $personInitials ?>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h3 class="mb-0 fw-bold text-dark" style="font-size: 1.15rem; letter-spacing: -0.3px;">
                                    <?= htmlspecialchars($person->full_name, ENT_QUOTES, 'UTF-8') ?>
                                </h3>
                                <span class="badge bg-secondary-lt text-secondary fw-semibold" style="font-size: 10px; padding: 2px 6px;">
                                    #<?= (int) $person->id ?>
                                </span>
                            </div>
                            <div class="text-secondary small mt-0.5 d-flex flex-wrap align-items-center gap-2" style="font-size: 11.5px;">
                                <span><i class="ti ti-briefcase text-muted me-1"></i><?= htmlspecialchars($person->job ?: 'Personel', ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-muted">•</span>
                                <span><i class="ti ti-wallet text-muted me-1"></i><?= $person->wage_type == 1 ? 'Aylık Ücretli' : 'Günlük Ücretli' ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 ms-auto">
                        <span class="badge bg-secondary-lt text-secondary px-3 py-1.5 rounded-2 fw-semibold d-inline-flex align-items-center" style="font-size: 11.5px;">
                            <i class="ti ti-calendar-event me-1.5 fs-4"></i><?= htmlspecialchars(Date::monthName((int) $ay) . ' ' . $yil) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. 3'lü KPI Özet Kartları (Standard Design System) -->
    <div class="col-sm-4">
        <div class="card modal-kpi-card">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM GELİR</span>
                    <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                        <i class="ti ti-trending-up" style="font-size: 18px;"></i>
                    </div>
                </div>
                <div class="h1 mb-2 fw-bold text-dark" id="modal-total-income" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                    ₺0,00
                </div>
                <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                    <span class="text-muted" style="font-size: 11.5px;">Maaş ve Ek Kazançlar</span>
                    <span class="badge bg-success-lt fw-semibold" style="font-size: 10px; padding: 3px 8px;">Hakediş</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card modal-kpi-card">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM KESİNTİ</span>
                    <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                        <i class="ti ti-trending-down" style="font-size: 18px;"></i>
                    </div>
                </div>
                <div class="h1 mb-2 fw-bold text-dark" id="modal-total-expense" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                    ₺0,00
                </div>
                <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                    <span class="text-muted" style="font-size: 11.5px;">Ödeme ve Kesintiler</span>
                    <span class="badge bg-danger-lt fw-semibold" style="font-size: 10px; padding: 3px 8px;">Kesinti</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-4">
        <div class="card modal-kpi-card">
            <div class="card-body p-3.5">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">NET ÖDENECEK</span>
                    <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                        <i class="ti ti-wallet" style="font-size: 18px;"></i>
                    </div>
                </div>
                <div class="h1 mb-2 fw-bold text-dark" id="modal-net-payment" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                    ₺0,00
                </div>
                <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                    <span class="text-muted" style="font-size: 11.5px;">Kalan Bakiye</span>
                    <span class="badge bg-info-lt fw-semibold" style="font-size: 10px; padding: 3px 8px;">Dönem Sonu</span>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Gelir ve Kesintiler Kartı (2-Kolon Dengeli Ayrım) -->
    <?php
    $total_income = 0;
    $total_expense = 0;
    foreach ($incomes as $inc) { $total_income += $inc->tutar; }
    foreach ($expenses as $exp) { $total_expense += $exp->tutar; }
    ?>
    <?php
    $formatTxDate = function($item) {
        if (!empty($item->created_at) && $item->created_at !== '0000-00-00 00:00:00' && $item->created_at !== 'null') {
            $ts = strtotime($item->created_at);
            if ($ts > 0) {
                return date('d.m.Y', $ts);
            }
        }
        if (!empty($item->gun)) {
            return Date::dmY($item->gun);
        }
        return '';
    };
    ?>
    <div class="col-12">
        <div class="card modal-section-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                        <i class="ti ti-receipt-2" style="font-size: 18px;"></i>
                    </div>
                    <div>
                        <h4 class="card-title mb-0 fw-bold" style="font-size: 14px; letter-spacing: -0.2px;">Gelir ve Kesintiler</h4>
                        <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.2;">Döneme ait hakediş, avans ve kesinti hareketleri</p>
                    </div>
                </div>
            </div>
            
            <div class="p-4">
                <div class="row g-3">
                    <!-- Sol Kolon: Gelirler (Hakedişler) -->
                    <div class="col-md-6">
                        <div class="transaction-sub-panel">
                            <div class="transaction-sub-panel-head bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success-lt text-success p-1 rounded-2"><i class="ti ti-plus"></i></span>
                                    <span class="fw-bold text-dark" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Gelir Kalemleri</span>
                                </div>
                                <span class="badge bg-success-lt text-success fw-bold" style="font-size: 11px;">
                                    +₺<?= Helper::formattedMoneyWithoutCurrency($total_income) ?>
                                </span>
                            </div>
                            <div style="max-height: 250px; overflow-y: auto;">
                                <?php if (!empty($incomes)): ?>
                                    <?php foreach ($incomes as $income): 
                                        $incomeNameRaw = (string) ($income->turu ?: 'Gelir');
                                        $income_name = htmlspecialchars($incomeNameRaw, ENT_QUOTES, 'UTF-8');
                                        $incomeDescription = trim((string) ($income->aciklama ?? ''));
                                        $incomeDate = $formatTxDate($income);
                                        $isPuantaj = ((int) ($income->kategori ?? 0) === 14);
                                        $canDeleteIncome = $showTransactionActions && $canDeleteIncomeExpense
                                             && ($income->tablename ?? '') === 'maas_gelir_kesinti'
                                             && !in_array((int) ($income->kategori ?? 0), [14, 16, 17], true)
                                             && !empty($income->id);
                                     ?>
                                        <div class="transaction-sub-item">
                                            <div style="min-width: 0; flex: 1;" class="pe-2">
                                                <div class="fw-semibold text-dark text-truncate" style="font-size: 13px;"><?= $income_name ?></div>
                                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                                    <?php if (!$isPuantaj && !empty($incomeDate)): ?>
                                                        <span class="text-secondary small d-inline-flex align-items-center gap-1" style="font-size: 11px; font-weight: 500;">
                                                            <i class="ti ti-calendar text-muted" style="font-size: 12px;"></i>
                                                            <span>Tarih:</span>
                                                            <strong class="text-dark"><?= htmlspecialchars($incomeDate, ENT_QUOTES, 'UTF-8') ?></strong>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($incomeDescription !== '' && $incomeDescription !== $incomeNameRaw): ?>
                                                        <span class="text-muted small" style="font-size: 11px;">
                                                            <?= (!$isPuantaj && !empty($incomeDate)) ? '• ' : '' ?><?= htmlspecialchars($incomeDescription, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0 text-end">
                                                <span class="fw-bold text-success" style="font-size: 13px;">+₺<?= Helper::formattedMoneyWithoutCurrency($income->tutar) ?></span>
                                                <?php if ($canDeleteIncome): ?>
                                                    <button type="button" class="modal-action-btn delete-payroll-transaction"
                                                        data-id="<?= htmlspecialchars(Security::encrypt($income->id), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-source="maas_gelir_kesinti"
                                                        data-month="<?= (int) $ay ?>"
                                                        data-year="<?= (int) $yil ?>"
                                                        data-label="<?= $income_name ?>"
                                                        title="Geliri Sil" aria-label="Geliri sil">
                                                        <i class="ti ti-trash" style="font-size: 14px;"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4 text-muted small"><i class="ti ti-receipt-off d-block fs-2 mb-1 opacity-50"></i>Kayıtlı gelir hareketi yok.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Sağ Kolon: Kesintiler ve Ödemeler -->
                    <div class="col-md-6">
                        <div class="transaction-sub-panel">
                            <div class="transaction-sub-panel-head bg-light">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-danger-lt text-danger p-1 rounded-2"><i class="ti ti-minus"></i></span>
                                    <span class="fw-bold text-dark" style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Kesinti ve Ödemeler</span>
                                </div>
                                <span class="badge bg-danger-lt text-danger fw-bold" style="font-size: 11px;">
                                    -₺<?= Helper::formattedMoneyWithoutCurrency($total_expense) ?>
                                </span>
                            </div>
                            <div style="max-height: 250px; overflow-y: auto;">
                                <?php if (!empty($expenses)): ?>
                                    <?php foreach ($expenses as $expense): 
                                        $is_icra = (!empty($expense->turu) && strpos($expense->turu, 'İcra') !== false);
                                        $name = $expense->turu ?: $Defines->getTypeNameById($expense->kategori ?? 0);
                                        $name = htmlspecialchars((string) ($name ?: 'Kesinti'), ENT_QUOTES, 'UTF-8');
                                        $description = trim((string) ($expense->aciklama ?? ''));
                                        $expenseCategory = (int) ($expense->kategori ?? 0);
                                        $expenseSource = (string) ($expense->tablename ?? '');
                                        $expenseDate = $formatTxDate($expense);
                                        $isPayment = ($expenseCategory === 7 || mb_stripos($name, 'Ödeme') !== false || mb_stripos($name, 'Maaş') !== false);
                                        $isSystemDeduction = $is_icra || in_array($expenseCategory, [14, 16, 17], true);
                                        $hasDeletePermission = $expenseCategory === 7 ? $canDeletePayment : $canDeleteIncomeExpense;
                                        $canDeleteExpense = $showTransactionActions && $hasDeletePermission
                                             && in_array($expenseSource, ['maas_gelir_kesinti', 'case_transactions'], true)
                                             && !$isSystemDeduction
                                             && !empty($expense->id);
                                     ?>
                                        <div class="transaction-sub-item">
                                            <div style="min-width: 0; flex: 1;" class="pe-2">
                                                <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                    <span class="fw-semibold text-dark text-truncate" style="font-size: 13px;"><?= $name ?></span>
                                                    <?php if ($is_icra): ?>
                                                        <span class="badge bg-purple-lt text-purple" style="font-size: 9.5px; padding: 2px 6px;">İcra</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                                    <?php if (!empty($expenseDate)): ?>
                                                        <span class="text-secondary small d-inline-flex align-items-center gap-1" style="font-size: 11px; font-weight: 500;">
                                                            <i class="ti ti-calendar text-muted" style="font-size: 12px;"></i>
                                                            <span><?= $isPayment ? 'Ödeme Tarihi:' : 'Tarih:' ?></span>
                                                            <strong class="text-dark"><?= htmlspecialchars($expenseDate, ENT_QUOTES, 'UTF-8') ?></strong>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($description !== '' && $description !== html_entity_decode($name, ENT_QUOTES, 'UTF-8') && !str_starts_with($description, html_entity_decode($name, ENT_QUOTES, 'UTF-8'))): ?>
                                                        <span class="text-muted small" style="font-size: 11px;">
                                                            <?= !empty($expenseDate) ? '• ' : '' ?><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0 text-end">
                                                <span class="fw-bold text-danger" style="font-size: 13px;">-₺<?= Helper::formattedMoneyWithoutCurrency($expense->tutar) ?></span>
                                                <?php if ($canDeleteExpense): ?>
                                                    <button type="button" class="modal-action-btn delete-payroll-transaction"
                                                        data-id="<?= htmlspecialchars(Security::encrypt($expense->id), ENT_QUOTES, 'UTF-8') ?>"
                                                        data-source="<?= htmlspecialchars($expenseSource, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-month="<?= (int) $ay ?>"
                                                        data-year="<?= (int) $yil ?>"
                                                        data-label="<?= $name ?>"
                                                        title="Hareketi Sil" aria-label="Hareketi sil">
                                                        <i class="ti ti-trash" style="font-size: 14px;"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4 text-muted small"><i class="ti ti-receipt-off d-block fs-2 mb-1 opacity-50"></i>Kayıtlı kesinti veya ödeme yok.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Kart Altı Net Bakiye Özeti -->
            <div class="px-4 py-3 border-top d-flex align-items-center justify-content-between flex-wrap gap-2" style="background: #f8fafc; border-color: #eef2f6 !important;">
                <div class="d-flex align-items-center gap-2.5 flex-wrap">
                    <span class="fw-bold text-muted small" style="font-size: 11.5px; letter-spacing: 0.5px;">DÖNEM HESAP ÖZETİ:</span>
                    <span class="badge bg-success-lt text-success px-2.5 py-1" style="font-size: 11.5px;">Gelir: ₺<?= Helper::formattedMoneyWithoutCurrency($total_income) ?></span>
                    <span class="badge bg-danger-lt text-danger px-2.5 py-1" style="font-size: 11.5px;">Kesinti: ₺<?= Helper::formattedMoneyWithoutCurrency($total_expense) ?></span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="fw-bold text-secondary small" style="font-size: 11.5px;">NET KALAN:</span>
                    <span class="fw-bold text-dark" style="font-size: 1.2rem; letter-spacing: -0.3px;">₺<?= Helper::formattedMoneyWithoutCurrency(max(0, $total_income - $total_expense)) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Günlük Puantaj Kartı (Detaylı İstatistikler + Akordeon Liste & 7-Kolon Takvim) -->
    <div class="col-12">
        <div class="card modal-section-card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                        <i class="ti ti-calendar-stats" style="font-size: 18px;"></i>
                    </div>
                    <div>
                        <h4 class="card-title mb-0 fw-bold" style="font-size: 14px; letter-spacing: -0.2px;">Günlük Puantaj</h4>
                        <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.2;">Ayın gün bazındaki çalışma ve hakediş dökümü</p>
                    </div>
                </div>
                <div class="btn-group btn-group-sm no-print" role="group" aria-label="Puantaj görünümü" style="height: 32px;">
                    <button type="button" class="btn btn-outline-secondary active py-1 px-3 d-inline-flex align-items-center" id="btn-view-list" onclick="togglePuantajView('list')">
                        <i class="ti ti-list me-1.5" style="font-size: 15px;"></i> Liste
                    </button>
                    <button type="button" class="btn btn-outline-secondary py-1 px-3 d-inline-flex align-items-center" id="btn-view-calendar" onclick="togglePuantajView('calendar')">
                        <i class="ti ti-calendar me-1.5" style="font-size: 15px;"></i> Takvim
                    </button>
                </div>
            </div>

            <!-- Mini İstatistik Özeti Barı -->
            <div class="puantaj-stat-bar d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="puantaj-stat-chip">
                        <i class="ti ti-calendar-check text-primary" style="font-size: 15px;"></i>
                        <span class="text-muted" style="font-size: 11.5px;">Çalışma:</span>
                        <strong class="text-dark" style="font-size: 12px;"><?= $pt_work_days ?> Gün</strong>
                    </div>
                    <div class="puantaj-stat-chip">
                        <i class="ti ti-coffee text-danger" style="font-size: 15px;"></i>
                        <span class="text-muted" style="font-size: 11.5px;">Hafta Sonu:</span>
                        <strong class="text-dark" style="font-size: 12px;"><?= $pt_weekend_days ?> Gün</strong>
                    </div>
                    <div class="puantaj-stat-chip">
                        <i class="ti ti-clock text-info" style="font-size: 15px;"></i>
                        <span class="text-muted" style="font-size: 11.5px;">Toplam Süre:</span>
                        <strong class="text-dark" style="font-size: 12px;"><?= number_format($pt_total_hours, 1, ',', '.') ?> Sa</strong>
                    </div>
                    <div class="puantaj-stat-chip">
                        <i class="ti ti-coin text-success" style="font-size: 15px;"></i>
                        <span class="text-muted" style="font-size: 11.5px;">Puantaj Hakedişi:</span>
                        <strong class="text-success" style="font-size: 12px;">₺<?= Helper::formattedMoneyWithoutCurrency($pt_total_amount) ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- LIST VIEW (Akordeon Hesaplama Detaylı) -->
            <div id="puantaj-list-view">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-4 py-2.5 border-bottom no-print" style="background: #ffffff; border-color: #eef2f6 !important;">
                    <span class="text-muted" style="font-size: 11.5px;">
                        <i class="ti ti-cursor-text text-primary me-1"></i>Detaylı hesaplama formülünü görmek için ilgili gün satırına tıklayın.
                    </span>
                    <label class="form-check form-switch mb-0 cursor-pointer">
                        <input class="form-check-input cursor-pointer" type="checkbox" id="show-recorded-days-only">
                        <span class="form-check-label text-muted fw-semibold" style="font-size: 11.5px;">Yalnızca kayıtlı günler</span>
                    </label>
                </div>

                <!-- Dikey Kaydırılabilir Liste -->
                <div class="px-4 py-3" style="max-height: 460px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
                    <div class="d-flex flex-column gap-2" id="puantaj-items-list">
                        <?php foreach ($dates as $dateStr): ?>
                            <?php
                            $pt = $puantaj_by_date[$dateStr] ?? null; 
                            $isWeekend = (date('N', strtotime($dateStr)) >= 6);
                            $dayNames = [1 => 'Pazartesi', 2 => 'Salı', 3 => 'Çarşamba', 4 => 'Perşembe', 5 => 'Cuma', 6 => 'Cumartesi', 7 => 'Pazar'];
                            $dayName = $dayNames[(int) date('N', strtotime($dateStr))];
                            $dayNum = date('d', strtotime($dateStr));
                            $formattedDate = date('d.m.Y', strtotime($dateStr));

                            $saatVal = 0;
                            $tutarVal = 0;
                            if ($pt) {
                                $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
                                $tutarVal = floatval($pt['tutar']);
                            }
                            $calc = $getPuantajCalcDetails($pt, $saatVal, $isWeekend);
                            $collapseId = 'pt-calc-' . $dayNum . '-' . substr(md5($dateStr), 0, 6);
                            ?>
                            
                            <div class="puantaj-day-card is-clickable <?= $isWeekend ? 'is-weekend' : '' ?> <?= $pt ? 'has-puantaj-record' : 'empty-puantaj-record' ?>"
                                 data-bs-toggle="collapse" data-bs-target="#<?= $collapseId ?>" aria-expanded="false" role="button">
                                
                                <!-- Üst Satır: Gün Başlığı + Değerler -->
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                    <!-- Sol: Gün No + Gün Adı + Tarih -->
                                    <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1;">
                                        <div class="avatar avatar-sm rounded-2 d-flex align-items-center justify-content-center flex-shrink-0 fw-bold" 
                                             style="width: 38px; height: 38px; font-size: 13px; background: <?= $isWeekend ? 'rgba(239, 68, 68, 0.1)' : 'rgba(32, 107, 196, 0.1)'; ?>; color: <?= $isWeekend ? '#dc2626' : '#206bc4'; ?>;">
                                            <?= $dayNum ?>
                                        </div>
                                        <div style="min-width: 0; flex: 1;">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-bold text-dark" style="font-size: 13.5px; line-height: 1.2;">
                                                    <?= htmlspecialchars($dayName, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <span class="text-muted" style="font-size: 12px;">(<?= $formattedDate ?>)</span>
                                            </div>
                                            <div class="text-secondary small mt-0.5" style="font-size: 11.5px;">
                                                <?php if ($pt): ?>
                                                    <span class="text-dark fw-medium"><?= htmlspecialchars($pt['puantaj_adi'] ?: 'Puantaj', ENT_QUOTES, 'UTF-8') ?></span>
                                                <?php elseif ($isWeekend): ?>
                                                    <span class="text-danger fw-medium">Hafta Tatili</span>
                                                <?php else: ?>
                                                    <span class="text-muted">Kayıt Yok</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sağ: Durum Rozeti + Saat & Tutar & Chevron -->
                                    <div class="d-flex align-items-center gap-2.5 flex-shrink-0 ms-auto">
                                        <?php if ($pt): ?>
                                            <?php
                                             $bgColor = $pt['ArkaPlanRengi'] ?: '#dcfce7';
                                             $fontColor = $pt['FontRengi'] ?: '#166534';
                                             ?>
                                            <span class="badge fw-semibold" style="background-color: <?php echo $bgColor; ?> !important; color: <?php echo $fontColor; ?> !important; font-size: 11px; padding: 4px 8px; border-radius: 6px;">
                                                <?php echo htmlspecialchars($pt['PuantajKod'] ?: $pt['puantaj_adi'], ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                            <span class="badge bg-light text-secondary border fw-semibold" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">
                                                <?= number_format($saatVal, 1, ',', '.') ?> Sa
                                            </span>
                                            <span class="fw-bold <?= $tutarVal > 0 ? 'text-primary' : 'text-dark' ?>" style="font-size: 13.5px; min-width: 85px; text-align: right;">
                                                ₺<?= Helper::formattedMoneyWithoutCurrency($tutarVal) ?>
                                            </span>
                                        <?php elseif ($isWeekend): ?>
                                            <span class="badge bg-danger-lt text-danger fw-semibold" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">Hafta Tatili</span>
                                            <span class="badge bg-light text-secondary border fw-semibold" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">0,0 Sa</span>
                                            <span class="text-muted" style="font-size: 13px; min-width: 85px; text-align: right;">₺0,00</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt text-secondary" style="font-size: 11px; padding: 4px 8px; border-radius: 6px;">Kayıt Yok</span>
                                            <span class="text-muted" style="font-size: 13px; min-width: 85px; text-align: right;">-</span>
                                        <?php endif; ?>
                                        <div class="puantaj-chevron text-muted ms-1" style="width: 20px; text-align: center;">
                                            <i class="ti ti-chevron-down" style="font-size: 16px;"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Accordion Detay Gövdesi (Tıklanınca Açılır) -->
                                <div class="collapse" id="<?= $collapseId ?>">
                                    <div class="puantaj-calc-panel">
                                        
                                        <!-- Header of Panel -->
                                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-2.5 mb-3 border-bottom" style="border-color: #e2e8f0 !important;">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-xs rounded-2 bg-primary-lt text-primary" style="width: 28px; height: 28px;">
                                                    <i class="ti ti-calculator" style="font-size: 15px;"></i>
                                                </div>
                                                <strong class="text-dark" style="font-size: 13.5px;">Hesaplama ve Hakediş Detayı</strong>
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-primary-lt text-primary fw-semibold px-2.5 py-1" style="font-size: 11px;">
                                                    <?= htmlspecialchars($calc['title'] ?? ($pt['pt_turu'] ?? 'Normal Mesai'), ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                                <span class="badge bg-secondary-lt text-secondary fw-semibold px-2.5 py-1" style="font-size: 11px;">
                                                    <?= ((int) ($person->wage_type ?? 1)) === 1 ? 'Aylık Maaşlı' : 'Günlük Ücretli' ?>
                                                </span>
                                            </div>
                                        </div>

                                        <!-- 3'lü KPI Parametre Kartları -->
                                        <div class="row g-2.5 mb-3">
                                            <div class="col-sm-4">
                                                <div class="puantaj-calc-card h-100">
                                                    <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 10.5px; letter-spacing: 0.5px;">Birim Ücret</div>
                                                    <div class="fw-bold text-dark" style="font-size: 14px;">
                                                        ₺<?= number_format($calc['hourly_rate'] ?? 0, 2, ',', '.') ?> <span class="text-muted fw-normal" style="font-size: 11.5px;">/ Saat</span>
                                                    </div>
                                                    <div class="text-muted small mt-1" style="font-size: 11px;">
                                                        <?= ((int) ($person->wage_type ?? 1)) === 1 ? '₺' . number_format($calc['daily_rate'] ?? 0, 2, ',', '.') . ' / Günlük Pay' : 'Günlük Ücret Esaslı' ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-sm-4">
                                                <div class="puantaj-calc-card h-100">
                                                    <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 10.5px; letter-spacing: 0.5px;">Çalışma Süresi</div>
                                                    <div class="fw-bold text-dark" style="font-size: 14px;">
                                                        <?= number_format($calc['hours'] ?? 0, 1, ',', '.') ?> <span class="text-muted fw-normal" style="font-size: 11.5px;">Saat</span>
                                                    </div>
                                                    <div class="text-muted small mt-1" style="font-size: 11px;">
                                                        <?= ($calc['hours'] ?? 0) >= $wh ? 'Tam Gün Standart Mesai' : (($calc['hours'] ?? 0) > 0 ? 'Kısmi / Esnek Süre' : 'Çalışma Yok') ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-sm-4">
                                                <div class="puantaj-calc-card h-100">
                                                    <div class="text-muted text-uppercase fw-bold mb-1" style="font-size: 10.5px; letter-spacing: 0.5px;">Günlük Hakediş</div>
                                                    <div class="fw-bold <?= ($calc['total_amount'] ?? 0) > 0 ? 'text-success' : 'text-dark' ?>" style="font-size: 14px;">
                                                        <?= ($calc['total_amount'] ?? 0) > 0 ? '₺' . Helper::formattedMoneyWithoutCurrency($calc['total_amount']) : (((int) ($person->wage_type ?? 1)) === 1 ? 'Maaşa Dahil' : '₺0,00') ?>
                                                    </div>
                                                    <div class="text-muted small mt-1" style="font-size: 11px;">
                                                        <?= ($calc['total_amount'] ?? 0) > 0 ? 'Dönem Ek Kazancı' : 'Aylık Sabit Maaş Kapsamında' ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Formül Kutusu -->
                                        <div class="p-3 bg-white rounded-2 border mb-2.5" style="border-color: #e2e8f0 !important;">
                                            <div class="text-muted fw-bold text-uppercase mb-1.5" style="font-size: 10.5px; letter-spacing: 0.5px;">
                                                <i class="ti ti-function text-primary me-1"></i>Hesaplama Formülü ve Matematiksel Açıklama:
                                            </div>
                                            <div class="font-monospace fw-semibold text-dark" style="font-size: 12.5px; line-height: 1.6;">
                                                <?= $calc['formula'] ?? 'Hesaplama formülü bulunamadı.' ?>
                                            </div>
                                        </div>

                                        <!-- Bilgi / Açıklama Notu -->
                                        <?php if (!empty($calc['note'])): ?>
                                            <div class="p-2.5 rounded-2 d-flex align-items-center gap-2" style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; font-size: 11.5px;">
                                                <i class="ti ti-info-circle flex-shrink-0" style="font-size: 15px;"></i>
                                                <span><?= htmlspecialchars($calc['note'], ENT_QUOTES, 'UTF-8') ?></span>
                                            </div>
                                        <?php endif; ?>

                                    </div>
                                </div>

                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="d-none text-center py-5 text-muted" id="puantaj-filter-empty">
                    <i class="ti ti-calendar-off d-block fs-1 mb-2 opacity-50"></i>Kayıtlı puantaj günü bulunamadı.
                </div>
            </div>

            <!-- CALENDAR VIEW (Modern 7-Kolon CSS Grid) -->
            <div class="p-3.5" id="puantaj-calendar-view" style="display: none;">
                <div class="puantaj-grid-container">
                    <!-- 7 Gün Başlığı -->
                    <div class="puantaj-grid-head">Pzt</div>
                    <div class="puantaj-grid-head">Sal</div>
                    <div class="puantaj-grid-head">Çar</div>
                    <div class="puantaj-grid-head">Per</div>
                    <div class="puantaj-grid-head">Cum</div>
                    <div class="puantaj-grid-head is-weekend-head">Cmt</div>
                    <div class="puantaj-grid-head is-weekend-head">Paz</div>

                    <?php
                    $firstDayOfWeek = (int) date('N', strtotime($dates[0]));
                    // Başlangıç boş hücreleri
                    for ($i = 1; $i < $firstDayOfWeek; $i++) {
                        echo '<div class="puantaj-grid-cell is-empty"></div>';
                    }

                    // Ayın günleri
                    foreach ($dates as $dateStr) {
                        $pt = $puantaj_by_date[$dateStr] ?? null;
                        $dayNum = (int) date('j', strtotime($dateStr));
                        $dayOfWeek = (int) date('N', strtotime($dateStr));
                        $isWeekend = ($dayOfWeek >= 6);
                        $cellClass = $isWeekend ? 'is-weekend-cell' : '';

                        echo '<div class="puantaj-grid-cell ' . $cellClass . '">';
                        echo '<div class="d-flex justify-content-between align-items-center mb-1">';
                        echo '<span class="fw-bold ' . ($isWeekend ? 'text-danger' : 'text-dark') . '" style="font-size: 11.5px;">' . $dayNum . '</span>';

                        if ($pt) {
                            $bgColor = $pt['ArkaPlanRengi'] ?: '#dcfce7';
                            $fontColor = $pt['FontRengi'] ?: '#166534';
                            echo '<span class="badge fw-semibold" style="font-size: 9px; padding: 2px 5px; border-radius: 4px; background-color: ' . $bgColor . ' !important; color: ' . $fontColor . ' !important;">' . htmlspecialchars($pt['PuantajKod'] ?: $pt['puantaj_adi'], ENT_QUOTES, 'UTF-8') . '</span>';
                        } elseif ($isWeekend) {
                            echo '<span class="badge bg-danger-lt text-danger fw-semibold" style="font-size: 9px; padding: 2px 5px; border-radius: 4px;">HT</span>';
                        }

                        echo '</div>';
                        echo '<div class="text-end mt-auto">';

                        if ($pt) {
                            $saatVal = ($pt['pt_turu'] != 'Saatlik') ? $PuantajModel->getPuantajSaatiByfirm($pt['puantaj_id']) : $pt['saat'];
                            if (floatval($saatVal) > 0) {
                                echo '<span class="text-muted d-block" style="font-size: 10px;">' . number_format($saatVal, 1, ',', '.') . ' Sa</span>';
                            }
                            if (floatval($pt['tutar']) > 0) {
                                $popoverContent = $buildPopoverContent($pt, $saatVal);
                                echo '<span class="fw-bold text-primary d-block cursor-pointer" data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-placement="top" data-bs-html="true" data-bs-title="Tutar Hesaplaması" data-bs-content="' . htmlspecialchars($popoverContent) . '" style="font-size: 11px;">₺' . Helper::formattedMoneyWithoutCurrency($pt['tutar']) . '</span>';
                            }
                        }

                        echo '</div>';
                        echo '</div>';
                    }

                    // Kalan boş hücreler
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
    
    if (typeof window.updatePayrollTableRow === 'function') {
        window.updatePayrollTableRow('<?= htmlspecialchars($id_raw, ENT_QUOTES, 'UTF-8') ?>', {
            income: <?= (float)$total_income ?>,
            expense: <?= (float)$total_expense ?>,
            net: <?= (float)($total_income - $total_expense) ?>,
            formatted_income: '<?= Helper::formattedMoney($total_income) ?>',
            formatted_expense: '<?= Helper::formattedMoney($total_expense) ?>',
            formatted_net: '<?= Helper::formattedMoney($total_income - $total_expense) ?>'
        });
    }
    
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
        var $items = $('#puantaj-items-list .puantaj-day-card');
        
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

    $('#payroll-detail-content .collapse').on('show.bs.collapse', function() {
        $(this).closest('.puantaj-day-card').addClass('is-active').attr('aria-expanded', 'true');
    }).on('hide.bs.collapse', function() {
        $(this).closest('.puantaj-day-card').removeClass('is-active').attr('aria-expanded', 'false');
    });
</script>
