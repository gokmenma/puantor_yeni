<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/security.php';
require_once 'Model/Persons.php';
require_once 'Model/Bordro.php';
require_once 'Model/MyFirmModel.php';
require_once 'Model/DefinesModel.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$personObj = new Persons();
$bordroObj = new Bordro();
$myFirmObj = new MyFirmModel();
$definesObj = new DefinesModel();

$firm_id = $_SESSION['firm_id'];
$firm = $myFirmObj->find($firm_id);

$ids_str = $_GET['ids'] ?? '';
$ay_enc = $_GET['month'] ?? '';
$yil_enc = $_GET['year'] ?? '';

if (empty($ids_str)) {
    die("Personel seçilmedi.");
}

$ay = Security::decrypt($ay_enc);
$yil = Security::decrypt($yil_enc);
$ids = explode(',', $ids_str);

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Toplu Bordro Yazdır - <?= Date::monthName($ay) ?> <?= $yil ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #1e293b;
            --secondary-color: #64748b;
            --accent-color: #0284c7;
            --border-color: #e2e8f0;
            --bg-light: #f8fafc;
        }

        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            background: #fff;
            color: var(--primary-color);
            font-size: 11px;
        }

        .no-print-zone {
            background: #f1f5f9;
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #e2e8f0;
        }

        @media print {
            .no-print-zone { display: none; }
            body { background: #fff; }
            .page-break { page-break-after: always; }
        }

        .bordro-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 40px;
            border: 1px solid var(--border-color);
            position: relative;
            background: #fff;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 15px;
        }

        .company-info h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .company-info p {
            margin: 2px 0;
            color: var(--secondary-color);
        }

        .bordro-title {
            text-align: right;
        }

        .bordro-title h2 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
            color: var(--accent-color);
        }

        .bordro-title p {
            margin: 5px 0 0;
            font-weight: 600;
            background: var(--bg-light);
            padding: 4px 10px;
            border-radius: 4px;
            display: inline-block;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            background: var(--bg-light);
            padding: 15px;
            border-radius: 8px;
        }

        .info-box h3 {
            margin: 0 0 10px;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--secondary-color);
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 5px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 4px;
        }

        .info-label { font-weight: 500; color: var(--secondary-color); }
        .info-value { font-weight: 600; color: var(--primary-color); }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 30px;
        }

        .details-column {
            padding: 0;
        }

        .details-column:first-child {
            border-right: 1px solid var(--border-color);
        }

        .details-header {
            background: var(--primary-color);
            color: #fff;
            padding: 8px 15px;
            font-weight: 600;
            text-align: center;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table td {
            padding: 8px 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .details-table tr:last-child td {
            border-bottom: none;
        }

        .val-col { text-align: right; font-weight: 600; }

        .summary-box {
            margin-top: 20px;
            border: 2px solid var(--accent-color);
            border-radius: 8px;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f0f9ff;
        }

        .summary-label {
            font-size: 14px;
            font-weight: 700;
            color: var(--accent-color);
        }

        .summary-value {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-color);
        }

        .footer {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .signature-box {
            border-top: 1px solid var(--primary-color);
            padding-top: 10px;
            text-align: center;
        }

        .signature-title {
            font-weight: 600;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>

<div class="no-print-zone">
    <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer; background: #0284c7; color: white; border: none; border-radius: 5px; font-weight: bold;">
        Yazdırmayı Başlat
    </button>
    <p style="margin-top: 10px; font-size: 12px; color: #64748b;">Her personel ayrı sayfaya basılacak şekilde ayarlanmıştır.</p>
</div>

<?php foreach ($ids as $enc_id): 
    $p_id = Security::decrypt($enc_id);
    $person = $personObj->find($p_id);
    if (!$person) continue;

    $incomes = $bordroObj->getPersonIncome($p_id, $ay, $yil);
    $expenses = $bordroObj->getPersonExpense($p_id, $ay, $yil);

    $total_income = 0;
    foreach($incomes as $inc) $total_income += $inc->tutar;

    $total_expense = 0;
    foreach($expenses as $exp) $total_expense += $exp->tutar;

    $net_pay = $total_income - $total_expense;
?>
    <div class="bordro-container page-break">
        <div class="header">
            <div class="company-info">
                <h1><?= htmlspecialchars($firm->firm_name) ?></h1>
                <p><?= htmlspecialchars($firm->address ?? '') ?></p>
                <p><?= htmlspecialchars($firm->phone ?? '') ?> | <?= htmlspecialchars($firm->email ?? '') ?></p>
            </div>
            <div class="bordro-title">
                <h2>ÜCRET BORDROSU</h2>
                <p><?= Date::monthName($ay) ?> / <?= $yil ?></p>
            </div>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <h3>Personel Bilgileri</h3>
                <div class="info-row">
                    <span class="info-label">Adı Soyadı:</span>
                    <span class="info-value"><?= htmlspecialchars($person->full_name) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">T.C. Kimlik No:</span>
                    <span class="info-value"><?= htmlspecialchars(Security::safeDecrypt($person->kimlik_no ?? '')) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Görevi / Ünvan:</span>
                    <span class="info-value"><?= htmlspecialchars($person->job ?? '-') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">İşe Giriş:</span>
                    <span class="info-value"><?= htmlspecialchars($person->job_start_date ?? '-') ?></span>
                </div>
            </div>
            <div class="info-box">
                <h3>Ödeme Bilgileri</h3>
                <div class="info-row">
                    <span class="info-label">IBAN:</span>
                    <span class="info-value"><?= htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '-')) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Ücret Türü:</span>
                    <span class="info-value"><?= $person->wage_type == 1 ? 'Aylık (Beyaz Yaka)' : 'Günlük (Mavi Yaka)' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Banka:</span>
                    <span class="info-value"><?= htmlspecialchars($person->bank_name ?? '-') ?></span>
                </div>
            </div>
        </div>

        <div class="details-grid">
            <div class="details-column">
                <div class="details-header">KAZANÇLAR</div>
                <table class="details-table">
                    <?php foreach($incomes as $inc): ?>
                    <tr>
                        <td><?= htmlspecialchars($inc->turu) ?></td>
                        <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($inc->tutar) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($incomes)): ?>
                        <tr><td colspan="2" style="text-align: center; color: #94a3b8;">Kayıt yok</td></tr>
                    <?php endif; ?>
                    <tr style="background: #f1f5f9; font-weight: bold;">
                        <td>TOPLAM KAZANÇ</td>
                        <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($total_income) ?></td>
                    </tr>
                </table>
            </div>
            <div class="details-column">
                <div class="details-header" style="background: #ef4444;">KESİNTİLER</div>
                <table class="details-table">
                    <?php foreach($expenses as $exp): ?>
                    <tr>
                        <td><?= htmlspecialchars($definesObj->getTypeNameById($exp->kategori ?? 0) . " - " . $exp->turu) ?></td>
                        <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($exp->tutar) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($expenses)): ?>
                        <tr><td colspan="2" style="text-align: center; color: #94a3b8;">Kayıt yok</td></tr>
                    <?php endif; ?>
                    <tr style="background: #fef2f2; font-weight: bold;">
                        <td>TOPLAM KESİNTİ</td>
                        <td class="val-col"><?= Helper::formattedMoneyWithoutCurrency($total_expense) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="summary-box">
            <div class="summary-label">NET ÖDENECEK TUTAR</div>
            <div class="summary-value"><?= Helper::formattedMoney($net_pay) ?></div>
        </div>

        <div class="footer">
            <div class="signature-box">
                <div class="signature-title">İşveren / Yetkili İmza</div>
                <div style="font-size: 9px; color: var(--secondary-color);">Kaşe / İmza</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Personel İmza</div>
                <div style="font-size: 9px; color: var(--secondary-color);">Yukarıdaki bilgiler doğrultusunda ücretimi tam ve eksiksiz aldım.</div>
            </div>
        </div>
    </div>
<?php endforeach; ?>

</body>
</html>
