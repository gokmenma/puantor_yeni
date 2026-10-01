<?php

use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

require_once ROOT . "/Model/Cases.php";
require_once ROOT . "/Model/CaseTransactions.php";
require_once ROOT . "/Model/DefinesModel.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/App/Helper/company.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/financial.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/projects.php";
require_once ROOT . "/App/Helper/person.php";
require_once ROOT . "/App/Helper/users.php";

$company = new CompanyHelper();
$userHelper = new UserHelper();
$financial = new Financial();
$financialHelper = $financial;
$projectHelper = new ProjectHelper();
$personHelper = new PersonHelper();
$CompanyHelper = new CompanyHelper();
$define = new DefinesModel();
$caseObj = new Cases();
$cases = $caseObj;
$ct = new CaseTransactions();

// Güvenlik ve Yetki Kontrolleri
$Auths->checkFirmReturn();
$perm->checkAuthorize("cash_register_list");

$raw_id = $_GET["id"] ?? 0;
$decrypted_id = Security::decrypt($raw_id);
$id = $decrypted_id ? (int)$decrypted_id : (int)$raw_id;

if ($id <= 0) {
    header("Location: /index.php?p=financial/case/list");
    exit;
}

$case = $caseObj->find($id);
if (!$case) {
    header("Location: /index.php?p=financial/case/list");
    exit;
}

$encCaseId = Security::encrypt($case->id);
$case_id = $id;

// Kasa Özet İstatistiklerini Hesaplama
$stats = $ct->getTransactionsSummaryStats([$id]);
$total_transactions = $stats['total_count'];
$total_income_try = $stats['total_income'];
$total_expense_try = $stats['total_expense'];
$income_count = $stats['income_count'];
$expense_count = $stats['expense_count'];
$net_balance_try = $stats['net_balance'];

$moneyUnit = $case->case_money_unit ?? 1;
$isBank = !empty($case->bank_name);
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'transactions-summary-collapsed',
            localStorage.getItem('transactions_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.transactions-summary-collapsed #transactionSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="caseManagePage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-wallet" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                                <?= htmlspecialchars($case->case_name, ENT_QUOTES, 'UTF-8') ?>
                            </h2>
                            <?php if ($case->isDefault == 1): ?>
                                <span class="badge bg-success-lt text-success fw-semibold" style="font-size: 10.5px;">
                                    <i class="ti ti-star me-0.5"></i> Varsayılan
                                </span>
                            <?php endif; ?>
                            <?php if ($isBank): ?>
                                <span class="badge bg-purple-lt text-purple fw-semibold" style="font-size: 10.5px;">
                                    <i class="ti ti-building-bank me-0.5"></i> Banka Hesabı
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary-lt text-secondary fw-semibold" style="font-size: 10.5px;">
                                    <i class="ti ti-cash me-0.5"></i> Nakit Kasa
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            <?php if ($isBank): ?>
                                <?= htmlspecialchars($case->bank_name, ENT_QUOTES, 'UTF-8') ?><?= !empty($case->branch_name) ? ' - ' . htmlspecialchars($case->branch_name, ENT_QUOTES, 'UTF-8') : '' ?> &bull; 
                            <?php endif; ?>
                            Bu kasaya ait tüm gelir, gider, ödeme ve virman hareketleri
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Sütunlar Butonu -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon manage-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="transactionColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar JS ile dinamik oluşturulur -->
                        </div>
                    </div>

                    <!-- Kasalar Listesi Butonu -->
                    <button type="button" class="btn btn-sm btn-outline-secondary manage-header-action route-link" data-page="financial/case/list">
                        <i class="ti ti-arrow-left me-1"></i> Kasalar Listesi
                    </button>

                    <!-- Yeni Hareket Ekle (Birincil Aksiyon) -->
                    <?php if ($Auths->hasPermission('income_expense_add_update')): ?>
                        <a href="javascript:void(0);" class="btn btn-sm btn-dark manage-header-action" data-bs-toggle="modal" data-bs-target="#general-modal" style="background-color: #1e293b; border-color: #1e293b;">
                            <i class="ti ti-plus me-1"></i> Yeni Hareket Ekle
                        </a>
                    <?php endif; ?>

                    <!-- İşlemler Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle manage-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0);" id="btnExportTransactionExcel" class="dropdown-item">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <?php if ($Auths->Authorize("cash_register_add_update")): ?>
                                <a href="javascript:void(0);" class="dropdown-item edit-case" data-id="<?= $id ?>">
                                    <i class="ti ti-pencil icon me-2 text-primary"></i> Kasayı Düzenle
                                </a>
                            <?php endif; ?>
                            <?php if ($Auths->hasPermission("intercash_transfer")): ?>
                                <a class="dropdown-item intercash-transfer" href="javascript:void(0);" data-id="<?= $id ?>">
                                    <i class="ti ti-arrows-left-right icon me-2 text-warning"></i> Kasalararası Virman
                                </a>
                            <?php endif; ?>
                            <?php if ($Auths->hasPermission('make_staff_payment')): ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#pay_to_persons-modal">
                                    <i class="ti ti-users icon me-2 text-azure"></i> Toplu Personel Ödemesi
                                </a>
                            <?php endif; ?>
                            <?php if ($Auths->hasPermission('income_expense_add_update')): ?>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#get_payment_from_project-modal">
                                    <i class="ti ti-building icon me-2 text-info"></i> Projeden Tahsilat
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#add_expense_received_project-modal">
                                    <i class="ti ti-briefcase icon me-2 text-warning"></i> Projeye Gider Girişi
                                </a>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#pay_to_company-modal">
                                    <i class="ti ti-building-bank icon me-2 text-danger"></i> Cariye / Firmaya Ödeme
                                </a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="javascript:window.print();" class="dropdown-item">
                                <i class="ti ti-printer icon me-2 text-secondary"></i> Yazdır
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="transactionSummaryCards">
        <!-- Kart 1: Toplam İşlem -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border manage-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM İŞLEM</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-receipt-2" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="kpiTotalTransactions" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_transactions, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Kasa Hareket Adedi
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm hareketleri göster">
                            <input type="radio" name="transaction_type_filter" value="" class="type-filter" checked>
                            <span><i class="ti ti-arrows-diff"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Toplam Gelir -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border manage-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM GELİR</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-up-right" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-success" id="kpiTotalIncome" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_income_try, $moneyUnit) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Giriş: <strong class="text-success" id="kpiIncomeCount"><?= $income_count ?> Adet</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Yalnızca Gelirleri göster">
                            <input type="radio" name="transaction_type_filter" value="Gelir" class="type-filter">
                            <span><i class="ti ti-arrow-up-right"></i> Gelir</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Toplam Gider -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border manage-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM GİDER</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-down-left" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-danger" id="kpiTotalExpense" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_expense_try, $moneyUnit) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Çıkış: <strong class="text-danger" id="kpiExpenseCount"><?= $expense_count ?> Adet</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Yalnızca Giderleri göster">
                            <input type="radio" name="transaction_type_filter" value="Gider" class="type-filter">
                            <span><i class="ti ti-arrow-down-left"></i> Gider</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Güncel Kasa Bakiyesi -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border manage-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">GÜNCEL BAKİYE</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-scale" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold <?= $net_balance_try >= 0 ? 'text-success' : 'text-danger' ?>" id="kpiNetBalance" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($net_balance_try, $moneyUnit) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Kasa Net Durumu
                        </span>
                        <span class="badge <?= $net_balance_try >= 0 ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' ?> fw-semibold" id="kpiNetBalanceBadge" style="font-size: 10px;">
                            <?= $net_balance_try >= 0 ? '+ Artıda' : '- Ekside' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card manage-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">
                                    Kasa Hareketleri
                                </h4>
                                <?php if ($Auths->hasPermission('income_expense_add_update')): ?>
                                    <a href="javascript:void(0);" class="btn-card-header-add" data-bs-toggle="modal" data-bs-target="#general-modal" title="Yeni Hareket Ekle">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">
                                Anlık arama, filtreleme ve işlem geçmişi
                            </p>
                        </div>
                    </div>

                    <!-- Hidden Inputs for Ajax Case Filter -->
                    <input type="hidden" name="firm_cases" id="firm_cases" value="<?= $encCaseId ?>">

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon manage-search-wrap" style="min-width: 200px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="transactions-fast-search" class="form-control form-control-sm" placeholder="Hareketlerde ara..." autocomplete="off">
                            <button type="button" id="transactions-search-clear" class="manage-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <!-- Özet Kartları Gizle/Göster Butonu -->
                        <button type="button" id="toggleTransactionSummary" class="btn btn-sm btn-outline-secondary btn-icon manage-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container -->
                <div class="table-responsive manage-table-area">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="transactionTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">Sıra</th>
                                <th style="width: 14%;">Kasa</th>
                                <th style="width: 95px;" class="text-center">Tarih</th>
                                <th style="width: 15%;">İşlem Türü</th>
                                <th>Hesap / Muhatap Adı</th>
                                <th style="width: 14%;" class="text-end">Tutar</th>
                                <th>Açıklama</th>
                                <th style="width: 75px; min-width: 75px;" class="text-end no-export actions-column" data-orderable="false">İşlem</th>
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
/* Header Action Buttons */
.manage-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.manage-header-icon-action,
.manage-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.manage-header-icon-action i,
.manage-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#caseManagePage .manage-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    border-radius: 12px;
    overflow: hidden;
}

#transactionSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#caseManagePage .manage-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    border-radius: 12px;
    overflow: hidden;
}

.manage-table-card > .manage-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.manage-table-card > .card-header {
    border-bottom: 0 !important;
}

/* Quick Add button in Card Header */
.btn-card-header-add {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 5px;
    background: rgba(32, 107, 196, 0.1);
    color: #206bc4;
    font-size: 12px;
    text-decoration: none;
    transition: all 0.15s ease;
}
.btn-card-header-add:hover {
    background: #206bc4;
    color: #fff;
}

/* Status Filter Pill in Summary Cards */
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

/* Search input wrapper */
.manage-search-wrap { position: relative; }
#transactions-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.manage-search-wrap,
.manage-search-wrap.input-icon {
    height: 32px !important;
}
.manage-search-clear {
    position: absolute;
    top: 50%;
    right: 6px;
    transform: translateY(-50%);
    width: 20px;
    height: 20px;
    padding: 0;
    border: none;
    background: #e2e8f0;
    color: #64748b;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    transition: all .15s ease;
    z-index: 4;
}
.manage-search-clear:hover {
    background: #cbd5e1;
    color: #1e293b;
}

/* DataTables Global Stili */
table#transactionTable.data-table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    overflow: hidden;
}
table#transactionTable.data-table thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 11.5px !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    padding: 8px 10px !important;
    border-bottom: 1px solid #dbe3ec !important;
    border-top: none !important;
    border-left: none !important;
    border-right: 1px solid #eef2f6 !important;
    vertical-align: middle;
}
table#transactionTable.data-table thead th:last-child {
    border-right: none !important;
}
table#transactionTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f1f5f9 !important;
    border-top: none !important;
    border-left: none !important;
    border-right: 1px solid #f8fafc !important;
    background: #ffffff;
}
table#transactionTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#transactionTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}
table#transactionTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}

/* Popover Filter Hunisi */
.dt-col-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    margin-left: 4px;
    padding: 0;
    border: none;
    background: transparent;
    color: #94a3b8;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.15s ease;
    vertical-align: middle;
}
.dt-col-filter-btn:hover {
    color: #206bc4;
    background: rgba(32, 107, 196, 0.1);
}
.dt-col-filter-btn.active {
    color: #206bc4;
    background: rgba(32, 107, 196, 0.15);
}

/* Context Menu */
.custom-context-menu {
    position: fixed;
    z-index: 99999;
    display: none;
    min-width: 190px;
    padding: 6px;
    background: #ffffff;
    border: 1px solid #dbe3ec;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
}
.custom-context-menu .cm-header {
    padding: 4px 8px 6px;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
}
.custom-context-menu a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 10px;
    font-size: 12.5px;
    color: #334155;
    text-decoration: none;
    border-radius: 6px;
    transition: background-color 0.15s ease;
}
.custom-context-menu a:hover {
    background-color: #f1f5f9;
    color: #1e293b;
}
.custom-context-menu a.cm-danger {
    color: #d63939;
}
.custom-context-menu a.cm-danger:hover {
    background-color: rgba(214, 57, 57, 0.08);
}
.custom-context-menu .cm-divider {
    height: 1px;
    background: #f1f5f9;
    margin: 4px 0;
}

/* Dark Mode */
[data-bs-theme="dark"] table#transactionTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#transactionTable.data-table,
[data-bs-theme="dark"] table#transactionTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#transactionTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#transactionTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#transactionTable.data-table tbody tr:hover td {
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
[data-bs-theme="dark"] .manage-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .manage-summary-card,
[data-bs-theme="dark"] .manage-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}
[data-bs-theme="dark"] .custom-context-menu {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.4);
}
[data-bs-theme="dark"] .custom-context-menu .cm-header {
    color: #94a3b8;
    border-bottom-color: #334155;
}
[data-bs-theme="dark"] .custom-context-menu a {
    color: #e2e8f0;
}
[data-bs-theme="dark"] .custom-context-menu a:hover {
    background-color: #334155;
}
[data-bs-theme="dark"] .custom-context-menu .cm-divider {
    background: #334155;
}
</style>

<!-- Modal Bileşenleri -->
<?php include_once ROOT . "/pages/financial/transactions/modals/general-modal.php"; ?>
<?php include_once ROOT . "/pages/financial/transactions/modals/get_payment_from_project-modal.php"; ?>
<?php include_once ROOT . "/pages/financial/transactions/modals/pay_to_person-modal.php"; ?>
<?php include_once ROOT . "/pages/financial/transactions/modals/pay_to_persons-modal.php"; ?>
<?php include_once ROOT . "/pages/financial/transactions/modals/pay_to_company-modal.php"; ?>
<?php include_once ROOT . "/pages/financial/transactions/modals/add_expense_received_project-modal.php"; ?>
<?php include_once ROOT . "/pages/financial/transactions/modals/intercash_transfer-modal.php"; ?>
<?php include_once ROOT . "/pages/financial/case/content/case-modal.php"; ?>