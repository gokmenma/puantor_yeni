<?php
// Puantor Mobil - Bildirimler (Duyurular) Listesi
require_once ROOT . "/Model/DuyuruModel.php";

$Duyuru = new DuyuruModel();
$kullanici_id = $_SESSION['user']->id ?? 0;
$firma_id = $_SESSION['firm_id'] ?? 0;
$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$is_main_user = !$is_superadmin && (($_SESSION['user']->parent_id ?? 1) == 0);

$duyurular = [];
try {
    $duyurular = $Duyuru->getDuyurular($kullanici_id, $firma_id, $is_superadmin, $is_main_user);
} catch (Exception $e) {
    // duyurular tablosu yok vs
}
?>

<div class="container px-2">
  <!-- Geri Dönüş ve Başlık -->
  <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
    <div class="d-flex align-items-center gap-2">
      <a href="javascript:history.back()" class="btn btn-icon btn-sm btn-outline-secondary border-0 btn-active-scale">
        <i class="ti ti-arrow-left" style="font-size: 1.35rem;"></i>
      </a>
      <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Bildirimler</h2>
    </div>
    <?php if (!empty($duyurular)): ?>
      <button id="btn-mark-all-read" class="btn btn-sm btn-ghost-primary rounded-pill px-3 py-1 btn-active-scale" style="font-size: 0.8rem;">
        <i class="ti ti-checks me-1"></i> Tümünü Oku
      </button>
    <?php endif; ?>
  </div>

  <?php if (empty($duyurular)): ?>
    <!-- Boş Bildirim Ekranı -->
    <div class="mobile-card text-center py-5 px-3 d-flex flex-column align-items-center justify-content-center" style="border-radius: 20px; background: #fff; min-height: 250px;">
      <div class="avatar avatar-xl rounded-circle bg-secondary-lt mb-3 text-secondary" style="width: 72px; height: 72px;">
        <i class="ti ti-bell-off" style="font-size: 2.2rem;"></i>
      </div>
      <h4 class="text-bold text-dark mb-1" style="font-size: 1.05rem;">Henüz Bildirim Yok</h4>
      <p class="text-muted text-xs mb-0 px-4">Herhangi bir yeni duyuru veya bildirim aldığınızda burada listelenecektir.</p>
    </div>
  <?php else: ?>
    <!-- Bildirim Listesi -->
    <div class="mobile-card p-0 overflow-hidden mb-5" style="border-radius: 20px; background: #fff; border: 1px solid #eef2f7; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
      <div class="list-group list-group-flush divide-y" id="notifications-list-group">
        <?php foreach ($duyurular as $d): 
          $okundu = !$is_superadmin && !empty($d->okundu_at);
          $oncelik_classes = [
              'acil' => ['bg' => 'bg-red-lt', 'text' => 'text-red', 'dot' => 'bg-red'],
              'onemli' => ['bg' => 'bg-orange-lt', 'text' => 'text-orange', 'dot' => 'bg-orange']
          ];
          $style_cfg = $oncelik_classes[$d->oncelik] ?? ['bg' => 'bg-blue-lt', 'text' => 'text-blue', 'dot' => 'bg-blue'];
          
          $is_avans = (trim($d->baslik) === 'Yeni Avans Talebi');
        ?>
          <div class="list-group-item notification-item-wrapper py-3 px-3 swipe-container" 
               data-id="<?= \App\Helper\Security::encrypt($d->id) ?>" 
               data-okundu="<?= $okundu ? '1' : '0' ?>"
               data-is-avans="<?= $is_avans ? '1' : '0' ?>"
               style="cursor: pointer; background: <?= $okundu ? 'transparent' : 'rgba(32, 107, 196, 0.02)' ?>; transition: background 0.2s ease;">
            <div class="d-flex align-items-start gap-3">
              <!-- Öncelik / Bildirim İkonu -->
              <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $style_cfg['bg'] ?> <?= $style_cfg['text'] ?>" style="width: 40px; height: 40px; border: 1.5px solid rgba(0,0,0,0.02);">
                <i class="ti <?= $is_avans ? 'ti-cash-banknote' : 'ti-bell' ?>" style="font-size: 1.25rem;"></i>
              </div>
              
              <div class="flex-1 min-w-0">
                <div class="d-flex align-items-center justify-content-between gap-2">
                  <h4 class="mb-0 text-truncate text-bold <?= $okundu ? 'text-secondary fw-normal' : 'text-dark fw-bold' ?>" style="font-size: 0.9rem; line-height: 1.2;">
                    <?= htmlspecialchars($d->baslik) ?>
                  </h4>
                  <!-- Öncelik Animasyonlu Noktası (Okunmadıysa) -->
                  <?php if (!$okundu): ?>
                    <span class="status-dot status-dot-animated <?= $style_cfg['dot'] ?> flex-shrink-0"></span>
                  <?php endif; ?>
                </div>
                
                <p class="text-xs mt-1 mb-2 <?= $okundu ? 'text-secondary opacity-75' : 'text-secondary' ?>" style="line-height: 1.4; word-wrap: break-word;">
                  <?= htmlspecialchars($d->icerik) ?>
                </p>
                
                <div class="d-flex align-items-center justify-content-between">
                  <span class="text-muted text-xxs"><?= date('d.m.Y H:i', strtotime($d->created_at)) ?></span>
                  <?php if ($is_avans): ?>
                    <span class="text-primary text-xxs text-semibold d-flex align-items-center gap-0.5">
                      Talebi İncele <i class="ti ti-arrow-right" style="font-size: 0.75rem;"></i>
                    </span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    // Bildirime tıklanma olayı
    $(document).on('click', '.notification-item-wrapper', function() {
        var $item = $(this);
        var encId = $item.data('id');
        var okundu = $item.data('okundu');
        var isAvans = $item.data('is-avans');
        
        var nextStep = function() {
            if (isAvans == '1') {
                window.location.href = 'advance-requests';
            } else {
                window.location.reload();
            }
        };

        if (okundu == '1') {
            nextStep();
            return;
        }

        // AJAX ile okundu olarak işaretle (desktop API'sine istek atıyoruz, yolu ../pages/duyurular/api.php)
        $.ajax({
            url: '../pages/duyurular/api.php',
            method: 'POST',
            data: { action: 'okundu', id: encId },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    $item.css('background', 'transparent');
                    $item.data('okundu', '1');
                    $item.find('.status-dot').remove();
                    $item.find('h4').removeClass('fw-bold').addClass('fw-normal text-secondary');
                }
            },
            complete: function() {
                nextStep();
            }
        });
    });

    // Tümünü okundu olarak işaretle
    $(document).on('click', '#btn-mark-all-read', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: 'Tüm bildirimleri okundu olarak işaretlemek istiyor musunuz?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet',
            cancelButtonText: 'İptal',
            confirmButtonColor: '#206bc4'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../pages/duyurular/api.php',
                    method: 'POST',
                    data: { action: 'tumunu_okundu' },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                title: 'Başarılı!',
                                text: 'Tüm bildirimler okundu olarak işaretlendi.',
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            Swal.fire('Hata!', res.message || 'Bir hata oluştu.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Hata!', 'Bağlantı kurulurken bir hata oluştu.', 'error');
                    }
                });
            }
        });
    });
});
</script>
