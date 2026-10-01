<?php

if ((int) ($_SESSION['user']->superadmin ?? 0) !== 1) {
    header('Location: /yetkisiz-erisim');
    exit();
}

require_once ROOT . '/Model/SystemDashboardModel.php';
require_once ROOT . '/Service/SystemLogService.php';
require_once ROOT . '/App/Helper/helper.php';

use App\Helper\Helper;
use App\Helper\Security;

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

$aylarTr = ['01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan', '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos', '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'];
$gunlerTr = ['Sunday' => 'Pazar', 'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba', 'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi'];
$todayFormatted = date('d') . ' ' . ($aylarTr[date('m')] ?? date('m')) . ' ' . date('Y') . ', ' . ($gunlerTr[date('l')] ?? date('l'));
$userName = $_SESSION['user']->full_name ?? 'Süper Admin';

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
    /* Quick Action Mini Cards on Top Right */
    .quick-nav-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        width: 76px;
        height: 72px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .04), 0 4px 10px rgba(15, 23, 42, .03);
        transition: all 0.2s ease;
        color: #475569;
        text-align: center;
        padding: 6px;
    }
    .quick-nav-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(15, 23, 42, .08);
        border-color: #cbd5e1;
        color: #0f172a;
    }
    .quick-nav-card i {
        font-size: 20px;
        margin-bottom: 4px;
    }
    .quick-nav-card span {
        font-size: 10.5px;
        font-weight: 500;
        line-height: 1.15;
        white-space: nowrap;
    }
    [data-bs-theme="dark"] .quick-nav-card {
        background: #1e293b;
        border-color: #334155;
        color: #94a3b8;
    }
    [data-bs-theme="dark"] .quick-nav-card:hover {
        color: #ffffff;
        border-color: #475569;
    }

    /* KPI Summary Cards */
    .stat-kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0 !important;
        border-radius: 12px !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .05), 0 4px 12px rgba(15, 23, 42, .03) !important;
        transition: all 0.2s ease;
        height: 100%;
        min-height: 130px;
        display: flex;
        flex-direction: column;
    }
    .stat-kpi-card:hover {
        box-shadow: 0 6px 20px rgba(15, 23, 42, .08) !important;
        border-color: #cbd5e1 !important;
    }
    .stat-kpi-title {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stat-kpi-value {
        font-size: 1.55rem;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.2;
        letter-spacing: -0.4px;
    }
    .stat-kpi-footer {
        border-top: 1px solid #f1f5f9;
        padding-top: 8px;
        margin-top: auto;
    }

    [data-bs-theme="dark"] .stat-kpi-card {
        background: #1a2234;
        border-color: rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35) !important;
    }
    [data-bs-theme="dark"] .stat-kpi-title {
        color: #94a3b8;
    }
    [data-bs-theme="dark"] .stat-kpi-value {
        color: #f8fafc;
    }
    [data-bs-theme="dark"] .stat-kpi-footer {
        border-top-color: #242e42;
    }

    /* Widgets Container Cards */
    #admin-widgets-sortable .card {
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07), 0 1px 3px rgba(15, 23, 42, 0.04) !important;
        border: 1px solid #dbe3ec !important;
        border-radius: 12px !important;
        transition: box-shadow 0.25s ease, transform 0.25s ease;
        background-color: #ffffff;
    }
    #admin-widgets-sortable .card:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.11), 0 2px 6px rgba(15, 23, 42, 0.05) !important;
    }
    [data-bs-theme="dark"] #admin-widgets-sortable .card {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        background-color: #1a2234;
    }

    /* Mac Style Titlebar */
    .mac-titlebar {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        background: #fcfcfc;
        border-bottom: 1px solid #f0f0f0;
        border-top-left-radius: 11px;
        border-top-right-radius: 11px;
    }
    [data-bs-theme="dark"] .mac-titlebar {
        background: #1d2735;
        border-bottom: 1px solid #2d394b;
    }
    .mac-buttons {
        display: flex;
        gap: 6px;
        margin-right: 12px;
    }
    .mac-btn {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .mac-btn:hover {
        transform: scale(1.2);
    }
    .mac-close { background-color: #ff5f56; }
    .mac-min { background-color: #ffbd2e; }
    .mac-max { background-color: #27c93f; }
    .mac-title {
        font-size: 11px;
        font-weight: 600;
        color: #6c7a91;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0;
    }
    [data-bs-theme="dark"] .mac-title {
        color: #94a3b8;
    }
    .drag-handle {
        cursor: grab;
        color: #adb5bd;
    }
    .drag-handle:active {
        cursor: grabbing;
    }
    [data-bs-theme="dark"] .drag-handle {
        color: #4b5563;
    }
    .sortable-ghost {
        opacity: 0.4;
    }
    .fullscreen-card {
        position: fixed !important;
        top: 0;
        left: 0;
        width: 100vw !important;
        height: 100vh !important;
        z-index: 1050;
        margin: 0 !important;
        border-radius: 0 !important;
    }
    .resizable-card {
        resize: both;
        overflow: hidden;
        min-width: 300px;
        min-height: 150px;
    }
    .minimized-card {
        height: auto !important;
        min-height: 0 !important;
        resize: none !important;
    }
    .minimized-card .card-body,
    .minimized-card .table-responsive,
    .minimized-card .list-group {
        display: none !important;
    }

    /* Activity line styles */
    .activity-line {
        position: relative;
    }
    .activity-line:not(:last-child)::after {
        content: "";
        position: absolute;
        left: 17px;
        top: 38px;
        bottom: -12px;
        width: 1px;
        background: var(--tblr-border-color, #e2e8f0);
    }
    .text-clamp {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<div class="page-wrapper">
    <!-- Hero / Greeting Header -->
    <div class="page-header d-print-none pb-2">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <!-- Left: Greeting, Date & Widget Dropdown -->
                <div class="col">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-white text-secondary border px-2 py-1 fw-medium" style="font-size: 11px; border-radius: 6px;">
                            <i class="ti ti-calendar me-1 text-primary"></i><?php echo $todayFormatted; ?>
                        </span>
                        <span class="badge bg-purple-lt border border-purple-subtle px-2 py-1 fw-medium" style="font-size: 11px; border-radius: 6px;">
                            <i class="ti ti-shield-check me-1 text-purple"></i>Süper Admin
                        </span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-white border dropdown-toggle px-2 py-1 text-secondary" data-bs-toggle="dropdown" style="font-size: 11px; border-radius: 6px;">
                                <i class="ti ti-layout-grid me-1 text-primary"></i> Widget İşlemleri
                            </button>
                            <div class="dropdown-menu shadow-sm p-3" id="admin-dashboard-colvis-menu" style="min-width: 260px; max-height: 380px; overflow-y: auto;">
                                <!-- Dynamic checkboxes loaded via JS -->
                            </div>
                        </div>
                        <button class="btn btn-sm btn-white border px-2 py-1 text-secondary" onclick="resetAdminDashboardLayout()" style="font-size: 11px; border-radius: 6px;" title="Varsayılan Düzen">
                            <i class="ti ti-rotate-clockwise me-1 text-primary"></i> Sıfırla
                        </button>
                    </div>
                    <h2 class="page-title fw-bold text-dark mb-1" style="font-size: 1.4rem; letter-spacing: -0.4px;">
                        Hoş Geldiniz, <?php echo htmlspecialchars($userName); ?> 👑
                    </h2>
                    <div class="text-secondary small">
                        Sistem abonelik performansı, platform kullanım analizleri, hata ve güvenlik kayıtları görünümü.
                    </div>
                </div>

                <!-- Right: Mini Quick Action Cards -->
                <div class="col-auto ms-auto d-none d-md-block">
                    <div class="d-flex gap-2">
                        <a href="/abonelikler" class="quick-nav-card text-decoration-none">
                            <i class="ti ti-crown text-warning"></i>
                            <span>Abonelikler</span>
                        </a>
                        <a href="/paketler" class="quick-nav-card text-decoration-none">
                            <i class="ti ti-packages text-primary"></i>
                            <span>Paketler</span>
                        </a>
                        <a href="/sistem-aktiviteleri" class="quick-nav-card text-decoration-none">
                            <i class="ti ti-activity text-azure"></i>
                            <span>Aktiviteler</span>
                        </a>
                        <a href="/sistem-aktiviteleri?tab=sistem-hatalari" class="quick-nav-card text-decoration-none">
                            <i class="ti ti-bug text-danger"></i>
                            <span>Hatalar</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body mt-2">
        <div class="container-xl">
            <!-- 4 Equal Size & Height KPI Summary Cards -->
            <div class="row row-cards g-3 mb-3" id="admin-stats-sortable">
                <!-- 1. Toplam Abone -->
                <div class="col-sm-6 col-xl-3" data-id="stat-total-subscribers">
                    <div class="stat-kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="stat-kpi-title">TOPLAM ABONE</span>
                            <span class="avatar avatar-sm rounded-2 bg-blue-lt text-primary">
                                <i class="ti ti-users-group" style="font-size: 18px;"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="stat-kpi-value"><?php echo number_format((int) ($summary->total_subscribers ?? 0), 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between stat-kpi-footer">
                            <div class="d-flex gap-1 flex-wrap">
                                <span class="badge bg-success-lt" style="font-size: 10px; font-weight: 600;">Aktif Kullanıcı: <?php echo (int) ($summary->active_users ?? 0); ?></span>
                                <?php if (($summary->trial_subscribers ?? 0) > 0): ?>
                                    <span class="badge bg-cyan-lt" style="font-size: 10px;">Deneme: <?php echo (int) $summary->trial_subscribers; ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="/abonelikler" class="text-primary small text-decoration-none fw-semibold" style="font-size: 11px;">
                                Aboneler <i class="ti ti-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 2. Aktif Abonelik -->
                <div class="col-sm-6 col-xl-3" data-id="stat-active-subscriptions">
                    <div class="stat-kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="stat-kpi-title">AKTİF ABONELİK</span>
                            <span class="avatar avatar-sm rounded-2 bg-success-lt text-success">
                                <i class="ti ti-rosette-discount-check" style="font-size: 18px;"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="stat-kpi-value"><?php echo number_format((int) ($summary->active_subscriptions ?? 0), 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between stat-kpi-footer">
                            <div class="d-flex gap-1 flex-wrap">
                                <?php if (($summary->expiring_subscriptions ?? 0) > 0): ?>
                                    <span class="badge bg-warning-lt" style="font-size: 10px; font-weight: 600;">7 Günde Bitecek: <?php echo (int) $summary->expiring_subscriptions; ?></span>
                                <?php endif; ?>
                                <span class="badge bg-purple-lt" style="font-size: 10px;">Firma: <?php echo (int) ($summary->total_firms ?? 0); ?></span>
                            </div>
                            <a href="/abonelikler" class="text-success small text-decoration-none fw-semibold" style="font-size: 11px;">
                                Yönet <i class="ti ti-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 3. Bugünkü İşlem & Giriş -->
                <div class="col-sm-6 col-xl-3" data-id="stat-daily-activity">
                    <div class="stat-kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="stat-kpi-title">BUGÜNKÜ İŞLEM VE GİRİŞ</span>
                            <span class="avatar avatar-sm rounded-2 bg-orange-lt text-orange">
                                <i class="ti ti-bolt" style="font-size: 18px;"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="stat-kpi-value"><?php echo number_format((int) ($summary->activities_today ?? 0), 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between stat-kpi-footer">
                            <div class="d-flex gap-1">
                                <span class="badge bg-indigo-lt" style="font-size: 10px; font-weight: 600;">Giriş: <?php echo (int) ($summary->users_logged_in_today ?? 0); ?></span>
                                <span class="badge bg-success-lt" style="font-size: 10px;">Bugün Aktif</span>
                            </div>
                            <a href="/sistem-aktiviteleri" class="text-orange small text-decoration-none fw-semibold" style="font-size: 11px;">
                                Detay <i class="ti ti-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 4. Sistem Hataları -->
                <div class="col-sm-6 col-xl-3" data-id="stat-system-health">
                    <div class="stat-kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="stat-kpi-title">BUGÜNKÜ SİSTEM HATALARI</span>
                            <span class="avatar avatar-sm rounded-2 <?php echo ((int) ($systemErrors['today']['total'] ?? 0) > 0) ? 'bg-danger-lt text-danger' : 'bg-teal-lt text-teal'; ?>">
                                <i class="ti <?php echo ((int) ($systemErrors['today']['total'] ?? 0) > 0) ? 'ti-bug' : 'ti-circle-check'; ?>" style="font-size: 18px;"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="stat-kpi-value <?php echo ((int) ($systemErrors['today']['total'] ?? 0) > 0) ? 'text-danger' : 'text-dark'; ?>">
                                <?php echo number_format((int) ($systemErrors['today']['total'] ?? 0), 0, ',', '.'); ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between stat-kpi-footer">
                            <div class="d-flex gap-1">
                                <span class="badge bg-red-lt" style="font-size: 10px;">Kritik: <?php echo (int) ($systemErrors['today']['critical'] ?? 0); ?></span>
                                <span class="badge bg-warning-lt" style="font-size: 10px;">Hata/Uyarı: <?php echo (int) (($systemErrors['today']['error'] ?? 0) + ($systemErrors['today']['warning'] ?? 0)); ?></span>
                            </div>
                            <a href="/sistem-aktiviteleri?tab=sistem-hatalari" class="badge bg-danger-lt text-danger text-decoration-none" style="font-size: 10px;">
                                Loglar
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widgets Container Row -->
            <div class="row row-cards mt-3" id="admin-widgets-sortable">
                <!-- Widget 1: Abone ve Abonelik Gelişimi (Aylık Trend) -->
                <div class="col-lg-8" data-id="widget-subscription-trend">
                    <div class="card">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <span class="mac-btn mac-close" title="Kapat"></span>
                                <span class="mac-btn mac-min" title="Küçült"></span>
                                <span class="mac-btn mac-max" title="Büyüt"></span>
                            </div>
                            <span class="mac-title">ABONE VE ABONELİK GELİŞİMİ (SON 12 AY)</span>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <i class="ti ti-grip-vertical drag-handle" title="Taşı"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="system-subscription-trend" style="min-height: 290px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Widget 2: Abonelik Durumları (Donut Dağılımı) -->
                <div class="col-lg-4" data-id="widget-subscription-status">
                    <div class="card">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <span class="mac-btn mac-close" title="Kapat"></span>
                                <span class="mac-btn mac-min" title="Küçült"></span>
                                <span class="mac-btn mac-max" title="Büyüt"></span>
                            </div>
                            <span class="mac-title">ABONELİK DURUM DAĞILIMI</span>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <i class="ti ti-grip-vertical drag-handle" title="Taşı"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="system-subscription-status" style="min-height: 290px;"></div>
                        </div>
                    </div>
                </div>

                <!-- Widget 3: Son Abone Olanlar Tablosu -->
                <div class="col-12" data-id="widget-recent-subscribers">
                    <div class="card">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <span class="mac-btn mac-close" title="Kapat"></span>
                                <span class="mac-btn mac-min" title="Küçült"></span>
                                <span class="mac-btn mac-max" title="Büyüt"></span>
                            </div>
                            <span class="mac-title">SON ABONE OLANLAR</span>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <a href="/abonelikler" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 11px;">
                                    Tüm Aboneler
                                </a>
                                <i class="ti ti-grip-vertical drag-handle" title="Taşı"></i>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th>Abone Bilgisi</th>
                                        <th>Paket</th>
                                        <th>Firma Sayısı</th>
                                        <th>Kayıt Tarihi</th>
                                        <th>Bitiş Tarihi</th>
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
                                                    <span class="avatar avatar-sm bg-blue-lt text-blue me-2 fw-bold" style="border-radius: 8px;">
                                                        <?php echo htmlspecialchars(systemDashboardInitials($subscriber->full_name)); ?>
                                                    </span>
                                                    <div>
                                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($subscriber->full_name); ?></div>
                                                        <div class="text-secondary small"><?php echo htmlspecialchars($subscriber->email); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-azure-lt fw-medium">
                                                    <?php echo htmlspecialchars($subscriber->package_name ?? ($isTrial ? '15 Günlük Deneme' : 'Paket yok')); ?>
                                                </span>
                                            </td>
                                            <td><span class="badge bg-secondary-lt fw-semibold"><?php echo (int) $subscriber->firm_count; ?> Firma</span></td>
                                            <td class="text-nowrap small text-secondary"><?php echo date('d.m.Y H:i', strtotime($subscriber->created_at)); ?></td>
                                            <td class="text-nowrap small"><?php echo $subscriber->bitis_tarihi ? date('d.m.Y', strtotime($subscriber->bitis_tarihi)) : '—'; ?></td>
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
                                    <tr><td colspan="6" class="text-center text-secondary py-4">Henüz abone kaydı bulunmuyor.</td></tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Widget 4: Sistem Hata & İstisna Takibi -->
                <div class="col-12" data-id="widget-system-errors">
                    <div class="card">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <span class="mac-btn mac-close" title="Kapat"></span>
                                <span class="mac-btn mac-min" title="Küçült"></span>
                                <span class="mac-btn mac-max" title="Büyüt"></span>
                            </div>
                            <span class="mac-title">SİSTEM HATALARI VE UYARILAR</span>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <span class="badge bg-red-lt text-red" style="font-size: 10px;">Bugün <?php echo (int) ($systemErrors['today']['critical'] ?? 0); ?> kritik</span>
                                <span class="badge bg-danger-lt text-danger" style="font-size: 10px;"><?php echo (int) ($systemErrors['today']['error'] ?? 0); ?> hata</span>
                                <span class="badge bg-warning-lt text-warning" style="font-size: 10px;"><?php echo (int) ($systemErrors['today']['warning'] ?? 0); ?> uyarı</span>
                                <a href="/sistem-aktiviteleri?tab=sistem-hatalari" class="btn btn-sm btn-outline-danger py-0 px-2" style="font-size: 11px;">
                                    Tüm Hatalar
                                </a>
                                <i class="ti ti-grip-vertical drag-handle" title="Taşı"></i>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 100px;">Seviye</th>
                                        <th>Hata Mesajı & Kod</th>
                                        <th>İstek (Endpoint)</th>
                                        <th>Aktör (Kullanıcı / Firma)</th>
                                        <th>Zaman</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($systemErrors['records'])): ?>
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
                                            <td style="min-width: 280px;">
                                                <div class="fw-semibold text-dark text-wrap"><?php echo htmlspecialchars((string) ($systemError['message'] ?? 'Bilinmeyen hata')); ?></div>
                                                <div class="text-secondary small mt-1">
                                                    <code><?php echo htmlspecialchars((string) ($systemError['type'] ?? 'application_error')); ?></code>
                                                    <?php if ($sourceFile): ?>
                                                        · <span class="text-muted"><?php echo htmlspecialchars($sourceFile . $sourceLine); ?></span>
                                                    <?php endif; ?>
                                                    · ID: <span class="badge bg-light text-secondary border" style="font-size: 9.5px;"><?php echo htmlspecialchars((string) ($systemError['request_id'] ?? '—')); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-nowrap small">
                                                <span class="badge bg-secondary-lt fw-bold"><?php echo htmlspecialchars((string) ($errorRequest['method'] ?? 'GET')); ?></span>
                                                <div class="text-secondary mt-1"><?php echo htmlspecialchars((string) ($errorRequest['path'] ?? '—')); ?></div>
                                            </td>
                                            <td class="text-nowrap small">
                                                <div>Kullanıcı: <strong><?php echo isset($errorActor['user_id']) && $errorActor['user_id'] !== null ? (int) $errorActor['user_id'] : '—'; ?></strong></div>
                                                <div class="text-secondary">Firma: <?php echo isset($errorActor['firm_id']) && $errorActor['firm_id'] !== null ? (int) $errorActor['firm_id'] : '—'; ?></div>
                                            </td>
                                            <td class="text-nowrap small text-secondary">
                                                <?php
                                                $errorTimestamp = strtotime((string) ($systemError['timestamp'] ?? ''));
                                                echo $errorTimestamp ? date('d.m.Y H:i:s', $errorTimestamp) : '—';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-secondary py-4">
                                            <i class="ti ti-circle-check text-success me-1"></i>Henüz sistem hatası kaydedilmedi.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Widget 5: Son Sistem Aktiviteleri -->
                <div class="col-lg-7" data-id="widget-recent-activities">
                    <div class="card">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <span class="mac-btn mac-close" title="Kapat"></span>
                                <span class="mac-btn mac-min" title="Küçült"></span>
                                <span class="mac-btn mac-max" title="Büyüt"></span>
                            </div>
                            <span class="mac-title">SON SİSTEM AKTİVİTELERİ</span>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <a href="/sistem-aktiviteleri" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 11px;">
                                    Detaylı Loglar
                                </a>
                                <i class="ti ti-grip-vertical drag-handle" title="Taşı"></i>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-3">
                            <?php if ($recentActivities): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <?php [$activityIcon, $activityColor] = $activityIcons[$activity->activity_type] ?? ['activity', 'secondary']; ?>
                                    <div class="d-flex gap-3 activity-line">
                                        <span class="avatar avatar-sm rounded-circle bg-<?php echo $activityColor; ?>-lt text-<?php echo $activityColor; ?> flex-shrink-0">
                                            <i class="ti ti-<?php echo $activityIcon; ?>"></i>
                                        </span>
                                        <div class="flex-fill min-w-0">
                                            <div class="text-clamp fw-medium text-dark"><?php echo htmlspecialchars($activity->description); ?></div>
                                            <div class="text-secondary small mt-1 d-flex align-items-center flex-wrap gap-1">
                                                <span class="fw-semibold text-primary"><?php echo htmlspecialchars($activity->user_name ?? 'Sistem'); ?></span>
                                                <?php if ($activity->firm_name): ?>
                                                    · <span class="text-muted"><?php echo htmlspecialchars($activity->firm_name); ?></span>
                                                <?php endif; ?>
                                                · <span><?php echo date('d.m.Y H:i', strtotime($activity->created_at)); ?></span>
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
                                        <span class="badge bg-secondary-lt align-self-start text-uppercase" style="font-size: 9.5px;"><?php echo htmlspecialchars($activity->action); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty py-4">
                                    <div class="empty-icon"><i class="ti ti-activity"></i></div>
                                    <p class="empty-title">Aktivite kaydı yok</p>
                                </div>
                            <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Widget 6: Son Girişler ve Güvenlik Olayları -->
                <div class="col-lg-5" data-id="widget-recent-logins">
                    <div class="card">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <span class="mac-btn mac-close" title="Kapat"></span>
                                <span class="mac-btn mac-min" title="Küçült"></span>
                                <span class="mac-btn mac-max" title="Büyüt"></span>
                            </div>
                            <span class="mac-title">SON GİRİŞLER & GÜVENLİK</span>
                            <div class="ms-auto d-flex align-items-center gap-2">
                                <i class="ti ti-grip-vertical drag-handle" title="Taşı"></i>
                            </div>
                        </div>
                        <div class="list-group list-group-flush">
                        <?php if ($recentLogins): ?>
                            <?php foreach (array_slice($recentLogins, 0, 6) as $login): ?>
                                <?php [$deviceIcon, $deviceName] = systemDashboardDevice($login->user_agent); ?>
                                <div class="list-group-item py-2 px-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="avatar avatar-xs bg-azure-lt text-azure rounded-circle fw-bold"><?php echo htmlspecialchars(systemDashboardInitials($login->user_name)); ?></span>
                                        <div class="flex-fill min-w-0">
                                            <div class="d-flex justify-content-between gap-1">
                                                <span class="fw-semibold text-dark text-truncate" style="font-size: 12.5px;"><?php echo htmlspecialchars($login->user_name); ?></span>
                                                <span class="text-secondary small text-nowrap" style="font-size: 11px;"><?php echo date('d.m H:i', strtotime($login->login_time)); ?></span>
                                            </div>
                                            <div class="text-secondary small text-truncate" style="font-size: 11.5px;">
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
                            <div class="text-center text-secondary py-3 small">Giriş kaydı bulunmuyor.</div>
                        <?php endif; ?>
                        </div>

                        <?php if ($securityEvents): ?>
                            <div class="p-2 border-top bg-light-subtle">
                                <div class="text-muted fw-bold text-uppercase px-2 mb-1" style="font-size: 10px; letter-spacing: 0.5px;">
                                    <i class="ti ti-shield-alert text-danger me-1"></i>Güvenlik Olayları
                                </div>
                                <div class="list-group list-group-flush">
                                    <?php foreach (array_slice($securityEvents, 0, 3) as $event): ?>
                                        <div class="list-group-item py-1.5 px-2 bg-transparent border-0">
                                            <div class="d-flex gap-2">
                                                <span class="avatar avatar-xs bg-red-lt text-red flex-shrink-0"><i class="ti ti-alert-triangle"></i></span>
                                                <div class="flex-fill min-w-0">
                                                    <div class="fw-medium text-truncate small"><?php echo htmlspecialchars($event->description); ?></div>
                                                    <div class="text-secondary" style="font-size: 10.5px;">
                                                        <?php echo htmlspecialchars($event->user_name ?? 'Tanımsız'); ?> · <?php echo htmlspecialchars($event->ip_address); ?> · <?php echo date('d.m.Y H:i', strtotime($event->created_at)); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Sortable JS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const layoutKey = 'admin_dashboard_layout_v1';
    const cardSelector = '#admin-stats-sortable > [data-id], #admin-widgets-sortable > [data-id]';

    function saveLayout() {
        const layout = {
            'admin-stats-sortable': Array.from(document.getElementById('admin-stats-sortable')?.children || []).map(c => c.getAttribute('data-id')).filter(Boolean),
            'admin-widgets-sortable': Array.from(document.getElementById('admin-widgets-sortable')?.children || []).map(c => c.getAttribute('data-id')).filter(Boolean)
        };
        localStorage.setItem(layoutKey, JSON.stringify(layout));
    }

    function restoreLayout() {
        const savedLayout = localStorage.getItem(layoutKey);
        if (savedLayout) {
            try {
                const layout = JSON.parse(savedLayout);
                for (const containerId in layout) {
                    const container = document.getElementById(containerId);
                    if (container) {
                        layout[containerId].forEach(id => {
                            const el = document.querySelector(`[data-id="${id}"]`);
                            if (el) {
                                container.appendChild(el);
                            }
                        });
                    }
                }
            } catch(e) {}
        }

        // Kapatılmış kartları gizle
        document.querySelectorAll(cardSelector).forEach(function(el) {
            var id = el.getAttribute('data-id');
            if (id && localStorage.getItem('admin_card_hidden_' + id) === '1') {
                el.style.display = 'none';
            }
        });
    }

    restoreLayout();

    function initCardVisibilityDropdown() {
        const colvisMenu = document.getElementById('admin-dashboard-colvis-menu');
        if (!colvisMenu) return;

        colvisMenu.innerHTML = '';

        const cards = [];
        document.querySelectorAll(cardSelector).forEach(el => {
            const id = el.getAttribute('data-id');
            if (!id) return;

            let title = '';
            const titleEl = el.querySelector('.mac-title') || el.querySelector('.stat-kpi-title');
            if (titleEl) {
                title = titleEl.textContent.trim();
            }
            if (!title) {
                if (id === 'stat-total-subscribers') title = 'TOPLAM ABONE';
                else if (id === 'stat-active-subscriptions') title = 'AKTİF ABONELİK';
                else if (id === 'stat-daily-activity') title = 'BUGÜNKÜ İŞLEM VE GİRİŞ';
                else if (id === 'stat-system-health') title = 'BUGÜNKÜ SİSTEM HATALARI';
                else if (id === 'widget-subscription-trend') title = 'ABONE GELİŞİMİ';
                else if (id === 'widget-subscription-status') title = 'ABONELİK DURUM DAĞILIMI';
                else if (id === 'widget-recent-subscribers') title = 'SON ABONE OLANLAR';
                else if (id === 'widget-system-errors') title = 'SİSTEM HATALARI';
                else if (id === 'widget-recent-activities') title = 'SON SİSTEM AKTİVİTELERİ';
                else if (id === 'widget-recent-logins') title = 'SON GİRİŞLER & GÜVENLİK';
                else title = id.replace('widget-', '').replace('stat-', '').toUpperCase();
            }

            cards.push({ id, title, element: el });
        });

        cards.forEach(card => {
            const isChecked = localStorage.getItem('admin_card_hidden_' + card.id) !== '1';
            
            const div = document.createElement('div');
            div.className = 'form-check mb-2';
            
            const chk = document.createElement('input');
            chk.className = 'form-check-input admin-dashboard-colvis-chk';
            chk.type = 'checkbox';
            chk.id = 'chk-admin-colvis-' + card.id;
            chk.checked = isChecked;
            
            const label = document.createElement('label');
            label.className = 'form-check-label text-nowrap fw-medium';
            label.htmlFor = chk.id;
            label.textContent = card.title;
            
            chk.addEventListener('change', function() {
                if (this.checked) {
                    localStorage.removeItem('admin_card_hidden_' + card.id);
                    card.element.style.display = '';
                } else {
                    localStorage.setItem('admin_card_hidden_' + card.id, '1');
                    card.element.style.display = 'none';
                }
            });
            
            div.appendChild(chk);
            div.appendChild(label);
            colvisMenu.appendChild(div);
        });
    }

    initCardVisibilityDropdown();

    function initSortable(containerId) {
        var el = document.getElementById(containerId);
        if (!el) return;
        
        Sortable.create(el, {
            group: 'admin-dashboard-widgets',
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onSort: function () {
                saveLayout();
            }
        });
    }

    initSortable('admin-widgets-sortable');

    // Make widget cards resizable and handle size persistence
    var resizeObserver = new ResizeObserver(function(entries) {
        for (var i = 0; i < entries.length; i++) {
            var entry = entries[i];
            var card = entry.target;
            var wrapper = card.closest('[data-id]');
            if (!wrapper) continue;

            if (card.style.width || card.style.height) {
                var id = wrapper.getAttribute('data-id');
                var dimensions = {
                    width: card.style.width,
                    height: card.style.height
                };
                localStorage.setItem('admin_card_size_' + id, JSON.stringify(dimensions));

                wrapper.style.width = 'fit-content';
                wrapper.style.flex = '0 0 auto';
            }
        }
    });

    document.querySelectorAll('#admin-widgets-sortable .card').forEach(function(card) {
        var wrapper = card.closest(cardSelector);
        if (wrapper) {
            card.classList.add('resizable-card');
            
            var id = wrapper.getAttribute('data-id');
            var saved = localStorage.getItem('admin_card_size_' + id);
            if (saved) {
                try {
                    var dim = JSON.parse(saved);
                    if (dim.width) card.style.width = dim.width;
                    if (dim.height) card.style.height = dim.height;
                    
                    if (dim.width) {
                        wrapper.style.width = 'fit-content';
                        wrapper.style.flex = '0 0 auto';
                    }
                } catch (e) {}
            }
            
            var isMin = localStorage.getItem('admin_card_min_' + id);
            if (isMin === '1') {
                card.classList.add('minimized-card');
            }
            
            resizeObserver.observe(card);
        }
    });

    // Mac Buttons Handler
    document.addEventListener('click', function(e) {
        // Kapatma (Close)
        if (e.target.classList.contains('mac-close')) {
            let cardWrap = e.target.closest('[data-id]');
            if (cardWrap) {
                cardWrap.style.display = 'none';
                let id = cardWrap.getAttribute('data-id');
                if (id) {
                    localStorage.setItem('admin_card_hidden_' + id, '1');
                    let chk = document.getElementById('chk-admin-colvis-' + id);
                    if (chk) chk.checked = false;
                }
            }
        }
        // Küçültme (Minimize)
        if (e.target.classList.contains('mac-min')) {
            let card = e.target.closest('.card');
            if (card) {
                card.classList.toggle('minimized-card');
                
                let wrapper = card.closest('[data-id]');
                if (wrapper) {
                    let id = wrapper.getAttribute('data-id');
                    if (card.classList.contains('minimized-card')) {
                        localStorage.setItem('admin_card_min_' + id, '1');
                    } else {
                        localStorage.removeItem('admin_card_min_' + id);
                    }
                }
            }
        }
        // Tam Ekran (Maximize)
        if (e.target.classList.contains('mac-max')) {
            let card = e.target.closest('.card');
            if (card) {
                card.classList.toggle('fullscreen-card');
            }
        }
    });

    window.resetAdminDashboardLayout = function() {
        if(confirm("Yönetici panosu düzenini ve boyutlarını sıfırlamak istediğinize emin misiniz?")) {
            localStorage.removeItem(layoutKey);
            document.querySelectorAll(cardSelector).forEach(function(el) {
                var id = el.getAttribute('data-id');
                localStorage.removeItem('admin_card_size_' + id);
                localStorage.removeItem('admin_card_min_' + id);
                localStorage.removeItem('admin_card_hidden_' + id);
            });
            window.location.reload();
        }
    };

    // ApexCharts Render
    if (typeof ApexCharts !== 'undefined') {
        const trend = <?php echo json_encode($monthlyTrend, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const statusRows = <?php echo json_encode($subscriptionStatuses, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const statusLabels = <?php echo json_encode($statusLabels, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
        const theme = document.body.getAttribute('data-bs-theme') || 'light';
        const textColor = theme === 'dark' ? '#aeb7c2' : '#667382';
        const gridColor = theme === 'dark' ? '#2b3545' : '#e6e8eb';

        const trendEl = document.querySelector('#system-subscription-trend');
        if (trendEl) {
            new ApexCharts(trendEl, {
                chart: { type: 'area', height: 290, toolbar: { show: false }, fontFamily: 'inherit' },
                series: [
                    { name: 'Yeni Abonelik', data: trend.subscriptions || [] },
                    { name: 'Yeni Abone', data: trend.subscribers || [] }
                ],
                colors: ['#206bc4', '#2fb344'],
                stroke: { curve: 'smooth', width: 3 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .28, opacityTo: .03, stops: [0, 95, 100] } },
                dataLabels: { enabled: false },
                xaxis: { categories: trend.labels || [], labels: { style: { colors: textColor } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { min: 0, forceNiceScale: true, labels: { style: { colors: textColor }, formatter: value => Math.round(value) } },
                grid: { borderColor: gridColor, strokeDashArray: 4 },
                legend: { position: 'top', horizontalAlign: 'right', labels: { colors: textColor } },
                tooltip: { theme: theme }
            }).render();
        }

        const statusEl = document.querySelector('#system-subscription-status');
        if (statusEl) {
            const statusSeries = (statusRows || []).map(row => Number(row.total));
            const translatedStatuses = (statusRows || []).map(row => statusLabels[row.durum] || row.durum);
            new ApexCharts(statusEl, {
                chart: { type: 'donut', height: 290, fontFamily: 'inherit' },
                series: statusSeries.length ? statusSeries : [1],
                labels: statusSeries.length ? translatedStatuses : ['Kayıt yok'],
                colors: statusSeries.length ? ['#2fb344', '#667382', '#d63939', '#f59f00', '#4299e1'] : ['#dce1e7'],
                dataLabels: { enabled: statusSeries.length > 0 },
                legend: { position: 'bottom', labels: { colors: textColor } },
                stroke: { width: 2 },
                plotOptions: { pie: { donut: { size: '68%', labels: { show: true, total: { show: true, label: 'Toplam', color: textColor } } } } },
                tooltip: { enabled: statusSeries.length > 0, theme: theme }
            }).render();
        }
    }
});
</script>
