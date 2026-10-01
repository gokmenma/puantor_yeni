<?php
require_once "Model/Cari.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Security;

// Kullanıcının firmasını kontrol eder
$Auths->checkFirmReturn();

// Yetki kontrolü
$perm->checkAuthorize("cari_takip");

$cariModel = new Cari();
$firm_id = (int)($_SESSION["firm_id"] ?? 0);
$cariler = $cariModel->getCariByFirm($firm_id);
$totals = $cariModel->getFirmTotals($firm_id);
$total_balance = ($totals->total_borc ?? 0) - ($totals->total_alacak ?? 0);
$total_cari_count = count($cariler);

$borclu_count = 0;
$alacakli_count = 0;
$dengede_count = 0;
$cari_balances = [];

foreach ($cariler as $c) {
    $bal = $cariModel->getBalance($c->id);
    $cari_balances[$c->id] = $bal;
    if ($bal > 0) {
        $borclu_count++;
    } elseif ($bal < 0) {
        $alacakli_count++;
    } else {
        $dengede_count++;
    }
}
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'cari-summary-collapsed',
            localStorage.getItem('cari_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.cari-summary-collapsed #cariSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>
<div class="container-xl mt-1" id="cariPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-address-book" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Cari Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Cari hesaplar, borç/alacak takibi, bakiye durumu ve cari hareketleri
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon cari-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="cariColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxes will be rendered dynamically by JS -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark shadow-sm cari-header-action" data-bs-toggle="modal" data-bs-target="#cari-modal" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-plus me-1"></i> Yeni Cari Ekle
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle cari-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0);" class="dropdown-item" id="btnExportCariExcel">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
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

    <!-- KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="cariSummaryCards">
        <!-- Kart 1: Toplam Cari -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border cari-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM CARİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-users" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_cari_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Kayıtlı Cari Hesap
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm carileri göster">
                            <input type="radio" name="cari_balance_filter" value="" class="cari-filter" checked>
                            <span><i class="ti ti-users"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Toplam Borç (Verilen) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border cari-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM BORÇ (VERİLEN)</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-up-right" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($totals->total_borc ?? 0) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Borçlu: <strong class="text-danger"><?= $borclu_count ?> Cari</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Borçlu carileri göster">
                            <input type="radio" name="cari_balance_filter" value="Borçlu" class="cari-filter">
                            <span><i class="ti ti-arrow-up-right"></i> Borçlular</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Toplam Alacak (Alınan) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border cari-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM ALACAK (ALINAN)</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-down-left" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($totals->total_alacak ?? 0) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Alacaklı: <strong class="text-success"><?= $alacakli_count ?> Cari</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Alacaklı carileri göster">
                            <input type="radio" name="cari_balance_filter" value="Alacaklı" class="cari-filter">
                            <span><i class="ti ti-arrow-down-left"></i> Alacaklılar</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Net Bakiye Durumu -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border cari-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">NET BAKİYE DURUMU</span>
                        <div class="avatar avatar-sm rounded-2 <?= $total_balance < 0 ? 'bg-danger-lt text-danger' : ($total_balance > 0 ? 'bg-success-lt text-success' : 'bg-info-lt text-info') ?>" style="width: 32px; height: 32px;">
                            <i class="ti ti-scale" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold <?= $total_balance < 0 ? 'text-danger' : ($total_balance > 0 ? 'text-success' : 'text-dark') ?>" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney(abs($total_balance)) . ($total_balance < 0 ? ' (A)' : ($total_balance > 0 ? ' (B)' : '')) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Firma Dengesi
                        </span>
                        <span class="badge <?= $total_balance < 0 ? 'bg-danger-lt text-danger' : ($total_balance > 0 ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary') ?> fw-semibold" style="font-size: 10px;">
                            <?= $total_balance < 0 ? 'Alacak Fazlası' : ($total_balance > 0 ? 'Borç Fazlası' : 'Dengede') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card cari-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-address-book text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Cari Listesi</h4>
                                <a href="javascript:void(0);" class="btn-card-header-add" data-bs-toggle="modal" data-bs-target="#cari-modal" data-tooltip="Yeni Cari Ekle">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık arama, sütun filtreleme ve bakiye durumu</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon cari-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="cari-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="cari-search-clear" class="cari-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleCariSummary" class="btn btn-sm btn-outline-secondary btn-icon cari-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container -->
                <div class="table-responsive cari-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="cariTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">Sıra</th>
                                <th>Firma Adı</th>
                                <th>Yetkili</th>
                                <th>Telefon</th>
                                <th>Email</th>
                                <th>Adres</th>
                                <th class="text-end">Bakiye</th>
                                <th style="width: 95px; min-width: 95px;" class="no-export text-end" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            foreach ($cariler as $cari): 
                                $id = Security::encrypt($cari->id);
                                $balance = $cari_balances[$cari->id] ?? 0;
                            ?>
                                <tr data-cari-id="<?= $id; ?>" data-cari-name="<?= htmlspecialchars($cari->FirmaAdi ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="text-center text-muted fw-medium"><?= $i++; ?></td>
                                    <td>
                                        <a href="?p=cari/movements&id=<?= $id; ?>" class="fw-bold text-primary text-decoration-none d-inline-flex align-items-center">
                                            <i class="ti ti-building me-1 text-primary"></i>
                                            <?= htmlspecialchars($cari->FirmaAdi ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($cari->YetkiliAdi ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <?php if (!empty($cari->Telefon)): ?>
                                            <a href="tel:<?= htmlspecialchars($cari->Telefon, ENT_QUOTES, 'UTF-8'); ?>" class="text-reset d-inline-flex align-items-center">
                                                <i class="ti ti-phone me-1 text-muted"></i>
                                                <?= htmlspecialchars($cari->Telefon, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($cari->Email)): ?>
                                            <a href="mailto:<?= htmlspecialchars($cari->Email, ENT_QUOTES, 'UTF-8'); ?>" class="text-reset d-inline-flex align-items-center">
                                                <i class="ti ti-mail me-1 text-muted"></i>
                                                <?= htmlspecialchars($cari->Email, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 250px;" title="<?= htmlspecialchars($cari->Adres ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($cari->Adres ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <span class="fw-semibold <?= $balance < 0 ? 'text-danger' : ($balance > 0 ? 'text-success' : 'text-muted'); ?>">
                                            <?= Helper::formattedMoney(abs($balance)) . ($balance < 0 ? ' (A)' : ($balance > 0 ? ' (B)' : '')); ?>
                                        </span>
                                    </td>
                                    <td class="text-end actions-column">
                                        <div class="d-inline-flex align-items-center gap-1">
                                            <a href="?p=cari/movements&id=<?= $id; ?>" class="btn btn-sm btn-icon btn-outline-info" data-tooltip="Cari Hareketleri" title="Cari Hareketleri">
                                                <i class="ti ti-list"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-cari" data-id="<?= $id; ?>" data-tooltip="Düzenle" title="Düzenle">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-cari" data-id="<?= $id; ?>" data-tooltip="Sil" title="Sil">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include_once "modals/cari-modal.php"; ?>

<style>
.cari-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.cari-header-icon-action,
.cari-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.cari-header-icon-action i,
.cari-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#cariPage .cari-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    border-radius: 12px;
    overflow: hidden;
}

#cariSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#cariPage .cari-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    border-radius: 12px;
    overflow: hidden;
}

.cari-table-card > .cari-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.cari-table-card > .card-header {
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

.cari-search-wrap { position: relative; }
#cari-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.cari-search-wrap,
.cari-search-wrap.input-icon {
    height: 32px !important;
}
.cari-search-clear {
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
.cari-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#cariTable_wrapper,
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
table#cariTable.data-table,
table#cariTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#cariTable.data-table tbody,
table#cariTable.dataTable tbody,
table#cariTable.data-table tbody tr:last-child,
table#cariTable.dataTable tbody tr:last-child,
#cariTable_wrapper .dt-layout-table,
#cariTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#cariTable.data-table thead th {
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
table#cariTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Sütun Başlığı İçi Filtre Butonu ve Düzeni */
table#cariTable.data-table thead th .dt-header-content {
    min-height: 24px;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#cariTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#cariTable.data-table td.actions-column .btn.btn-sm.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
table#cariTable.data-table td.actions-column .btn.btn-sm.btn-icon i {
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    font-size: 13px !important;
}
table#cariTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#cariTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#cariTable.dataTable > tbody > tr:last-child > *,
table#cariTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#cariTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#cariTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#cariTable_wrapper .dt-layout-row:has(.dt-paging),
div#cariTable_wrapper .dt-layout-row:has(.dt-info) {
    margin: 0 !important;
    margin-top: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
    position: static !important;
    flex-shrink: 0 !important;
}

/* Modern Select Styling */
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
[data-bs-theme="dark"] table#cariTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#cariTable.data-table,
[data-bs-theme="dark"] table#cariTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#cariTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#cariTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#cariTable.data-table tbody tr:hover td {
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
[data-bs-theme="dark"] .cari-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .cari-summary-card,
[data-bs-theme="dark"] .cari-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

#cariTable th:last-child,
#cariTable td:last-child {
    width: 95px !important;
    min-width: 95px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 10px !important;
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
    var $summaryToggle = $('#toggleCariSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('cari-summary-collapsed');
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
        var isCollapsed = document.documentElement.classList.toggle('cari-summary-collapsed');
        try {
            localStorage.setItem('cari_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    // Sütun Konfigürasyonu
    var columnConfig = {
        1: { label: 'Firma Adı', default: true },
        2: { label: 'Yetkili', default: true },
        3: { label: 'Telefon', default: true },
        4: { label: 'Email', default: true },
        5: { label: 'Adres', default: true },
        6: { label: 'Bakiye', default: true }
    };

    var savedVisibility = {};
    try {
        var rawSaved = localStorage.getItem('cari_column_visibility');
        if (rawSaved) {
            savedVisibility = JSON.parse(rawSaved);
        }
    } catch(e) {}

    var $cariTable = $('#cariTable');
    var tableOptions = {
        order: [[0, 'asc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        autoWidth: false,
        colReorder: true,
        columnDefs: [
            { targets: [0, 7], orderable: false, searchable: false },
            { targets: 0, className: 'text-center' },
            { targets: 6, className: 'text-end' },
            { targets: 7, width: '95px', className: 'text-end no-export actions-column' }
        ],
        layout: {
            bottomStart: ['info', 'pageLength'],
            bottomEnd: 'paging',
            topStart: null,
            topEnd: null
        },
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'd-none btn-export-excel-hidden',
                title: 'Cari Listesi',
                exportOptions: {
                    columns: ':visible:not(.no-export)'
                }
            }
        ],
        language: { url: 'src/tr.json' },
        initComplete: function() {
            var api = this.api();

            $.each(columnConfig, function(colIdx, conf) {
                colIdx = parseInt(colIdx, 10);
                var isVisible = (typeof savedVisibility[colIdx] === 'boolean')
                    ? savedVisibility[colIdx]
                    : conf.default;
                api.column(colIdx).visible(isVisible, false);
            });

            if (typeof window.initDataTableColumnFilters === 'function') {
                window.initDataTableColumnFilters($('#cariTable'), api);
            }
            if (typeof window.initPuantorDTManager === 'function') {
                window.initPuantorDTManager($('#cariTable'), api);
            }

            api.columns.adjust().draw(false);
        }
    };

    var table = $.fn.DataTable.isDataTable($cariTable[0])
        ? $cariTable.DataTable()
        : $cariTable.DataTable(tableOptions);

    // Sütun Göster / Gizle Menüsü
    var $colvisMenu = $('#cariColvisMenu');
    $colvisMenu.empty();

    if (table) {
        $.each(columnConfig, function(colIdx, conf) {
            colIdx = parseInt(colIdx, 10);
            var isVisible = (savedVisibility && typeof savedVisibility[colIdx] === 'boolean') ? savedVisibility[colIdx] : conf.default;

            var $item = $(
                '<label class="dropdown-item d-flex align-items-center py-1.5 px-3 rounded-2 cursor-pointer" style="font-size: 0.85rem;">' +
                '<div class="form-check mb-0 w-100">' +
                '<input class="form-check-input me-2 mt-0 cari-col-trigger" type="checkbox" data-column="' + colIdx + '"' + (isVisible ? ' checked' : '') + '>' +
                '<span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">' + conf.label + '</span>' +
                '</div>' +
                '</label>'
            );
            $colvisMenu.append($item);
        });
    }

    $colvisMenu.on('change', '.cari-col-trigger', function(e) {
        e.stopPropagation();
        var colIdx = parseInt($(this).data('column'), 10);
        var isChecked = $(this).is(':checked');

        table.column(colIdx).visible(isChecked, true);

        savedVisibility[colIdx] = isChecked;
        try {
            localStorage.setItem('cari_column_visibility', JSON.stringify(savedVisibility));
        } catch(err) {}
    });

    $colvisMenu.on('click', function(e) {
        e.stopPropagation();
    });

    // Özet Kartı Radyo Filtreleri
    $('.cari-filter').on('change', function() {
        var filterVal = $(this).val();
        if (!table) return;

        if (filterVal === 'Borçlu') {
            table.column(6).search('\\(B\\)', true, false).draw();
        } else if (filterVal === 'Alacaklı') {
            table.column(6).search('\\(A\\)', true, false).draw();
        } else {
            table.column(6).search('').draw();
        }
    });

    // Hızlı Genel Arama
    var searchTimer = null;
    $('#cari-fast-search').on('input', function() {
        var val = this.value;
        $('#cari-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (table) table.search(val).draw();
        }, 300);
    });

    $('#cari-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#cari-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        if (table) table.search('').draw();
    });

    // Excel Export Butonu
    $('#btnExportCariExcel').off('click').on('click', function(e) {
        e.preventDefault();
        if (table && table.button) {
            table.button('.buttons-excel, .btn-export-excel-hidden').trigger();
        }
    });

    // Cari Modal Sıfırlama
    $('#cari-modal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-cari') && !$(e.relatedTarget).hasClass('context-edit-cari')) {
            $('#cariForm')[0].reset();
            $('#cari_id').val(0);
            $('#cari-modal .modal-title').html('<i class="ti ti-address-book me-2 text-primary"></i>Yeni Cari Ekle');
        }
    });

    // Cari Düzenleme
    function editCariAction(id) {
        $.ajax({
            url: '/api/cari/get_cari.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(data) {
                if (data.status === 'success') {
                    $('#cari_id').val(id);
                    $('#FirmaAdi').val(data.cari.FirmaAdi);
                    $('#YetkiliAdi').val(data.cari.YetkiliAdi);
                    $('#Telefon').val(data.cari.Telefon);
                    $('#Email').val(data.cari.Email);
                    $('#Adres').val(data.cari.Adres);
                    $('#notlar').val(data.cari.notlar);
                    $('#cari-modal .modal-title').html('<i class="ti ti-edit me-2 text-primary"></i>Cari Güncelle');
                    $('#cari-modal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: data.message || 'Cari bilgisi alınamadı.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Sunucu ile iletişim kurulamadı.'
                });
            }
        });
    }

    $(document).on('click', '.edit-cari', function() {
        var id = $(this).data('id');
        editCariAction(id);
    });

    // Cari Kaydetme
    $('#saveCari').click(function() {
        var form = document.getElementById('cariForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        var formData = $('#cariForm').serialize();
        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...');

        $.ajax({
            url: '/api/cari/save_cari.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(data) {
                $btn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Kaydet');
                if (data.status === 'success') {
                    $('#cari-modal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı',
                        text: data.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: data.message
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Kaydet');
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Kayıt sırasında bir hata oluştu.'
                });
            }
        });
    });

    // Cari Silme
    function deleteCariAction(id) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Cari kaydı ve ilgili bağlantılar etkilenecektir!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63939',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="ti ti-trash me-1"></i> Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/cari/delete_cari.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Silindi',
                                text: data.message,
                                timer: 1200,
                                showConfirmButton: false
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata',
                                text: data.message
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: 'Silme işlemi sırasında sunucu hatası oluştu.'
                        });
                    }
                });
            }
        });
    }

    $(document).on('click', '.delete-cari', function() {
        var id = $(this).data('id');
        deleteCariAction(id);
    });

    // Tabloda Sağ Tık (Custom Context Menu)
    $(document).on('contextmenu', '#cariTable tbody tr', function(e) {
        var $tr = $(this);
        var cariId = $tr.attr('data-cari-id');
        var cariName = $tr.attr('data-cari-name') || 'Cari İşlemleri';

        if (!cariId) return;

        e.preventDefault();

        $('#cariTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-address-book me-1"></i> ${$('<div>').text(cariName).html()}</div>
            <a href="?p=cari/movements&id=${cariId}"><i class="ti ti-list text-info"></i> Cari Hareketleri</a>
            <a href="javascript:void(0);" class="context-edit-cari" data-id="${cariId}"><i class="ti ti-edit text-warning"></i> Bilgileri Güncelle</a>
            <div class="cm-divider"></div>
            <a href="javascript:void(0);" class="cm-danger context-delete-cari" data-id="${cariId}"><i class="ti ti-trash"></i> Cariyi Sil</a>
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

    $(document).on('click', '.context-edit-cari', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#customContextMenu').hide();
        $('#cariTable tbody tr').removeClass('context-menu-active');
        editCariAction(id);
    });

    $(document).on('click', '.context-delete-cari', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('#customContextMenu').hide();
        $('#cariTable tbody tr').removeClass('context-menu-active');
        deleteCariAction(id);
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#cariTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#cariTable tbody tr').removeClass('context-menu-active');
    });
});
</script>
