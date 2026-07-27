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
    App\Helper\Helper::authorizePage();
    return;
}

$firm_id = $_SESSION['firm_id'];
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

<style>
/* Select2 Single Selection Styling */
.select2-container--bootstrap-5 .select2-selection--single,
.select2-container--default .select2-selection--single,
.select2-container .select2-selection--single {
    height: 38px !important;
    display: flex !important;
    align-items: center !important;
    border-color: var(--tblr-border-color, #dadcde) !important;
    background-color: var(--tblr-bg-surface, #ffffff) !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered,
.select2-container--default .select2-selection--single .select2-selection__rendered,
.select2-container .select2-selection--single .select2-selection__rendered {
    line-height: normal !important;
    padding-left: 0.75rem !important;
    padding-right: 1.75rem !important;
    color: var(--tblr-body-color, #1e293b) !important;
    display: flex !important;
    align-items: center !important;
    height: 100% !important;
}

.select2-container .select2-selection--single .select2-selection__placeholder {
    color: var(--tblr-secondary, #6c757d) !important;
    line-height: normal !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__arrow,
.select2-container--default .select2-selection--single .select2-selection__arrow,
.select2-container .select2-selection--single .select2-selection__arrow {
    height: 100% !important;
    top: 0 !important;
    display: flex !important;
    align-items: center !important;
    right: 8px !important;
}

/* Select2 Multiple Selection Styling Fix */
.select2-container--bootstrap-5 .select2-selection--multiple,
.select2-container--default .select2-selection--multiple,
.select2-container .select2-selection--multiple {
    min-height: 38px !important;
    height: auto !important;
    border: 1px solid var(--tblr-border-color, #dadcde) !important;
    background-color: var(--tblr-bg-surface, #ffffff) !important;
    border-radius: 4px !important;
    padding: 3px 6px !important;
    display: flex !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    gap: 4px !important;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice,
.select2-container--default .select2-selection--multiple .select2-selection__choice,
.select2-container .select2-selection--multiple .select2-selection__choice {
    background-color: var(--tblr-bg-surface-secondary, #f1f5f9) !important;
    color: var(--tblr-body-color, #1e293b) !important;
    border: 1px solid var(--tblr-border-color, #cbd5e1) !important;
    border-radius: 4px !important;
    padding: 3px 10px !important;
    margin: 2px !important;
    font-size: 0.8125rem !important;
    font-weight: 600 !important;
    display: inline-flex !important;
    align-items: center !important;
}

.select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice__remove,
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove,
.select2-container .select2-selection--multiple .select2-selection__choice__remove {
    display: none !important;
}

.select2-container--bootstrap-5 .select2-dropdown .select2-results__option--selected,
.select2-container--default .select2-dropdown .select2-results__option--selected {
    background-color: rgba(32, 107, 196, 0.15) !important;
}

.select2-container .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #ffffff !important;
}

.select2-container .select2-search--inline {
    display: inline-flex !important;
    align-items: center !important;
    flex-grow: 1 !important;
}

.select2-container .select2-search--inline .select2-search__field {
    margin-top: 0 !important;
    height: 28px !important;
    line-height: 28px !important;
    font-size: 0.875rem !important;
    background: transparent !important;
    color: var(--tblr-body-color, inherit) !important;
    border: none !important;
    box-shadow: none !important;
}

/* Dark Mode Overrides for Select2 */
[data-bs-theme="dark"] .select2-container--bootstrap-5 .select2-selection--multiple .select2-selection__choice,
[data-bs-theme="dark"] .select2-container--default .select2-selection--multiple .select2-selection__choice,
[data-bs-theme="dark"] .select2-container .select2-selection--multiple .select2-selection__choice {
    background-color: rgba(32, 107, 196, 0.2) !important;
    color: var(--tblr-primary, #206bc4) !important;
    border-color: rgba(32, 107, 196, 0.4) !important;
}

[data-bs-theme="dark"] .select2-dropdown {
    background-color: var(--tblr-bg-surface, #1e293b) !important;
    border-color: var(--tblr-border-color, #334155) !important;
    color: var(--tblr-body-color, #f8fafc) !important;
}

/* Compact option padding for Select2 */
.select2-container .select2-results__option {
    padding-top: 4px !important;
    padding-bottom: 4px !important;
}
</style>

<div class="container-fluid px-4 py-3">
    <!-- Sayfa Başlığı ve İşlem Butonları -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle text-secondary">Personel Yönetimi</div>
                <h2 class="page-title text-primary font-weight-700">
                    <i class="ti ti-file-invoice me-2 text-primary fs-1"></i>İcra Dosyaları Listesi
                </h2>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-success shadow-sm px-3" id="btn-export-excel-list">
                        <i class="ti ti-file-spreadsheet me-1 fs-3"></i> Excel'e Aktar
                    </button>
                    <button type="button" class="btn btn-outline-secondary shadow-sm px-3" id="btn-print-list">
                        <i class="ti ti-printer me-1 fs-3"></i> Yazdır
                    </button>
                    <button type="button" class="btn btn-primary shadow-sm px-3" id="btn-open-add-modal">
                        <i class="ti ti-plus me-1 fs-3"></i> Yeni İcra Dosyası
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- İstatistik Kartları (KPI) -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-primary text-white avatar">
                                <i class="ti ti-files icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium" id="kpi-total-files">
                                <?= number_format($stats['total_files']); ?>
                            </div>
                            <div class="text-secondary">
                                Toplam Dosya
                                <div class="small text-muted mt-1">Borç: <strong id="kpi-total-debt"><?= Helper::formattedMoney($stats['total_debt']); ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-green text-white avatar">
                                <i class="ti ti-scissors icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium" id="kpi-active-files">
                                <?= number_format($stats['active_files']); ?>
                            </div>
                            <div class="text-secondary">
                                Kesilen (Aktif) Dosyalar
                                <div class="small text-muted mt-1">Mevcut Kesinti Yapılanlar</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-warning text-white avatar">
                                <i class="ti ti-clock icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium" id="kpi-pending-files">
                                <?= number_format($stats['pending_files']); ?>
                            </div>
                            <div class="text-secondary">
                                Bekleyen Dosyalar
                                <div class="small text-muted mt-1">Sırada / Bekleyen</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-danger text-white avatar">
                                <i class="ti ti-wallet icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium" id="kpi-remaining-debt">
                                <?= Helper::formattedMoney($stats['remaining_debt']); ?>
                            </div>
                            <div class="text-secondary">
                                Kalan Toplam Borç
                                <div class="small text-muted mt-1">Kesilen: <strong class="text-success" id="kpi-total-deductions"><?= Helper::formattedMoney($stats['total_deductions']); ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtre Kartı ve Tablo Container -->
    <div class="card shadow-sm border-0 rounded-3">
        <div class="card-header py-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title font-weight-700 mb-0">
                <i class="ti ti-list-check me-2 text-primary"></i>İcra Dosyaları
            </h3>

            <!-- Sağ Taraf: Durum Çoklu Seçim Filtresi (Select2 Multiple) -->
            <div style="min-width: 300px; width: 420px; max-width: 100%;" class="ms-auto">
                <select class="form-select select2-init" id="icra-status-multiselect" multiple="multiple" data-placeholder="Durum Seçiniz...">
                    <?php foreach ($statuses as $key => $stInfo): ?>
                        <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>" <?= $key === 'Kesilen' ? 'selected' : ''; ?>>
                            <?= htmlspecialchars($stInfo['title'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped table-hover align-middle w-100" id="icraMainTable">
                    <thead class="bg-light">
                        <tr>
                            <th class="w-1 text-center">#</th>
                            <th>Personel</th>
                            <th class="text-center">Sıra</th>
                            <th>İcra Dairesi</th>
                            <th>Dosya No</th>
                            <th>Alacaklı</th>
                            <th class="text-end">Toplam Borç</th>
                            <th class="text-center">Kesinti Yöntemi</th>
                            <th class="text-end">Yapılan Kesinti</th>
                            <th class="text-end">Kalan Borç</th>
                            <th class="text-center">Durum</th>
                            <th class="text-center w-1">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- DataTables AJAX / JS ile yüklenecek -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- İcra Dosyası Ekle / Düzenle Modal -->
<div class="modal modal-blur fade" id="icraFileModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <form id="form-icra-file" enctype="multipart/form-data">
                <input type="hidden" name="id" id="icra-file-id" value="">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-700 text-white" id="modal-icra-title">
                        <i class="ti ti-file-plus me-2"></i>Yeni İcra Dosyası Ekle
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body py-3">
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label required font-weight-600">Personel</label>
                            <select class="form-select" name="person_id" id="modal-person-id" required style="width: 100%;">
                                <option value="">Personel Seçiniz...</option>
                                <?php foreach ($firmPersons as $p): ?>
                                    <?php $decryptedKimlik = Security::safeDecrypt($p->kimlik_no ?? ''); ?>
                                    <option value="<?= Security::encrypt($p->id); ?>" data-person-id="<?= (int)$p->id; ?>" data-fullname="<?= htmlspecialchars($p->full_name ?? '', ENT_QUOTES, 'UTF-8'); ?>" data-tc="<?= htmlspecialchars($decryptedKimlik ?: 'TC Yok', ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($p->full_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required font-weight-600">İcra Sırası</label>
                            <input type="number" class="form-control" name="icra_sirasi" id="modal-icra-sirasi" value="1" min="1" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label required font-weight-600">İcra Dairesi</label>
                            <input type="text" class="form-control" name="icra_dairesi" id="modal-icra-dairesi" list="icra-daireleri-list" placeholder="Örn: İstanbul 3. İcra Dairesi" required autocomplete="off">
                            <datalist id="icra-daireleri-list">
                                <?php foreach ($icraDaireleri as $daire): ?>
                                    <option value="<?= htmlspecialchars($daire, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required font-weight-600">Dosya No</label>
                            <input type="text" class="form-control" name="dosya_no" id="modal-dosya-no" placeholder="Örn: 2026/1234 Esas" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required font-weight-600">Alacaklı / Avukat</label>
                            <input type="text" class="form-control" name="alacakli" id="modal-alacakli" placeholder="Alacaklı kişi veya kurum adı" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required font-weight-600">Toplam Borç Tutarı (₺)</label>
                            <input type="text" class="form-control money-input" name="toplam_borc" id="modal-toplam-borc" placeholder="0,00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required font-weight-600">Durum</label>
                            <select class="form-select select2-modal" name="durum" id="modal-durum" required style="width:100%;">
                                <?php foreach ($statuses as $key => $stInfo): ?>
                                    <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>">
                                        <?= htmlspecialchars($stInfo['title'], ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required font-weight-600">Kesinti Yöntemi</label>
                            <select class="form-select select2-modal" name="kesinti_yontemi" id="modal-kesinti-yontemi" style="width:100%;">
                                <option value="oran">Maaş Oranı (%25, 1/4 vb.)</option>
                                <option value="sabit">Sabit Tutar (₺)</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="wrapper-kesinti-orani">
                            <label class="form-label font-weight-600">Kesinti Oranı</label>
                            <input type="text" class="form-control" name="kesinti_orani" id="modal-kesinti-orani" value="1/4" placeholder="Örn: %25 veya 1/4">
                        </div>
                        <div class="col-md-6 d-none" id="wrapper-kesinti-tutari">
                            <label class="form-label font-weight-600">Aylık Sabit Kesinti Tutarı (₺)</label>
                            <input type="text" class="form-control money-input" name="kesinti_tutari" id="modal-kesinti-tutari" placeholder="0,00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-600">Başlama Tarihi</label>
                            <input type="text" class="form-control flatpickr-input" name="baslama_tarihi" id="modal-baslama-tarihi" placeholder="YYYY-AA-GG">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-600">Bitiş Tarihi</label>
                            <input type="text" class="form-control flatpickr-input" name="bitis_tarihi" id="modal-bitis-tarihi" placeholder="YYYY-AA-GG">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-600">Gelen Evrak No/Tarih</label>
                            <input type="text" class="form-control" name="gelen_evrak" id="modal-gelen-evrak" placeholder="Örn: 12345 / 15.01.2026">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-weight-600">Giden Evrak No/Tarih</label>
                            <input type="text" class="form-control" name="giden_evrak" id="modal-giden-evrak" placeholder="Örn: Cevap Yazısı 54321">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label font-weight-600">Açıklama / Notlar</label>
                            <textarea class="form-control" name="aciklama" id="modal-aciklama" rows="2" placeholder="Varsa ek notlar..."></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label font-weight-600">İcra Belgesi (PDF / Resim / Doküman)</label>
                            <input type="file" class="form-control" name="belge_dosyasi" id="modal-belge-dosyasi" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx">
                            <div class="form-text text-muted small">Maksimum 5MB. Yeni dosya seçerseniz mevcut belge güncellenir.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary px-4" id="btn-save-icra">
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
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-700 text-white" id="modal-deductions-title">
                    <i class="ti ti-history me-2"></i>İcra Kesintileri Geçmişi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted text-uppercase tracking-wide font-weight-600">Personel / Dosya</div>
                        <div class="font-weight-700" id="modal-deductions-person-name">-</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted text-uppercase tracking-wide font-weight-600">Toplam Kesilen</div>
                        <div class="h3 mb-0 text-success font-weight-700" id="modal-deductions-total">0,00 ₺</div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-vcenter table-striped table-sm mb-0">
                        <thead class="sticky-top">
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
            <div class="modal-footer py-2 d-flex justify-content-between">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-sm me-1" id="btn-modal-print-deductions">
                        <i class="ti ti-printer me-1"></i> Yazdır
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm me-1" id="btn-modal-excel-deductions">
                        <i class="ti ti-file-spreadsheet me-1"></i> Excel'e İndir
                    </button>
                </div>
                <button type="button" class="btn btn-secondary px-4 ms-auto" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let icraDataTable = null;

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
        $('#icra-status-multiselect').select2({
            theme: 'bootstrap-5',
            width: '100%',
            closeOnSelect: false,
            placeholder: 'Durum Seçiniz...',
            dropdownParent: $('body'),
            templateResult: function(state) {
                if (!state.id) return state.text;
                const isSelected = $(state.element).is(':selected');
                if (isSelected) {
                    return $('<div class="d-flex align-items-center justify-content-between text-primary font-weight-700 w-100 py-1"><span>' + state.text + '</span><i class="ti ti-check fs-2 text-primary"></i></div>');
                } else {
                    return $('<div class="d-flex align-items-center justify-content-between w-100 py-1"><span>' + state.text + '</span></div>');
                }
            },
            templateSelection: function(state) {
                if (!state.id) return state.text;
                return $('<span class="d-inline-flex align-items-center"><i class="ti ti-check me-1 text-primary"></i>' + state.text + '</span>');
            }
        });

        $('#icra-status-multiselect').on('select2:select select2:unselect', function (e) {
            setTimeout(function() {
                $('.select2-results__option').each(function() {
                    const data = $(this).data('data');
                    if (data && data.element) {
                        const isSelected = $(data.element).is(':selected');
                        if (isSelected) {
                            $(this).addClass('select2-results__option--selected');
                            if ($(this).find('.ti-check').length === 0) {
                                $(this).find('> div').addClass('text-primary font-weight-700').append('<i class="ti ti-check fs-2 text-primary"></i>');
                            }
                        } else {
                            $(this).removeClass('select2-results__option--selected');
                            $(this).find('.ti-check').remove();
                            $(this).find('> div').removeClass('text-primary font-weight-700');
                        }
                    }
                });
            }, 10);
        });

        $('.select2-modal').select2({
            theme: 'bootstrap-5',
            width: '100%',
            dropdownParent: $('#icraFileModal')
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
                if (!tc) return state.text;
                return $(
                    '<div style="line-height: 1.15; padding: 1px 0;">' +
                        '<div class="fw-bold text-dark" style="font-size: 0.85rem;">' + fullname + '</div>' +
                        '<div class="text-muted" style="font-size: 0.75rem; margin-top: 1px;">TC: ' + tc + '</div>' +
                    '</div>'
                );
            },
            templateSelection: function(state) {
                if (!state.id) return state.text;
                const elem = $(state.element);
                const fullname = elem.data('fullname') || state.text;
                const tc = elem.data('tc');
                if (!tc) return state.text;
                return $(
                    '<div class="d-inline-flex align-items-center gap-2">' +
                        '<span class="fw-bold">' + fullname + '</span>' +
                        '<span class="badge bg-secondary-lt text-secondary" style="font-size: 0.72rem;">TC: ' + tc + '</span>' +
                    '</div>'
                );
            }
        });
    }

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

    // 4. Veri Yükleme & DataTable Kurulumu
    function loadIcraTable() {
        const selectedStatuses = $('#icra-status-multiselect').val() || [];

        $.ajax({
            url: 'api/persons/icra.php',
            type: 'POST',
            data: {
                action: 'firm_list',
                status_filter: selectedStatuses
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
                                    <a href="api/persons/icra.php?action=download&id=${f.id}" class="btn btn-sm btn-icon btn-ghost-primary me-1" title="Evrak İndir" target="_blank">
                                        <i class="ti ti-download fs-2 text-primary"></i>
                                    </a>
                                `;
                            }

                            let fileJsonData = encodeURIComponent(JSON.stringify(f));

                            let tr = `
                                <tr>
                                    <td class="text-center font-weight-600 text-secondary">${idx + 1}</td>
                                    <td>
                                        <a href="index.php?p=persons/manage&id=${f.person_id}&tab=icra" class="font-weight-700 text-reset text-decoration-none hover-primary">
                                            ${f.person_name}
                                        </a>
                                        <div class="small text-muted">TC: ${f.person_tc || '-'} | Sicil: ${f.person_sicil || '-'}</div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-blue-lt text-blue">${f.icra_sirasi}. Sıra</span>
                                    </td>
                                    <td class="icra-dairesi-cell">${f.icra_dairesi}</td>
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
                                    <td class="text-center">
                                        <div class="d-inline-flex gap-1">
                                            ${fileBtn}
                                            <button type="button" class="btn btn-sm btn-icon btn-ghost-primary btn-edit-icra me-1" data-file="${fileJsonData}" title="Düzenle">
                                                <i class="ti ti-edit fs-2"></i>
                                            </button>
                                            <a href="index.php?p=persons/manage&id=${f.person_id}&tab=icra" class="btn btn-sm btn-icon btn-ghost-secondary me-1" title="Personel Detayı">
                                                <i class="ti ti-external-link fs-2"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-icon btn-ghost-danger btn-delete-icra" data-id="${f.id}" title="Sil">
                                                <i class="ti ti-trash fs-2"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            tbody.append(tr);
                        });
                    }

                    // window.createDataTable kullanarak DataTable oluştur
                    if (typeof window.createDataTable === 'function') {
                        icraDataTable = window.createDataTable('#icraMainTable', {
                            pageLength: 25,
                            order: [[1, 'asc']]
                        });
                    } else if ($.fn.DataTable) {
                        icraDataTable = $('#icraMainTable').DataTable({
                            pageLength: 25,
                            language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json' }
                        });
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

    // 5. Durum Multi-Select Değişimi
    $('#icra-status-multiselect').on('change', function() {
        loadIcraTable();
    });

    // 6. Yeni İcra Dosyası Modal Açma
    $('#btn-open-add-modal').on('click', function() {
        $('#form-icra-file')[0].reset();
        $('#icra-file-id').val('');
        $('#modal-icra-title').html('<i class="ti ti-file-plus me-2"></i>Yeni İcra Dosyası Ekle');
        $('#modal-person-id').val('').trigger('change');
        $('#modal-durum').val('Kesilen').trigger('change');
        $('#modal-kesinti-yontemi').val('oran').trigger('change');
        $('#icraFileModal').modal('show');
    });

    // 7. Düzenle (Edit) Modal Açma
    $(document).on('click', '.btn-edit-icra', function() {
        const rawData = $(this).attr('data-file');
        if (!rawData) return;

        try {
            const f = JSON.parse(decodeURIComponent(rawData));

            $('#form-icra-file')[0].reset();
            $('#icra-file-id').val(f.id);
            $('#modal-icra-title').html('<i class="ti ti-edit me-2"></i>İcra Dosyası Güncelle');

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

            $('#icraFileModal').modal('show');
        } catch(e) {
            console.error("Error parsing icra file JSON:", e);
        }
    });

    // 8. Kaydetme Form Submit (Ekle & Güncelle)
    $('#form-icra-file').on('submit', function(e) {
        e.preventDefault();
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
                if (res.status === 'success') {
                    $('#icraFileModal').modal('hide');
                    Swal.fire('Başarılı!', res.message || 'İcra dosyası kaydedildi.', 'success');
                    loadIcraTable();
                } else {
                    Swal.fire('Hata!', res.message || 'Kaydedilemedi.', 'error');
                }
            },
            error: function(err) {
                Swal.fire('Hata!', 'Sunucu hatası oluştu.', 'error');
            }
        });
    });

    // 9. Silme İşlemi (SweetAlert2)
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

    // 10. Kesintiler Geçmişi Detayı Tıklama
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

    // 11. Modal ve Liste İndirme / Yazdırma Butonları
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
        const selectedStatuses = $('#icra-status-multiselect').val() || [];
        window.location.href = 'pages/persons/icra-export-xls.php?status_filter=' + encodeURIComponent(selectedStatuses.join(','));
    });

    $('#btn-print-list').on('click', function() {
        const selectedStatuses = $('#icra-status-multiselect').val() || [];
        window.open('print_icra.php?status_filter=' + encodeURIComponent(selectedStatuses.join(',')), '_blank');
    });
});
</script>
