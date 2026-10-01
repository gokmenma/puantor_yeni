<?php
require_once "App/Helper/helper.php";
require_once "Model/OdemelerModel.php";
require_once "Model/AbonelerModel.php";
require_once "Model/AbonelikPaketleriModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;
use App\Helper\Helper;

// Yetki Kontrolü
$perm->checkAuthorize("abonelik_satin_alimlari");

$odemelerModel = new OdemelerModel();
$payments = $odemelerModel->getPayments();

$abonelerModel = new AbonelerModel();
$subscribers = $abonelerModel->getSubscribers();

$paketModel = new AbonelikPaketleriModel();
$packages = $paketModel->getPackages();

// İstatistikler
$total_payments = count($payments);
$success_count = 0;
$success_total = 0;
$pending_count = 0;
$pending_total = 0;
$this_month_total = 0;
$current_month = date('Y-m');

foreach ($payments as $pay) {
    $tutar = (float)$pay->tutar;
    if ($pay->durum === 'basarili') {
        $success_count++;
        $success_total += $tutar;
        if (!empty($pay->odeme_tarihi) && substr($pay->odeme_tarihi, 0, 7) === $current_month) {
            $this_month_total += $tutar;
        }
    } elseif ($pay->durum === 'beklemede') {
        $pending_count++;
        $pending_total += $tutar;
    }
}
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'odemeler-summary-collapsed',
            localStorage.getItem('odemeler_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.odemeler-summary-collapsed #odemeSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}

.odeme-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.odeme-header-icon-action,
.odeme-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}

.odeme-header-icon-action i,
.odeme-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

html:not([data-bs-theme="dark"]) #odemePage .odeme-summary-card,
html:not([data-bs-theme="dark"]) .odeme-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#odemeSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

html:not([data-bs-theme="dark"]) #odemePage .odeme-table-card,
html:not([data-bs-theme="dark"]) .odeme-table-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.odeme-table-card > .odeme-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.odeme-table-card > .card-header {
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

.odeme-search-wrap {
    position: relative;
    min-width: 170px;
}
.odeme-search-wrap input {
    height: 32px;
    border-radius: 6px;
    font-size: 12.5px;
    padding-right: 26px;
}
.odeme-search-clear {
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
.odeme-search-clear:hover {
    background: #cbd5e1;
    color: #1e293b;
}

table#odemeTable.dataTable,
table#odemeTable.table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    overflow: hidden !important;
}

table#odemeTable thead th {
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
table#odemeTable thead th:last-child {
    border-right: none !important;
}

table#odemeTable tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #334155 !important;
    border-bottom: 1px solid #edf2f7 !important;
    border-right: 1px solid #edf2f7 !important;
    vertical-align: middle !important;
}
table#odemeTable tbody td:last-child {
    border-right: none !important;
}
table#odemeTable tbody tr:last-child td {
    border-bottom: none !important;
}
table#odemeTable tbody tr:hover td {
    background-color: #f1f5f9 !important;
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
[data-bs-theme="dark"] #odemePage .odeme-summary-card,
[data-bs-theme="dark"] .odeme-summary-card,
[data-bs-theme="dark"] #odemePage .odeme-table-card,
[data-bs-theme="dark"] .odeme-table-card {
    background: #182433 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}
[data-bs-theme="dark"] table#odemeTable thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#odemeTable.dataTable,
[data-bs-theme="dark"] table#odemeTable.table {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#odemeTable tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#odemeTable tbody tr:hover td {
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
[data-bs-theme="dark"] .odeme-search-clear {
    color: #94a3b8;
    background: #334155;
}
</style>

<div class="container-xl mt-1" id="odemePage">

    <!-- Page Header (Standart Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-shopping-cart-check" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Satın Alma & Ödeme İşlemleri
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Abonelik satın alımları, tahsilat kayıtları, ödeme durumları ve manuel paket tanımlamaları
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon odeme-header-icon-action" id="odemeColvisDropdownBtn" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="odemeColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- JS dinamik render -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary shadow-sm odeme-header-action btn-add-transaction">
                        <i class="ti ti-plus me-1"></i> Yeni İşlem Ekle
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle odeme-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm">
                            <a class="dropdown-item route-link" href="#" data-page="abonelik-islemleri/list">
                                <i class="ti ti-users icon me-2 text-primary"></i> Aboneler Yönetimi
                            </a>
                            <a class="dropdown-item route-link" href="#" data-page="abonelik-islemleri/paketler">
                                <i class="ti ti-packages icon me-2 text-primary"></i> Abonelik Paketleri
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="odemeSummaryCards">
        <!-- Kart 1: Toplam İşlem -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border odeme-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM İŞLEM</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-receipt" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_payments, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Başarılı: <strong class="text-success"><?= $success_count ?></strong> | Bekleyen: <strong class="text-warning"><?= $pending_count ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm işlemleri göster">
                            <input type="radio" name="odeme_status_filter" value="" class="status-filter" checked>
                            <span><i class="ti ti-receipt"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Başarılı Tahsilat -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border odeme-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BAŞARILI TAHSİLAT</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-cash" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-success" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($success_total, 2, ',', '.') ?> ₺
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Toplam <?= $success_count ?> Başarılı İşlem
                        </span>
                        <label class="status-summary-filter mb-0" title="Başarılı işlemleri göster">
                            <input type="radio" name="odeme_status_filter" value="Başarılı" class="status-filter">
                            <span><i class="ti ti-check"></i> Başarılı</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Bekleyen Ödemeler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border odeme-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BEKLEYEN ÖDEMELER</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-clock-dollar" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-warning" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($pending_total, 2, ',', '.') ?> ₺
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            <?= $pending_count ?> Bekleyen Onay
                        </span>
                        <label class="status-summary-filter mb-0" title="Beklemede olan işlemleri göster">
                            <input type="radio" name="odeme_status_filter" value="Beklemede" class="status-filter">
                            <span><i class="ti ti-clock"></i> Beklemede</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Bu Ayki Tahsilat -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border odeme-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BU AYKİ TAHSİLAT</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-calendar-stats" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-info" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($this_month_total, 2, ',', '.') ?> ₺
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Bu Ay Gerçekleşen Ciro
                        </span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;"><?= date('m/Y') ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Tablo Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card odeme-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-credit-card" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Satın Alma & Ödeme Geçmişi</h4>
                                <a href="#" class="btn-add-transaction text-primary" title="Yeni İşlem Ekle">
                                    <i class="ti ti-circle-plus" style="font-size: 16px;"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.2;">Yapılan abonelik ödemeleri, tahsilat yöntemleri ve işlem durumları</p>
                        </div>
                    </div>

                    <!-- Aksiyonlar ve Arama -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Hızlı Arama -->
                        <div class="input-icon odeme-search-wrap">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="odeme-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="odeme-search-clear" class="odeme-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleOdemeSummary" class="btn btn-sm btn-outline-secondary btn-icon odeme-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Tablo Kapsayıcısı -->
                <div class="table-responsive odeme-table-area">
                    <table class="table table-hover text-nowrap w-100 mb-0" id="odemeTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">Sıra</th>
                                <th>Abone Adı Soyadı</th>
                                <th>Email</th>
                                <th>Satın Alınan Paket</th>
                                <th>Tutar</th>
                                <th>Başlangıç Tarihi</th>
                                <th>Bitiş Tarihi</th>
                                <th>Ödeme Tarihi</th>
                                <th>Ödeme Yöntemi</th>
                                <th style="width: 95px;">Ödeme Durumu</th>
                                <th style="width: 80px;" class="text-end no-export" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($payments as $pay):
                                $id = Security::encrypt($pay->id);
                                $tutar_format = number_format($pay->tutar, 2, ',', '.') . ' ₺';
                                $baslangic_tarihi = $pay->baslangic_tarihi ? date('d.m.Y', strtotime($pay->baslangic_tarihi)) : '-';
                                $bitis_tarihi = $pay->bitis_tarihi ? date('d.m.Y', strtotime($pay->bitis_tarihi)) : '-';
                                $odeme_tarihi = $pay->odeme_tarihi ? date('d.m.Y H:i', strtotime($pay->odeme_tarihi)) : '-';
                                
                                $status_badge = '';
                                $status_text = '';
                                if ($pay->durum == 'basarili') {
                                    $status_badge = '<span class="badge bg-success-lt fw-semibold"><i class="ti ti-check me-1"></i>Başarılı</span>';
                                    $status_text = 'Başarılı';
                                } elseif ($pay->durum == 'basarisiz') {
                                    $status_badge = '<span class="badge bg-danger-lt fw-semibold"><i class="ti ti-x me-1"></i>Başarısız</span>';
                                    $status_text = 'Başarısız';
                                } else {
                                    $status_badge = '<span class="badge bg-warning-lt fw-semibold"><i class="ti ti-clock me-1"></i>Beklemede</span>';
                                    $status_text = 'Beklemede';
                                }
                                ?>
                                <tr data-payment-id="<?php echo $id; ?>" data-subscriber-name="<?php echo htmlspecialchars($pay->subscriber_name ?? '-', ENT_QUOTES); ?>" data-payment-status="<?php echo $pay->durum; ?>">
                                    <td class="text-center text-muted small"><?php echo $i; ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?php echo htmlspecialchars($pay->subscriber_name ?? '-'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pay->subscriber_email ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-blue-lt fw-semibold"><?php echo htmlspecialchars($pay->paket_adi ?? 'Bilinmeyen Paket'); ?></span>
                                    </td>
                                    <td><span class="fw-bold text-dark"><?php echo $tutar_format; ?></span></td>
                                    <td><span class="text-secondary"><?php echo $baslangic_tarihi; ?></span></td>
                                    <td><span class="text-secondary"><?php echo $bitis_tarihi; ?></span></td>
                                    <td><span class="text-secondary"><?php echo $odeme_tarihi; ?></span></td>
                                    <td>
                                        <span class="badge bg-light text-secondary"><?php echo htmlspecialchars($pay->odeme_yontemi ?? 'Manuel'); ?></span>
                                    </td>
                                    <td>
                                        <?php echo $status_badge; ?>
                                        <span class="d-none"><?php echo $status_text; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary btn-icon dropdown-toggle-no-caret" data-bs-toggle="dropdown" title="İşlemler" style="width: 28px; height: 28px; padding: 0;">
                                                    <i class="ti ti-dots-vertical" style="font-size: 13px;"></i>
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end shadow-sm">
                                                    <?php if ($pay->durum !== 'basarili'): ?>
                                                        <a class="dropdown-item change-status-btn text-success" href="#" data-id="<?php echo $id; ?>" data-status="basarili">
                                                            <i class="ti ti-check icon me-2 text-success"></i> Başarılı Yap
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($pay->durum !== 'beklemede'): ?>
                                                        <a class="dropdown-item change-status-btn text-warning" href="#" data-id="<?php echo $id; ?>" data-status="beklemede">
                                                            <i class="ti ti-clock icon me-2 text-warning"></i> Beklemede Yap
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($pay->durum !== 'basarisiz'): ?>
                                                        <a class="dropdown-item change-status-btn text-danger" href="#" data-id="<?php echo $id; ?>" data-status="basarisiz">
                                                            <i class="ti ti-x icon me-2 text-danger"></i> Başarısız Yap
                                                        </a>
                                                    <?php endif; ?>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item edit-payment-btn" href="#"
                                                       data-id="<?php echo $id; ?>"
                                                       data-kullanici-id="<?php echo $pay->kullanici_id; ?>"
                                                       data-paket-id="<?php echo $pay->paket_id; ?>"
                                                       data-firma-hakki="<?php echo (int)$pay->firma_hakki; ?>"
                                                       data-alt-kullanici-hakki="<?php echo (int)$pay->alt_kullanici_hakki; ?>"
                                                       data-tutar="<?php echo htmlspecialchars($pay->tutar); ?>"
                                                       data-baslangic-tarihi="<?php echo $pay->baslangic_tarihi ? date('d.m.Y', strtotime($pay->baslangic_tarihi)) : ''; ?>"
                                                       data-bitis-tarihi="<?php echo $pay->bitis_tarihi ? date('d.m.Y', strtotime($pay->bitis_tarihi)) : ''; ?>">
                                                        <i class="ti ti-edit icon me-2 text-primary"></i> Düzenle
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item delete-payment text-danger" href="#" data-id="<?php echo $id; ?>">
                                                        <i class="ti ti-trash icon me-2 text-danger"></i> Sil
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
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

<!-- Yeni Satış / Ödeme Ekle Modalı (Standart Tabler ERP Modal) -->
<div class="modal modal-blur fade" id="transactionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header py-3 px-4 bg-light-subtle border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary" style="width: 40px; height: 40px;">
                        <i class="ti ti-shopping-cart" style="font-size: 20px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="modalTitleText" style="font-size: 1.15rem;">Yeni İşlem Ekle</h4>
                        <div class="text-secondary small">Manuel abonelik satışı ve tahsilat kaydı oluşturun</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" id="transactionForm">
                <div class="modal-body p-4 bg-white">
                    <input type="hidden" name="action" id="tx_action" value="addManualSale">
                    <input type="hidden" name="payment_id" id="tx_payment_id" value="">

                    <div class="row g-3">
                        <!-- Kullanıcı Seçimi -->
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Abone / Kullanıcı</label>
                            <select name="kullanici_id" id="tx_kullanici_id" class="form-select select2-modal" required>
                                <option value="">Kullanıcı seçiniz...</option>
                                <?php foreach ($subscribers as $sub): ?>
                                    <option value="<?php echo Security::encrypt($sub->id); ?>"
                                            data-id="<?php echo $sub->id; ?>">
                                        <?php echo htmlspecialchars($sub->full_name . ' (' . $sub->email . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Paket Seçimi -->
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Abonelik Paketi</label>
                            <select name="paket_id" id="tx_paket_id" class="form-select select2-modal" required>
                                <option value="">Paket seçiniz...</option>
                                <?php foreach ($packages as $pkg): 
                                    if ($pkg->aktif_mi != 1) continue;
                                    ?>
                                    <option value="<?php echo Security::encrypt($pkg->id); ?>"
                                            data-id="<?php echo $pkg->id; ?>"
                                            data-sure="<?php echo (int)$pkg->sure; ?>"
                                            data-firma_hakki="<?php echo (int)$pkg->firma_hakki; ?>"
                                            data-alt_kullanici_hakki="<?php echo (int)$pkg->alt_kullanici_hakki; ?>"
                                            data-fiyat="<?php echo htmlspecialchars($pkg->fiyat); ?>">
                                        <?php echo htmlspecialchars($pkg->ad . ' - ' . number_format($pkg->fiyat, 2, ',', '.') . ' ₺ (' . $pkg->sure . ' Gün)'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Tutar -->
                        <div class="col-md-12">
                            <label class="form-label required fw-semibold">İşlem Tutarı (₺)</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-currency-lira text-muted"></i></span>
                                <input type="number" step="0.01" min="0" name="tutar" id="tx_tutar" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>

                        <!-- Firma Limiti & Alt Kullanıcı Limiti -->
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Firma Limiti</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-building text-muted"></i></span>
                                <input type="number" min="1" name="firma_hakki" id="tx_firma_hakki" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Alt Kullanıcı Limiti</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-users text-muted"></i></span>
                                <input type="number" min="0" name="alt_kullanici_hakki" id="tx_alt_kullanici_hakki" class="form-control" required>
                            </div>
                        </div>

                        <!-- Başlangıç & Bitiş Tarihleri -->
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Başlangıç Tarihi</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar text-muted"></i></span>
                                <input type="text" name="baslangic_tarihi" id="tx_baslangic_tarihi" class="form-control flatpickr-modal" required value="<?php echo date('d.m.Y'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Bitiş Tarihi</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar text-muted"></i></span>
                                <input type="text" name="bitis_tarihi" id="tx_bitis_tarihi" class="form-control flatpickr-modal" required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top">
                    <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold" id="saveTransactionBtn">
                        <i class="ti ti-device-floppy icon me-1"></i> İşlemi Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var $summaryToggle = $('#toggleOdemeSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('odemeler-summary-collapsed');
        $summaryToggle
            .attr('aria-expanded', String(!isCollapsed))
            .attr('aria-label', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle')
            .attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
        $summaryToggle.find('i')
            .removeClass('ti-chevron-up ti-chevron-down')
            .addClass(isCollapsed ? 'ti-chevron-down' : 'ti-chevron-up');
    }

    $summaryToggle.on('click', function() {
        var willCollapse = !document.documentElement.classList.contains('odemeler-summary-collapsed');
        document.documentElement.classList.toggle('odemeler-summary-collapsed', willCollapse);
        try {
            localStorage.setItem('odemeler_summary_collapsed', willCollapse ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });
    syncSummaryToggle();

    var fpStart = null;
    var fpEnd = null;

    if (typeof flatpickr !== 'undefined') {
        fpStart = flatpickr("#tx_baslangic_tarihi", {
            dateFormat: "d.m.Y",
            locale: "tr",
            onChange: function(selectedDates, dateStr, instance) {
                recalculateEndDate();
            }
        });

        fpEnd = flatpickr("#tx_bitis_tarihi", {
            dateFormat: "d.m.Y",
            locale: "tr"
        });
    }

    // DataTable Initialization
    var $odemeTable = $('#odemeTable');
    if ($.fn.DataTable.isDataTable($odemeTable[0])) {
        $odemeTable.DataTable().destroy();
    }

    function renderOdemeColvisMenu(dtApi) {
        var $colvisMenu = $('#odemeColvisMenu');
        if (!$colvisMenu.length || !dtApi) return;
        $colvisMenu.empty();
        dtApi.columns().every(function (idx) {
            if (idx === 10) return; // Skip actions
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

    var table = $odemeTable.DataTable({
        language: {
            url: '/src/tr.json'
        },
        pageLength: 25,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]],
        order: [[0, 'asc']],
        dom: '<"d-none"f>rt<"d-flex justify-content-between align-items-center p-2 border-top"lip>',
        columnDefs: [
            { orderable: false, targets: [10] }
        ],
        initComplete: function() {
            var api = this.api();
            if (typeof window.initDataTableColumnFilters === 'function') {
                window.initDataTableColumnFilters($('#odemeTable'), api);
            }
            renderOdemeColvisMenu(api);
        }
    });

    // Fast Instant Search
    var $fastSearch = $('#odeme-fast-search');
    var $searchClear = $('#odeme-search-clear');

    $fastSearch.on('input', function () {
        var val = $(this).val();
        table.search(val).draw();
        $searchClear.toggleClass('d-none', !val);
    });

    $searchClear.on('click', function () {
        $fastSearch.val('').trigger('input').focus();
    });

    // Status Summary Radio Filters
    $('input[name="odeme_status_filter"]').on('change', function () {
        var filterVal = $(this).val();
        // Sütun 9: Ödeme Durumu sütunu
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

    // Modal show event (Add)
    $(document).on('click', '.btn-add-transaction', function(e) {
        e.preventDefault();
        $('#transactionForm')[0].reset();
        
        $('#tx_action').val('addManualSale');
        $('#tx_payment_id').val('');
        $('#modalTitleText').text('Yeni Satış / Ödeme Ekle');
        
        $('#tx_kullanici_id').val('').trigger('change');
        $('#tx_paket_id').val('').trigger('change');
        
        var today = new Date();
        if (fpStart) fpStart.setDate(today);
        if (fpEnd) fpEnd.setDate('');

        $('#transactionModal').modal('show');
    });

    // Modal show event (Edit)
    $(document).on('click', '.edit-payment-btn', function(e) {
        e.preventDefault();
        $('#transactionForm')[0].reset();
        
        var payment_id = $(this).data('id');
        var kullanici_id = $(this).data('kullanici-id');
        var paket_id = $(this).data('paket-id');
        var firma_hakki = $(this).data('firma-hakki');
        var alt_kullanici_hakki = $(this).data('alt-kullanici-hakki');
        var tutar = $(this).data('tutar');
        var baslangic_tarihi = $(this).data('baslangic-tarihi');
        var bitis_tarihi = $(this).data('bitis-tarihi');
        
        $('#tx_action').val('editManualSale');
        $('#tx_payment_id').val(payment_id);
        $('#modalTitleText').text('Satış İşlemini Düzenle');
        
        var kullaniciOpt = $('#tx_kullanici_id option').filter(function() {
            return $(this).data('id') == kullanici_id;
        });
        if (kullaniciOpt.length) {
            $('#tx_kullanici_id').val(kullaniciOpt.val()).trigger('change');
        }
        
        var paketOpt = $('#tx_paket_id option').filter(function() {
            return $(this).data('id') == paket_id;
        });
        if (paketOpt.length) {
            $('#tx_paket_id').val(paketOpt.val()).trigger('change');
        }
        
        $('#tx_firma_hakki').val(firma_hakki);
        $('#tx_alt_kullanici_hakki').val(alt_kullanici_hakki);
        $('#tx_tutar').val(tutar);
        
        if (fpStart) fpStart.setDate(baslangic_tarihi);
        if (fpEnd) fpEnd.setDate(bitis_tarihi);

        $('#transactionModal').modal('show');
    });

    // Initialize Select2 correctly inside modal
    $('#transactionModal').on('shown.bs.modal', function () {
        if ($.fn.select2) {
            $('.select2-modal').select2({
                dropdownParent: $('#transactionModal'),
                width: '100%'
            });
        }
    });

    // Package select trigger
    $(document).on('change', '#tx_paket_id', function() {
        var selectedOpt = $('option:selected', this);
        if (selectedOpt.val()) {
            var firma_hakki = selectedOpt.data('firma_hakki');
            var alt_kullanici_hakki = selectedOpt.data('alt_kullanici_hakki');
            var fiyat = selectedOpt.data('fiyat');
            
            $('#tx_firma_hakki').val(firma_hakki);
            $('#tx_alt_kullanici_hakki').val(alt_kullanici_hakki);
            $('#tx_tutar').val(fiyat);
            
            recalculateEndDate();
        } else {
            $('#tx_firma_hakki').val('');
            $('#tx_alt_kullanici_hakki').val('');
            $('#tx_tutar').val('');
            if (fpEnd) fpEnd.setDate('');
        }
    });

    function recalculateEndDate() {
        var selectedOpt = $('#tx_paket_id option:selected');
        if (!selectedOpt.val()) return;
        
        var duration = parseInt(selectedOpt.data('sure')) || 30;
        var startDateStr = $('#tx_baslangic_tarihi').val();
        if (!startDateStr) return;
        
        var parts = startDateStr.split('.');
        if (parts.length === 3) {
            var day = parseInt(parts[0], 10);
            var month = parseInt(parts[1], 10) - 1;
            var year = parseInt(parts[2], 10);
            
            var startDate = new Date(year, month, day);
            startDate.setDate(startDate.getDate() + duration);
            
            if (fpEnd) {
                fpEnd.setDate(startDate);
            } else {
                var endDay = String(startDate.getDate()).padStart(2, '0');
                var endMonth = String(startDate.getMonth() + 1).padStart(2, '0');
                var endYear = startDate.getFullYear();
                $('#tx_bitis_tarihi').val(endDay + '.' + endMonth + '.' + endYear);
            }
        }
    }

    // Submit handler
    $(document).on('submit', '#transactionForm', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = $('#saveTransactionBtn');
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...');

        var formData = new FormData(form[0]);

        fetch('/api/abonelik-islemleri/odemeler.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var title = data.status === "success" ? "Başarılı" : "Hata";
            Swal.fire({
                title: title,
                text: data.message,
                icon: data.status
            }).then(function() {
                if (data.status === "success") {
                    $('#transactionModal').modal('hide');
                    window.location.reload();
                }
            });
        })
        .catch(function(error) {
            Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
        })
        .finally(function() {
            submitBtn.prop('disabled', false).html('<i class="ti ti-device-floppy icon me-1"></i> İşlemi Kaydet');
        });
    });

    // Status change handler
    $(document).on("click", ".change-status-btn", function (e) {
        e.preventDefault();
        var id = $(this).data("id");
        var status = $(this).data("status");
        var statusText = '';
        
        switch (status) {
            case 'basarili': statusText = 'Başarılı'; break;
            case 'basarisiz': statusText = 'Başarısız'; break;
            case 'beklemede': statusText = 'Beklemede'; break;
        }

        Swal.fire({
            title: "Emin misiniz?",
            text: "Ödeme işleminin durumunu '" + statusText + "' olarak değiştirmek istiyor musunuz?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Evet, Değiştir",
            cancelButtonText: "Vazgeç"
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append("action", "updatePaymentStatus");
                formData.append("id", id);
                formData.append("status", status);

                fetch("/api/abonelik-islemleri/odemeler.php", {
                    method: "POST",
                    body: formData
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    var iconType = data.status === "success" ? "success" : "error";
                    var titleText = data.status === "success" ? "Başarılı" : "Hata";
                    
                    Swal.fire({
                        title: titleText,
                        text: data.message,
                        icon: iconType
                    }).then(function() {
                        if (data.status === "success") {
                            window.location.reload();
                        }
                    });
                })
                .catch(function(error) {
                    Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
                });
            }
        });
    });

    // Delete payment handler
    $(document).on("click", ".delete-payment", function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Silmek İstediğinize Emin Misiniz?',
            text: 'Seçilen satın alma işlemi ve ilişkili abonelik silinecektir! Bu işlemi geri alamazsınız.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('action', 'deletePayment');
                formData.append('id', id);

                fetch('/api/abonelik-islemleri/odemeler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(function(res) { return res.json(); })
                .then(function(data) {
                    Swal.fire({
                        title: data.status === 'success' ? 'Silindi' : 'Hata',
                        text: data.message,
                        icon: data.status
                    }).then(function() {
                        if (data.status === 'success') {
                            window.location.reload();
                        }
                    });
                })
                .catch(function(err) {
                    Swal.fire('Hata', 'Bir hata oluştu: ' + err.message, 'error');
                });
            }
        });
    });

    // Sağ Tık Bağlam Menüsü (Custom Context Menu)
    $(document).on('contextmenu', '#odemeTable tbody tr, #odemeTable tbody td', function(e) {
        var $tr = $(this).closest('tr');
        if (!$tr.length) return;

        var $editBtn = $tr.find('.edit-payment-btn');
        var $deleteBtn = $tr.find('.delete-payment');
        var paymentId = $tr.attr('data-payment-id') || $editBtn.attr('data-id') || $deleteBtn.attr('data-id');
        var subName = $tr.attr('data-subscriber-name') || $tr.find('td').eq(1).text().trim() || 'Ödeme İşlemleri';
        var status = $tr.attr('data-payment-status') || '';

        if (!paymentId) return;

        e.preventDefault();
        $('#odemeTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var statusActionsHtml = '';
        if (status !== 'basarili') {
            statusActionsHtml += `<a href="#" class="cm-action-status text-success" data-id="${paymentId}" data-status="basarili"><i class="ti ti-check text-success"></i> Başarılı Yap</a>`;
        }
        if (status !== 'beklemede') {
            statusActionsHtml += `<a href="#" class="cm-action-status text-warning" data-id="${paymentId}" data-status="beklemede"><i class="ti ti-clock text-warning"></i> Beklemede Yap</a>`;
        }
        if (status !== 'basarisiz') {
            statusActionsHtml += `<a href="#" class="cm-action-status text-danger" data-id="${paymentId}" data-status="basarisiz"><i class="ti ti-x text-danger"></i> Başarısız Yap</a>`;
        }

        var targetAbonePage = 'abonelik-islemleri/list' + (subName ? '&search=' + encodeURIComponent(subName) : '');

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-credit-card me-1"></i> ${$('<div>').text(subName).html()}</div>
            <a href="#" class="cm-action-edit-pay" data-id="${paymentId}">
                <i class="ti ti-edit text-primary"></i> İşlemi Düzenle
            </a>
            ${statusActionsHtml}
            <a href="#" class="route-link" data-page="${targetAbonePage}">
                <i class="ti ti-users text-info"></i> Abone Detayı
            </a>
            <div class="cm-divider"></div>
            <a href="#" class="cm-danger cm-action-delete-pay" data-id="${paymentId}">
                <i class="ti ti-trash"></i> İşlemi Sil
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

    $(document).on('click', '.cm-action-status', function(e) {
        e.preventDefault();
        $('#customContextMenu').hide();
        var id = $(this).data('id');
        var status = $(this).data('status');
        $('a.change-status-btn[data-id="' + id + '"][data-status="' + status + '"]').trigger('click');
    });

    $(document).on('click', '.cm-action-edit-pay', function(e) {
        e.preventDefault();
        $('#customContextMenu').hide();
        var id = $(this).data('id');
        $('a.edit-payment-btn[data-id="' + id + '"]').trigger('click');
    });

    $(document).on('click', '.cm-action-delete-pay', function(e) {
        e.preventDefault();
        $('#customContextMenu').hide();
        var id = $(this).data('id');
        $('a.delete-payment[data-id="' + id + '"]').trigger('click');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#odemeTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#odemeTable tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#odemeTable tbody tr').removeClass('context-menu-active');
    });
});
</script>
