<?php
require_once ROOT . "/Model/GorevModel.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;
use App\Helper\Security;

// Namespace düzenlemesi: Eğer GorevModel globalde ise direkt çağırıyoruz.
$gorevObj = new GorevModel();
$yaklasanGorevler = $gorevObj->getYaklasanGorevler($firm_id, 10);

if (!$Auths->Authorize("home_page_mission_view")) {
    return;
}
?>

<div class="col-md-6" data-id="widget-gorevler">
    <div class="card" style="max-height: 450px; display: flex; flex-direction: column;">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">YAKLAŞAN GÖREVLER</span>
            <div class="ms-auto d-flex align-items-center">
                <a href="index.php?p=gorevler/list" class="btn btn-sm btn-link me-2" style="font-size:10px; padding:0;">Tümünü Gör</a>
                <i class="ti ti-grid-dots drag-handle text-muted"></i>
            </div>
        </div>
        <div class="card-body p-0" style="overflow-y: auto;">
            <div class="list-group list-group-flush">
                <?php if (count($yaklasanGorevler) > 0): ?>
                    <?php foreach ($yaklasanGorevler as $gorev): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <?php if ($gorev->yildizli): ?>
                                        <i class="ti ti-star-filled text-warning" title="Yıldızlı"></i>
                                    <?php else: ?>
                                        <i class="ti ti-circle text-muted" style="font-size: 8px;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="col text-truncate">
                                    <a href="index.php?p=gorevler/list" class="text-reset d-block" title="<?php echo htmlspecialchars($gorev->baslik); ?>">
                                        <?php echo htmlspecialchars($gorev->baslik); ?>
                                    </a>
                                    <div class="d-flex align-items-center mt-1" style="gap: 8px;">
                                        <span class="badge" style="background-color: <?php echo $gorev->liste_renk; ?>; font-size: 10px; padding: 2px 6px;">
                                            <?php echo htmlspecialchars($gorev->liste_adi); ?>
                                        </span>
                                        <small class="text-secondary" style="font-size: 11px;">
                                            <i class="ti ti-calendar-event me-1"></i>
                                            <?php echo $gorev->tarih ? date('d.m', strtotime($gorev->tarih)) : '-'; ?>
                                            <?php if ($gorev->saat): ?>
                                                <i class="ti ti-clock ms-1 me-1"></i>
                                                <?php echo substr($gorev->saat, 0, 5); ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <a href="index.php?p=gorevler/list" class="list-group-item-actions">
                                        <i class="ti ti-chevron-right text-muted"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-task mb-2" style="font-size: 32px; opacity: 0.5;"></i>
                        <div style="font-size: 13px;">Henüz bekleyen görev yok.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
