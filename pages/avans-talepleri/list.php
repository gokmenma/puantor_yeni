<?php
require_once "Model/AdvanceRequest.php";
require_once "Model/Persons.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/security.php";
require_once "App/Helper/date.php";

use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\Date;

// Kullanıcının firmasını kontrol eder
$Auths->checkFirmReturn();

// Yetki kontrolü - avans_talepleri yetkisine bağlı
$perm->checkAuthorize("avans_talepleri");

$advanceModel = new AdvanceRequest();
$requests = $advanceModel->getRequestsByFirm($_SESSION["firm_id"]);
$stats = $advanceModel->getStats($_SESSION["firm_id"]);

$personsModel = new Persons();
$persons = $personsModel->getPersonsByFirm($_SESSION["firm_id"]);

?>
<style>
    /* Animated Icon Button Base */
    /* Premium Button Style matching Tabler "Buttons with icon" */
    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        background: #ffffff !important;
        border: 1px solid #e6e7e9 !important;
        border-radius: 4px;
        padding: 0.4rem 0.75rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #1e293b !important;
        transition: all 0.2s ease;
        text-decoration: none !important;
        box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    }
    
    .btn-action:hover {
        background: #f8fafc !important;
        border-color: #cbd5e1 !important;
        color: #0f172a !important;
    }
    
    .btn-action .icon {
        transition: all 0.3s ease;
    }

    /* Animations for Icons */
    .btn-animate-tada:hover .icon { animation: tada 1s ease infinite; }
    .btn-animate-shake:hover .icon { animation: shake 0.5s ease infinite; }
    .btn-animate-rotate:hover .icon { transform: rotate(90deg); }

    @keyframes tada {
        0% { transform: scale(1); }
        10%, 20% { transform: scale(0.9) rotate(-3deg); }
        30%, 50%, 70%, 90% { transform: scale(1.1) rotate(3deg); }
        40%, 60%, 80% { transform: scale(1.1) rotate(-3deg); }
        100% { transform: scale(1) rotate(0); }
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-3px); }
        75% { transform: translateX(3px); }
    }

    /* Vibrant Icon Colors */
    .icon-success-vibrant { color: #2fb344 !important; }
    .icon-danger-vibrant { color: #d63939 !important; }
    
    .clickable-desc {
        cursor: pointer;
        transition: color 0.15s ease;
    }
    .clickable-desc:hover {
        color: #206bc4;
        text-decoration: underline;
    }
</style>

<div class="page-header d-print-none mb-0">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Avans Talepleri</h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAvansModal">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    Avans Ekle
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Avans Ekle Modal -->
<div class="modal modal-blur fade" id="addAvansModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Personele Avans Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addAvansForm">
                    <div class="mb-3">
                        <label class="form-label required">Personel</label>
                        <select class="form-select" name="person_id" id="avans_person_id" required>
                            <option value=""></option>
                            <?php foreach ($persons as $person): ?>
                                <option value="<?php echo $person->id; ?>"><?php echo htmlspecialchars($person->full_name, ENT_QUOTES, 'UTF-8'); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Tutar (₺)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" name="tutar" id="avans_tutar" placeholder="0.00" required>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label required">Hedef Ay</label>
                                <select class="form-select" name="hedef_ay" id="avans_hedef_ay" required>
                                    <?php for ($m = 1; $m <= 12; $m++): ?>
                                        <option value="<?php echo $m; ?>" <?php echo $m == date('n') ? 'selected' : ''; ?>>
                                            <?php echo Date::monthName($m); ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="mb-3">
                                <label class="form-label required">Hedef Yıl</label>
                                <select class="form-select" name="hedef_yil" id="avans_hedef_yil" required>
                                    <?php for ($y = date('Y') - 1; $y <= date('Y') + 1; $y++): ?>
                                        <option value="<?php echo $y; ?>" <?php echo $y == date('Y') ? 'selected' : ''; ?>>
                                            <?php echo $y; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="aciklama" id="avans_aciklama" rows="2" placeholder="Açıklama giriniz..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="saveAvansBtn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                    Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Avans Detay Modal -->
<div class="modal modal-blur fade" id="detailAvansModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-primary" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    Avans Talebi Detayı
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="card mb-3 bg-body-tertiary border-0 shadow-none">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-muted small">Personel</div>
                                <div class="fw-bold fs-3 text-dark" id="detail_personel">-</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Talep Tutarı</div>
                                <div class="fw-bold fs-3 text-primary" id="detail_tutar">-</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Hedef Dönem</div>
                                <div class="fw-medium text-dark" id="detail_donem">-</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Durum</div>
                                <div id="detail_durum">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="text-muted small mb-1 d-flex align-items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-calendar" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" /><path d="M16 3v4" /><path d="M8 3v4" /><path d="M4 11h16" /></svg>
                                    Talep Tarihi
                                </div>
                                <div class="fw-bold text-dark" id="detail_talep_tarihi">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="text-muted small mb-1 d-flex align-items-center gap-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-clock" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /><path d="M12 7v5l3 3" /></svg>
                                    İşlem / Onay Tarihi
                                </div>
                                <div class="fw-bold text-dark" id="detail_islem_tarihi">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="text-muted small mb-1 d-flex align-items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-user-check" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0" /><path d="M6 21v-2a4 4 0 0 1 4 -4h4" /><path d="M15 19l2 2l4 -4" /></svg>
                        İşlemi Yapan (Onaylayan / Reddeden)
                    </div>
                    <div class="p-2 border rounded bg-white fw-medium text-dark" id="detail_islem_yapan">-</div>
                </div>

                <div>
                    <div class="text-muted small mb-1 d-flex align-items-center gap-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-text" width="16" height="16" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 3v4a1 1 0 0 0 1 1h4" /><path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z" /><path d="M9 9l1 0" /><path d="M9 13l6 0" /><path d="M9 17l6 0" /></svg>
                        Açıklama
                    </div>
                    <div class="p-3 border rounded bg-body text-dark" id="detail_aciklama" style="white-space: pre-wrap; word-break: break-word; min-height: 80px; max-height: 250px; overflow-y: auto;">-</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary ms-auto" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<div class="container-xl mt-3">
    <!-- Summary Cards -->
    <div class="row row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card card-sm">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-yellow-lt avatar">
                                <i class="ti ti-clock icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Bekleyen Talepler</div>
                            <div class="text-muted"><?php echo $stats->pending_count ?? 0; ?> Kayıt</div>
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
                            <span class="bg-green-lt avatar">
                                <i class="ti ti-check icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Onaylanan Talepler</div>
                            <div class="text-muted"><?php echo $stats->approved_count ?? 0; ?> Kayıt</div>
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
                            <span class="bg-primary-lt avatar">
                                <i class="ti ti-cash icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Toplam Onaylanan</div>
                            <div class="text-muted"><?php echo Helper::formattedMoney($stats->approved_amount ?? 0); ?></div>
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
                            <span class="bg-red-lt avatar">
                                <i class="ti ti-x icon"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium">Reddedilen Talepler</div>
                            <div class="text-muted"><?php echo $stats->rejected_count ?? 0; ?> Kayıt</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Avans Talepleri</h3>
                </div>
                <div class="table-responsive">
                    <table id="advanceTable" class="table card-table text-nowrap table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Personel</th>
                                <th>Tutar</th>
                                <th>Dönem</th>
                                <th>Açıklama</th>
                                <th>Tarih</th>
                                <th>Durum</th>
                                <th class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): 
                                $status_badge = '';
                                if ($req->durum == 0) {
                                    $status_badge = '<span class="badge bg-warning-lt">Beklemede</span>';
                                } elseif ($req->durum == 1) {
                                    $status_badge = '<span class="badge bg-success-lt">Onaylandı</span>';
                                } elseif ($req->durum == 2) {
                                    $status_badge = '<span class="badge bg-danger-lt">Reddedildi</span>';
                                }
                                $islem_tarihi_formatted = ($req->durum != 0 && !empty($req->formatted_updated_at)) ? $req->formatted_updated_at : '-';
                                ?>
                                <tr>
                                    <td><?php echo $req->id; ?></td>
                                    <td><?php echo htmlspecialchars($req->full_name, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="font-weight-bold"><?php echo Helper::formattedMoney($req->tutar); ?></td>
                                    <td><?php echo Date::monthName($req->hedef_ay) . ' ' . $req->hedef_yil; ?></td>
                                    <td>
                                        <span class="text-truncate d-inline-block clickable-desc view-detail" style="max-width: 200px;" 
                                              title="Detayı görüntülemek için tıklayın: <?php echo htmlspecialchars($req->aciklama ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-id="<?php echo $req->id; ?>"
                                              data-personel="<?php echo htmlspecialchars($req->full_name, ENT_QUOTES, 'UTF-8'); ?>"
                                              data-tutar="<?php echo Helper::formattedMoney($req->tutar); ?>"
                                              data-donem="<?php echo Date::monthName($req->hedef_ay) . ' ' . $req->hedef_yil; ?>"
                                              data-talep-tarihi="<?php echo $req->formatted_date; ?>"
                                              data-islem-tarihi="<?php echo $islem_tarihi_formatted; ?>"
                                              data-durum="<?php echo $req->durum; ?>"
                                              data-islem-yapan="<?php echo htmlspecialchars($req->processed_by_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                              data-aciklama="<?php echo htmlspecialchars($req->aciklama ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($req->aciklama ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $req->formatted_date; ?></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end flex-nowrap">
                                            <button class="btn-action btn-animate-rotate view-detail" 
                                                    data-id="<?php echo $req->id; ?>"
                                                    data-personel="<?php echo htmlspecialchars($req->full_name, ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-tutar="<?php echo Helper::formattedMoney($req->tutar); ?>"
                                                    data-donem="<?php echo Date::monthName($req->hedef_ay) . ' ' . $req->hedef_yil; ?>"
                                                    data-talep-tarihi="<?php echo $req->formatted_date; ?>"
                                                    data-islem-tarihi="<?php echo $islem_tarihi_formatted; ?>"
                                                    data-durum="<?php echo $req->durum; ?>"
                                                    data-islem-yapan="<?php echo htmlspecialchars($req->processed_by_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-aciklama="<?php echo htmlspecialchars($req->aciklama ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1 text-primary">
                                                    <path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
                                                    <path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"></path>
                                                </svg>
                                                Detay
                                            </button>
                                            <?php if ($req->durum == 0): ?>
                                                <button class="btn-action btn-animate-tada update-status" data-id="<?php echo $req->id; ?>" data-status="1">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1 icon-success-vibrant">
                                                        <path d="M5 12l5 5l10 -10"></path>
                                                    </svg>
                                                    Onayla
                                                </button>
                                                <button class="btn-action btn-animate-shake update-status" data-id="<?php echo $req->id; ?>" data-status="2">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1 icon-danger-vibrant">
                                                        <path d="M18 6l-12 12"></path>
                                                        <path d="M6 6l12 12"></path>
                                                    </svg>
                                                    Reddet
                                                </button>
                                                <button class="btn-action btn-animate-shake delete-request" data-id="<?php echo Security::encrypt($req->id); ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1 icon-danger-vibrant">
                                                        <path d="M4 7l16 0"></path>
                                                        <path d="M10 11l0 6"></path>
                                                        <path d="M14 11l0 6"></path>
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                    </svg>
                                                    Sil
                                                </button>
                                            <?php elseif ($req->durum == 1 && $perm->hasPermission("onayli_avanslarda_islem_yap")): ?>
                                                <button class="btn-action btn-animate-shake delete-request" data-id="<?php echo Security::encrypt($req->id); ?>">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1 icon-danger-vibrant">
                                                        <path d="M4 7l16 0"></path>
                                                        <path d="M10 11l0 6"></path>
                                                        <path d="M14 11l0 6"></path>
                                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                                    </svg>
                                                    Sil
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

<script>
$(document).ready(function() {

    if ($.fn.DataTable && !$.fn.DataTable.isDataTable('#advanceTable') && $('#advanceTable').length) {
        if (typeof window.createDataTable === 'function') {
            window.createDataTable('#advanceTable', {
                order: [[0, 'desc']]
            });
        }
    }

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

        $('#detail_personel').text(personel || '-');
        $('#detail_tutar').text(tutar || '-');
        $('#detail_donem').text(donem || '-');
        $('#detail_talep_tarihi').text(talepTarihi || '-');

        renderDetailStatus(durum, islemTarihi, islemYapan);
        $('#detail_aciklama').text(aciklama && aciklama.trim() !== '' ? aciklama : 'Açıklama girilmemiş.');

        $('#detailAvansModal').modal('show');

        // Backend'den en güncel veriyi AJAX ile da çekelim
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

    $('#saveAvansBtn').on('click', function() {
        var $btn = $(this);
        var form = document.getElementById('addAvansForm');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        $btn.prop('disabled', true).text('Kaydediliyor...');

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
                    Swal.fire('Başarılı', response.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata', response.message, 'error');
                }
            },
            error: function() {
                Swal.fire('Hata', 'İşlem sırasında bir hata oluştu.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<svg xmlns="http://www.w3.org/2000/svg" class="icon me-1" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg> Kaydet');
            }
        });
    });

    $('#addAvansModal').on('shown.bs.modal', function() {
        if (!$('#avans_person_id').hasClass('select2-hidden-accessible')) {
            $('#avans_person_id').select2({
                dropdownParent: $('#addAvansModal'),
                placeholder: 'Personel seçiniz...',
                allowClear: true,
                width: '100%'
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
        $('#avans_hedef_ay').val(<?php echo date('n'); ?>).trigger('change');
        $('#avans_hedef_yil').val(<?php echo date('Y'); ?>).trigger('change');
    });

    $(document).on('click', '.update-status', function() {
        var id = $(this).data('id');
        var status = $(this).data('status');
        var statusText = status == 1 ? 'onaylamak' : 'reddetmek';
        var confirmButtonText = status == 1 ? 'Evet, Onayla' : 'Evet, Reddet';
        var cancelButtonText = status == 1 ? 'İptal Et' : 'Vazgeç';
        var confirmButtonColor = status == 1 ? '#2fb344' : '#d63939';

        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu talebi " + statusText + " istediğinize emin misiniz?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: confirmButtonColor,
            cancelButtonColor: '#6c757d',
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/advances/advances.php',
                    type: 'POST',
                    data: { 
                        action: 'update_status',
                        id: id, 
                        status: status 
                    },
                    dataType: 'json',
                    success: function(response) {
                        if(response.status === 'success') {
                            Swal.fire('Başarılı', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Hata', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Hata', 'İşlem sırasında bir hata oluştu.', 'error');
                    }
                });
            }
        });
    });

    $(document).on('click', '.delete-request', function() {
        var id = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Onaylanmış avans talebi silinecektir! Bu işlem geri alınamaz.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63939',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, sil!',
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
                        if(response.status === 'success') {
                            Swal.fire('Silindi', response.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Hata', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Hata', 'İşlem sırasında bir hata oluştu.', 'error');
                    }
                });
            }
        });
    });
});
</script>
