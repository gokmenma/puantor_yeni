<?php
require_once "App/Helper/helper.php";
require_once "App/Helper/security.php";
require_once "App/Helper/date.php";
require_once "Model/DefinesModel.php";

use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\Date;

// Yetki Kontrolü
$perm->checkAuthorize("income_expense_type_definition");

$defines = new DefinesModel();
$items = $defines->getIncExpTypesByFirm();

// İstatistikleri hesapla
$totalCount = count($items);
$incomeCount = 0;
$expenseCount = 0;
$firmCustomCount = 0;

foreach ($items as $it) {
    if ((int)$it->type_id === 1) {
        $incomeCount++;
    } elseif ((int)$it->type_id === 2) {
        $expenseCount++;
    }
    if ((int)$it->firm_id > 0) {
        $firmCustomCount++;
    }
}
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'incexp-summary-collapsed',
            localStorage.getItem('incexp_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.incexp-summary-collapsed #incExpSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="incExpPage">

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
                            Gelir - Gider Türleri
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Kasa ve finans hareketlerinde kullanılacak standart ve firmaya özel gelir-gider kategorileri
                        </div>
                    </div>
                </div>
            </div>

            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Sütunlar Butonu -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon incexp-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="incExpColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar JS ile dinamik oluşturulur -->
                        </div>
                    </div>

                    <!-- Birincil Aksiyon: Yeni Tür Tanımla -->
                    <button type="button" class="btn btn-sm btn-dark shadow-sm incexp-header-action" data-bs-toggle="modal" data-bs-target="#incExpModal" id="btnNewIncExp" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-plus me-1"></i> Yeni Tür Tanımla
                    </button>

                    <!-- İşlemler Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle incexp-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0);" id="btnExportIncExpExcel" class="dropdown-item">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4'lü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="incExpSummaryCards">
        <!-- Kart 1: Toplam Tanım -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border incexp-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM TANIM</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-folders" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($totalCount, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Tüm Kategoriler
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm tanımları göster">
                            <input type="radio" name="incexp_filter" value="" class="incexp-type-filter" checked>
                            <span><i class="ti ti-folders"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Gelir Türleri -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border incexp-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">GELİR TÜRLERİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-down-left" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($incomeCount, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Giriş Kategorileri
                        </span>
                        <label class="status-summary-filter mb-0" title="Yalnızca Gelir tanımlarını göster">
                            <input type="radio" name="incexp_filter" value="Gelir" class="incexp-type-filter">
                            <span><i class="ti ti-arrow-down-left"></i> Gelir</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Gider Türleri -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border incexp-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">GİDER TÜRLERİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-up-right" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($expenseCount, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Çıkış Kategorileri
                        </span>
                        <label class="status-summary-filter mb-0" title="Yalnızca Gider tanımlarını göster">
                            <input type="radio" name="incexp_filter" value="Gider" class="incexp-type-filter">
                            <span><i class="ti ti-arrow-up-right"></i> Gider</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Firma Özel Tanımlar -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border incexp-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">FİRMA ÖZEL</span>
                        <div class="avatar avatar-sm rounded-2 bg-purple-lt text-purple" style="width: 32px; height: 32px;">
                            <i class="ti ti-building" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($firmCustomCount, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Özel Tanımlanmış
                        </span>
                        <label class="status-summary-filter mb-0" title="Yalnızca Firma Özel tanımları göster">
                            <input type="radio" name="incexp_filter" value="Firma Özel" class="incexp-type-filter">
                            <span><i class="ti ti-building"></i> Firma Özel</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card incexp-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Gelir / Gider Türü Listesi</h4>
                                <button type="button" class="btn-card-header-add" data-bs-toggle="modal" data-bs-target="#incExpModal" id="btnNewIncExpHeader" title="Yeni Tür Tanımla">
                                    <i class="ti ti-plus"></i>
                                </button>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Sistem ve firma gelir-gider kategorileri, anlık arama ve yönetim</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon incexp-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="incexp-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="incexp-search-clear" class="incexp-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <!-- Özet Kartları Gizle/Göster Butonu -->
                        <button type="button" id="toggleIncExpSummary" class="btn btn-sm btn-outline-secondary btn-icon incexp-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container (Seamless inside card) -->
                <div class="table-responsive incexp-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="incexp-table" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 50px; min-width: 50px;" class="text-center">Sıra</th>
                                <th>Gelir / Gider Adı</th>
                                <th>Türü</th>
                                <th>Kapsam</th>
                                <th>Açıklama</th>
                                <th>Eklenme Tarihi</th>
                                <th style="width: 80px; min-width: 80px;" class="no-export text-end actions-column">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($items as $item):
                                $encrypted_id = Security::encrypt($item->id);
                                $isSystem = ((int)$item->firm_id === 0);
                                $turName = ((int)$item->type_id === 1) ? 'Gelir' : 'Gider';
                                $kapsamName = $isSystem ? 'Sistem Tanımı' : 'Firma Özel';
                                $created_date = !empty($item->created_at) ? Date::dmY($item->created_at) : '-';
                                $descText = $item->description ?? '';
                                ?>
                                <tr data-item-id="<?= $encrypted_id ?>"
                                    data-item-name="<?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8') ?>"
                                    data-item-type="<?= (int)$item->type_id ?>"
                                    data-item-desc="<?= htmlspecialchars($descText, ENT_QUOTES, 'UTF-8') ?>"
                                    data-is-system="<?= $isSystem ? '1' : '0' ?>">
                                    <td class="text-center text-muted"><?= $i++; ?></td>
                                    <td>
                                        <?php if (!$isSystem): ?>
                                            <a class="btn-edit-incexp fw-semibold text-primary text-decoration-none" href="javascript:void(0);"
                                                data-id="<?= $encrypted_id ?>"
                                                data-name="<?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8') ?>"
                                                data-type="<?= (int)$item->type_id ?>"
                                                data-desc="<?= htmlspecialchars($descText, ENT_QUOTES, 'UTF-8') ?>">
                                                <i class="ti ti-tag me-1 opacity-75"></i><?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="fw-semibold text-dark">
                                                <i class="ti ti-lock me-1 text-muted opacity-75"></i><?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ((int)$item->type_id === 1): ?>
                                            <span class="badge bg-success-lt px-2 py-1">
                                                <i class="ti ti-arrow-down-left me-1"></i>Gelir
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-lt px-2 py-1">
                                                <i class="ti ti-arrow-up-right me-1"></i>Gider
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($isSystem): ?>
                                            <span class="badge bg-secondary-lt px-2 py-1" title="Sistem genelinde tanımlı standart kategori">
                                                <i class="ti ti-lock me-1"></i>Sistem Tanımı
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-purple-lt px-2 py-1" title="Firmanıza özel tanımlanmış kategori">
                                                <i class="ti ti-building me-1"></i>Firma Özel
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-muted">
                                        <?= !empty($descText) ? htmlspecialchars($descText, ENT_QUOTES, 'UTF-8') : '<span class="text-muted opacity-50">-</span>'; ?>
                                    </td>
                                    <td class="text-start"><?= $created_date; ?></td>
                                    <td class="text-end">
                                        <?php if (!$isSystem): ?>
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle align-text-top py-1 px-2"
                                                    data-bs-toggle="dropdown" data-bs-boundary="viewport" style="font-size: 12px;">
                                                    İşlem
                                                </button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item btn-edit-incexp" href="javascript:void(0);"
                                                        data-id="<?= $encrypted_id ?>"
                                                        data-name="<?= htmlspecialchars($item->name, ENT_QUOTES, 'UTF-8') ?>"
                                                        data-type="<?= (int)$item->type_id ?>"
                                                        data-desc="<?= htmlspecialchars($descText, ENT_QUOTES, 'UTF-8') ?>">
                                                        <i class="ti ti-edit icon me-2 text-primary"></i> Düzenle
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item delete-incexp text-danger" href="javascript:void(0);"
                                                        data-id="<?= $encrypted_id ?>">
                                                        <i class="ti ti-trash icon me-2"></i> Sil
                                                    </a>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt text-muted px-2 py-1" title="Sistem kayıtları değiştirilemez">
                                                <i class="ti ti-lock me-1"></i>Sabit
                                            </span>
                                        <?php endif; ?>
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

<!-- Modal: Ekle/Düzenle Formu -->
<div class="modal modal-blur fade" id="incExpModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-2.5 px-3 bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="incExpModalTitle">
                    <i class="ti ti-receipt-2 text-primary me-2"></i>Yeni Gelir/Gider Türü
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-3">
                <form id="incExpModalForm">
                    <input type="hidden" name="id" id="incExp_id">
                    <input type="hidden" name="action" value="saveIncExpType">

                    <div class="mb-3">
                        <label class="form-label required fw-medium" style="font-size: 13px;">Gelir / Gider Adı</label>
                        <input type="text" name="incexp_name" id="incexp_name" class="form-control" placeholder="Örn: Yemek Gideri, Kira, Proje Hakedişi vb." required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-medium" style="font-size: 13px;">Türü</label>
                        <select name="incexp_type" id="incexp_type" class="form-select select2-modal" style="width: 100%;">
                            <option value="1">Gelir (Kasa Girişi)</option>
                            <option value="2">Gider (Kasa Çıkışı)</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-medium" style="font-size: 13px;">Açıklama</label>
                        <textarea name="description" id="description" class="form-control" rows="3" placeholder="İsteğe bağlı kategori açıklaması girebilirsiniz..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 px-3 bg-light border-top">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary ms-auto shadow-sm" id="saveIncExpType" style="background-color: #206bc4;">
                    <i class="ti ti-device-floppy icon me-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.incexp-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.incexp-header-icon-action,
.incexp-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.incexp-header-icon-action i,
.incexp-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

.btn-card-header-add {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    background: #e2e8f0;
    color: #475569;
    border-radius: 4px;
    border: none;
    font-size: 12px;
    transition: all .15s ease;
    text-decoration: none;
}
.btn-card-header-add:hover {
    background: #cbd5e1;
    color: #0f172a;
}

#incExpPage .incexp-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
}

#incExpSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

.incexp-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
}

.incexp-table-card > .incexp-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.incexp-table-card > .card-header {
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

.incexp-search-wrap { position: relative; }
#incexp-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.incexp-search-wrap,
.incexp-search-wrap.input-icon {
    height: 32px !important;
}
.incexp-search-clear {
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
.incexp-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#incexp-table_wrapper,
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
table#incexp-table.data-table,
table#incexp-table.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#incexp-table.data-table tbody,
table#incexp-table.dataTable tbody,
table#incexp-table.data-table tbody tr:last-child,
table#incexp-table.dataTable tbody tr:last-child,
#incexp-table_wrapper .dt-layout-table,
#incexp-table_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#incexp-table.data-table thead th {
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
table#incexp-table.data-table thead th:last-child {
    border-right: none !important;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#incexp-table.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#incexp-table.data-table tbody td:last-child {
    border-right: none !important;
}
table#incexp-table.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#incexp-table.dataTable > tbody > tr:last-child > *,
table#incexp-table.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#incexp-table.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#incexp-table_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child {
    margin: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
}

#incexp-table th:last-child,
#incexp-table td:last-child {
    width: 80px !important;
    min-width: 80px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 12px !important;
}

/* Dark Mode */
[data-bs-theme="dark"] table#incexp-table.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#incexp-table.data-table,
[data-bs-theme="dark"] table#incexp-table.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#incexp-table.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#incexp-table.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#incexp-table.data-table tbody tr:hover td {
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
[data-bs-theme="dark"] .incexp-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .incexp-summary-card,
[data-bs-theme="dark"] .incexp-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

/* Custom Context Menu */
.custom-context-menu {
    position: fixed;
    z-index: 1050;
    display: none;
    min-width: 180px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    padding: 6px;
    font-size: 13px;
    user-select: none;
}
[data-bs-theme="dark"] .custom-context-menu {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
}
.custom-context-menu .cm-header {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
[data-bs-theme="dark"] .custom-context-menu .cm-header {
    border-bottom-color: #334155;
    color: #94a3b8;
}
.custom-context-menu a {
    display: flex;
    align-items: center;
    padding: 6px 10px;
    color: #334155;
    text-decoration: none;
    border-radius: 6px;
    transition: background-color 0.15s;
    font-weight: 500;
}
[data-bs-theme="dark"] .custom-context-menu a {
    color: #e2e8f0;
}
.custom-context-menu a i {
    font-size: 15px;
    margin-right: 8px;
    width: 16px;
    text-align: center;
}
.custom-context-menu a:hover {
    background-color: #f1f5f9;
    color: #206bc4;
}
[data-bs-theme="dark"] .custom-context-menu a:hover {
    background-color: #334155;
    color: #38bdf8;
}
.custom-context-menu .cm-divider {
    height: 1px;
    background-color: #e2e8f0;
    margin: 4px 0;
}
[data-bs-theme="dark"] .custom-context-menu .cm-divider {
    background-color: #334155;
}
.custom-context-menu a.cm-danger {
    color: #ef4444;
}
.custom-context-menu a.cm-danger:hover {
    background-color: #fef2f2;
    color: #dc2626;
}
[data-bs-theme="dark"] .custom-context-menu a.cm-danger:hover {
    background-color: rgba(239, 68, 68, 0.15);
    color: #f87171;
}
tbody tr.context-menu-active td {
    background-color: rgba(32, 107, 196, 0.08) !important;
}
</style>

<script>
$(document).ready(function() {
    var $summaryToggle = $('#toggleIncExpSummary');
    var $incExpTable = $('#incexp-table');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('incexp-summary-collapsed');
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
        var isCollapsed = document.documentElement.classList.toggle('incexp-summary-collapsed');
        try {
            localStorage.setItem('incexp_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    // Sütunların yapılandırması
    var columnConfig = {
        1: { label: 'Gelir / Gider Adı', default: true },
        2: { label: 'Türü', default: true },
        3: { label: 'Kapsam', default: true },
        4: { label: 'Açıklama', default: true },
        5: { label: 'Eklenme Tarihi', default: true }
    };

    var savedVisibility = {};
    try {
        var rawStored = localStorage.getItem('incexp_column_visibility');
        if (rawStored) {
            var parsed = JSON.parse(rawStored);
            if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
                savedVisibility = parsed;
            }
        }
    } catch(e) {
        savedVisibility = {};
    }

    var tableOptions = {
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[0, 'asc']],
        stateSave: false,
        columnDefs: [
            { targets: '_all', defaultContent: '' },
            { targets: [0, 6], orderable: false, searchable: false },
            { targets: [0, 5], className: 'text-center' },
            { targets: 6, width: '80px', className: 'text-end no-export actions-column' }
        ],
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'd-none',
                title: 'Gelir Gider Turleri',
                filename: 'gelir_gider_turleri_' + new Date().toLocaleDateString('tr-TR').replace(/\./g, '-'),
                exportOptions: {
                    columns: ':not(.no-export)'
                }
            }
        ],
        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: ['info', 'pageLength'],
            bottomEnd: 'paging'
        },
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
                window.initDataTableColumnFilters($('#incexp-table'), api);
            }

            api.columns.adjust().draw(false);
        }
    };

    var table = $.fn.DataTable.isDataTable($incExpTable[0])
        ? $incExpTable.DataTable()
        : $incExpTable.DataTable(tableOptions);

    var $colvisMenu = $('#incExpColvisMenu');
    $colvisMenu.empty();

    if (table) {
        $.each(columnConfig, function(colIdx, conf) {
            colIdx = parseInt(colIdx, 10);
            var isVisible = (savedVisibility && typeof savedVisibility[colIdx] === 'boolean') ? savedVisibility[colIdx] : conf.default;

            var $item = $(
                '<label class="dropdown-item d-flex align-items-center py-1.5 px-3 rounded-2 cursor-pointer" style="font-size: 0.85rem;">' +
                '<div class="form-check mb-0 w-100">' +
                '<input class="form-check-input me-2 mt-0 col-toggle-cb" type="checkbox" data-column="' + colIdx + '"' + (isVisible ? ' checked' : '') + '>' +
                '<span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">' + conf.label + '</span>' +
                '</div>' +
                '</label>'
            );
            $colvisMenu.append($item);
        });
    }

    // Checkbox değiştiğinde sütunu göster/gizle ve kaydet
    $colvisMenu.on('change', '.col-toggle-cb', function(e) {
        e.stopPropagation();
        var colIdx = parseInt($(this).data('column'), 10);
        var isChecked = $(this).is(':checked');

        table.column(colIdx).visible(isChecked, true);

        savedVisibility[colIdx] = isChecked;
        try {
            localStorage.setItem('incexp_column_visibility', JSON.stringify(savedVisibility));
        } catch(err) {}
    });

    $colvisMenu.on('click', function(e) {
        e.stopPropagation();
    });

    // Özet Kartı Radyo Filtreleri
    $('.incexp-type-filter').on('change', function() {
        var filterVal = $(this).val();
        if (!table) return;

        if (!filterVal) {
            table.column(2).search('').column(3).search('').draw();
        } else if (filterVal === 'Gelir' || filterVal === 'Gider') {
            table.column(2).search(filterVal).column(3).search('').draw();
        } else if (filterVal === 'Firma Özel') {
            table.column(2).search('').column(3).search('Firma Özel').draw();
        }
    });

    // Hızlı Genel Arama Inputu
    var searchTimer = null;
    $('#incexp-fast-search').on('input', function() {
        var val = this.value;
        $('#incexp-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (table) table.search(val).draw();
        }, 300);
    });

    $('#incexp-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#incexp-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        if (table) table.search('').draw();
    });

    // Excel Export Butonu
    $('#btnExportIncExpExcel').off('click').on('click', function(e) {
        e.preventDefault();
        if (table && table.button) {
            table.button('.buttons-excel').trigger();
        }
    });

    // Tabloda Sağ Tık (Custom Context Menu)
    $(document).on('contextmenu', '#incexp-table tbody tr', function(e) {
        var $tr = $(this);
        var itemId = $tr.attr('data-item-id');
        var itemName = $tr.attr('data-item-name') || 'Gelir/Gider Türü';
        var itemType = $tr.attr('data-item-type');
        var itemDesc = $tr.attr('data-item-desc') || '';
        var isSystem = $tr.attr('data-is-system') === '1';

        if (!itemId) return;

        e.preventDefault();

        $('#incexp-table tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var menuHtml = '';
        if (!isSystem) {
            menuHtml = `
                <div class="cm-header"><i class="ti ti-receipt-2 me-1"></i> ${$('<div>').text(itemName).html()}</div>
                <a href="javascript:void(0);" class="btn-edit-incexp" data-id="${itemId}" data-name="${$('<div>').text(itemName).html()}" data-type="${itemType}" data-desc="${$('<div>').text(itemDesc).html()}"><i class="ti ti-edit text-primary"></i> Düzenle</a>
                <div class="cm-divider"></div>
                <a href="javascript:void(0);" class="cm-danger delete-incexp" data-id="${itemId}"><i class="ti ti-trash"></i> Tanımı Sil</a>
            `;
        } else {
            menuHtml = `
                <div class="cm-header"><i class="ti ti-lock me-1"></i> ${$('<div>').text(itemName).html()} (Sistem)</div>
                <div class="px-2 py-1 text-muted small"><i class="ti ti-info-circle me-1"></i> Bu kayıt sistem sabit tanımıdır ve değiştirilemez.</div>
            `;
        }

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
            $('#incexp-table tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#incexp-table tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#incexp-table tbody tr').removeClass('context-menu-active');
    });
});
</script>