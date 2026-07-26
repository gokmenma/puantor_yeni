<?php

if ((int) ($_SESSION['user']->superadmin ?? 0) !== 1) {
    header('Location: index.php?p=authorize');
    exit();
}

require_once ROOT . '/Model/SystemDashboardModel.php';
require_once ROOT . '/Service/SystemLogService.php';

$dashboardModel = new SystemDashboardModel();
$systemLogService = new \Service\SystemLogService();
$summary = $dashboardModel->getSummary();
$monthlyTrend = $dashboardModel->getMonthlyTrend();
$subscriptionStatuses = $dashboardModel->getSubscriptionStatuses();
$recentSubscribers = $dashboardModel->getRecentSubscribers();
$recentActivities = $dashboardModel->getRecentActivities();
$recentLogins = $dashboardModel->getRecentLogins();
$securityEvents = $dashboardModel->getRecentSecurityEvents();
$systemErrors = $systemLogService->getDashboardData(20);

$statusLabels = [
    'aktif' => 'Aktif',
    'sona_erdi' => 'Sona erdi',
    'iptal' => 'İptal',
    'onay_bekliyor' => 'Onay bekliyor',
    'beklemede' => 'Beklemede',
];

$statusClasses = [
    'aktif' => 'bg-success-lt text-success',
    'sona_erdi' => 'bg-secondary-lt text-secondary',
    'iptal' => 'bg-danger-lt text-danger',
    'onay_bekliyor' => 'bg-warning-lt text-warning',
    'beklemede' => 'bg-azure-lt text-azure',
];

$activityIcons = [
    'personnel' => ['users', 'blue'],
    'project' => ['building', 'green'],
    'puantaj' => ['calendar-time', 'orange'],
    'finance' => ['wallet', 'red'],
    'todo' => ['checkbox', 'purple'],
    'auth' => ['shield-lock', 'yellow'],
    'kvkk' => ['lock', 'cyan'],
];

$errorLevelClasses = [
    'critical' => 'bg-red text-white',
    'error' => 'bg-danger-lt text-danger',
    'warning' => 'bg-warning-lt text-warning',
    'notice' => 'bg-azure-lt text-azure',
];

$errorLevelLabels = [
    'critical' => 'Kritik',
    'error' => 'Hata',
    'warning' => 'Uyarı',
    'notice' => 'Bilgi',
];

function systemDashboardInitials(?string $name): string
{
    $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = '';
    foreach (array_slice($words ?: [], 0, 2) as $word) {
        $initials .= mb_substr($word, 0, 1, 'UTF-8');
    }
    return $initials !== '' ? mb_strtoupper($initials, 'UTF-8') : '?';
}

function systemDashboardDevice(?string $userAgent): array
{
    $agent = (string) $userAgent;
    if (preg_match('/Mobile|Android|iPhone|iPad/i', $agent)) {
        return ['device-mobile', 'Mobil'];
    }
    if (stripos($agent, 'Windows') !== false) {
        return ['brand-windows', 'Windows'];
    }
    if (stripos($agent, 'Macintosh') !== false) {
        return ['brand-apple', 'Mac'];
    }
    if (stripos($agent, 'Linux') !== false) {
        return ['brand-ubuntu', 'Linux'];
    }
    return ['device-desktop', 'Masaüstü'];
}
?>

<style>
.system-dashboard .metric-card {
    border: 0;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06), 0 10px 30px rgba(15, 23, 42, .04);
}
.system-dashboard .metric-icon {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    font-size: 1.35rem;
}
.system-dashboard .metric-value {
    font-size: 1.65rem;
    line-height: 1.1;
    font-weight: 700;
    letter-spacing: -.03em;
}
.system-dashboard .dashboard-card {
    border: 0;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
}
.system-dashboard .activity-line {
    position: relative;
}
.system-dashboard .activity-line:not(:last-child)::after {
    content: "";
    position: absolute;
    left: 19px;
    top: 43px;
    bottom: -12px;
    width: 1px;
    background: var(--tblr-border-color);
}
.system-dashboard .table td {
    vertical-align: middle;
}
.system-dashboard .text-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

<div class="page-wrapper system-dashboard">
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Superadmin</div>
                    <h2 class="page-title">Sistem Yönetimi</h2>
                    <div class="text-secondary mt-1">Platformun abonelik, kullanım ve güvenlik görünümü</div>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <div class="btn-list">
                        <a href="index.php?p=activities/index" class="btn btn-outline-secondary">
                            <i class="ti ti-activity me-2"></i>Tüm aktiviteler
                        </a>
                        <a href="index.php?p=abonelik-islemleri/list" class="btn btn-primary">
                            <i class="ti ti-crown me-2"></i>Abonelikleri yönet
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="metric-icon bg-blue-lt text-blue"><i class="ti ti-users-group"></i></span>
                            <div>
                                <div class="metric-value"><?php echo number_format((int) $summary->total_subscribers, 0, ',', '.'); ?></div>
                                <div class="text-secondary">Toplam abone</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="metric-icon bg-success-lt text-success"><i class="ti ti-rosette-discount-check"></i></span>
                            <div>
                                <div class="metric-value"><?php echo number_format((int) $summary->active_subscriptions, 0, ',', '.'); ?></div>
                                <div class="text-secondary">Aktif abonelik</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="metric-icon bg-purple-lt text-purple"><i class="ti ti-building-community"></i></span>
                            <div>
                                <div class="metric-value"><?php echo number_format((int) $summary->total_firms, 0, ',', '.'); ?></div>
                                <div class="text-secondary">Kayıtlı firma</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="metric-icon bg-azure-lt text-azure"><i class="ti ti-user-check"></i></span>
                            <div>
                                <div class="metric-value"><?php echo number_format((int) $summary->active_users, 0, ',', '.'); ?></div>
                                <div class="text-secondary">Aktif kullanıcı</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="metric-icon bg-warning-lt text-warning"><i class="ti ti-clock-exclamation"></i></span>
                            <div>
                                <div class="metric-value"><?php echo number_format((int) $summary->expiring_subscriptions, 0, ',', '.'); ?></div>
                                <div class="text-secondary">7 günde bitecek</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="metric-icon bg-cyan-lt text-cyan"><i class="ti ti-hourglass"></i></span>
                            <div>
                                <div class="metric-value"><?php echo number_format((int) $summary->trial_subscribers, 0, ',', '.'); ?></div>
                                <div class="text-secondary">Deneme hesabı</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="metric-icon bg-orange-lt text-orange"><i class="ti ti-bolt"></i></span>
                            <div>
                                <div class="metric-value"><?php echo number_format((int) $summary->activities_today, 0, ',', '.'); ?></div>
                                <div class="text-secondary">Bugünkü işlem</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card metric-card">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="metric-icon bg-indigo-lt text-indigo"><i class="ti ti-login-2"></i></span>
                            <div>
                                <div class="metric-value"><?php echo number_format((int) $summary->users_logged_in_today, 0, ',', '.'); ?></div>
                                <div class="text-secondary">Bugün giriş yapan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-deck row-cards mt-1">
                <div class="col-lg-8">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Abone ve abonelik gelişimi</h3>
                                <div class="text-secondary small mt-1">Son 12 ay</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="system-subscription-trend" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Abonelik durumları</h3>
                                <div class="text-secondary small mt-1">Abonelerin son paket kaydı</div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="system-subscription-status" style="min-height: 300px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-deck row-cards mt-1">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Son abone olanlar</h3>
                                <div class="text-secondary small mt-1">En yeni ana hesaplar ve güncel paket bilgileri</div>
                            </div>
                            <div class="card-actions">
                                <a href="index.php?p=abonelik-islemleri/list" class="btn btn-sm btn-outline-primary">Tüm aboneler</a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Abone</th>
                                        <th>Paket</th>
                                        <th>Firma</th>
                                        <th>Kayıt tarihi</th>
                                        <th>Bitiş tarihi</th>
                                        <th>Durum</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($recentSubscribers): ?>
                                    <?php foreach ($recentSubscribers as $subscriber): ?>
                                        <?php
                                        $subscriptionStatus = $subscriber->subscription_status ?? '';
                                        $isTrial = !$subscriptionStatus
                                            && (int) $subscriber->status === 1
                                            && strtotime($subscriber->created_at) >= strtotime('-15 days');
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm bg-blue-lt text-blue me-2"><?php echo htmlspecialchars(systemDashboardInitials($subscriber->full_name)); ?></span>
                                                    <div>
                                                        <div class="fw-medium"><?php echo htmlspecialchars($subscriber->full_name); ?></div>
                                                        <div class="text-secondary small"><?php echo htmlspecialchars($subscriber->email); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($subscriber->package_name ?? ($isTrial ? '15 Günlük Deneme' : 'Paket yok')); ?></td>
                                            <td><span class="badge bg-secondary-lt"><?php echo (int) $subscriber->firm_count; ?></span></td>
                                            <td class="text-nowrap"><?php echo date('d.m.Y H:i', strtotime($subscriber->created_at)); ?></td>
                                            <td class="text-nowrap"><?php echo $subscriber->bitis_tarihi ? date('d.m.Y', strtotime($subscriber->bitis_tarihi)) : '—'; ?></td>
                                            <td>
                                                <?php if ($isTrial): ?>
                                                    <span class="badge bg-cyan-lt text-cyan">Deneme</span>
                                                <?php elseif ($subscriptionStatus): ?>
                                                    <span class="badge <?php echo $statusClasses[$subscriptionStatus] ?? 'bg-secondary-lt'; ?>">
                                                        <?php echo htmlspecialchars($statusLabels[$subscriptionStatus] ?? $subscriptionStatus); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-lt text-secondary">Abonelik yok</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" class="text-center text-secondary py-5">Henüz abone kaydı bulunmuyor.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-deck row-cards mt-1">
                <div class="col-12">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Sistem hataları</h3>
                                <div class="text-secondary small mt-1">PHP ve uygulama katmanından merkezi olarak yakalanan son kayıtlar</div>
                            </div>
                            <div class="card-actions d-flex align-items-center gap-2">
                                <span class="badge bg-red-lt text-red">Bugün <?php echo (int) $systemErrors['today']['critical']; ?> kritik</span>
                                <span class="badge bg-danger-lt text-danger"><?php echo (int) $systemErrors['today']['error']; ?> hata</span>
                                <span class="badge bg-warning-lt text-warning"><?php echo (int) $systemErrors['today']['warning']; ?> uyarı</span>
                                <span class="badge bg-secondary-lt"><?php echo (int) $systemErrors['today']['total']; ?> toplam</span>
                                <a href="index.php?p=activities/index&amp;tab=sistem-hatalari" class="btn btn-sm btn-outline-danger">Tümünü gör</a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th style="width: 110px;">Seviye</th>
                                        <th>Hata</th>
                                        <th>İstek</th>
                                        <th>Kullanıcı / Firma</th>
                                        <th>Zaman</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if ($systemErrors['records']): ?>
                                    <?php foreach ($systemErrors['records'] as $systemError): ?>
                                        <?php
                                        $errorLevel = (string) ($systemError['level'] ?? 'error');
                                        $errorContext = is_array($systemError['context'] ?? null) ? $systemError['context'] : [];
                                        $errorRequest = is_array($systemError['request'] ?? null) ? $systemError['request'] : [];
                                        $errorActor = is_array($systemError['actor'] ?? null) ? $systemError['actor'] : [];
                                        $sourceFile = !empty($errorContext['file']) ? basename((string) $errorContext['file']) : '';
                                        $sourceLine = !empty($errorContext['line']) ? ':' . (int) $errorContext['line'] : '';
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?php echo $errorLevelClasses[$errorLevel] ?? 'bg-secondary-lt'; ?>">
                                                    <?php echo htmlspecialchars($errorLevelLabels[$errorLevel] ?? $errorLevel); ?>
                                                </span>
                                            </td>
                                            <td style="min-width: 300px;">
                                                <div class="fw-medium text-wrap"><?php echo htmlspecialchars((string) ($systemError['message'] ?? 'Bilinmeyen hata')); ?></div>
                                                <div class="text-secondary small mt-1">
                                                    <?php echo htmlspecialchars((string) ($systemError['type'] ?? 'application_error')); ?>
                                                    <?php if ($sourceFile): ?>
                                                        · <?php echo htmlspecialchars($sourceFile . $sourceLine); ?>
                                                    <?php endif; ?>
                                                    · Kod: <?php echo htmlspecialchars((string) ($systemError['request_id'] ?? '—')); ?>
                                                </div>
                                            </td>
                                            <td class="text-nowrap">
                                                <div><?php echo htmlspecialchars((string) ($errorRequest['method'] ?? '—')); ?></div>
                                                <div class="text-secondary small"><?php echo htmlspecialchars((string) ($errorRequest['path'] ?? '—')); ?></div>
                                            </td>
                                            <td class="text-nowrap">
                                                <div>Kullanıcı: <?php echo isset($errorActor['user_id']) && $errorActor['user_id'] !== null ? (int) $errorActor['user_id'] : '—'; ?></div>
                                                <div class="text-secondary small">Firma: <?php echo isset($errorActor['firm_id']) && $errorActor['firm_id'] !== null ? (int) $errorActor['firm_id'] : '—'; ?></div>
                                            </td>
                                            <td class="text-nowrap">
                                                <?php
                                                $errorTimestamp = strtotime((string) ($systemError['timestamp'] ?? ''));
                                                echo $errorTimestamp ? date('d.m.Y H:i:s', $errorTimestamp) : '—';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-5">
                                            <i class="ti ti-circle-check text-success me-1"></i>Henüz sistem hatası kaydedilmedi.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row row-deck row-cards mt-1">
                <div class="col-lg-7">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Son sistem aktiviteleri</h3>
                                <div class="text-secondary small mt-1">Kim, hangi firma için, hangi işlemi yaptı?</div>
                            </div>
                            <div class="card-actions">
                                <a href="index.php?p=activities/index" class="btn btn-sm btn-outline-primary">Detaylı loglar</a>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-3">
                            <?php if ($recentActivities): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <?php [$activityIcon, $activityColor] = $activityIcons[$activity->activity_type] ?? ['activity', 'secondary']; ?>
                                    <div class="d-flex gap-3 activity-line">
                                        <span class="avatar avatar-sm rounded-circle bg-<?php echo $activityColor; ?>-lt text-<?php echo $activityColor; ?>">
                                            <i class="ti ti-<?php echo $activityIcon; ?>"></i>
                                        </span>
                                        <div class="flex-fill min-w-0">
                                            <div class="text-clamp"><?php echo htmlspecialchars($activity->description); ?></div>
                                            <div class="text-secondary small mt-1 d-flex align-items-center flex-wrap gap-1">
                                                <span class="fw-medium"><?php echo htmlspecialchars($activity->user_name ?? 'Sistem'); ?></span>
                                                <?php if ($activity->firm_name): ?>
                                                    · <?php echo htmlspecialchars($activity->firm_name); ?>
                                                <?php endif; ?>
                                                · <?php echo date('d.m.Y H:i', strtotime($activity->created_at)); ?>
                                                <?php
                                                    $platform = !empty($activity->platform) ? $activity->platform : 'Masaüstü';
                                                    $isMobile = (strpos(mb_strtolower($platform, 'UTF-8'), 'mobil') !== false);
                                                    $badgeColor = $isMobile ? 'azure' : 'secondary';
                                                    $badgeIcon = $isMobile ? 'device-mobile' : 'device-desktop';
                                                ?>
                                                <span class="badge bg-<?php echo $badgeColor; ?>-lt text-<?php echo $badgeColor; ?> ms-1" style="font-size: 10px; padding: 2px 6px;">
                                                    <i class="ti ti-<?php echo $badgeIcon; ?> me-1"></i><?php echo htmlspecialchars($platform); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="badge bg-secondary-lt align-self-start"><?php echo htmlspecialchars($activity->action); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty py-5">
                                    <div class="empty-icon"><i class="ti ti-activity"></i></div>
                                    <p class="empty-title">Aktivite kaydı yok</p>
                                </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card dashboard-card">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Son girişler</h3>
                                <div class="text-secondary small mt-1">Kullanıcı, cihaz ve IP kayıtları</div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                        <?php if ($recentLogins): ?>
                            <?php foreach ($recentLogins as $login): ?>
                                <?php [$deviceIcon, $deviceName] = systemDashboardDevice($login->user_agent); ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="avatar avatar-sm"><?php echo htmlspecialchars(systemDashboardInitials($login->user_name)); ?></span>
                                        <div class="flex-fill min-w-0">
                                            <div class="d-flex justify-content-between gap-2">
                                                <span class="fw-medium text-truncate"><?php echo htmlspecialchars($login->user_name); ?></span>
                                                <span class="text-secondary small text-nowrap"><?php echo date('d.m H:i', strtotime($login->login_time)); ?></span>
                                            </div>
                                            <div class="text-secondary small text-truncate">
                                                <i class="ti ti-<?php echo $deviceIcon; ?> me-1"></i><?php echo $deviceName; ?>
                                                · <?php echo htmlspecialchars($login->ip_address); ?>
                                                <?php if ($login->firm_name): ?>
                                                    · <?php echo htmlspecialchars($login->firm_name); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-secondary py-5">Giriş kaydı bulunmuyor.</div>
                        <?php endif; ?>
                        </div>
                    </div>

                    <div class="card dashboard-card mt-3">
                        <div class="card-header">
                            <div>
                                <h3 class="card-title">Güvenlik olayları</h3>
                                <div class="text-secondary small mt-1">Giriş ve superadmin güvenlik kayıtları</div>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                        <?php if ($securityEvents): ?>
                            <?php foreach ($securityEvents as $event): ?>
                                <div class="list-group-item">
                                    <div class="d-flex gap-3">
                                        <span class="avatar avatar-sm bg-red-lt text-red"><i class="ti ti-shield-exclamation"></i></span>
                                        <div class="flex-fill min-w-0">
                                            <div class="fw-medium"><?php echo htmlspecialchars($event->description); ?></div>
                                            <div class="text-secondary small mt-1">
                                                <?php echo htmlspecialchars($event->user_name ?? 'Tanımsız kullanıcı'); ?>
                                                · <?php echo htmlspecialchars($event->ip_address); ?>
                                                · <?php echo date('d.m.Y H:i', strtotime($event->created_at)); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-secondary py-5">Güvenlik olayı bulunmuyor.</div>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof ApexCharts === 'undefined') {
        return;
    }

    const trend = <?php echo json_encode($monthlyTrend, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const statusRows = <?php echo json_encode($subscriptionStatuses, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const statusLabels = <?php echo json_encode($statusLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const theme = document.body.getAttribute('data-bs-theme') || 'light';
    const textColor = theme === 'dark' ? '#aeb7c2' : '#667382';
    const gridColor = theme === 'dark' ? '#2b3545' : '#e6e8eb';

    new ApexCharts(document.querySelector('#system-subscription-trend'), {
        chart: { type: 'area', height: 300, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [
            { name: 'Yeni abonelik', data: trend.subscriptions },
            { name: 'Yeni abone', data: trend.subscribers }
        ],
        colors: ['#206bc4', '#2fb344'],
        stroke: { curve: 'smooth', width: 3 },
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .28, opacityTo: .03, stops: [0, 95, 100] } },
        dataLabels: { enabled: false },
        xaxis: { categories: trend.labels, labels: { style: { colors: textColor } }, axisBorder: { show: false }, axisTicks: { show: false } },
        yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: textColor }, formatter: value => Math.round(value) } },
        grid: { borderColor: gridColor, strokeDashArray: 4 },
        legend: { position: 'top', horizontalAlign: 'right', labels: { colors: textColor } },
        tooltip: { theme: theme }
    }).render();

    const statusSeries = statusRows.map(row => Number(row.total));
    const translatedStatuses = statusRows.map(row => statusLabels[row.durum] || row.durum);
    new ApexCharts(document.querySelector('#system-subscription-status'), {
        chart: { type: 'donut', height: 300, fontFamily: 'inherit' },
        series: statusSeries.length ? statusSeries : [1],
        labels: statusSeries.length ? translatedStatuses : ['Kayıt yok'],
        colors: statusSeries.length ? ['#2fb344', '#667382', '#d63939', '#f59f00', '#4299e1'] : ['#dce1e7'],
        dataLabels: { enabled: statusSeries.length > 0 },
        legend: { position: 'bottom', labels: { colors: textColor } },
        stroke: { width: 2 },
        plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Toplam', color: textColor } } } } },
        tooltip: { enabled: statusSeries.length > 0, theme: theme }
    }).render();
});
</script>
