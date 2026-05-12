<?php
require_once ROOT . "/Model/Cari.php";
require_once ROOT . "/Model/CariHareketleri.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$cari_id_enc = $_GET['id'] ?? null;
if (!$cari_id_enc) {
    echo "<script>window.location.href='?p=cari/index';</script>";
    exit;
}

$cari_id = Security::decrypt($cari_id_enc);
$cariModel = new Cari();
$cari = $cariModel->find($cari_id);

if (!$cari || $cari->firma != $_SESSION['firm_id']) {
    echo "<script>window.location.href='?p=cari/index';</script>";
    exit;
}

$moveModel = new CariHareketleri();
$movements = $moveModel->getMovementsByCari($cari_id);

$running_balance = 0;
?>

<style>
/* Swipe to Delete Styles */
.transaction-item-wrapper {
    position: relative;
    overflow: hidden;
    background: #fff;
    border-radius: 16px;
    margin-bottom: 12px;
    user-select: none;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}
body[data-bs-theme="dark"] .transaction-item-wrapper,
body[data-bs-theme="dark"] .transaction-item-content {
    background: #1e293b !important;
}
.transaction-item-actions {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    display: flex;
    align-items: center;
    background: #d63f3f;
    z-index: 1;
    border-radius: 0 16px 16px 0;
}
.transaction-item-content {
    position: relative;
    background: #fff;
    z-index: 2;
    transition: transform 0.2s ease-out;
    width: 100%;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.btn-swipe-delete {
    color: white;
    width: 70px;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    font-size: 0.7rem;
    font-weight: 600;
}
.btn-swipe-delete i {
    font-size: 1.2rem;
    margin-bottom: 2px;
}
.avatar-move {
    width: 36px;
    height: 36px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<div class="container px-0">
    <div class="mb-4 d-flex align-items-center gap-3 px-2">
        <a href="?p=cari/index" class="btn btn-icon btn-light rounded-circle" style="width: 40px; height: 40px;">
            <i class="ti ti-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">
                <?php echo htmlspecialchars($cari->FirmaAdi); ?>
                <?php if (!empty($cari->YetkiliAdi)): ?>
                    <small class="text-muted" style="font-size: 65%;">(<?php echo htmlspecialchars($cari->YetkiliAdi); ?>)</small>
                <?php endif; ?>
            </h2>
            <p class="text-muted text-xs mb-0">Hesap Hareketleri ve Ekstre</p>
        </div>
    </div>

    <!-- Üst Bakiye Kartı (Kasa'daki gibi) -->
    <?php 
    $total_balance = $cariModel->getBalance($cari_id);
    $total_borc = 0;
    $total_alacak = 0;
    foreach ($movements as $m) {
        $total_borc += $m->borc;
        $total_alacak += $m->alacak;
    }
    ?>
    <div class="mobile-card bg-primary text-white p-4 mb-3 position-relative overflow-hidden mx-2" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #206bc4 0%, #104b8c 100%) !important;">
        <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
            <i class="ti ti-receipt"></i>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;">Güncel Bakiye</span>
            <i class="ti ti-wallet" style="font-size: 1.5rem; opacity: 0.8;"></i>
        </div>
        <h3 class="mb-0 text-bold" style="font-size: 2.2rem; letter-spacing: -1px;">₺ <?php echo Helper::formattedMoneyWithoutCurrency(abs($total_balance)); ?></h3>
        <div class="mt-3">
            <span class="badge bg-white-10 text-white text-xs" style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 4px 10px;">
                <?php echo $total_balance < 0 ? 'Borçlu durumdasınız' : ($total_balance > 0 ? 'Alacaklı durumdasınız' : 'Hesap dengede'); ?>
            </span>
        </div>
    </div>

    <!-- Yan Yana Alt Kartlar -->
    <div class="row g-2 mb-4 px-2">
        <div class="col-6">
            <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(214, 63, 63, 0.1); color: #d63f3f; border-radius: 16px;">
                <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Verilen (Borç)</div>
                <div class="text-bold h3 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_borc); ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(47, 179, 68, 0.1); color: #2fb344; border-radius: 16px;">
                <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Alınan (Alacak)</div>
                <div class="text-bold h3 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_alacak); ?></div>
            </div>
        </div>
    </div>

    <div class="px-2 mb-2">
        <h4 class="mb-0 text-semibold" style="font-size: 0.95rem;">Son Hareketler</h4>
    </div>

    <div class="px-2">
        <div class="mobile-card p-0 mb-4 overflow-hidden shadow-sm border-0" style="background: #fff; border-radius: 18px;">
            <div id="movements-list">
                <?php if (empty($movements)): ?>
                    <div class="text-center py-5">
                        <i class="ti ti-receipt-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        <p class="text-muted text-sm mb-0">Hareket bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($movements) as $m): 
                        $is_borc = $m->borc > 0;
                        $mid = Security::encrypt($m->id);
                    ?>
                        <div class="transaction-item-wrapper border-bottom border-light">
                            <div class="transaction-item-actions">
                                <button class="btn-swipe-delete btn-delete-movement" data-id="<?php echo $mid; ?>">
                                    <i class="ti ti-trash"></i>
                                    <span>Sil</span>
                                </button>
                            </div>
                            <div class="transaction-item-content p-3" style="background: #fff; display: flex; width: 100%;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: <?php echo $is_borc ? 'rgba(214, 63, 63, 0.1)' : 'rgba(47, 179, 68, 0.1)'; ?>; color: <?php echo $is_borc ? '#d63f3f' : '#2fb344'; ?>;">
                                        <i class="ti <?php echo $is_borc ? 'ti-arrow-down-left' : 'ti-arrow-up-right'; ?>" style="font-size: 1.25rem;"></i>
                                    </div>
                                    <div>
                                        <div class="text-bold text-sm" style="color: #1d273b;"><?php echo htmlspecialchars($m->aciklama ?: ($is_borc ? 'Borç İşlemi' : 'Alacak İşlemi')); ?></div>
                                        <div class="text-muted text-xs"><?php echo Date::dmY($m->islem_tarihi); ?> <?php echo $m->belge_no ? ' | No: '.$m->belge_no : ''; ?></div>
                                    </div>
                                </div>
                                <div class="text-end ms-auto">
                                    <div class="text-bold text-sm <?php echo $is_borc ? 'text-red' : 'text-green'; ?>" style="font-size: 0.95rem;">
                                        <?php echo ($is_borc ? '-' : '+') . ' ₺' . Helper::formattedMoneyWithoutCurrency($is_borc ? $m->borc : $m->alacak); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<a href="#" class="mobile-fab" data-bs-toggle="modal" data-bs-target="#add-movement-modal">
    <i class="ti ti-plus"></i>
</a>

<!-- Add Movement Modal -->
<div class="modal modal-blur fade" id="add-movement-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header py-3">
                <h5 class="modal-title">Yeni Hareket Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="mobileMovementForm">
                    <input type="hidden" name="id" value="0">
                    <input type="hidden" name="cari_id" value="<?php echo $cari_id_enc; ?>">
                    
                    <div class="mb-3">
                        <label class="form-label text-muted text-xs text-uppercase fw-bold mb-2">İşlem Tipi</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-selectgroup-item w-100">
                                    <input type="radio" name="mType" value="alacak" class="form-selectgroup-input" checked>
                                    <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2.5 rounded-3 border">
                                        <i class="ti ti-plus text-green me-1"></i> Tahsilat
                                    </span>
                                </label>
                            </div>
                            <div class="col-6">
                                <label class="form-selectgroup-item w-100">
                                    <input type="radio" name="mType" value="borc" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2.5 rounded-3 border">
                                        <i class="ti ti-minus text-red me-1"></i> Ödeme
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="number" step="0.01" class="form-control" name="mAmount" id="mAmount" placeholder="0.00" required>
                        <label for="mAmount">Tutar (₺) *</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control flatpickr" name="islem_tarihi" id="mIslemTarihi" value="<?php echo date('d.m.Y'); ?>" required>
                        <label for="mIslemTarihi">İşlem Tarihi</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="belge_no" id="mBelgeNo" placeholder="Belge No">
                        <label for="mBelgeNo">Belge No</label>
                    </div>

                    <div class="form-floating mb-3">
                        <textarea class="form-control" name="aciklama" id="mAciklama" style="height: 80px;" placeholder="Açıklama"></textarea>
                        <label for="mAciklama">Açıklama</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary px-4" id="saveMobileMovement">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#mIslemTarihi", {
            dateFormat: "d.m.Y",
            locale: "tr",
            disableMobile: "true"
        });
    }

    $('#saveMobileMovement').click(function() {
        var type = $('input[name="mType"]:checked').val();
        var amount = $('#mAmount').val();
        
        var formData = {
            id: $('#mobileMovementForm input[name="id"]').val(),
            cari_id: $('#mobileMovementForm input[name="cari_id"]').val(),
            islem_tarihi: $('#mIslemTarihi').val(),
            belge_no: $('#mBelgeNo').val(),
            aciklama: $('#mAciklama').val(),
            borc: type === 'borc' ? amount : 0,
            alacak: type === 'alacak' ? amount : 0
        };

        $.ajax({
            url: '/api/cari/save_movement.php',
            type: 'POST',
            data: formData,
            success: function(response) {
                var data = JSON.parse(response);
                if(data.status === 'success') {
                    Swal.fire({
                        title: 'Başarılı',
                        text: data.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata', data.message, 'error');
                }
            }
        });
    });

    $(document).on('click', '.btn-delete-movement', function() {
        var id = $(this).data('id');
        if (confirm('Bu hareketi silmek istediğinize emin misiniz?')) {
            $.ajax({
                url: '/api/cari/delete_movement.php',
                type: 'POST',
                data: { id: id },
                success: function(response) {
                    var data = JSON.parse(response);
                    if(data.status === 'success') {
                        location.reload();
                    } else {
                        Swal.fire('Hata', data.message, 'error');
                    }
                }
            });
        }
    });

    // Swipe functionality
    let touchStartX = 0;
    let touchMoveX = 0;
    const swipeThreshold = 70;

    $(document).on('touchstart', '.transaction-item-content', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
        $('.transaction-item-content').not($(this)).css('transform', 'translateX(0)');
    });

    $(document).on('touchmove', '.transaction-item-content', function(e) {
        touchMoveX = e.originalEvent.touches[0].clientX;
        let diff = touchStartX - touchMoveX;
        if (diff > 0) {
            if (diff > swipeThreshold + 20) diff = swipeThreshold + 20;
            $(this).css('transition', 'none').css('transform', 'translateX(-' + diff + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    $(document).on('touchend', '.transaction-item-content', function(e) {
        let diff = touchStartX - touchMoveX;
        $(this).css('transition', 'transform 0.2s ease-out');
        if (diff > swipeThreshold / 2) {
            $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });
});
</script>
