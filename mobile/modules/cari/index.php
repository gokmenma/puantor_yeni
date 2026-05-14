<?php
require_once ROOT . "/Model/Cari.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Security;

$firm_id = $_SESSION['firm_id'] ?? 0;
$cariModel = new Cari();
$cariler = $cariModel->getCariByFirm($firm_id);
$totals = $cariModel->getFirmTotals($firm_id);
$total_balance = ($totals->total_borc ?? 0) - ($totals->total_alacak ?? 0);

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
    text-decoration: none;
    color: inherit;
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
.avatar-cari {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: rgba(32, 107, 196, 0.08);
    color: var(--mobile-primary);
    display: flex;
    align-items: center;
    justify-content: center;
}
</style>

<div class="container px-0">
    <div class="mb-4 d-flex align-items-center justify-content-between px-2">
        <div>
            <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Cari Takip</h2>
            <p class="text-muted text-xs mb-0">Carilerinizin listesi ve bakiyeleri.</p>
        </div>
    </div>

    <!-- Üst Bakiye Kartı (Kasa'daki gibi) -->
    <div class="mobile-card bg-primary text-white p-4 mb-3 position-relative overflow-hidden mx-2" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #206bc4 0%, #104b8c 100%) !important;">
        <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
            <i class="ti ti-users"></i>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;">Net Bakiye Durumu</span>
            <i class="ti ti-scale" style="font-size: 1.5rem; opacity: 0.8;"></i>
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
                <div class="text-bold h3 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($totals->total_borc ?? 0); ?></div>
            </div>
        </div>
        <div class="col-6">
            <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(47, 179, 68, 0.1); color: #2fb344; border-radius: 16px;">
                <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Alınan (Alacak)</div>
                <div class="text-bold h3 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($totals->total_alacak ?? 0); ?></div>
            </div>
        </div>
    </div>

    <div class="px-2 mb-2 d-flex align-items-center justify-content-between">
        <h4 class="mb-0 text-semibold" style="font-size: 0.95rem;">Cari Listesi</h4>
    </div>

    <div class="px-2">
        <div class="mobile-card p-0 mb-4 overflow-hidden shadow-sm border-0" style="background: #fff; border-radius: 18px;">
            <div id="cari-list">
                <?php if (empty($cariler)): ?>
                    <div class="text-center py-5">
                        <i class="ti ti-users-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                        <p class="text-muted text-sm mb-0">Cari kaydı bulunamadı.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($cariler as $cari): 
                        $id = Security::encrypt($cari->id);
                        $balance = $cariModel->getBalance($cari->id);
                    ?>
                        <div class="transaction-item-wrapper border-bottom border-light">
                            <div class="transaction-item-actions">
                                <button class="btn-swipe-delete btn-delete-cari" data-id="<?php echo $id; ?>">
                                    <i class="ti ti-trash"></i>
                                    <span>Sil</span>
                                </button>
                            </div>
                            <a href="cari-movements?id=<?php echo $id; ?>" class="transaction-item-content p-3" style="background: #fff; display: flex; width: 100%;">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(32, 107, 196, 0.1); color: #206bc4;">
                                        <i class="ti ti-user" style="font-size: 1.25rem;"></i>
                                    </div>
                                    <div>
                                        <div class="text-bold text-sm" style="color: #1d273b;">
                                            <?php echo htmlspecialchars($cari->FirmaAdi); ?>
                                            <?php if ($cari->YetkiliAdi): ?>
                                                <span class="text-muted font-weight-normal" style="font-size: 0.75rem;">(<?php echo htmlspecialchars($cari->YetkiliAdi); ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-muted text-xs"><?php echo htmlspecialchars($cari->Telefon ?: 'Telefon Yok'); ?></div>
                                    </div>
                                </div>
                                <div class="text-end ms-auto">
                                    <div class="text-bold text-sm <?php echo $balance < 0 ? 'text-red' : ($balance > 0 ? 'text-green' : ''); ?>" style="font-size: 0.9rem;">
                                        <?php echo ($balance < 0 ? '-' : ($balance > 0 ? '+' : '')) . ' ₺' . Helper::formattedMoneyWithoutCurrency(abs($balance)); ?>
                                    </div>
                                    <div class="text-muted text-xxs" style="font-size: 0.65rem;"><?php echo $balance < 0 ? 'Alacaklı' : ($balance > 0 ? 'Borçlu' : 'Dengede'); ?></div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<a href="#" class="mobile-fab" data-bs-toggle="modal" data-bs-target="#add-cari-modal">
    <i class="ti ti-plus"></i>
</a>

<!-- Add Cari Modal -->
<div class="modal modal-blur fade" id="add-cari-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header py-3">
                <h5 class="modal-title">Yeni Cari Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="mobileCariForm">
                    <input type="hidden" name="id" value="0">
                    <div class="form-floating mb-3">
                        <div class="input-group">
                            <div class="form-floating flex-grow-1">
                                <input type="text" class="form-control" name="FirmaAdi" id="mFirmaAdi" placeholder="Firma Adı" required>
                                <label for="mFirmaAdi">Firma Adı *</label>
                            </div>
                            <button type="button" class="btn btn-icon btn-light border" id="btnPickContact" style="height: 58px; width: 50px; border-radius: 0 12px 12px 0;">
                                <i class="ti ti-address-book" style="font-size: 1.5rem;"></i>
                            </button>
                        </div>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="YetkiliAdi" id="mYetkiliAdi" placeholder="Yetkili Adı">
                        <label for="mYetkiliAdi">Yetkili Adı</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" name="Telefon" id="mTelefon" placeholder="Telefon">
                        <label for="mTelefon">Telefon</label>
                    </div>
                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" name="Email" id="mEmail" placeholder="Email">
                        <label for="mEmail">Email</label>
                    </div>
                    <div class="form-floating mb-3">
                        <textarea class="form-control" name="Adres" id="mAdres" style="height: 80px;" placeholder="Adres"></textarea>
                        <label for="mAdres">Adres</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-link text-muted" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary px-4" id="saveMobileCari">Kaydet</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Contact Picker API
    const btnPickContact = document.getElementById('btnPickContact');
    if (!('contacts' in navigator && 'select' in navigator.contacts)) {
        btnPickContact.style.display = 'none'; // API not supported
    }

    btnPickContact.addEventListener('click', async () => {
        const props = ['name', 'tel', 'email'];
        const opts = { multiple: false };

        try {
            const contacts = await navigator.contacts.select(props, opts);
            if (contacts.length > 0) {
                const contact = contacts[0];
                
                // Set Name
                if (contact.name && contact.name.length > 0) {
                    $('#mFirmaAdi').val(contact.name[0]);
                    $('#mYetkiliAdi').val(contact.name[0]);
                }

                // Set Phone
                if (contact.tel && contact.tel.length > 0) {
                    // Temizleme: boşlukları ve özel karakterleri kaldırabiliriz ama genelde ham veri istenir
                    let phone = contact.tel[0].replace(/\s/g, '');
                    $('#mTelefon').val(phone);
                }

                // Set Email
                if (contact.email && contact.email.length > 0) {
                    $('#mEmail').val(contact.email[0]);
                }
            }
        } catch (ex) {
            console.log('Contact picker error:', ex);
            if(ex.name !== 'AbortError') {
                Swal.fire('Hata', 'Rehbere erişilemedi veya tarayıcınız bu özelliği desteklemiyor.', 'error');
            }
        }
    });

    $('#saveMobileCari').click(function() {
        var formData = $('#mobileCariForm').serialize();
        $.ajax({
            url: '/api/cari/save_cari.php',
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

    $(document).on('click', '.btn-delete-cari', function() {
        var id = $(this).data('id');
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu cari kaydını silmek istediğinize emin misiniz?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'Vazgeç',
            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '/api/cari/delete_cari.php',
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
    });

    // Swipe functionality
    let touchStartX = 0;
    let touchStartY = 0;
    let touchMoveX = 0;
    let touchMoveY = 0;
    let isHorizontalSwipe = false;
    let isVerticalScroll = false;
    const swipeThreshold = 70;
    const minMovement = 10;

    $(document).on('touchstart', '.transaction-item-content', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
        touchStartY = e.originalEvent.touches[0].clientY;
        touchMoveX = touchStartX;
        touchMoveY = touchStartY;
        isHorizontalSwipe = false;
        isVerticalScroll = false;
        
        // Sadece diğerlerini kapat, tıklanana dokunma (belki zaten açıktır)
        $('.transaction-item-content').not($(this)).css('transition', 'transform 0.2s ease-out').css('transform', 'translateX(0)');
    });

    $(document).on('touchmove', '.transaction-item-content', function(e) {
        touchMoveX = e.originalEvent.touches[0].clientX;
        touchMoveY = e.originalEvent.touches[0].clientY;
        
        let diffX = touchStartX - touchMoveX;
        let diffY = Math.abs(touchStartY - touchMoveY);

        // Eğer dikey kaydırma olduğu kesinleştiyse yatay hareketi engelle
        if (isVerticalScroll) return;

        // Henüz ne olduğu belli değilse kontrol et
        if (!isHorizontalSwipe && !isVerticalScroll) {
            if (Math.abs(diffX) > minMovement && Math.abs(diffX) > diffY) {
                isHorizontalSwipe = true;
            } else if (diffY > minMovement) {
                isVerticalScroll = true;
                return;
            }
        }

        if (isHorizontalSwipe) {
            // Yatay kaydırma ise tarayıcının varsayılan (sayfa kaydırma) hareketini durdur
            if (e.cancelable) e.preventDefault();
            
            if (diffX > 0) {
                // Sola kaydırma (sil butonu açma)
                let moveAmount = diffX;
                if (moveAmount > swipeThreshold + 30) moveAmount = swipeThreshold + 30;
                $(this).css('transition', 'none').css('transform', 'translateX(-' + moveAmount + 'px)');
            } else {
                // Sağa kaydırma (kapatma)
                $(this).css('transition', 'none').css('transform', 'translateX(0)');
            }
        }
    });

    $(document).on('touchend', '.transaction-item-content', function(e) {
        if (!isHorizontalSwipe) {
            // Eğer sadece tıklandıysa veya dikey kaydırıldıysa transformu sıfırla (eğer hafif kıpırdadıysa)
            if (!isVerticalScroll) {
                 $(this).css('transition', 'transform 0.2s ease-out').css('transform', 'translateX(0)');
            }
            return;
        }

        let diffX = touchStartX - touchMoveX;
        $(this).css('transition', 'transform 0.2s ease-out');
        
        if (diffX > swipeThreshold) {
            $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    // Tıklama kontrolü: Eğer öğe açıkken (swiped) tıklandıysa gitme, sadece kapat
    $(document).on('click', '.transaction-item-content', function(e) {
        let transform = $(this).css('transform');
        // Matrix kontrolü (translateX'i kontrol eder)
        let matrix = new WebKitCSSMatrix(transform);
        if (matrix.m41 < -10) {
            e.preventDefault();
            $(this).css('transition', 'transform 0.2s ease-out').css('transform', 'translateX(0)');
        }
    });
});
</script>
