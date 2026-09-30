<?php
require_once ROOT . "/Model/DefinesModel.php";
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/Model/Wages.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$Defines = new DefinesModel();
$SettingsModel = new SettingsModel();
$WagesModel = new Wages();

$overtime_rate = floatval($SettingsModel->getSettings("overtime_rate")->set_value ?? 50);
if ($overtime_rate < 50) { $overtime_rate = 50; }
$overtime_multiplier = 1 + ($overtime_rate / 100);
$work_hour = floatval($SettingsModel->getSettings("work_hour")->set_value ?? 8);

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
<div class="bordro-container" style="font-family: DejaVu Sans, sans-serif; color: #0f172a; font-size: 10px; padding: 20px;">
    <!-- Header -->
    <table width="100%" style="border-bottom: 2px solid #0f172a; padding-bottom: 12px; margin-bottom: 16px;">
        <tr>
            <td width="60%" style="vertical-align: top;">
                <div style="font-size: 15px; font-weight: bold; color: #0f172a; margin-bottom: 2px;"><?= htmlspecialchars($firm->firm_name ?? '') ?></div>
                <div style="font-size: 9px; color: #64748b; line-height: 1.35;">
                    <?= htmlspecialchars($firm->address ?? '') ?><br>
                    <?= htmlspecialchars($firm->phone ?? '') ?> <?= !empty($firm->email) ? '| ' . htmlspecialchars($firm->email) : '' ?>
                </div>
            </td>
            <td width="40%" align="right" style="vertical-align: top;">
                <div style="font-size: 16px; font-weight: bold; color: #0054a6; margin-bottom: 4px;">ÜCRET BORDROSU</div>
                <div style="display: inline-block; background: #f1f5f9; border: 1px solid #dbe3ec; padding: 3px 8px; border-radius: 4px; font-weight: bold; font-size: 10px;">
                    <?= Date::monthName($ay) ?> / <?= $yil ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- Info Grid -->
    <table width="100%" style="margin-bottom: 16px;" cellspacing="0" cellpadding="0">
        <tr>
            <td width="48%" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; vertical-align: top;">
                <div style="font-size: 9px; font-weight: bold; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 6px;">
                    PERSONEL BİLGİLERİ
                </div>
                <table width="100%" style="font-size: 9.5px;">
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">Adı Soyadı:</td>
                        <td align="right" style="font-weight: bold; color: #0f172a;"><?= htmlspecialchars($person->full_name) ?></td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">T.C. Kimlik No:</td>
                        <td align="right" style="font-weight: bold;"><?= htmlspecialchars(Security::safeDecrypt($person->kimlik_no ?? '')) ?></td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">Görevi / Ünvan:</td>
                        <td align="right"><?= htmlspecialchars($person->job ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">İşe Giriş:</td>
                        <td align="right"><?= htmlspecialchars($person->job_start_date ?? '-') ?></td>
                    </tr>
                </table>
            </td>
            <td width="4%">&nbsp;</td>
            <td width="48%" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; vertical-align: top;">
                <div style="font-size: 9px; font-weight: bold; color: #475569; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px; margin-bottom: 6px;">
                    ÖDEME BİLGİLERİ
                </div>
                <table width="100%" style="font-size: 9.5px;">
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">IBAN:</td>
                        <td align="right" style="font-weight: bold;"><?= htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '-')) ?></td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">Ücret Türü:</td>
                        <td align="right"><?= $person->wage_type == 1 ? 'Aylık (Beyaz Yaka)' : 'Günlük (Mavi Yaka)' ?></td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;"><?= $person->wage_type == 1 ? 'Aylık Maaş:' : 'Günlük Ücret:' ?></td>
                        <td align="right" style="font-weight: bold; color: #0054a6;">₺<?= Helper::formattedMoneyWithoutCurrency($effective_wage) ?></td>
                    </tr>
                    <tr>
                        <td style="color: #64748b; padding: 2px 0;">Saatlik Ücret:</td>
                        <td align="right">₺<?= Helper::formattedMoneyWithoutCurrency($effective_hourly) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Details Grid -->
    <table width="100%" style="border: 1px solid #dbe3ec; border-radius: 6px; margin-bottom: 16px; border-collapse: collapse;" cellspacing="0" cellpadding="0">
        <tr>
            <!-- Kazançlar Kolonu -->
            <td width="50%" style="vertical-align: top; border-right: 1px solid #dbe3ec;">
                <div style="background: #0f172a; color: #ffffff; padding: 6px 10px; font-weight: bold; font-size: 10px;">
                    KAZANÇLAR / HAKEDİŞLER
                </div>
                <table width="100%" style="border-collapse: collapse; font-size: 9.5px;">
                    <?php if (($person->wage_type ?? 0) != 1 && $normal_tutar_sum > 0): ?>
                    <tr>
                        <td style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9;">
                            Normal Çalışma (<?= $normal_days_count ?> Gün / <?= number_format($normal_hours_sum, 1, ',', '.') ?> Sa)
                        </td>
                        <td align="right" style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold;">
                            <?= Helper::formattedMoneyWithoutCurrency($normal_tutar_sum) ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($overtime_tutar_sum > 0): ?>
                    <tr>
                        <td style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9; color: #0054a6;">
                            Fazla Mesai (%<?= number_format($overtime_rate, 0) ?>) - <?= number_format($overtime_hours_sum, 1, ',', '.') ?> Sa
                        </td>
                        <td align="right" style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold; color: #0054a6;">
                            <?= Helper::formattedMoneyWithoutCurrency($overtime_tutar_sum) ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if ($saatlik_tutar_sum > 0): ?>
                    <tr>
                        <td style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9;">
                            Saatlik Çalışma (<?= number_format($saatlik_hours_sum, 1, ',', '.') ?> Sa)
                        </td>
                        <td align="right" style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold;">
                            <?= Helper::formattedMoneyWithoutCurrency($saatlik_tutar_sum) ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php foreach($incomes as $inc): ?>
                        <?php if (!in_array($inc->kategori ?? 0, [5, 14])): ?>
                        <tr>
                            <td style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9;"><?= htmlspecialchars($inc->turu) ?></td>
                            <td align="right" style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold;"><?= Helper::formattedMoneyWithoutCurrency($inc->tutar) ?></td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <tr style="background: #f8fafc; font-weight: bold;">
                        <td style="padding: 6px 8px; border-top: 1px solid #dbe3ec;">TOPLAM KAZANÇ</td>
                        <td align="right" style="padding: 6px 8px; border-top: 1px solid #dbe3ec;"><?= Helper::formattedMoneyWithoutCurrency($total_income) ?></td>
                    </tr>
                </table>
            </td>

            <!-- Kesintiler Kolonu -->
            <td width="50%" style="vertical-align: top;">
                <div style="background: #dc2626; color: #ffffff; padding: 6px 10px; font-weight: bold; font-size: 10px;">
                    KESİNTİLER
                </div>
                <table width="100%" style="border-collapse: collapse; font-size: 9.5px;">
                    <?php foreach($expenses as $exp): ?>
                        <?php
                        $exp_name = (!empty($exp->turu) && strpos($exp->turu, 'İcra') !== false)
                            ? $exp->turu
                            : ($Defines->getTypeNameById($exp->kategori ?? 0) . ($exp->turu ? " - " . $exp->turu : ''));
                        ?>
                    <tr>
                        <td style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9;"><?= htmlspecialchars($exp_name) ?></td>
                        <td align="right" style="padding: 5px 8px; border-bottom: 1px solid #f1f5f9; font-weight: bold; color: #dc2626;"><?= Helper::formattedMoneyWithoutCurrency($exp->tutar) ?></td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(empty($expenses)): ?>
                        <tr><td colspan="2" align="center" style="padding: 10px; color: #94a3b8;">Kesinti bulunmuyor</td></tr>
                    <?php endif; ?>

                    <tr style="background: #fef2f2; font-weight: bold; color: #dc2626;">
                        <td style="padding: 6px 8px; border-top: 1px solid #dbe3ec;">TOPLAM KESİNTİ</td>
                        <td align="right" style="padding: 6px 8px; border-top: 1px solid #dbe3ec;"><?= Helper::formattedMoneyWithoutCurrency($total_expense) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Net Tutar Vurgusu -->
    <table width="100%" style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 6px; padding: 10px 14px; margin-bottom: 24px;">
        <tr>
            <td style="font-size: 11px; font-weight: bold; color: #166534;">NET ÖDENECEK TUTAR</td>
            <td align="right" style="font-size: 17px; font-weight: bold; color: #14532d;"><?= Helper::formattedMoney($net_pay) ?></td>
        </tr>
    </table>

    <!-- İmza Tablosu -->
    <table width="100%" style="margin-top: 24px;">
        <tr>
            <td width="45%" style="border-top: 1.5px solid #cbd5e1; padding-top: 8px; text-align: center;">
                <div style="font-weight: bold; font-size: 10px; color: #0f172a; margin-bottom: 26px;">İşveren / Yetkili İmza & Kaşe</div>
                <div style="font-size: 8px; color: #64748b;">Onaylanmıştır</div>
            </td>
            <td width="10%">&nbsp;</td>
            <td width="45%" style="border-top: 1.5px solid #cbd5e1; padding-top: 8px; text-align: center;">
                <div style="font-weight: bold; font-size: 10px; color: #0f172a; margin-bottom: 26px;">Personel İmza</div>
                <div style="font-size: 8px; color: #64748b;">Net ücretimi tam ve eksiksiz teslim aldım.</div>
            </td>
        </tr>
    </table>
</div>