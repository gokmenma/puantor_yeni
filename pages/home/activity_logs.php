<?php
require_once ROOT . "/Model/ActivityLogModel.php";

$activityLogObj = new ActivityLogModel();
$firm_id = $_SESSION['firm_id'];

// Firmaya ait son aktiviteleri getir
$activities = $activityLogObj->getRecentActivities(15);
?>

<div class="col-md-6" data-id="widget-activity-logs">
    <div class="card" style="max-height: 450px; display: flex; flex-direction: column;">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">SON AKTİVİTELER</span>
            <div class="ms-auto d-flex align-items-center">
                <span class="badge bg-green-lt me-2" style="font-size: 9px; padding: 3px 6px;">Senkronize</span>
                <i class="ti ti-grid-dots drag-handle text-muted"></i>
            </div>
        </div>
        <div class="card-body p-0" style="overflow-y: auto;">
            <div class="list-group list-group-flush">
                <?php if (count($activities) > 0): ?>
                    <?php foreach ($activities as $activity): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <?php 
                                        $icon = 'ti-activity';
                                        $color = 'bg-secondary-lt';
                                        switch($activity->activity_type) {
                                            case 'personnel': $icon = 'ti-users'; $color = 'bg-blue-lt'; break;
                                            case 'project': $icon = 'ti-buildings'; $color = 'bg-green-lt'; break;
                                            case 'puantaj': $icon = 'ti-calendar'; $color = 'bg-orange-lt'; break;
                                            case 'finance': $icon = 'ti-wallet'; $color = 'bg-red-lt'; break;
                                            case 'todo': $icon = 'ti-checklist'; $color = 'bg-purple-lt'; break;
                                        }
                                    ?>
                                    <span class="avatar avatar-sm <?php echo $color; ?> rounded-circle">
                                        <i class="ti <?php echo $icon; ?>"></i>
                                    </span>
                                </div>
                                <div class="col text-truncate">
                                    <div class="text-reset d-block" style="font-size: 13px; font-weight: 500;">
                                        <?php echo htmlspecialchars($activity->description); ?>
                                    </div>
                                    <div class="d-flex align-items-center mt-1" style="gap: 8px;">
                                        <small class="text-secondary" style="font-size: 11px;">
                                            <i class="ti ti-user me-1"></i>
                                            <?php echo htmlspecialchars($activity->user_name ?? 'Bilinmeyen'); ?>
                                        </small>
                                        <small class="text-secondary" style="font-size: 11px;">
                                            <i class="ti ti-clock me-1"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($activity->created_at)); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-activity mb-2" style="font-size: 32px; opacity: 0.5;"></i>
                        <div style="font-size: 13px;">Henüz bir aktivite kaydı yok.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
