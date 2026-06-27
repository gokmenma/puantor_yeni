<?php
require_once ROOT . '/Model/IzinTalep.php';
require_once ROOT . '/Model/IzinHakedis.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

$izinTalepModel  = new IzinTalep();
$izinHakedisModel = new IzinHakedis();

$bugun_izinliler     = $izinTalepModel->getBugunIzinliler($firm_id);
$bekleyen_sayi       = $izinTalepModel->getBekleyenSayisi($firm_id);
$bu_ay_kullanilan    = $izinTalepModel->getBuAyKullanilanGun($firm_id);
$en_cok_kullananlar  = $izinTalepModel->getEnCokKullananlar($firm_id, 5);
$yaklasan_hakedisler = $izinHakedisModel->getYaklasanHakedisler($firm_id);
?>

<div class="col-md-6" data-id="widget-izin">
    <div class="card" style="max-height: 500px; display: flex; flex-direction: column;">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <div class="mac-title">
                <i class="ti ti-beach me-1 text-success"></i> Yıllık İzin Özeti
            </div>
            <i class="ti ti-grid-dots drag-handle ms-auto text-muted"></i>
        </div>
        <div class="card-body p-0" style="overflow-y: auto; flex: 1;">

            <!-- İstatistik rozetleri -->
            <div class="d-flex gap-2 flex-wrap p-3 border-bottom">
                <a href="?p=izin/list&durum=beklemede" class="badge bg-warning-lt text-warning border border-warning-subtle text-decoration-none px-3 py-2">
                    <i class="ti ti-clock me-1"></i> <?= $bekleyen_sayi ?> Bekleyen Talep
                </a>
                <span class="badge bg-blue-lt px-3 py-2">
                    <i class="ti ti-calendar-stats me-1"></i> Bu ay <?= $bu_ay_kullanilan ?> gün kullanıldı
                </span>
                <span class="badge bg-green-lt px-3 py-2">
                    <i class="ti ti-beach me-1"></i> Bugün <?= count($bugun_izinliler) ?> kişi izinli
                </span>
            </div>

            <!-- Bugün izinliler -->
            <?php if (!empty($bugun_izinliler)): ?>
            <div class="px-3 pt-3 pb-1">
                <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing: .5px;">Bugün İzinli</p>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <?php foreach ($bugun_izinliler as $k): ?>
                        <span class="badge bg-green-lt">
                            <i class="ti ti-user me-1"></i><?= htmlspecialchars($k->personel_adi) ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- En çok kullananlar -->
            <?php if (!empty($en_cok_kullananlar)): ?>
            <div class="px-3 py-2 border-top">
                <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing: .5px;">Bu Yıl En Çok Kullananlar</p>
                <?php foreach ($en_cok_kullananlar as $k): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small"><?= htmlspecialchars($k->full_name) ?></span>
                        <span class="badge bg-secondary-lt text-secondary"><?= $k->toplam_gun ?> gün</span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Hakedişi yaklaşanlar -->
            <?php if (!empty($yaklasan_hakedisler)): ?>
            <div class="px-3 py-2 border-top">
                <p class="text-muted small fw-semibold text-uppercase mb-2" style="letter-spacing: .5px;">Hakedişi Yaklaşanlar (30 gün)</p>
                <?php foreach ($yaklasan_hakedisler as $h): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="small"><?= htmlspecialchars($h->full_name) ?></span>
                        <span class="badge bg-azure-lt text-azure">
                            <?= date('d.m.Y', strtotime($h->hakedis_tarihi)) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

        </div>
        <div class="card-footer d-flex gap-2">
            <a href="?p=izin/list" class="btn btn-sm btn-outline-secondary flex-fill">
                <i class="ti ti-list me-1"></i> Talepler
            </a>
            <a href="?p=izin/hakedis" class="btn btn-sm btn-outline-success flex-fill">
                <i class="ti ti-calendar-check me-1"></i> Hakedişler
            </a>
        </div>
    </div>
</div>
