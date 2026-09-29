<?php
if (!$perm->hasPermission('personnel_page')) {
    return;
}

require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;
use App\Helper\Security;

$personnelModel = new Persons();
$personStats = $personnelModel->getPersonnelStats($firm_id);
$upcomingBirthdays = $personnelModel->getUpcomingBirthdays($firm_id, 30);
$recentHires = $personnelModel->getRecentHires($firm_id, 5);
?>

<div class="col-md-6" data-id="widget-personnel-summary">
    <div class="card resizable-card" style="max-height: 480px; display: flex; flex-direction: column;">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">İK & PERSONEL BAKIŞI</span>
            <div class="ms-auto d-flex align-items-center">
                <a href="index.php?p=persons/list" class="btn btn-sm btn-link me-2" style="font-size:10px; padding:0;">Personel Listesi</a>
                <i class="ti ti-grid-dots drag-handle text-muted"></i>
            </div>
        </div>
        <div class="card-body p-0" style="overflow-y: auto;">
            <!-- Üst İK Mini Rozetleri -->
            <div class="p-3 border-bottom bg-light-subtle">
                <div class="row g-2 text-center">
                    <div class="col-4">
                        <div class="p-2 border rounded bg-white">
                            <div class="text-secondary small">Aktif Çalışan</div>
                            <div class="fw-bold text-success" style="font-size: 15px;"><?php echo $personStats['active']; ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded bg-white">
                            <div class="text-secondary small">Aylık Ücretli</div>
                            <div class="fw-bold text-primary" style="font-size: 15px;"><?php echo $personStats['monthly_wage_count']; ?></div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-2 border rounded bg-white">
                            <div class="text-secondary small">Günlük / Yevmiye</div>
                            <div class="fw-bold text-warning" style="font-size: 15px;"><?php echo $personStats['daily_wage_count']; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Yaklaşan Doğum Günleri Bölümü -->
            <div class="p-3 border-bottom">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                        <i class="ti ti-cake text-danger me-1"></i> Yaklaşan Doğum Günleri (30 Gün)
                    </span>
                    <span class="badge bg-danger-lt" style="font-size: 10px;"><?php echo count($upcomingBirthdays); ?> Kişi</span>
                </div>
                <?php if (!empty($upcomingBirthdays)): ?>
                    <div class="list-group list-group-flush border rounded-2">
                        <?php foreach (array_slice($upcomingBirthdays, 0, 4) as $bday): ?>
                            <div class="list-group-item py-2 px-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center text-truncate">
                                    <span class="avatar avatar-xs bg-pink-lt rounded-circle me-2">
                                        <i class="ti ti-gift" style="font-size: 14px;"></i>
                                    </span>
                                    <div class="text-truncate">
                                        <div class="fw-medium small text-dark"><?php echo htmlspecialchars($bday->full_name); ?></div>
                                        <div class="text-secondary" style="font-size: 10px;">
                                            <?php echo htmlspecialchars($bday->job ?: 'Personel'); ?> &bull; <?php echo $bday->formatted_birth_date; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end ms-2">
                                    <?php if ($bday->days_left == 0): ?>
                                        <span class="badge bg-success text-white" style="font-size: 10px;">Bugün! 🎉</span>
                                    <?php else: ?>
                                        <span class="badge bg-pink-lt" style="font-size: 10px;"><?php echo $bday->days_left; ?> gün kaldı</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-2 text-muted small" style="font-size: 12px;">
                        Önümüzdeki 30 gün içinde doğum günü olan personel yok.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Son İşe Başlayanlar Bölümü -->
            <div class="p-3">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted fw-semibold text-uppercase" style="font-size: 10px; letter-spacing: 0.5px;">
                        <i class="ti ti-user-check text-success me-1"></i> Son Katılan Personeller
                    </span>
                </div>
                <?php if (!empty($recentHires)): ?>
                    <div class="list-group list-group-flush border rounded-2">
                        <?php foreach ($recentHires as $hire): ?>
                            <div class="list-group-item py-2 px-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center text-truncate">
                                    <span class="avatar avatar-xs bg-blue-lt rounded-circle me-2">
                                        <i class="ti ti-user" style="font-size: 14px;"></i>
                                    </span>
                                    <div class="text-truncate">
                                        <div class="fw-medium small text-dark"><?php echo htmlspecialchars($hire->full_name); ?></div>
                                        <div class="text-secondary" style="font-size: 10px;">
                                            <?php echo htmlspecialchars($hire->job_group_name ?: ($hire->job ?: 'Personel')); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end ms-2">
                                    <?php if (!empty($hire->job_start_date)): ?>
                                        <span class="badge bg-secondary-lt" style="font-size: 10px;">
                                            <i class="ti ti-calendar me-1"></i><?php echo htmlspecialchars($hire->job_start_date); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-2 text-muted small" style="font-size: 12px;">
                        Kayıt bulunamadı.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
