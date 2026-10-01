<?php

use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\Date;

require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/MyFirmModel.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/DefinesModel.php';
require_once ROOT . '/Model/SettingsModel.php';
require_once ROOT . '/Model/Wages.php';
require_once ROOT . '/Model/ActivityLogModel.php';

// Yetki Kontrolü
if (!isset($Auths)) {
    require_once ROOT . '/Model/Auths.php';
    $Auths = new Auths();
}
$Auths->checkAuthorize('payroll_page');

$Persons = new Persons();
$Bordro = new Bordro();
$MyFirm = new MyFirmModel();
$Defines = new DefinesModel();
$SettingsModel = new SettingsModel();
$WagesModel = new Wages();

$firm_id = $_SESSION['firm_id'] ?? 0;
$firm = $MyFirm->find($firm_id);

if (ob_get_level() > 0) {
    ob_end_clean();
}

$raw_id = $_GET['id'] ?? '';
$personel_id = Security::safeDecrypt($raw_id);
if (!$personel_id && is_numeric($raw_id)) {
    $personel_id = (int)$raw_id;
}

$raw_month = $_GET['month'] ?? '';
$ay = Security::safeDecrypt($raw_month);
if (!$ay && is_numeric($raw_month)) {
    $ay = (int)$raw_month;
}
if (!$ay) $ay = (int)date('n');

$raw_year = $_GET['year'] ?? '';
$yil = Security::safeDecrypt($raw_year);
if (!$yil && is_numeric($raw_year)) {
    $yil = (int)$raw_year;
}
if (!$yil) $yil = (int)date('Y');

$person = $Persons->find($personel_id);

// Firma/Personel eşleşme doğrulaması
if (!$person || $person->firm_id != $firm_id) {
    header("Location: /yetkisiz-erisim");
    exit();
}

// Log kaydı oluştur
ActivityLogModel::log('payroll', 'print', "Personel {$person->full_name} için {$yil}-{$ay} dönemi maaş bordrosu görüntülendi.");

// Personel Gelir & Gider Bilgileri
$incomes = $Bordro->getPersonIncome($personel_id, $ay, $yil);
$expenses = $Bordro->getPersonExpense($personel_id, $ay, $yil);

$overtime_rate = floatval($SettingsModel->getSettings("overtime_rate")->set_value ?? 50);
if ($overtime_rate < 50) { $overtime_rate = 50; }
$overtime_multiplier = 1 + ($overtime_rate / 100);
$work_hour = floatval($SettingsModel->getSettings("work_hour")->set_value ?? 8);

$first_day = Date::firstDay($ay, $yil);
$last_day = Date::lastDay($ay, $yil);

$db = $Bordro->connect();
$stmt = $db->prepare("SELECT p.*, pt.PuantajAdi, pt.PuantajKod, pt.Turu, pt.EklenecekSaat, pt.operant 
                      FROM puantaj p 
                      LEFT JOIN puantajturu pt ON p.puantaj_id = pt.id 
                      WHERE p.person = :person_id 
                        AND p.gun >= :first_day AND p.gun <= :last_day 
                        AND p.tutar > 0");
$stmt->execute([':person_id' => $personel_id, ':first_day' => $first_day, ':last_day' => $last_day]);
$puantaj_rows = $stmt->fetchAll(PDO::FETCH_OBJ);

$normal_days_count = 0;
$normal_hours_sum = 0;
$normal_tutar_sum = 0;

$overtime_hours_sum = 0;
$overtime_tutar_sum = 0;

$saatlik_hours_sum = 0;
$saatlik_tutar_sum = 0;

foreach ($puantaj_rows as $row) {
    $saat = floatval($row->saat);
    $tutar = floatval($row->tutar);
    $is_overtime = ($row->Turu == 'Fazla Çalışma');
    
    if ($is_overtime) {
        $extra_hours = max(0, $saat - $work_hour);
        if ($extra_hours <= 0 && !empty($row->EklenecekSaat)) {
            $extra_hours = floatval($row->EklenecekSaat);
        }
        
        $eff_mult = $work_hour + ($extra_hours * $overtime_multiplier);
        $base_hourly = ($eff_mult > 0) ? ($tutar / $eff_mult) : 0;
        
        $normal_pay = $base_hourly * $work_hour;
        $ot_pay = $extra_hours * $base_hourly * $overtime_multiplier;
        
        if (($person->wage_type ?? 0) != 1) { // Mavi Yaka
            $normal_days_count += 1;
            $normal_hours_sum += $work_hour;
            $normal_tutar_sum += $normal_pay;
        }
        
        $overtime_hours_sum += $extra_hours;
        $overtime_tutar_sum += $ot_pay;
    } elseif ($row->Turu == 'Saatlik') {
        $saatlik_hours_sum += $saat;
        $saatlik_tutar_sum += $tutar;
    } else { // Normal Çalışma, Tatil vb.
        if (($person->wage_type ?? 0) != 1) { // Mavi Yaka
            $normal_days_count += 1;
            $normal_hours_sum += $saat;
            $normal_tutar_sum += $tutar;
        }
    }
}

$defined_wage = $WagesModel->getWageByPersonIdAndDate($personel_id, $first_day)->amount ?? 0;
$effective_wage = ($defined_wage > 0) ? floatval($defined_wage) : floatval($person->daily_wages ?? 0);

if (($person->wage_type ?? 0) == 1) { // Beyaz Yaka (Aylık)
    $effective_daily = $effective_wage / 30;
    $effective_hourly = ($work_hour > 0) ? ($effective_daily / $work_hour) : 0;
} else { // Mavi Yaka (Günlük)
    $effective_daily = $effective_wage;
    $effective_hourly = ($work_hour > 0) ? ($effective_wage / $work_hour) : 0;
}

$total_income = 0;
foreach ($incomes as $income) {
    $total_income += $income->tutar;
}

$total_expense = 0;
foreach ($expenses as $expense) {
    $total_expense += $expense->tutar;
}

$net_pay = $total_income - $total_expense;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ücret Bordrosu - <?= htmlspecialchars($person->full_name) ?> (<?= Date::monthName($ay) ?> <?= $yil ?>)</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/dist/css/tabler-icons.min.css">
    <style>
        :root {
            --primary: #0054a6;
            --primary-dark: #003b73;
            --primary-light: #eef3f8;
            --secondary: #64748b;
            --dark: #0f172a;
            --success: #16a34a;
            --success-light: #f0fdf4;
            --danger: #dc2626;
            --danger-light: #fef2f2;
            --border: #dbe3ec;
            --border-light: #f1f5f9;
            --bg-page: #eef3f8;
            --bg-card: #ffffff;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            margin: 0;
            padding: 0;
            background-color: var(--bg-page);
            color: var(--dark);
            font-size: 12px;
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }

        /* Üst Kontrol & Navigasyon Çubuğu */
        .print-control-bar {
            position: sticky;
            top: 0;
            z-index: 999;
            background: rgba(255, 255, 255, 0.94);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
        }

        .control-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .control-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e0f2fe;
            color: #0369a1;
            font-weight: 600;
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 6px;
        }

        .control-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.15s ease;
            text-decoration: none;
            border: 1px solid transparent;
        }

        .btn-print-primary {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 2px 6px rgba(0, 84, 166, 0.25);
        }

        .btn-print-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
            color: #ffffff;
        }

        .btn-close-window {
            background-color: #ffffff;
            color: var(--secondary);
            border-color: var(--border);
        }

        .btn-close-window:hover {
            background-color: #f8fafc;
            color: var(--dark);
        }

        /* A4 Bordro Kartı */
        .bordro-sheet {
            max-width: 820px;
            margin: 28px auto;
            background: var(--bg-card);
            border-radius: 12px;
            border: 1px solid var(--border);
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
            padding: 36px 40px;
            position: relative;
        }

        /* Kurumsal Başlık */
        .sheet-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 22px;
        }

        .company-meta {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .company-icon-box {
            width: 48px;
            height: 48px;
            background: #eef3f8;
            color: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 700;
            border: 1px solid #dbe3ec;
        }

        .company-title {
            margin: 0 0 3px 0;
            font-size: 17px;
            font-weight: 700;
            color: var(--dark);
            letter-spacing: -0.3px;
        }

        .company-subtitle {
            margin: 0;
            font-size: 11px;
            color: var(--secondary);
            line-height: 1.35;
        }

        .bordro-badge-box {
            text-align: right;
        }

        .bordro-main-title {
            margin: 0 0 4px 0;
            font-size: 19px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.3px;
        }

        .bordro-period-pill {
            display: inline-block;
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            font-size: 11.5px;
            padding: 4px 12px;
            border-radius: 6px;
            border: 1px solid var(--border);
        }

        /* Bilgi Gridleri (Personel & Ücret) */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .info-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px 14px;
        }

        .info-card-header {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }

        .info-card-header i {
            color: var(--primary);
            font-size: 14px;
        }

        .info-card-body {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11.5px;
            padding: 2px 0;
        }

        .info-label {
            color: var(--secondary);
            font-weight: 500;
        }

        .info-value {
            color: var(--dark);
            font-weight: 600;
            text-align: right;
        }

        /* Kazanç ve Kesintiler İki Kolon Tablo Yapısı */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .detail-box {
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            display: flex;
            flex-direction: column;
        }

        .detail-box-header {
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            letter-spacing: 0.3px;
        }

        .header-income {
            background: #0f172a;
            color: #ffffff;
        }

        .header-expense {
            background: #dc2626;
            color: #ffffff;
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11.5px;
            flex-grow: 1;
        }

        .detail-table td {
            padding: 7px 12px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .detail-table tr:last-child td {
            border-bottom: none;
        }

        .detail-item-title {
            color: #334155;
            font-weight: 500;
        }

        .detail-item-sub {
            font-size: 9.5px;
            color: var(--secondary);
            margin-top: 1px;
        }

        .detail-item-amount {
            text-align: right;
            font-weight: 600;
            color: var(--dark);
            white-space: nowrap;
        }

        .detail-total-row {
            border-top: 1.5px solid var(--border);
            font-weight: 700;
        }

        .total-income-bg {
            background: #f8fafc;
            color: var(--dark);
        }

        .total-expense-bg {
            background: #fef2f2;
            color: var(--danger);
        }

        /* Net Tutar Vurgu Kartı */
        .summary-card {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1.5px solid #86efac;
            border-radius: 10px;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 28px;
        }

        .summary-label-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .summary-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            background: #16a34a;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .summary-title {
            font-size: 13px;
            font-weight: 700;
            color: #166534;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .summary-amount {
            font-size: 22px;
            font-weight: 800;
            color: #14532d;
            letter-spacing: -0.3px;
        }

        /* İmza Alanı */
        .signature-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 36px;
            padding-top: 14px;
        }

        .signature-box {
            text-align: center;
            border-top: 1.5px solid #cbd5e1;
            padding-top: 8px;
        }

        .signature-title {
            font-weight: 700;
            font-size: 12px;
            color: var(--dark);
            margin-bottom: 34px;
        }

        .signature-disclaimer {
            font-size: 9px;
            color: var(--secondary);
            line-height: 1.35;
        }

        /* Yazıcı Çıktı Standardı (A4 Print Kuralları) */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }

            .print-control-bar {
                display: none !important;
            }

            .bordro-sheet {
                margin: 0 !important;
                padding: 24px 28px !important;
                max-width: 100% !important;
                width: 100% !important;
                border: none !important;
                box-shadow: none !important;
                page-break-after: auto;
            }

            .summary-card {
                background: #f0fdf4 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .header-income {
                background: #0f172a !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            .header-expense {
                background: #dc2626 !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

<!-- Kontrol Barı (Yalnızca Ekranda Görünür) -->
<div class="print-control-bar">
    <div class="control-info">
        <span class="control-badge">
            <i class="ti ti-calendar"></i> <?= Date::monthName($ay) ?> <?= $yil ?>
        </span>
        <span style="font-weight: 600; color: #334155;">
            Personel: <strong class="text-dark"><?= htmlspecialchars($person->full_name) ?></strong>
        </span>
    </div>
    <div class="control-actions">
        <button type="button" onclick="window.close()" class="btn-action btn-close-window">
            <i class="ti ti-x"></i> Kapat
        </button>
        <button type="button" onclick="window.print()" class="btn-action btn-print-primary">
            <i class="ti ti-printer"></i> Yazdır / PDF Kaydet
        </button>
    </div>
</div>

<div class="bordro-sheet">
    <!-- Header -->
    <div class="sheet-header">
        <div class="company-meta">
            <div class="company-icon-box">
                <i class="ti ti-building"></i>
            </div>
            <div>
                <h1 class="company-title"><?= htmlspecialchars($firm->firm_name ?? 'Firma Adı') ?></h1>
                <p class="company-subtitle">
                    <?= htmlspecialchars($firm->address ?? '') ?><br>
                    <?= htmlspecialchars($firm->phone ?? '') ?> <?= !empty($firm->email) ? '| ' . htmlspecialchars($firm->email) : '' ?>
                </p>
            </div>
        </div>
        <div class="bordro-badge-box">
            <h2 class="bordro-main-title">ÜCRET BORDROSU</h2>
            <div class="bordro-period-pill">
                <i class="ti ti-calendar me-1"></i> <?= Date::monthName($ay) ?> / <?= $yil ?>
            </div>
        </div>
    </div>

    <!-- Info Grid (Personel ve Ücret Bilgileri) -->
    <div class="info-grid">
        <!-- Personel Bilgileri Kartı -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="ti ti-user"></i> Personel Bilgileri
            </div>
            <div class="info-card-body">
                <div class="info-item">
                    <span class="info-label">Adı Soyadı:</span>
                    <span class="info-value text-dark fw-bold"><?= htmlspecialchars($person->full_name) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">T.C. Kimlik No:</span>
                    <span class="info-value"><?= htmlspecialchars(Security::safeDecrypt($person->kimlik_no ?? '')) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Görevi / Ünvan:</span>
                    <span class="info-value"><?= htmlspecialchars($person->job ?? '-') ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">İşe Giriş Tarihi:</span>
                    <span class="info-value"><?= htmlspecialchars($person->job_start_date ?? '-') ?></span>
                </div>
            </div>
        </div>

        <!-- Ödeme ve Ücret Parametreleri -->
        <div class="info-card">
            <div class="info-card-header">
                <i class="ti ti-credit-card"></i> Ödeme Bilgileri
            </div>
            <div class="info-card-body">
                <div class="info-item">
                    <span class="info-label">IBAN:</span>
                    <span class="info-value" style="font-family: monospace; font-size: 11px;">
                        <?= htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '-')) ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Ücret Türü:</span>
                    <span class="info-value">
                        <?= $person->wage_type == 1 ? 'Aylık Maaş (Beyaz Yaka)' : 'Günlük Ücret (Mavi Yaka)' ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><?= $person->wage_type == 1 ? 'Aylık Maaş:' : 'Günlük Ücret:' ?></span>
                    <span class="info-value text-primary">₺<?= Helper::formattedMoneyWithoutCurrency($effective_wage) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Saatlik Ücret:</span>
                    <span class="info-value">₺<?= Helper::formattedMoneyWithoutCurrency($effective_hourly) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Kazançlar ve Kesintiler -->
    <div class="details-grid">
        <!-- Kazançlar Kolonu -->
        <div class="detail-box">
            <div class="detail-box-header header-income">
                <span><i class="ti ti-trending-up me-1"></i> KAZANÇLAR / HAKEDİŞLER</span>
                <span>TUTAR (TL)</span>
            </div>
            <table class="detail-table">
                <tbody>
                    <?php if (($person->wage_type ?? 0) != 1 && $normal_tutar_sum > 0): ?>
                    <tr>
                        <td>
                            <div class="detail-item-title">Normal Çalışma</div>
                            <div class="detail-item-sub">
                                <?= $normal_days_count ?> Gün / <?= number_format($normal_hours_sum, 1, ',', '.') ?> Saat
                            </div>
                        </td>
                        <td class="detail-item-amount"><?= Helper::formattedMoneyWithoutCurrency($normal_tutar_sum) ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($overtime_tutar_sum > 0): ?>
                    <tr>
                        <td>
                            <div class="detail-item-title text-primary">Fazla Mesai (%<?= number_format($overtime_rate, 0) ?>)</div>
                            <div class="detail-item-sub">
                                <?= number_format($overtime_hours_sum, 1, ',', '.') ?> Saat Fazla Çalışma
                            </div>
                        </td>
                        <td class="detail-item-amount text-primary"><?= Helper::formattedMoneyWithoutCurrency($overtime_tutar_sum) ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($saatlik_tutar_sum > 0): ?>
                    <tr>
                        <td>
                            <div class="detail-item-title">Saatlik Çalışma</div>
                            <div class="detail-item-sub">
                                <?= number_format($saatlik_hours_sum, 1, ',', '.') ?> Saat
                            </div>
                        </td>
                        <td class="detail-item-amount"><?= Helper::formattedMoneyWithoutCurrency($saatlik_tutar_sum) ?></td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach($incomes as $inc): ?>
                        <?php if (!in_array($inc->kategori ?? 0, [5, 14])): // Puantaj dışındaki ek gelirler ?>
                        <tr>
                            <td>
                                <div class="detail-item-title"><?= htmlspecialchars($inc->turu) ?></div>
                            </td>
                            <td class="detail-item-amount"><?= Helper::formattedMoneyWithoutCurrency($inc->tutar) ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <?php if(empty($incomes) && $normal_tutar_sum <= 0 && $overtime_tutar_sum <= 0 && $saatlik_tutar_sum <= 0): ?>
                        <tr><td colspan="2" style="text-align: center; color: #94a3b8; padding: 16px;">Kayıt bulunamadı</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <table class="detail-table" style="flex-grow: 0;">
                <tr class="detail-total-row total-income-bg">
                    <td>TOPLAM KAZANÇ</td>
                    <td class="detail-item-amount" style="color: #0f172a; font-size: 12px;"><?= Helper::formattedMoneyWithoutCurrency($total_income) ?></td>
                </tr>
            </table>
        </div>

        <!-- Kesintiler Kolonu -->
        <div class="detail-box">
            <div class="detail-box-header header-expense">
                <span><i class="ti ti-trending-down me-1"></i> KESİNTİLER</span>
                <span>TUTAR (TL)</span>
            </div>
            <table class="detail-table">
                <tbody>
                    <?php foreach($expenses as $exp): ?>
                        <?php
                        $exp_name = (!empty($exp->turu) && strpos($exp->turu, 'İcra') !== false)
                            ? $exp->turu
                            : ($Defines->getTypeNameById($exp->kategori ?? 0) . ($exp->turu ? " - " . $exp->turu : ''));
                        ?>
                    <tr>
                        <td>
                            <div class="detail-item-title"><?= htmlspecialchars($exp_name) ?></div>
                        </td>
                        <td class="detail-item-amount text-danger"><?= Helper::formattedMoneyWithoutCurrency($exp->tutar) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(empty($expenses)): ?>
                        <tr><td colspan="2" style="text-align: center; color: #94a3b8; padding: 16px;">Kesinti bulunmuyor</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <table class="detail-table" style="flex-grow: 0;">
                <tr class="detail-total-row total-expense-bg">
                    <td>TOPLAM KESİNTİ</td>
                    <td class="detail-item-amount" style="color: var(--danger); font-size: 12px;"><?= Helper::formattedMoneyWithoutCurrency($total_expense) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Net Ödenecek Tutar Kutusu -->
    <div class="summary-card">
        <div class="summary-label-group">
            <div class="summary-icon">
                <i class="ti ti-cash"></i>
            </div>
            <div>
                <div class="summary-title">NET ÖDENECEK TUTAR</div>
                <div style="font-size: 11px; color: #15803d;">Tüm hakediş ve kesintiler hesaplanmıştır.</div>
            </div>
        </div>
        <div class="summary-amount"><?= Helper::formattedMoney($net_pay) ?></div>
    </div>

    <!-- İmza Alanı -->
    <div class="signature-area">
        <div class="signature-box">
            <div class="signature-title">İşveren / Yetkili İmza & Kaşe</div>
            <div class="signature-disclaimer">Yukarıdaki tahakkuk bilgileri onaylanmıştır.</div>
        </div>
        <div class="signature-box">
            <div class="signature-title">Personel İmza</div>
            <div class="signature-disclaimer">
                Yukarıdaki tahakkuk ve kesinti bilgilerini okudum, net ücretimi tam ve eksiksiz teslim aldım.
            </div>
        </div>
    </div>
</div>

</body>
</html>
<?php
exit();