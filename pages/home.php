<?php
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/Model/CaseTransactions.php";
require_once ROOT . "/Model/AdvanceRequest.php";
require_once ROOT . "/Model/IzinTalep.php";
require_once ROOT . "/Model/GorevModel.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;
use App\Helper\Security;

$personObj = new Persons();
$projectObj = new Projects();
$caseTransObj = new CaseTransactions();
$advanceModel = new AdvanceRequest();
$izinTalepModel = new IzinTalep();
$gorevModel = new GorevModel();

$firm_id = $_SESSION['firm_id'];

// İstatistikleri çek
$personStats = $personObj->getPersonnelStats($firm_id);
$totalPersons = $personStats['total'] ?? 0;
$activePersons = $personStats['active'] ?? 0;
$passivePersons = $personStats['passive'] ?? 0;

$projSummary = $projectObj->getProjectStatusSummary($firm_id);
$totalProjects = $projSummary['total'] ?? 0;
$activeProjects = $projSummary['active'] ?? 0;
$completedProjects = $projSummary['completed'] ?? 0;

$balances = $caseTransObj->getFirmBalance($firm_id);
$totalIncome = $balances->total_income ?? 0;
$totalExpense = $balances->total_expense ?? 0;
$netBalance = $totalIncome - $totalExpense;

// Bekleyen Operasyonlar
$pendingAdvances = $advanceModel->getPendingRequestsByFirm($firm_id);
$pendingAdvancesCount = count($pendingAdvances);
$pendingAdvancesTotal = array_sum(array_map(function($a) { return (float)($a->tutar ?? 0); }, $pendingAdvances));

$pendingLeavesCount = $izinTalepModel->getBekleyenSayisi($firm_id);
$todayLeaves = $izinTalepModel->getBugunIzinliler($firm_id);
$todayLeaveCount = count($todayLeaves);

$upcomingTasks = $gorevModel->getYaklasanGorevler($firm_id, 50);
$pendingTasksCount = count($upcomingTasks);

$aylarTr = ['01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan', '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos', '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'];
$gunlerTr = ['Sunday' => 'Pazar', 'Monday' => 'Pazartesi', 'Tuesday' => 'Salı', 'Wednesday' => 'Çarşamba', 'Thursday' => 'Perşembe', 'Friday' => 'Cuma', 'Saturday' => 'Cumartesi'];
$todayFormatted = date('d') . ' ' . ($aylarTr[date('m')] ?? date('m')) . ' ' . date('Y') . ', ' . ($gunlerTr[date('l')] ?? date('l'));
$userName = $_SESSION['user']->full_name ?? 'Yönetici';
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

    /* 4 KPI Summary Cards (Equal Size & Height) */
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
    #widgets-sortable .card,
    #quick-actions-sortable .card {
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07), 0 1px 3px rgba(15, 23, 42, 0.04) !important;
        border: 1px solid #dbe3ec !important;
        border-radius: 12px !important;
        transition: box-shadow 0.25s ease, transform 0.25s ease;
        background-color: #ffffff;
    }
    #widgets-sortable .card:hover,
    #quick-actions-sortable .card:hover {
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.11), 0 2px 6px rgba(15, 23, 42, 0.05) !important;
    }
    [data-bs-theme="dark"] #widgets-sortable .card,
    [data-bs-theme="dark"] #quick-actions-sortable .card {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.35) !important;
        border: 1px solid rgba(255, 255, 255, 0.08) !important;
        background-color: #1a2234;
    }

    /* Mac Style Titlebar for Dashboard Widgets */
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
    .minimized-card .card-body {
        display: none !important;
    }
</style>

<div class="page-wrapper">
    <!-- Hero / Greeting Header (Reference Layout) -->
    <div class="page-header d-print-none pb-2">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <!-- Left: Greeting & Date & Widget dropdown -->
                <div class="col">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-white text-secondary border px-2 py-1 fw-medium" style="font-size: 11px; border-radius: 6px;">
                            <i class="ti ti-calendar me-1 text-primary"></i><?php echo $todayFormatted; ?>
                        </span>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-white border dropdown-toggle px-2 py-1 text-secondary" data-bs-toggle="dropdown" style="font-size: 11px; border-radius: 6px;">
                                <i class="ti ti-layout-grid me-1 text-primary"></i> Widget İşlemleri
                            </button>
                            <div class="dropdown-menu shadow-sm p-3" id="dashboard-colvis-menu" style="min-width: 260px; max-height: 380px; overflow-y: auto;">
                                <!-- Dynamic checkboxes loaded via JS -->
                            </div>
                        </div>
                        <button class="btn btn-sm btn-white border px-2 py-1 text-secondary" onclick="resetDashboardLayout()" style="font-size: 11px; border-radius: 6px;" title="Varsayılan Düzen">
                            <i class="ti ti-rotate-clockwise me-1 text-primary"></i> Sıfırla
                        </button>
                    </div>
                    <h2 class="page-title fw-bold text-dark mb-1" style="font-size: 1.4rem; letter-spacing: -0.4px;">
                        Hoş Geldiniz, <?php echo htmlspecialchars($userName); ?> 👋
                    </h2>
                    <div class="text-secondary small">
                        Operasyonel süreçler, personel takibi ve aktif projelerinize genel bakış.
                    </div>
                </div>

                <!-- Right: Mini Quick Action Cards -->
                <div class="col-auto ms-auto d-none d-md-block">
                    <div class="d-flex gap-2">
                        <?php if ($perm->hasPermission('personnel_add_update')): ?>
                        <a href="/personel-ekle" class="quick-nav-card text-decoration-none">
                            <i class="ti ti-user-plus text-primary"></i>
                            <span>Yeni Personel</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($perm->hasPermission('project_add_update')): ?>
                        <a href="/proje-ekle" class="quick-nav-card text-decoration-none">
                            <i class="ti ti-plus text-success"></i>
                            <span>Yeni Proje</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($perm->hasPermission('company_page')): ?>
                        <a href="/firmalar#new" class="quick-nav-card text-decoration-none">
                            <i class="ti ti-building text-warning"></i>
                            <span>Yeni Firma</span>
                        </a>
                        <?php endif; ?>
                        <?php if ($perm->hasPermission('gorevler')): ?>
                        <a href="/gorevler" class="quick-nav-card text-decoration-none">
                            <i class="ti ti-checkbox text-azure"></i>
                            <span>Görev Ekle</span>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body mt-2">
        <div class="container-xl">
            <!-- 4 Equal Size & Height KPI Cards Row -->
            <div class="row row-cards g-3 mb-3" id="stats-sortable">
                <!-- 1. Personel -->
                <?php if ($perm->hasPermission('personnel_page')): ?>
                <div class="col-sm-6 col-xl-3" data-id="stat-personel">
                    <div class="stat-kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="stat-kpi-title">AKTİF PERSONELLER</span>
                            <span class="avatar avatar-sm rounded-2 bg-blue-lt text-primary">
                                <i class="ti ti-users" style="font-size: 18px;"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="stat-kpi-value"><?php echo number_format($totalPersons, 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between stat-kpi-footer">
                            <div class="d-flex gap-1">
                                <span class="badge bg-warning-lt" style="font-size: 10px; font-weight: 600;">Aktif: <?php echo $activePersons; ?></span>
                                <?php if ($passivePersons > 0): ?>
                                    <span class="badge bg-secondary-lt" style="font-size: 10px;">Pasif: <?php echo $passivePersons; ?></span>
                                <?php endif; ?>
                            </div>
                            <a href="/personeller" class="text-primary small text-decoration-none fw-semibold" style="font-size: 11px;">
                                Tümü <i class="ti ti-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 2. Proje -->
                <?php if ($perm->hasPermission('project_page') || $perm->hasPermission('project_add_update')): ?>
                <div class="col-sm-6 col-xl-3" data-id="stat-proje">
                    <div class="stat-kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="stat-kpi-title">AKTİF PROJELER</span>
                            <span class="avatar avatar-sm rounded-2 bg-warning-lt text-warning">
                                <i class="ti ti-buildings" style="font-size: 18px;"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="stat-kpi-value"><?php echo number_format($totalProjects, 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between stat-kpi-footer">
                            <div class="d-flex gap-1">
                                <span class="badge bg-warning-lt" style="font-size: 10px; font-weight: 600;">Devam: <?php echo $activeProjects; ?></span>
                                <span class="badge bg-success-lt" style="font-size: 10px; font-weight: 600;">Biten: <?php echo $completedProjects; ?></span>
                            </div>
                            <a href="/projeler" class="text-warning small text-decoration-none fw-semibold" style="font-size: 11px;">
                                Projeler <i class="ti ti-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 3. Kasa -->
                <?php if ($perm->hasPermission('income_expense_operations')): ?>
                <div class="col-sm-6 col-xl-3" data-id="stat-kasa">
                    <div class="stat-kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="stat-kpi-title">NET KASA BAKİYESİ</span>
                            <span class="avatar avatar-sm rounded-2 bg-success-lt text-success">
                                <i class="ti ti-wallet" style="font-size: 18px;"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="stat-kpi-value <?php echo $netBalance >= 0 ? 'text-dark' : 'text-danger'; ?> text-truncate" title="<?php echo Helper::formattedMoney($netBalance); ?> ₺">
                                <?php echo Helper::formattedMoney($netBalance); ?> ₺
                            </span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between stat-kpi-footer">
                            <div class="d-flex align-items-center text-muted" style="font-size: 11px;">
                                <span class="text-success fw-semibold"><i class="ti ti-arrow-up"></i> <?php echo Helper::formattedMoney($totalIncome); ?></span>
                            </div>
                            <span class="badge <?php echo $netBalance >= 0 ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger'; ?>" style="font-size: 10px; font-weight: 600;">
                                <?php echo $netBalance >= 0 ? 'Net Artı' : 'Net Eksi'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 4. Bekleyen İşlemler -->
                <div class="col-sm-6 col-xl-3" data-id="stat-operasyon">
                    <div class="stat-kpi-card p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="stat-kpi-title">BEKLEYEN İŞLEMLER</span>
                            <span class="avatar avatar-sm rounded-2 bg-purple-lt text-purple">
                                <i class="ti ti-bell-ringing" style="font-size: 18px;"></i>
                            </span>
                        </div>
                        <div class="d-flex align-items-baseline mb-2">
                            <span class="stat-kpi-value"><?php echo ($pendingAdvancesCount + $pendingLeavesCount + $pendingTasksCount); ?></span>
                        </div>
                        <div class="d-flex align-items-center justify-content-between stat-kpi-footer">
                            <div class="d-flex gap-1" style="font-size: 11px;">
                                <span class="badge bg-orange-lt" style="font-size: 10px;">Avans: <?php echo $pendingAdvancesCount; ?></span>
                                <span class="badge bg-warning-lt" style="font-size: 10px;">İzin: <?php echo $pendingLeavesCount; ?></span>
                            </div>
                            <a href="/avans-talepleri" class="badge bg-purple-lt text-purple text-decoration-none" style="font-size: 10px;">
                                İşlemler
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widgets Row -->
            <div class="row row-cards mt-3" id="widgets-sortable">
                <!-- Finansal Analiz & Nakit Akışı Grafiği -->
                <?php include_once "home/finance_chart.php" ?>

                <!-- Proje Durum Dağılımı -->
                <?php include_once "home/project_overview.php" ?>

                <!-- İK & Personel Bakışı -->
                <?php include_once "home/personnel_summary.php" ?>

                <!-- Proje Gantt Şeması -->
                <?php include_once "home/project_gantt.php" ?>

                <!-- Yaklaşan Görevler -->
                <?php include_once "home/gorevler.php" ?>

                <!-- Bekleyen Avans Talepleri -->
                <?php include_once "home/avans_talepleri.php" ?>

                <!-- Yıllık İzin Özeti -->
                <?php include_once "home/izin_widget.php" ?>

                <!-- Son Aktiviteler -->
                <?php include_once "home/activity_logs.php" ?>

                <!-- Son Giriş Kayıtları -->
                <?php include_once "home/login_logs.php" ?>
            </div>
        </div>
    </div>
</div>

<!-- Sortable JS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const layoutKey = 'dashboard_layout_v3';
    const cardSelector = '#stats-sortable > [data-id], #widgets-sortable > [data-id]';

    function saveLayout() {
        const layout = {
            'stats-sortable': Array.from(document.getElementById('stats-sortable')?.children || []).map(c => c.getAttribute('data-id')).filter(Boolean),
            'widgets-sortable': Array.from(document.getElementById('widgets-sortable')?.children || []).map(c => c.getAttribute('data-id')).filter(Boolean)
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
            if (id && localStorage.getItem('card_hidden_' + id) === '1') {
                el.style.display = 'none';
            }
        });
    }

    // İlk olarak layout'u eski haline getir
    restoreLayout();

    function initCardVisibilityDropdown() {
        const colvisMenu = document.getElementById('dashboard-colvis-menu');
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
                if (id === 'stat-personel') title = 'PERSONEL ÖZETİ';
                else if (id === 'stat-proje') title = 'PROJE ÖZETİ';
                else if (id === 'stat-kasa') title = 'NET KASA BAKİYESİ';
                else if (id === 'stat-operasyon') title = 'BEKLEYEN İŞLEMLER';
                else if (id === 'widget-finance-chart') title = 'FİNANSAL ANALİZ GRAFİĞİ';
                else if (id === 'widget-project-overview') title = 'PROJE DURUM DAĞILIMI';
                else if (id === 'widget-personnel-summary') title = 'İK & PERSONEL BAKIŞI';
                else if (id === 'widget-project-gantt') title = 'PROJE GANTT ŞEMASI';
                else if (id === 'widget-gorevler') title = 'YAKLAŞAN GÖREVLER';
                else if (id === 'widget-avans-talepleri') title = 'AVANS TALEPLERİ';
                else if (id === 'widget-izin') title = 'YILLIK İZİN ÖZETİ';
                else if (id === 'widget-activity-logs') title = 'SON AKTİVİTELER';
                else if (id === 'widget-login-logs') title = 'SON GİRİŞ KAYITLARI';
                else title = id.replace('widget-', '').replace('stat-', '').toUpperCase();
            }

            cards.push({ id, title, element: el });
        });

        cards.forEach(card => {
            const isChecked = localStorage.getItem('card_hidden_' + card.id) !== '1';
            
            const div = document.createElement('div');
            div.className = 'form-check mb-2';
            
            const chk = document.createElement('input');
            chk.className = 'form-check-input dashboard-colvis-chk';
            chk.type = 'checkbox';
            chk.id = 'chk-colvis-' + card.id;
            chk.checked = isChecked;
            
            const label = document.createElement('label');
            label.className = 'form-check-label text-nowrap fw-medium';
            label.htmlFor = chk.id;
            label.textContent = card.title;
            
            chk.addEventListener('change', function() {
                if (this.checked) {
                    localStorage.removeItem('card_hidden_' + card.id);
                    card.element.style.display = '';
                } else {
                    localStorage.setItem('card_hidden_' + card.id, '1');
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
        
        var sortable = Sortable.create(el, {
            group: 'dashboard-widgets',
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onSort: function (evt) {
                saveLayout();
            }
        });
    }

    // Initialize sortables for widgets section
    initSortable('widgets-sortable');

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
                localStorage.setItem('card_size_' + id, JSON.stringify(dimensions));

                wrapper.style.width = 'fit-content';
                wrapper.style.flex = '0 0 auto';
            }
        }
    });

    document.querySelectorAll('#widgets-sortable .card').forEach(function(card) {
        var wrapper = card.closest(cardSelector);
        if (wrapper) {
            card.classList.add('resizable-card');
            
            // Restore saved size
            var id = wrapper.getAttribute('data-id');
            var saved = localStorage.getItem('card_size_' + id);
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
            
            // Restore minimized state
            var isMin = localStorage.getItem('card_min_' + id);
            if (isMin === '1') {
                card.classList.add('minimized-card');
            }
            
            // Observe for future resizes
            resizeObserver.observe(card);
        }
    });

    // Mac Buttons İşlevselliği
    document.addEventListener('click', function(e) {
        // Kapatma
        if (e.target.classList.contains('mac-close')) {
            let cardWrap = e.target.closest('[data-id]');
            if (cardWrap) {
                cardWrap.style.display = 'none';
                let id = cardWrap.getAttribute('data-id');
                if (id) {
                    localStorage.setItem('card_hidden_' + id, '1');
                    let chk = document.getElementById('chk-colvis-' + id);
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
                        localStorage.setItem('card_min_' + id, '1');
                    } else {
                        localStorage.removeItem('card_min_' + id);
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

    window.resetDashboardLayout = function() {
        if(confirm("Pano düzenini ve boyutlarını sıfırlamak istediğinize emin misiniz?")) {
            localStorage.removeItem(layoutKey);
            localStorage.removeItem('dashboard_layout_v2');
            document.querySelectorAll(cardSelector).forEach(function(el) {
                var id = el.getAttribute('data-id');
                localStorage.removeItem('card_size_' + id);
                localStorage.removeItem('card_min_' + id);
                localStorage.removeItem('card_hidden_' + id);
            });
            window.location.reload();
        }
    };
});
</script>
<?php include_once ROOT . "/pages/projects/modals/task-modal.php"; ?>