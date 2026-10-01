<?php
if (!$perm->hasPermission('project_page') && !$perm->hasPermission('project_add_update')) {
    return;
}

require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;

$projectModel = new Projects();
$projSummary = $projectModel->getProjectStatusSummary($firm_id);
$projectsList = $projSummary['projects'] ?? [];
?>

<div class="col-md-6" data-id="widget-project-overview">
    <div class="card resizable-card" style="max-height: 480px; display: flex; flex-direction: column;">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">PROJE DURUM VE İLERLEME DAĞILIMI</span>
            <div class="ms-auto d-flex align-items-center">
                <a href="/projeler" class="btn btn-sm btn-link me-2" style="font-size:10px; padding:0;">Tüm Projeler</a>
                <i class="ti ti-grid-dots drag-handle text-muted"></i>
            </div>
        </div>
        <div class="card-body p-3" style="overflow-y: auto;">
            <!-- Üst KPI Rozetleri -->
            <div class="row g-2 mb-3">
                <div class="col-4">
                    <div class="p-2 border rounded text-center bg-light-subtle">
                        <div class="text-secondary" style="font-size: 11px;">Toplam</div>
                        <div class="fw-bold text-dark" style="font-size: 16px;"><?php echo $projSummary['total']; ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 border rounded text-center bg-light-subtle">
                        <div class="text-secondary" style="font-size: 11px;">Aktif</div>
                        <div class="fw-bold text-primary" style="font-size: 16px;"><?php echo $projSummary['active']; ?></div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-2 border rounded text-center bg-light-subtle">
                        <div class="text-secondary" style="font-size: 11px;">Bütçe</div>
                        <div class="fw-bold text-success text-truncate" style="font-size: 13px;" title="<?php echo Helper::formattedMoney($projSummary['total_budget']); ?> ₺">
                            <?php echo Helper::formattedMoney($projSummary['total_budget']); ?> ₺
                        </div>
                    </div>
                </div>
            </div>

            <!-- Durum Dağılım Çubukları / Donut -->
            <?php if (!empty($projSummary['status_distribution'])): ?>
                <div class="mb-3">
                    <div class="text-muted fw-semibold small mb-2 text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Durum Dağılımı</div>
                    <div class="progress progress-separated mb-2" style="height: 10px;">
                        <?php foreach ($projSummary['status_distribution'] as $item): ?>
                            <div class="progress-bar" 
                                 role="progressbar" 
                                 style="width: <?php echo $item['percentage']; ?>%; background-color: <?php echo $item['color']; ?>;" 
                                 title="<?php echo htmlspecialchars($item['name']) . ': ' . $item['count'] . ' (' . $item['percentage'] . '%)'; ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        <?php foreach ($projSummary['status_distribution'] as $item): ?>
                            <span class="d-flex align-items-center gap-1 small text-secondary" style="font-size: 11px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background-color: <?php echo $item['color']; ?>; display: inline-block;"></span>
                                <span><?php echo htmlspecialchars($item['name']); ?>: <strong><?php echo $item['count']; ?></strong></span>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Aktif / Son Projeler Listesi -->
            <div>
                <div class="text-muted fw-semibold small mb-2 text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">Son Projeler</div>
                <?php if (!empty($projectsList)): ?>
                    <div class="list-group list-group-flush border rounded-2">
                        <?php foreach (array_slice($projectsList, 0, 5) as $proj): ?>
                            <a href="/proje-duzenle?id=<?php echo \App\Helper\Security::encrypt($proj->id); ?>" class="list-group-item list-group-item-action py-2 px-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-truncate me-2">
                                        <div class="fw-medium text-dark text-truncate" style="font-size: 13px;">
                                            <i class="ti ti-building me-1 text-muted"></i>
                                            <?php echo htmlspecialchars($proj->project_name); ?>
                                        </div>
                                        <div class="text-secondary d-flex align-items-center gap-2 mt-1" style="font-size: 11px;">
                                            <?php if (!empty($proj->start_date)): ?>
                                                <span><i class="ti ti-calendar me-1"></i><?php echo htmlspecialchars($proj->start_date); ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($proj->budget) && $proj->budget > 0): ?>
                                                <span class="text-success"><i class="ti ti-coin me-1"></i><?php echo Helper::formattedMoney($proj->budget); ?> ₺</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted small">
                        <i class="ti ti-buildings mb-1 d-block" style="font-size: 24px; opacity: 0.5;"></i>
                        Henüz proje eklenmemiş.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
