<?php
require_once ROOT . "/Model/Cases.php";
require_once ROOT . "/App/Helper/company.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/financial.php";
require_once ROOT . "/Model/CaseTransactions.php";
require_once ROOT . "/App/Helper/users.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Security;

$Cases = new Cases();
$CaseTransactions = new CaseTransactions();
$company = new CompanyHelper();
$userHelper = new UserHelper();
$financialHelper = new Financial();

$Auths->checkFirmReturn();
$perm->checkAuthorize("cash_register_list");

$is_main_user = $_SESSION['user']->parent_id;
if ($is_main_user == 0) {
    $cases = $Cases->allCaseWithFirmId();
} else {
    $cases = $Cases->getCasesByUserIds();
}

// Özet İstatistikleri Hesaplama
$total_cases = count($cases);
$total_balance_try = 0;
$default_case_name = 'Belirtilmemiş';
$bank_cases_count = 0;
$cash_cases_count = 0;

$caseBalances = [];
foreach ($cases as $c) {
    $bal = $CaseTransactions->getCaseBalance($c->id)->balance ?? 0;
    $caseBalances[$c->id] = $bal;

    if (!empty($c->bank_name)) {
        $bank_cases_count++;
    } else {
        $cash_cases_count++;
    }

    if ($c->isDefault == 1) {
        $default_case_name = $c->case_name;
    }

    if (($c->case_money_unit ?? 1) == 1) {
        $total_balance_try += (float)$bal;
    }
}
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'case-summary-collapsed',
            localStorage.getItem('case_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.case-summary-collapsed #caseSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="casePage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-wallet" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Kasa Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Tanımlı kasalar, banka hesapları, güncel bakiyeler ve kasa yönetimi
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Sütunlar Butonu -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon case-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="caseColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar JS ile dinamik oluşturulur -->
                        </div>
                    </div>

                    <!-- Yeni Kasa Ekle (Birincil Aksiyon) -->
                    <?php if ($Auths->Authorize("cash_register_add_update")): ?>
                        <button type="button" class="btn btn-sm btn-dark shadow-sm case-header-action" id="btn-new-case" style="background-color: #1e293b; border-color: #1e293b;">
                            <i class="ti ti-plus me-1"></i> Yeni Kasa Ekle
                        </button>
                    <?php endif; ?>

                    <!-- İşlemler Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle case-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0);" id="btnExportCaseExcel" class="dropdown-item">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <?php if ($Auths->hasPermission("intercash_transfer") && $total_cases > 1): ?>
                                <a class="dropdown-item intercash-transfer-header" href="javascript:void(0);">
                                    <i class="ti ti-arrows-left-right icon me-2 text-warning"></i> Kasalararası Virman
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
    <div class="row row-cards g-3 mb-3" id="caseSummaryCards">
        <!-- Kart 1: Toplam Kasa -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border case-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM KASA</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-wallet" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_cases, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Nakit: <strong class="text-dark"><?= $cash_cases_count ?></strong> | Banka: <strong class="text-dark"><?= $bank_cases_count ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm kasaları göster">
                            <input type="radio" name="case_type_filter" value="" class="case-type-filter" checked>
                            <span><i class="ti ti-wallet"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Toplam Bakiye (TRY) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border case-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM BAKİYE (TL)</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-currency-lira" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold <?= $total_balance_try >= 0 ? 'text-success' : 'text-danger'; ?>" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_balance_try, 1); ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            TRY Kasaları Net Bakiye
                        </span>
                        <span class="badge bg-success-lt fw-semibold" style="font-size: 10px;">Güncel</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Banka Hesapları -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border case-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BANKA HESAPLARI</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-building-bank" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($bank_cases_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Kayıtlı Banka Hesabı
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece banka kasalarını göster">
                            <input type="radio" name="case_type_filter" value="Banka" class="case-type-filter">
                            <span><i class="ti ti-building-bank"></i> Banka</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Nakit / Elden Kasa -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border case-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">NAKİT / ELDEN KASA</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-cash" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($cash_cases_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted text-truncate d-inline-block" style="font-size: 11.5px; max-width: 140px;" title="Varsayılan: <?= htmlspecialchars($default_case_name, ENT_QUOTES, 'UTF-8'); ?>">
                            Varsayılan: <strong class="text-dark"><?= htmlspecialchars($default_case_name, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece nakit kasaları göster">
                            <input type="radio" name="case_type_filter" value="Nakit" class="case-type-filter">
                            <span><i class="ti ti-cash"></i> Nakit</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card case-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Kasa Listesi</h4>
                                <?php if ($Auths->Authorize("cash_register_add_update")): ?>
                                    <a href="javascript:void(0);" class="btn-card-header-add" id="btn-new-case-icon" data-tooltip="Yeni Kasa Ekle" title="Yeni Kasa Ekle">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Tanımlı kasalar, banka hesapları, güncel bakiyeler ve kasa yönetimi</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon case-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="case-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="case-search-clear" class="case-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <!-- Özet Kartları Gizle/Göster Butonu -->
                        <button type="button" id="toggleCaseSummary" class="btn btn-sm btn-outline-secondary btn-icon case-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container -->
                <div class="table-responsive case-table-area">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="caseTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">Sıra</th>
                                <th>Firması</th>
                                <th>Kasa Adı</th>
                                <th>Banka / Şube</th>
                                <th class="text-center" style="width: 105px;">Kasa Türü</th>
                                <th class="text-center" style="width: 90px;">Para Birimi</th>
                                <th class="text-center" style="width: 95px;">Varsayılan</th>
                                <th class="text-end" style="width: 130px;">Güncel Bakiye</th>
                                <th>Açıklama</th>
                                <th style="width: 85px; min-width: 85px;" class="text-end no-export actions-column" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($Auths->checkFirm() && !empty($cases)): ?>
                                <?php
                                $i = 1;
                                foreach ($cases as $case):
                                    $id = Security::encrypt($case->id);
                                    $balance = $caseBalances[$case->id] ?? 0;
                                    $moneyUnit = $case->case_money_unit ?? 1;
                                    $hasBank = !empty($case->bank_name);
                                    $isDefault = ((int)($case->isDefault ?? 0) === 1);
                                    $firmName = $company->getFirmName($case->company_id ?? '');
                                    ?>
                                    <tr data-id="<?= $id; ?>" data-name="<?= htmlspecialchars($case->case_name, ENT_QUOTES, 'UTF-8'); ?>" data-type="<?= $hasBank ? 'Banka' : 'Nakit'; ?>">
                                        <!-- 0: Sıra -->
                                        <td class="text-center text-muted fw-medium"><?= $i; ?></td>

                                        <!-- 1: Firması -->
                                        <td>
                                            <span class="d-inline-flex align-items-center text-secondary">
                                                <i class="ti ti-building text-muted me-1.5" style="font-size: 15px;"></i>
                                                <?= htmlspecialchars($firmName ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>

                                        <!-- 2: Kasa Adı -->
                                        <td>
                                            <?php if ($Auths->hasPermission("cash_register_add_update")): ?>
                                                <a class="edit-case fw-bold text-primary text-decoration-none d-inline-flex align-items-center"
                                                    data-tooltip="Detay / Güncelle" data-id="<?= $id; ?>" href="javascript:void(0);">
                                                    <i class="ti ti-wallet me-1.5 text-primary" style="font-size: 15px;"></i>
                                                    <?= htmlspecialchars($case->case_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="fw-bold d-inline-flex align-items-center text-dark">
                                                    <i class="ti ti-wallet me-1.5 text-secondary" style="font-size: 15px;"></i>
                                                    <?= htmlspecialchars($case->case_name, ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 3: Banka / Şube -->
                                        <td>
                                            <?php if ($hasBank): ?>
                                                <div>
                                                    <span class="fw-medium text-dark"><i class="ti ti-building-bank text-muted me-1"></i><?= htmlspecialchars($case->bank_name, ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php if (!empty($case->branch_name)): ?>
                                                        <br><small class="text-muted ps-3"><?= htmlspecialchars($case->branch_name, ENT_QUOTES, 'UTF-8'); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 4: Kasa Türü -->
                                        <td class="text-center">
                                            <?php if ($hasBank): ?>
                                                <span class="badge bg-warning-lt text-warning fw-semibold" style="font-size: 11px;">
                                                    <i class="ti ti-building-bank me-0.5"></i> Banka
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-info-lt text-info fw-semibold" style="font-size: 11px;">
                                                    <i class="ti ti-cash me-0.5"></i> Nakit
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 5: Para Birimi -->
                                        <td class="text-center">
                                            <span class="badge bg-blue-lt fw-semibold" style="font-size: 11px;">
                                                <?= htmlspecialchars(Helper::money($moneyUnit), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>

                                        <!-- 6: Varsayılan -->
                                        <td class="text-center">
                                            <?php if ($isDefault): ?>
                                                <span class="badge bg-success-lt text-success fw-semibold" style="font-size: 10.5px;" data-tooltip="Varsayılan Kasa">
                                                    <i class="ti ti-check me-0.5"></i> Varsayılan
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- 7: Güncel Bakiye -->
                                        <td class="text-end">
                                            <span class="fw-bold <?= $balance > 0 ? 'text-success' : ($balance < 0 ? 'text-danger' : 'text-dark'); ?>" style="font-size: 13.5px;">
                                                <?= Helper::formattedMoney($balance, $moneyUnit); ?>
                                            </span>
                                        </td>

                                        <!-- 8: Açıklama -->
                                        <td>
                                            <span class="text-muted text-truncate d-inline-block" style="max-width: 220px;" title="<?= htmlspecialchars($case->description ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars($case->description ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        </td>

                                        <!-- 9: İşlemler -->
                                        <td class="text-end actions-column" style="white-space: nowrap;">
                                            <div class="d-inline-flex align-items-center justify-content-end gap-1">
                                                <a href="javascript:void(0);" class="btn btn-sm btn-icon btn-outline-info route-link"
                                                    data-page="financial/case/manage&id=<?= $id; ?>" data-tooltip="Kasa Hareketleri" title="Kasa Hareketleri">
                                                    <i class="ti ti-receipt-2"></i>
                                                </a>

                                                <?php if ($Auths->hasPermission("cash_register_add_update")): ?>
                                                    <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-case" data-id="<?= $id; ?>" data-tooltip="Düzenle / Detay" title="Düzenle / Detay">
                                                        <i class="ti ti-pencil"></i>
                                                    </button>
                                                <?php endif; ?>

                                                <div class="dropdown d-inline-block">
                                                    <button class="btn btn-sm btn-icon btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" title="Diğer İşlemler" aria-label="Diğer İşlemler">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item route-link"
                                                            data-page="financial/case/manage&id=<?= $id; ?>" href="javascript:void(0);">
                                                            <i class="ti ti-receipt-2 icon me-2 text-info"></i> Kasa Hareketleri
                                                        </a>

                                                        <?php if ($Auths->hasPermission("cash_register_add_update")): ?>
                                                            <a class="dropdown-item edit-case" data-id="<?= $id; ?>" href="javascript:void(0);">
                                                                <i class="ti ti-pencil icon me-2 text-primary"></i> Düzenle / Detay
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($Auths->hasPermission("intercash_transfer")): ?>
                                                            <a class="dropdown-item intercash-transfer" data-id="<?= $id; ?>" href="javascript:void(0);">
                                                                <i class="ti ti-arrows-left-right icon me-2 text-warning"></i> Kasalararası Virman
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if (!$isDefault): ?>
                                                            <a class="dropdown-item default-case" data-id="<?= $id; ?>" href="javascript:void(0);">
                                                                <i class="ti ti-star icon me-2 text-yellow"></i> Varsayılan Yap
                                                            </a>
                                                        <?php endif; ?>

                                                        <?php if ($Auths->hasPermission("cash_delete") && !$isDefault): ?>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item text-danger delete-case" data-id="<?= $id; ?>" href="javascript:void(0);">
                                                                <i class="ti ti-trash icon me-2"></i> Kasayı Sil
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    $i++;
                                endforeach;
                                ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
/* Header Action Buttons */
.case-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.case-header-icon-action,
.case-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.case-header-icon-action i,
.case-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#casePage .case-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    border-radius: 12px;
    overflow: hidden;
}

#caseSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#casePage .case-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    border-radius: 12px;
    overflow: hidden;
}

.case-table-card > .case-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.case-table-card > .card-header {
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
.case-search-wrap { position: relative; }
#case-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.case-search-wrap,
.case-search-wrap.input-icon {
    height: 32px !important;
}
.case-search-clear {
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
.case-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

/* DataTables Container overrides */
#casePage .table-responsive,
#casePage .case-table-area,
#casePage #caseTable_wrapper,
#casePage div.dt-container,
#casePage div.dt-container .dt-layout-row.dt-layout-table,
#casePage div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell,
#casePage div.dt-container .dt-layout-table,
#casePage div.dt-container .dt-scroll-body {
    height: auto !important;
    min-height: 0 !important;
    min-height: unset !important;
    max-height: none !important;
    flex-grow: 0 !important;
    border: none !important;
    box-shadow: none !important;
    overflow-y: visible !important;
}

#casePage .case-table-area {
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

/* Tablo Başlık Arama Satırlarını Gizle */
#caseTable thead tr.search-input-row,
#caseTable .search-input-row {
    display: none !important;
}

/* Tablo Çerçevesi & Kenarlıkları */
table#caseTable.data-table,
table#caseTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#caseTable.data-table tbody,
table#caseTable.dataTable tbody,
table#caseTable.data-table tbody tr:last-child,
table#caseTable.dataTable tbody tr:last-child,
#caseTable_wrapper .dt-layout-table,
#caseTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Başlık Hücreleri */
table#caseTable.data-table thead th {
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
table#caseTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Gövde Hücreleri */
table#caseTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#caseTable.data-table td.actions-column .btn.btn-sm.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
table#caseTable.data-table td.actions-column .btn.btn-sm.btn-icon i {
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    font-size: 13px !important;
}
table#caseTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#caseTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#caseTable.dataTable > tbody > tr:last-child > *,
table#caseTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#caseTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#caseTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#caseTable_wrapper .dt-layout-row:has(.dt-paging),
div#caseTable_wrapper .dt-layout-row:has(.dt-info) {
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
[data-bs-theme="dark"] table#caseTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#caseTable.data-table,
[data-bs-theme="dark"] table#caseTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#caseTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#caseTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#caseTable.data-table tbody tr:hover td {
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
[data-bs-theme="dark"] .case-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .case-summary-card,
[data-bs-theme="dark"] .case-table-card {
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
<?php require_once "content/intercash_transfer-modal.php"; ?>
<?php require_once "content/case-modal.php"; ?>