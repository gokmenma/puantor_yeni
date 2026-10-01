<?php
$perm->checkAuthorize("personnel_page");
require_once ROOT . '/Model/Persons.php';
$personsModel = new Persons();
$firm_id = (int)($_SESSION['firm_id'] ?? 0);
$stats = $personsModel->getPersonnelStats($firm_id);
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'personnel-summary-collapsed',
            localStorage.getItem('persons_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.personnel-summary-collapsed #personnelSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>
<div class="container-xl mt-1" id="personnelPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-users" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Personel Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Sistemdeki tüm personeller, özlük bilgileri, durum takibi ve bordro işlemleri
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon persons-header-icon-action" id="personsColvisDropdownBtn" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="personsColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxes will be rendered dynamically by JS -->
                        </div>
                    </div>
                    <a href="#" class="btn btn-sm btn-primary route-link shadow-sm persons-header-action" data-page="persons/manage">
                        <i class="ti ti-plus me-1"></i> Yeni Personel Ekle
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle persons-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="/pages/persons/to-xls.php" target="_blank" class="dropdown-item">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <a class="dropdown-item route-link"
                                data-tooltip="Personelleri Excel dosyasından yükleyin" data-tooltip-location="left"
                                href="#" data-page="persons/xls/person-load">
                                <i class="ti ti-upload icon me-2"></i> Excelden Yükle
                            </a>
                            <a class="dropdown-item" data-tooltip="Günlük Ücretleri toplu olarak güncelleyin"
                                data-tooltip-location="left" href="#" data-bs-toggle="modal"
                                data-bs-target="#bulk-wages-modal">
                                <i class="ti ti-user-dollar icon me-2"></i> Ücretleri Güncelle
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="/pages/persons/to-pdf.php" target="_blank" class="dropdown-item">
                                <i class="ti ti-file-type-pdf icon me-2 text-danger"></i> PDF Raporu Al
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="personnelSummaryCards">
        <!-- Kart 1: Toplam Personel -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border personnel-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM PERSONEL</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats['total'], 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Mavi: <strong><?= $stats['daily_wage_count'] ?></strong> | Beyaz: <strong><?= $stats['monthly_wage_count'] ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm personelleri göster">
                            <input type="radio" name="person_status" value="" class="status-filter">
                            <span><i class="ti ti-users"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Aktif Personeller -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border personnel-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">AKTİF PERSONELLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-hourglass-low" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats['active'], 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Çalışan: <strong class="text-warning"><?= $stats['active'] ?> Personel</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Aktif personelleri göster">
                            <input type="radio" name="person_status" value="Aktif" class="status-filter" checked>
                            <span><i class="ti ti-user-check"></i> Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Pasif / Ayrılan -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border personnel-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">PASİF / AYRILAN</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats['passive'], 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Aktif Oranı: <strong class="text-success">%<?= $stats['active_percent'] ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Pasif personelleri göster">
                            <input type="radio" name="person_status" value="Pasif" class="status-filter">
                            <span><i class="ti ti-user-x"></i> Pasif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Bu Ay Giriş Yapan -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border personnel-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BU AY GİRİŞ YAPAN</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-calendar" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats['this_month_hires'], 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Yeni Başlayanlar
                        </span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;"><?= date('m/Y') ?> Dönemi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card personnel-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px;">
                            <i class="ti ti-list" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Personel Listesi</h4>
                                <a href="#" class="btn-card-header-add route-link" data-page="persons/manage" data-tooltip="Yeni Personel Ekle">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık arama, sütun filtreleme ve personel yönetimi</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <button type="button" id="btnDeleteSelected" class="btn btn-sm btn-danger d-none">
                            <i class="ti ti-trash icon me-1"></i> Seçilenleri Sil
                        </button>

                        <!-- Fast Instant Search -->
                        <div class="input-icon persons-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="persons-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="persons-search-clear" class="persons-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="togglePersonnelSummary" class="btn btn-sm btn-outline-secondary btn-icon persons-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>

                    </div>
                </div>

                <!-- Table Responsive Container (Seamless inside card) -->
                <div class="table-responsive persons-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="persons" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false"><input type="checkbox"
                                        class="form-check-input select-all-persons"></th>
                                <th style="width:5%">Sıra</th>
                                <th>Adı Soyadı</th>
                                <th>TC Kimlik No</th>
                                <th>Firma Adı</th>
                                <th>Ücret Türü</th>
                                <th>İşe Giriş Tarihi</th>
                                <th>İşten Çıkış Tarihi</th>
                                <th>Telefon</th>
                                <th>E-posta</th>
                                <th>IBAN Numarası</th>
                                <th>Grubu</th>
                                <th>Görevi</th>
                                <th>Ekip</th>
                                <th>Proje</th>
                                <th>Günlük/Aylık Ücretİ</th>
                                <th>Durumu</th>
                                <th>Güncel Bakiyesi</th>
                                <th>Adres</th>
                                <th>Açıklama</th>
                                <th style="width:95px; min-width:95px;" class="no-export text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.persons-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.persons-header-icon-action,
.persons-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.persons-header-icon-action i,
.persons-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

html:not([data-bs-theme="dark"]) #personnelPage .personnel-summary-card,
html:not([data-bs-theme="dark"]) .personnel-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#personnelSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

html:not([data-bs-theme="dark"]) #personnelPage .personnel-table-card,
html:not([data-bs-theme="dark"]) .personnel-table-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.personnel-table-card > .persons-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.personnel-table-card > .card-header {
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
    transition: all .15s ease;
}
.status-summary-filter:hover span,
.status-summary-filter input:focus-visible + span {
    border-color: #94a3b8;
    color: #334155;
}
.status-summary-filter input:checked + span {
    border-color: #206bc4;
    color: #206bc4;
    background: rgba(32, 107, 196, .08);
    box-shadow: 0 0 0 1px rgba(32, 107, 196, .08);
}

.persons-search-wrap { position: relative; }
#persons-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.persons-search-wrap,
.persons-search-wrap.input-icon {
    height: 32px !important;
}
.persons-search-clear {
    position: absolute;
    top: 50%;
    right: 6px;
    z-index: 3;
    display: inline-flex;
    width: 22px;
    height: 22px;
    padding: 0;
    align-items: center;
    justify-content: center;
    transform: translateY(-50%);
    border: 0;
    border-radius: 50%;
    color: #64748b;
    background: #f1f5f9;
    cursor: pointer;
}
.persons-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#persons_wrapper,
div.dt-container,
div.dt-container .dt-layout-row.dt-layout-table,
div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell {
    height: auto !important;
    min-height: 0 !important;
    min-height: unset !important;
    max-height: none !important;
    flex-grow: 0 !important;
    border: none !important;
    box-shadow: none !important;
}

div.dt-container .dt-layout-row.dt-layout-table {
    padding: 0 !important;
    margin: 0 !important;
}

div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell {
    padding: 0 !important;
    margin: 0 !important;
}

/* Tek Çerçeve (Kart ile Bütünleşik Tablo) */
table#persons.data-table,
table#persons.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#persons.data-table tbody,
table#persons.dataTable tbody,
table#persons.data-table tbody tr:last-child,
table#persons.dataTable tbody tr:last-child,
#persons_wrapper .dt-layout-table,
#persons_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#persons.data-table thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 11.5px !important;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}
table#persons.data-table thead th:last-child {
    border-right: none !important;
}

/* Sütun Başlığı İçi Filtre Butonu ve Düzeni */
table#persons.data-table thead th .dt-header-content {
    min-height: 24px;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#persons.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#persons.data-table .form-check-input.select-all-persons,
table#persons.data-table .form-check-input.person-checkbox {
    width: 18px !important;
    min-width: 18px !important;
    height: 18px !important;
    min-height: 18px !important;
    margin: 0 !important;
    padding: 0 !important;
    vertical-align: middle !important;
    border-radius: 5px !important;
}
table#persons.data-table td.actions-column .btn.btn-sm.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
table#persons.data-table td.actions-column .btn.btn-sm.btn-icon i {
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    font-size: 13px !important;
}
table#persons.data-table tbody td:last-child {
    border-right: none !important;
}
table#persons.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#persons.dataTable > tbody > tr:last-child > *,
table#persons.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#persons.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı - Doğal Bitişik Yerleşim */
#persons_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#persons_wrapper .dt-layout-row:has(.dt-paging),
div#persons_wrapper .dt-layout-row:has(.dt-info) {
    margin: 0 !important;
    margin-top: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
    position: static !important;
    flex-shrink: 0 !important;
}

/* Modern Select Styling (DataTables Length) */
.dataTables_length select,
.dt-length select {
    display: inline-block !important;
    width: auto !important;
    min-width: 65px !important;
    height: 30px !important;
    padding: 3px 26px 3px 8px !important;
    font-size: 12px !important;
    font-weight: 500 !important;
    line-height: 1.5 !important;
    color: #334155 !important;
    background-color: #ffffff !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%2364748b' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 7px center !important;
    background-size: 10px 8px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 6px !important;
    outline: none !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    cursor: pointer !important;
    appearance: none !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
}
.dataTables_length select:hover,
.dt-length select:hover {
    border-color: #94a3b8 !important;
}
.dataTables_length select:focus,
.dt-length select:focus {
    border-color: #206bc4 !important;
    box-shadow: 0 0 0 2px rgba(32, 107, 196, 0.15) !important;
}

/* Dark Mode */
[data-bs-theme="dark"] table#persons.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#persons.data-table,
[data-bs-theme="dark"] table#persons.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#persons.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#persons.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#persons.data-table tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] .dataTables_length select,
[data-bs-theme="dark"] .dt-length select {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
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
[data-bs-theme="dark"] .persons-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] #personnelPage .personnel-summary-card,
[data-bs-theme="dark"] .personnel-summary-card,
[data-bs-theme="dark"] #personnelPage .personnel-table-card,
[data-bs-theme="dark"] .personnel-table-card {
    background: #182433 !important;
    border-color: rgba(255, 255, 255, 0.08) !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

#persons th:last-child,
#persons td:last-child {
    width: 95px !important;
    min-width: 95px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 12px !important;
}

.dropdown-toggle-no-caret::after,
.btn-icon.dropdown-toggle::after,
td .dropdown-toggle::after {
    display: none !important;
    content: none !important;
}
</style>

<script>
$(document).ready(function() {
    var $summaryToggle = $('#togglePersonnelSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('personnel-summary-collapsed');
        $summaryToggle
            .attr('aria-expanded', String(!isCollapsed))
            .attr('aria-label', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle')
            .attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
        $summaryToggle.find('i')
            .toggleClass('ti-chevron-up', !isCollapsed)
            .toggleClass('ti-chevron-down', isCollapsed);
    }

    syncSummaryToggle();

    $summaryToggle.on('click', function() {
        var isCollapsed = document.documentElement.classList.toggle('personnel-summary-collapsed');
        try {
            localStorage.setItem('persons_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    var currentStatus = 'active';

    var table = $('#persons').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        colReorder: true,
        searchDelay: 400,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[2, 'asc']],
        ajax: {
            url: 'api/persons/list.php',
            type: 'POST',
            data: function(data) {
                data.status = currentStatus;
            }
        },
        columnDefs: [
            { targets: [0, 20], orderable: false, searchable: false },
            { targets: 0, className: 'no-export' },
            { targets: 1, className: 'text-center' },
            { targets: 20, width: '95px', className: 'text-end no-export actions-column' }
        ],
        language: {
            url: 'src/tr.json',
            processing: '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...'
        },
        initComplete: function() {
            var api = this.api();
            if (typeof window.initDataTableColumnFilters === 'function') {
                window.initDataTableColumnFilters($('#persons'), api);
            }
            if (typeof window.initPuantorDTManager === 'function') {
                window.initPuantorDTManager($('#persons'), api);
            }
            renderPersonsColvisMenu();
        },
        drawCallback: function() {
            $('.select-all-persons').prop('checked', false);
            if (typeof toggleBulkDeleteButton === 'function') {
                toggleBulkDeleteButton();
            }
        }
    });

    // Personel Tablosu Sütun Konfigürasyonu
    var personColumnConfig = {
        2: 'Adı Soyadı',
        3: 'TC Kimlik No',
        4: 'Firma Adı',
        5: 'Ücret Türü',
        6: 'İşe Giriş Tarihi',
        7: 'İşten Çıkış Tarihi',
        8: 'Telefon',
        9: 'E-posta',
        10: 'IBAN Numarası',
        11: 'Grubu',
        12: 'Görevi',
        13: 'Ekip',
        14: 'Proje',
        15: 'Günlük/Aylık Ücreti',
        16: 'Durumu',
        17: 'Güncel Bakiyesi',
        18: 'Adres',
        19: 'Açıklama'
    };

    // Dinamik Sütun Menüsü Oluşturucu (Gizli sütunlarda da %100 güvenli okuma)
    function renderPersonsColvisMenu() {
        var menuHtml = '';
        var settings = table ? table.settings()[0] : null;

        $.each(personColumnConfig, function (origIdxStr, label) {
            var origIdx = parseInt(origIdxStr, 10);
            var isVisible = true;

            if (settings && settings.aoColumns) {
                for (var c = 0; c < settings.aoColumns.length; c++) {
                    var colCfg = settings.aoColumns[c];
                    var cOrig = colCfg._crOriginalIdx !== undefined ? colCfg._crOriginalIdx : c;
                    if (cOrig === origIdx) {
                        isVisible = colCfg.bVisible !== false;
                        break;
                    }
                }
            } else if (table) {
                try {
                    isVisible = table.column(origIdx).visible();
                } catch(e) {
                    isVisible = true;
                }
            }

            menuHtml += `
                <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size: 0.85rem;">
                    <div class="form-check mb-0 w-100">
                        <input class="form-check-input persons-col-trigger" type="checkbox" id="colCheck_${origIdx}" data-column="${origIdx}" data-orig-idx="${origIdx}" ${isVisible ? "checked" : ""}>
                        <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">
                            ${label}
                        </span>
                    </div>
                </label>`;
        });

        menuHtml += `
            <div class="dropdown-divider my-1"></div>
            <button type="button" class="dropdown-item text-danger py-1.5 px-3 rounded-2" id="resetTableColumnsBtn" style="font-size: 0.8rem;">
                <i class="ti ti-rotate-2 me-1"></i> Görünümü Sıfırla
            </button>
        `;

        $('#personsColvisMenu').html(menuHtml);
    }

    // İlk render ve dropdown açıldığında anında güncelle
    renderPersonsColvisMenu();
    $('#personsColvisDropdownBtn').on('click', function () {
        renderPersonsColvisMenu();
    });
    $('#personsColvisDropdownBtn').parent().on('show.bs.dropdown', function () {
        renderPersonsColvisMenu();
    });

    // Görünümü Sıfırla Butonu
    $(document).on('click', '#resetTableColumnsBtn', function(e) {
        e.preventDefault();
        if (typeof window.resetPuantorDTState === 'function') {
            window.resetPuantorDTState($('#persons'), table, function() {
                table.columns().visible(true, true);
                table.columns.adjust().draw(false);
                renderPersonsColvisMenu();
            });
        }
    });

    $(document).on('click', '#personsColvisMenu', function(e) {
        e.stopPropagation();
    });

    $('.status-filter').on('change', function() {
        var val = $(this).val();
        currentStatus = val === 'Aktif' ? 'active' : (val === 'Pasif' ? 'passive' : '');
        table.ajax.reload(null, true);
    });

    // Hızlı Genel Arama Inputu
    var searchTimer = null;
    $('#persons-fast-search').on('input', function() {
        var val = this.value;
        $('#persons-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            table.search(val).draw();
        }, 300);
    });

    $('#persons-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#persons-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        table.search('').draw();
    });

    // Tabloda Sağ Tık (Custom Context Menu)
    $(document).on('contextmenu', '#persons tbody tr', function(e) {
        var $tr = $(this);
        var $editBtn = $tr.find('a[data-page*="persons/manage"]');
        var $deleteBtn = $tr.find('.delete-person');

        if (!$editBtn.length && !$deleteBtn.length) return;

        e.preventDefault();
        $('#persons tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var personName = $tr.find('td').eq(2).text().trim() || 'Personel İşlemleri';
        var editPage = $editBtn.attr('data-page') || '';
        var deleteId = $deleteBtn.attr('data-id') || '';

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-user me-1"></i> ${$('<div>').text(personName).html()}</div>
            ${editPage ? `<a href="#" class="route-link" data-page="${editPage}"><i class="ti ti-edit"></i> Detay / Düzenle</a>` : ''}
            ${deleteId ? `<a href="#" class="route-link" data-page="persons/statement&id=${deleteId}"><i class="ti ti-receipt"></i> Hesap Ekstresi</a>` : ''}
            <a href="#" class="route-link" data-page="puantaj/list"><i class="ti ti-calendar"></i> Puantaj Sayfası</a>
            <div class="cm-divider"></div>
            ${deleteId ? `<a href="#" class="cm-danger delete-person" data-id="${deleteId}"><i class="ti ti-trash"></i> Personeli Sil</a>` : ''}
        `;

        $contextMenu.html(menuHtml);
        $contextMenu.css({ display: 'block', opacity: 0 });

        var menuWidth = $contextMenu.outerWidth();
        var menuHeight = $contextMenu.outerHeight();
        var clickX = e.clientX;
        var clickY = e.clientY;
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();

        var posX = (clickX + menuWidth > windowWidth) ? windowWidth - menuWidth - 10 : clickX;
        var posY = (clickY + menuHeight > windowHeight) ? windowHeight - menuHeight - 10 : clickY;

        $contextMenu.css({
            top: posY + 'px',
            left: posX + 'px',
            opacity: 1
        });
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#persons tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#persons tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#persons tbody tr').removeClass('context-menu-active');
    });
});
</script>

<?php include ROOT . '/pages/payroll/content/bulk-wages-modal.php'; ?>
