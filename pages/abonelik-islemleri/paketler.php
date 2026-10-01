<?php
require_once "App/Helper/helper.php";
require_once "Model/AbonelikPaketleriModel.php";
require_once "Model/Auths.php";
require_once "App/Helper/security.php";

use App\Helper\Security;
use App\Helper\Helper;

// Yetki Kontrolü
$perm->checkAuthorize("aboneler_paketleri");

$paketModel = new AbonelikPaketleriModel();
$packages = $paketModel->getPackages();

$authsModel = new Auths();
$topLevelModules = $authsModel->auths();
$moduleTitlesById = [];
foreach ($topLevelModules as $module) {
    $moduleTitlesById[$module->id] = $module->title;
    foreach ($authsModel->subAuths($module->id) as $sub) {
        $moduleTitlesById[$sub->id] = $sub->title;
    }
}

// İstatistikler
$total_packages = count($packages);
$active_packages = 0;
$hidden_packages = 0;
$total_price = 0;

foreach ($packages as $pkg) {
    if ((int)$pkg->aktif_mi === 1) {
        $active_packages++;
        $total_price += (float)$pkg->fiyat;
    }
    if ((int)($pkg->kullaniciya_goster_mi ?? 1) === 0) {
        $hidden_packages++;
    }
}

$avg_price = $active_packages > 0 ? ($total_price / $active_packages) : 0;
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'paketler-summary-collapsed',
            localStorage.getItem('paketler_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.paketler-summary-collapsed #paketSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}

.paket-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.paket-header-icon-action,
.paket-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}

.paket-header-icon-action i,
.paket-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

html:not([data-bs-theme="dark"]) #paketPage .paket-summary-card,
html:not([data-bs-theme="dark"]) .paket-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#paketSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

html:not([data-bs-theme="dark"]) #paketPage .paket-table-card,
html:not([data-bs-theme="dark"]) .paket-table-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.paket-table-card > .paket-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.paket-table-card > .card-header {
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

.paket-search-wrap {
    position: relative;
    min-width: 170px;
}
.paket-search-wrap input {
    height: 32px;
    border-radius: 6px;
    font-size: 12.5px;
    padding-right: 26px;
}
.paket-search-clear {
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
.paket-search-clear:hover {
    background: #cbd5e1;
    color: #1e293b;
}

table#paketTable.dataTable,
table#paketTable.table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    overflow: hidden !important;
}

table#paketTable thead th {
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
table#paketTable thead th:last-child {
    border-right: none !important;
}

table#paketTable tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #334155 !important;
    border-bottom: 1px solid #edf2f7 !important;
    border-right: 1px solid #edf2f7 !important;
    vertical-align: middle !important;
}
table#paketTable tbody td:last-child {
    border-right: none !important;
}
table#paketTable tbody tr:last-child td {
    border-bottom: none !important;
}
table#paketTable tbody tr:hover td {
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
[data-bs-theme="dark"] #paketPage .paket-summary-card,
[data-bs-theme="dark"] .paket-summary-card,
[data-bs-theme="dark"] #paketPage .paket-table-card,
[data-bs-theme="dark"] .paket-table-card {
    background: #182433 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}
[data-bs-theme="dark"] table#paketTable thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#paketTable.dataTable,
[data-bs-theme="dark"] table#paketTable.table {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#paketTable tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#paketTable tbody tr:hover td {
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
[data-bs-theme="dark"] .paket-search-clear {
    color: #94a3b8;
    background: #334155;
}
</style>

<div class="container-xl mt-1" id="paketPage">

    <!-- Page Header (Standart Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-packages" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Abonelik Paketleri
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Sistemdeki paket tanımları, fiyatlandırma, süre, firma/kullanıcı limitleri ve modül yetkileri
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon paket-header-icon-action" id="paketColvisDropdownBtn" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="paketColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- JS dinamik render -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary shadow-sm paket-header-action btn-add-paket">
                        <i class="ti ti-plus me-1"></i> Yeni Paket Ekle
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle paket-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm">
                            <a class="dropdown-item route-link" href="#" data-page="abonelik-islemleri/list">
                                <i class="ti ti-users icon me-2 text-primary"></i> Aboneler Yönetimi
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
    <div class="row row-cards g-3 mb-3" id="paketSummaryCards">
        <!-- Kart 1: Toplam Paket -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border paket-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM PAKET</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-packages" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_packages, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Aktif: <strong class="text-success"><?= $active_packages ?></strong> | Pasif: <strong class="text-secondary"><?= $total_packages - $active_packages ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm paketleri göster">
                            <input type="radio" name="paket_status_filter" value="" class="status-filter" checked>
                            <span><i class="ti ti-packages"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Aktif Paketler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border paket-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">AKTİF PAKETLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-circle-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-success" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($active_packages, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Satışta / Kullanımda
                        </span>
                        <label class="status-summary-filter mb-0" title="Aktif paketleri göster">
                            <input type="radio" name="paket_status_filter" value="Aktif" class="status-filter">
                            <span><i class="ti ti-check"></i> Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Gizli / Yönetici Özel -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border paket-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">GİZLİ / ÖZEL PAKETLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-eye-off" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-info" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($hidden_packages, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Yalnızca Yönetici Atayabilir
                        </span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;">Özel Planlar</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Ortalama Paket Fiyatı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border paket-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ORTALAMA FİYAT</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-coin" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-warning" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($avg_price, 2, ',', '.') ?> ₺
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Aktif Paket Ortalaması
                        </span>
                        <span class="badge bg-warning-lt fw-semibold" style="font-size: 10px;">Fiyat / Dönem</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Tablo Kartı -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card paket-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-packages" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Paket Listesi</h4>
                                <a href="#" class="btn-add-paket text-primary" title="Yeni Paket Ekle">
                                    <i class="ti ti-circle-plus" style="font-size: 16px;"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.2;">Tanımlı abonelik paketleri, limitler ve yetkiler</p>
                        </div>
                    </div>

                    <!-- Aksiyonlar ve Arama -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Hızlı Arama -->
                        <div class="input-icon paket-search-wrap">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="paket-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="paket-search-clear" class="paket-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="togglePaketSummary" class="btn btn-sm btn-outline-secondary btn-icon paket-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Tablo Kapsayıcısı -->
                <div class="table-responsive paket-table-area">
                    <table class="table table-hover text-nowrap w-100 mb-0" id="paketTable">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">Sıra</th>
                                <th>Paket Adı</th>
                                <th>Fiyat</th>
                                <th>Süre</th>
                                <th>Firma Limiti</th>
                                <th>Kullanıcı Limiti</th>
                                <th>Modüller</th>
                                <th>Özellikler</th>
                                <th>Görünürlük</th>
                                <th style="width: 90px;">Durum</th>
                                <th style="width: 80px;" class="text-end no-export" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($packages as $pkg):
                                $id = Security::encrypt($pkg->id);
                                $fiyat_format = number_format($pkg->fiyat, 2, ',', '.') . ' ₺';
                                $status_badge = (int)$pkg->aktif_mi === 1 
                                    ? '<span class="badge bg-success-lt fw-semibold"><i class="ti ti-check me-1"></i>Aktif</span>' 
                                    : '<span class="badge bg-secondary-lt fw-semibold"><i class="ti ti-x me-1"></i>Pasif</span>';
                                $status_text = (int)$pkg->aktif_mi === 1 ? 'Aktif' : 'Pasif';
                                ?>
                                <tr data-paket-id="<?php echo $id; ?>" data-paket-name="<?php echo htmlspecialchars($pkg->ad, ENT_QUOTES); ?>">
                                    <td class="text-center text-muted small"><?php echo $i; ?></td>
                                    <td>
                                        <div class="fw-bold text-primary"><?php echo htmlspecialchars($pkg->ad); ?></div>
                                    </td>
                                    <td><span class="fw-bold text-dark"><?php echo $fiyat_format; ?></span></td>
                                    <td><span class="badge bg-light text-secondary"><?php echo (int)$pkg->sure; ?> Gün</span></td>
                                    <td><span class="badge bg-blue-lt"><?php echo (int)$pkg->firma_hakki; ?> Firma</span></td>
                                    <td><span class="badge bg-cyan-lt"><?php echo (int)$pkg->alt_kullanici_hakki; ?> Kullanıcı</span></td>
                                    <td>
                                        <?php if (empty($pkg->modul_auth_ids)): ?>
                                            <span class="badge bg-success-lt"><i class="ti ti-check me-1"></i>Tüm Modüller</span>
                                        <?php else:
                                            $pkgModuleIds = array_filter(array_map('intval', explode(',', $pkg->modul_auth_ids)));
                                            $pkgModuleTitles = array_map(function ($mid) use ($moduleTitlesById) {
                                                return $moduleTitlesById[$mid] ?? null;
                                            }, $pkgModuleIds);
                                            $pkgModuleTitles = array_filter($pkgModuleTitles);
                                            $countMod = count($pkgModuleTitles);
                                            ?>
                                            <span class="badge bg-primary-lt" title="<?php echo htmlspecialchars(implode(', ', $pkgModuleTitles)); ?>">
                                                <i class="ti ti-apps me-1"></i><?php echo $countMod; ?> Modül Seçili
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted text-truncate d-inline-block small" style="max-width: 220px;" title="<?php echo htmlspecialchars($pkg->ozellikler ?? ''); ?>">
                                            <?php echo htmlspecialchars($pkg->ozellikler ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo ($pkg->kullaniciya_goster_mi ?? 1) == 1
                                            ? '<span class="badge bg-info-lt"><i class="ti ti-eye me-1"></i>Görünür</span>'
                                            : '<span class="badge bg-warning-lt"><i class="ti ti-eye-off me-1"></i>Gizli (Özel)</span>'; ?>
                                    </td>
                                    <td>
                                        <?php echo $status_badge; ?>
                                        <span class="d-none"><?php echo $status_text; ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="d-flex align-items-center justify-content-end gap-1">
                                            <button type="button" class="btn btn-sm btn-outline-primary btn-icon btn-edit-paket"
                                                    title="Düzenle"
                                                    data-id="<?php echo $id; ?>"
                                                    data-ad="<?php echo htmlspecialchars($pkg->ad); ?>"
                                                    data-fiyat="<?php echo htmlspecialchars($pkg->fiyat); ?>"
                                                    data-sure="<?php echo (int)$pkg->sure; ?>"
                                                    data-firma_hakki="<?php echo (int)$pkg->firma_hakki; ?>"
                                                    data-alt_kullanici_hakki="<?php echo (int)$pkg->alt_kullanici_hakki; ?>"
                                                    data-ozellikler="<?php echo htmlspecialchars($pkg->ozellikler ?? ''); ?>"
                                                    data-aktif_mi="<?php echo (int)$pkg->aktif_mi; ?>"
                                                    data-kullaniciya_goster_mi="<?php echo (int)($pkg->kullaniciya_goster_mi ?? 1); ?>"
                                                    style="width: 28px; height: 28px; padding: 0;">
                                                <i class="ti ti-edit" style="font-size: 13px;"></i>
                                            </button>
                                            <a href="#" class="btn btn-sm btn-outline-info btn-icon route-link"
                                               title="Modülleri Düzenle"
                                               data-page="abonelik-islemleri/paket-moduller&id=<?php echo $id; ?>"
                                               style="width: 28px; height: 28px; padding: 0;">
                                                <i class="ti ti-apps" style="font-size: 13px;"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-icon delete-paket"
                                                    title="Sil"
                                                    data-id="<?php echo $id; ?>"
                                                    style="width: 28px; height: 28px; padding: 0;">
                                                <i class="ti ti-trash" style="font-size: 13px;"></i>
                                            </button>
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

<!-- Paket Ekle / Güncelle Modalı (Standart Tabler ERP Modal) -->
<div class="modal modal-blur fade" id="paketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 14px; overflow: hidden; border: 0; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header py-3 px-4 bg-light-subtle border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary" style="width: 40px; height: 40px;">
                        <i class="ti ti-package" style="font-size: 20px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="paketModalTitle" style="font-size: 1.15rem;">
                            <span>Yeni Paket Ekle</span>
                        </h4>
                        <div class="text-secondary small">Abonelik paketi bilgileri, fiyat ve erişim limitleri</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" id="paketForm">
                <div class="modal-body p-4 bg-white">
                    <input type="hidden" name="id" id="paket_id" value="0">
                    <input type="hidden" name="action" value="savePackage">

                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label required fw-semibold">Paket Adı</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-tag text-muted"></i></span>
                                <input type="text" name="ad" id="paket_ad" class="form-control" placeholder="Örn: Profesyonel Plan" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Fiyat (₺)</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-currency-lira text-muted"></i></span>
                                <input type="number" step="0.01" min="0" name="fiyat" id="paket_fiyat" class="form-control" placeholder="0.00" required>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Süre (Gün)</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar text-muted"></i></span>
                                <input type="number" min="1" name="sure" id="paket_sure" class="form-control" placeholder="30" required value="30">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Firma Limiti</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-building text-muted"></i></span>
                                <input type="number" min="1" name="firma_hakki" id="paket_firma_hakki" class="form-control" placeholder="10" required value="30">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label required fw-semibold">Alt Kullanıcı Limiti</label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-users text-muted"></i></span>
                                <input type="number" min="0" name="alt_kullanici_hakki" id="paket_alt_kullanici_hakki" class="form-control" placeholder="3" required value="3">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="card border rounded-3 p-3 bg-light-subtle">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="fw-semibold text-dark">Kullanıcılara Satın Alma Ekranında Göster</div>
                                        <div class="text-secondary small">Kapatılırsa paket müşterinin ekranında listelenmez; sadece yönetici manuel tanımlayabilir.</div>
                                    </div>
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="paket_kullaniciya_goster_mi" name="kullaniciya_goster_mi" value="1" checked>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold">Özellikler & Açıklama</label>
                            <textarea name="ozellikler" id="paket_ozellikler" rows="2" class="form-control" placeholder="Paket avantajları, ek özellikler vb."></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label required fw-semibold">Paket Durumu</label>
                            <div class="form-selectgroup w-100">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="aktif_mi" value="1" class="form-selectgroup-input" checked id="radio_aktif">
                                    <span class="form-selectgroup-label text-success fw-semibold"><i class="ti ti-check me-1"></i> Aktif</span>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="aktif_mi" value="0" class="form-selectgroup-input" id="radio_pasif">
                                    <span class="form-selectgroup-label text-secondary fw-semibold"><i class="ti ti-x me-1"></i> Pasif</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top">
                    <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold" id="savePackageBtn">
                        <i class="ti ti-device-floppy icon me-1"></i> Paketi Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var $summaryToggle = $('#togglePaketSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('paketler-summary-collapsed');
        $summaryToggle
            .attr('aria-expanded', String(!isCollapsed))
            .attr('aria-label', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle')
            .attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
        $summaryToggle.find('i')
            .removeClass('ti-chevron-up ti-chevron-down')
            .addClass(isCollapsed ? 'ti-chevron-down' : 'ti-chevron-up');
    }

    $summaryToggle.on('click', function() {
        var willCollapse = !document.documentElement.classList.contains('paketler-summary-collapsed');
        document.documentElement.classList.toggle('paketler-summary-collapsed', willCollapse);
        try {
            localStorage.setItem('paketler_summary_collapsed', willCollapse ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });
    syncSummaryToggle();

    // DataTable Initialization
    var $paketTable = $('#paketTable');
    if ($.fn.DataTable.isDataTable($paketTable[0])) {
        $paketTable.DataTable().destroy();
    }

    function renderPaketColvisMenu(dtApi) {
        var $colvisMenu = $('#paketColvisMenu');
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

    var table = $paketTable.DataTable({
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
                window.initDataTableColumnFilters($('#paketTable'), api);
            }
            renderPaketColvisMenu(api);
        }
    });

    // Fast Instant Search
    var $fastSearch = $('#paket-fast-search');
    var $searchClear = $('#paket-search-clear');

    $fastSearch.on('input', function () {
        var val = $(this).val();
        table.search(val).draw();
        $searchClear.toggleClass('d-none', !val);
    });

    $searchClear.on('click', function () {
        $fastSearch.val('').trigger('input').focus();
    });

    // Status Summary Radio Filters
    $('input[name="paket_status_filter"]').on('change', function () {
        var filterVal = $(this).val();
        // Sütun 9: Durum sütunu
        table.column(9).search(filterVal ? '^' + filterVal : '', true, false).draw();
    });

    // Add button handler
    $(document).on('click', '.btn-add-paket', function(e) {
        e.preventDefault();
        $('#paketForm')[0].reset();
        $('#paket_id').val('0');
        $('#radio_aktif').prop('checked', true);
        $('#paket_kullaniciya_goster_mi').prop('checked', true);
        $('#paketModalTitle span').text('Yeni Paket Ekle');
        $('#paketModal').modal('show');
    });

    // Edit button handler
    $(document).on('click', '.btn-edit-paket', function(e) {
        e.preventDefault();
        $('#paketForm')[0].reset();

        var id = $(this).data('id');
        var ad = $(this).data('ad');
        var fiyat = $(this).data('fiyat');
        var sure = $(this).data('sure');
        var firma_hakki = $(this).data('firma_hakki');
        var alt_kullanici_hakki = $(this).data('alt_kullanici_hakki');
        var ozellikler = $(this).data('ozellikler');
        var aktif_mi = $(this).data('aktif_mi');
        var kullaniciya_goster_mi = $(this).data('kullaniciya_goster_mi');

        $('#paket_id').val(id);
        $('#paket_ad').val(ad);
        $('#paket_fiyat').val(fiyat);
        $('#paket_sure').val(sure);
        $('#paket_firma_hakki').val(firma_hakki);
        $('#paket_alt_kullanici_hakki').val(alt_kullanici_hakki);
        $('#paket_ozellikler').val(ozellikler);
        if (parseInt(aktif_mi) === 1) {
            $('#radio_aktif').prop('checked', true);
        } else {
            $('#radio_pasif').prop('checked', true);
        }
        $('#paket_kullaniciya_goster_mi').prop('checked', String(kullaniciya_goster_mi) === '1');

        $('#paketModalTitle span').text('Paket Bilgilerini Güncelle');
        $('#paketModal').modal('show');
    });

    // Submit handler
    $(document).on('submit', '#paketForm', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(form[0]);
        var btn = $('#savePackageBtn');
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...');

        fetch('/api/abonelik-islemleri/paketler.php', {
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
                    $('#paketModal').modal('hide');
                    window.location.reload();
                }
            });
        })
        .catch(function(error) {
            Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
        })
        .finally(function() {
            btn.prop('disabled', false).html('<i class="ti ti-device-floppy icon me-1"></i> Paketi Kaydet');
        });
    });

    // Delete handler
    $(document).on("click", ".delete-paket", function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Silmek İstediğinize Emin Misiniz?',
            text: 'Seçilen abonelik paketi silinecektir! Bu paketi kullanan abonelikler etkilenebilir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then(function(result) {
            if (result.isConfirmed) {
                var formData = new FormData();
                formData.append('action', 'deletePackage');
                formData.append('id', id);

                fetch('/api/abonelik-islemleri/paketler.php', {
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
    $(document).on('contextmenu', '#paketTable tbody tr, #paketTable tbody td', function(e) {
        var $tr = $(this).closest('tr');
        if (!$tr.length) return;

        var $editBtn = $tr.find('.btn-edit-paket');
        var $deleteBtn = $tr.find('.delete-paket');
        var paketId = $tr.attr('data-paket-id') || $editBtn.attr('data-id') || $deleteBtn.attr('data-id');
        var paketName = $tr.attr('data-paket-name') || $editBtn.attr('data-ad') || $tr.find('td').eq(1).text().trim() || 'Paket İşlemleri';

        if (!paketId) return;

        e.preventDefault();
        $('#paketTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-package me-1"></i> ${$('<div>').text(paketName).html()}</div>
            <a href="#" class="cm-action-edit-pkg" data-id="${paketId}">
                <i class="ti ti-edit text-primary"></i> Paketi Düzenle
            </a>
            <a href="#" class="route-link" data-page="abonelik-islemleri/paket-moduller&id=${paketId}">
                <i class="ti ti-apps text-info"></i> Modülleri Düzenle
            </a>
            <a href="#" class="route-link" data-page="abonelik-islemleri/satin-alma-islemleri">
                <i class="ti ti-receipt text-success"></i> Bu Paketin Satışları
            </a>
            <div class="cm-divider"></div>
            <a href="#" class="cm-danger cm-action-delete-pkg" data-id="${paketId}">
                <i class="ti ti-trash"></i> Paketi Sil
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

    $(document).on('click', '.cm-action-edit-pkg', function(e) {
        e.preventDefault();
        $('#customContextMenu').hide();
        var id = $(this).data('id');
        $('button.btn-edit-paket[data-id="' + id + '"]').trigger('click');
    });

    $(document).on('click', '.cm-action-delete-pkg', function(e) {
        e.preventDefault();
        $('#customContextMenu').hide();
        var id = $(this).data('id');
        $('button.delete-paket[data-id="' + id + '"]').trigger('click');
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#paketTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#paketTable tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#paketTable tbody tr').removeClass('context-menu-active');
    });
});
</script>
