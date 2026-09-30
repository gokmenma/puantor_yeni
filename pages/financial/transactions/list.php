<?php
require_once ROOT . "/Model/Cases.php";
require_once ROOT . "/Model/CaseTransactions.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/financial.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/projects.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$projectHelper = new ProjectHelper();
$financial = new Financial();
$financialHelper = $financial;
$cases = new Cases();
$ct = new CaseTransactions();

$case_id = 0;
if (isset($_POST['case_id']) && $_POST['case_id'] !== '0' && $_POST['case_id'] !== '') {
    $decrypted = Security::decrypt($_POST['case_id']);
    $case_id = $decrypted ? (int)$decrypted : (int)$_POST['case_id'];
}

// Kullanıcının firmasını kontrol eder
$Auths->checkFirmReturn();

// Sayfa yetki kontrolü
$perm->checkAuthorize("income_expense_operations");

$firm_id = (int)($_SESSION["firm_id"] ?? 0);
$is_main_user = ($_SESSION['user']->parent_id == 0 || (isset($_SESSION['user']->is_main_user) && $_SESSION['user']->is_main_user == 1));
$firm_cases = $is_main_user ? $cases->allCaseWithFirmId() : $cases->getCasesByUserIds();
$authorizedCaseIds = array_map(function($c) { return (int)$c->id; }, $firm_cases);

if (count($firm_cases) == 1) {
    if ($firm_cases[0]->isDefault != 1) {
        $cases->setDefaultCase($firm_cases[0]->id);
    }
    if ($case_id == 0) {
        $case_id = (int)$firm_cases[0]->id;
    }
}

$targetCaseIds = ($case_id > 0 && in_array($case_id, $authorizedCaseIds, true)) ? [$case_id] : $authorizedCaseIds;
$stats = $ct->getTransactionsSummaryStats($targetCaseIds);

$total_transactions = $stats['total_count'];
$total_income_try = $stats['total_income'];
$total_expense_try = $stats['total_expense'];
$income_count = $stats['income_count'];
$expense_count = $stats['expense_count'];
$net_balance_try = $stats['net_balance'];
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

<div class="container-xl mt-1" id="transactionsPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-arrows-diff" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Gelir - Gider Hareketleri
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Kasa hareketleri, tahsilat, ödeme ve virman kayıtlarının takibi
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Sütunlar Butonu -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon transactions-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="transactionColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar JS ile dinamik oluşturulur -->
                        </div>
                    </div>

                    <!-- Birincil Aksiyon: Yeni Hareket Ekle -->
                    <?php if ($Auths->hasPermission('income_expense_add_update')): ?>
                        <a href="javascript:void(0);" class="btn btn-sm btn-dark transactions-header-action" data-bs-toggle="modal" data-bs-target="#general-modal" style="background-color: #1e293b; border-color: #1e293b;">
                            <i class="ti ti-plus me-1"></i> Yeni Hareket Ekle
                        </a>
                    <?php endif; ?>

                    <!-- İşlemler Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle transactions-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0);" id="btnExportTransactionExcel" class="dropdown-item">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <?php if ($Auths->hasPermission('make_staff_payment')): ?>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#pay_to_persons-modal">
                                    <i class="ti ti-users icon me-2 text-primary"></i> Toplu Personel Ödemesi
                                </a>
                            <?php endif; ?>
                            <?php if ($Auths->hasPermission("intercash_transfer")): ?>
                                <a class="dropdown-item" href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#intercash_transfer-modal">
                                    <i class="ti ti-arrows-left-right icon me-2 text-warning"></i> Kasalararası Virman
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4'lü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="transactionSummaryCards">
        <!-- Kart 1: Toplam İşlem -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border transactions-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM İŞLEM</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrows-diff" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="kpiTotalTransactions" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_transactions, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Toplam Kasa Hareketi
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
            <div class="card card-sm border transactions-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM GELİR</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-up-right" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-success" id="kpiTotalIncome" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_income_try, 1) ?>
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
            <div class="card card-sm border transactions-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM GİDER</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-down-left" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-danger" id="kpiTotalExpense" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_expense_try, 1) ?>
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

        <!-- Kart 4: Net Kasa Durumu -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border transactions-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">NET KASA DURUMU</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-scale" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold <?= $net_balance_try >= 0 ? 'text-success' : 'text-danger' ?>" id="kpiNetBalance" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($net_balance_try, 1) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Gelir - Gider Farkı
                        </span>
                        <span class="badge <?= $net_balance_try >= 0 ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger' ?> fw-semibold" id="kpiNetBalanceBadge" style="font-size: 10px;">
                            <?= $net_balance_try >= 0 ? '+ Fazla' : '- Açık' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card transactions-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Gelir - Gider Hareketleri</h4>
                                <?php if ($Auths->hasPermission('income_expense_add_update')): ?>
                                    <a href="javascript:void(0);" class="btn-card-header-add" data-bs-toggle="modal" data-bs-target="#general-modal" title="Yeni Hareket Ekle">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                <?php endif; ?>
                                <input type="hidden" class="form-control" id="transaction_id" name="transaction_id" value="0">
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık arama, filtreleme ve kasa hareketleri takibi</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <?php if ($Auths->hasPermission('delete_income_expense')): ?>
                            <button type="button" id="btnDeleteSelectedTransactions" class="btn btn-sm btn-danger d-none">
                                <i class="ti ti-trash icon me-1"></i> Seçilenleri Sil
                            </button>
                        <?php endif; ?>

                        <!-- Kasa Filtre Seçimi -->
                        <div class="transactions-case-filter" style="min-width: 180px; max-width: 240px;">
                            <form action="#" method="post" id="caseForm" class="m-0">
                                <?= $financialHelper->getCasesSelectByUser("firm_cases", $case_id) ?>
                            </form>
                        </div>

                        <!-- Fast Instant Search -->
                        <div class="input-icon transactions-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="transactions-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="transactions-search-clear" class="transactions-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <!-- Özet Kartları Gizle/Göster Butonu -->
                        <button type="button" id="toggleTransactionSummary" class="btn btn-sm btn-outline-secondary btn-icon transactions-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container -->
                <div class="table-responsive transactions-table-area">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="transactionTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false">
                                    <input type="checkbox" class="form-check-input select-all-transactions">
                                </th>
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
.transactions-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.transactions-header-icon-action,
.transactions-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.transactions-header-icon-action i,
.transactions-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#transactionsPage .transactions-summary-card {
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

#transactionsPage .transactions-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    border-radius: 12px;
    overflow: hidden;
}

.transactions-table-card > .transactions-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.transactions-table-card > .card-header {
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
.transactions-search-wrap { position: relative; }
#transactions-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.transactions-search-wrap,
.transactions-search-wrap.input-icon {
    height: 32px !important;
}
.transactions-search-clear {
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
.transactions-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

/* Kasa Select Dropdown */
.transactions-case-filter .select2-container .select2-selection--single {
    height: 32px !important;
    padding: 2px 6px !important;
    font-size: 12.5px !important;
    border-radius: 6px !important;
    border-color: #dbe3ec !important;
}
.transactions-case-filter .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 26px !important;
}
.transactions-case-filter .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 30px !important;
}

/* DataTables Container overrides - Enable natural mouse wheel page scrolling */
#transactionsPage .table-responsive,
#transactionsPage .transactions-table-area,
#transactionsPage #transactionTable_wrapper,
#transactionsPage div.dt-container,
#transactionsPage div.dt-container .dt-layout-row.dt-layout-table,
#transactionsPage div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell,
#transactionsPage div.dt-container .dt-layout-table,
#transactionsPage div.dt-container .dt-scroll-body {
    height: auto !important;
    min-height: 0 !important;
    min-height: unset !important;
    max-height: none !important;
    flex-grow: 0 !important;
    border: none !important;
    box-shadow: none !important;
    overflow-y: visible !important;
}

#transactionsPage .transactions-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
    overflow-x: auto !important;
    overflow-y: visible !important;
}

div.dt-container .dt-layout-row.dt-layout-table {
    padding: 0 !important;
    margin: 0 !important;
    overflow: visible !important;
}

div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell {
    padding: 0 !important;
    margin: 0 !important;
    overflow: visible !important;
}

/* Tablo Çerçevesi & Kenarlıkları */
table#transactionTable.data-table,
table#transactionTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#transactionTable.data-table tbody,
table#transactionTable.dataTable tbody,
table#transactionTable.data-table tbody tr:last-child,
table#transactionTable.dataTable tbody tr:last-child,
#transactionTable_wrapper .dt-layout-table,
#transactionTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Başlık Hücreleri */
table#transactionTable.data-table thead th {
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
table#transactionTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Gövde Hücreleri */
table#transactionTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#transactionTable.data-table td.actions-column .btn.btn-sm.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
table#transactionTable.data-table td.actions-column .btn.btn-sm.btn-icon i {
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    font-size: 13px !important;
}
table#transactionTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#transactionTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#transactionTable.dataTable > tbody > tr:last-child > *,
table#transactionTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#transactionTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#transactionTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#transactionTable_wrapper .dt-layout-row:has(.dt-paging),
div#transactionTable_wrapper .dt-layout-row:has(.dt-info) {
    margin: 0 !important;
    margin-top: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
    position: static !important;
    flex-shrink: 0 !important;
}

/* Custom Context Menu */
.custom-context-menu {
    position: fixed;
    z-index: 9999;
    background: #ffffff;
    border: 1px solid #dbe3ec;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.12);
    min-width: 190px;
    padding: 4px;
    display: none;
}
.custom-context-menu .cm-header {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 240px;
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
[data-bs-theme="dark"] .transactions-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .transactions-summary-card,
[data-bs-theme="dark"] .transactions-table-card {
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
<?php include_once "modals/general-modal.php"; ?>
<?php include_once "modals/get_payment_from_project-modal.php"; ?>
<?php include_once "modals/pay_to_person-modal.php"; ?>
<?php include_once "modals/pay_to_persons-modal.php"; ?>
<?php include_once "modals/pay_to_company-modal.php"; ?>
<?php include_once "modals/add_expense_received_project-modal.php"; ?>
<?php include_once "modals/intercash_transfer-modal.php"; ?>