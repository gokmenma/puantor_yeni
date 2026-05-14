<?php
require_once "Model/AdvanceRequest.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Security;

// Kullanıcının firmasını kontrol eder
$Auths->checkFirmReturn();

// Yetki kontrolü - avans_talepleri yetkisine bağlı
$perm->checkAuthorize("avans_talepleri");

$advanceModel = new AdvanceRequest();
$requests = $advanceModel->getRequestsByFirm($_SESSION["firm_id"]);
$stats = $advanceModel->getStats($_SESSION["firm_id"]);

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
                                    <td><?php echo $req->hedef_ay . '/' . $req->hedef_yil; ?></td>
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
                    url: 'api/advances_admin.php',
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
                    url: 'api/advances_admin.php',
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
