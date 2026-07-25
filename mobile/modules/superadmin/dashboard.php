<?php
if (empty($is_superadmin)) {
    http_response_code(403);
    exit('Bu sayfaya erişim yetkiniz yok.');
}

require_once ROOT . '/Model/SystemDashboardModel.php';

$dashboardModel = new SystemDashboardModel();
$summary = $dashboardModel->getSummary();
$monthlyTrend = $dashboardModel->getMonthlyTrend(6);
$subscriptionStatuses = $dashboardModel->getSubscriptionStatuses();
$recentSubscribers = $dashboardModel->getRecentSubscribers(5);
$recentActivities = $dashboardModel->getRecentActivities(6);
$securityEvents = $dashboardModel->getRecentSecurityEvents(4);

$statusLabels = [
    'aktif' => 'Aktif',
    'sona_erdi' => 'Sona erdi',
    'iptal' => 'İptal',
    'onay_bekliyor' => 'Onay bekliyor',
    'beklemede' => 'Beklemede',
];
$statusColors = [
    'aktif' => '#2fb344',
    'sona_erdi' => '#667382',
    'iptal' => '#d63939',
    'onay_bekliyor' => '#f59f00',
    'beklemede' => '#4299e1',
];
$statusClasses = [
    'aktif' => 'bg-success-lt text-success',
    'sona_erdi' => 'bg-secondary-lt text-secondary',
    'iptal' => 'bg-danger-lt text-danger',
    'onay_bekliyor' => 'bg-warning-lt text-warning',
    'beklemede' => 'bg-azure-lt text-azure',
];

$statusChartLabels = [];
$statusChartValues = [];
$statusChartColors = [];
foreach ($subscriptionStatuses as $status) {
    $key = (string) $status->durum;
    $statusChartLabels[] = $statusLabels[$key] ?? ucfirst(str_replace('_', ' ', $key));
    $statusChartValues[] = (int) $status->total;
    $statusChartColors[] = $statusColors[$key] ?? '#94a3b8';
}

function mobileDashboardInitials(?string $name): string
{
    $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY);
    $initials = '';
    foreach (array_slice($words ?: [], 0, 2) as $word) {
        $initials .= mb_substr($word, 0, 1, 'UTF-8');
    }
    return $initials !== '' ? mb_strtoupper($initials, 'UTF-8') : '?';
}
?>

<style>
.system-mobile-dashboard{padding-bottom:1rem}
.system-mobile-dashboard .hero{border:0;border-radius:20px;background:linear-gradient(135deg,#206bc4 0%,#164b87 100%);box-shadow:0 14px 30px rgba(32,107,196,.22);overflow:hidden;position:relative}
.system-mobile-dashboard .hero::after{content:"";position:absolute;width:150px;height:150px;border-radius:50%;right:-55px;top:-70px;background:rgba(255,255,255,.1)}
.system-mobile-dashboard .metric{border:0;border-radius:16px;box-shadow:0 2px 12px rgba(15,23,42,.05)}
.system-mobile-dashboard .metric .card-body{padding:1rem}
.system-mobile-dashboard .metric-icon{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;font-size:1.15rem}
.system-mobile-dashboard .metric-value{font-size:1.35rem;font-weight:750;line-height:1;letter-spacing:-.04em}
.system-mobile-dashboard .metric-label{font-size:.69rem;color:var(--tblr-secondary);margin-top:.35rem;white-space:nowrap}
.system-mobile-dashboard .dashboard-card{border:0;border-radius:18px;box-shadow:0 2px 14px rgba(15,23,42,.055);overflow:hidden}
.system-mobile-dashboard .dashboard-card .card-header{min-height:auto;padding:1rem;border-bottom:1px solid var(--tblr-border-color)}
.system-mobile-dashboard .dashboard-card .card-body{padding:1rem}
.system-mobile-dashboard .section-title{font-size:.95rem;font-weight:700;margin:0}
.system-mobile-dashboard .section-subtitle{font-size:.68rem;color:var(--tblr-secondary);margin-top:.15rem}
.system-mobile-dashboard .quick-action{border:1px solid var(--tblr-border-color);border-radius:15px;padding:.85rem .5rem;text-align:center;text-decoration:none;background:var(--tblr-bg-surface)}
.system-mobile-dashboard .quick-action .avatar{margin:0 auto .5rem}
.system-mobile-dashboard .quick-action span:last-child{font-size:.68rem;font-weight:600;color:var(--tblr-body-color);display:block}
.system-mobile-dashboard .subscriber-row+.subscriber-row,.system-mobile-dashboard .activity-row+.activity-row{border-top:1px solid var(--tblr-border-color)}
.system-mobile-dashboard .subscriber-row,.system-mobile-dashboard .activity-row{padding:.8rem 0}
.system-mobile-dashboard .subscriber-row:first-child,.system-mobile-dashboard .activity-row:first-child{padding-top:0}
.system-mobile-dashboard .subscriber-row:last-child,.system-mobile-dashboard .activity-row:last-child{padding-bottom:0}
.system-mobile-dashboard .clamp{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
.system-mobile-dashboard .security-event{border-left:3px solid var(--tblr-danger);padding-left:.75rem}
</style>

<div class="container-xl py-3 system-mobile-dashboard">
  <div class="card hero text-white mb-3">
    <div class="card-body p-4 position-relative">
      <div class="d-flex align-items-center gap-3">
        <span class="avatar bg-white-lt text-white"><i class="ti ti-shield-lock fs-2"></i></span>
        <div>
          <div class="text-uppercase small opacity-75 fw-semibold">Superadmin</div>
          <div class="fw-bold fs-2">Sistem Yönetimi</div>
          <div class="opacity-75 small">Platformun anlık durum özeti</div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-2 mb-3">
    <?php
    $metrics = [
        ['total_subscribers', 'Toplam abone', 'users-group', 'blue'],
        ['active_subscriptions', 'Aktif abonelik', 'rosette-discount-check', 'green'],
        ['total_firms', 'Kayıtlı firma', 'building-community', 'purple'],
        ['active_users', 'Aktif kullanıcı', 'user-check', 'azure'],
        ['expiring_subscriptions', '7 günde bitecek', 'clock-exclamation', 'yellow'],
        ['trial_subscribers', 'Deneme hesabı', 'hourglass', 'cyan'],
        ['activities_today', 'Bugünkü işlem', 'bolt', 'orange'],
        ['users_logged_in_today', 'Bugün giriş', 'login-2', 'indigo'],
    ];
    foreach ($metrics as [$property, $label, $icon, $color]):
    ?>
      <div class="col-6">
        <div class="card metric h-100">
          <div class="card-body d-flex align-items-center gap-3">
            <span class="metric-icon bg-<?= $color ?>-lt text-<?= $color ?>"><i class="ti ti-<?= $icon ?>"></i></span>
            <div class="min-w-0">
              <div class="metric-value"><?= number_format((int) ($summary->{$property} ?? 0), 0, ',', '.') ?></div>
              <div class="metric-label text-truncate"><?= $label ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card dashboard-card mb-3">
    <div class="card-header">
      <div>
        <h3 class="section-title">Abone ve abonelik gelişimi</h3>
        <div class="section-subtitle">Son 6 aylık yeni kayıtlar</div>
      </div>
    </div>
    <div class="card-body">
      <div id="mobile-subscription-trend" style="min-height:245px"></div>
    </div>
  </div>

  <div class="card dashboard-card mb-3">
    <div class="card-header">
      <div>
        <h3 class="section-title">Abonelik durumları</h3>
        <div class="section-subtitle">Abonelerin son paket kaydı</div>
      </div>
    </div>
    <div class="card-body">
      <div id="mobile-subscription-status" style="min-height:235px"></div>
    </div>
  </div>

  <div class="mb-3">
    <div class="small text-uppercase text-muted fw-semibold mb-2 px-1">Hızlı İşlemler</div>
    <div class="row g-2">
      <div class="col-3"><a class="quick-action d-block h-100" href="abonelik-islemleri"><span class="avatar avatar-sm bg-indigo-lt text-indigo"><i class="ti ti-crown"></i></span><span>Abonelik</span></a></div>
      <div class="col-3"><a class="quick-action d-block h-100" href="tickets"><span class="avatar avatar-sm bg-blue-lt text-blue"><i class="ti ti-headset"></i></span><span>Destek</span></a></div>
      <div class="col-3"><a class="quick-action d-block h-100" href="notifications"><span class="avatar avatar-sm bg-orange-lt text-orange"><i class="ti ti-bell"></i></span><span>Bildirim</span></a></div>
      <div class="col-3"><a class="quick-action d-block h-100" href="settings"><span class="avatar avatar-sm bg-teal-lt text-teal"><i class="ti ti-settings"></i></span><span>Ayarlar</span></a></div>
    </div>
  </div>

  <div class="card dashboard-card mb-3">
    <div class="card-header d-flex align-items-center">
      <div class="flex-fill">
        <h3 class="section-title">Son abone olanlar</h3>
        <div class="section-subtitle">En yeni ana hesaplar</div>
      </div>
      <a href="abonelik-islemleri" class="btn btn-sm btn-outline-primary">Tümü</a>
    </div>
    <div class="card-body">
      <?php if ($recentSubscribers): foreach ($recentSubscribers as $subscriber): ?>
        <?php
        $subscriptionStatus = (string) ($subscriber->subscription_status ?? '');
        $isTrial = !$subscriptionStatus && (int) $subscriber->status === 1
            && strtotime($subscriber->created_at) >= strtotime('-15 days');
        ?>
        <div class="subscriber-row d-flex align-items-center gap-2">
          <span class="avatar avatar-sm bg-blue-lt text-blue"><?= htmlspecialchars(mobileDashboardInitials($subscriber->full_name)) ?></span>
          <div class="flex-fill min-w-0">
            <div class="fw-semibold text-truncate"><?= htmlspecialchars($subscriber->full_name) ?></div>
            <div class="text-muted small text-truncate"><?= htmlspecialchars($subscriber->package_name ?? ($isTrial ? '15 Günlük Deneme' : 'Paket yok')) ?></div>
          </div>
          <div class="text-end">
            <?php if ($isTrial): ?>
              <span class="badge bg-cyan-lt text-cyan">Deneme</span>
            <?php elseif ($subscriptionStatus): ?>
              <span class="badge <?= $statusClasses[$subscriptionStatus] ?? 'bg-secondary-lt' ?>"><?= htmlspecialchars($statusLabels[$subscriptionStatus] ?? $subscriptionStatus) ?></span>
            <?php else: ?>
              <span class="badge bg-secondary-lt">Paket yok</span>
            <?php endif; ?>
            <div class="text-muted mt-1" style="font-size:.65rem"><?= date('d.m.Y', strtotime($subscriber->created_at)) ?></div>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="text-center text-muted py-3">Henüz abone kaydı bulunmuyor.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card dashboard-card mb-3">
    <div class="card-header">
      <div>
        <h3 class="section-title">Son sistem aktiviteleri</h3>
        <div class="section-subtitle">Platform genelindeki son işlemler</div>
      </div>
    </div>
    <div class="card-body">
      <?php if ($recentActivities): foreach ($recentActivities as $activity): ?>
        <div class="activity-row d-flex gap-2">
          <span class="avatar avatar-sm bg-blue-lt text-blue"><i class="ti ti-activity"></i></span>
          <div class="flex-fill min-w-0">
            <div class="small clamp"><?= htmlspecialchars($activity->description) ?></div>
            <div class="text-muted mt-1" style="font-size:.65rem">
              <?= htmlspecialchars($activity->user_name ?? 'Sistem') ?>
              <?php if (!empty($activity->firm_name)): ?> · <?= htmlspecialchars($activity->firm_name) ?><?php endif; ?>
              · <?= date('d.m H:i', strtotime($activity->created_at)) ?>
            </div>
          </div>
        </div>
      <?php endforeach; else: ?>
        <div class="text-center text-muted py-3">Aktivite kaydı bulunmuyor.</div>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($securityEvents): ?>
    <div class="card dashboard-card">
      <div class="card-header">
        <div>
          <h3 class="section-title">Güvenlik olayları</h3>
          <div class="section-subtitle">Son kritik oturum hareketleri</div>
        </div>
      </div>
      <div class="card-body">
        <?php foreach ($securityEvents as $event): ?>
          <div class="activity-row security-event">
            <div class="small fw-semibold"><?= htmlspecialchars($event->description ?: $event->event_type) ?></div>
            <div class="text-muted mt-1" style="font-size:.65rem">
              <?= htmlspecialchars($event->user_name ?? 'Bilinmeyen kullanıcı') ?> ·
              <?= htmlspecialchars($event->ip_address ?? 'IP yok') ?> ·
              <?= date('d.m H:i', strtotime($event->created_at)) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script src="../dist/libs/apexcharts/dist/apexcharts.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  if (typeof ApexCharts === 'undefined') return;
  const isDark = document.body.dataset.bsTheme === 'dark';
  const textColor = isDark ? '#aeb7c4' : '#667382';
  const gridColor = isDark ? 'rgba(255,255,255,.08)' : 'rgba(98,105,118,.12)';

  new ApexCharts(document.querySelector('#mobile-subscription-trend'), {
    chart: {type: 'area', height: 245, toolbar: {show: false}, zoom: {enabled: false}, fontFamily: 'inherit'},
    series: [
      {name: 'Yeni aboneler', data: <?= json_encode($monthlyTrend['subscribers'], JSON_UNESCAPED_UNICODE) ?>},
      {name: 'Yeni abonelikler', data: <?= json_encode($monthlyTrend['subscriptions'], JSON_UNESCAPED_UNICODE) ?>}
    ],
    colors: ['#206bc4', '#2fb344'],
    stroke: {curve: 'smooth', width: 3},
    fill: {type: 'gradient', gradient: {opacityFrom: .3, opacityTo: .03}},
    dataLabels: {enabled: false},
    xaxis: {categories: <?= json_encode($monthlyTrend['labels'], JSON_UNESCAPED_UNICODE) ?>, labels: {style: {colors: textColor, fontSize: '10px'}}},
    yaxis: {min: 0, forceNiceScale: true, labels: {style: {colors: textColor, fontSize: '10px'}}},
    grid: {borderColor: gridColor, strokeDashArray: 4},
    legend: {position: 'top', horizontalAlign: 'left', fontSize: '11px', labels: {colors: textColor}},
    tooltip: {theme: isDark ? 'dark' : 'light'}
  }).render();

  const statusValues = <?= json_encode($statusChartValues) ?>;
  const statusElement = document.querySelector('#mobile-subscription-status');
  if (statusValues.length && statusValues.some(value => value > 0)) {
    new ApexCharts(statusElement, {
      chart: {type: 'donut', height: 235, fontFamily: 'inherit'},
      series: statusValues,
      labels: <?= json_encode($statusChartLabels, JSON_UNESCAPED_UNICODE) ?>,
      colors: <?= json_encode($statusChartColors) ?>,
      stroke: {width: 2},
      dataLabels: {enabled: false},
      legend: {position: 'bottom', fontSize: '11px', labels: {colors: textColor}},
      plotOptions: {pie: {donut: {size: '68%', labels: {show: true, total: {show: true, label: 'Toplam'}}}}},
      tooltip: {theme: isDark ? 'dark' : 'light'}
    }).render();
  } else {
    statusElement.innerHTML = '<div class="text-center text-muted py-5">Abonelik durum verisi bulunmuyor.</div>';
  }
});
</script>
