<?php
require_once "Model/PersonIcra.php";
require_once "Model/Persons.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/security.php";
require_once "App/Helper/date.php";

use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\Date;

// Kullanıcının firmasını kontrol et
$Auths->checkFirmReturn();

// Yetki kontrolü - icra_files_list veya person_page_icra_info yetkisi
if (!$Auths->Authorize('icra_files_list') && !$Auths->Authorize('person_page_icra_info')) {
    Helper::authorizePage();
    return;
}

$firm_id = (int)($_SESSION['firm_id'] ?? 0);
$personIcraModel = new PersonIcra();
$personsModel = new Persons();

// İstatistik verileri
$stats = $personIcraModel->getFirmIcraStats($firm_id);

// Firma personelleri (Yeni dosya ekleme modalı için)
$firmPersons = $personsModel->getPersonsByFirm($firm_id);

// İcra daireleri tanımları (Modal için)
$admin_id = $_SESSION['user']->parent_id != 0 ? $_SESSION['user']->parent_id : $_SESSION['user']->id;
$sql_defines = "SELECT DISTINCT daire_adi FROM icra_daireleri WHERE (admin_id = ? OR admin_id = 0) AND durum = 'Aktif' AND silinme_tarihi IS NULL ORDER BY daire_adi ASC";
$q_defines = $personsModel->getDb()->prepare($sql_defines);
$q_defines->execute([$admin_id]);
$icraDaireleri = $q_defines->fetchAll(PDO::FETCH_COLUMN);

// Merkezi Durum Tanımları
$statuses = PersonIcra::getStatuses();
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'icra-summary-collapsed',
            localStorage.getItem('icra_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.icra-summary-collapsed #icraSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="icraPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-file-invoice" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            İcra Dosyaları Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Personel icra dosyaları, kesinti durumları, bakiye ve icra dairesi takibi
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon icra-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="icraColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxes will be rendered dynamically by JS -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark shadow-sm icra-header-action" id="btn-open-add-modal" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-plus me-1"></i> Yeni İcra Dosyası
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle icra-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="javascript:void(0)" id="btn-export-excel-list" class="dropdown-item">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <a href="javascript:void(0)" id="btn-print-list" class="dropdown-item">
                                <i class="ti ti-printer icon me-2 text-secondary"></i> Yazdır / PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="icraSummaryCards">
        <!-- Kart 1: Toplam Dosya -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border icra-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM DOSYA</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-file-invoice" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;" id="kpi-total-files">
                        <?= number_format($stats['total_files'] ?? 0, 0, ',', '.'); ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Toplam: <strong class="text-dark" id="kpi-total-debt"><?= Helper::formattedMoney($stats['total_debt'] ?? 0); ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm icra dosyalarını göster">
                            <input type="radio" name="icra_status_filter" value="" class="status-filter">
                            <span><i class="ti ti-files"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Kesilen (Aktif) Dosyalar -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border icra-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">KESİLEN (AKTİF) DOSYALAR</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-scissors" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;" id="kpi-active-files">
                        <?= number_format($stats['active_files'] ?? 0, 0, ',', '.'); ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Aktif Kesintide
                        </span>
                        <label class="status-summary-filter mb-0" title="Kesilen (Aktif) dosyaları göster">
                            <input type="radio" name="icra_status_filter" value="Kesilen" class="status-filter" checked>
                            <span><i class="ti ti-scissors"></i> Kesilen</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Bekleyen / Sırada Dosyalar -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border icra-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BEKLEYEN / SIRADA DOSYALAR</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-clock" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;" id="kpi-pending-files">
                        <?= number_format($stats['pending_files'] ?? 0, 0, ',', '.'); ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Sıradaki Dosyalar
                        </span>
                        <label class="status-summary-filter mb-0" title="Bekleyen dosyaları göster">
                            <input type="radio" name="icra_status_filter" value="Bekliyor" class="status-filter">
                            <span><i class="ti ti-clock"></i> Bekliyor</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Kalan Borç -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border icra-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">KALAN BORÇ</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-wallet" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;" id="kpi-remaining-debt">
                        <?= Helper::formattedMoney($stats['remaining_debt'] ?? 0); ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Kesilen: <strong class="text-success" id="kpi-total-deductions"><?= Helper::formattedMoney($stats['total_deductions'] ?? 0); ?></strong>
                        </span>
                        <span class="badge bg-danger-lt fw-semibold" style="font-size: 10px;">Kalan Bakiye</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card icra-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">İcra Dosyaları Listesi</h4>
                                <a href="javascript:void(0)" class="btn-card-header-add" id="btn-open-add-modal-header" data-tooltip="Yeni İcra Dosyası Ekle">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık arama, sütun filtreleme ve icra dosyaları yönetimi</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon icra-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="icra-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="icra-search-clear" class="icra-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleIcraSummary" class="btn btn-sm btn-outline-secondary btn-icon icra-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container (Seamless inside card) -->
                <div class="table-responsive icra-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="icraMainTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false">#</th>
                                <th>Personel</th>
                                <th class="text-center" style="width: 80px;">İcra Sırası</th>
                                <th>İcra Dairesi</th>
                                <th>Dosya No</th>
                                <th>Alacaklı / Avukat</th>
                                <th class="text-end">Toplam Borç</th>
                                <th class="text-center">Kesinti Yöntemi</th>
                                <th class="text-end">Yapılan Kesinti</th>
                                <th class="text-end">Kalan Borç</th>
                                <th class="text-center">Durum</th>
                                <th style="width:110px; min-width:110px;" class="no-export text-end actions-column" data-orderable="false">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- AJAX / JS ile doldurulacak -->
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- İcra Dosyası Ekle / Düzenle Modal (Modern & Kullanıcı Dostu Sekmeli Tasarım) -->
<div class="modal modal-blur fade" id="icraFileModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 680px;">
        <div class="modal-content shadow-lg border-0 icra-modal-content">
            <form id="form-icra-file" enctype="multipart/form-data">
                <input type="hidden" name="id" id="icra-file-id" value="">
                
                <!-- Modal Header -->
                <div class="modal-header py-3 px-3.5 bg-light-subtle border-bottom">
                    <div class="d-flex align-items-center gap-2.5">
                        <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-xs" style="width: 38px; height: 38px;">
                            <i class="ti ti-file-invoice font-20"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0 font-15" id="modal-icra-title">
                                Yeni İcra Dosyası Ekle
                            </h5>
                            <div class="text-secondary font-11 mt-0.5">Personel icra takibi ve yasal maaş haczi detayları</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modern Segmented Pill Tabs -->
                <div class="px-3.5 pt-3 pb-1 bg-light-subtle border-bottom">
                    <div class="icra-segmented-tabs nav nav-pills nav-fill p-1 bg-white rounded-3 border" role="tablist">
                        <button type="button" class="nav-link active py-2 px-3 fw-semibold font-12 rounded-2 d-flex align-items-center justify-content-center gap-1.5" data-bs-toggle="pill" data-bs-target="#tab-icra-genel" role="tab">
                            <i class="ti ti-file-text font-16 text-primary"></i>
                            <span>Genel & Borç Bilgileri</span>
                        </button>
                        <button type="button" class="nav-link py-2 px-3 fw-semibold font-12 rounded-2 d-flex align-items-center justify-content-center gap-1.5" data-bs-toggle="pill" data-bs-target="#tab-icra-evrak" role="tab">
                            <i class="ti ti-calendar font-16 text-info"></i>
                            <span>Tarih, Evrak & Belgeler</span>
                        </button>
                    </div>
                </div>

                <!-- Modal Body (Tab İçerikleri) -->
                <div class="modal-body p-3.5">
                    <div class="tab-content">
                        
                        <!-- TAB 1: GENEL & BORÇ BİLGİLERİ -->
                        <div class="tab-pane active show" id="tab-icra-genel" role="tabpanel">
                            <div class="row g-3">
                                <!-- Personel Seçimi -->
                                <div class="col-12">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-user me-1 text-primary"></i>Personel
                                    </label>
                                    <select class="form-select select2-modal" name="person_id" id="modal-person-id" required style="width: 100%;">
                                        <option value="">Personel Seçiniz...</option>
                                        <?php foreach ($firmPersons as $p): ?>
                                            <?php $decryptedKimlik = Security::safeDecrypt($p->kimlik_no ?? ''); ?>
                                            <option value="<?= Security::encrypt($p->id); ?>" data-person-id="<?= (int)$p->id; ?>" data-fullname="<?= htmlspecialchars($p->full_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-tc="<?= htmlspecialchars($decryptedKimlik ?: 'TC Yok', ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars($p->full_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- İcra Dairesi & Dosya No -->
                                <div class="col-sm-6">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-building-bank me-1 text-muted"></i>İcra Dairesi
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-building-bank text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control" name="icra_dairesi" id="modal-icra-dairesi" list="icra-daireleri-list" placeholder="Örn: İstanbul 3. İcra Dairesi" required autocomplete="off">
                                        <datalist id="icra-daireleri-list">
                                            <?php foreach ($icraDaireleri as $daire): ?>
                                                <option value="<?= htmlspecialchars($daire, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php endforeach; ?>
                                        </datalist>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-hash me-1 text-muted"></i>Dosya No
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-file-certificate text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control" name="dosya_no" id="modal-dosya-no" placeholder="Örn: 2026/1234 Esas" required>
                                    </div>
                                </div>

                                <!-- Alacaklı / Avukat -->
                                <div class="col-12">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-scale me-1 text-muted"></i>Alacaklı / Avukat / Kurum
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-briefcase text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control" name="alacakli" id="modal-alacakli" placeholder="Alacaklı kişi, banka veya vekil adı" required>
                                    </div>
                                </div>

                                <!-- Toplam Borç Tutarı & Dosya Durumu -->
                                <div class="col-sm-6">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-coin me-1 text-muted"></i>Toplam Borç Tutarı
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-currency-lira text-primary fw-bold font-15"></i>
                                        </span>
                                        <input type="text" class="form-control money-input fw-bold text-dark" name="toplam_borc" id="modal-toplam-borc" placeholder="0,00" required>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-flag me-1 text-muted"></i>Dosya Durumu
                                    </label>
                                    <select class="form-select select2-modal" name="durum" id="modal-durum" required style="width:100%;">
                                        <?php foreach ($statuses as $key => $stInfo): ?>
                                            <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?= htmlspecialchars($stInfo['title'], ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <!-- Kesinti Yöntemi & Oran/Tutar -->
                                <div class="col-sm-6">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-adjustments-horizontal me-1 text-muted"></i>Kesinti Şekli
                                    </label>
                                    <select class="form-select select2-modal" name="kesinti_yontemi" id="modal-kesinti-yontemi" style="width:100%;">
                                        <option value="oran">Maaş Oranı (%25, 1/4 vb.)</option>
                                        <option value="sabit">Sabit Tutar (₺)</option>
                                    </select>
                                </div>
                                <div class="col-sm-6" id="wrapper-kesinti-orani">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-percentage me-1 text-muted"></i>Kesinti Oranı
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-percentage text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control" name="kesinti_orani" id="modal-kesinti-orani" value="1/4" placeholder="Örn: 1/4 veya %25">
                                    </div>
                                </div>
                                <div class="col-sm-6 d-none" id="wrapper-kesinti-tutari">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-currency-lira me-1 text-muted"></i>Aylık Sabit Tutar (₺)
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-currency-lira text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control money-input" name="kesinti_tutari" id="modal-kesinti-tutari" placeholder="0,00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: TARİH, EVRAK & BELGELER -->
                        <div class="tab-pane" id="tab-icra-evrak" role="tabpanel">
                            <div class="row g-3">
                                <!-- İcra Sırası -->
                                <div class="col-sm-4">
                                    <label class="form-label required fw-semibold font-12 mb-1">
                                        <i class="ti ti-list-numbers me-1 text-muted"></i>İcra Sırası
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-list-numbers text-muted font-15"></i>
                                        </span>
                                        <input type="number" class="form-control" name="icra_sirasi" id="modal-icra-sirasi" value="1" min="1" required>
                                    </div>
                                </div>
                                <!-- Başlama ve Bitiş Tarihleri -->
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold font-12 mb-1">
                                        <i class="ti ti-calendar-event me-1 text-muted"></i>Başlama Tarihi
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control flatpickr-input" name="baslama_tarihi" id="modal-baslama-tarihi" placeholder="YYYY-AA-GG">
                                    </div>
                                </div>
                                <div class="col-sm-4">
                                    <label class="form-label fw-semibold font-12 mb-1">
                                        <i class="ti ti-calendar-off me-1 text-muted"></i>Bitiş Tarihi
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar-off text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control flatpickr-input" name="bitis_tarihi" id="modal-bitis-tarihi" placeholder="YYYY-AA-GG">
                                    </div>
                                </div>

                                <!-- Gelen / Giden Evrak No -->
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold font-12 mb-1">
                                        <i class="ti ti-mail-down me-1 text-muted"></i>Gelen Evrak No / Tarih
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-mail-down text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control" name="gelen_evrak" id="modal-gelen-evrak" placeholder="Örn: 12345 / 15.01.2026">
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label fw-semibold font-12 mb-1">
                                        <i class="ti ti-mail-up me-1 text-muted"></i>Giden Evrak No / Tarih
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-mail-up text-muted font-15"></i>
                                        </span>
                                        <input type="text" class="form-control" name="giden_evrak" id="modal-giden-evrak" placeholder="Örn: Cevap Yazısı 54321">
                                    </div>
                                </div>

                                <!-- İcra Belgesi Yükleme (Dropzone Box) -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold font-12 mb-1">
                                        <i class="ti ti-paperclip me-1 text-muted"></i>İcra Belgesi (PDF / Görsel / Doküman)
                                    </label>
                                    <div class="icra-file-upload-box p-3 bg-light-subtle rounded-3 border border-dashed text-center position-relative" id="modal-file-box">
                                        <input type="file" class="form-control position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" name="belge_dosyasi" id="modal-belge-dosyasi" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" style="z-index: 2;">
                                        <div class="d-flex flex-column align-items-center pointer-events-none">
                                            <div class="avatar avatar-sm bg-primary-lt text-primary rounded-circle mb-1.5" style="width: 32px; height: 32px;">
                                                <i class="ti ti-cloud-upload font-18"></i>
                                            </div>
                                            <div class="fw-semibold text-dark font-12" id="modal-file-label">Belge Seçmek İçin Tıklayın veya Sürükleyin</div>
                                            <div class="text-muted font-11 mt-0.5">Maksimum 5MB • PDF, Word, Excel, Görsel</div>
                                        </div>
                                    </div>
                                    <div id="modal-existing-file-container" class="mt-2 d-none">
                                        <div class="d-flex align-items-center justify-content-between p-2 rounded-2 bg-blue-lt border border-blue-subtle">
                                            <div class="d-flex align-items-center gap-2">
                                                <i class="ti ti-file-check text-blue font-16"></i>
                                                <span class="font-12 fw-medium text-blue">Bu dosyaya ait sisteme kayıtlı bir belge mevcut</span>
                                            </div>
                                            <a href="javascript:void(0)" id="modal-existing-file-link" target="_blank" class="btn btn-sm btn-primary py-0.5 px-2.5 font-11 shadow-xs">
                                                <i class="ti ti-download me-1"></i>Belgeyi İndir / Aç
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <!-- Açıklama / Notlar -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold font-12 mb-1">
                                        <i class="ti ti-notes me-1 text-muted"></i>Açıklama / Notlar
                                    </label>
                                    <textarea class="form-control" name="aciklama" id="modal-aciklama" rows="2" placeholder="Varsa ek açıklama, banka IBAN bilgisi veya takip notları..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer py-2.5 px-3.5 bg-light-subtle border-top d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none fw-medium" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-dark px-4 shadow-sm" id="btn-save-icra" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-device-floppy me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- İcra Kesintileri Geçmişi Modalı -->
<div class="modal modal-blur fade" id="deductionsHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header py-2.5 px-3 bg-light-subtle border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                        <i class="ti ti-history font-16"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold font-14 mb-0" id="modal-deductions-title">İcra Kesintileri Geçmişi</h5>
                        <div class="text-muted font-11">Bordro dönemlerinde yapılan maaş haczi kesintileri</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light-subtle">
                    <div>
                        <div class="small text-muted text-uppercase tracking-wide font-weight-600" style="font-size: 11px;">Personel / Dosya</div>
                        <div class="font-weight-700 fs-4" id="modal-deductions-person-name">-</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted text-uppercase tracking-wide font-weight-600" style="font-size: 11px;">Toplam Kesilen</div>
                        <div class="h3 mb-0 text-success font-weight-700" id="modal-deductions-total">0,00 ₺</div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-vcenter table-striped table-sm mb-0">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th class="ps-3">Dönem</th>
                                <th>Açıklama</th>
                                <th class="text-end pe-3">Tutar</th>
                            </tr>
                        </thead>
                        <tbody id="modal-deductions-table-body">
                            <!-- AJAX ile doldurulacak -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 px-3 bg-light-subtle d-flex justify-content-between">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-modal-print-deductions">
                        <i class="ti ti-printer me-1"></i> Yazdır
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm" id="btn-modal-excel-deductions">
                        <i class="ti ti-file-excel me-1"></i> Excel
                    </button>
                </div>
                <button type="button" class="btn btn-secondary btn-sm px-3 ms-auto" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
.icra-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.icra-header-icon-action,
.icra-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.icra-header-icon-action i,
.icra-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#icraPage .icra-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#icraSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#icraPage .icra-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.icra-table-card > .icra-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.icra-table-card > .card-header {
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

.icra-search-wrap { position: relative; }
#icra-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.icra-search-wrap,
.icra-search-wrap.input-icon {
    height: 32px !important;
}
.icra-search-clear {
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
.icra-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#icraMainTable_wrapper,
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
table#icraMainTable.data-table,
table#icraMainTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#icraMainTable.data-table tbody,
table#icraMainTable.dataTable tbody,
table#icraMainTable.data-table tbody tr:last-child,
table#icraMainTable.dataTable tbody tr:last-child,
#icraMainTable_wrapper .dt-layout-table,
#icraMainTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#icraMainTable.data-table thead th {
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
table#icraMainTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#icraMainTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#icraMainTable.data-table td.actions-column .btn.btn-sm.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
table#icraMainTable.data-table td.actions-column .btn.btn-sm.btn-icon i {
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    font-size: 13px !important;
}
table#icraMainTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#icraMainTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#icraMainTable.dataTable > tbody > tr:last-child > *,
table#icraMainTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#icraMainTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#icraMainTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#icraMainTable_wrapper .dt-layout-row:has(.dt-paging),
div#icraMainTable_wrapper .dt-layout-row:has(.dt-info) {
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

/* Custom Context Menu */
.custom-context-menu {
    position: fixed;
    z-index: 1050;
    display: none;
    min-width: 190px;
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
    display: flex;
    align-items: center;
    gap: 6px;
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

/* Modal & Input UX/UI Enhancement */
.icra-modal-content {
    border-radius: 14px !important;
    overflow: hidden;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.18) !important;
}

.icra-segmented-tabs {
    background: #f1f5f9 !important;
    border-radius: 9px !important;
    padding: 3px !important;
    border: 1px solid #e2e8f0 !important;
}
.icra-segmented-tabs .nav-link {
    color: #64748b !important;
    border: none !important;
    background: transparent !important;
    border-radius: 7px !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
}
.icra-segmented-tabs .nav-link:hover {
    color: #1e293b !important;
    background: rgba(255, 255, 255, 0.6) !important;
}
.icra-segmented-tabs .nav-link.active {
    color: #0f172a !important;
    background: #ffffff !important;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08), 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    font-weight: 700 !important;
}

#icraFileModal .form-control,
#icraFileModal .form-select {
    border-color: #cbd5e1;
    border-radius: 8px;
    font-size: 13px;
    background-color: #f8fafc;
    min-height: 38px;
    transition: all 0.15s ease-in-out;
}
#icraFileModal .form-control:focus,
#icraFileModal .form-select:focus {
    background-color: #ffffff;
    border-color: #206bc4;
    box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.12);
}

#icraFileModal .input-icon {
    position: relative;
}
#icraFileModal .input-icon .input-icon-addon {
    color: #64748b;
    width: 2.25rem;
    height: 100%;
    position: absolute;
    top: 0;
    left: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
    z-index: 4;
}
#icraFileModal .input-icon > .form-control,
#icraFileModal .input-icon > .form-select {
    padding-left: 2.25rem !important;
}
#icraFileModal .input-icon textarea.form-control {
    padding-left: 2.25rem !important;
    min-height: 70px;
}

#icraFileModal .select2-container--bootstrap-5 .select2-selection {
    border-color: #cbd5e1;
    border-radius: 8px;
    background-color: #f8fafc;
    min-height: 38px;
    padding: 4px 10px;
    font-size: 13px;
    transition: all 0.15s ease-in-out;
}
#icraFileModal .select2-container--bootstrap-5.select2-container--focus .select2-selection,
#icraFileModal .select2-container--bootstrap-5.select2-container--open .select2-selection {
    background-color: #ffffff;
    border-color: #206bc4;
    box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.12);
}

.icra-file-upload-box {
    background-color: #f8fafc;
    border: 1.5px dashed #cbd5e1 !important;
    border-radius: 8px;
    transition: all 0.2s ease;
    cursor: pointer;
}
.icra-file-upload-box:hover {
    background-color: rgba(32, 107, 196, 0.03);
    border-color: #206bc4 !important;
}

/* Dark Mode */
[data-bs-theme="dark"] #icraFileModal .icra-modal-content {
    background-color: #182433 !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] .icra-segmented-tabs {
    background: #0f172a !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] .icra-segmented-tabs .nav-link {
    color: #94a3b8 !important;
}
[data-bs-theme="dark"] .icra-segmented-tabs .nav-link.active {
    background: #1e293b !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #icraFileModal .form-control,
[data-bs-theme="dark"] #icraFileModal .form-select,
[data-bs-theme="dark"] #icraFileModal .select2-container--bootstrap-5 .select2-selection {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] .icra-file-upload-box {
    background-color: #0f172a !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#icraMainTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#icraMainTable.data-table,
[data-bs-theme="dark"] table#icraMainTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#icraMainTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#icraMainTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#icraMainTable.data-table tbody tr:hover td {
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
[data-bs-theme="dark"] .icra-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .icra-summary-card,
[data-bs-theme="dark"] .icra-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

#icraMainTable th:last-child,
#icraMainTable td:last-child {
    width: 110px !important;
    min-width: 110px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 12px !important;
}
</style>

<script>
$(document).ready(function() {
    let icraDataTable = null;
    let currentStatus = 'Kesilen';

    // Summary Toggle Logic
    var $summaryToggle = $('#toggleIcraSummary');
    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('icra-summary-collapsed');
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
        var isCollapsed = document.documentElement.classList.toggle('icra-summary-collapsed');
        try {
            localStorage.setItem('icra_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    // Status UI Badge Mapping
    const statusMap = {
        'Kesilen': '<span class="badge bg-success-lt text-success fw-bold"><i class="ti ti-scissors me-1"></i>Kesilen</span>',
        'Bekliyor': '<span class="badge bg-warning-lt text-warning fw-bold"><i class="ti ti-clock me-1"></i>Bekliyor</span>',
        'Güncellendi': '<span class="badge bg-info-lt text-info fw-bold"><i class="ti ti-refresh me-1"></i>Güncellendi</span>',
        'Durduruldu': '<span class="badge bg-secondary-lt text-secondary fw-bold"><i class="ti ti-player-pause me-1"></i>Durduruldu</span>',
        'Durduruldu(Bekleyen)': '<span class="badge bg-warning-lt text-warning fw-bold"><i class="ti ti-pause me-1"></i>Durduruldu (Bekleyen)</span>',
        'Fekki Geldi': '<span class="badge bg-success-lt text-success fw-bold"><i class="ti ti-check me-1"></i>Fekki Geldi</span>',
        'Kesinti Bitti': '<span class="badge bg-muted-lt text-muted fw-bold"><i class="ti ti-circle-check me-1"></i>Kesinti Bitti</span>'
    };

    // 1. Select2 İlklendirme
    if ($.fn.select2) {
        $('#modal-durum').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#icraFileModal'),
            minimumResultsForSearch: Infinity,
            templateResult: function(state) {
                if (!state.id) return state.text;
                var dotClass = 'bg-secondary';
                if (state.id === 'Kesilen') dotClass = 'bg-success';
                else if (state.id === 'Bekliyor') dotClass = 'bg-warning';
                else if (state.id === 'Güncellendi') dotClass = 'bg-info';
                else if (state.id === 'Durduruldu' || state.id === 'Durduruldu(Bekleyen)') dotClass = 'bg-danger';
                else if (state.id === 'Fekki Geldi' || state.id === 'Kesinti Bitti') dotClass = 'bg-primary';

                return $(
                    '<div class="d-flex align-items-center gap-2 py-0.5">' +
                        '<span class="badge badge-dot ' + dotClass + '"></span>' +
                        '<span class="fw-medium font-12 text-dark">' + state.text + '</span>' +
                    '</div>'
                );
            },
            templateSelection: function(state) {
                if (!state.id) return state.text;
                var dotClass = 'bg-secondary';
                if (state.id === 'Kesilen') dotClass = 'bg-success';
                else if (state.id === 'Bekliyor') dotClass = 'bg-warning';
                else if (state.id === 'Güncellendi') dotClass = 'bg-info';
                else if (state.id === 'Durduruldu' || state.id === 'Durduruldu(Bekleyen)') dotClass = 'bg-danger';
                else if (state.id === 'Fekki Geldi' || state.id === 'Kesinti Bitti') dotClass = 'bg-primary';

                return $(
                    '<div class="d-inline-flex align-items-center gap-2">' +
                        '<span class="badge badge-dot ' + dotClass + '"></span>' +
                        '<span class="fw-semibold text-dark font-12">' + state.text + '</span>' +
                    '</div>'
                );
            }
        });

        $('#modal-kesinti-yontemi').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#icraFileModal'),
            minimumResultsForSearch: Infinity
        });

        $('#modal-person-id').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#icraFileModal'),
            templateResult: function(state) {
                if (!state.id) return state.text;
                const elem = $(state.element);
                const fullname = elem.data('fullname') || state.text;
                const tc = elem.data('tc');
                const initial = fullname.charAt(0).toUpperCase() || 'P';
                
                return $(
                    '<div class="d-flex align-items-center gap-2 py-1">' +
                        '<span class="avatar avatar-xs rounded bg-primary-lt text-primary fw-bold" style="width: 24px; height: 24px; font-size: 11px;">' + initial + '</span>' +
                        '<div style="line-height: 1.2;">' +
                            '<div class="fw-bold text-dark font-12">' + fullname + '</div>' +
                            (tc ? '<div class="text-muted font-11">TC: ' + tc + '</div>' : '') +
                        '</div>' +
                    '</div>'
                );
            },
            templateSelection: function(state) {
                if (!state.id) return state.text;
                const elem = $(state.element);
                const fullname = elem.data('fullname') || state.text;
                const tc = elem.data('tc');
                
                return $(
                    '<div class="d-inline-flex align-items-center gap-1.5">' +
                        '<i class="ti ti-user text-primary font-14"></i>' +
                        '<span class="fw-bold text-dark font-12">' + fullname + '</span>' +
                        (tc ? '<span class="badge bg-primary-lt text-primary font-11 py-0 px-1.5 ms-1">TC: ' + tc + '</span>' : '') +
                    '</div>'
                );
            }
        });
    }

    // File change handler for custom dropzone
    $('#modal-belge-dosyasi').on('change', function() {
        var fileName = this.files && this.files.length ? this.files[0].name : '';
        if (fileName) {
            $('#modal-file-label').html('<i class="ti ti-file-check text-success me-1"></i> ' + fileName);
            $('#modal-file-box').addClass('border-success bg-success-lt');
        } else {
            $('#modal-file-label').text('Belge Seçmek İçin Tıklayın veya Sürükleyin');
            $('#modal-file-box').removeClass('border-success bg-success-lt');
        }
    });

    // 2. Flatpickr İlklendirme
    if (typeof flatpickr !== 'undefined') {
        flatpickr(".flatpickr-input", {
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d.m.Y",
            locale: "tr",
            allowInput: true
        });
    }

    // 3. Kesinti Yöntemi Değişimi
    $('#modal-kesinti-yontemi').on('change', function() {
        const val = $(this).val();
        if (val === 'oran') {
            $('#wrapper-kesinti-orani').removeClass('d-none');
            $('#wrapper-kesinti-tutari').addClass('d-none');
        } else {
            $('#wrapper-kesinti-orani').addClass('d-none');
            $('#wrapper-kesinti-tutari').removeClass('d-none');
        }
    });

    // Sütun Konfigürasyonu
    var columnConfig = {
        1: { label: 'Personel', default: true },
        2: { label: 'İcra Sırası', default: true },
        3: { label: 'İcra Dairesi', default: true },
        4: { label: 'Dosya No', default: true },
        5: { label: 'Alacaklı / Avukat', default: true },
        6: { label: 'Toplam Borç', default: true },
        7: { label: 'Kesinti Yöntemi', default: true },
        8: { label: 'Yapılan Kesinti', default: true },
        9: { label: 'Kalan Borç', default: true },
        10: { label: 'Durum', default: true }
    };

    function setupColumnVisibilityMenu(dtInstance) {
        var savedVisibility = localStorage.getItem('icra_column_visibility');
        var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};

        var menuHtml = '';
        $.each(columnConfig, function(idx, conf) {
            var isVisible = visibilityState.hasOwnProperty(idx) ? visibilityState[idx] : conf.default;
            dtInstance.column(idx).visible(isVisible, false);

            menuHtml += `
                <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size: 0.85rem;">
                    <div class="form-check mb-0 w-100">
                        <input class="form-check-input icra-col-trigger" type="checkbox" id="colCheck_${idx}" data-column="${idx}" ${isVisible ? "checked" : ""}>
                        <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">
                            ${conf.label}
                        </span>
                    </div>
                </label>`;
        });

        $('#icraColvisMenu').html(menuHtml);
        dtInstance.columns.adjust();
    }

    $(document).on('change', '.icra-col-trigger', function() {
        if (!icraDataTable) return;
        var colIdx = parseInt($(this).data('column'));
        var isChecked = this.checked;
        icraDataTable.column(colIdx).visible(isChecked);

        var savedVisibility = localStorage.getItem('icra_column_visibility');
        var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};
        visibilityState[colIdx] = isChecked;
        localStorage.setItem('icra_column_visibility', JSON.stringify(visibilityState));
    });

    $(document).on('click', '#icraColvisMenu', function(e) {
        e.stopPropagation();
    });

    // 4. Veri Yükleme & DataTable Kurulumu
    function loadIcraTable() {
        $.ajax({
            url: 'api/persons/icra.php',
            type: 'POST',
            data: {
                action: 'firm_list',
                status_filter: currentStatus ? [currentStatus] : []
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // KPI güncelle
                    if (res.stats) {
                        $('#kpi-total-files').text(res.stats.total_files || 0);
                        $('#kpi-active-files').text(res.stats.active_files || 0);
                        $('#kpi-pending-files').text(res.stats.pending_files || 0);
                        $('#kpi-remaining-debt').text(res.stats.remaining_debt || '0,00 ₺');
                        $('#kpi-total-debt').text(res.stats.total_debt || '0,00 ₺');
                        $('#kpi-total-deductions').text(res.stats.total_deductions || '0,00 ₺');
                    }

                    // DataTable destroy if exists
                    if ($.fn.DataTable.isDataTable('#icraMainTable')) {
                        $('#icraMainTable').DataTable().destroy();
                    }

                    const tbody = $('#icraMainTable tbody');
                    tbody.empty();

                    if (res.files && res.files.length > 0) {
                        res.files.forEach((f, idx) => {
                            let statusBadge = statusMap[f.durum] || `<span class="badge bg-secondary-lt text-secondary fw-bold">${f.durum}</span>`;

                            let kesintiSekli = '';
                            if (f.kesinti_yontemi === 'sabit') {
                                kesintiSekli = f.kesinti_tutari ? (f.kesinti_tutari + ' (Sabit)') : 'Sabit';
                            } else {
                                kesintiSekli = (f.kesinti_orani || '%25') + ' (Oran)';
                            }

                            let fileBtn = '';
                            if (f.has_belge) {
                                fileBtn = `
                                    <a href="api/persons/icra.php?action=download&id=${f.id}" class="btn btn-sm btn-icon btn-ghost-primary" title="Evrak İndir" target="_blank">
                                        <i class="ti ti-download"></i>
                                    </a>
                                `;
                            }

                            let fileJsonData = encodeURIComponent(JSON.stringify(f));

                            let tr = `
                                <tr data-file="${fileJsonData}" data-id="${f.id}" data-person-id="${f.person_id}" data-person-name="${f.person_name}" data-dosya-no="${f.dosya_no}" data-has-belge="${f.has_belge ? '1' : '0'}">
                                    <td class="text-center font-weight-600 text-secondary">${idx + 1}</td>
                                    <td>
                                        <a href="index.php?p=persons/manage&id=${f.person_id}&tab=icra" class="font-weight-700 text-reset text-decoration-none hover-primary">
                                            ${f.person_name}
                                        </a>
                                        <div class="small text-muted" style="font-size: 11px;">TC: ${f.person_tc || '-'} | Sicil: ${f.person_sicil || '-'}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-blue-lt text-blue">${f.icra_sirasi}. Sıra</span>
                                    </td>
                                    <td>${f.icra_dairesi}</td>
                                    <td class="font-weight-600">${f.dosya_no}</td>
                                    <td>${f.alacakli}</td>
                                    <td class="text-end font-weight-700">${f.toplam_borc}</td>
                                    <td class="text-center"><span class="badge bg-secondary-lt text-secondary">${kesintiSekli}</span></td>
                                    <td class="text-end font-weight-700">
                                        <a href="javascript:void(0)" class="text-success text-decoration-underline btn-view-deductions" data-file-id="${f.id}" data-person-id="${f.person_id}" title="Kesinti Detaylarını Gör">
                                            ${f.yapilan_kesinti} <i class="ti ti-info-circle ms-1 small"></i>
                                        </a>
                                    </td>
                                    <td class="text-end text-danger font-weight-700">${f.kalan_borc}</td>
                                    <td class="text-center">${statusBadge}</td>
                                    <td class="text-end actions-column">
                                        <div class="d-inline-flex gap-1 align-items-center justify-content-end">
                                            ${fileBtn}
                                            <button type="button" class="btn btn-sm btn-icon btn-ghost-primary btn-edit-icra" data-file="${fileJsonData}" title="Düzenle">
                                                <i class="ti ti-edit"></i>
                                            </button>
                                            <a href="index.php?p=persons/manage&id=${f.person_id}&tab=icra" class="btn btn-sm btn-icon btn-ghost-secondary" title="Personel Detayı">
                                                <i class="ti ti-external-link"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-icon btn-ghost-danger btn-delete-icra" data-id="${f.id}" title="Sil">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            tbody.append(tr);
                        });
                    }

                    // DataTable init
                    icraDataTable = $('#icraMainTable').DataTable({
                        pageLength: 25,
                        lengthMenu: [10, 25, 50, 100],
                        order: [[1, 'asc']],
                        columnDefs: [
                            { targets: [0, 11], orderable: false, searchable: false },
                            { targets: 0, className: 'text-center no-export' },
                            { targets: [2, 7, 10], className: 'text-center' },
                            { targets: [6, 8, 9], className: 'text-end' },
                            { targets: 11, width: '110px', className: 'text-end no-export actions-column' }
                        ],
                        language: {
                            url: 'src/tr.json',
                            processing: '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...'
                        },
                        initComplete: function() {
                            var api = this.api();
                            if (typeof window.initDataTableColumnFilters === 'function') {
                                window.initDataTableColumnFilters($('#icraMainTable'), api);
                            }
                            setupColumnVisibilityMenu(api);
                        }
                    });

                    // Eğer hızlı arama kutusunda değer varsa uygula
                    const searchVal = $('#icra-fast-search').val();
                    if (searchVal) {
                        icraDataTable.search(searchVal).draw();
                    }
                } else {
                    Swal.fire('Hata!', res.message || 'Veriler yüklenirken bir hata oluştu.', 'error');
                }
            },
            error: function(err) {
                console.error("AJAX Error:", err);
                Swal.fire('Hata!', 'Sunucuyla iletişim kurulurken bir hata oluştu.', 'error');
            }
        });
    }

    // İlk yükleme
    loadIcraTable();

    // 5. Durum Filtresi Değişimi
    $('.status-filter').on('change', function() {
        currentStatus = $(this).val();
        loadIcraTable();
    });

    // 6. Hızlı Genel Arama Inputu
    var searchTimer = null;
    $('#icra-fast-search').on('input', function() {
        var val = this.value;
        $('#icra-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (icraDataTable) {
                icraDataTable.search(val).draw();
            }
        }, 300);
    });

    $('#icra-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#icra-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        if (icraDataTable) {
            icraDataTable.search('').draw();
        }
    });

    // 7. Yeni İcra Dosyası Modal Açma (Header veya Ana Buton)
    $(document).on('click', '#btn-open-add-modal, #btn-open-add-modal-header', function() {
        $('#form-icra-file')[0].reset();
        $('#icra-file-id').val('');
        $('#modal-icra-title').html('Yeni İcra Dosyası Ekle');
        $('#modal-person-id').val('').trigger('change');
        $('#modal-durum').val('Kesilen').trigger('change');
        $('#modal-kesinti-yontemi').val('oran').trigger('change');
        $('#modal-kesinti-orani').val('1/4');
        $('#modal-existing-file-container').addClass('d-none');

        // İlk sekmeye geçiş
        $('#icraFileModal [data-bs-toggle="pill"]:first').tab('show');
        $('#modal-file-label').text('Belge Seçmek İçin Tıklayın veya Sürükleyin');
        $('#modal-file-box').removeClass('border-success bg-success-lt');

        // Reset dates
        if (typeof flatpickr !== 'undefined') {
            document.querySelector("#modal-baslama-tarihi")?._flatpickr?.clear();
            document.querySelector("#modal-bitis-tarihi")?._flatpickr?.clear();
        }

        $('#icraFileModal').modal('show');
    });

    // 8. Düzenle (Edit) Modal Açma
    $(document).on('click', '.btn-edit-icra', function() {
        const rawData = $(this).attr('data-file');
        if (!rawData) return;

        try {
            const f = JSON.parse(decodeURIComponent(rawData));

            $('#form-icra-file')[0].reset();
            $('#icra-file-id').val(f.id);
            $('#modal-icra-title').html('İcra Dosyası Güncelle');
            $('#modal-file-label').text('Belge Seçmek İçin Tıklayın veya Sürükleyin');
            $('#modal-file-box').removeClass('border-success bg-success-lt');

            let targetPersonId = f.raw_person_id || f.person_id_raw;
            let optionVal = $('#modal-person-id option[data-person-id="' + targetPersonId + '"]').val();
            if (optionVal) {
                $('#modal-person-id').val(optionVal).trigger('change');
            } else {
                $('#modal-person-id').val(f.person_id).trigger('change');
            }
            $('#modal-icra-sirasi').val(f.icra_sirasi);
            $('#modal-icra-dairesi').val(f.icra_dairesi);
            $('#modal-dosya-no').val(f.dosya_no);
            $('#modal-alacakli').val(f.alacakli);
            $('#modal-toplam-borc').val(f.toplam_borc_raw || f.toplam_borc);
            $('#modal-durum').val(f.durum).trigger('change');
            $('#modal-kesinti-yontemi').val(f.kesinti_yontemi || 'oran').trigger('change');
            $('#modal-kesinti-orani').val(f.kesinti_orani || '1/4');
            $('#modal-kesinti-tutari').val(f.kesinti_tutari_raw || f.kesinti_tutari || '');

            if (typeof flatpickr !== 'undefined') {
                const fpStart = document.querySelector("#modal-baslama-tarihi")?._flatpickr;
                if (fpStart) fpStart.setDate(f.baslama_tarihi || '');
                else $('#modal-baslama-tarihi').val(f.baslama_tarihi || '');

                const fpEnd = document.querySelector("#modal-bitis-tarihi")?._flatpickr;
                if (fpEnd) fpEnd.setDate(f.bitis_tarihi || '');
                else $('#modal-bitis-tarihi').val(f.bitis_tarihi || '');
            } else {
                $('#modal-baslama-tarihi').val(f.baslama_tarihi || '');
                $('#modal-bitis-tarihi').val(f.bitis_tarihi || '');
            }

            $('#modal-gelen-evrak').val(f.gelen_evrak || '');
            $('#modal-giden-evrak').val(f.giden_evrak || '');
            $('#modal-aciklama').val(f.aciklama || '');

            // File reset / preview
            if (f.has_belge) {
                $('#modal-existing-file-link').attr('href', 'api/persons/icra.php?action=download&id=' + encodeURIComponent(f.id));
                $('#modal-existing-file-container').removeClass('d-none');
            } else {
                $('#modal-existing-file-container').addClass('d-none');
            }

            // İlk sekmeye geçiş
            $('#icraFileModal [data-bs-toggle="pill"]:first').tab('show');

            $('#icraFileModal').modal('show');
        } catch(e) {
            console.error("Error parsing icra file JSON:", e);
        }
    });

    // 9. Kaydetme Form Submit (Ekle & Güncelle)
    $('#form-icra-file').on('submit', function(e) {
        e.preventDefault();
        const $saveBtn = $('#btn-save-icra');
        const origBtnHtml = $saveBtn.html();
        $saveBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...');

        const formData = new FormData(this);
        formData.append('action', 'save');

        $.ajax({
            url: 'api/persons/icra.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(res) {
                $saveBtn.prop('disabled', false).html(origBtnHtml);
                if (res.status === 'success') {
                    $('#icraFileModal').modal('hide');
                    Swal.fire({
                        title: 'Başarılı!',
                        text: res.message || 'İcra dosyası kaydedildi.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadIcraTable();
                } else {
                    Swal.fire('Hata!', res.message || 'Kaydedilemedi.', 'error');
                }
            },
            error: function(err) {
                $saveBtn.prop('disabled', false).html(origBtnHtml);
                Swal.fire('Hata!', 'Sunucu hatası oluştu.', 'error');
            }
        });
    });

    // 10. Silme İşlemi (SweetAlert2)
    $(document).on('click', '.btn-delete-icra', function() {
        const fileId = $(this).data('id');
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu icra dosyası silinecektir!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/persons/icra.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        id: fileId
                    },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire('Silindi!', res.message || 'İcra dosyası silindi.', 'success');
                            loadIcraTable();
                        } else {
                            Swal.fire('Hata!', res.message || 'Silinemedi.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Hata!', 'Sunucu hatası oluştu.', 'error');
                    }
                });
            }
        });
    });

    let currentDeductionsFileId = null;

    // 11. Kesintiler Geçmişi Detayı Tıklama
    $(document).on('click', '.btn-view-deductions', function(e) {
        e.preventDefault();
        const fileId = $(this).data('file-id') || '';
        const personId = $(this).data('person-id') || '';
        currentDeductionsFileId = fileId;

        $('#modal-deductions-person-name').text('Yükleniyor...');
        $('#modal-deductions-total').text('0,00 ₺');
        $('#modal-deductions-table-body').html('<tr><td colspan="3" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Kesintiler yükleniyor...</td></tr>');
        $('#deductionsHistoryModal').modal('show');

        $.ajax({
            url: 'api/persons/icra.php',
            type: 'POST',
            data: {
                action: 'deductions_history',
                file_id: fileId,
                person_id: personId
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#modal-deductions-person-name').html(`<strong>${res.person_name}</strong> <span class="badge bg-secondary-lt ms-2">${res.dosya_no}</span>`);
                    $('#modal-deductions-total').text(res.total_amount || '0,00 ₺');

                    const tbody = $('#modal-deductions-table-body');
                    tbody.empty();

                    if (!res.history || res.history.length === 0) {
                        tbody.html('<tr><td colspan="3" class="text-center py-4 text-muted"><i class="ti ti-folder-off fs-1 d-block mb-1 text-secondary"></i>Bu icra dosyasına ait bordro kesintisi bulunamadı.</td></tr>');
                    } else {
                        res.history.forEach((h) => {
                            tbody.append(`
                                <tr>
                                    <td class="ps-3 fw-bold">${h.donem}</td>
                                    <td>
                                        <div class="font-weight-600">${h.aciklama || h.turu}</div>
                                        <div class="small text-muted">${h.created_at || ''}</div>
                                    </td>
                                    <td class="text-end pe-3 text-success font-weight-700">${h.tutar}</td>
                                </tr>
                            `);
                        });
                    }
                } else {
                    Swal.fire('Hata!', res.message || 'Kesintiler yüklenemedi.', 'error');
                }
            },
            error: function() {
                Swal.fire('Hata!', 'Sunucu hatası oluştu.', 'error');
            }
        });
    });

    // 12. Modal ve Liste İndirme / Yazdırma Butonları
    $('#btn-modal-print-deductions').on('click', function() {
        if (!currentDeductionsFileId) {
            Swal.fire('Uyarı', 'Lütfen önce bir icra dosyası seçiniz.', 'warning');
            return;
        }
        window.open('print_icra.php?id=' + encodeURIComponent(currentDeductionsFileId), '_blank');
    });

    $('#btn-modal-excel-deductions').on('click', function() {
        if (!currentDeductionsFileId) {
            Swal.fire('Uyarı', 'Lütfen önce bir icra dosyası seçiniz.', 'warning');
            return;
        }
        window.location.href = 'pages/persons/icra-export-xls.php?id=' + encodeURIComponent(currentDeductionsFileId);
    });

    $('#btn-export-excel-list').on('click', function() {
        const filterVal = currentStatus || '';
        window.location.href = 'pages/persons/icra-export-xls.php?status_filter=' + encodeURIComponent(filterVal);
    });

    $('#btn-print-list').on('click', function() {
        const filterVal = currentStatus || '';
        window.open('print_icra.php?status_filter=' + encodeURIComponent(filterVal), '_blank');
    });

    // 13. Tabloda Sağ Tık (Custom Context Menu)
    $(document).on('contextmenu', '#icraMainTable tbody tr', function(e) {
        var $tr = $(this);
        var rawData = $tr.attr('data-file') || $tr.find('.btn-edit-icra').attr('data-file');
        var fileId = $tr.attr('data-id') || $tr.find('.btn-delete-icra').attr('data-id');
        var personId = $tr.attr('data-person-id');
        var personName = $tr.attr('data-person-name') || $tr.find('td').eq(1).find('a').text().trim() || 'İcra Dosyası';
        var dosyaNo = $tr.attr('data-dosya-no') || $tr.find('td').eq(4).text().trim() || '';
        var hasBelge = $tr.attr('data-has-belge') === '1' || $tr.find('.ti-download').length > 0;

        if (!fileId && !rawData) return;

        e.preventDefault();
        $('#icraMainTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var headerTitle = $('<div>').text(personName + (dosyaNo ? ' (' + dosyaNo + ')' : '')).html();

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-file-invoice"></i> <span class="text-truncate">${headerTitle}</span></div>
            <a href="javascript:void(0)" class="cm-action-edit" data-file="${rawData}"><i class="ti ti-edit"></i> Dosyayı Düzenle</a>
            <a href="javascript:void(0)" class="cm-action-deductions" data-file-id="${fileId}" data-person-id="${personId}"><i class="ti ti-receipt-2"></i> Kesintileri Gör</a>
            ${personId ? `<a href="index.php?p=persons/manage&id=${personId}&tab=icra"><i class="ti ti-user"></i> Personel Sayfası</a>` : ''}
            ${hasBelge ? `<a href="api/persons/icra.php?action=download&id=${fileId}" target="_blank"><i class="ti ti-download"></i> Evrak İndir</a>` : ''}
            <div class="cm-divider"></div>
            <a href="pages/persons/icra-export-xls.php?id=${encodeURIComponent(fileId)}"><i class="ti ti-file-excel text-success"></i> Excel Raporu</a>
            <a href="print_icra.php?id=${encodeURIComponent(fileId)}" target="_blank"><i class="ti ti-printer text-secondary"></i> Yazdır / PDF</a>
            <div class="cm-divider"></div>
            <a href="javascript:void(0)" class="cm-danger cm-action-delete" data-id="${fileId}"><i class="ti ti-trash"></i> İcra Dosyasını Sil</a>
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
            $('#icraMainTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#icraMainTable tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#icraMainTable tbody tr').removeClass('context-menu-active');
    });

    // Context menu aksiyon tetikleyicileri
    $(document).on('click', '.cm-action-edit', function(e) {
        e.preventDefault();
        var rawData = $(this).attr('data-file');
        var $dummy = $('<button class="btn-edit-icra"></button>').attr('data-file', rawData);
        $('body').append($dummy);
        $dummy.trigger('click');
        $dummy.remove();
    });

    $(document).on('click', '.cm-action-deductions', function(e) {
        e.preventDefault();
        var fileId = $(this).attr('data-file-id');
        var personId = $(this).data('person-id');
        var $dummy = $('<button class="btn-view-deductions"></button>').data('file-id', fileId).data('person-id', personId);
        $('body').append($dummy);
        $dummy.trigger('click');
        $dummy.remove();
    });

    $(document).on('click', '.cm-action-delete', function(e) {
        e.preventDefault();
        var fileId = $(this).attr('data-id');
        var $dummy = $('<button class="btn-delete-icra"></button>').data('id', fileId);
        $('body').append($dummy);
        $dummy.trigger('click');
        $dummy.remove();
    });
});
</script>
