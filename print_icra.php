<?php
// Standalone Printable Execution (İcra) File Report
session_start();
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    die("Oturum kapalı. Lütfen giriş yapın.");
}

if (!defined('ROOT')) define('ROOT', __DIR__);

require_once ROOT . "/Database/db.php";
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/PersonIcra.php';
require_once ROOT . '/Model/MyFirmModel.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/App/Helper/security.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';

use App\Helper\Security;
use App\Helper\Date;
use App\Helper\Helper;

$Auths = new Auths();
if (!$Auths->Authorize('icra_files_list') && !$Auths->Authorize('person_page_icra_info')) {
    http_response_code(403);
    die("Bu işlem için yetkiniz yok.");
}

$firm_id = (int)($_SESSION['firm_id'] ?? 0);
$PersonIcra = new PersonIcra();
$Persons = new Persons();
$MyFirm = new MyFirmModel();

$firm = $MyFirm->find($firm_id);
$firm_name = htmlspecialchars($firm->name ?? $_SESSION['full_name'] ?? 'Firma', ENT_QUOTES, 'UTF-8');

$id_param = $_GET['id'] ?? $_GET['file_id'] ?? '';
$file_id = is_numeric($id_param) ? (int)$id_param : (!empty($id_param) ? (int)Security::decrypt($id_param) : 0);

$is_single = ($file_id > 0);
$icra_file = null;
$person = null;
$history = [];
$files_list = [];

if ($is_single) {
    $icra_file = $PersonIcra->find($file_id);
    if (!$icra_file || $icra_file->deleted_at !== null) {
        die("İcra dosyası bulunamadı.");
    }
    $person = $Persons->find($icra_file->person_id);
    if (!$person || (int)$person->firm_id !== $firm_id) {
        die("Yetkisiz erişim.");
    }
    $history = $PersonIcra->getDeductionsHistory($person->id, $icra_file->dosya_no);
    ActivityLogModel::log('icra', 'print', "İcra dosyası yazdırıldı. Dosya No: {$icra_file->dosya_no}");
} else {
    $status_filter = $_GET['status_filter'] ?? ['Kesilen'];
    if (is_string($status_filter)) {
        $status_filter = explode(',', $status_filter);
    }
    $files_list = $PersonIcra->getFirmIcraFiles($firm_id, $status_filter);
    ActivityLogModel::log('icra', 'print', "Tüm icra dosyaları listesi yazdırıldı.");
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_single ? 'İcra Dosyası Raporu - ' . htmlspecialchars($icra_file->dosya_no, ENT_QUOTES, 'UTF-8') : 'İcra Dosyaları Listesi Raporu' ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <style>
        :root {
            --primary: #1e293b;
            --secondary: #64748b;
            --border: #cbd5e1;
            --bg-light: #f8fafc;
            --success: #16a34a;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f1f5f9;
            color: var(--primary);
            font-size: 12px;
            line-height: 1.4;
        }

        .no-print-bar {
            background: #1e293b;
            color: #fff;
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .no-print-bar .title {
            font-weight: 700;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-group {
            display: flex;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-primary { background: #206bc4; color: #fff; }
        .btn-primary:hover { background: #1a569d; }

        .btn-success { background: #2fb344; color: #fff; }
        .btn-success:hover { background: #248a35; }

        .btn-secondary { background: #64748b; color: #fff; }
        .btn-secondary:hover { background: #475569; }

        .print-container {
            max-width: 900px;
            margin: 24px auto;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 15px;
        }

        .report-title {
            font-size: 18px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f172a;
            margin-top: 5px;
        }

        .company-name {
            font-size: 14px;
            font-weight: 600;
            color: var(--secondary);
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            background: #f1f5f9;
            padding: 8px 12px;
            border-left: 4px solid #206bc4;
            margin: 20px 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-card {
            background: #fafafa;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px dashed #e2e8f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--secondary);
        }

        .info-value {
            font-weight: 700;
            color: var(--primary);
            text-align: right;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        table.data-table th {
            background: #f8fafc;
            color: #334155;
            font-weight: 700;
            text-align: left;
            padding: 8px 10px;
            border: 1px solid var(--border);
            font-size: 11px;
            text-transform: uppercase;
        }

        table.data-table td {
            padding: 8px 10px;
            border: 1px solid var(--border);
            font-size: 11.5px;
        }

        table.data-table tr:nth-child(even) {
            background: #fbfcfd;
        }

        .text-end { text-align: right; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: 700; }
        .text-success { color: #16a34a; }
        .text-danger { color: #dc2626; }

        .footer-signatures {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        .sig-box {
            width: 200px;
            text-align: center;
            border-top: 1px solid #94a3b8;
            padding-top: 8px;
            font-weight: 600;
            color: #475569;
        }

        @media print {
            .no-print-bar { display: none !important; }
            body { background: #fff; font-size: 10.5pt; }
            .print-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: none !important;
                box-shadow: none !important;
            }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body>

    <!-- Yazdırma Üst Barı (Ekranda görünür, yazdırırken gizlenir) -->
    <div class="no-print-bar">
        <div class="title">
            <i class="ti ti-file-text fs-2"></i>
            <span><?= $is_single ? 'İcra Dosyası Detayı & Kesintileri Raporu' : 'Tüm İcra Dosyaları Listesi' ?></span>
        </div>
        <div class="btn-group">
            <button class="btn btn-primary" onclick="window.print()">
                <i class="ti ti-printer"></i> Yazdır
            </button>
            <a href="pages/persons/icra-export-xls.php?id=<?= Security::encrypt($file_id); ?>" class="btn btn-success">
                <i class="ti ti-file-spreadsheet"></i> Excel'e İndir
            </a>
            <button class="btn btn-secondary" onclick="window.close()">
                <i class="ti ti-x"></i> Kapat
            </button>
        </div>
    </div>

    <div class="print-container">
        <!-- Rapor Başlığı & Firma Bilgileri -->
        <table class="header-table">
            <tr>
                <td>
                    <div class="company-name"><?= $firm_name; ?></div>
                    <div class="report-title"><?= $is_single ? 'İCRA DOSYASI VE KESİNTİ GEÇMİŞİ RAPORU' : 'PERSONEL İCRA DOSYALARI RAPORU' ?></div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div style="font-size: 11px; color: #64748b;">Rapor Tarihi: <strong><?= date('d.m.Y H:i'); ?></strong></div>
                </td>
            </tr>
        </table>

        <?php if ($is_single): ?>
            <?php
            $decryptedKimlik = Security::safeDecrypt($person->kimlik_no ?? '');
            $toplam_borc = (float)$icra_file->toplam_borc;

            $total_deducted = 0.0;
            foreach ($history as $h) {
                $total_deducted += (float)$h->tutar;
            }
            $remaining_debt = max(0.0, $toplam_borc - $total_deducted);
            ?>

            <!-- Genel Bilgiler Grid -->
            <div class="section-title"><i class="ti ti-user me-1"></i> Personel ve İcra Dosya Bilgileri</div>
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">Personel Adı Soyadı:</span>
                        <span class="info-value"><?= htmlspecialchars($person->full_name, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">TC Kimlik No:</span>
                        <span class="info-value"><?= htmlspecialchars($decryptedKimlik ?: '-', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Sicil No:</span>
                        <span class="info-value"><?= htmlspecialchars($person->sigorta_no ?? '-', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">İcra Sırası:</span>
                        <span class="info-value"><?= (int)$icra_file->icra_sirasi; ?>. Sıra</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Durum:</span>
                        <span class="info-value"><?= htmlspecialchars($icra_file->durum, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-row">
                        <span class="info-label">İcra Dairesi:</span>
                        <span class="info-value"><?= htmlspecialchars($icra_file->icra_dairesi, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dosya No:</span>
                        <span class="info-value"><?= htmlspecialchars($icra_file->dosya_no, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Alacaklı / Avukat:</span>
                        <span class="info-value"><?= htmlspecialchars($icra_file->alacakli, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kesinti Şekli:</span>
                        <span class="info-value">
                            <?= $icra_file->kesinti_yontemi === 'sabit' 
                                ? (Helper::formattedMoney($icra_file->kesinti_tutari) . ' (Sabit)') 
                                : (($icra_file->kesinti_orani ?: '%25') . ' (Oran)'); ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Başlama / Bitiş Tarihi:</span>
                        <span class="info-value">
                            <?= !empty($icra_file->baslama_tarihi) ? Date::dmY($icra_file->baslama_tarihi) : '-'; ?> / 
                            <?= !empty($icra_file->bitis_tarihi) ? Date::dmY($icra_file->bitis_tarihi) : '-'; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Mali Özet Tablosu -->
            <div class="info-grid">
                <div class="info-card" style="grid-column: span 2; background: #f8fafc; border-color: #cbd5e1;">
                    <div style="display: flex; justify-content: space-around; text-align: center;">
                        <div>
                            <div class="info-label">TOPLAM BORÇ</div>
                            <div style="font-size: 16px; font-weight: 700; color: #1e293b;"><?= Helper::formattedMoney($toplam_borc); ?></div>
                        </div>
                        <div style="border-left: 1px solid #e2e8f0; padding-left: 20px;">
                            <div class="info-label">TOPLAM KESİLEN TUTAR</div>
                            <div style="font-size: 16px; font-weight: 700; color: #16a34a;"><?= Helper::formattedMoney($total_deducted); ?></div>
                        </div>
                        <div style="border-left: 1px solid #e2e8f0; padding-left: 20px;">
                            <div class="info-label">KALAN KALAN BORÇ</div>
                            <div style="font-size: 16px; font-weight: 700; color: #dc2626;"><?= Helper::formattedMoney($remaining_debt); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bordro Kesintileri Geçmişi -->
            <div class="section-title"><i class="ti ti-history me-1"></i> Yapılan Bordro Kesintileri Geçmişi</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 40px;">#</th>
                        <th class="text-center" style="width: 100px;">Dönem</th>
                        <th>Açıklama / Kesinti Türü</th>
                        <th class="text-center" style="width: 140px;">Kayıt Tarihi</th>
                        <th class="text-end" style="width: 130px;">Tutar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($history)): ?>
                        <tr>
                            <td colspan="5" class="text-center" style="padding: 20px; color: #64748b;">
                                Bu icra dosyasına ait bordro kesintisi bulunamamıştır.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $i = 1; foreach ($history as $h): ?>
                            <tr>
                                <td class="text-center"><?= $i++; ?></td>
                                <td class="text-center fw-bold"><?= sprintf('%02d/%04d', (int)$h->ay, (int)$h->yil); ?></td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($h->aciklama ?: ($h->turu ?? 'İcra Kesintisi'), ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td class="text-center"><?= !empty($h->created_at) ? Date::dmYHis($h->created_at) : '-'; ?></td>
                                <td class="text-end fw-bold text-success"><?= Helper::formattedMoney((float)$h->tutar); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: 700;">
                        <td colspan="4" class="text-end">GENEL TOPLAM KESİLEN:</td>
                        <td class="text-end text-success" style="font-size: 13px;"><?= Helper::formattedMoney($total_deducted); ?></td>
                    </tr>
                </tfoot>
            </table>

        <?php else: ?>
            <!-- Tüm İcra Dosyaları Listesi -->
            <div class="section-title"><i class="ti ti-list me-1"></i> İcra Dosyaları Listesi</div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 30px;">#</th>
                        <th>Personel</th>
                        <th class="text-center">Sıra</th>
                        <th>İcra Dairesi</th>
                        <th>Dosya No</th>
                        <th>Alacaklı</th>
                        <th class="text-end">Toplam Borç</th>
                        <th class="text-end">Kesilen</th>
                        <th class="text-end">Kalan Borç</th>
                        <th class="text-center">Durum</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $i = 1;
                    $sum_toplam = 0.0;
                    $sum_kesilen = 0.0;
                    $sum_kalan = 0.0;
                    foreach ($files_list as $f):
                        $toplam = (float)$f->toplam_borc;
                        $kesilen = (float)$f->yapilan_kesinti;
                        $kalan = (float)$f->kalan_borc;

                        $sum_toplam += $toplam;
                        $sum_kesilen += $kesilen;
                        $sum_kalan += $kalan;
                    ?>
                        <tr>
                            <td class="text-center"><?= $i++; ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($f->full_name, ENT_QUOTES, 'UTF-8'); ?></div>
                                <div style="font-size: 10px; color: #64748b;">TC: <?= htmlspecialchars(Security::safeDecrypt($f->kimlik_no ?? ''), ENT_QUOTES, 'UTF-8') ?: '-'; ?></div>
                            </td>
                            <td class="text-center"><?= (int)$f->icra_sirasi; ?>. Sıra</td>
                            <td><?= htmlspecialchars($f->icra_dairesi, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($f->dosya_no, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars($f->alacakli, ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="text-end fw-bold"><?= Helper::formattedMoney($toplam); ?></td>
                            <td class="text-end fw-bold text-success"><?= Helper::formattedMoney($kesilen); ?></td>
                            <td class="text-end fw-bold text-danger"><?= Helper::formattedMoney($kalan); ?></td>
                            <td class="text-center"><?= htmlspecialchars($f->durum, ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr style="background: #f1f5f9; font-weight: 700;">
                        <td colspan="6" class="text-end">TOPLAM:</td>
                        <td class="text-end"><?= Helper::formattedMoney($sum_toplam); ?></td>
                        <td class="text-end text-success"><?= Helper::formattedMoney($sum_kesilen); ?></td>
                        <td class="text-end text-danger"><?= Helper::formattedMoney($sum_kalan); ?></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>

        <!-- İmza Alanı -->
        <div class="footer-signatures">
            <div class="sig-box">
                Düzenleyen / İnsan Kaynakları<br><br><br>
                İmza / Tarih
            </div>
            <div class="sig-box">
                Onaylayan / Şirket Yetkilisi<br><br><br>
                İmza / Tarih
            </div>
        </div>
    </div>

</body>
</html>
