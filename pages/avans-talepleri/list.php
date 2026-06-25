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
                                <option value="<?php echo $person->id; ?>"><?php echo htmlspecialchars($person->full_name); ?></option>
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
                                            <?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>
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
                    <table id="advanceTable" class="table card-table text-nowrap table-hover datatable">
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
                                ?>
                                <tr>
                                    <td><?php echo $req->id; ?></td>
                                    <td><?php echo $req->full_name; ?></td>
                                    <td class="font-weight-bold"><?php echo Helper::formattedMoney($req->tutar); ?></td>
                                    <td><?php echo Date::monthName($req->hedef_ay) . ' ' . $req->hedef_yil; ?></td>
                                    <td>
                                        <span class="text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo $req->aciklama; ?>">
                                            <?php echo $req->aciklama; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $req->formatted_date; ?></td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td class="text-end">
                                        <div class="btn-list justify-content-end flex-nowrap">
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
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
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
