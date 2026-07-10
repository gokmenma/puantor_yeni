<?php
require_once __DIR__ . "/../../../Model/PersonIcra.php";
require_once __DIR__ . "/../../../App/Helper/helper.php";

$personIcraObj = new PersonIcra();
$icraStats = $personIcraObj->getStats($_SESSION['personel_id']);
$icraFiles = $personIcraObj->getByPersonId($_SESSION['personel_id']);

// Dağıtım mantığı
$remaining_deductions = $icraStats['total_deductions'];
$processed_files = [];
foreach ($icraFiles as $f) {
    $toplam_borc = (float)$f->toplam_borc;
    $yapilan_kesinti = 0.0;
    
    if ($remaining_deductions > 0) {
        if ($remaining_deductions >= $toplam_borc) {
            $yapilan_kesinti = $toplam_borc;
            $remaining_deductions -= $toplam_borc;
        } else {
            $yapilan_kesinti = $remaining_deductions;
            $remaining_deductions = 0.0;
        }
    }
    
    $kalan_borc = max(0.0, $toplam_borc - $yapilan_kesinti);
    
    $processed_files[] = [
        'id' => $f->id,
        'icra_sirasi' => (int)$f->icra_sirasi,
        'icra_dairesi' => htmlspecialchars($f->icra_dairesi),
        'dosya_no' => htmlspecialchars($f->dosya_no),
        'alacakli' => htmlspecialchars($f->alacakli),
        'toplam_borc' => $toplam_borc,
        'yapilan_kesinti' => $yapilan_kesinti,
        'kalan_borc' => $kalan_borc,
        'kesinti_yontemi' => $f->kesinti_yontemi,
        'kesinti_orani' => $f->kesinti_orani,
        'kesinti_tutari' => (float)$f->kesinti_tutari,
        'durum' => $f->durum,
        'aciklama' => htmlspecialchars($f->aciklama ?? '')
    ];
}
?>
<div id="icra-tab" class="tab-content active">
    <!-- Sayfa Başlığı ve Geri Dön Butonu -->
    <div class="d-flex align-items-start gap-3 mb-4">
        <a href="?route=more" class="btn btn-icon btn-outline-secondary border-0 p-0 mt-1" style="width: 36px; height: 36px; border-radius: 50% !important; background-color: rgba(0,0,0,0.03);">
            <i class="ti ti-arrow-left fs-2"></i>
        </a>
        <div class="page-header">
            <h2 class="h1 mb-0 fw-bold text-dark" style="letter-spacing: -1px; line-height: 1.1;">İcra Kesintilerim</h2>
            <p class="text-muted small mb-0">İcra dosyalarınız ve kesinti detaylarınız.</p>
        </div>
    </div>

    <!-- Özet kart (İzin Sayfası Tasarımında - Mor/İndigo Gradyan) -->
    <div class="mobile-card text-white p-4 mb-4 position-relative overflow-hidden" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important; cursor: pointer; transition: transform 0.15s ease;" onclick="this.style.transform='scale(0.97)';" ontouchend="this.style.transform='none';">
        <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
            <i class="ti ti-scale"></i>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.5px;">KALAN TOPLAM BORÇ</span>
            <i class="ti ti-scale" style="font-size: 1.5rem; opacity: 0.8;"></i>
        </div>
        <div class="d-flex align-items-baseline gap-1 text-white mb-2">
            <h2 class="mb-0 text-bold" style="font-size: 2.2rem; letter-spacing: -1px; font-weight: 800;">
                <?= App\Helper\Helper::formattedMoney($icraStats['remaining_debt']); ?>
            </h2>
        </div>
        <div class="d-flex justify-content-between text-white-50 border-top border-white-10 pt-2" style="font-size: 0.75rem;">
            <span>Toplam Borç: <?= App\Helper\Helper::formattedMoney($icraStats['total_debt']); ?></span>
            <span>Kesinti: <?= App\Helper\Helper::formattedMoney($icraStats['total_deductions']); ?></span>
        </div>
    </div>

    <!-- Liste Başlığı -->
    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h3 class="h3 mb-0 fw-bold text-dark">Kayıtlı Dosyalarım</h3>
        <span class="text-muted small fw-semibold bg-light px-2 py-1 rounded-pill"><?= count($processed_files); ?> Dosya</span>
    </div>

    <!-- Dosya Listesi (Leave Listesi Tarzında) -->
    <div class="list-group shadow-sm border-0 mb-5" style="border-radius: 16px; overflow: hidden;">
        <?php foreach ($processed_files as $pf): 
            $statusClass = 'bg-secondary-lt text-secondary';
            if ($pf['durum'] === 'Bekliyor') $statusClass = 'bg-warning-lt text-warning';
            else if ($pf['durum'] === 'Kesilen') $statusClass = 'bg-blue-lt text-blue';
            else if ($pf['durum'] === 'Güncellendi') $statusClass = 'bg-info-lt text-info';
            else if ($pf['durum'] === 'Durduruldu') $statusClass = 'bg-danger-lt text-danger';
            else if ($pf['durum'] === 'Durduruldu(Bekleyen)') $statusClass = 'bg-purple-lt text-purple';
            else if ($pf['durum'] === 'Fekki Geldi') $statusClass = 'bg-teal-lt text-teal';
            else if ($pf['durum'] === 'Kesinti Bitti') $statusClass = 'bg-success-lt text-success';

            // JS tarafında kullanmak için formatlanmış para değerlerini ekliyoruz
            $pf['toplam_borc_formatted'] = App\Helper\Helper::formattedMoney($pf['toplam_borc']);
            $pf['yapilan_kesinti_formatted'] = App\Helper\Helper::formattedMoney($pf['yapilan_kesinti']);
            $pf['kalan_borc_formatted'] = App\Helper\Helper::formattedMoney($pf['kalan_borc']);
            $pfJson = rawurlencode(json_encode($pf));
        ?>
        <a href="javascript:void(0)" onclick="showIcraDetail('<?= $pfJson; ?>')" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between p-3 border-0 border-bottom border-light" style="transition: background-color 0.15s ease;">
            <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-sm <?= $statusClass; ?> rounded-circle fw-bold shadow-none" style="width: 36px; height: 36px; font-size: 0.9rem;">
                    #<?= $pf['icra_sirasi']; ?>
                </div>
                <div class="flex-fill">
                    <strong class="text-dark d-block fs-4" style="max-width: 170px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"><?= $pf['icra_dairesi']; ?></strong>
                    <span class="text-muted small">Dosya No: <?= $pf['dosya_no']; ?></span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge <?= $statusClass; ?> px-2 py-1 fw-bold" style="font-size: 0.7rem; border-radius: 6px;"><?= $pf['durum']; ?></span>
                <i class="ti ti-chevron-right text-muted fs-2"></i>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
function showIcraDetail(pfJson) {
    const pf = JSON.parse(decodeURIComponent(pfJson));
    const statusClass = getStatusClass(pf.durum);
    const kesintiTipi = pf.kesinti_yontemi === 'oran' ? `% ${pf.kesinti_orani} Oranında` : pf.kesinti_tutari_formatted + ' Sabit Tutar';
    
    const bodyHtml = `
        <div class="space-y-3 p-1">
            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-light">
                <span class="fw-bold text-dark fs-3">Sıra #${pf.icra_sirasi} - ${pf.dosya_no}</span>
                <span class="badge ${statusClass} px-2 py-1 fw-bold">${pf.durum}</span>
            </div>
            
            <div class="small text-secondary space-y-2" style="font-size: 0.95rem;">
                <div class="d-flex justify-content-between">
                    <span>İcra Dairesi:</span>
                    <strong class="text-dark">${pf.icra_dairesi}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Alacaklı:</span>
                    <strong class="text-dark">${pf.alacakli}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Kesinti Yöntemi:</span>
                    <strong class="text-dark">${kesintiTipi}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Toplam Borç:</span>
                    <strong class="text-dark">${pf.toplam_borc_formatted}</strong>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Ödenen Tutar:</span>
                    <strong class="text-success">${pf.yapilan_kesinti_formatted}</strong>
                </div>
                <div class="d-flex justify-content-between border-top border-light pt-2 mt-2">
                    <span class="fw-bold text-dark">Kalan Borç:</span>
                    <strong class="text-danger fw-bold fs-3">${pf.kalan_borc_formatted}</strong>
                </div>
            </div>
            
            ${pf.aciklama ? `
            <div class="mt-3 p-3 bg-light rounded-3 text-secondary" style="font-size: 0.85rem; border: 1px solid rgba(0,0,0,0.03);">
                <strong class="text-dark d-block mb-1">Açıklama:</strong>
                <p class="mb-0 text-muted" style="line-height: 1.4;">${pf.aciklama}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    window.app.showModal('İcra Dosyası Detayı', bodyHtml);
}

function getStatusClass(durum) {
    if (durum === 'Bekliyor') return 'bg-warning-lt text-warning';
    if (durum === 'Kesilen') return 'bg-blue-lt text-blue';
    if (durum === 'Güncellendi') return 'bg-info-lt text-info';
    if (durum === 'Durduruldu') return 'bg-danger-lt text-danger';
    if (durum === 'Durduruldu(Bekleyen)') return 'bg-purple-lt text-purple';
    if (durum === 'Fekki Geldi') return 'bg-teal-lt text-teal';
    if (durum === 'Kesinti Bitti') return 'bg-success-lt text-success';
    return 'bg-secondary-lt text-secondary';
}
</script>
