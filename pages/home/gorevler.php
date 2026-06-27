<?php
require_once ROOT . "/Model/GorevModel.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;
use App\Helper\Security;

$gorevObj = new GorevModel();
$yaklasanGorevler = $gorevObj->getYaklasanGorevler($firm_id, 10);

if (!$Auths->Authorize("home_page_mission_view")) {
    return;
}

$bugun = date('Y-m-d');
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
                    <?php foreach ($yaklasanGorevler as $gorev):
                        $gecmis = $gorev->tarih && $gorev->tarih < $bugun;
                        $bugunMu = $gorev->tarih && $gorev->tarih === $bugun;
                        $avatarClass = $gecmis ? 'bg-red-lt' : ($bugunMu ? 'bg-orange-lt' : 'bg-blue-lt');
                        $listeRenk = $gorev->liste_renk ?? '#6c757d';
                    ?>
                        <a href="index.php?p=gorevler/list" class="list-group-item list-group-item-action text-decoration-none">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar avatar-sm <?php echo $avatarClass; ?> rounded-circle">
                                        <?php if ($gorev->yildizli): ?>
                                            <i class="ti ti-star-filled"></i>
                                        <?php else: ?>
                                            <i class="ti ti-checkbox"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="col text-truncate">
                                    <div class="text-reset d-block text-truncate" style="font-size: 13px; font-weight: 500;" title="<?php echo htmlspecialchars($gorev->baslik); ?>">
                                        <?php echo htmlspecialchars($gorev->baslik); ?>
                                    </div>
                                    <div class="d-flex align-items-center mt-1" style="gap: 8px;">
                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($listeRenk); ?>; color: #fff; font-size: 10px; padding: 2px 6px;">
                                            <?php echo htmlspecialchars($gorev->liste_adi); ?>
                                        </span>
                                        <?php if ($gorev->tarih): ?>
                                            <small class="<?php echo $gecmis ? 'text-danger' : 'text-secondary'; ?>" style="font-size: 11px;">
                                                <i class="ti ti-calendar-event me-1"></i>
                                                <?php echo date('d.m.Y', strtotime($gorev->tarih)); ?>
                                                <?php if ($gorev->saat): ?>
                                                    <i class="ti ti-clock ms-1 me-1"></i>
                                                    <?php echo substr($gorev->saat, 0, 5); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-checkbox mb-2" style="font-size: 32px; opacity: 0.5;"></i>
                        <div style="font-size: 13px;">Henüz bekleyen görev yok.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
