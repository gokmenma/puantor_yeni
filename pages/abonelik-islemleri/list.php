<?php
require_once "App/Helper/helper.php";
require_once "Model/AbonelerModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;

// Yetki Kontrolü
$perm->checkAuthorize("aboneler_sayfasi");

$abonelerModel = new AbonelerModel();
$subscribers = $abonelerModel->getSubscribers();

// İstatistikleri hesapla
$total_subscribers = count($subscribers);
$active_count = 0;
$passive_count = 0;
$expiring_soon_count = 0;
$today = new DateTime(date('Y-m-d'));

foreach ($subscribers as $sub) {
    $status = $sub->abonelik_durumu;
    $has_package = !empty($sub->paket_adi);
    $is_active = false;
    
    if ($has_package && $sub->bitis_tarihi) {
        $end_date = new DateTime($sub->bitis_tarihi);
        if ($today <= $end_date) {
            $interval = $today->diff($end_date);
            $days = (int)$interval->format('%r%a');
            if ($days <= 7) {
                $expiring_soon_count++;
            }
            if ($status == 'aktif') {
                $is_active = true;
            }
        }
    } elseif ($has_package && $status == 'aktif') {
        $is_active = true;
    }
    
    if ($is_active) {
        $active_count++;
    } else {
        $passive_count++;
    }
}
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'aboneler-summary-collapsed',
            localStorage.getItem('aboneler_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.aboneler-summary-collapsed #aboneSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}

.abone-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.abone-header-icon-action,
.abone-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}

.abone-header-icon-action i,
.abone-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

html:not([data-bs-theme="dark"]) #abonePage .abone-summary-card,
html:not([data-bs-theme="dark"]) .abone-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#aboneSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

html:not([data-bs-theme="dark"]) #abonePage .abone-table-card,
html:not([data-bs-theme="dark"]) .abone-table-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.abone-table-card > .abone-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.abone-table-card > .card-header {
    border-bottom: 0 !important;
}

.status-summary-filter { cursor: pointer; }
.status-summary-filter input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.status-summary-filter span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 24px;
    padding: 2px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #64748b;
    background: #fff;
    font-size: 10.5px;
    font-weight: 600;
    transition: all 0.15s ease-in-out;
}
.status-summary-filter input:checked + span {
    color: #0284c7;
    background: #e0f2fe;
    border-color: #0284c7;
}

.abone-search-wrap {
    position: relative;
    min-width: 170px;
}
.abone-search-wrap input {
    height: 32px;
    border-radius: 6px;
    font-size: 12.5px;
    padding-right: 26px;
}
.abone-search-clear {
    position: absolute;
    right: 7px;
    top: 50%;
    transform: translateY(-50%);
    background: #e2e8f0;
    border: 0;
    width: 18px;
    height: 18px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    font-size: 11px;
    cursor: pointer;
    padding: 0;
}
.abone-search-clear:hover {
    background: #cbd5e1;
    color: #1e293b;
}

table#aboneTable.dataTable,
table#aboneTable.table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    overflow: hidden !important;
}

table#aboneTable thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-size: 11.5px !important;
    font-weight: 700 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.4px !important;
    padding: 8px 10px !important;
    border-bottom: 1px solid #dbe3ec !important;
    border-right: 1px solid #edf2f7 !important;
}
table#aboneTable thead th:last-child {
    border-right: none !important;
}

table#aboneTable tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #334155 !important;
    border-bottom: 1px solid #edf2f7 !important;
    border-right: 1px solid #edf2f7 !important;
    vertical-align: middle !important;
}
table#aboneTable tbody td:last-child {
    border-right: none !important;
}
table#aboneTable tbody tr:last-child td {
    border-bottom: none !important;
}
table#aboneTable tbody tr:hover td {
    background-color: #f1f5f9 !important;
}

/* Summernote Tabler Icons */
.note-toolbar .note-btn i.ti,
.note-popover .note-btn i.ti {
    font-size: 1rem;
    line-height: 1;
    vertical-align: middle;
}
.note-toolbar .note-btn i.ti-chevron-down {
    font-size: 0.65rem;
}
.note-color-all .note-btn i.ti {
    font-size: 0.9rem;
}

/* Custom Context Menu */
.custom-context-menu {
    display: none;
    position: fixed !important;
    z-index: 999999 !important;
    background: #ffffff;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.16), 0 2px 8px rgba(0,0,0,0.06);
    border: 1px solid #e2e8f0;
    padding: 6px 0;
    min-width: 210px;
    backdrop-filter: blur(8px);
    user-select: none;
}
.custom-context-menu .cm-header {
    padding: 6px 14px 8px;
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 3px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 260px;
}
.custom-context-menu a {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 7px 14px;
    font-size: 13px;
    font-weight: 500;
    color: #334155;
    background: transparent;
    border: none;
    text-align: left;
    text-decoration: none;
    cursor: pointer;
    transition: background 0.12s ease, color 0.12s ease;
}
.custom-context-menu a:hover {
    background: #f1f5f9;
    color: #206bc4;
}
.custom-context-menu a.cm-danger {
    color: #e11d48;
}
.custom-context-menu a.cm-danger:hover {
    background: #fff1f2;
    color: #be123c;
}
.custom-context-menu i {
    width: 18px;
    font-size: 15px;
    margin-right: 10px;
    text-align: center;
}
.custom-context-menu .cm-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 4px 0;
}
tbody tr.context-menu-active td {
    background-color: rgba(32, 107, 196, 0.08) !important;
}

/* Dark Mode */
[data-bs-theme="dark"] #abonePage .abone-summary-card,
[data-bs-theme="dark"] .abone-summary-card,
[data-bs-theme="dark"] #abonePage .abone-table-card,
[data-bs-theme="dark"] .abone-table-card {
    background: #182433 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}
[data-bs-theme="dark"] table#aboneTable thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#aboneTable.dataTable,
[data-bs-theme="dark"] table#aboneTable.table {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#aboneTable tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#aboneTable tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] .status-summary-filter span {
    color: #94a3b8;
    background: #1e293b;
    border-color: #334155;
}
[data-bs-theme="dark"] .status-summary-filter input:checked + span {
    color: #60a5fa;
    background: rgba(59, 130, 246, .14);
    border-color: #3b82f6;
}
[data-bs-theme="dark"] .abone-search-clear {
    color: #94a3b8;
    background: #334155;
}
</style>

<div class="container-xl mt-1" id="abonePage">

    <!-- Page Header (Standart Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-id-badge-2" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Aboneler Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Sistemdeki tüm ana aboneler, aktif paketler, abonelik süreleri ve veri temizleme işlemleri
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon abone-header-icon-action" id="aboneColvisDropdownBtn" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="aboneColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- JS dinamik render -->
                        </div>
                    </div>
                    <a href="#" class="btn btn-sm btn-dark shadow-sm abone-header-action route-link" data-page="abonelik-islemleri/satin-alma-islemleri">
                        <i class="ti ti-shopping-cart-plus me-1"></i> Yeni Abonelik Tanımla
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle abone-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm">
                            <a class="dropdown-item route-link" href="#" data-page="abonelik-islemleri/paketler">
                                <i class="ti ti-packages icon me-2 text-primary"></i> Abonelik Paketleri
                            </a>
                            <a class="dropdown-item route-link" href="#" data-page="abonelik-islemleri/satin-alma-islemleri">
                                <i class="ti ti-receipt icon me-2 text-success"></i> Satın Alma & Ödemeler
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="aboneSummaryCards">
        <!-- Kart 1: Toplam Abone -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border abone-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM ABONE</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_subscribers, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Aktif: <strong class="text-success"><?= $active_count ?></strong> | Pasif: <strong class="text-danger"><?= $passive_count ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm aboneleri göster">
                            <input type="radio" name="abone_status_filter" value="" class="status-filter" checked>
                            <span><i class="ti ti-users"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Aktif Aboneler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border abone-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">AKTİF ABONELER</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-circle-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-success" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($active_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Kullanımda: <strong class="text-success"><?= $active_count ?> Abone</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Aktif aboneleri göster">
                            <input type="radio" name="abone_status_filter" value="Aktif" class="status-filter">
                            <span><i class="ti ti-user-check"></i> Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Pasif / Süresi Dolan -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border abone-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">PASİF / SÜRESİ DOLAN</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-clock-pause" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-danger" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($passive_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Yenileme Bekleyenler
                        </span>
                        <label class="status-summary-filter mb-0" title="Pasif ve süresi bitenleri göster">
                            <input type="radio" name="abone_status_filter" value="Pasif" class="status-filter">
                            <span><i class="ti ti-user-x"></i> Pasif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Bitişi Yaklaşanlar (<= 7 Gün) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border abone-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BİTİŞİ YAKLAŞANLAR (≤7 GÜN)</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-hourglass-low" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-warning" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($expiring_soon_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Hatırlatma Gönderilebilir
                        </span>
                        <span class="badge bg-warning-lt fw-semibold" style="font-size: 10px;">Kritik Süre</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Tablo Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card abone-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-id-badge-2" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Abone Listesi</h4>
                                <span id="selected-count" class="badge bg-primary-lt d-none font-11">0 seçildi</span>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.2;">Sistemdeki ana aboneler ve abonelik durumları</p>
                        </div>
                    </div>

                    <!-- Aksiyonlar ve Arama -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <button type="button" id="btn-send-mail" class="btn btn-sm btn-primary d-none abone-header-action shadow-sm">
                            <i class="ti ti-mail icon me-1"></i> Mail Gönder
                        </button>
                        <button type="button" id="btn-clear-data" class="btn btn-sm btn-danger d-none abone-header-action shadow-sm">
                            <i class="ti ti-trash icon me-1"></i> Verileri Temizle
                        </button>

                        <!-- Hızlı Arama -->
                        <div class="input-icon abone-search-wrap">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="abone-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="abone-search-clear" class="abone-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleAboneSummary" class="btn btn-sm btn-outline-secondary btn-icon abone-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Tablo Kapsayıcısı -->
                <div class="table-responsive abone-table-area">
                    <table class="table table-hover text-nowrap w-100 mb-0" id="aboneTable">
                        <thead>
                            <tr>
                                <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false">
                                    <input type="checkbox" id="select-all-abones" class="form-check-input m-0" style="width: 18px; height: 18px; cursor: pointer;">
                                </th>
                                <th style="width: 50px;" class="text-center">Sıra</th>
                                <th>Adı Soyadı</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Aktif Paket</th>
                                <th>Başlangıç Tarihi</th>
                                <th>Bitiş Tarihi</th>
                                <th>Kalan Gün</th>
                                <th style="width: 100px;">Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($subscribers as $sub):
                                // Baş harfler
                                $words = explode(" ", trim($sub->full_name ?? ''));
                                $initials = "";
                                foreach ($words as $w) {
                                    $initials .= mb_substr($w, 0, 1, 'UTF-8');
                                }
                                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'));
                                if (empty($initials)) {
                                    $initials = "AB";
                                }

                                $status = $sub->abonelik_durumu;
                                $paket_adi = $sub->paket_adi;
                                
                                $baslangic = $sub->baslangic_tarihi ? date('d.m.Y', strtotime($sub->baslangic_tarihi)) : '-';
                                $bitis = $sub->bitis_tarihi ? date('d.m.Y', strtotime($sub->bitis_tarihi)) : '-';

                                $kalan_gun_str = '-';
                                $kalan_gun_badge = '<span class="text-muted">-</span>';
                                $is_active = false;
                                $has_package = !empty($paket_adi);

                                if ($has_package && $sub->bitis_tarihi) {
                                    $end_date = new DateTime($sub->bitis_tarihi);
                                    if ($today <= $end_date) {
                                        $interval = $today->diff($end_date);
                                        $days = (int)$interval->format('%r%a');
                                        if ($days == 0) {
                                            $kalan_gun_badge = '<span class="badge bg-warning-lt fw-semibold">Bugün son gün</span>';
                                        } else {
                                            $badgeClass = $days <= 7 ? 'bg-warning-lt text-warning' : 'bg-success-lt text-success';
                                            $kalan_gun_badge = '<span class="badge ' . $badgeClass . ' fw-semibold">' . $days . ' gün kaldı</span>';
                                        }
                                        if ($status == 'aktif') {
                                            $is_active = true;
                                        }
                                    } else {
                                        $kalan_gun_badge = '<span class="badge bg-danger-lt text-danger fw-semibold">Süresi doldu</span>';
                                        $is_active = false;
                                    }
                                } elseif ($has_package) {
                                    if ($status == 'aktif') {
                                        $is_active = true;
                                    }
                                }

                                if (!$has_package) {
                                    $status_badge = '<span class="badge bg-secondary-lt">Abonelik Yok</span>';
                                    $status_text = 'Pasif';
                                } elseif ($is_active) {
                                    $status_badge = '<span class="badge bg-success-lt fw-semibold"><i class="ti ti-check me-1"></i>Aktif</span>';
                                    $status_text = 'Aktif';
                                } else {
                                    $status_badge = '<span class="badge bg-danger-lt fw-semibold"><i class="ti ti-x me-1"></i>Pasif</span>';
                                    $status_text = 'Pasif';
                                }
                                ?>
                                <tr data-abone-id="<?php echo (int)$sub->id; ?>" data-abone-name="<?php echo htmlspecialchars($sub->full_name, ENT_QUOTES); ?>" data-abone-email="<?php echo htmlspecialchars($sub->email, ENT_QUOTES); ?>">
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input m-0 abone-checkbox"
                                               value="<?php echo (int)$sub->id; ?>"
                                               data-name="<?php echo htmlspecialchars($sub->full_name, ENT_QUOTES); ?>"
                                               data-email="<?php echo htmlspecialchars($sub->email, ENT_QUOTES); ?>"
                                               style="width: 18px; height: 18px; cursor: pointer;">
                                    </td>
                                    <td class="text-center text-muted small"><?php echo $i; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm me-2 rounded-2 bg-primary-lt text-primary fw-bold" style="width: 28px; height: 28px; font-size: 11px;">
                                                <?php echo htmlspecialchars($initials); ?>
                                            </span>
                                            <div class="fw-semibold text-dark"><?php echo htmlspecialchars($sub->full_name); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($sub->email); ?></td>
                                    <td><?php echo htmlspecialchars($sub->phone ?? '-'); ?></td>
                                    <td>
                                        <?php if ($paket_adi): ?>
                                            <span class="badge bg-blue-lt fw-semibold"><?php echo htmlspecialchars($paket_adi); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small">Paket Yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="text-secondary"><?php echo $baslangic; ?></span></td>
                                    <td><span class="text-secondary"><?php echo $bitis; ?></span></td>
                                    <td><?php echo $kalan_gun_badge; ?></td>
                                    <td>
                                        <?php echo $status_badge; ?>
                                        <span class="d-none"><?php echo $status_text; ?></span>
                                    </td>
                                </tr>
                                <?php
                                $i++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Mail Gönder Modal (Standart 14px Tabler ERP Modal) -->
<div class="modal modal-blur fade" id="sendMailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header py-3 px-4 bg-light-subtle border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary" style="width: 40px; height: 40px;">
                        <i class="ti ti-mail" style="font-size: 20px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.15rem;">Abonelere E-Posta Gönder</h4>
                        <div class="text-secondary small">Seçili abonelere toplu bilgilendirme veya duyuru iletin</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <div class="mb-3">
                    <label class="form-label fw-semibold text-muted small text-uppercase">Seçili Alıcılar</label>
                    <div id="recipients-display" class="p-2.5 border rounded-3 bg-light-subtle" style="min-height: 48px; max-height: 120px; overflow-y: auto;"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label required fw-semibold">Konu Başlığı</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="ti ti-heading text-muted"></i></span>
                        <input type="text" id="mail-subject" class="form-control" placeholder="E-posta konusunu giriniz..." required>
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label required fw-semibold">Mesaj İçeriği</label>
                    <textarea id="mail-body"></textarea>
                </div>
            </div>
            <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top">
                <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" id="btn-confirm-send" class="btn btn-primary px-4 shadow-sm fw-semibold">
                    <i class="ti ti-send icon me-1"></i> E-Postaları Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Veri Temizleme Modal (Standart Tabler ERP Modal) -->
<div class="modal modal-blur fade" id="clearDataModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <form autocomplete="off" onsubmit="return false;">
                <input type="text" name="fake_username_autofill" style="display:none;" tabindex="-1" autocomplete="username">
                <div class="modal-header py-3 px-4 bg-danger-lt border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-md rounded-3 bg-danger text-white shadow-sm" style="width: 40px; height: 40px;">
                            <i class="ti ti-trash" style="font-size: 20px;"></i>
                        </div>
                        <div>
                            <h4 class="modal-title fw-bold text-danger mb-0" style="font-size: 1.15rem;">Abone Verilerini Temizle</h4>
                            <div class="text-secondary small">Seçilen abonelere ait verileri kalıcı olarak sıfırlayın</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-white">
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-muted small text-uppercase">Seçili Aboneler</label>
                        <div id="clear-recipients-display" class="p-2.5 border rounded-3 bg-light-subtle" style="min-height: 48px; max-height: 100px; overflow-y: auto;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Temizlenecek Modül Verileri</label>
                        <div class="card border rounded-3 p-3 bg-light-subtle">
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="puantaj" checked>
                                    <span class="form-check-label fw-semibold">Puantaj Verileri <small class="text-muted fw-normal">(Çalışma saatleri ve puantaj cetvelleri)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="personnel" checked>
                                    <span class="form-check-label fw-semibold">Personel Kayıtları <small class="text-muted fw-normal">(Personel, izinler, avanslar, ücretler)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="finance" checked>
                                    <span class="form-check-label fw-semibold">Kasa & Finans <small class="text-muted fw-normal">(Gelir ve gider hareketleri)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="companies" checked>
                                    <span class="form-check-label fw-semibold">Cari Firmalar <small class="text-muted fw-normal">(Müşteri ve tedarikçi carileri)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="projects" checked>
                                    <span class="form-check-label fw-semibold">Projeler & Görevler <small class="text-muted fw-normal">(Projeler ve şantiye takibi)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="offers" checked>
                                    <span class="form-check-label fw-semibold">Teklifler <small class="text-muted fw-normal">(Teklifler ve teklif kalemleri)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="roles" checked>
                                    <span class="form-check-label fw-semibold">Yetki Grupları <small class="text-muted fw-normal">(Kullanıcı rolleri ve yetkileri)</small></span>
                                </label>
                            </div>
                            <div class="mb-0">
                                <label class="form-check m-0 text-danger">
                                    <input class="form-check-input clear-module-checkbox border-danger" type="checkbox" value="myfirms">
                                    <span class="form-check-label fw-bold">Firmalarım <small class="text-danger fw-normal">(Kendi tanımladığı alt firmalar sıfırlanır!)</small></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold">Yönetici Şifreniz</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-lock text-muted"></i></span>
                            <input type="password" id="admin-password" name="admin_password" class="form-control" placeholder="İşlemi onaylamak için yönetici şifrenizi giriniz..." autocomplete="current-password">
                        </div>
                    </div>

                    <div class="alert alert-warning mb-0 border-0 shadow-sm rounded-3">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-alert-triangle text-warning" style="font-size: 20px;"></i>
                            <div>
                                <div class="fw-bold">Kalıcı Silme Uyarısı</div>
                                <div class="text-secondary small">Seçtiğiniz modüllere ait veriler geri getirilemez şekilde silinecektir.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top">
                    <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" id="btn-confirm-clear" class="btn btn-danger px-4 shadow-sm fw-semibold">
                        <i class="ti ti-trash icon me-1"></i> Verileri Kalıcı Olarak Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    var $summaryToggle = $('#toggleAboneSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('aboneler-summary-collapsed');
        $summaryToggle
            .attr('aria-expanded', String(!isCollapsed))
            .attr('aria-label', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle')
            .attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
        $summaryToggle.find('i')
            .removeClass('ti-chevron-up ti-chevron-down')
            .addClass(isCollapsed ? 'ti-chevron-down' : 'ti-chevron-up');
    }

    $summaryToggle.on('click', function() {
        var willCollapse = !document.documentElement.classList.contains('aboneler-summary-collapsed');
        document.documentElement.classList.toggle('aboneler-summary-collapsed', willCollapse);
        try {
            localStorage.setItem('aboneler_summary_collapsed', willCollapse ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });
    syncSummaryToggle();

    // Summernote başlat
    $('#mail-body').summernote({
        height: 250,
        lang: 'tr-TR',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['font', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'hr']],
            ['view', ['fullscreen', 'codeview']]
        ],
        icons: {
            'align':          'ti ti-align-left',
            'alignCenter':    'ti ti-align-center',
            'alignJustify':   'ti ti-align-justified',
            'alignLeft':      'ti ti-align-left',
            'alignRight':     'ti ti-align-right',
            'arrowsAlt':      'ti ti-arrows-maximize',
            'bold':           'ti ti-bold',
            'caret':          'ti ti-chevron-down',
            'close':          'ti ti-x',
            'code':           'ti ti-code',
            'italic':         'ti ti-italic',
            'link':           'ti ti-link',
            'unlink':         'ti ti-link-off',
            'orderedlist':    'ti ti-list-numbers',
            'strikethrough':  'ti ti-strikethrough',
            'underline':      'ti ti-underline',
            'unorderedlist':  'ti ti-list'
        },
        fontNames: ['inter', 'Arial', 'sans-serif'],
        addDefaultFonts: 'inter'
    });

    // DataTable Initialization
    var $aboneTable = $('#aboneTable');
    if ($.fn.DataTable.isDataTable($aboneTable[0])) {
        $aboneTable.DataTable().destroy();
    }

    function renderAboneColvisMenu(dtApi) {
        var $colvisMenu = $('#aboneColvisMenu');
        if (!$colvisMenu.length || !dtApi) return;
        $colvisMenu.empty();
        dtApi.columns().every(function (idx) {
            if (idx === 0) return; // Skip checkbox
            var headerEl = this.header();
            if (!headerEl) return;
            var headerText = $(headerEl).text().trim();
            if (!headerText) return;
            var isVisible = this.visible();
            var item = $('<label class="dropdown-item d-flex align-items-center gap-2 py-1 px-2 cursor-pointer mb-0 font-12">' +
                '<input type="checkbox" class="form-check-input m-0 colvis-toggle" data-column="' + idx + '" ' + (isVisible ? 'checked' : '') + '>' +
                '<span>' + headerText + '</span>' +
                '</label>');
            $colvisMenu.append(item);
        });

        $colvisMenu.off('change', '.colvis-toggle').on('change', '.colvis-toggle', function (e) {
            e.stopPropagation();
            var colIdx = $(this).data('column');
            var column = dtApi.column(colIdx);
            column.visible($(this).is(':checked'));
        });
    }

    var table = $aboneTable.DataTable({
        language: {
            url: '/src/tr.json'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]],
        order: [[1, 'asc']],
        dom: '<"d-none"f>rt<"d-flex justify-content-between align-items-center p-2 border-top"lip>',
        columnDefs: [
            { orderable: false, targets: [0] }
        ],
        initComplete: function() {
            var api = this.api();
            if (typeof window.initDataTableColumnFilters === 'function') {
                window.initDataTableColumnFilters($('#aboneTable'), api);
            }
            renderAboneColvisMenu(api);
        }
    });

    // Fast Instant Search
    var $fastSearch = $('#abone-fast-search');
    var $searchClear = $('#abone-search-clear');

    $fastSearch.on('input', function () {
        var val = $(this).val();
        table.search(val).draw();
        $searchClear.toggleClass('d-none', !val);
    });

    $searchClear.on('click', function () {
        $fastSearch.val('').trigger('input').focus();
    });

    // Status Summary Radio Filters
    $('input[name="abone_status_filter"]').on('change', function () {
        var filterVal = $(this).val();
        // Sütun 9: Durum sütunu
        table.column(9).search(filterVal ? '^' + filterVal : '', true, false).draw();
    });

    // URL'den gelen search / abone parametresini otomatik filtrele
    var urlParams = new URLSearchParams(window.location.search);
    var searchParam = urlParams.get('search') || urlParams.get('abone') || urlParams.get('q');
    if (searchParam) {
        $fastSearch.val(searchParam);
        $searchClear.removeClass('d-none');
        table.search(searchParam).draw();
    }

    // Checkbox and Multi-actions UI
    var selectAll = document.getElementById('select-all-abones');
    var btnSendMail = document.getElementById('btn-send-mail');
    var btnClearData = document.getElementById('btn-clear-data');
    var selectedCountEl = document.getElementById('selected-count');

    function getChecked() {
        return document.querySelectorAll('.abone-checkbox:checked');
    }

    function updateUI() {
        var count = getChecked().length;
        if (count > 0) {
            btnSendMail.classList.remove('d-none');
            btnClearData.classList.remove('d-none');
            selectedCountEl.classList.remove('d-none');
            selectedCountEl.textContent = count + ' kişi seçildi';
        } else {
            btnSendMail.classList.add('d-none');
            btnClearData.classList.add('d-none');
            selectedCountEl.classList.add('d-none');
        }
        var allBoxes = document.querySelectorAll('.abone-checkbox');
        if (selectAll) {
            selectAll.checked = allBoxes.length > 0 && count === allBoxes.length;
            selectAll.indeterminate = count > 0 && count < allBoxes.length;
        }
    }

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.abone-checkbox').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateUI();
        });
    }

    $(document).on('change', '.abone-checkbox', function () {
        updateUI();
    });

    // Send Mail Dialog
    btnSendMail.addEventListener('click', function () {
        var checked = getChecked();
        var display = document.getElementById('recipients-display');
        display.innerHTML = Array.from(checked).map(function (cb) {
            return '<span class="badge bg-primary-lt text-primary me-1 mb-1 font-12 py-1 px-2">' +
                   cb.dataset.name + ' &lt;' + cb.dataset.email + '&gt;</span>';
        }).join('');
        document.getElementById('mail-subject').value = '';
        $('#mail-body').summernote('reset');
        new bootstrap.Modal(document.getElementById('sendMailModal')).show();
    });

    document.getElementById('btn-confirm-send').addEventListener('click', function () {
        var checked = getChecked();
        var userIds = Array.from(checked).map(function (cb) { return cb.value; });
        var subject = document.getElementById('mail-subject').value.trim();
        var body = $('#mail-body').summernote('code').trim();
        var bodyEmpty = $('#mail-body').summernote('isEmpty');

        if (!subject) {
            Swal.fire('Uyarı', 'Lütfen e-posta konusunu giriniz.', 'warning');
            return;
        }
        if (bodyEmpty) {
            Swal.fire('Uyarı', 'Lütfen e-posta içeriğini giriniz.', 'warning');
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gönderiliyor...';

        fetch('/api/abonelik-islemleri/send-mail.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_ids: userIds, subject: subject, body: body})
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            bootstrap.Modal.getInstance(document.getElementById('sendMailModal')).hide();
            Swal.fire({
                title: data.success ? 'Başarılı' : 'Hata',
                text: data.message,
                icon: data.success ? 'success' : 'error'
            }).then(function () {
                if (data.success) {
                    document.querySelectorAll('.abone-checkbox').forEach(function (cb) { cb.checked = false; });
                    if (selectAll) selectAll.checked = false;
                    updateUI();
                }
            });
        })
        .catch(function (err) {
            Swal.fire('Hata', 'Bir hata oluştu: ' + err.message, 'error');
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-send icon me-1"></i> E-Postaları Gönder';
        });
    });

    // Clear Data Dialog
    btnClearData.addEventListener('click', function () {
        var checked = getChecked();
        var display = document.getElementById('clear-recipients-display');
        display.innerHTML = Array.from(checked).map(function (cb) {
            return '<span class="badge bg-danger-lt text-danger me-1 mb-1 font-12 py-1 px-2">' + cb.dataset.name + '</span>';
        }).join('');
        document.getElementById('admin-password').value = '';
        document.querySelectorAll('.clear-module-checkbox').forEach(function (cb) {
            cb.checked = (cb.value !== 'myfirms');
        });
        new bootstrap.Modal(document.getElementById('clearDataModal')).show();
    });

    document.getElementById('btn-confirm-clear').addEventListener('click', function () {
        var checked = getChecked();
        var userIds = Array.from(checked).map(function (cb) { return cb.value; });
        
        var selectedModules = [];
        document.querySelectorAll('.clear-module-checkbox:checked').forEach(function (cb) {
            selectedModules.push(cb.value);
        });

        var password = document.getElementById('admin-password').value.trim();

        if (selectedModules.length === 0) {
            Swal.fire('Uyarı', 'Lütfen temizlemek istediğiniz en az bir modül seçiniz.', 'warning');
            return;
        }

        if (!password) {
            Swal.fire('Uyarı', 'Lütfen işlemi onaylamak için yönetici şifrenizi giriniz.', 'warning');
            return;
        }

        var btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Temizleniyor...';

        fetch('/api/abonelik-islemleri/clear-data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_ids: userIds, modules: selectedModules, password: password})
        })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            bootstrap.Modal.getInstance(document.getElementById('clearDataModal')).hide();
            Swal.fire({
                title: data.success ? 'Başarılı' : 'Hata',
                text: data.message,
                icon: data.success ? 'success' : 'error'
            }).then(function () {
                if (data.success) {
                    document.querySelectorAll('.abone-checkbox').forEach(function (cb) { cb.checked = false; });
                    if (selectAll) selectAll.checked = false;
                    updateUI();
                }
            });
        })
        .catch(function (err) {
            Swal.fire('Hata', 'Bir hata oluştu: ' + err.message, 'error');
        })
        .finally(function () {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-trash icon me-1"></i> Verileri Kalıcı Olarak Sil';
        });
    });

    // Sağ Tık Bağlam Menüsü (Custom Context Menu)
    $(document).on('contextmenu', '#aboneTable tbody tr, #aboneTable tbody td', function(e) {
        var $tr = $(this).closest('tr');
        if (!$tr.length) return;

        var $checkbox = $tr.find('.abone-checkbox');
        var aboneId = $tr.attr('data-abone-id') || $checkbox.val();
        var aboneName = $tr.attr('data-abone-name') || $checkbox.attr('data-name') || $tr.find('td').eq(2).text().trim() || 'Abone İşlemleri';
        var aboneEmail = $tr.attr('data-abone-email') || $checkbox.attr('data-email') || $tr.find('td').eq(3).text().trim() || '';

        if (!aboneId) return;

        e.preventDefault();
        $('#aboneTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var filterKeyword = aboneName.trim();
        var targetPage = 'abonelik-islemleri/satin-alma-islemleri' + (filterKeyword ? '&search=' + encodeURIComponent(filterKeyword) : '');

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-user me-1"></i> ${$('<div>').text(aboneName).html()}</div>
            <a href="#" class="cm-action-mail" data-id="${aboneId}" data-name="${$('<div>').text(aboneName).html()}" data-email="${$('<div>').text(aboneEmail).html()}">
                <i class="ti ti-mail text-primary"></i> E-Posta Gönder
            </a>
            <a href="#" class="route-link" data-page="${targetPage}">
                <i class="ti ti-receipt text-success"></i> Satın Alma & Ödemeler
            </a>
            <div class="cm-divider"></div>
            <a href="#" class="cm-danger cm-action-clear" data-id="${aboneId}" data-name="${$('<div>').text(aboneName).html()}">
                <i class="ti ti-trash"></i> Verileri Temizle
            </a>
        `;

        $contextMenu.html(menuHtml);
        $contextMenu.css({ display: 'block', opacity: 0, position: 'fixed', zIndex: 999999 });

        var menuWidth = $contextMenu.outerWidth() || 210;
        var menuHeight = $contextMenu.outerHeight() || 180;
        var clickX = e.clientX;
        var clickY = e.clientY;
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();

        var posX = (clickX + menuWidth > windowWidth) ? Math.max(10, windowWidth - menuWidth - 15) : clickX;
        var posY = (clickY + menuHeight > windowHeight) ? Math.max(10, windowHeight - menuHeight - 15) : clickY;

        $contextMenu.css({
            top: posY + 'px',
            left: posX + 'px',
            opacity: 1
        });
    });

    $(document).on('click', '.cm-action-mail', function(e) {
        e.preventDefault();
        $('#customContextMenu').hide();
        var id = $(this).data('id');
        var name = $(this).data('name');
        var email = $(this).data('email');

        document.querySelectorAll('.abone-checkbox').forEach(function(cb) { cb.checked = (cb.value == id); });
        updateUI();

        var display = document.getElementById('recipients-display');
        display.innerHTML = '<span class="badge bg-primary-lt text-primary me-1 mb-1 font-12 py-1 px-2">' + name + ' &lt;' + email + '&gt;</span>';
        document.getElementById('mail-subject').value = '';
        $('#mail-body').summernote('reset');
        new bootstrap.Modal(document.getElementById('sendMailModal')).show();
    });

    $(document).on('click', '.cm-action-clear', function(e) {
        e.preventDefault();
        $('#customContextMenu').hide();
        var id = $(this).data('id');
        var name = $(this).data('name');

        document.querySelectorAll('.abone-checkbox').forEach(function(cb) { cb.checked = (cb.value == id); });
        updateUI();

        var display = document.getElementById('clear-recipients-display');
        display.innerHTML = '<span class="badge bg-danger-lt text-danger me-1 mb-1 font-12 py-1 px-2">' + name + '</span>';
        document.getElementById('admin-password').value = '';
        document.querySelectorAll('.clear-module-checkbox').forEach(function (cb) {
            cb.checked = (cb.value !== 'myfirms');
        });
        new bootstrap.Modal(document.getElementById('clearDataModal')).show();
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#aboneTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#aboneTable tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#aboneTable tbody tr').removeClass('context-menu-active');
    });
});
</script>
