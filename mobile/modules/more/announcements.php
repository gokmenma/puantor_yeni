<?php
// Puantor Mobil - Duyurular Listesi
require_once ROOT . "/Model/DuyuruModel.php";

$Duyuru = new DuyuruModel();
$kullanici_id = $_SESSION['user']->id ?? 0;
$firma_id = $_SESSION['firm_id'] ?? 0;
$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$is_main_user = !$is_superadmin && (($_SESSION['user']->parent_id ?? 1) == 0);

$duyurular = [];
try {
    $duyurular = $Duyuru->getDuyurular($kullanici_id, $firma_id, $is_superadmin, $is_main_user, 'duyuru');
} catch (Exception $e) {
    // duyurular tablosu yok vs
}
?>

<style>
.notification-content p {
  margin-bottom: 0.35rem;
}
.notification-content p:last-child {
  margin-bottom: 0;
}
.notification-content img {
  max-width: 100%;
  height: auto;
  border-radius: 8px;
  margin: 6px 0;
  display: block;
}
</style>

<div class="container px-2">
  <!-- Geri Dönüş ve Başlık -->
  <div class="d-flex align-items-center justify-content-between mb-4 mt-2">
    <div class="d-flex align-items-center gap-2">
      <a href="javascript:history.back()" class="btn btn-icon btn-sm btn-outline-secondary border-0 btn-active-scale">
        <i class="ti ti-arrow-left" style="font-size: 1.35rem;"></i>
      </a>
      <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Duyurular</h2>
    </div>
    <?php if (!empty($duyurular)): ?>
      <button id="btn-mark-all-read" class="btn btn-sm btn-ghost-primary rounded-pill px-3 py-1 btn-active-scale" style="font-size: 0.8rem;">
        <i class="ti ti-checks me-1"></i> Tümünü Oku
      </button>
    <?php endif; ?>
  </div>

  <?php if (empty($duyurular)): ?>
    <!-- Boş Duyuru Ekranı -->
    <div class="mobile-card text-center py-5 px-3 d-flex flex-column align-items-center justify-content-center" style="border-radius: 20px; background: #fff; min-height: 250px;">
      <div class="avatar avatar-xl rounded-circle bg-secondary-lt mb-3 text-secondary" style="width: 72px; height: 72px;">
        <i class="ti ti-speakerphone-off" style="font-size: 2.2rem;"></i>
      </div>
      <h4 class="text-bold text-dark mb-1" style="font-size: 1.05rem;">Henüz Duyuru Yok</h4>
      <p class="text-muted text-xs mb-0 px-4">Yayınlanmış herhangi bir duyuru bulunduğunda burada görebilirsiniz.</p>
    </div>
  <?php else: ?>
    <!-- Duyuru Listesi -->
    <div class="mobile-card p-0 overflow-hidden mb-5" style="border-radius: 20px; background: #fff; border: 1px solid #eef2f7; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
      <div class="list-group list-group-flush divide-y" id="announcements-list-group">
        <?php foreach ($duyurular as $d): 
          $okundu = !$is_superadmin && !empty($d->okundu_at);
          $oncelik_classes = [
              'acil' => ['bg' => 'bg-red-lt', 'text' => 'text-red', 'dot' => 'bg-red'],
              'onemli' => ['bg' => 'bg-orange-lt', 'text' => 'text-orange', 'dot' => 'bg-orange']
          ];
          $style_cfg = $oncelik_classes[$d->oncelik] ?? ['bg' => 'bg-blue-lt', 'text' => 'text-blue', 'dot' => 'bg-blue'];
        ?>
          <div class="list-group-item notification-item-wrapper py-3 px-3 swipe-container" 
               data-id="<?= \App\Helper\Security::encrypt($d->id) ?>" 
               data-okundu="<?= $okundu ? '1' : '0' ?>"
               style="cursor: pointer; background: <?= $okundu ? 'transparent' : 'rgba(32, 107, 196, 0.02)' ?>; transition: background 0.2s ease;">
            <div class="d-flex align-items-start gap-3">
              <!-- İkon -->
              <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 <?= $style_cfg['bg'] ?> <?= $style_cfg['text'] ?>" style="width: 40px; height: 40px; border: 1.5px solid rgba(0,0,0,0.02);">
                <i class="ti ti-speakerphone" style="font-size: 1.25rem;"></i>
              </div>
              
              <div class="flex-1 min-w-0">
                <div class="d-flex align-items-center justify-content-between gap-2">
                  <h4 class="mb-0 text-truncate text-bold <?= $okundu ? 'text-secondary fw-normal' : 'text-dark fw-bold' ?>" style="font-size: 0.9rem; line-height: 1.2;">
                    <?= htmlspecialchars($d->baslik) ?>
                  </h4>
                  <!-- Nokta -->
                  <?php if (!$okundu): ?>
                    <span class="status-dot status-dot-animated <?= $style_cfg['dot'] ?> flex-shrink-0"></span>
                  <?php endif; ?>
                </div>
                
                <div class="notification-content text-xs mt-1 mb-2 <?= $okundu ? 'text-secondary opacity-75' : 'text-secondary' ?>" style="line-height: 1.4; word-break: break-word;">
                  <?= $d->icerik ?>
                </div>
                
                <div class="d-flex align-items-center justify-content-between">
                  <span class="text-muted text-xxs"><?= date('d.m.Y H:i', strtotime($d->created_at)) ?></span>
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
    // Duyuruya tıklanma olayı
    $(document).on('click', '.notification-item-wrapper', function() {
        var $item = $(this);
        var encId = $item.data('id');
        var okundu = $item.data('okundu');
        
        if (okundu == '1') return;

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
            }
        });
    });

    // Tümünü okundu işaretle
    $(document).on('click', '#btn-mark-all-read', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: 'Tüm duyuruları okundu olarak işaretlemek istiyor musunuz?',
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
                    data: { action: 'tumunu_okundu', type: 'duyuru' },
                    dataType: 'json',
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                title: 'Başarılı!',
                                text: 'Tüm duyurular okundu olarak işaretlendi.',
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
