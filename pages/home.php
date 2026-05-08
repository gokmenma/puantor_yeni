<?php
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/Model/CaseTransactions.php";

$personObj = new Persons();
$projectObj = new Projects();
$caseTransObj = new CaseTransactions();

$firm_id = $_SESSION['firm_id'];

// İstatistikleri çek
$totalPersons = count($personObj->getPersonsByFirm($firm_id));
$totalProjects = count($projectObj->getProjectsByFirm($firm_id));
$balances = $caseTransObj->getFirmBalance($firm_id);

$totalIncome = $balances->total_income ?? 0;
$totalExpense = $balances->total_expense ?? 0;

require_once ROOT . "/App/Helper/helper.php";
use App\Helper\Helper;
?>

<style>
    .mac-titlebar {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        background: #fcfcfc;
        border-bottom: 1px solid #f0f0f0;
        border-top-left-radius: 4px;
        border-top-right-radius: 4px;
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
    .drag-handle {
        cursor: grab;
    }
    .drag-handle:active {
        cursor: grabbing;
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
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <!-- Page pre-title -->
                    <div class="page-pretitle">
                        Genel Bakış
                    </div>
                    <h2 class="page-title">
                        Özet Bilgiler kontrol
                    </h2>
                </div>
                <!-- Action Buttons Here -->
                <div class="col-auto ms-auto d-print-none">
                    <button class="btn btn-outline-secondary" onclick="resetDashboardLayout()">
                        <i class="ti ti-rotate-clockwise mb-0 me-2"></i> Varsayılana Dön
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <!-- Stats Cards Row -->
            <div class="row row-deck row-cards" id="stats-sortable">
                <div class="col-md-6 col-xl-3" data-id="stat-personel">
                    <div class="card card-sm">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <div class="mac-btn mac-close"></div>
                                <div class="mac-btn mac-min"></div>
                                <div class="mac-btn mac-max"></div>
                            </div>
                            <span class="mac-title">PERSONEL</span>
                            <i class="ti ti-grid-dots drag-handle ms-auto text-muted"></i>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-primary text-white avatar">
                                        <i class="ti ti-users"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium"><?php echo number_format($totalPersons, 0, ',', '.'); ?></div>
                                    <div class="text-secondary">Toplam Personel</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3" data-id="stat-proje">
                    <div class="card card-sm">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <div class="mac-btn mac-close"></div>
                                <div class="mac-btn mac-min"></div>
                                <div class="mac-btn mac-max"></div>
                            </div>
                            <span class="mac-title">PROJE</span>
                            <i class="ti ti-grid-dots drag-handle ms-auto text-muted"></i>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-warning text-white avatar">
                                        <i class="ti ti-buildings"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium"><?php echo number_format($totalProjects, 0, ',', '.'); ?></div>
                                    <div class="text-secondary">Toplam Proje</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3" data-id="stat-gelir">
                    <div class="card card-sm">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <div class="mac-btn mac-close"></div>
                                <div class="mac-btn mac-min"></div>
                                <div class="mac-btn mac-max"></div>
                            </div>
                            <span class="mac-title">GELİR</span>
                            <i class="ti ti-grid-dots drag-handle ms-auto text-muted"></i>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-success text-white avatar">
                                        <i class="ti ti-download"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium"><?php echo Helper::formattedMoney($totalIncome); ?> ₺</div>
                                    <div class="text-secondary">Toplam Gelir</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3" data-id="stat-gider">
                    <div class="card card-sm">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <div class="mac-btn mac-close"></div>
                                <div class="mac-btn mac-min"></div>
                                <div class="mac-btn mac-max"></div>
                            </div>
                            <span class="mac-title">GİDER</span>
                            <i class="ti ti-grid-dots drag-handle ms-auto text-muted"></i>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="bg-danger text-white avatar">
                                        <i class="ti ti-upload"></i>
                                    </span>
                                </div>
                                <div class="col">
                                    <div class="font-weight-medium"><?php echo Helper::formattedMoney($totalExpense); ?> ₺</div>
                                    <div class="text-secondary">Toplam Gider</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Header -->
            <div class="row g-2 align-items-center mt-4">
                <div class="col">
                    <h2 class="page-title">
                        Hızlı İşlemler
                    </h2>
                </div>
            </div>

            <!-- Quick Actions Buttons -->
            <div class="row row-cards mt-2" id="quick-actions-sortable">
                <div class="col-12" data-id="quick-actions">
                    <div class="card resizable-card">
                        <div class="mac-titlebar">
                            <div class="mac-buttons">
                                <div class="mac-btn mac-close"></div>
                                <div class="mac-btn mac-min"></div>
                                <div class="mac-btn mac-max"></div>
                            </div>
                            <span class="mac-title">İŞLEMLER</span>
                            <i class="ti ti-grid-dots drag-handle ms-auto text-muted"></i>
                        </div>
                        <div class="card-body">
                            <div class="btn-list">
                                <a href="index.php?p=persons/manage" class="btn btn-outline-primary p-3 d-flex flex-column align-items-center" style="min-width: 120px;">
                                    <i class="ti ti-user-plus mb-1" style="font-size: 20px;"></i>
                                    <span>Personel Ekle</span>
                                </a>
                                <a href="index.php?p=projects/manage" class="btn btn-outline-primary p-3 d-flex flex-column align-items-center" style="min-width: 120px;">
                                    <i class="ti ti-building-plus mb-1" style="font-size: 20px;"></i>
                                    <span>Proje Ekle</span>
                                </a>
                                <a href="index.php?p=companies/manage" class="btn btn-outline-primary p-3 d-flex flex-column align-items-center" style="min-width: 120px;">
                                    <i class="ti ti-building-store mb-1" style="font-size: 20px;"></i>
                                    <span>Firma Ekle</span>
                                </a>
                                <a href="index.php?p=financial/transactions/list" class="btn btn-outline-success p-3 d-flex flex-column align-items-center" style="min-width: 120px;">
                                    <i class="ti ti-trending-up mb-1" style="font-size: 20px;"></i>
                                    <span>Gelir Ekle</span>
                                </a>
                                <a href="index.php?p=financial/transactions/list" class="btn btn-outline-danger p-3 d-flex flex-column align-items-center" style="min-width: 120px;">
                                    <i class="ti ti-trending-down mb-1" style="font-size: 20px;"></i>
                                    <span>Gider Ekle</span>
                                </a>
                                <a href="index.php?p=gorevler/list" class="btn btn-outline-primary p-3 d-flex flex-column align-items-center" style="min-width: 120px;">
                                    <i class="ti ti-plus mb-1" style="font-size: 20px;"></i>
                                    <span>Görev Ekle</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Widgets Row -->
            <div class="row row-cards mt-3" id="widgets-sortable">
                <?php include_once "home/gorevler.php" ?>
                <?php include_once "home/activity_logs.php" ?>
                <?php include_once "home/login_logs.php" ?>
            </div>
        </div>
    </div>
</div>

<!-- Sortable JS -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const layoutKey = 'dashboard_layout_v2';

    function saveLayout() {
        const layout = {
            'stats-sortable': Array.from(document.getElementById('stats-sortable')?.children || []).map(c => c.getAttribute('data-id')).filter(Boolean),
            'quick-actions-sortable': Array.from(document.getElementById('quick-actions-sortable')?.children || []).map(c => c.getAttribute('data-id')).filter(Boolean),
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
    }

    // İlk olarak layout'u eski haline getir
    restoreLayout();

    function initSortable(containerId) {
        var el = document.getElementById(containerId);
        if (!el) return;
        
        var sortable = Sortable.create(el, {
            group: 'dashboard-cards', // Bütün kartların birbiri arasına taşınabilmesi için
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            onSort: function (evt) {
                saveLayout();
            }
        });
    }

    // Initialize sortables for each section
    initSortable('stats-sortable');
    initSortable('quick-actions-sortable');
    initSortable('widgets-sortable');

    // Make all cards resizable and handle size persistence
    var resizeObserver = new ResizeObserver(function(entries) {
        for (var i = 0; i < entries.length; i++) {
            var entry = entries[i];
            var card = entry.target;
            var wrapper = card.closest('[data-id]');
            if (!wrapper) continue;

            // Eğer CSS resize ile kullanıcı manuel boyutlandırmışsa inline style atanır
            if (card.style.width || card.style.height) {
                var id = wrapper.getAttribute('data-id');
                var dimensions = {
                    width: card.style.width,
                    height: card.style.height
                };
                localStorage.setItem('card_size_' + id, JSON.stringify(dimensions));

                // fit-content ile Bootstrap gutter (padding) korunur, aralarda boşluk kalır
                wrapper.style.width = 'fit-content';
                wrapper.style.flex = '0 0 auto';
            }
        }
    });

    document.querySelectorAll('.card').forEach(function(card) {
        var wrapper = card.closest('[data-id]');
        if (wrapper) {
            // Add resizable class
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
            let cardWrap = e.target.closest('[class*="col-"]');
            if (cardWrap) {
                cardWrap.style.display = 'none';
                // Opsiyonel: Kapatılanları local storage'a kaydedebiliriz ama şimdilik sadece gizliyoruz
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
            document.querySelectorAll('[data-id]').forEach(function(el) {
                var id = el.getAttribute('data-id');
                localStorage.removeItem('card_size_' + id);
                localStorage.removeItem('card_min_' + id);
            });
            window.location.reload();
        }
    };
});
</script>