<?php
require_once ROOT . '/Model/IzinTalep.php';
require_once ROOT . '/Model/IzinTur.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Company.php';
require_once ROOT . '/App/Helper/security.php';
require_once ROOT . '/App/Helper/helper.php';

use App\Helper\Security;
use App\Helper\Helper;

$perm->checkAuthorize('izin_talepler');
$Auths->checkFirmReturn();

$firma_id    = (int) ($_SESSION['firm_id'] ?? 0);
$izinTalepModel = new IzinTalep();
$stats       = $izinTalepModel->getIzinStats($firma_id);
$turler      = (new IzinTur())->getPersonelTurler();
$personeller = (new Persons())->getPersonsByFirm($firma_id);

$firmaModel  = new Company();
$firmaData   = $firmaModel->findMyFirm($firma_id);
$default_firma_unvani = $firmaData ? $firmaData->firm_name : '';
$default_yetkili_adi = $firmaData ? $firmaData->yetkili_adi : '';
if ($default_yetkili_adi === '0') {
    $default_yetkili_adi = '';
}
if ($default_firma_unvani === '0') {
    $default_firma_unvani = '';
}

$initial_durum = $_GET['durum'] ?? '';
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'izin-summary-collapsed',
            localStorage.getItem('izin_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.izin-summary-collapsed #izinSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="izinPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-calendar-event" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            İzin Talepleri
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Personel izin talepleri, onay/red işlemleri ve izin formları
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon izin-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="izinColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar dinamik yüklenecek -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark shadow-sm izin-header-action" id="btn-header-yeni-talep" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-plus me-1"></i> Yeni Talep Ekle
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle izin-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0)" class="dropdown-item" id="export_excel">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <a href="javascript:void(0)" class="dropdown-item" id="export_pdf">
                                <i class="ti ti-file-type-pdf icon me-2 text-danger"></i> PDF Raporu Al
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="izinSummaryCards">
        <!-- Kart 1: Toplam Talep -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border izin-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM TALEP</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-calendar-event" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="stat-total" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats['total'], 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Onaylı: <strong class="text-dark" id="stat-toplam-gun"><?= $stats['toplam_onayli_gun'] ?> Gün</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm talepleri göster">
                            <input type="radio" name="filter_durum_group" value="" class="filter-durum-radio" <?= empty($initial_durum) ? 'checked' : '' ?>>
                            <span><i class="ti ti-list"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Bekleyen Talepler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border izin-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BEKLEYEN TALEPLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-clock" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="stat-beklemede" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats['beklemede'], 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Onay Bekliyor
                        </span>
                        <label class="status-summary-filter mb-0" title="Bekleyen talepleri göster">
                            <input type="radio" name="filter_durum_group" value="beklemede" class="filter-durum-radio" <?= $initial_durum === 'beklemede' ? 'checked' : '' ?>>
                            <span><i class="ti ti-clock"></i> Beklemede</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Onaylanan Talepler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border izin-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ONAYLANAN TALEPLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-circle-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="stat-onaylandi" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats['onaylandi'], 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Bu Ay: <strong class="text-success" id="stat-bu-ay-gun"><?= $stats['bu_ay_gun'] ?> Gün</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Onaylanan talepleri göster">
                            <input type="radio" name="filter_durum_group" value="onaylandi" class="filter-durum-radio" <?= $initial_durum === 'onaylandi' ? 'checked' : '' ?>>
                            <span><i class="ti ti-circle-check"></i> Onaylı</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Reddedilen Talepler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border izin-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">REDDEDİLEN TALEPLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-circle-x" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="stat-reddedildi" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats['reddedildi'], 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            İşlem Yapılan
                        </span>
                        <label class="status-summary-filter mb-0" title="Reddedilen talepleri göster">
                            <input type="radio" name="filter_durum_group" value="reddedildi" class="filter-durum-radio" <?= $initial_durum === 'reddedildi' ? 'checked' : '' ?>>
                            <span><i class="ti ti-circle-x"></i> Reddedildi</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card izin-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">İzin Talepleri Listesi</h4>
                                <a href="javascript:void(0)" class="btn-card-header-add" id="btn-header-add" data-tooltip="Yeni Talep Ekle">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık arama, sütun filtreleme ve onay süreçleri</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon izin-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="izin-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="izin-search-clear" class="izin-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleIzinSummary" class="btn btn-sm btn-outline-secondary btn-icon izin-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container (Seamless inside card) -->
                <div class="table-responsive izin-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="izin-table" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th>Personel</th>
                                <th>İzin Türü</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th class="text-center">Toplam Gün</th>
                                <th class="text-center">Düşülecek Gün</th>
                                <th class="text-center">Durum</th>
                                <th>Talep Tarihi</th>
                                <th>Onaylayan</th>
                                <th>Açıklama</th>
                                <th class="text-end no-export actions-column" data-orderable="false" style="width: 105px; min-width: 105px;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal: Yeni Talep -->
<div class="modal modal-blur fade" id="modalYeniTalep" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-calendar-plus text-primary fs-2"></i>
                    Yeni İzin Talebi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required">Personel</label>
                    <select id="yeni-personel" class="form-select select2-modal">
                        <option value="">Seçiniz</option>
                        <?php foreach ($personeller as $p): ?>
                            <option value="<?= Security::encrypt($p->id) ?>" data-person-id="<?= $p->id ?>"><?= htmlspecialchars($p->full_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label required">İzin Türü</label>
                    <select id="yeni-tur" class="form-select select2-modal">
                        <option value="">Seçiniz</option>
                        <?php foreach ($turler as $t): ?>
                            <option value="<?= $t->id ?>" data-kod="<?= htmlspecialchars($t->kod) ?>"><?= htmlspecialchars($t->ad) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label required">Başlangıç Tarihi</label>
                        <input type="text" id="yeni-baslangic" class="form-control flatpickr-modal" placeholder="gg.aa.yyyy" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label class="form-label required">Bitiş Tarihi</label>
                        <input type="text" id="yeni-bitis" class="form-control flatpickr-modal" placeholder="gg.aa.yyyy" autocomplete="off">
                    </div>
                </div>
                <div class="mt-2 mb-3 p-2 bg-light rounded" id="izin-hesap-container">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Kullanılacak İzin Günü:</span>
                        <strong id="takvim-gun-sayisi-preview" class="text-secondary">—</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Düşülecek İzin Günü (İş Günü):</span>
                        <strong id="gun-sayisi-preview" class="text-success">—</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" id="yeni-aciklama-label">Açıklama <span class="text-muted small">(opsiyonel)</span></label>
                    <textarea id="yeni-aciklama" class="form-control" rows="3" placeholder="Talep açıklaması..."></textarea>
                </div>
                <div class="mb-3" id="yeni-adres-container">
                    <label class="form-label">İznin Geçirileceği Adres</label>
                    <textarea id="yeni-adres" class="form-control" rows="2" placeholder="İzninizi geçireceğiniz adres..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> İptal
                </button>
                <button type="button" class="btn btn-primary" id="btn-talep-kaydet">
                    <i class="ti ti-check me-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Red Notu -->
<div class="modal modal-blur fade" id="modalRed" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-x text-danger fs-2"></i>
                    Red Notu
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="red-not" class="form-control" rows="4" placeholder="Neden reddediliyor?"></textarea>
                <input type="hidden" id="red-talep-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> İptal
                </button>
                <button type="button" class="btn btn-danger" id="btn-red-onayla">
                    <i class="ti ti-circle-x me-1"></i> Reddet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Kısmi Onayla -->
<div class="modal modal-blur fade" id="modalKismiOnay" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-calendar-check text-warning fs-2"></i>
                    Kısmi Onay
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted small">Personel</span>
                    <strong id="kismi-personel-adi" class="small text-end"></strong>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted small">Talep Tarihleri</span>
                    <span id="kismi-talep-tarihleri" class="small text-end"></span>
                </div>
                <div class="mb-3 d-flex justify-content-between">
                    <span class="text-muted small">Talep Edilen</span>
                    <span id="kismi-orijinal-gun" class="small fw-bold text-end"></span>
                </div>
                <hr class="my-2">
                <div class="mb-3">
                    <label class="form-label required">Onaylanacak Bitiş Tarihi</label>
                    <input type="text" id="kismi-bitis" class="form-control" placeholder="gg.aa.yyyy" autocomplete="off">
                    <div class="mt-2 p-2 bg-light rounded">
                        <span class="text-muted small">Onaylanacak gün: </span>
                        <strong id="kismi-gun-preview" class="text-warning">—</strong>
                    </div>
                </div>
                <input type="hidden" id="kismi-talep-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> İptal
                </button>
                <button type="button" class="btn btn-warning" id="btn-kismi-onayla">
                    <i class="ti ti-check me-1"></i> Kısmi Onayla
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Formu Yazdır / İmza Modali -->
<div class="modal modal-blur fade" id="modalYazdir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-printer text-primary fs-2"></i>
                    İzin Formu İmza Bilgileri
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="print-talep-id">
                <p class="text-muted small mb-3">İzin talep formunun altında yer alacak onaylayan kişileri ve ünvanlarını seçiniz. Yeni bir ünvan veya isim eklemek için yazıp <strong>Enter</strong> tuşuna basınız.</p>
                
                <div class="card mb-2">
                    <div class="card-header py-2 bg-light">
                        <h3 class="card-title text-muted fw-bold small mb-0">1. ONAYLAYAN (Örn: İnsan Kaynakları)</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ünvan/Görev</label>
                                <select id="print-unvan-1" class="form-select select2-modal-signature" data-type="unvan"></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ad Soyad</label>
                                <select id="print-isim-1" class="form-select select2-modal-signature" data-type="isim"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-2">
                    <div class="card-header py-2 bg-light">
                        <h3 class="card-title text-muted fw-bold small mb-0">2. ONAYLAYAN (Örn: Bölüm Yöneticisi)</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ünvan/Görev</label>
                                <select id="print-unvan-2" class="form-select select2-modal-signature" data-type="unvan"></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ad Soyad</label>
                                <select id="print-isim-2" class="form-select select2-modal-signature" data-type="isim"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-2">
                    <div class="card-header py-2 bg-light">
                        <h3 class="card-title text-muted fw-bold small mb-0">3. ONAYLAYAN (Örn: Genel Müdür)</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ünvan/Görev</label>
                                <select id="print-unvan-3" class="form-select select2-modal-signature" data-type="unvan"></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ad Soyad</label>
                                <select id="print-isim-3" class="form-select select2-modal-signature" data-type="isim"></select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> İptal
                </button>
                <button type="button" class="btn btn-primary" id="btn-form-yazdir">
                    <i class="ti ti-printer me-1"></i> Yazdır
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Ücretsiz İzin Dilekçesi Yazdır / İmza Modali -->
<div class="modal modal-blur fade" id="modalYazdirUcretsiz" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-printer text-primary fs-2"></i>
                    Ücretsiz İzin Dilekçesi İmza Bilgileri
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="print-ucretsiz-talep-id">
                <p class="text-muted small mb-3">Dilekçenin altında yer alacak işveren onay bilgilerini doldurunuz.</p>
                
                <div class="mb-3">
                    <label class="form-label text-muted small mb-1">Onaylayan Adı Soyadı</label>
                    <input type="text" id="print-ucretsiz-ad-soyad" class="form-control" value="<?= htmlspecialchars($default_yetkili_adi) ?>" placeholder="Örn: Ahmet Yılmaz">
                </div>
                
                <div class="mb-3">
                    <label class="form-label text-muted small mb-1">Firma Ünvanı</label>
                    <input type="text" id="print-ucretsiz-firma-unvan" class="form-control" value="<?= htmlspecialchars($default_firma_unvani) ?>" placeholder="Örn: Gökmen İnşaat Ltd. Şti.">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> İptal
                </button>
                <button type="button" class="btn btn-primary" id="btn-ucretsiz-form-yazdir">
                    <i class="ti ti-printer me-1"></i> Yazdır
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.izin-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.izin-header-icon-action,
.izin-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.izin-header-icon-action i,
.izin-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#izinPage .izin-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    border-radius: 12px !important;
    overflow: hidden;
}

#izinSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#izinPage .izin-table-card {
    border-radius: 12px !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.izin-table-card > .izin-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.izin-table-card > .card-header {
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

.izin-search-wrap { position: relative; }
#izin-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.izin-search-wrap,
.izin-search-wrap.input-icon {
    height: 32px !important;
}
.izin-search-clear {
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
.izin-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#izin-table_wrapper,
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
table#izin-table.data-table,
table#izin-table.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#izin-table.data-table tbody,
table#izin-table.dataTable tbody,
table#izin-table.data-table tbody tr:last-child,
table#izin-table.dataTable tbody tr:last-child,
#izin-table_wrapper .dt-layout-table,
#izin-table_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#izin-table.data-table thead th {
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
table#izin-table.data-table thead th:last-child {
    border-right: none !important;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#izin-table.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#izin-table.data-table td.actions-column .btn.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
table#izin-table.data-table td.actions-column .btn.btn-icon i {
    font-size: 14px !important;
    margin: 0 !important;
}
table#izin-table.data-table tbody td:last-child {
    border-right: none !important;
}
table#izin-table.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#izin-table.dataTable > tbody > tr:last-child > *,
table#izin-table.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#izin-table.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#izin-table_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#izin-table_wrapper .dt-layout-row:has(.dt-paging),
div#izin-table_wrapper .dt-layout-row:has(.dt-info) {
    margin: 0 !important;
    margin-top: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
    position: static !important;
    flex-shrink: 0 !important;
}

#izin-table th:last-child,
#izin-table td:last-child {
    width: 105px !important;
    min-width: 105px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 12px !important;
}

/* Dark Mode */
[data-bs-theme="dark"] table#izin-table.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#izin-table.data-table,
[data-bs-theme="dark"] table#izin-table.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#izin-table.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#izin-table.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#izin-table.data-table tbody tr:hover td {
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
[data-bs-theme="dark"] .izin-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .izin-summary-card,
[data-bs-theme="dark"] .izin-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}
</style>

<script>
const CAN_DELETE_APPROVED = <?= $Auths->Authorize('onayli_izinleri_sil') ? 'true' : 'false' ?>;

$(document).ready(function() {

    const IZIN_API = 'api/izin/talep.php';

    // ---------- Summary Toggle Logic ----------
    var $summaryToggle = $('#toggleIzinSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('izin-summary-collapsed');
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
        var isCollapsed = document.documentElement.classList.toggle('izin-summary-collapsed');
        try {
            localStorage.setItem('izin_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    // ---------- Select2 & Flatpickr ----------
    $('.select2-modal').select2({ width: '100%', allowClear: true, placeholder: 'Seçiniz', dropdownParent: $('#modalYeniTalep') });

    const fpOpts = { dateFormat: 'd.m.Y', locale: 'tr', allowInput: true };

    let fpBaslangic, fpBitis;
    fpBaslangic = flatpickr('#yeni-baslangic', {
        ...fpOpts,
        onChange: function(dates) {
            if (fpBitis) fpBitis.set('minDate', dates[0] || null);
            calcGun();
        }
    });
    fpBitis = flatpickr('#yeni-bitis', {
        ...fpOpts,
        onChange: calcGun
    });

    // ---------- Helper Functions ----------
    function parseTrDate(str) {
        if (!str) return '';
        const p = str.split('.');
        return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : str;
    }

    function fmtDate(d) {
        if (!d) return '—';
        const p = (d + '').split(/[-T ]/);
        return p.length >= 3 ? `${p[2]}.${p[1]}.${p[0]}` : d;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return $('<div>').text(str).html();
    }

    function durumBadge(d) {
        const map = {
            beklemede:  ['bg-warning-lt text-warning', '<i class="ti ti-clock icon me-1"></i>Beklemede'],
            onaylandi:  ['bg-success-lt text-success', '<i class="ti ti-check icon me-1"></i>Onaylı'],
            reddedildi: ['bg-danger-lt text-danger',   '<i class="ti ti-x icon me-1"></i>Reddedildi'],
            iptal:      ['bg-secondary-lt text-secondary', 'İptal']
        };
        const [cls, lbl] = map[d] || ['bg-secondary-lt text-secondary', d];
        return `<span class="badge ${cls} fw-semibold">${lbl}</span>`;
    }

    function buildActions(row) {
        let html = '';
        const idVal = row.enc_id || row.id;
        const printBtn = `<button type="button" class="btn btn-icon btn-sm rounded-circle btn-outline-primary" onclick="openPrintModal('${idVal}', '${row.tur_kod}')" title="Formu Yazdır">
            <i class="ti ti-printer"></i>
        </button>`;

        if (row.durum === 'beklemede') {
            html += `<button type="button" class="btn btn-icon btn-sm rounded-circle btn-outline-success" onclick="onayla('${idVal}')" title="Onayla">
                <i class="ti ti-check"></i>
            </button>`;
            html += `<button type="button" class="btn btn-icon btn-sm rounded-circle btn-outline-warning" onclick="kismiOnaylaAc('${idVal}', '${row.baslangic_tarihi}', '${row.bitis_tarihi}', '${escapeHtml(row.personel_adi)}', ${row.gun_sayisi})" title="Kısmi Onayla">
                <i class="ti ti-calendar-check"></i>
            </button>`;
            html += `<button type="button" class="btn btn-icon btn-sm rounded-circle btn-outline-danger" onclick="openRedModal('${idVal}')" title="Reddet">
                <i class="ti ti-x"></i>
            </button>`;
            html += printBtn;
        } else if (row.durum === 'onaylandi') {
            html += `<button type="button" class="btn btn-xs btn-outline-warning" onclick="openRedModal('${idVal}')" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 0.2rem 0.5rem; font-weight: 500; font-size: 11.5px; border-radius: 4px;">
                <i class="ti ti-rotate-left me-1"></i> Geri Al
            </button>`;
            if (CAN_DELETE_APPROVED) {
                html += ` <button type="button" class="btn btn-xs btn-outline-danger ms-1" onclick="talepSil('${idVal}')" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 0.2rem 0.5rem; font-weight: 500; font-size: 11.5px; border-radius: 4px;">
                    <i class="ti ti-trash me-1"></i> Sil
                </button>`;
            }
            html += ` ${printBtn}`;
        } else if (row.durum === 'reddedildi') {
            if (CAN_DELETE_APPROVED) {
                html += `<button type="button" class="btn btn-xs btn-outline-danger" onclick="talepSil('${idVal}')" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 0.2rem 0.5rem; font-weight: 500; font-size: 11.5px; border-radius: 4px;">
                    <i class="ti ti-trash me-1"></i> Sil
                </button>`;
            } else {
                html = '<span class="text-muted">—</span>';
            }
        } else {
            html = '<span class="text-muted">—</span>';
        }
        return `<div class="d-flex align-items-center justify-content-end gap-1 text-nowrap">${html}</div>`;
    }

    // Sütunların yapılandırması
    var izinColumnConfig = {
        0: 'Personel',
        1: 'İzin Türü',
        2: 'Başlangıç',
        3: 'Bitiş',
        4: 'Toplam Gün',
        5: 'Düşülecek Gün',
        6: 'Durum',
        7: 'Talep Tarihi',
        8: 'Onaylayan',
        9: 'Açıklama'
    };

    // ---------- DataTable ----------
    const dt = window.createDataTable('#izin-table', {
        data: [],
        columns: [
            { data: 'personel_adi' },
            { data: 'tur_adi' },
            { data: 'baslangic_tarihi', render: fmtDate },
            { data: 'bitis_tarihi',     render: fmtDate },
            { data: 'toplam_gun', className: 'text-center', render: d => `<strong>${d ?? '—'}</strong>` },
            { data: 'gun_sayisi', className: 'text-center', render: d => `<strong>${d ?? '—'}</strong>` },
            { data: 'durum', className: 'text-center', render: durumBadge },
            { data: 'olusturma_tarihi', render: fmtDate },
            { data: 'onaylayan_adi', render: d => d || '<span class="text-muted">—</span>' },
            { data: 'aciklama', render: d => d ? `<span class="text-truncate d-inline-block" style="max-width: 160px;" title="${escapeHtml(d)}">${escapeHtml(d)}</span>` : '<span class="text-muted">—</span>' },
            { data: null, orderable: false, searchable: false, className: 'text-end no-export actions-column', render: (d, t, row) => buildActions(row) }
        ],
        order: [[7, 'desc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        skipSearch: ['İşlem'],
        initComplete: function() {
            renderIzinColvisMenu();
        }
    });

    // Dinamik Sütun Menüsü Oluşturucu
    function renderIzinColvisMenu() {
        var menuHtml = '';
        var settings = dt ? dt.settings()[0] : null;

        $.each(izinColumnConfig, function (origIdxStr, label) {
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
            } else if (dt) {
                try {
                    isVisible = dt.column(origIdx).visible();
                } catch(e) {
                    isVisible = true;
                }
            }

            menuHtml += `
                <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size: 0.85rem;">
                    <div class="form-check mb-0 w-100">
                        <input class="form-check-input izin-col-trigger" type="checkbox" id="colCheck_${origIdx}" data-column="${origIdx}" data-orig-idx="${origIdx}" ${isVisible ? "checked" : ""}>
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

        $('#izinColvisMenu').html(menuHtml);
    }

    renderIzinColvisMenu();

    // Görünümü Sıfırla Butonu
    $(document).on('click', '#resetTableColumnsBtn', function() {
        if (typeof window.resetPuantorDTState === 'function') {
            window.resetPuantorDTState($('#izin-table'), dt, function() {
                renderIzinColvisMenu();
            });
        }
    });

    function getSelectedDurum() {
        return $('input[name="filter_durum_group"]:checked').val() || '';
    }

    function refreshStats() {
        $.get(IZIN_API, { action: 'stats' }, function(res) {
            if (res.status === 'success' && res.stats) {
                $('#stat-total').text(new Intl.NumberFormat('tr-TR').format(res.stats.total));
                $('#stat-beklemede').text(new Intl.NumberFormat('tr-TR').format(res.stats.beklemede));
                $('#stat-onaylandi').text(new Intl.NumberFormat('tr-TR').format(res.stats.onaylandi));
                $('#stat-reddedildi').text(new Intl.NumberFormat('tr-TR').format(res.stats.reddedildi));
                $('#stat-toplam-gun').text(res.stats.toplam_onayli_gun + ' Gün');
                $('#stat-bu-ay-gun').text(res.stats.bu_ay_gun + ' Gün');
            }
        });
    }

    function loadList() {
        const params = new URLSearchParams({
            action: 'list',
            durum:  getSelectedDurum()
        });

        $.get(IZIN_API + '?' + params.toString(), function(res) {
            if (res.status !== 'success') return;
            dt.clear().rows.add(res.list).draw();
        });
    }

    // Fast Instant Search
    var searchTimer = null;
    $('#izin-fast-search').on('input', function() {
        var val = this.value;
        $('#izin-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            dt.search(val).draw();
        }, 300);
    });

    $('#izin-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#izin-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        dt.search('').draw();
    });

    // Durum Filtresi Değişimi
    $('.filter-durum-radio').on('change', function() {
        loadList();
    });

    // Excel Export
    $('#export_excel').off('click').on('click', function(e) {
        e.preventDefault();
        if (dt && dt.button) {
            dt.button('.buttons-excel').trigger();
        }
    });

    // PDF Export
    $('#export_pdf').off('click').on('click', function(e) {
        e.preventDefault();
        if (dt && dt.button) {
            dt.button('.buttons-pdf').trigger();
        }
    });

    // ---------- Gün hesaplama ----------
    let calcTimer = null;
    function calcGun() {
        const b = $('#yeni-baslangic').val();
        const s = $('#yeni-bitis').val();
        if (!b || !s) { 
            $('#gun-sayisi-preview').text('—'); 
            $('#takvim-gun-sayisi-preview').text('—'); 
            return; 
        }

        const parseTr = (str) => {
            const parts = str.split('.');
            if (parts.length === 3) {
                return new Date(parts[2], parts[1] - 1, parts[0]);
            }
            return null;
        };

        const d1 = parseTr(b);
        const d2 = parseTr(s);
        if (d1 && d2 && d2 >= d1) {
            const diffTime = Math.abs(d2 - d1);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            $('#takvim-gun-sayisi-preview').text(diffDays + ' gün');
        } else {
            $('#takvim-gun-sayisi-preview').text('—');
        }

        clearTimeout(calcTimer);
        calcTimer = setTimeout(() => {
            $.get(IZIN_API, { action: 'calc_gun', baslangic: parseTrDate(b), bitis: parseTrDate(s) }, function(res) {
                $('#gun-sayisi-preview').text(res.status === 'success' ? res.gun_sayisi + ' gün' : '—');
            });
        }, 400);
    }

    // ---------- Yeni talep ----------
    $('#yeni-tur').on('change', function() {
        const kod = $(this).find('option:selected').data('kod');
        if (kod === 'ucretsiz') {
            $('#izin-hesap-container').hide();
            $('#yeni-adres-container').hide();
            $('#yeni-aciklama-label').text('Mazeret');
            if (!$('#yeni-aciklama').val().trim()) {
                $('#yeni-aciklama').val('Özel sebeplerden dolayı');
            }
        } else {
            $('#izin-hesap-container').show();
            $('#yeni-adres-container').show();
            $('#yeni-aciklama-label').html('Açıklama <span class="text-muted small">(opsiyonel)</span>');
            if ($('#yeni-aciklama').val() === 'Özel sebeplerden dolayı') {
                $('#yeni-aciklama').val('');
            }
        }
    });

    function openYeniTalepModal() {
        $('#yeni-personel').val(null).trigger('change');
        $('#yeni-aciklama').val('');
        $('#yeni-adres').val('');
        $('#yeni-tur').val(null).trigger('change');
        $('#gun-sayisi-preview').text('—');
        $('#takvim-gun-sayisi-preview').text('—');
        if (fpBaslangic) fpBaslangic.clear();
        if (fpBitis) fpBitis.clear();
        new bootstrap.Modal('#modalYeniTalep').show();
    }

    $('#btn-header-yeni-talep, #btn-header-add').on('click', openYeniTalepModal);

    $('#btn-talep-kaydet').on('click', function() {
        const data = {
            action:           'add',
            personel_id:      $('#yeni-personel').val(),
            tur_id:           $('#yeni-tur').val(),
            baslangic_tarihi: parseTrDate($('#yeni-baslangic').val()),
            bitis_tarihi:     parseTrDate($('#yeni-bitis').val()),
            aciklama:         $('#yeni-aciklama').val(),
            adres:            $('#yeni-adres').val()
        };
        $.post(IZIN_API, data, function(res) {
            Swal.fire({
                title: res.status === 'success' ? 'Başarılı' : 'Hata',
                text: res.message,
                icon: res.status === 'success' ? 'success' : 'error',
                confirmButtonText: 'Tamam'
            });
            if (res.status === 'success') {
                bootstrap.Modal.getInstance('#modalYeniTalep').hide();
                loadList();
                refreshStats();
            }
        });
    });

    // ---------- Onay / Red ----------
    window.onayla = function(encId) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu izin talebini onaylamak istediğinize emin misiniz?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2fb344',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Onayla',
            cancelButtonText: 'İptal Et'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(IZIN_API, { action: 'approve', id: encId }, function(res) {
                    Swal.fire({
                        title: res.status === 'success' ? 'Başarılı' : 'Hata',
                        text: res.message,
                        icon: res.status === 'success' ? 'success' : 'error',
                        confirmButtonText: 'Tamam'
                    });
                    if (res.status === 'success') {
                        loadList();
                        refreshStats();
                    }
                });
            }
        });
    };

    window.openRedModal = function(encId) {
        $('#red-talep-id').val(encId);
        $('#red-not').val('');
        new bootstrap.Modal('#modalRed').show();
    };

    $('#btn-red-onayla').on('click', function() {
        $.post(IZIN_API, { action: 'reject', id: $('#red-talep-id').val(), not: $('#red-not').val() }, function(res) {
            Swal.fire({
                title: res.status === 'success' ? 'Başarılı' : 'Hata',
                text: res.message,
                icon: res.status === 'success' ? 'success' : 'error',
                confirmButtonText: 'Tamam'
            });
            if (res.status === 'success') {
                bootstrap.Modal.getInstance('#modalRed').hide();
                loadList();
                refreshStats();
            }
        });
    });

    // ---------- Kısmi Onayla ----------
    let fpKismi = null;
    let kismiBaslangic = null;

    window.kismiOnaylaAc = function(encId, baslangic, bitis, personelAdi, orijinalGun) {
        kismiBaslangic = baslangic;
        $('#kismi-talep-id').val(encId);
        $('#kismi-personel-adi').text(personelAdi);
        $('#kismi-talep-tarihleri').text(fmtDate(baslangic) + ' – ' + fmtDate(bitis));
        $('#kismi-orijinal-gun').text(orijinalGun + ' iş günü');
        $('#kismi-gun-preview').text('—');

        if (fpKismi) fpKismi.destroy();
        fpKismi = flatpickr('#kismi-bitis', {
            ...fpOpts,
            minDate: fmtDate(baslangic),
            maxDate: fmtDate(bitis),
            defaultDate: null,
            onChange: calcKismiGun
        });
        fpKismi.clear();

        new bootstrap.Modal('#modalKismiOnay').show();
    };

    let kismiTimer = null;
    function calcKismiGun() {
        const val = $('#kismi-bitis').val();
        if (!val) { $('#kismi-gun-preview').text('—'); return; }
        clearTimeout(kismiTimer);
        kismiTimer = setTimeout(() => {
            $.get(IZIN_API, {
                action: 'calc_gun',
                baslangic: kismiBaslangic,
                bitis: parseTrDate(val)
            }, function(res) {
                $('#kismi-gun-preview').text(res.status === 'success' ? res.gun_sayisi + ' gün' : '—');
            });
        }, 400);
    }

    $('#btn-kismi-onayla').on('click', function() {
        const id    = $('#kismi-talep-id').val();
        const bitis = parseTrDate($('#kismi-bitis').val());
        if (!bitis) {
            Swal.fire({ title: 'Hata', text: 'Lütfen bitiş tarihi giriniz.', icon: 'error', confirmButtonText: 'Tamam' });
            return;
        }
        $.post(IZIN_API, { action: 'partial_approve', id: id, yeni_bitis: bitis }, function(res) {
            Swal.fire({
                title: res.status === 'success' ? 'Başarılı' : 'Hata',
                text: res.message,
                icon: res.status === 'success' ? 'success' : 'error',
                confirmButtonText: 'Tamam'
            });
            if (res.status === 'success') {
                bootstrap.Modal.getInstance('#modalKismiOnay').hide();
                loadList();
                refreshStats();
            }
        });
    });

    window.talepSil = function(encId) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu izin talebi kalıcı olarak silinecektir. Bu işlem geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal Et'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(IZIN_API, { action: 'delete', id: encId }, function(res) {
                    Swal.fire({
                        title: res.status === 'success' ? 'Başarılı' : 'Hata',
                        text: res.message,
                        icon: res.status === 'success' ? 'success' : 'error',
                        confirmButtonText: 'Tamam'
                    });
                    if (res.status === 'success') {
                        loadList();
                        refreshStats();
                    }
                });
            }
        });
    };

    // ---------- Signature Printing Modal Logic ----------
    let loadedOptions = { unvan: [], isim: [] };

    function templateResultFormat(state) {
        if (!state.id || state.id === "") {
            return state.text;
        }
        if (!state.element) {
            return state.text;
        }
        const selectEl = $(state.element).closest('select');
        const type = selectEl.data('type');
        if (!type) return state.text;

        const opt = loadedOptions[type].find(o => o.deger === state.text);
        if (!opt) {
            return state.text;
        }

        return $(
            '<div class="d-flex justify-content-between align-items-center w-100">' +
                '<span>' + state.text + '</span>' +
                '<button type="button" class="btn btn-sm btn-ghost-danger p-0 delete-sig-opt" data-id="' + opt.id + '" style="line-height: 1; height: 18px; width: 18px; display: inline-flex; align-items: center; justify-content: center; border:none; background:transparent;">' +
                    '<i class="ti ti-x text-danger fs-4"></i>' +
                '</button>' +
            '</div>'
        );
    }

    $('.select2-modal-signature').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Seçiniz veya yazınız...',
        dropdownParent: $('#modalYazdir'),
        tags: true,
        templateResult: templateResultFormat
    });

    function loadSignatureOptions(callback) {
        $.get('api/izin/form_onay_options.php', { action: 'list' }, function(res) {
            if (res.status === 'success') {
                loadedOptions.unvan = res.unvanlar;
                loadedOptions.isim = res.isimler;

                populateSelect2Signature('#print-unvan-1', res.unvanlar);
                populateSelect2Signature('#print-unvan-2', res.unvanlar);
                populateSelect2Signature('#print-unvan-3', res.unvanlar);

                populateSelect2Signature('#print-isim-1', res.isimler);
                populateSelect2Signature('#print-isim-2', res.isimler);
                populateSelect2Signature('#print-isim-3', res.isimler);

                if (res.last_selections) {
                    $('#print-unvan-1').val(res.last_selections.izin_form_onaylayan_unvan_1 || '').trigger('change');
                    $('#print-isim-1').val(res.last_selections.izin_form_onaylayan_isim_1 || '').trigger('change');
                    $('#print-unvan-2').val(res.last_selections.izin_form_onaylayan_unvan_2 || '').trigger('change');
                    $('#print-isim-2').val(res.last_selections.izin_form_onaylayan_isim_2 || '').trigger('change');
                    $('#print-unvan-3').val(res.last_selections.izin_form_onaylayan_unvan_3 || '').trigger('change');
                    $('#print-isim-3').val(res.last_selections.izin_form_onaylayan_isim_3 || '').trigger('change');
                }

                if (callback) callback();
            }
        });
    }

    function populateSelect2Signature(selector, data) {
        const select = $(selector);
        const currentVal = select.val();
        let html = '<option value="">Seçiniz</option>';
        data.forEach(item => {
            html += `<option value="${item.deger}">${item.deger}</option>`;
        });
        select.html(html);
        select.val(currentVal);
    }

    $('.select2-modal-signature').on('select2:select', function(e) {
        const data = e.params.data;
        const selectEl = $(this);
        const type = selectEl.data('type');
        const text = data.text.trim();

        const exists = loadedOptions[type].some(opt => opt.deger.toLowerCase() === text.toLowerCase());
        if (!exists && text !== '') {
            $.post('api/izin/form_onay_options.php', { action: 'add', type: type, val: text }, function(res) {
                if (res.status === 'success') {
                    loadedOptions[type].push({ id: res.id, deger: text });
                    $(`.select2-modal-signature[data-type="${type}"]`).each(function() {
                        const s = $(this);
                        const val = s.val();
                        let html = '<option value="">Seçiniz</option>';
                        loadedOptions[type].forEach(item => {
                            html += `<option value="${item.deger}">${item.deger}</option>`;
                        });
                        s.html(html).val(val).trigger('change');
                    });
                    selectEl.val(text).trigger('change');
                }
            });
        }
    });

    $(document).on('click', '.delete-sig-opt', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const btn = $(this);
        const id = btn.data('id');

        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu seçenek silinecektir. Sildiğinizde listede tekrar görünmeyecektir.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('.select2-modal-signature').select2('close');
                $.post('api/izin/form_onay_options.php', { action: 'delete', id: id }, function(res) {
                    if (res.status === 'success') {
                        $('.select2-modal-signature').each(function() {
                            if ($(this).val() === btn.parent().find('span').text()) {
                                $(this).val('').trigger('change');
                            }
                        });
                        loadSignatureOptions();
                    } else {
                        Swal.fire('Hata!', res.message, 'error');
                    }
                });
            }
        });
    });

    window.openPrintModal = function(encId, turKod) {
        if (turKod === 'ucretsiz') {
            $('#print-ucretsiz-talep-id').val(encId);
            new bootstrap.Modal('#modalYazdirUcretsiz').show();
        } else {
            $('#print-talep-id').val(encId);
            loadSignatureOptions(function() {
                new bootstrap.Modal('#modalYazdir').show();
            });
        }
    };

    $('#btn-form-yazdir').on('click', function() {
        const id = $('#print-talep-id').val();
        const selections = {
            izin_form_onaylayan_unvan_1: $('#print-unvan-1').val() || '',
            izin_form_onaylayan_isim_1: $('#print-isim-1').val() || '',
            izin_form_onaylayan_unvan_2: $('#print-unvan-2').val() || '',
            izin_form_onaylayan_isim_2: $('#print-isim-2').val() || '',
            izin_form_onaylayan_unvan_3: $('#print-unvan-3').val() || '',
            izin_form_onaylayan_isim_3: $('#print-isim-3').val() || ''
        };

        $.post('api/izin/form_onay_options.php', { action: 'save_last', selections: selections }, function(res) {
            if (res.status === 'success') {
                bootstrap.Modal.getInstance('#modalYazdir').hide();
                const params = new URLSearchParams({
                    id: id,
                    u1: selections.izin_form_onaylayan_unvan_1,
                    i1: selections.izin_form_onaylayan_isim_1,
                    u2: selections.izin_form_onaylayan_unvan_2,
                    i2: selections.izin_form_onaylayan_isim_2,
                    u3: selections.izin_form_onaylayan_unvan_3,
                    i3: selections.izin_form_onaylayan_isim_3
                });
                window.open('print_izin.php?' + params.toString(), '_blank');
            } else {
                Swal.fire('Hata!', 'Tercihler kaydedilemedi: ' + res.message, 'error');
            }
        });
    });

    $('#btn-ucretsiz-form-yazdir').on('click', function() {
        const id = $('#print-ucretsiz-talep-id').val();
        const adSoyad = $('#print-ucretsiz-ad-soyad').val() || '';
        const unvan = $('#print-ucretsiz-firma-unvan').val() || '';
        
        bootstrap.Modal.getInstance('#modalYazdirUcretsiz').hide();
        const params = new URLSearchParams({
            id: id,
            ad_soyad: adSoyad,
            unvan: unvan
        });
        window.open('print_izin.php?' + params.toString(), '_blank');
    });

    // Tabloda Sağ Tık (Context Menu)
    $(document).on('contextmenu', '#izin-table tbody tr', function(e) {
        var $tr = $(this);
        var rowData = dt.row($tr).data();
        if (!rowData) return;

        e.preventDefault();
        $('#izin-table tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var idVal = rowData.enc_id || rowData.id;
        var personelAdi = rowData.personel_adi || 'İzin Talebi';
        var durum = rowData.durum;

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-calendar me-1"></i> ${escapeHtml(personelAdi)}</div>
            <a href="javascript:void(0)" onclick="openPrintModal('${idVal}', '${rowData.tur_kod}')"><i class="ti ti-printer"></i> Formu Yazdır</a>
            ${durum === 'beklemede' ? `
                <a href="javascript:void(0)" onclick="onayla('${idVal}')" class="text-success"><i class="ti ti-check"></i> Talebi Onayla</a>
                <a href="javascript:void(0)" onclick="kismiOnaylaAc('${idVal}', '${rowData.baslangic_tarihi}', '${rowData.bitis_tarihi}', '${escapeHtml(rowData.personel_adi)}', ${rowData.gun_sayisi})" class="text-warning"><i class="ti ti-calendar-check"></i> Kısmi Onayla</a>
                <a href="javascript:void(0)" onclick="openRedModal('${idVal}')" class="text-danger"><i class="ti ti-x"></i> Talebi Reddet</a>
            ` : ''}
            ${durum === 'onaylandi' ? `
                <a href="javascript:void(0)" onclick="openRedModal('${idVal}')" class="text-warning"><i class="ti ti-rotate-left"></i> Onayı Geri Al</a>
            ` : ''}
            ${CAN_DELETE_APPROVED && (durum === 'onaylandi' || durum === 'reddedildi') ? `
                <div class="cm-divider"></div>
                <a href="javascript:void(0)" onclick="talepSil('${idVal}')" class="cm-danger"><i class="ti ti-trash"></i> Talebi Sil</a>
            ` : ''}
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
            $('#izin-table tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#izin-table tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#izin-table tbody tr').removeClass('context-menu-active');
    });

    loadList();
});
</script>
