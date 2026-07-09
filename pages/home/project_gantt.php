<?php
$ganttProjects    = $projectObj->getProjectsByFirm($firm_id);
$defaultGanttProj = $projectObj->getHomeGanttProject($firm_id);
$defaultGanttId   = $defaultGanttProj ? (int)$defaultGanttProj->id : 0;

if (empty($ganttProjects)) return;
?>

<div class="col-12" data-id="widget-project-gantt">
    <div class="card">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">PROJE GANTT ŞEMASI</span>
            <i class="ti ti-grid-dots drag-handle ms-auto text-muted"></i>
        </div>
        <div class="card-body">
            <div class="d-flex align-items-center flex-wrap gap-3 mb-3">
                <div style="min-width:260px;max-width:340px;flex:1;">
                    <select id="home-gantt-project" class="form-select" data-default="<?php echo $defaultGanttId; ?>">
                        <option value="">Proje seçiniz...</option>
                        <?php foreach ($ganttProjects as $proj): ?>
                            <option value="<?php echo (int)$proj->id; ?>" <?php echo ($proj->id == $defaultGanttId) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($proj->project_name); ?>
                                <?php if ($proj->id == $defaultGanttId): ?> ★<?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="button" class="btn btn-primary btn-sm d-none" id="btn-home-add-task">
                    <i class="ti ti-plus icon me-1"></i> Yeni Görev
                </button>
                <div class="btn-group btn-group-sm" id="home-gantt-view-modes">
                    <button type="button" class="btn btn-outline-secondary" data-mode="Day">Gün</button>
                    <button type="button" class="btn btn-outline-secondary active" data-mode="Week">Hafta</button>
                    <button type="button" class="btn btn-outline-secondary" data-mode="Month">Ay</button>
                </div>
                <div class="d-flex align-items-center gap-3 ms-auto">
                    <span class="d-flex align-items-center gap-1">
                        <span style="width:13px;height:13px;border-radius:3px;background:#206bc4;display:inline-block;flex-shrink:0"></span>
                        <small class="text-muted">Bekliyor</small>
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span style="width:13px;height:13px;border-radius:3px;background:#f59f00;display:inline-block;flex-shrink:0"></span>
                        <small class="text-muted">Devam Ediyor</small>
                    </span>
                    <span class="d-flex align-items-center gap-1">
                        <span style="width:13px;height:13px;border-radius:3px;background:#2fb344;display:inline-block;flex-shrink:0"></span>
                        <small class="text-muted">Tamamlandı</small>
                    </span>
                </div>
            </div>

            <div id="home-gantt-placeholder" class="text-center py-5 text-muted" <?php echo $defaultGanttId ? 'style="display:none;"' : ''; ?>>
                <i class="ti ti-chart-bar" style="font-size:2.5rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
                Gantt şemasını görüntülemek için bir proje seçin.
            </div>
            <div id="home-gantt-empty" class="text-center py-5 text-muted" style="display:none;">
                <i class="ti ti-calendar-off" style="font-size:2.5rem;display:block;margin-bottom:.5rem;opacity:.4;"></i>
                Bu projeye ait tarih girilmiş iş kalemi bulunamadı.
            </div>
            <div id="home-gantt-loading" class="text-center py-4" <?php echo $defaultGanttId ? '' : 'style="display:none;"'; ?>>
                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                <span class="text-muted">Yükleniyor...</span>
            </div>
            <div id="home-gantt-container" style="overflow-x:auto;display:none;"></div>
        </div>
    </div>
</div>
