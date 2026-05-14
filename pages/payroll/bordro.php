<?php
require_once ROOT . "/Model/DefinesModel.php";
use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$Defines = new DefinesModel();

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
                            <td class="info-value"><?= $person->wage_type == 1 ? 'Aylık' : 'Günlük' ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">Banka:</td>
                            <td class="info-value"><?= htmlspecialchars($person->bank_name ?? '-') ?></td>
                        </tr>
                        <tr>
                            <td class="info-label">&nbsp;</td>
                            <td class="info-value">&nbsp;</td>
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
                        <?php foreach($incomes as $inc): ?>
                        <tr>
                            <td><?= htmlspecialchars($inc->turu) ?></td>
                            <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($inc->tutar) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($incomes)): ?>
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
                        <tr>
                            <td><?= htmlspecialchars($Defines->getTypeNameById($exp->kategori ?? 0) . " - " . $exp->turu) ?></td>
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