<?php
require_once ROOT . "/Model/AdvanceRequest.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

if (!$Auths->hasPermission("avans_talepleri")) {
    return;
}

$advanceModel = new AdvanceRequest();
$pendingRequests = $advanceModel->getPendingRequestsByFirm($firm_id);
?>

<style>
    .icon-success-vibrant { color: #2fb344 !important; }
    .icon-danger-vibrant { color: #d63939 !important; }
</style>

<div class="col-md-6" data-id="widget-avans-talepleri">
    <div class="card" style="max-height: 450px; display: flex; flex-direction: column;">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">BEKLEYEN AVANS TALEPLERİ</span>
            <div class="ms-auto d-flex align-items-center">
                <a href="index.php?p=avans-talepleri/list" class="btn btn-sm btn-link me-2" style="font-size:10px; padding:0;">Tümünü Gör</a>
                <i class="ti ti-grid-dots drag-handle text-muted"></i>
            </div>
        </div>
        <div class="card-body p-0" style="overflow-y: auto;">
            <div class="list-group list-group-flush">
                <?php if (count($pendingRequests) > 0): ?>
                    <?php foreach ($pendingRequests as $req): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar avatar-sm bg-orange-lt rounded-circle">
                                        <i class="ti ti-wallet"></i>
                                    </span>
                                </div>
                                <div class="col text-truncate">
                                    <div class="text-reset d-block" style="font-size: 13px; font-weight: 500;">
                                        <?php echo htmlspecialchars($req->full_name); ?>
                                    </div>
                                    <div class="d-flex align-items-center mt-1" style="gap: 8px;">
                                        <span class="badge bg-blue-lt" style="font-size: 10px; padding: 2px 6px;">
                                            <?php echo Helper::formattedMoney($req->tutar); ?> ₺
                                        </span>
                                        <small class="text-secondary" style="font-size: 11px;">
                                            <i class="ti ti-calendar-event me-1"></i>
                                            <?php echo Date::monthName($req->hedef_ay) . ' ' . $req->hedef_yil; ?>
                                        </small>
                                    </div>
                                    <?php if (!empty($req->aciklama)): ?>
                                        <div class="text-secondary text-truncate mt-1" style="font-size: 11px;" title="<?php echo htmlspecialchars($req->aciklama); ?>">
                                            <?php echo htmlspecialchars($req->aciklama); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-actions">
                                        <button class="btn btn-action update-avans-status me-1" 
                                                data-id="<?php echo $req->id; ?>" 
                                                data-status="1" 
                                                title="Onayla">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1 icon-success-vibrant">
                                                <path d="M5 12l5 5l10 -10"></path>
                                            </svg>
                                        </button>
                                        <button class="btn btn-action update-avans-status me-1" 
                                                data-id="<?php echo $req->id; ?>" 
                                                data-status="2" 
                                                title="Reddet">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1 icon-danger-vibrant">
                                                <path d="M18 6l-12 12"></path>
                                                <path d="M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                        <button class="btn btn-action delete-avans-request" 
                                                data-id="<?php echo Security::encrypt($req->id); ?>" 
                                                title="Sil">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1 icon-danger-vibrant">
                                                <path d="M4 7l16 0"></path>
                                                <path d="M10 11l0 6"></path>
                                                <path d="M14 11l0 6"></path>
                                                <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                                                <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-wallet mb-2" style="font-size: 32px; opacity: 0.5;"></i>
                        <div style="font-size: 13px;">Bekleyen avans talebi yok.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Event delegation with namespace to avoid double bindings
    $(document).off('click.homeAvans').on('click.homeAvans', '.update-avans-status', function(e) {
        e.preventDefault();
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

    $(document).off('click.homeAvansDelete').on('click.homeAvansDelete', '.delete-avans-request', function(e) {
        e.preventDefault();
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
