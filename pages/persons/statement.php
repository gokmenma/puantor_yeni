<?php
session_start();
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    header("Location: ../../sign-in.php");
    exit();
}

require_once '../../Database/require.php';
require_once '../../Model/Persons.php';
require_once '../../Model/Bordro.php';
require_once '../../Model/MyFirmModel.php';
require_once '../../App/Helper/helper.php';
require_once '../../App/Helper/security.php';
require_once '../../App/Helper/date.php';
require_once '../../App/Helper/financial.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$personObj = new Persons();
$bordroObj = new Bordro();
$myFirmObj = new MyFirmModel();
$financialHelper = new Financial();

$firm_id = $_SESSION['firm_id'] ?? 0;
$firm = $myFirmObj->find($firm_id);

$enc_id = $_GET['id'] ?? '';
if (empty($enc_id)) {
    die("Personel bulunamadı.");
}

$p_id = Security::decrypt($enc_id);
$person = $personObj->find($p_id);
if (!$person) {
    die("Personel bulunamadı.");
}

// Hesap hareketlerini getir
$income_expenses_raw = $bordroObj->getPersonWorkTransactions($p_id);

// Kronolojik olarak sırala (Eskiden yeniye - bakiye hesabı için)
usort($income_expenses_raw, function($a, $b) {
    if ($a->gun == $b->gun) {
        return (int)$a->id - (int)$b->id;
    }
    return strcmp($a->gun, $b->gun);
});

$running_balance = 0;
foreach ($income_expenses_raw as &$item) {
    $type = $financialHelper->getTransactionTypeById($item->kategori);
    if ($type && $type->type_id == 1) {
        $running_balance += $item->tutar;
    } else {
        $running_balance -= $item->tutar;
    }
    $item->running_balance = $running_balance;
    $item->transaction_type = $type;
}
unset($item);

// Toplam gelir, gider ve bakiye bilgilerini al
$summary = $bordroObj->sumAllIncomeExpense($p_id);
$total_income = $summary->total_income ?? 0;
$total_expense = $summary->total_expense ?? 0;
$balance = $total_income - $total_expense;

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($person->full_name) ?> - Hesap Ekstresi</title>
    <!-- Google Fonts Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --secondary: #64748b;
            --accent: #0284c7;
            --accent-light: #e0f2fe;
            --success: #16a34a;
            --success-light: #dcfce7;
            --danger: #dc2626;
            --danger-light: #fee2e2;
            --border: #cbd5e1;
            --bg-light: #f8fafc;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
            color: var(--primary);
            font-size: 13px;
            line-height: 1.5;
            padding-bottom: 50px;
        }

        /* Üst Araç Çubuğu (Yazdır, Geri Dön vb.) */
        .no-print-zone {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1000px;
            margin: 0 auto 20px auto;
            border-radius: 0 0 16px 16px;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
        }

        .btn-primary {
            background-color: var(--accent);
            color: #fff;
        }

        .btn-primary:hover {
            background-color: #0369a1;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: #fff;
            color: var(--primary-light);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover {
            background-color: var(--bg-light);
            color: var(--primary);
        }

        /* Ekstre Kart Yapısı */
        .ekstre-container {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid rgba(226, 232, 240, 0.8);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 25px -5px rgba(15, 23, 42, 0.05), 0 10px 10px -5px rgba(15, 23, 42, 0.02);
        }

        /* Logo & Başlık */
        .ekstre-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 24px;
            margin-bottom: 30px;
        }

        .company-details h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.02em;
        }

        .company-details p {
            color: var(--secondary);
            font-size: 12px;
            margin-top: 4px;
        }

        .document-title {
            text-align: right;
        }

        .document-title h2 {
            font-size: 22px;
            font-weight: 800;
            color: var(--accent);
            letter-spacing: 0.05em;
        }

        .document-title p {
            font-size: 11px;
            font-weight: 600;
            color: var(--secondary);
            margin-top: 6px;
            text-transform: uppercase;
        }

        /* Personel Bilgi Kartı */
        .person-info-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 24px;
            margin-bottom: 30px;
        }

        .info-box {
            background-color: var(--bg-light);
            border: 1px solid rgba(226, 232, 240, 0.6);
            border-radius: 12px;
            padding: 20px;
        }

        .info-box h3 {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--secondary);
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
            margin-bottom: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed rgba(226, 232, 240, 0.4);
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: var(--secondary);
            font-weight: 500;
        }

        .info-val {
            color: var(--primary);
            font-weight: 600;
        }

        /* Özet Kutuları */
        .summary-widgets {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 35px;
        }

        .summary-card {
            border-radius: 12px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        .summary-card.income {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }

        .summary-card.expense {
            background-color: #fef2f2;
            border-color: #fecaca;
        }

        .summary-card.balance {
            background-color: var(--bg-light);
            border-color: var(--border);
        }

        .summary-card.balance.positive {
            background-color: #f0fdf4;
            border-color: #bbf7d0;
        }

        .summary-card.balance.negative {
            background-color: #fef2f2;
            border-color: #fecaca;
        }

        .summary-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--secondary);
            margin-bottom: 6px;
        }

        .summary-val {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
        }

        .summary-card.income .summary-val { color: var(--success); }
        .summary-card.expense .summary-val { color: var(--danger); }
        .summary-card.balance.positive .summary-val { color: var(--success); }
        .summary-card.balance.negative .summary-val { color: var(--danger); }

        /* Hareket Tablosu */
        .movements-title {
            font-size: 15px;
            font-weight: 700;
            color: var(--primary-light);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-responsive {
            border: 1px solid var(--border);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 40px;
        }

        .movement-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .movement-table th {
            background-color: var(--primary-light);
            color: #ffffff;
            font-weight: 600;
            padding: 12px 16px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .movement-table td {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            font-size: 12px;
        }

        .movement-table tr:last-child td {
            border-bottom: none;
        }

        .movement-table tr:nth-child(even) td {
            background-color: var(--bg-light);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
        }

        .badge-income {
            background-color: var(--success-light);
            color: var(--success);
        }

        .badge-expense {
            background-color: var(--danger-light);
            color: var(--danger);
        }

        .badge-neutral {
            background-color: #e2e8f0;
            color: #475569;
        }

        .text-right {
            text-align: right;
        }

        .text-success {
            color: var(--success) !important;
            font-weight: 700;
        }

        .text-danger {
            color: var(--danger) !important;
            font-weight: 700;
        }

        .font-bold {
            font-weight: 700;
        }

        /* İmza Alanı */
        .ekstre-footer {
            margin-top: 50px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            padding-top: 20px;
        }

        .signature-box {
            border-top: 1px solid var(--primary-light);
            padding-top: 12px;
            text-align: center;
        }

        .signature-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-light);
            margin-bottom: 50px;
        }

        .signature-desc {
            font-size: 10px;
            color: var(--secondary);
        }

        /* Yazdırma Modu Düzenlemeleri */
        @media print {
            body {
                background-color: #ffffff;
                padding: 0;
            }

            .no-print-zone {
                display: none !important;
            }

            .ekstre-container {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
            }

            .summary-card {
                border: 1px solid #cbd5e1 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge {
                border: 1px solid rgba(0,0,0,0.1) !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .movement-table th {
                background-color: #1e293b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Üst Kontrol Paneli (Yazdır & Geri Dön) -->
    <div class="no-print-zone">
        <a href="../../index.php?p=persons/manage&id=<?= $enc_id ?>" class="btn btn-secondary">
            <!-- Sol Ok SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Profile Geri Dön
        </a>
        <button onclick="window.print()" class="btn btn-primary">
            <!-- Yazıcı SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><path d="M6 9V3a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v6"/><rect x="6" y="14" width="12" height="8" rx="1"/></svg>
            Ekstreyi Yazdır
        </button>
    </div>

    <!-- Ana Ekstre Kartı -->
    <div class="ekstre-container">
        <!-- Logo & Başlık Bilgisi -->
        <div class="ekstre-header">
            <div class="company-details">
                <h1><?= htmlspecialchars($firm->firm_name ?? 'FİRMA AMBLEM / UNVANI') ?></h1>
                <p><?= htmlspecialchars($firm->address ?? '') ?></p>
                <p><?= htmlspecialchars($firm->phone ?? '') ?> | <?= htmlspecialchars($firm->email ?? '') ?></p>
            </div>
            <div class="document-title">
                <h2>CARI HESAP EKSTRESİ</h2>
                <p>Belge Tarihi: <?= date('d.m.Y H:i') ?></p>
            </div>
        </div>

        <!-- Personel Bilgisi -->
        <div class="person-info-grid">
            <div class="info-box">
                <h3>Personel Bilgileri</h3>
                <div class="info-row">
                    <span class="info-label">Adı Soyadı:</span>
                    <span class="info-val"><?= htmlspecialchars($person->full_name) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">T.C. Kimlik No:</span>
                    <span class="info-val"><?= htmlspecialchars(Security::safeDecrypt($person->kimlik_no ?? '')) ?: '-' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Görevi / Ünvanı:</span>
                    <span class="info-val"><?= htmlspecialchars($person->job ?? '-') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">İşe Başlama Tarihi:</span>
                    <span class="info-val"><?= $person->job_start_date ? date('d.m.Y', strtotime($person->job_start_date)) : '-' ?></span>
                </div>
            </div>
            <div class="info-box">
                <h3>Ücret ve Banka Bilgileri</h3>
                <div class="info-row">
                    <span class="info-label">Ücret Türü:</span>
                    <span class="info-val"><?= $person->wage_type == 1 ? 'Aylık (Beyaz Yaka)' : 'Günlük (Mavi Yaka)' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Banka Adı:</span>
                    <span class="info-val"><?= htmlspecialchars($person->bank_name ?? '-') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">IBAN Numarası:</span>
                    <span class="info-val"><?= htmlspecialchars(Security::safeDecrypt($person->iban_number ?? '')) ?: '-' ?></span>
                </div>
            </div>
        </div>

        <!-- Özet Rapor Kartları -->
        <div class="summary-widgets">
            <div class="summary-card income">
                <span class="summary-title">Toplam Hak Ediş (Alacak)</span>
                <span class="summary-val"><?= Helper::formattedMoney($total_income) ?></span>
            </div>
            <div class="summary-card expense">
                <span class="summary-title">Toplam Ödeme/Kesinti (Borç)</span>
                <span class="summary-val"><?= Helper::formattedMoney($total_expense) ?></span>
            </div>
            <div class="summary-card balance <?= $balance >= 0 ? 'positive' : 'negative' ?>">
                <span class="summary-title">Güncel Bakiye</span>
                <span class="summary-val"><?= Helper::formattedMoney($balance) ?></span>
            </div>
        </div>

        <!-- Hesap Hareketleri Listesi -->
        <div class="movements-title">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
            Hesap Hareket Detayları
        </div>

        <div class="table-responsive">
            <table class="movement-table">
                <thead>
                    <tr>
                        <th width="40">#</th>
                        <th width="110">İşlem Tarihi</th>
                        <th width="150">İşlem Kategorisi</th>
                        <th>Açıklama</th>
                        <th width="130" class="text-right">Tutar</th>
                        <th width="130" class="text-right">Bakiye</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach ($income_expenses_raw as $item): 
                        $is_income = ($item->transaction_type && $item->transaction_type->type_id == 1);
                        $badge_class = $is_income ? 'badge-income' : 'badge-expense';
                        $amount_prefix = $is_income ? '+' : '-';
                    ?>
                        <tr>
                            <td><?= $counter++ ?></td>
                            <td><?= Date::dmY($item->gun) ?></td>
                            <td>
                                <span class="badge <?= $badge_class ?>">
                                    <?= htmlspecialchars($item->transaction_type->adi ?? 'Bilinmeyen') ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                if ($item->kategori == 14) {
                                    echo '<strong>Puantaj Hak Edişi</strong> (' . sprintf('%02d', $item->ay) . '/' . $item->yil . ')';
                                } else {
                                    echo htmlspecialchars($item->turu);
                                    if (!empty($item->aciklama)) {
                                        echo ' - <span class="text-secondary">' . htmlspecialchars($item->aciklama) . '</span>';
                                    }
                                }
                                ?>
                            </td>
                            <td class="text-right <?= $is_income ? 'text-success' : 'text-danger' ?>">
                                <?= $amount_prefix . Helper::formattedMoney($item->tutar) ?>
                            </td>
                            <td class="text-right font-bold <?= $item->running_balance >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= Helper::formattedMoney($item->running_balance) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($income_expenses_raw)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: var(--secondary);">
                                Bu personele ait herhangi bir hesap hareketi bulunmamaktadır.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- İmza ve Tasdik Bölümü -->
        <div class="ekstre-footer">
            <div class="signature-box">
                <div class="signature-title">İşveren / Yetkili İmza</div>
                <div class="signature-desc">Kaşe / Islak İmza</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Personel İmza / Onay</div>
                <div class="signature-desc">Yukarıdaki cari hesap hareketlerini ve bakiyeyi inceleyerek mutabık olduğumu onaylarım.</div>
            </div>
        </div>
    </div>

</body>
</html>
