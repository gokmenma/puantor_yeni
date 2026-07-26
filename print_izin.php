<?php
require_once __DIR__ . '/App/bootstrap.php';

// Standalone Printable Leave Request Page
session_start();
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    die("Oturum kapalı. Lütfen giriş yapın.");
}

if (!defined('ROOT')) define('ROOT', __DIR__);

require_once ROOT . "/Database/db.php";
require_once ROOT . '/Model/IzinTalep.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/IzinTur.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

// Fetch parameters
$id = Security::safeDecrypt($_GET['id'] ?? '');
if (!$id) {
    die("Geçersiz talep ID.");
}

$firma_id = (int) ($_SESSION['firm_id'] ?? 0);

$talepModel = new IzinTalep();
$talep = $talepModel->find($id);
if (!$talep || (int)$talep->firma_id !== $firma_id) {
    die("Talep bulunamadı.");
}

$personModel = new Persons();
$person = $personModel->find((int)$talep->personel_id);
if (!$person) {
    die("Personel bulunamadı.");
}

$turModel = new IzinTur();
$tur = $turModel->find((int)$talep->tur_id);
$tur_adi = $tur ? $tur->ad : 'Yıllık İzin';

// Signature parameters
$u1 = htmlspecialchars($_GET['u1'] ?? '');
$i1 = htmlspecialchars($_GET['i1'] ?? '');
$u2 = htmlspecialchars($_GET['u2'] ?? '');
$i2 = htmlspecialchars($_GET['i2'] ?? '');
$u3 = htmlspecialchars($_GET['u3'] ?? '');
$i3 = htmlspecialchars($_GET['i3'] ?? '');

// Formatting dates helper
function formatDateTr($date) {
    if (!$date) return '—';
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $p = explode('-', $date);
        return "{$p[2]}.{$p[1]}.{$p[0]}";
    }
    if (preg_match('/^\d{2}\.\d{2}\.\d{4}$/', $date)) {
        return $date;
    }
    return date('d.m.Y', strtotime($date));
}

$baslangic_formatted = formatDateTr($talep->baslangic_tarihi);
$bitis_formatted = formatDateTr($talep->bitis_tarihi);
$talep_tarihi_formatted = formatDateTr($talep->olusturma_tarihi);
$giris_formatted = formatDateTr($person->job_start_date);

// Calculate entitlement date
$db = (new Database\Db())->connect();
$sqlHakedis = $db->prepare("SELECT hakedis_tarihi, yil FROM izin_hakedis WHERE personel_id = ? AND hakedis_tarihi <= ? ORDER BY hakedis_tarihi DESC LIMIT 1");
$sqlHakedis->execute([$person->id, $talep->baslangic_tarihi]);
$hakedisRow = $sqlHakedis->fetch(PDO::FETCH_OBJ);
$hakedis_tarihi = $hakedisRow ? formatDateTr($hakedisRow->hakedis_tarihi) : '—';
$hakedis_yil = $hakedisRow ? $hakedisRow->yil : '';

$is_ucretsiz = ($tur && $tur->kod === 'ucretsiz');
$calendar_days = (new DateTime($talep->baslangic_tarihi))->diff(new DateTime($talep->bitis_tarihi))->days + 1;

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_ucretsiz ? 'Ücretsiz İzin Dilekçesi' : 'Yıllık İzin Formu'; ?> - <?php echo htmlspecialchars($person->full_name); ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
            background-color: #fff;
            margin: 0;
            padding: 0;
        }
        .page {
            width: 18cm;
            margin: 0.5cm auto;
            padding: 0.5cm;
            box-sizing: border-box;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin: 0;
            text-transform: uppercase;
        }
        .section-boxes {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            gap: 15px;
        }
        .box {
            width: 50%;
            border: 1px solid #000;
            padding: 8px 10px;
            box-sizing: border-box;
        }
        .box-title {
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #000;
            padding-bottom: 3px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            font-size: 10pt;
            padding: 6px 4px;
            vertical-align: middle;
        }
        .info-table-bordered {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .info-table-bordered td {
            font-size: 10.5pt;
            padding: 8px 10px;
            border: 1px solid #000;
        }
        .info-table-bordered td.label {
            width: 25%;
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .info-table td.label {
            width: 50%;
            font-weight: bold;
        }
        .info-table td.val {
            width: 50%;
        }
        .info-table td.val::before {
            content: ": ";
        }
        .textarea-box {
            border: 1px solid #ccc;
            min-height: 60px;
            padding: 5px;
            font-size: 9pt;
            margin-top: 5px;
            white-space: pre-wrap;
        }
        .petition-text {
            font-size: 11pt;
            margin-bottom: 20px;
            text-align: justify;
            line-height: 1.6;
        }
        .petition-footer {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            font-size: 11pt;
        }
        .approval-text {
            font-size: 11pt;
            margin-bottom: 15px;
            font-style: italic;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        .approval-header {
            font-size: 10pt;
            font-weight: bold;
            text-align: center;
            margin-bottom: 8px;
            text-decoration: underline;
        }
        .signatures-container {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            gap: 10px;
        }
        .signature-block {
            width: 33%;
            border: 1px solid #000;
            padding: 6px;
            text-align: center;
            box-sizing: border-box;
            min-height: 120px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .sig-title {
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            padding-bottom: 4px;
            margin-bottom: 5px;
        }
        .sig-name {
            font-size: 9pt;
            margin-top: 5px;
            font-weight: normal;
        }
        .sig-date {
            font-size: 8.5pt;
            margin-top: 5px;
        }
        .sig-space {
            height: 45px;
        }
        .footer-receipt {
            border-top: 1px dashed #000;
            padding-top: 10px;
            margin-top: 15px;
            font-size: 10pt;
        }
        
        @media print {
            body {
                background: none;
                color: #000;
            }
            .page {
                width: 100%;
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>

<div class="page">
    <?php if ($is_ucretsiz): ?>
        <!-- ============================================================ -->
        <!-- ÜCRETSİZ İZİN DİLEKÇESİ ŞABLONU -->
        <!-- ============================================================ -->
        <div class="header" style="margin-top: 20px; margin-bottom: 30px;">
            <h1 style="font-size: 16pt; text-decoration: none; border-bottom: 2px solid #000; padding-bottom: 5px;">ÜCRETSİZ İZİN DİLEKÇESİ</h1>
        </div>

        <table class="info-table-bordered">
            <tr>
                <td class="label">TC KİMLİK NO</td>
                <td><?php echo htmlspecialchars($person->tc_no ?? '—'); ?></td>
            </tr>
            <tr>
                <td class="label">ADI SOYADI</td>
                <td style="font-weight: bold;"><?php echo htmlspecialchars($person->full_name); ?></td>
            </tr>
            <tr>
                <td class="label">GÖREVİ</td>
                <td><?php echo htmlspecialchars($person->job ?? '—'); ?></td>
            </tr>
        </table>

        <div class="petition-text" style="margin-top: 30px; margin-bottom: 40px; font-size: 11.5pt;">
            <?php 
                $mazeret = trim($talep->aciklama ?? '');
                if (empty($mazeret)) {
                    $mazeret = 'Özel sebeplerden dolayı';
                }
                echo htmlspecialchars($mazeret); 
            ?> <strong><?php echo $baslangic_formatted; ?></strong> - <strong><?php echo $bitis_formatted; ?></strong> tarihleri arası (<strong><?php echo (int) $calendar_days; ?></strong>) günlük ücretsiz izin kullanmak istiyorum. Gereğinin yapılmasını arz ederim.
        </div>

        <div class="petition-footer" style="margin-bottom: 60px;">
            <div></div>
            <div style="text-align: right; width: 250px; line-height: 1.8;">
                <strong>İMZA</strong>: __________________<br>
                <strong>TARİH</strong>: <?php echo $talep_tarihi_formatted; ?>
            </div>
        </div>

        <div style="border-top: 2px dashed #000; margin: 50px 0 30px 0;"></div>

        <div class="petition-text" style="font-weight: bold; text-align: center; margin-bottom: 40px; font-size: 11pt;">
            YUKARIDA BELİRTİLEN TARİHLERDE ÜCRETSİZ İZİN KULLANILMASI İŞYERİMİZCE UYGUN GÖRÜLMÜŞTÜR.
        </div>

        <div style="display: flex; justify-content: space-between; font-size: 11pt; line-height: 1.8;">
            <div style="width: 60%;">
                <strong>İŞVERENİN</strong><br>
                Adı Soyadı / Ünvanı : <strong><?php echo htmlspecialchars($_GET['ad_soyad'] ?? ''); ?> / <?php echo htmlspecialchars($_GET['unvan'] ?? ''); ?></strong><br>
                Tarih : <strong><?php echo date('d.m.Y'); ?></strong>
            </div>
            <div style="width: 35%; text-align: center; display: flex; flex-direction: column; justify-content: flex-end;">
                <div>Kaşe / İmza</div>
                <div style="height: 60px;"></div>
                <div>________________________</div>
            </div>
        </div>

    <?php else: ?>
        <!-- ============================================================ -->
        <!-- YILLIK İZİN FORM ŞABLONU -->
        <!-- ============================================================ -->
        <div class="header">
            <h1>YILLIK ÜCRETLİ İZİN TALEP VE ONAY FORMU</h1>
        </div>

        <div class="section-boxes">
            <!-- İK Bölümü -->
            <div class="box">
                <div class="box-title">** BU BÖLÜM İNSAN KAYNAKLARI TARAFINDAN DOLDURULACAKTIR.</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Personelin Adı</td>
                        <td class="val"><?php echo htmlspecialchars($person->full_name); ?></td>
                    </tr>
                    <tr>
                        <td class="label">Görevi</td>
                        <td class="val"><?php echo htmlspecialchars($person->job ?? '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="label">İşe Giriş Tarihi</td>
                        <td class="val"><?php echo $giris_formatted; ?></td>
                    </tr>
                    <tr>
                        <td class="label">Yıllık İzni Hak Ettiği Tarih</td>
                        <td class="val"><?php echo $hakedis_tarihi; ?></td>
                    </tr>
                    <tr>
                        <td class="label">İzin Başlangıç Tarihi</td>
                        <td class="val"><?php echo $baslangic_formatted; ?></td>
                    </tr>
                    <tr>
                        <td class="label">İzin Bitiş (İşe Başlama) Tarihi</td>
                        <td class="val"><?php echo $ise_baslama_tarihi; ?></td>
                    </tr>
                    <tr>
                        <td class="label">İzin Gün Sayısı</td>
                        <td class="val"><strong><?php echo (int) $talep->gun_sayisi; ?> İş Günü</strong></td>
                    </tr>
                </table>
            </div>

            <!-- Personel Bölümü -->
            <div class="box">
                <div class="box-title">** BU BÖLÜM PERSONEL TARAFINDAN DOLDURULACAKTIR.</div>
                <table class="info-table">
                    <tr>
                        <td class="label">Personelin Adı</td>
                        <td class="val"><?php echo htmlspecialchars($person->full_name); ?></td>
                    </tr>
                    <tr>
                        <td class="label">İzin Talep Tarihi</td>
                        <td class="val"><?php echo $talep_tarihi_formatted; ?></td>
                    </tr>
                    <tr>
                        <td class="label">İmza</td>
                        <td class="val">__________________</td>
                    </tr>
                    <tr>
                        <td class="label">İrtibat Telefon No</td>
                        <td class="val"><?php echo htmlspecialchars(Security::safeDecrypt($person->phone ?? '') ?: '—'); ?></td>
                    </tr>
                    <tr>
                        <td class="label" colspan="2" style="font-weight: bold; padding-top: 8px;">İzinde Bulunacağı Adres:</td>
                    </tr>
                </table>
                <div class="textarea-box"><?php echo htmlspecialchars($talep->adres ?? '—'); ?></div>
            </div>
        </div>

        <!-- Dilekçe Metni -->
        <div class="petition-text">
            <strong>İnsan Kaynakları Birimi'ne</strong>,<br>
            Yıllık Ücretli İzin kullanma dönemim olan <strong><?php echo $baslangic_formatted; ?></strong> ile <strong><?php echo $bitis_formatted; ?></strong> tarihleri arasında kullanmam gereken izin hakkımı "Yıllık Ücretli İzin Talep Formu" nda beyan ettiğim günlerde kullanmak istiyorum.<br>
            Gereğini arz ederim.
        </div>

        <div class="petition-footer">
            <div></div>
            <div style="text-align: right; width: 250px;">
                Saygılarımla,<br><br>
                Tarih: <?php echo $talep_tarihi_formatted; ?><br>
                İmza: __________________
            </div>
        </div>

        <!-- Onay Metni -->
        <div class="approval-text">
            Kimliği yukarıda yer alan personelimizin, <strong><?php echo !empty($hakedis_yil) ? $hakedis_yil : '..........'; ?></strong> yılına ait yıllık ücretli izin hakkını <strong><?php echo $baslangic_formatted; ?></strong> tarihinde ayrılmak ve <strong><?php echo $ise_baslama_tarihi; ?></strong> tarihinde göreve başlamak kaydıyla kullanması uygundur.
        </div>

        <div class="approval-header">DÜŞÜNCE VE ONAY</div>

        <!-- Onay İmzaları -->
        <div class="signatures-container">
            <!-- Onay 1 -->
            <div class="signature-block">
                <div class="sig-title"><?php echo !empty($u1) ? $u1 : 'İNSAN KAYNAKLARI'; ?></div>
                <div>ONAY</div>
                <div class="sig-space"></div>
                <div class="sig-name"><?php echo !empty($i1) ? $i1 : '__________________'; ?></div>
                <div class="sig-date">..... / .... / 20....</div>
            </div>

            <!-- Onay 2 -->
            <div class="signature-block">
                <div class="sig-title"><?php echo !empty($u2) ? $u2 : 'BÖLÜM YÖN. / GRUP YÖNET.'; ?></div>
                <div>ONAY</div>
                <div class="sig-space"></div>
                <div class="sig-name"><?php echo !empty($i2) ? $i2 : '__________________'; ?></div>
                <div class="sig-date">..... / .... / 20....</div>
            </div>

            <!-- Onay 3 -->
            <div class="signature-block">
                <div class="sig-title"><?php echo !empty($u3) ? $u3 : 'GENEL MÜD. / GENEL MÜD. YR.'; ?></div>
                <div>ONAY</div>
                <div class="sig-space"></div>
                <div class="sig-name"><?php echo !empty($i3) ? $i3 : '__________________'; ?></div>
                <div class="sig-date">..... / .... / 20....</div>
            </div>
        </div>

        <!-- İzin Sonrası Bildirimi / Makbuz -->
        <div class="footer-receipt">
            <strong><?php echo !empty($hakedis_yil) ? $hakedis_yil : '..........'; ?></strong> Yılına ait yıllık ücretli izin hakkımı <strong><?php echo $baslangic_formatted; ?></strong> tarihi ile <strong><?php echo $bitis_formatted; ?></strong> tarihleri arasında kullandım.
            <br><br>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%;">İmza: ________________________</td>
                    <td style="width: 50%; text-align: right;">İş Başı Tarihi: <strong><?php echo $ise_baslama_tarihi; ?></strong></td>
                </tr>
            </table>
        </div>
    <?php endif; ?>
</div>

<script>
    window.onload = function() {
        window.print();
    };
</script>
</body>
</html>
