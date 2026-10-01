<?php
require_once "Model/Cari.php";
require_once "Model/CariHareketleri.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/date.php";
require_once "App/Helper/security.php";
require_once "App/Helper/company.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

// Kullanıcının firmasını kontrol eder
$Auths->checkFirmReturn();

// Yetki kontrolü
$perm->checkAuthorize("cari_hareketleri");

$cari_id_enc = $_GET['id'] ?? null;
if (!$cari_id_enc) {
    Helper::redirect("?p=cari/list");
}

$cari_id = Security::decrypt($cari_id_enc);
$cariModel = new Cari();
$cari = $cariModel->find($cari_id);

if (!$cari || $cari->firma != $_SESSION['firm_id']) {
    Helper::redirect("?p=cari/list");
}

$moveModel = new CariHareketleri();
$movements = $moveModel->getMovementsByCari($cari_id);

$companyHelper = new CompanyHelper();
$firm_name = $companyHelper->getFirmName($_SESSION['firm_id']);

$total_borc = 0;
$total_alacak = 0;
$movement_count = count($movements);
$borc_count = 0;
$alacak_count = 0;

foreach ($movements as $m) {
    $total_borc += (float)$m->borc;
    $total_alacak += (float)$m->alacak;
    if ((float)$m->borc > 0) {
        $borc_count++;
    }
    if ((float)$m->alacak > 0) {
        $alacak_count++;
    }
}
$net_balance = $total_borc - $total_alacak;
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'cari-movements-summary-collapsed',
            localStorage.getItem('cari_movements_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.cari-movements-summary-collapsed #cariMovementsSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}

@media print {
    .navbar, aside, #navbar, .page-header.d-print-none, 
    .footer, .fab-menu, .dropdown-toggle, 
    .dataTables_wrapper .row:first-child, 
    .dataTables_wrapper .row:last-child,
    .dt-container .dt-layout-row:not(.dt-layout-table),
    .datatable thead tr:has(input), .datatable thead tr + tr:has(input),
    .datatable thead input,
    header.navbar-expand-md,
    .d-print-none {
        display: none !important;
    }
    
    thead tr:nth-child(2) {
        display: none !important;
    }
    
    thead tr:first-child {
        display: table-row !important;
    }

    body {
        background-color: #fff !important;
        color: #000 !important;
        font-size: 11px !important;
    }

    .page-wrapper {
        padding: 0 !important;
        margin: 0 !important;
    }

    .container-xl {
        width: 100% !important;
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    .card {
        border: none !important;
        box-shadow: none !important;
    }

    .table-responsive {
        overflow: visible !important;
    }

    table.table {
        border: 1px solid #cbd5e1 !important;
        width: 100% !important;
        border-collapse: collapse !important;
    }

    table.table th, table.table td {
        border: 1px solid #cbd5e1 !important;
        padding: 5px 8px !important;
    }
    
    .text-danger {
        color: #dc2626 !important;
    }
    
    .text-success {
        color: #16a34a !important;
    }
}
</style>

<div class="container-xl mt-1" id="cariMovementsPage">

    <!-- Yazdırma Başlığı (Sadece Yazdırırken Görünür) -->
    <div class="d-none d-print-block mb-4" style="border-bottom: 2px solid #0f172a; padding-bottom: 12px;">
        <div class="row align-items-center">
            <div class="col-6">
                <h2 class="mb-1 fw-bold" style="color: #0f172a; font-size: 18px;"><?= htmlspecialchars($firm_name, ENT_QUOTES, 'UTF-8'); ?></h2>
                <div class="text-muted small">CARİ HESAP EKSTRESİ & HAREKET DÖKÜMÜ</div>
            </div>
            <div class="col-6 text-end">
                <div class="small text-muted">Döküm Tarihi: <strong><?= date('d.m.Y H:i'); ?></strong></div>
                <div class="small text-muted">Toplam Kayıt: <strong><?= $movement_count; ?> adet</strong></div>
            </div>
        </div>
        
        <div class="row mt-3 pt-3" style="border-top: 1px dashed #cbd5e1;">
            <div class="col-7">
                <div class="text-uppercase fw-bold text-muted small mb-1">CARİ BİLGİLERİ</div>
                <div class="h3 mb-1 fw-bold text-dark"><?= htmlspecialchars($cari->FirmaAdi, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php if (!empty($cari->YetkiliAdi)): ?>
                    <div class="small"><strong>Yetkili:</strong> <?= htmlspecialchars($cari->YetkiliAdi, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if (!empty($cari->Telefon)): ?>
                    <div class="small"><strong>Telefon:</strong> <?= htmlspecialchars($cari->Telefon, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if (!empty($cari->Email)): ?>
                    <div class="small"><strong>E-Posta:</strong> <?= htmlspecialchars($cari->Email, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
                <?php if (!empty($cari->Adres)): ?>
                    <div class="small"><strong>Adres:</strong> <?= htmlspecialchars($cari->Adres, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endif; ?>
            </div>
            <div class="col-5 text-end">
                <div class="text-uppercase fw-bold text-muted small mb-1">BAKİYE ÖZETİ</div>
                <div class="small">Toplam Borç: <strong class="text-danger"><?= Helper::formattedMoney($total_borc); ?></strong></div>
                <div class="small">Toplam Alacak: <strong class="text-success"><?= Helper::formattedMoney($total_alacak); ?></strong></div>
                <div class="mt-2 pt-2 border-top">
                    <div class="small fw-bold text-muted">GÜNCEL BAKİYE</div>
                    <div class="h2 mb-0 fw-bold <?= $net_balance < 0 ? 'text-danger' : ($net_balance > 0 ? 'text-success' : 'text-dark'); ?>">
                        <?= Helper::formattedMoney(abs($net_balance)); ?>
                        <span style="font-size: 13px;">(<?= $net_balance < 0 ? 'Alacaklı' : ($net_balance > 0 ? 'Borçlu' : 'Dengede'); ?>)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-receipt-2" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Cari Hareketleri: <?= htmlspecialchars($cari->FirmaAdi, ENT_QUOTES, 'UTF-8'); ?><?= $cari->YetkiliAdi ? ' (' . htmlspecialchars($cari->YetkiliAdi, ENT_QUOTES, 'UTF-8') . ')' : ''; ?>
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            <?= !empty($cari->Telefon) ? htmlspecialchars($cari->Telefon, ENT_QUOTES, 'UTF-8') . ' | ' : '' ?>
                            <?= !empty($cari->Email) ? htmlspecialchars($cari->Email, ENT_QUOTES, 'UTF-8') . ' | ' : '' ?>
                            Borç / Alacak hareketleri, tahsilat, ödeme ve bakiye dökümü
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="?p=cari/list" class="btn btn-sm btn-outline-secondary cari-movements-header-action" title="Cari Listesine Dön">
                        <i class="ti ti-arrow-left me-1"></i> Geri
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon cari-movements-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="movementsColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxes will be rendered dynamically by JS -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark shadow-sm cari-movements-header-action" data-bs-toggle="modal" data-bs-target="#movement-modal" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-plus me-1"></i> Yeni Hareket Ekle
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle cari-movements-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0);" class="dropdown-item" id="btnExportMovementsExcel">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <a href="javascript:window.print();" class="dropdown-item">
                                <i class="ti ti-printer icon me-2 text-secondary"></i> Yazdır / Ekstre
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="?p=cari/list" class="dropdown-item">
                                <i class="ti ti-arrow-left icon me-2 text-primary"></i> Cari Listesine Dön
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="cariMovementsSummaryCards">
        <!-- Kart 1: Toplam İşlem Sayısı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border cari-movements-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM İŞLEM</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-receipt-2" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($movement_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Hareket Kaydı
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm hareketleri göster">
                            <input type="radio" name="movement_type_filter" value="" class="movement-filter" checked>
                            <span><i class="ti ti-list"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Toplam Borç (Verilen / Ödeme) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border cari-movements-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM VERİLEN (BORÇ)</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-up-right" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-danger" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_borc) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Borç: <strong class="text-danger"><?= $borc_count ?> İşlem</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece borç hareketlerini göster">
                            <input type="radio" name="movement_type_filter" value="Ödeme (Borç)" class="movement-filter">
                            <span><i class="ti ti-arrow-up-right"></i> Borç</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Toplam Alacak (Alınan / Tahsilat) -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border cari-movements-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM ALINAN (ALACAK)</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-down-left" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-success" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_alacak) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Alacak: <strong class="text-success"><?= $alacak_count ?> İşlem</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece alacak hareketlerini göster">
                            <input type="radio" name="movement_type_filter" value="Tahsilat (Alacak)" class="movement-filter">
                            <span><i class="ti ti-arrow-down-left"></i> Alacak</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Güncel Net Bakiye -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border cari-movements-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">GÜNCEL BAKİYE</span>
                        <div class="avatar avatar-sm rounded-2 <?= $net_balance < 0 ? 'bg-danger-lt text-danger' : ($net_balance > 0 ? 'bg-success-lt text-success' : 'bg-info-lt text-info') ?>" style="width: 32px; height: 32px;">
                            <i class="ti ti-scale" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold <?= $net_balance < 0 ? 'text-danger' : ($net_balance > 0 ? 'text-success' : 'text-dark') ?>" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney(abs($net_balance)) . ($net_balance < 0 ? ' (A)' : ($net_balance > 0 ? ' (B)' : '')) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Hesap Durumu
                        </span>
                        <span class="badge <?= $net_balance < 0 ? 'bg-danger-lt text-danger' : ($net_balance > 0 ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary') ?> fw-semibold" style="font-size: 10px;">
                            <?= $net_balance < 0 ? 'Alacaklı' : ($net_balance > 0 ? 'Borçlu' : 'Dengede') ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card cari-movements-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Hareket Dökümü</h4>
                                <a href="javascript:void(0);" class="btn-card-header-add" data-bs-toggle="modal" data-bs-target="#movement-modal" data-tooltip="Yeni Hareket Ekle" title="Yeni Hareket Ekle">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">İşlem geçmişi, borç, alacak ve yürüyen bakiye takibi</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon cari-movements-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="movements-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="movements-search-clear" class="cari-movements-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleMovementsSummary" class="btn btn-sm btn-outline-secondary btn-icon cari-movements-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container -->
                <div class="table-responsive cari-movements-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="movementsTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">Sıra</th>
                                <th>Tarih</th>
                                <th>Belge No</th>
                                <th>Açıklama</th>
                                <th>İşlem Türü</th>
                                <th class="text-end">Borç (₺)</th>
                                <th class="text-end">Alacak (₺)</th>
                                <th class="text-end">Bakiye (₺)</th>
                                <th style="width: 85px; min-width: 85px;" class="no-export text-end" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $running_balance = 0;
                            $i = 1;
                            foreach ($movements as $m): 
                                $mid = Security::encrypt($m->id);
                                $running_balance += ((float)$m->borc - (float)$m->alacak);
                                $is_borc = (float)$m->borc > 0;
                                $is_alacak = (float)$m->alacak > 0;
                            ?>
                            <tr data-movement-id="<?= $mid; ?>">
                                <td class="text-center text-muted fw-medium"><?= $i++; ?></td>
                                <td>
                                    <span class="fw-medium text-dark">
                                        <i class="ti ti-calendar me-1 text-muted"></i>
                                        <?= Date::dmY($m->islem_tarihi); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($m->belge_no)): ?>
                                        <span class="badge bg-secondary-lt fw-medium">
                                            <i class="ti ti-file-text me-1"></i><?= htmlspecialchars($m->belge_no, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="text-truncate d-inline-block" style="max-width: 320px;" title="<?= htmlspecialchars($m->aciklama ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($m->aciklama ?: '-', ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($is_borc): ?>
                                        <span class="badge bg-danger-lt fw-semibold">
                                            <i class="ti ti-arrow-up-right me-1"></i>Ödeme (Borç)
                                        </span>
                                    <?php elseif ($is_alacak): ?>
                                        <span class="badge bg-success-lt fw-semibold">
                                            <i class="ti ti-arrow-down-left me-1"></i>Tahsilat (Alacak)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary-lt fw-semibold">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($is_borc): ?>
                                        <span class="fw-bold text-danger">
                                            <?= Helper::formattedMoney($m->borc); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($is_alacak): ?>
                                        <span class="fw-bold text-success">
                                            <?= Helper::formattedMoney($m->alacak); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <span class="fw-bold <?= $running_balance < 0 ? 'text-danger' : ($running_balance > 0 ? 'text-success' : 'text-muted'); ?>">
                                        <?= Helper::formattedMoney(abs($running_balance)) . ($running_balance < 0 ? ' (A)' : ($running_balance > 0 ? ' (B)' : '')); ?>
                                    </span>
                                </td>
                                <td class="text-end actions-column d-print-none">
                                    <div class="d-inline-flex align-items-center gap-1">
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-primary edit-movement" data-id="<?= $mid; ?>" data-tooltip="Düzenle" title="Düzenle">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-movement" data-id="<?= $mid; ?>" data-tooltip="Sil" title="Sil">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold" style="background-color: #f8fafc;">
                                <td colspan="5" class="text-end text-uppercase" style="font-size: 11.5px; letter-spacing: 0.5px;">GENEL TOPLAM:</td>
                                <td class="text-end text-danger" style="font-size: 13.5px;"><?= Helper::formattedMoney($total_borc); ?></td>
                                <td class="text-end text-success" style="font-size: 13.5px;"><?= Helper::formattedMoney($total_alacak); ?></td>
                                <td class="text-end <?= $running_balance < 0 ? 'text-danger' : ($running_balance > 0 ? 'text-success' : 'text-dark'); ?>" style="font-size: 13.5px;">
                                    <?= Helper::formattedMoney(abs($running_balance)) . ($running_balance < 0 ? ' (A)' : ($running_balance > 0 ? ' (B)' : '')); ?>
                                </td>
                                <td class="no-export d-print-none"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . "/modals/movement-modal.php"; ?>

<style>
.cari-movements-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.cari-movements-header-icon-action,
.cari-movements-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.cari-movements-header-icon-action i,
.cari-movements-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#cariMovementsPage .cari-movements-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    border-radius: 12px;
    overflow: hidden;
}

#cariMovementsSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#cariMovementsPage .cari-movements-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    border-radius: 12px;
    overflow: hidden;
}

.cari-movements-table-card > .cari-movements-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.cari-movements-table-card > .card-header {
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

.cari-movements-search-wrap { position: relative; }
#movements-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.cari-movements-search-wrap,
.cari-movements-search-wrap.input-icon {
    height: 32px !important;
}
.cari-movements-search-clear {
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
.cari-movements-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#movementsTable_wrapper,
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
table#movementsTable.data-table,
table#movementsTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#movementsTable.data-table tbody,
table#movementsTable.dataTable tbody,
table#movementsTable.data-table tbody tr:last-child,
table#movementsTable.dataTable tbody tr:last-child,
#movementsTable_wrapper .dt-layout-table,
#movementsTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#movementsTable.data-table thead th {
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
table#movementsTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Sütun Başlığı İçi Filtre Butonu ve Düzeni */
table#movementsTable.data-table thead th .dt-header-content {
    min-height: 24px;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#movementsTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#movementsTable.data-table td.actions-column .btn.btn-sm.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
table#movementsTable.data-table td.actions-column .btn.btn-sm.btn-icon i {
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    font-size: 13px !important;
}
table#movementsTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#movementsTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#movementsTable.dataTable > tbody > tr:last-child > *,
table#movementsTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#movementsTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Footer */
table#movementsTable tfoot td {
    padding: 8px 10px !important;
    border-top: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-bottom: none !important;
    border-left: none !important;
}
table#movementsTable tfoot td:last-child {
    border-right: none !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#movementsTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#movementsTable_wrapper .dt-layout-row:has(.dt-paging),
div#movementsTable_wrapper .dt-layout-row:has(.dt-info) {
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
[data-bs-theme="dark"] table#movementsTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#movementsTable.data-table,
[data-bs-theme="dark"] table#movementsTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#movementsTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#movementsTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#movementsTable.data-table tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] table#movementsTable tfoot td {
    background-color: #0f172a !important;
    border-top-color: #334155 !important;
    border-right-color: #334155 !important;
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
[data-bs-theme="dark"] .cari-movements-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .cari-movements-summary-card,
[data-bs-theme="dark"] .cari-movements-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

#movementsTable th:last-child,
#movementsTable td:last-child {
    width: 85px !important;
    min-width: 85px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 10px !important;
}

.btn-card-header-add {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 22px;
    height: 22px;
    background: #f1f5f9;
    color: #475569;
    border-radius: 6px;
    text-decoration: none;
    transition: all .15s ease;
    font-size: 13px;
}
.btn-card-header-add:hover {
    background: #206bc4;
    color: #ffffff;
}
</style>

<script>
$(document).ready(function() {
    // Özet Kartları Aç/Kapat Mantığı
    var $summaryToggle = $('#toggleMovementsSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('cari-movements-summary-collapsed');
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
        var isCollapsed = document.documentElement.classList.toggle('cari-movements-summary-collapsed');
        try {
            localStorage.setItem('cari_movements_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    // Sütun Konfigürasyonu
    var columnConfig = {
        1: { label: 'Tarih', default: true },
        2: { label: 'Belge No', default: true },
        3: { label: 'Açıklama', default: true },
        4: { label: 'İşlem Türü', default: true },
        5: { label: 'Borç (₺)', default: true },
        6: { label: 'Alacak (₺)', default: true },
        7: { label: 'Bakiye (₺)', default: true }
    };

    var savedVisibility = {};
    try {
        var rawSaved = localStorage.getItem('cari_movements_column_visibility');
        if (rawSaved) {
            savedVisibility = JSON.parse(rawSaved);
        }
    } catch(e) {}

    var $movementsTable = $('#movementsTable');
    var tableOptions = {
        order: [[1, 'desc']], // Tarihe göre azalan
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        autoWidth: false,
        colReorder: true,
        columnDefs: [
            { targets: [0, 8], orderable: false, searchable: false },
            { targets: 0, className: 'text-center' },
            { targets: [5, 6, 7], className: 'text-end' },
            { targets: 8, width: '85px', className: 'text-end no-export actions-column' }
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
                title: 'Cari Hareketleri - <?= addslashes(htmlspecialchars($cari->FirmaAdi, ENT_QUOTES, 'UTF-8')) ?>',
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
                window.initDataTableColumnFilters($('#movementsTable'), api);
            }
            if (typeof window.initPuantorDTManager === 'function') {
                window.initPuantorDTManager($('#movementsTable'), api);
            }

            api.columns.adjust().draw(false);
        }
    };

    var table = $.fn.DataTable.isDataTable($movementsTable[0])
        ? $movementsTable.DataTable()
        : $movementsTable.DataTable(tableOptions);

    // Sütun Göster / Gizle Menüsü
    var $colvisMenu = $('#movementsColvisMenu');
    $colvisMenu.empty();

    if (table) {
        $.each(columnConfig, function(colIdx, conf) {
            colIdx = parseInt(colIdx, 10);
            var isVisible = (savedVisibility && typeof savedVisibility[colIdx] === 'boolean') ? savedVisibility[colIdx] : conf.default;

            var $item = $(
                '<label class="dropdown-item d-flex align-items-center py-1.5 px-3 rounded-2 cursor-pointer" style="font-size: 0.85rem;">' +
                '<div class="form-check mb-0 w-100">' +
                '<input class="form-check-input me-2 mt-0 movement-col-trigger" type="checkbox" data-column="' + colIdx + '"' + (isVisible ? ' checked' : '') + '>' +
                '<span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">' + conf.label + '</span>' +
                '</div>' +
                '</label>'
            );
            $colvisMenu.append($item);
        });
    }

    $colvisMenu.on('change', '.movement-col-trigger', function(e) {
        e.stopPropagation();
        var colIdx = parseInt($(this).data('column'), 10);
        var isChecked = $(this).is(':checked');

        table.column(colIdx).visible(isChecked, true);

        savedVisibility[colIdx] = isChecked;
        try {
            localStorage.setItem('cari_movements_column_visibility', JSON.stringify(savedVisibility));
        } catch(err) {}
    });

    $colvisMenu.on('click', function(e) {
        e.stopPropagation();
    });

    // Özet Kartı Filtreleri (İşlem Türü)
    $('.movement-filter').on('change', function() {
        var filterVal = $(this).val();
        if (!table) return;

        if (filterVal) {
            table.column(4).search(filterVal, false, false).draw();
        } else {
            table.column(4).search('').draw();
        }
    });

    // Hızlı Arama
    var searchTimer = null;
    $('#movements-fast-search').on('input', function() {
        var val = this.value;
        $('#movements-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (table) table.search(val).draw();
        }, 300);
    });

    $('#movements-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#movements-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        if (table) table.search('').draw();
    });

    // Excel Dışa Aktar Butonu
    $('#btnExportMovementsExcel').off('click').on('click', function(e) {
        e.preventDefault();
        if (table && table.button) {
            table.button('.buttons-excel, .btn-export-excel-hidden').trigger();
        }
    });

    // Flatpickr Başlatma
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.flatpickr', {
            dateFormat: 'd.m.Y',
            locale: 'tr',
            allowInput: true
        });
    }

    // Modal Sıfırlama
    $('#movement-modal').on('show.bs.modal', function(e) {
        if (!$(e.relatedTarget).hasClass('edit-movement')) {
            $('#movementForm')[0].reset();
            $('#movement_id').val(0);
            $('#movement-modal .modal-title').html('<i class="ti ti-receipt-2 me-2 text-primary"></i>Yeni Hareket Ekle');
            $('input[name="mType"][value="alacak"]').prop('checked', true);
            $('#islem_tarihi').val('<?= date('d.m.Y'); ?>');
        }
    });

    // Hareket Düzenleme Açılışı
    $(document).on('click', '.edit-movement', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.ajax({
            url: '/api/cari/get_movement.php',
            type: 'POST',
            data: { id: id },
            dataType: 'json',
            success: function(data) {
                if (data.status === 'success' && data.movement) {
                    $('#movement_id').val(id);
                    $('#islem_tarihi').val(data.movement.islem_tarihi_fmt);
                    $('#belge_no').val(data.movement.belge_no || '');
                    $('#aciklama').val(data.movement.aciklama || '');
                    
                    var amount = 0;
                    if (parseFloat(data.movement.borc) > 0) {
                        $('input[name="mType"][value="borc"]').prop('checked', true);
                        amount = parseFloat(data.movement.borc);
                    } else {
                        $('input[name="mType"][value="alacak"]').prop('checked', true);
                        amount = parseFloat(data.movement.alacak);
                    }
                    $('#mAmount').val(amount);
                    
                    $('#movement-modal .modal-title').html('<i class="ti ti-edit me-2 text-primary"></i>Hareket Güncelle');
                    $('#movement-modal').modal('show');
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: data.message || 'Hareket bilgisi alınamadı.'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Sunucu ile iletişim kurulurken bir hata oluştu.'
                });
            }
        });
    });

    // Hareket Kaydetme
    $('#saveMovement').off('click').on('click', function() {
        var type = $('input[name="mType"]:checked').val();
        var amount = parseFloat($('#mAmount').val() || 0);
        var islem_tarihi = $('#islem_tarihi').val().trim();
        
        if (!amount || amount <= 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Uyarı',
                text: 'Lütfen geçerli bir tutar giriniz.'
            });
            $('#mAmount').focus();
            return;
        }

        if (!islem_tarihi) {
            Swal.fire({
                icon: 'warning',
                title: 'Uyarı',
                text: 'Lütfen işlem tarihini seçiniz.'
            });
            return;
        }

        var formData = {
            id: $('#movement_id').val(),
            cari_id: '<?= htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>',
            islem_tarihi: islem_tarihi,
            belge_no: $('#belge_no').val().trim(),
            aciklama: $('#aciklama').val().trim(),
            borc: type === 'borc' ? amount : 0,
            alacak: type === 'alacak' ? amount : 0
        };

        var $btn = $(this);
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...');

        $.ajax({
            url: '/api/cari/save_movement.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(data) {
                $btn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Kaydet');
                if (data.status === 'success') {
                    $('#movement-modal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı',
                        text: data.message || 'Hareket başarıyla kaydedildi.',
                        showConfirmButton: false,
                        timer: 1200
                    }).then(function() {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: data.message || 'Kayıt sırasında bir hata oluştu.'
                    });
                }
            },
            error: function() {
                $btn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Kaydet');
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'Sunucu ile iletişim kurulurken bir hata oluştu.'
                });
            }
        });
    });

    // Hareket Silme
    $(document).on('click', '.delete-movement', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu cari hareket kaydı silinecektir!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#64748b',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then(function(result) {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/cari/delete_movement.php',
                    type: 'POST',
                    data: { id: id },
                    dataType: 'json',
                    success: function(data) {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Silindi',
                                text: data.message || 'Hareket başarıyla silindi.',
                                showConfirmButton: false,
                                timer: 1200
                            }).then(function() {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata',
                                text: data.message || 'Silme sırasında bir hata oluştu.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: 'Sunucu ile iletişim kurulurken bir hata oluştu.'
                        });
                    }
                });
            }
        });
    });
});
</script>
