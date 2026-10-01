<?php
require_once "Model/RolesModel.php";
require_once "Model/UserModel.php";
require_once "App/Helper/security.php";
require_once "App/Helper/helper.php";

use App\Helper\Security;
use App\Helper\Helper;

$perm->checkAuthorize("permission_groups");

$firm_id = (int)($_SESSION['firm_id'] ?? 0);
$roleObj = new Roles();
$roles = $roleObj->getRolesByFirm($firm_id);

// Kullanıcı ve Rol İstatistikleri
$userModel = new UserModel();
$users = $userModel->getUsersByFirm($firm_id);

$roleUserCounts = [];
$totalUsersWithRole = 0;
foreach ($users as $u) {
    if (!empty($u->user_roles)) {
        $assignedRoleIds = array_filter(array_map('trim', explode(',', (string)$u->user_roles)));
        if (!empty($assignedRoleIds)) {
            $totalUsersWithRole++;
            foreach ($assignedRoleIds as $rId) {
                $roleUserCounts[$rId] = ($roleUserCounts[$rId] ?? 0) + 1;
            }
        }
    }
}

$total_roles = count($roles);
$system_roles = 0;
$custom_roles = 0;
$active_roles = 0;

foreach ($roles as $r) {
    if ((int)$r->main_role === 1) {
        $system_roles++;
    } else {
        $custom_roles++;
    }
    if ((int)$r->isActive === 1 || (string)$r->isActive === 'Aktif' || (string)$r->isActive === '1') {
        $active_roles++;
    }
}
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'roles-summary-collapsed',
            localStorage.getItem('roles_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.roles-summary-collapsed #rolesSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}

.roles-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.roles-header-icon-action,
.roles-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}

.roles-header-icon-action i,
.roles-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

html:not([data-bs-theme="dark"]) #rolesPage .roles-summary-card,
html:not([data-bs-theme="dark"]) .roles-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#rolesSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

html:not([data-bs-theme="dark"]) #rolesPage .roles-table-card,
html:not([data-bs-theme="dark"]) .roles-table-card {
    border: 1px solid #dbe3ec !important;
    border-radius: 12px !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
    background: #ffffff;
}

.roles-table-card > .card-header {
    border-bottom: 0 !important;
}

/* Tablo Kapsayıcısı ve Tek Çerçeve Standardı */
.roles-table-card > .roles-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 0 8px !important;
    padding: 0 !important;
    border: none !important;
    overflow-x: auto !important;
}

table#roleTable.data-table,
table#roleTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}

table#roleTable thead th {
    font-size: 11.5px !important;
    font-weight: 600 !important;
    color: #475569 !important;
    background: #f8fafc !important;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}

table#roleTable thead th:last-child {
    border-right: none !important;
}

table#roleTable tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}

table#roleTable tbody td:last-child {
    border-right: none !important;
}

table#roleTable tbody tr:last-child td {
    border-bottom: none !important;
}

table#roleTable tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Sayfalama ve Alt Bilgi Alanı (Tablo Dışında, Kartın Altında) */
#roleTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
#rolesPage .card-table-footer {
    margin: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}

div.dt-container .dt-layout-row.dt-layout-table {
    padding: 0 !important;
    margin: 0 !important;
}

div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell {
    padding: 0 !important;
    margin: 0 !important;
}

/* Sayfa Uzunluğu (PageLength) Seçici Stili */
.dataTables_length,
.dt-length {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    font-size: 12px !important;
    color: #64748b !important;
}

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

/* Bilgi ve Sayfalama Stilleri */
.dt-info,
.dataTables_info {
    font-size: 12px !important;
    color: #64748b !important;
    padding: 0 !important;
    margin: 0 !important;
}

.dt-paging,
.dataTables_paginate {
    padding: 0 !important;
    margin: 0 !important;
}

.dt-paging .pagination,
.dataTables_paginate .pagination {
    margin: 0 !important;
    gap: 4px;
}

.dt-paging .page-item .page-link,
.dataTables_paginate .paginate_button {
    border-radius: 6px !important;
    font-size: 12px !important;
    padding: 4px 10px !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
    background: #ffffff !important;
    cursor: pointer !important;
    text-decoration: none !important;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 30px;
    height: 30px;
}

.dt-paging .page-item.active .page-link,
.dataTables_paginate .paginate_button.current {
    background-color: #206bc4 !important;
    border-color: #206bc4 !important;
    color: #ffffff !important;
    font-weight: 600;
}

.dt-paging .page-item.disabled .page-link,
.dataTables_paginate .paginate_button.disabled {
    opacity: 0.5;
    cursor: not-allowed !important;
    background: #f8fafc !important;
}

#rolesPage .role-status-filter {
    cursor: pointer;
}

#rolesPage .role-status-filter input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

#rolesPage .role-status-filter span {
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

#rolesPage .role-status-filter:hover span,
#rolesPage .role-status-filter input:focus-visible + span {
    border-color: #94a3b8;
    color: #334155;
}

#rolesPage .role-status-filter input:checked + span {
    border-color: #206bc4;
    color: #206bc4;
    background: rgba(32, 107, 196, .08);
    box-shadow: 0 0 0 1px rgba(32, 107, 196, .08);
}

.roles-search-wrap {
    position: relative;
    height: 32px !important;
}

#roles-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 30px 4px 32px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}

.roles-search-clear {
    position: absolute;
    top: 50%;
    right: 6px;
    z-index: 3;
    display: inline-flex;
    width: 20px;
    height: 20px;
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

.roles-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

#rolesPage .btn-action-icon {
    width: 28px;
    height: 28px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
}

#rolesPage .btn-action-icon i {
    font-size: 13px;
}

#rolesActiveFilterBar {
    width: calc(100% - 16px);
    margin: 0 8px 8px;
    background: #f8fafc;
    border: 1px solid #dbe3ec;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 12px;
}

/* Dark Mode Desteği */
[data-bs-theme="dark"] #rolesPage .roles-summary-card,
[data-bs-theme="dark"] #rolesPage .roles-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

[data-bs-theme="dark"] table#roleTable.data-table,
[data-bs-theme="dark"] table#roleTable.dataTable {
    border-color: #334155 !important;
}

[data-bs-theme="dark"] table#roleTable thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}

[data-bs-theme="dark"] table#roleTable tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}

[data-bs-theme="dark"] table#roleTable tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}

[data-bs-theme="dark"] .dataTables_length select,
[data-bs-theme="dark"] .dt-length select {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}

[data-bs-theme="dark"] .dt-paging .page-item .page-link,
[data-bs-theme="dark"] .dataTables_paginate .paginate_button {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #94a3b8 !important;
}

[data-bs-theme="dark"] .dt-paging .page-item.active .page-link,
[data-bs-theme="dark"] .dataTables_paginate .paginate_button.current {
    background-color: #206bc4 !important;
    color: #ffffff !important;
}
/* Sağ Tık Context Menu */
.role-context-menu {
    position: fixed;
    z-index: 9999;
    display: none;
    min-width: 200px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 10px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.12), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    padding: 6px;
    font-size: 13px;
    user-select: none;
    animation: contextMenuFadeIn .12s ease;
}

@keyframes contextMenuFadeIn {
    from { opacity: 0; transform: scale(.96) translateY(-4px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

[data-bs-theme="dark"] .role-context-menu {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.45);
}

.role-context-menu .rcm-header {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.4px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 220px;
}

[data-bs-theme="dark"] .role-context-menu .rcm-header {
    border-bottom-color: #334155;
    color: #94a3b8;
}

.role-context-menu a {
    display: flex;
    align-items: center;
    padding: 6px 10px;
    color: #334155;
    text-decoration: none;
    border-radius: 7px;
    transition: background-color 0.12s;
    font-weight: 500;
    gap: 8px;
    cursor: pointer;
}

[data-bs-theme="dark"] .role-context-menu a {
    color: #e2e8f0;
}

.role-context-menu a i {
    font-size: 15px;
    width: 18px;
    text-align: center;
    flex-shrink: 0;
}

.role-context-menu a:hover {
    background-color: #f1f5f9;
    color: #206bc4;
}

[data-bs-theme="dark"] .role-context-menu a:hover {
    background-color: #334155;
    color: #38bdf8;
}

.role-context-menu .rcm-divider {
    height: 1px;
    background-color: #e2e8f0;
    margin: 4px 2px;
}

[data-bs-theme="dark"] .role-context-menu .rcm-divider {
    background-color: #334155;
}

.role-context-menu a.rcm-danger {
    color: #ef4444;
}

.role-context-menu a.rcm-danger:hover {
    background-color: #fef2f2;
    color: #dc2626;
}

[data-bs-theme="dark"] .role-context-menu a.rcm-danger:hover {
    background-color: rgba(239, 68, 68, 0.15);
    color: #f87171;
}

#roleTable tbody tr.rcm-active td {
    background-color: rgba(32, 107, 196, 0.06) !important;
}
</style>

<div class="container-xl mt-1" id="rolesPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-shield-lock" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Yetki Grupları (Rol Yönetimi)
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Kullanıcı rolleri, modül ve işlem yetki tanımlamaları ile yetki kopyalama yönetimi
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon roles-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="rolesColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxes will be rendered dynamically by JS -->
                        </div>
                    </div>

                    <?php if ($Auths->hasPermission('permission_group_add_update')) { ?>
                        <a href="#" class="btn btn-sm btn-dark route-link shadow-sm roles-header-action" data-page="users/roles/manage" style="background-color: #1e293b; border-color: #1e293b;">
                            <i class="ti ti-plus me-1"></i> Yeni Rol Ekle
                        </a>
                    <?php } ?>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle roles-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="#" class="dropdown-item" id="exportRolesExcel">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <a href="#" class="dropdown-item" id="exportRolesPdf">
                                <i class="ti ti-file-type-pdf icon me-2 text-danger"></i> PDF Raporu Al
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="#" class="dropdown-item" id="refreshRolesTable">
                                <i class="ti ti-refresh icon me-2 text-primary"></i> Listeyi Yenile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="rolesSummaryCards">
        <!-- Kart 1: Toplam Rol -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border roles-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM YETKİ GRUBU</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-shield-lock" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_roles, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Sistem: <strong class="text-dark"><?= $system_roles ?></strong> | Özel: <strong class="text-dark"><?= $custom_roles ?></strong>
                        </span>
                        <label class="role-status-filter mb-0" title="Tüm yetki gruplarını göster">
                            <input type="radio" name="role_type_filter" value="" class="role-type-filter" checked>
                            <span><i class="ti ti-list"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Sistem Rolleri -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border roles-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">SİSTEM ROLLERİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-shield-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($system_roles, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Varsayılan Ana Roller
                        </span>
                        <label class="role-status-filter mb-0" title="Sadece sistem rollerini göster">
                            <input type="radio" name="role_type_filter" value="Sistem Rolü" class="role-type-filter">
                            <span><i class="ti ti-shield-lock"></i> Sistem</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Özel Roller -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border roles-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ÖZEL ROLLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-user-plus" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($custom_roles, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Özelleştirilmiş Gruplar
                        </span>
                        <label class="role-status-filter mb-0" title="Sadece özel rolleri göster">
                            <input type="radio" name="role_type_filter" value="Özel Rol" class="role-type-filter">
                            <span><i class="ti ti-user-check"></i> Özel</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Kullanıcı Eşleşmesi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border roles-summary-card">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ATANMIŞ KULLANICI</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($totalUsersWithRole, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Rol Sahibi Hesaplar
                        </span>
                        <span class="badge bg-info-lt" style="font-size: 10px; font-weight: 600; padding: 3px 8px;">
                            <?= count($users) ?> Toplam Hesap
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Tablo Kartı -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card roles-table-card">
                <!-- Card Header -->
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Yetki Grupları Listesi</h4>
                                <?php if ($Auths->hasPermission('permission_group_add_update')) { ?>
                                    <a href="#" class="btn btn-sm btn-outline-primary btn-icon route-link" data-page="users/roles/manage" title="Yeni Rol Ekle" style="width: 22px; height: 22px; padding: 0; border-radius: 50%;">
                                        <i class="ti ti-plus" style="font-size: 12px;"></i>
                                    </a>
                                <?php } ?>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık arama, sütun filtreleme ve yetki yapılandırma</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon roles-search-wrap" style="min-width: 190px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="roles-fast-search" class="form-control form-control-sm" placeholder="Rol ara..." autocomplete="off">
                            <button type="button" id="roles-search-clear" class="roles-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleRolesSummary" class="btn btn-sm btn-outline-secondary btn-icon roles-summary-toggle" title="Özet kartlarını gizle/göster" aria-label="Özet kartlarını gizle veya göster">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Aktif Filtre Barı (Dinamik) -->
                <div id="rolesActiveFilterBar" class="d-none align-items-center justify-content-between flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="fw-semibold text-secondary"><i class="ti ti-filter me-1"></i> Aktif filtreler:</span>
                        <div id="rolesFilterChips" class="d-flex align-items-center gap-1 flex-wrap"></div>
                    </div>
                    <button type="button" id="rolesClearAllFilters" class="btn btn-sm btn-link text-danger p-0 text-decoration-none fw-semibold">
                        <i class="ti ti-x me-1"></i> Filtreleri Temizle
                    </button>
                </div>

                <!-- Tablo Kapsayıcısı -->
                <div class="table-responsive roles-table-area">
                    <table id="roleTable" class="table table-vcenter table-hover text-nowrap card-table data-table w-100 mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th style="min-width: 180px;">Yetki Grubu Adı</th>
                                <th>Açıklama</th>
                                <th style="width: 120px;" class="text-center">Grup Türü</th>
                                <th style="width: 120px;" class="text-center">Kullanıcı Sayısı</th>
                                <th style="width: 100px;" class="text-center">Durum</th>
                                <th style="width: 110px;" class="text-end no-export">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            if ($Auths->checkFirm()) {
                                foreach ($roles as $role):
                                    $encId = Security::encrypt($role->id);
                                    $isMain = ((int)$role->main_role === 1);
                                    $assignedCount = (int)($roleUserCounts[$role->id] ?? 0);
                                    $isActive = ((int)$role->isActive === 1 || (string)$role->isActive === 'Aktif' || (string)$role->isActive === '1');
                                    ?>
                                    <tr
                                        data-role-id="<?= $role->id ?>"
                                        data-enc-id="<?= $encId ?>"
                                        data-role-name="<?= htmlspecialchars((string)$role->roleName, ENT_QUOTES, 'UTF-8') ?>"
                                        data-is-main="<?= $isMain ? '1' : '0' ?>"
                                    >
                                        <td class="text-center text-muted fw-medium"><?= $i ?></td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-xs rounded-2 <?= $isMain ? 'bg-warning-lt text-warning' : 'bg-primary-lt text-primary' ?>" style="width: 28px; height: 28px;">
                                                    <i class="ti <?= $isMain ? 'ti-shield-check' : 'ti-shield' ?>" style="font-size: 15px;"></i>
                                                </div>
                                                <div>
                                                    <div class="fw-semibold text-dark"><?= htmlspecialchars((string)$role->roleName, ENT_QUOTES, 'UTF-8') ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-secondary small">
                                                <?= !empty($role->roleDescription) ? htmlspecialchars((string)$role->roleDescription, ENT_QUOTES, 'UTF-8') : '<span class="text-muted fst-italic">Açıklama belirtilmedi</span>' ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isMain): ?>
                                                <span class="badge bg-warning-lt"><i class="ti ti-lock me-1"></i> Sistem Rolü</span>
                                            <?php else: ?>
                                                <span class="badge bg-blue-lt"><i class="ti ti-user-cog me-1"></i> Özel Rol</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge <?= $assignedCount > 0 ? 'bg-info-lt' : 'bg-secondary-lt' ?>" style="font-size: 11px;">
                                                <i class="ti ti-user me-1"></i> <?= $assignedCount ?> Kullanıcı
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($isActive): ?>
                                                <span class="badge bg-success-lt"><i class="ti ti-check me-1"></i> Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-lt"><i class="ti ti-minus me-1"></i> Pasif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-flex align-items-center justify-content-end gap-1">
                                                <?php if ($Auths->hasPermission('transaction_permissions')) { ?>
                                                    <a href="#" class="btn btn-sm btn-outline-primary btn-action-icon route-link"
                                                        data-page="users/auths/auths&id=<?= $encId ?>"
                                                        title="Yetkileri Düzenle" aria-label="Yetkileri Düzenle">
                                                        <i class="ti ti-lock"></i>
                                                    </a>
                                                <?php } ?>

                                                <?php if ($Auths->hasPermission('permission_group_add_update')) { ?>
                                                    <a href="#" class="btn btn-sm btn-outline-secondary btn-action-icon route-link"
                                                        data-page="users/roles/manage&id=<?= $encId ?>"
                                                        title="Rolü Düzenle" aria-label="Rolü Düzenle">
                                                        <i class="ti ti-edit"></i>
                                                    </a>
                                                <?php } ?>

                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-sm btn-outline-secondary btn-action-icon" data-bs-toggle="dropdown" aria-expanded="false" title="Diğer İşlemler">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <?php if ($Auths->hasPermission('transaction_permissions')) { ?>
                                                            <a class="dropdown-item route-link" data-page="users/auths/auths&id=<?= $encId ?>" href="#">
                                                                <i class="ti ti-lock icon me-2 text-primary"></i> Yetkileri Yapılandır
                                                            </a>
                                                            <?php if (!$isMain) { ?>
                                                                <a class="dropdown-item copy-roles" data-bs-toggle="modal"
                                                                    data-id="<?= $encId ?>" data-name="<?= htmlspecialchars((string)$role->roleName, ENT_QUOTES, 'UTF-8') ?>"
                                                                    data-bs-target="#modal-small" href="#">
                                                                    <i class="ti ti-copy icon me-2 text-info"></i> Başka Rolden Kopyala
                                                                </a>
                                                            <?php } ?>
                                                        <?php } ?>

                                                        <?php if ($Auths->hasPermission('permission_group_add_update')) { ?>
                                                            <a class="dropdown-item route-link" data-page="users/roles/manage&id=<?= $encId ?>" href="#">
                                                                <i class="ti ti-pencil icon me-2 text-secondary"></i> Bilgileri Güncelle
                                                            </a>
                                                        <?php } ?>

                                                        <?php if (!$isMain && $Auths->hasPermission('permission_group_delete')) { ?>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item text-danger delete_role" href="#" data-id="<?= $encId ?>" data-name="<?= htmlspecialchars((string)$role->roleName, ENT_QUOTES, 'UTF-8') ?>">
                                                                <i class="ti ti-trash icon me-2"></i> Rolü Sil
                                                            </a>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    $i++;
                                endforeach;
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Yetkileri Kopyala Modalı -->
<div class="modal modal-blur fade" id="modal-small" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 440px;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header bg-light py-2 px-3 border-bottom">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2">
                    <i class="ti ti-copy text-primary" style="font-size: 18px;"></i>
                    Yetkileri Kopyala
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form action="" id="copyRoleForm">
                <input type="hidden" id="copy_role_id" name="copy_role_id">
                <input type="hidden" name="action" value="copyRolesModal">

                <div class="modal-body p-3">
                    <div class="alert alert-info bg-info-lt border-0 mb-3 p-2 rounded-2" style="font-size: 12.5px;">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ti ti-info-circle text-info mt-0.5" style="font-size: 16px;"></i>
                            <div>
                                <strong id="role_name" class="text-dark">Rol</strong> isimli yetki grubuna, seçeceğiniz başka bir yetki grubunun tüm modül ve işlem yetkileri aktarılacaktır.
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label required fw-semibold" style="font-size: 12.5px;">Kaynak Yetki Grubu (Yetkileri Alınacak Rol)</label>
                        <select name="role_to_copy" id="role_to_copy" class="form-select select2" style="width: 100%;">
                            <option value="">Kaynak Rol Seçin...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer py-2 px-3 bg-light border-top d-flex justify-content-between">
                    <button type="button" class="btn btn-sm btn-link link-secondary" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" id="copy_roles" class="btn btn-sm btn-primary">
                        <i class="ti ti-copy me-1"></i> Yetkileri Aktar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var $table = $('#roleTable');
    var dtInstance = null;

    // DataTable Başlatma
    if ($.fn.DataTable) {
        dtInstance = $table.DataTable({
            autoWidth: false,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Tümü"]],
            order: [[0, 'asc']],
            columnDefs: [
                { targets: [0, 6], orderable: false, searchable: false },
                { targets: [0, 3, 4, 5], className: 'text-center' },
                { targets: 6, className: 'text-end no-export' }
            ],
            layout: {
                topStart: null,
                topEnd: null,
                bottomStart: ['info', 'pageLength'],
                bottomEnd: 'paging'
            },
            language: {
                info: "Gösterilen <b>_START_ - _END_</b> / <b>_TOTAL_</b> kayıt",
                infoEmpty: "Gösterilen 0 kayıt",
                infoFiltered: "(_MAX_ kayıt içerisinden bulunan)",
                infoThousands: ".",
                lengthMenu: "Sayfada _MENU_ kayıt göster",
                loadingRecords: "Yükleniyor...",
                processing: "İşleniyor...",
                search: "Ara:",
                zeroRecords: "Eşleşen kayıt bulunamadı",
                emptyTable: "Tabloda veri bulunmuyor",
                paginate: {
                    first: "İlk",
                    last: "Son",
                    next: '<i class="ti ti-chevron-right"></i>',
                    previous: '<i class="ti ti-chevron-left"></i>'
                },
                aria: {
                    sortAscending: ": artan sütun sıralamasını aktifleştir",
                    sortDescending: ": azalan sütun sıralamasını aktifleştir"
                }
            }
        });

        // Sütun Görünürlüğü (Colvis Dropdown)
        var $colvisMenu = $('#rolesColvisMenu');
        if ($colvisMenu.length) {
            $colvisMenu.empty();
            dtInstance.columns().every(function(idx) {
                var headerText = $(this.header()).text().trim();
                var isNoExport = $(this.header()).hasClass('no-export');
                if (headerText && !isNoExport && idx !== 0) {
                    var isVisible = this.visible();
                    var $item = $('<label class="dropdown-item py-1 px-2 cursor-pointer" style="font-size: 12px; gap: 8px; display: flex; align-items: center;">' +
                        '<input type="checkbox" class="form-check-input m-0 col-toggle-cb" data-column="' + idx + '" ' + (isVisible ? 'checked' : '') + '> ' +
                        headerText +
                    '</label>');
                    $colvisMenu.append($item);
                }
            });

            $colvisMenu.on('change', '.col-toggle-cb', function(e) {
                e.stopPropagation();
                var colIdx = $(this).data('column');
                var column = dtInstance.column(colIdx);
                column.visible($(this).is(':checked'));
            });
        }

        // Hızlı Anlık Arama
        var searchTimer = null;
        $('#roles-fast-search').on('input', function() {
            var val = this.value;
            $('#roles-search-clear').toggleClass('d-none', !val);
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                dtInstance.search(val).draw();
                updateFilterBar();
            }, 200);
        });

        $('#roles-search-clear').on('click', function() {
            $('#roles-fast-search').val('').trigger('input');
        });

        // Rol Türü (Radio KPI) Filtreleme
        $('input[name="role_type_filter"]').on('change', function() {
            var selectedType = $(this).val();
            // Grup Türü sütunu (index 3)
            dtInstance.column(3).search(selectedType).draw();
            updateFilterBar();
        });

        // Excel & PDF Dışa Aktarma
        $('#exportRolesExcel').on('click', function(e) {
            e.preventDefault();
            exportTable('excel');
        });

        $('#exportRolesPdf').on('click', function(e) {
            e.preventDefault();
            window.print();
        });

        // Tabloyu Yenile
        $('#refreshRolesTable').on('click', function(e) {
            e.preventDefault();
            location.reload();
        });

        // Aktif Filtre Barı Güncellemesi
        function updateFilterBar() {
            var $bar = $('#rolesActiveFilterBar');
            var $chips = $('#rolesFilterChips');
            $chips.empty();
            var hasFilter = false;

            var typeVal = $('input[name="role_type_filter"]:checked').val();
            if (typeVal) {
                hasFilter = true;
                $chips.append(
                    '<span class="badge bg-primary-lt d-inline-flex align-items-center gap-1" style="font-size: 11px;">' +
                        'Tür: ' + typeVal +
                        ' <i class="ti ti-x cursor-pointer remove-type-filter" style="font-size: 12px;"></i>' +
                    '</span>'
                );
            }

            var searchVal = $('#roles-fast-search').val();
            if (searchVal) {
                hasFilter = true;
                $chips.append(
                    '<span class="badge bg-secondary-lt d-inline-flex align-items-center gap-1" style="font-size: 11px;">' +
                        'Arama: ' + searchVal +
                        ' <i class="ti ti-x cursor-pointer remove-search-filter" style="font-size: 12px;"></i>' +
                    '</span>'
                );
            }

            $bar.toggleClass('d-none', !hasFilter).toggleClass('d-flex', hasFilter);
        }

        $(document).on('click', '.remove-type-filter', function() {
            $('input[name="role_type_filter"][value=""]').prop('checked', true).trigger('change');
        });

        $(document).on('click', '.remove-search-filter', function() {
            $('#roles-search-clear').trigger('click');
        });

        $('#rolesClearAllFilters').on('click', function() {
            $('input[name="role_type_filter"][value=""]').prop('checked', true);
            $('#roles-fast-search').val('');
            $('#roles-search-clear').addClass('d-none');
            dtInstance.search('').columns().search('').draw();
            updateFilterBar();
        });

        function exportTable(type) {
            if (type === 'excel') {
                var csv = [];
                var rows = document.querySelectorAll("#roleTable tr");
                for (var i = 0; i < rows.length; i++) {
                    var row = [], cols = rows[i].querySelectorAll("td:not(.no-export), th:not(.no-export)");
                    for (var j = 0; j < cols.length; j++) {
                        var text = cols[j].innerText.replace(/(\r\n|\n|\r)/gm, " ").replace(/"/g, '""').trim();
                        row.push('"' + text + '"');
                    }
                    csv.push(row.join(";"));
                }
                var csvFile = new Blob(["\uFEFF" + csv.join("\n")], {type: "text/csv;charset=utf-8;"});
                var downloadLink = document.createElement("a");
                downloadLink.download = "yetki_gruplari_" + new Date().toISOString().slice(0, 10) + ".csv";
                downloadLink.href = window.URL.createObjectURL(csvFile);
                downloadLink.style.display = "none";
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);
            }
        }
    }

    // Özet Kartları Daraltma / Genişletme
    $('#toggleRolesSummary').on('click', function() {
        var isCollapsed = document.documentElement.classList.toggle('roles-summary-collapsed');
        try {
            localStorage.setItem('roles_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        $(this).find('i').toggleClass('ti-chevron-up', !isCollapsed).toggleClass('ti-chevron-down', isCollapsed);
        $(this).attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
    });

    // İkon durumunu localStorage'a göre eşitle
    if (localStorage.getItem('roles_summary_collapsed') === '1') {
        $('#toggleRolesSummary i').removeClass('ti-chevron-up').addClass('ti-chevron-down');
    }

    // Modal Açıldığında Select2 ve Veri Yükleme
    $('#modal-small').on('shown.bs.modal', function() {
        if ($.fn.select2) {
            $('#role_to_copy').select2({
                dropdownParent: $('#modal-small'),
                placeholder: 'Kaynak Yetki Grubu Seçin...',
                allowClear: true,
                width: '100%'
            });
        }
    });

    // ===== SAĞ TIK CONTEXT MENU =====
    var $rcm = $('<div id="roleContextMenu" class="role-context-menu"></div>').appendTo('body');

    // PHP yetki değişkenleri JS'e aktarıldı
    var canManageAuth   = <?= $Auths->hasPermission('transaction_permissions') ? 'true' : 'false' ?>;
    var canEditRole     = <?= $Auths->hasPermission('permission_group_add_update') ? 'true' : 'false' ?>;
    var canDeleteRole   = <?= $Auths->hasPermission('permission_group_delete') ? 'true' : 'false' ?>;

    function hideContextMenu() {
        $rcm.fadeOut(80);
        $('#roleTable tbody tr').removeClass('rcm-active');
    }

    function showContextMenu(e, $tr) {
        e.preventDefault();

        var encId   = $tr.data('enc-id');
        var roleName = $tr.data('role-name') || 'Rol İşlemleri';
        var isMain  = ($tr.data('is-main') == '1');

        if (!encId) return;

        // Aktif satırı vurgula
        $('#roleTable tbody tr').removeClass('rcm-active');
        $tr.addClass('rcm-active');

        // Menü içeriği oluştur
        var safe = $('<div>').text(roleName).html();
        var items = '';

        items += '<div class="rcm-header"><i class="ti ti-shield-lock me-1"></i>' + safe + '</div>';

        if (canManageAuth) {
            items += '<a href="#" class="route-link" data-page="users/auths/auths&id=' + encId + '">'
                   + '<i class="ti ti-lock text-primary"></i> Yetkileri Yapılandır'
                   + '</a>';
            if (!isMain) {
                items += '<a href="#" class="rcm-copy-roles" data-id="' + encId + '" data-name="' + safe + '">'
                       + '<i class="ti ti-copy text-info"></i> Başka Rolden Yetki Kopyala'
                       + '</a>';
            }
        }

        if (canEditRole) {
            items += '<a href="#" class="route-link" data-page="users/roles/manage&id=' + encId + '">'
                   + '<i class="ti ti-edit text-warning"></i> Bilgileri Düzenle'
                   + '</a>';
        }

        if (!isMain && canDeleteRole) {
            items += '<div class="rcm-divider"></div>';
            items += '<a href="#" class="rcm-danger rcm-delete-role" data-id="' + encId + '" data-name="' + safe + '">'
                   + '<i class="ti ti-trash"></i> Rolü Sil'
                   + '</a>';
        }

        $rcm.html(items).css({ display: 'block' });

        // Ekrandan taşmayı önle
        var menuW  = $rcm.outerWidth();
        var menuH  = $rcm.outerHeight();
        var winW   = $(window).width();
        var winH   = $(window).height();
        var posX   = (e.clientX + menuW > winW) ? winW - menuW - 10 : e.clientX;
        var posY   = (e.clientY + menuH > winH) ? winH - menuH - 10 : e.clientY;

        $rcm.css({ top: posY + 'px', left: posX + 'px' });
    }

    // Tablo satırlarında sağ tık
    $(document).on('contextmenu', '#roleTable tbody tr', function(e) {
        showContextMenu(e, $(this));
    });

    // Context menü içindeki Kopyala linkine tıklayınca modal aç
    $(document).on('click', '.rcm-copy-roles', function(e) {
        e.preventDefault();
        var id   = $(this).data('id');
        var name = $(this).data('name');
        hideContextMenu();
        // Mevcut copy-roles mekanizmasını tetikle
        $('#copy_role_id').val(id);
        $('#role_name').text(name);
        var fd = new FormData();
        fd.append('id', id);
        fd.append('action', 'copyRoles');
        fetch('api/users/roles.php', { method: 'POST', body: fd })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                data = data.roles || [];
                var opts = '<option value="">Kaynak Rol Seçin...</option>';
                data.forEach(function(el){ opts += '<option value="' + el.id + '">' + el.roleName + '</option>'; });
                $('#role_to_copy').html(opts).trigger('change');
                var modal = new bootstrap.Modal(document.getElementById('modal-small'));
                modal.show();
            });
    });

    // Context menü içindeki Sil linkine tıklayınca
    $(document).on('click', '.rcm-delete-role', function(e) {
        e.preventDefault();
        var id   = $(this).data('id');
        var name = $(this).data('name');
        hideContextMenu();
        // delete_role davranışıyla aynı şekilde sil
        Swal.fire({
            title: 'Emin misiniz?',
            html: '<strong>' + name + '</strong> isimli yetki grubu kalıcı olarak silinecektir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63f3f',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ti ti-trash me-1"></i> Evet, Sil!',
            cancelButtonText: 'Vazgeç'
        }).then(function(result) {
            if (!result.isConfirmed) return;
            var fd = new FormData();
            fd.append('id', id);
            fd.append('action', 'deleteRole');
            fetch('/api/users/roles.php', { method: 'POST', body: fd })
                .then(function(r){ return r.json(); })
                .then(function(data) {
                    Swal.fire({
                        icon: data.status,
                        title: data.status === 'success' ? 'Başarılı!' : 'Hata!',
                        text: data.message,
                        timer: data.status === 'success' ? 1500 : 3000,
                        showConfirmButton: data.status !== 'success'
                    }).then(function() {
                        if (data.status === 'success') location.reload();
                    });
                });
        });
    });

    // Dışarı tıklayınca kapat
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#roleContextMenu').length) {
            hideContextMenu();
        }
    });

    // Context menüdeki route-link'e tıklayınca gizle
    $(document).on('click', '#roleContextMenu a', function() {
        hideContextMenu();
    });

    // Scroll/resize/blur'da kapat
    $(window).on('scroll resize blur', function() {
        hideContextMenu();
    });

    // Tabloda Escape tuşu ile kapat
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape') hideContextMenu();
    });

    // Tablonun kendi sağ tık menüsünü engelle (tablo dışı alanlarda tarayıcı menüsü açılsın)
    $(document).on('contextmenu', '.role-context-menu', function(e) {
        e.preventDefault();
    });
    // ===== SAĞ TIK CONTEXT MENU BİTİŞ =====
});
</script>