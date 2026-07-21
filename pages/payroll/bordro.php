<?php
require_once ROOT . "/Model/DefinesModel.php";
require_once ROOT . "/Model/SettingsModel.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$Defines = new DefinesModel();
$SettingsModel = new SettingsModel();

$overtime_rate = floatval($SettingsModel->getSettings("overtime_rate")->set_value ?? 50);
if ($overtime_rate < 50) { $overtime_rate = 50; }
$overtime_multiplier = 1 + ($overtime_rate / 100);
$work_hour = floatval($SettingsModel->getSettings("work_hour")->set_value ?? 8);

// Detailed daily puantaj query for pay-slip breakdown
$first_day = Date::firstDay($ay, $yil);
$last_day = Date::lastDay($ay, $yil);

$db = (new Bordro())->connect();
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

require_once ROOT . "/Model/Wages.php";

$WagesModel = new Wages();
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
    <style>
        body {
            font-family: DejaVu Sans, sans-serif; /* mPDF works best with DejaVu for TR characters */
            margin: 0;
            padding: 0;
            color: #1e293b;
            font-size: 10px;
        }

        .bordro-container {
            width: 100%;
            padding: 20px;
            background: #fff;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #1e293b;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
        }

        .company-info {
            color: #64748b;
            font-size: 9px;
        }

        .bordro-title {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #0284c7;
        }

        .period-badge {
            text-align: right;
            margin-top: 5px;
        }

        .period-text {
            background: #f8fafc;
            padding: 4px 10px;
            font-weight: bold;
            border-radius: 4px;
        }

        /* Info Grid Table */
        .info-grid-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f8fafc;
            padding: 12px;
            border-radius: 8px;
            width: 48%;
            vertical-align: top;
        }

        .info-box h3 {
            margin: 0 0 8px;
            font-size: 9px;
            text-transform: uppercase;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .info-row {
            margin-bottom: 3px;
        }

        .info-label {
            color: #64748b;
            font-weight: normal;
        }

        .info-value {
            font-weight: bold;
            text-align: right;
        }

        /* Details Table */
        .details-container {
            width: 100%;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .details-column {
            width: 50%;
            vertical-align: top;
        }

        .details-header {
            background: #1e293b;
            color: #fff;
            padding: 6px;
            text-align: center;
            font-weight: bold;
        }

        .details-header.expense {
            background: #ef4444;
        }

        .inner-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inner-table td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        .val-col {
            text-align: right;
            font-weight: bold;
        }

        .total-row {
            background: #f1f5f9;
            font-weight: bold;
        }

        .total-row.expense {
            background: #fef2f2;
        }

        /* Summary Box */
        .summary-box {
            border: 2px solid #0284c7;
            background: #f0f9ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .summary-label {
            font-size: 12px;
            font-weight: bold;
            color: #0284c7;
        }

        .summary-value {
            font-size: 20px;
            font-weight: 900;
            text-align: right;
        }

        /* Footer */
        .footer-table {
            width: 100%;
            margin-top: 40px;
        }

        .signature-box {
            width: 45%;
            border-top: 1px solid #1e293b;
            padding-top: 10px;
            text-align: center;
            vertical-align: top;
        }

        .signature-title {
            font-weight: bold;
            margin-bottom: 30px;
        }

        .signature-note {
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="bordro-container">
        <!-- Header -->
        <table class="header-table">
            <tr>
                <td width="60%">
                    <div class="company-name"><?= htmlspecialchars($firm->firm_name) ?></div>
                    <div class="company-info">
                        <?= htmlspecialchars($firm->address ?? '') ?><br>
                        <?= htmlspecialchars($firm->phone ?? '') ?> | <?= htmlspecialchars($firm->email ?? '') ?>
                    </div>
                </td>
                <td width="40%" align="right">
                    <div class="bordro-title">ÜCRET BORDROSU</div>
                    <div class="period-badge">
                        <span class="period-text"><?= Date::monthName($ay) ?> / <?= $yil ?></span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Info Grid -->
        <table class="info-grid-table">
            <tr>
                <td class="info-box">
                    <h3>Personel Bilgileri</h3>
                    <table width="100%">
                        <tr>
                            <td class="info-label">Adı Soyadı:</td>
                            <td class="info-value"><?= htmlspecialchars($person->full_name) ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">T.C. Kimlik No:</td>
                            <td class="info-value"><?= htmlspecialchars(Security::safeDecrypt($person->kimlik_no ?? '')) ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Görevi / Ünvan:</td>
                            <td class="info-value"><?= htmlspecialchars($person->job ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">İşe Giriş:</td>
                            <td class="info-value"><?= htmlspecialchars($person->job_start_date ?? '-') ?></td>
                        </tr>
                    </table>
                </td>
                <td width="4%">&nbsp;</td>
                <td class="info-box">
                    <h3>Ödeme Bilgileri</h3>
                    <table width="100%">
                        <tr>
                            <td class="info-label">IBAN:</td>
                            <td class="info-value"><?= htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '-')) ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Ücret Türü:</td>
                            <td class="info-value"><?= $person->wage_type == 1 ? 'Aylık (Beyaz Yaka)' : 'Günlük (Mavi Yaka)' ?></td>
                        </tr>
                        <tr>
                            <td class="info-label"><?= $person->wage_type == 1 ? 'Aylık Maaş:' : 'Günlük Ücret:' ?></td>
                            <td class="info-value">₺<?= Helper::formattedMoneyWithoutCurrency($effective_wage) ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Saatlik Ücret:</td>
                            <td class="info-value">₺<?= Helper::formattedMoneyWithoutCurrency($effective_hourly) ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Details Grid -->
        <table class="details-container" cellspacing="0" cellpadding="0">
            <tr>
                <td class="details-column" style="border-right: 1px solid #e2e8f0;">
                    <div class="details-header">KAZANÇLAR</div>
                    <table class="inner-table">
                        <?php if (($person->wage_type ?? 0) != 1 && $normal_tutar_sum > 0): ?>
                        <tr>
                            <td>
                                Normal Çalışma
                                <div style="font-size: 8.5px; color: #64748b; margin-top: 1px;">
                                    <?= $normal_days_count ?> Gün / <?= number_format($normal_hours_sum, 1, ',', '.') ?> Sa
                                </div>
                            </td>
                            <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($normal_tutar_sum) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($overtime_tutar_sum > 0): ?>
                        <tr>
                            <td>
                                Fazla Mesai (%<?= number_format($overtime_rate, 0) ?>)
                                <div style="font-size: 8.5px; color: #0284c7; margin-top: 1px; font-weight: bold;">
                                    <?= number_format($overtime_hours_sum, 1, ',', '.') ?> Sa Fazla Mesai
                                </div>
                            </td>
                            <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($overtime_tutar_sum) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php if ($saatlik_tutar_sum > 0): ?>
                        <tr>
                            <td>
                                Saatlik Çalışma
                                <div style="font-size: 8.5px; color: #64748b; margin-top: 1px;">
                                    <?= number_format($saatlik_hours_sum, 1, ',', '.') ?> Sa
                                </div>
                            </td>
                            <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($saatlik_tutar_sum) ?></td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach($incomes as $inc): ?>
                            <?php if (!in_array($inc->kategori ?? 0, [5, 14])): // Puantaj dışındaki ek gelirler (Aylık Maaş, Prim vb.) ?>
                            <tr>
                                <td><?= htmlspecialchars($inc->turu) ?></td>
                                <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($inc->tutar) ?></td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>

                        <?php if(empty($incomes) && $normal_tutar_sum <= 0 && $overtime_tutar_sum <= 0 && $saatlik_tutar_sum <= 0): ?>
                            <tr><td colspan="2" align="center" style="color: #94a3b8;">Kayıt yok</td></tr>
                        <?php endif; ?>

                        <tr class="total-row">
                            <td>TOPLAM KAZANÇ</td>
                            <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($total_income) ?></td>
                        </tr>
                    </table>
                </td>
                <td class="details-column">
                    <div class="details-header expense">KESİNTİLER</div>
                    <table class="inner-table">
                        <?php foreach($expenses as $exp): ?>
                            <?php
                            $exp_name = (!empty($exp->turu) && strpos($exp->turu, 'İcra') !== false)
                                ? $exp->turu
                                : ($Defines->getTypeNameById($exp->kategori ?? 0) . ($exp->turu ? " - " . $exp->turu : ''));
                            ?>
                        <tr>
                            <td><?= htmlspecialchars($exp_name) ?></td>
                            <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($exp->tutar) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($expenses)): ?>
                            <tr><td colspan="2" align="center" style="color: #94a3b8;">Kayıt yok</td></tr>
                        <?php endif; ?>
                        <tr class="total-row expense">
                            <td>TOPLAM KESİNTİ</td>
                            <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($total_expense) ?></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Summary -->
        <table class="summary-box" width="100%">
            <tr>
                <td class="summary-label">NET ÖDENECEK TUTAR</td>
                <td class="summary-value"><?= Helper::formattedMoney($net_pay) ?></td>
            </tr>
        </table>

        <!-- Signatures -->
        <table class="footer-table">
            <tr>
                <td class="signature-box">
                    <div class="signature-title">İşveren / Yetkili İmza</div>
                    <div class="signature-note">Kaşe / İmza</div>
                </td>
                <td width="10%">&nbsp;</td>
                <td class="signature-box">
                    <div class="signature-title">Personel İmza</div>
                    <div class="signature-note">Yukarıdaki bilgiler doğrultusunda ücretimi tam ve eksiksiz aldım.</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>