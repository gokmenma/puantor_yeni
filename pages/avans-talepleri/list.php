<?php
require_once ROOT . "/Model/AdvanceRequest.php";
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\Date;

// Kullanıcının firmasını kontrol eder
$Auths->checkFirmReturn();

// Yetki kontrolü - avans_talepleri yetkisine bağlı
$perm->checkAuthorize("avans_talepleri");

$firm_id = (int)$_SESSION["firm_id"];
$advanceModel = new AdvanceRequest();
$requests = $advanceModel->getRequestsByFirm($firm_id);
$stats = $advanceModel->getStats($firm_id);

$personsModel = new Persons();
$persons = $personsModel->getPersonsByFirm($firm_id);
?>
<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'avans-summary-collapsed',
            localStorage.getItem('avans_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.avans-summary-collapsed #avansSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="avansPage">

    <!-- Sayfa Başlığı (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-cash" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Avans Talepleri
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Personel avans talepleri, onay süreçleri ve kesinti takibi
                        </div>
                    </div>
                </div>
            </div>
            <!-- Birincil Aksiyonlar -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Sütunlar -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon avans-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-layout-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="avansColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkbox'lar JS tarafından dinamik oluşturulur -->
                        </div>
                    </div>

                    <!-- Yeni Avans Ekle Butonu -->
                    <button type="button" class="btn btn-sm btn-dark shadow-sm avans-header-action" data-bs-toggle="modal" data-bs-target="#addAvansModal" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-plus me-1"></i> Yeni Avans Ekle
                    </button>

                    <!-- İşlemler Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle avans-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="pages/avans-talepleri/to-xls.php" target="_blank" class="dropdown-item">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <div class="dropdown-divider"></div>
                            <a href="javascript:void(0)" onclick="location.reload();" class="dropdown-item">
                                <i class="ti ti-refresh icon me-2 text-secondary"></i> Sayfayı Yenile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="avansSummaryCards">
        <!-- Kart 1: Toplam Talep -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border avans-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM TALEP</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-receipt" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats->total_count ?? 0, 0, ',', '.') ?> Adet
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Tutar: <strong class="text-dark"><?= Helper::formattedMoney($stats->total_amount ?? 0) ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm talepleri göster">
                            <input type="radio" name="avans_status_filter" value="" class="status-filter" checked>
                            <span><i class="ti ti-list"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Bekleyen Talepler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border avans-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">BEKLEYEN TALEPLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-clock" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats->pending_count ?? 0, 0, ',', '.') ?> Adet
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Tutar: <strong class="text-warning"><?= Helper::formattedMoney($stats->pending_amount ?? 0) ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Bekleyen talepleri göster">
                            <input type="radio" name="avans_status_filter" value="Beklemede" class="status-filter">
                            <span><i class="ti ti-clock text-warning"></i> Bekleyen</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Onaylanan Talepler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border avans-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ONAYLANAN TALEPLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-check" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($stats->approved_amount ?? 0) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Adet: <strong class="text-success"><?= number_format($stats->approved_count ?? 0, 0, ',', '.') ?> Talep</strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Onaylanan talepleri göster">
                            <input type="radio" name="avans_status_filter" value="Onaylandı" class="status-filter">
                            <span><i class="ti ti-check text-success"></i> Onaylanan</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Reddedilen Talepler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border avans-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">REDDEDİLEN TALEPLER</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-x" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($stats->rejected_count ?? 0, 0, ',', '.') ?> Adet
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Tutar: <strong class="text-danger"><?= Helper::formattedMoney($stats->rejected_amount ?? 0) ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Reddedilen talepleri göster">
                            <input type="radio" name="avans_status_filter" value="Reddedildi" class="status-filter">
                            <span><i class="ti ti-x text-danger"></i> Reddedilen</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ana Kart ve Tablo -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card avans-table-card" style="border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-list text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Avans Talepleri Listesi</h4>
                                <a href="javascript:void(0)" class="btn-card-header-add" data-bs-toggle="modal" data-bs-target="#addAvansModal" data-tooltip="Yeni Avans Ekle">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Personel avans talepleri, onay durumları ve kesinti takibi</p>
                        </div>
                    </div>

                    <!-- Arama & Daraltma Butonları -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Hızlı Genel Arama -->
                        <div class="input-icon avans-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="avans-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="avans-search-clear" class="avans-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <!-- Özet Kartları Aç/Kapat Butonu -->
                        <button type="button" id="toggleAvansSummary" class="btn btn-sm btn-outline-secondary btn-icon avans-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Tablo Kapsayıcısı -->
                <div class="table-responsive avans-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="advanceTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>Personel</th>
                                <th class="text-end">Tutar</th>
                                <th class="text-center">Hedef Dönem</th>
                                <th>Açıklama</th>
                                <th class="text-center">Talep Tarihi</th>
                                <th class="text-center">İşlem Tarihi</th>
                                <th class="text-center">Durum</th>
                                <th class="text-end no-export" style="width: 110px;" data-orderable="false">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $index => $req): 
                                $status_badge = '';
                                $status_text = 'Beklemede';
                                if ($req->durum == 0) {
                                    $status_badge = '<span class="badge bg-warning-lt">Beklemede</span>';
                                    $status_text = 'Beklemede';
                                } elseif ($req->durum == 1) {
                                    $status_badge = '<span class="badge bg-success-lt">Onaylandı</span>';
                                    $status_text = 'Onaylandı';
                                } elseif ($req->durum == 2) {
                                    $status_badge = '<span class="badge bg-danger-lt">Reddedildi</span>';
                                    $status_text = 'Reddedildi';
                                }
                                $islem_tarihi_formatted = ($req->durum != 0 && !empty($req->formatted_updated_at)) ? $req->formatted_updated_at : '-';
                                $donem_text = Date::monthName($req->hedef_ay) . ' ' . $req->hedef_yil;
                                $encrypted_id = Security::encrypt($req->id);
                            ?>
                                <tr class="advance-row"
                                    data-id="<?= (int)$req->id; ?>"
                                    data-encrypted-id="<?= $encrypted_id; ?>"
                                    data-durum="<?= (int)$req->durum; ?>"
                                    data-personel="<?= htmlspecialchars($req->full_name, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-tutar="<?= Helper::formattedMoney($req->tutar); ?>">
                                    <td class="text-center font-weight-medium text-muted"><?= (int)$req->id; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-xs me-2 bg-blue-lt rounded-circle">
                                                <i class="ti ti-user"></i>
                                            </div>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($req->full_name, ENT_QUOTES, 'UTF-8'); ?></div>
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold text-dark"><?= Helper::formattedMoney($req->tutar); ?></td>
                                    <td class="text-center"><?= htmlspecialchars($donem_text, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="text-truncate d-inline-block clickable-desc view-detail" style="max-width: 220px;" 
                                              title="Detayı görüntülemek için tıklayın: <?= htmlspecialchars($req->aciklama ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-id="<?= (int)$req->id; ?>"
                                              data-personel="<?= htmlspecialchars($req->full_name, ENT_QUOTES, 'UTF-8'); ?>"
                                              data-tutar="<?= Helper::formattedMoney($req->tutar); ?>"
                                              data-donem="<?= htmlspecialchars($donem_text, ENT_QUOTES, 'UTF-8'); ?>"
                                              data-talep-tarihi="<?= htmlspecialchars($req->formatted_date ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-islem-tarihi="<?= htmlspecialchars($islem_tarihi_formatted, ENT_QUOTES, 'UTF-8'); ?>"
                                              data-durum="<?= (int)$req->durum; ?>"
                                              data-islem-yapan="<?= htmlspecialchars($req->processed_by_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-admin-note="<?= htmlspecialchars($req->admin_note ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-aciklama="<?= htmlspecialchars($req->aciklama ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?= htmlspecialchars($req->aciklama ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td class="text-center small text-muted"><?= htmlspecialchars($req->formatted_date ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center small text-muted"><?= htmlspecialchars($islem_tarihi_formatted, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center" data-filter="<?= $status_text; ?>"><?= $status_badge; ?></td>
                                    <td class="text-end actions-column">
                                        <div class="d-inline-flex justify-content-end align-items-center gap-1">
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary view-detail" 
                                                    data-tooltip="Detay"
                                                    data-id="<?= (int)$req->id; ?>"
                                                    data-personel="<?= htmlspecialchars($req->full_name, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-tutar="<?= Helper::formattedMoney($req->tutar); ?>"
                                                    data-donem="<?= htmlspecialchars($donem_text, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-talep-tarihi="<?= htmlspecialchars($req->formatted_date ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-islem-tarihi="<?= htmlspecialchars($islem_tarihi_formatted, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-durum="<?= (int)$req->durum; ?>"
                                                    data-islem-yapan="<?= htmlspecialchars($req->processed_by_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-admin-note="<?= htmlspecialchars($req->admin_note ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-aciklama="<?= htmlspecialchars($req->aciklama ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                            <?php if ($req->durum == 0): ?>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-success update-status" 
                                                        data-tooltip="Onayla"
                                                        data-id="<?= $encrypted_id; ?>" 
                                                        data-status="1">
                                                    <i class="ti ti-check"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-warning update-status" 
                                                        data-tooltip="Reddet"
                                                        data-id="<?= $encrypted_id; ?>" 
                                                        data-status="2">
                                                    <i class="ti ti-x"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-request" 
                                                        data-tooltip="Sil"
                                                        data-id="<?= $encrypted_id; ?>">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            <?php elseif ($req->durum == 1 && $perm->hasPermission("onayli_avanslarda_islem_yap")): ?>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-request" 
                                                        data-tooltip="Sil"
                                                        data-id="<?= $encrypted_id; ?>">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            <?php endif; ?>
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

<!-- Avans Ekle Modal -->
<div class="modal modal-blur fade" id="addAvansModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title font-weight-700 d-flex align-items-center gap-2">
                    <i class="ti ti-cash text-primary"></i>
                    Personele Avans Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <form id="addAvansForm">
                    <div class="mb-3">
                        <label class="form-label required font-weight-600">Personel</label>
                        <select class="form-select" name="person_id" id="avans_person_id" required style="width: 100%;">
                            <option value="">Personel Seçiniz...</option>
                            <?php foreach ($persons as $person): ?>
                                <?php $decryptedKimlik = Security::safeDecrypt($person->kimlik_no ?? ''); ?>
                                <option value="<?= Security::encrypt($person->id); ?>" 
                                        data-person-id="<?= (int)$person->id; ?>" 
                                        data-fullname="<?= htmlspecialchars($person->full_name, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-tc="<?= htmlspecialchars($decryptedKimlik ?: 'TC Yok', ENT_QUOTES, 'UTF-8'); ?>">
                                    <?= htmlspecialchars($person->full_name, ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required font-weight-600">Tutar (₺)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="tutar" id="avans_tutar" placeholder="0.00" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label required font-weight-600">Hedef Ay</label>
                                <select class="form-select" name="hedef_ay" id="avans_hedef_ay" required style="width: 100%;">
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?= $m; ?>" <?= $m == date('n') ? 'selected' : ''; ?>>
                                             <?= Date::monthName($m); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label required font-weight-600">Hedef Yıl</label>
                                <select class="form-select" name="hedef_yil" id="avans_hedef_yil" required style="width: 100%;">
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?= $y; ?>" <?= $y == date('Y') ? 'selected' : ''; ?>>
                                            <?= $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label font-weight-600">Açıklama</label>
                        <textarea class="form-control" name="aciklama" id="avans_aciklama" rows="2" placeholder="Açıklama giriniz..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="saveAvansBtn">
                    <i class="ti ti-device-floppy me-1"></i>
                    Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Avans Detay Modal -->
<div class="modal modal-blur fade" id="detailAvansModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title font-weight-700 d-flex align-items-center gap-2">
                    <i class="ti ti-info-circle text-primary"></i>
                    Avans Talebi Detayı
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <div class="card mb-3 bg-body-tertiary border-0 shadow-none">
                    <div class="card-body p-3">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-muted small" style="font-size: 11.5px;">Personel</div>
                                <div class="fw-bold fs-4 text-dark" id="detail_personel">-</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small" style="font-size: 11.5px;">Talep Tutarı</div>
                                <div class="fw-bold fs-4 text-primary" id="detail_tutar">-</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small" style="font-size: 11.5px;">Hedef Dönem</div>
                                <div class="fw-medium text-dark" id="detail_donem">-</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small" style="font-size: 11.5px;">Durum</div>
                                <div id="detail_durum">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="card card-sm shadow-none border">
                            <div class="card-body p-2">
                                <div class="text-muted small mb-1 d-flex align-items-center gap-1" style="font-size: 11px;">
                                    <i class="ti ti-calendar"></i>
                                    Talep Tarihi
                                </div>
                                <div class="fw-bold text-dark small" id="detail_talep_tarihi">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-sm shadow-none border">
                            <div class="card-body p-2">
                                <div class="text-muted small mb-1 d-flex align-items-center gap-1" style="font-size: 11px;">
                                    <i class="ti ti-clock"></i>
                                    İşlem / Onay Tarihi
                                </div>
                                <div class="fw-bold text-dark small" id="detail_islem_tarihi">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1 d-flex align-items-center gap-1" style="font-size: 11.5px;">
                        <i class="ti ti-user-check"></i>
                        İşlemi Yapan (Onaylayan / Reddeden)
                    </div>
                    <div class="p-2 border rounded bg-light fw-medium text-dark small" id="detail_islem_yapan">-</div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1 d-flex align-items-center gap-1" style="font-size: 11.5px;">
                        <i class="ti ti-file-text"></i>
                        Talep Açıklaması
                    </div>
                    <div class="p-2 border rounded bg-body text-dark small" id="detail_aciklama" style="white-space: pre-wrap; word-break: break-word; min-height: 50px; max-height: 150px; overflow-y: auto;">-</div>
                </div>

                <div id="detail_admin_note_wrapper" class="d-none">
                    <div class="text-muted small mb-1 d-flex align-items-center gap-1" style="font-size: 11.5px;">
                        <i class="ti ti-notes text-warning"></i>
                        Yönetici Notu / Red Gerekçesi
                    </div>
                    <div class="p-2 border rounded bg-warning-lt text-dark small" id="detail_admin_note" style="white-space: pre-wrap; word-break: break-word;">-</div>
                </div>
            </div>
            <div class="modal-footer py-2 px-3">
                <button type="button" class="btn btn-secondary ms-auto" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
.avans-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.avans-header-icon-action,
.avans-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.avans-header-icon-action i,
.avans-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#avansPage .avans-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#avansSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#avansPage .avans-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
}

.avans-table-card > .avans-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.avans-table-card > .card-header {
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

.avans-search-wrap { position: relative; }
#avans-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.avans-search-wrap,
.avans-search-wrap.input-icon {
    height: 32px !important;
}
.avans-search-clear {
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
.avans-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#advanceTable_wrapper,
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

/* Tablo Bütünleşik Çerçeve Standardı */
table#advanceTable.data-table,
table#advanceTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#advanceTable.data-table tbody,
table#advanceTable.dataTable tbody,
table#advanceTable.data-table tbody tr:last-child,
table#advanceTable.dataTable tbody tr:last-child,
#advanceTable_wrapper .dt-layout-table,
#advanceTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#advanceTable.data-table thead th {
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
table#advanceTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Sütun Başlığı İçi Filtre Butonu ve Düzeni */
table#advanceTable.data-table thead th .dt-header-content {
    min-height: 24px;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#advanceTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#advanceTable.data-table td.actions-column .btn.btn-sm.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
table#advanceTable.data-table td.actions-column .btn.btn-sm.btn-icon i {
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    font-size: 13px !important;
}
table#advanceTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#advanceTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#advanceTable.dataTable > tbody > tr:last-child > *,
table#advanceTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#advanceTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

.clickable-desc {
    cursor: pointer;
    transition: color 0.15s ease;
}
.clickable-desc:hover {
    color: #206bc4;
    text-decoration: underline;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#advanceTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child,
div#advanceTable_wrapper .dt-layout-row:has(.dt-paging),
div#advanceTable_wrapper .dt-layout-row:has(.dt-info) {
    margin: 0 !important;
    margin-top: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
    position: static !important;
    flex-shrink: 0 !important;
}

/* Dark Mode */
[data-bs-theme="dark"] table#advanceTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#advanceTable.data-table,
[data-bs-theme="dark"] table#advanceTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#advanceTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#advanceTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#advanceTable.data-table tbody tr:hover td {
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
[data-bs-theme="dark"] .avans-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .avans-summary-card,
[data-bs-theme="dark"] .avans-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

#advanceTable tbody tr.context-menu-active {
    background-color: rgba(32, 107, 196, 0.08) !important;
}
[data-bs-theme="dark"] #advanceTable tbody tr.context-menu-active {
    background-color: rgba(59, 130, 246, 0.16) !important;
}

[data-bs-theme="dark"] .custom-context-menu {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5), 0 2px 8px rgba(0,0,0,0.3) !important;
}
[data-bs-theme="dark"] .custom-context-menu .cm-header {
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
}
[data-bs-theme="dark"] .custom-context-menu a,
[data-bs-theme="dark"] .custom-context-menu button {
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] .custom-context-menu a:hover,
[data-bs-theme="dark"] .custom-context-menu button:hover {
    background: #1e293b !important;
    color: #60a5fa !important;
}
[data-bs-theme="dark"] .custom-context-menu .cm-divider {
    background: #334155 !important;
}
[data-bs-theme="dark"] .custom-context-menu a.cm-danger:hover,
[data-bs-theme="dark"] .custom-context-menu button.cm-danger:hover {
    background: rgba(225, 29, 72, 0.15) !important;
    color: #f43f5e !important;
}
</style>

<script>
$(document).ready(function() {
    var $summaryToggle = $('#toggleAvansSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('avans-summary-collapsed');
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
        var isCollapsed = document.documentElement.classList.toggle('avans-summary-collapsed');
        try {
            localStorage.setItem('avans_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    var advanceDataTable = null;

    if (typeof window.createDataTable === 'function') {
        advanceDataTable = window.createDataTable('#advanceTable', {
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            columnDefs: [
                { targets: 0, className: 'text-center' },
                { targets: 2, className: 'text-end' },
                { targets: [3, 5, 6, 7], className: 'text-center' },
                { targets: 8, orderable: false, searchable: false, className: 'text-end no-export actions-column' }
            ],
            initComplete: function() {
                var api = this.api();
                if (typeof window.initDataTableColumnFilters === 'function') {
                    window.initDataTableColumnFilters($('#advanceTable'), api);
                }
            }
        });
    } else if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#advanceTable')) {
        advanceDataTable = $('#advanceTable').DataTable({
            order: [[0, 'desc']],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                url: 'src/tr.json'
            },
            columnDefs: [
                { targets: 0, className: 'text-center' },
                { targets: 2, className: 'text-end' },
                { targets: [3, 5, 6, 7], className: 'text-center' },
                { targets: 8, orderable: false, searchable: false, className: 'text-end no-export actions-column' }
            ],
            initComplete: function() {
                var api = this.api();
                if (typeof window.initDataTableColumnFilters === 'function') {
                    window.initDataTableColumnFilters($('#advanceTable'), api);
                }
            }
        });
    }

    // Sütunların varsayılan durumları ve etiketleri
    var columnConfig = {
        0: { label: '# ID', default: true },
        1: { label: 'Personel', default: true },
        2: { label: 'Tutar', default: true },
        3: { label: 'Hedef Dönem', default: true },
        4: { label: 'Açıklama', default: true },
        5: { label: 'Talep Tarihi', default: true },
        6: { label: 'İşlem Tarihi', default: true },
        7: { label: 'Durum', default: true }
    };

    if (advanceDataTable) {
        var savedVisibility = localStorage.getItem('avans_column_visibility');
        var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};

        var menuHtml = '';
        $.each(columnConfig, function(idx, conf) {
            var isVisible = visibilityState.hasOwnProperty(idx) ? visibilityState[idx] : conf.default;
            advanceDataTable.column(idx).visible(isVisible, false);

            menuHtml += `
                <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size: 0.85rem;">
                    <div class="form-check mb-0 w-100">
                        <input class="form-check-input avans-col-trigger" type="checkbox" id="colCheck_avans_${idx}" data-column="${idx}" ${isVisible ? "checked" : ""}>
                        <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">
                            ${conf.label}
                        </span>
                    </div>
                </label>`;
        });

        $('#avansColvisMenu').html(menuHtml);
        advanceDataTable.columns.adjust();

        $(document).on('change', '.avans-col-trigger', function() {
            var colIdx = parseInt($(this).data('column'));
            var isChecked = this.checked;
            advanceDataTable.column(colIdx).visible(isChecked);
            visibilityState[colIdx] = isChecked;
            localStorage.setItem('avans_column_visibility', JSON.stringify(visibilityState));
        });

        $(document).on('click', '#avansColvisMenu', function(e) {
            e.stopPropagation();
        });
    }

    // Durum Filtresi (Summary Card radio pills)
    $('input[name="avans_status_filter"]').on('change', function() {
        var val = $(this).val();
        if (advanceDataTable) {
            advanceDataTable.column(7).search(val ? '^' + val + '$' : '', true, false).draw();
        }
    });

    // Hızlı Genel Arama Inputu
    var searchTimer = null;
    $('#avans-fast-search').on('input', function() {
        var val = this.value;
        $('#avans-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (advanceDataTable) {
                advanceDataTable.search(val).draw();
            }
        }, 300);
    });

    $('#avans-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#avans-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        if (advanceDataTable) {
            advanceDataTable.search('').draw();
        }
    });

    // Detay Modal Açma
    $(document).on('click', '.view-detail', function() {
        var $el = $(this);
        var id = $el.data('id');
        var personel = $el.data('personel');
        var tutar = $el.data('tutar');
        var donem = $el.data('donem');
        var talepTarihi = $el.data('talep-tarihi');
        var islemTarihi = $el.data('islem-tarihi');
        var durum = parseInt($el.data('durum'));
        var islemYapan = $el.data('islem-yapan');
        var aciklama = $el.data('aciklama');
        var adminNote = $el.data('admin-note');

        $('#detail_personel').text(personel || '-');
        $('#detail_tutar').text(tutar || '-');
        $('#detail_donem').text(donem || '-');
        $('#detail_talep_tarihi').text(talepTarihi || '-');

        renderDetailStatus(durum, islemTarihi, islemYapan);
        $('#detail_aciklama').text(aciklama && aciklama.trim() !== '' ? aciklama : 'Açıklama girilmemiş.');

        if (adminNote && adminNote.trim() !== '') {
            $('#detail_admin_note').text(adminNote);
            $('#detail_admin_note_wrapper').removeClass('d-none');
        } else {
            $('#detail_admin_note_wrapper').addClass('d-none');
        }

        $('#detailAvansModal').modal('show');

        // En güncel veriyi AJAX ile çekelim
        if (id) {
            $.ajax({
                url: 'api/advances/advances.php',
                type: 'GET',
                data: { action: 'get_detail', id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.status === 'success' && res.detail) {
                        var d = res.detail;
                        $('#detail_personel').text(d.full_name || personel);
                        if (d.tutar) {
                            $('#detail_tutar').text(parseFloat(d.tutar).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ₺');
                        }
                        if (d.formatted_date) {
                            $('#detail_talep_tarihi').text(d.formatted_date);
                        }
                        var updatedDate = (d.durum != 0 && d.formatted_updated_at) ? d.formatted_updated_at : '-';
                        renderDetailStatus(parseInt(d.durum), updatedDate, d.processed_by_name);
                        $('#detail_aciklama').text(d.aciklama && d.aciklama.trim() !== '' ? d.aciklama : 'Açıklama girilmemiş.');
                        
                        if (d.admin_note && d.admin_note.trim() !== '') {
                            $('#detail_admin_note').text(d.admin_note);
                            $('#detail_admin_note_wrapper').removeClass('d-none');
                        } else {
                            $('#detail_admin_note_wrapper').addClass('d-none');
                        }
                    }
                }
            });
        }
    });

    function renderDetailStatus(durum, islemTarihi, islemYapan) {
        var statusBadge = '';
        var islemYapanText = '-';

        if (durum === 0) {
            statusBadge = '<span class="badge bg-warning-lt fs-4">Beklemede</span>';
            islemTarihi = 'Henüz işlem yapılmadı';
            islemYapanText = 'Henüz işlem yapılmadı';
        } else if (durum === 1) {
            statusBadge = '<span class="badge bg-success-lt fs-4">Onaylandı</span>';
            islemYapanText = (islemYapan && islemYapan.trim() !== '') ? 'Onaylayan: ' + islemYapan : 'Onaylayan: Yönetici / Sistem';
        } else if (durum === 2) {
            statusBadge = '<span class="badge bg-danger-lt fs-4">Reddedildi</span>';
            islemYapanText = (islemYapan && islemYapan.trim() !== '') ? 'Reddeden: ' + islemYapan : 'Reddeden: Yönetici / Sistem';
        }

        $('#detail_durum').html(statusBadge);
        $('#detail_islem_tarihi').text(islemTarihi || '-');
        $('#detail_islem_yapan').text(islemYapanText);
    }

    // Modal açıldığında Select2 başlatma (ISO 27001 ve Tabler dropdown standardı)
    $('#addAvansModal').on('shown.bs.modal', function() {
        if (!$('#avans_person_id').hasClass('select2-hidden-accessible')) {
            $('#avans_person_id').select2({
                dropdownParent: $('#addAvansModal'),
                placeholder: 'Personel seçiniz...',
                allowClear: true,
                width: '100%',
                templateResult: function(data) {
                    if (!data.id) return data.text;
                    var $elem = $(data.element);
                    var fullname = $elem.data('fullname') || data.text;
                    var tc = $elem.data('tc') || '';
                    return $('<div style="padding: 2px 0;"><div style="font-weight: 600; color: #1e293b; font-size: 13px; line-height: 1.2;">' + fullname + '</div>' + 
                             (tc ? '<div style="font-size: 11px; color: #64748b; line-height: 1.15;">TC: ' + tc + '</div>' : '') + 
                             '</div>');
                },
                templateSelection: function(data) {
                    if (!data.id) return data.text;
                    var $elem = $(data.element);
                    return $elem.data('fullname') || data.text;
                }
            });
        }
        if (!$('#avans_hedef_ay').hasClass('select2-hidden-accessible')) {
            $('#avans_hedef_ay').select2({
                dropdownParent: $('#addAvansModal'),
                minimumResultsForSearch: Infinity,
                width: '100%'
            });
        }
        if (!$('#avans_hedef_yil').hasClass('select2-hidden-accessible')) {
            $('#avans_hedef_yil').select2({
                dropdownParent: $('#addAvansModal'),
                minimumResultsForSearch: Infinity,
                width: '100%'
            });
        }
    });

    $('#addAvansModal').on('hidden.bs.modal', function() {
        document.getElementById('addAvansForm').reset();
        $('#avans_person_id').val(null).trigger('change');
        $('#avans_hedef_ay').val(<?= date('n'); ?>).trigger('change');
        $('#avans_hedef_yil').val(<?= date('Y'); ?>).trigger('change');
    });

    // Avans Ekleme Kayıt
    $('#saveAvansBtn').on('click', function() {
        var $btn = $(this);
        var form = document.getElementById('addAvansForm');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...');

        $.ajax({
            url: 'api/advances/advances.php',
            type: 'POST',
            data: {
                action: 'add',
                person_id: $('#avans_person_id').val(),
                tutar: $('#avans_tutar').val(),
                hedef_ay: $('#avans_hedef_ay').val(),
                hedef_yil: $('#avans_hedef_yil').val(),
                aciklama: $('#avans_aciklama').val()
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#addAvansModal').modal('hide');
                    Swal.fire({
                        icon: 'success',
                        title: 'Başarılı',
                        text: response.message,
                        confirmButtonText: 'Tamam'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Hata',
                        text: response.message || 'Kayıt başarısız.',
                        confirmButtonText: 'Tamam'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Hata',
                    text: 'İşlem sırasında bir hata oluştu.',
                    confirmButtonText: 'Tamam'
                });
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="ti ti-device-floppy me-1"></i> Kaydet');
            }
        });
    });

    // Durum Güncelleme (Onay / Red)
    $(document).on('click', '.update-status', function() {
        var id = $(this).data('id');
        var status = $(this).data('status');

        if (status == 2) {
            Swal.fire({
                title: 'Avans Talebini Reddet',
                text: 'Lütfen red gerekçesini belirtiniz:',
                input: 'textarea',
                inputValue: 'Uygun Değildir.',
                inputPlaceholder: 'Red gerekçesini giriniz...',
                showCancelButton: true,
                confirmButtonColor: '#d63939',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Reddet',
                cancelButtonText: 'Vazgeç',
                inputValidator: (value) => {
                    if (!value || !value.trim()) {
                        return 'Açıklama yazılması zorunludur!';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'api/advances/advances.php',
                        type: 'POST',
                        data: { 
                            action: 'update_status',
                            id: id, 
                            status: 2,
                            admin_note: result.value.trim()
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Başarılı',
                                    text: response.message,
                                    confirmButtonText: 'Tamam'
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Hata',
                                    text: response.message,
                                    confirmButtonText: 'Tamam'
                                });
                            }
                        },
                        error: function() {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata',
                                text: 'İşlem sırasında bir hata oluştu.',
                                confirmButtonText: 'Tamam'
                            });
                        }
                    });
                }
            });
            return;
        }

        Swal.fire({
            title: 'Onaylıyor musunuz?',
            text: "Bu avans talebini onaylamak istediğinize emin misiniz?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2fb344',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Onayla',
            cancelButtonText: 'Vazgeç',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/advances/advances.php',
                    type: 'POST',
                    data: { 
                        action: 'update_status',
                        id: id, 
                        status: 1 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı',
                                text: response.message,
                                confirmButtonText: 'Tamam'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata',
                                text: response.message,
                                confirmButtonText: 'Tamam'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: 'İşlem sırasında bir hata oluştu.',
                            confirmButtonText: 'Tamam'
                        });
                    }
                });
            }
        });
    });

    // Avans Talebini Sil
    $(document).on('click', '.delete-request', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Avans talebi ve bağlı tüm kayıtlar silinecektir! Bu işlem geri alınamaz.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63939',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/advances/advances.php',
                    type: 'POST',
                    data: { 
                        action: 'delete',
                        id: id
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Silindi',
                                text: response.message,
                                confirmButtonText: 'Tamam'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Hata',
                                text: response.message,
                                confirmButtonText: 'Tamam'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Hata',
                            text: 'İşlem sırasında bir hata oluştu.',
                            confirmButtonText: 'Tamam'
                        });
                    }
                });
            }
        });
    });

    var CAN_DELETE_APPROVED = <?= $perm->hasPermission("onayli_avanslarda_islem_yap") ? 'true' : 'false'; ?>;

    // Tabloda Sağ Tık (Custom Context Menu)
    $(document).on('contextmenu', '#advanceTable tbody tr', function(e) {
        var $tr = $(this);
        if ($tr.hasClass('dataTables_empty') || (!$tr.find('.view-detail').length && !$tr.data('id'))) return;

        e.preventDefault();
        $('#advanceTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var id = $tr.data('id') || $tr.find('.view-detail').data('id');
        var durum = parseInt($tr.data('durum') !== undefined ? $tr.data('durum') : $tr.find('.view-detail').data('durum'));
        var personel = $tr.data('personel') || $tr.find('.view-detail').data('personel') || 'Avans Talebi';
        var tutar = $tr.data('tutar') || $tr.find('.view-detail').data('tutar') || '';

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var headerTitle = personel + (tutar ? ' (' + tutar + ')' : '');

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-cash me-1"></i> ${$('<div>').text(headerTitle).html()}</div>
            <a href="javascript:void(0)" class="ctx-action-detail"><i class="ti ti-eye"></i> Detay Görüntüle</a>
        `;

        if (durum === 0) {
            menuHtml += `
                <a href="javascript:void(0)" class="ctx-action-approve text-success"><i class="ti ti-check"></i> Talebi Onayla</a>
                <a href="javascript:void(0)" class="ctx-action-reject text-warning"><i class="ti ti-x"></i> Talebi Reddet</a>
                <div class="cm-divider"></div>
                <a href="javascript:void(0)" class="ctx-action-delete cm-danger"><i class="ti ti-trash"></i> Talebi Sil</a>
            `;
        } else if (durum === 1 && CAN_DELETE_APPROVED) {
            menuHtml += `
                <div class="cm-divider"></div>
                <a href="javascript:void(0)" class="ctx-action-delete cm-danger"><i class="ti ti-trash"></i> Talebi Sil</a>
            `;
        } else if (durum === 2) {
            menuHtml += `
                <div class="cm-divider"></div>
                <a href="javascript:void(0)" class="ctx-action-delete cm-danger"><i class="ti ti-trash"></i> Talebi Sil</a>
            `;
        }

        $contextMenu.html(menuHtml);
        $contextMenu.data('target-row', $tr);
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

    $(document).on('click', '.ctx-action-detail', function() {
        var $tr = $('#customContextMenu').data('target-row');
        if ($tr && $tr.length) {
            $tr.find('.view-detail').first().trigger('click');
        }
    });

    $(document).on('click', '.ctx-action-approve', function() {
        var $tr = $('#customContextMenu').data('target-row');
        if ($tr && $tr.length) {
            $tr.find('.update-status[data-status="1"]').first().trigger('click');
        }
    });

    $(document).on('click', '.ctx-action-reject', function() {
        var $tr = $('#customContextMenu').data('target-row');
        if ($tr && $tr.length) {
            $tr.find('.update-status[data-status="2"]').first().trigger('click');
        }
    });

    $(document).on('click', '.ctx-action-delete', function() {
        var $tr = $('#customContextMenu').data('target-row');
        if ($tr && $tr.length) {
            $tr.find('.delete-request').first().trigger('click');
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#advanceTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a, #customContextMenu button', function() {
        $('#customContextMenu').hide();
        $('#advanceTable tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#advanceTable tbody tr').removeClass('context-menu-active');
    });
});
</script>
