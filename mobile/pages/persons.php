<?php
// Puantor Mobil - Personel Listesi
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Bordro.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Security;

$personModel = new Persons();
$bordroModel = new Bordro();

$firm_id = $_SESSION['firm_id'] ?? 0;
$persons = $personModel->getPersonsByFirm($firm_id);
?>

<style>
.person-item-wrapper {
    position: relative;
    overflow: hidden;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.05);
}
.person-item-actions {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    display: flex;
    align-items: center;
    background: #d63f3f;
    z-index: 1;
}
.person-item-content {
    position: relative;
    background: #fff;
    z-index: 2;
    transition: transform 0.2s ease-out;
    width: 100%;
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
.search-container {
    position: relative;
}
.search-input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 12px;
    background: #fff;
}
.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
}
</style>

<div class="container px-0">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Personeller</h2>
    <a href="?p=person_add" class="mobile-fab" style="position: static; width: 36px; height: 36px; border-radius: 12px; transform: none; box-shadow: none;">
      <i class="ti ti-plus" style="font-size: 1.2rem;"></i>
    </a>
  </div>

  <div class="search-container mb-3">
    <i class="ti ti-search search-icon"></i>
    <input type="text" id="personSearchInput" class="search-input" placeholder="Personel ara...">
  </div>

  <div class="person-list pb-4" id="personListContainer">
    <?php if(empty($persons)): ?>
      <div class="mobile-card text-center py-5">
        <i class="ti ti-users text-muted mb-2" style="font-size: 2rem;"></i>
        <p class="text-muted text-sm mb-0">Henüz personel eklenmemiş.</p>
      </div>
    <?php else: ?>
      <?php foreach ($persons as $person):
        $balance = $bordroModel->getBalance($person->id);
        $color = Helper::balanceColor($balance);
        $id_encrypted = Security::encrypt($person->id);
        $initials = mb_substr($person->full_name, 0, 2, 'UTF-8');
      ?>
        <div class="person-item-wrapper person-card" data-name="<?php echo strtolower($person->full_name); ?>">
          <div class="person-item-actions">
            <button class="btn-swipe-delete btn-delete-person" data-id="<?php echo $id_encrypted; ?>" data-name="<?php echo htmlspecialchars($person->full_name); ?>">
              <i class="ti ti-trash"></i>
              <span>Sil</span>
            </button>
          </div>
          <div class="person-item-content">
            <div class="mobile-card p-3 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md rounded-circle text-uppercase bg-primary-lt">
                  <?php echo $initials; ?>
                </div>
                <div class="text-truncate">
                  <a href="/index.php?p=persons/manage&id=<?php echo $id_encrypted; ?>" class="text-bold text-decoration-none text-reset d-block text-truncate" style="font-size: 0.95rem;">
                    <?php echo htmlspecialchars($person->full_name); ?>
                  </a>
                  <span class="text-muted text-xs d-block">
                    <?php echo $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka'; ?> • <?php echo htmlspecialchars($person->job ?? 'Görevi Yok'); ?>
                  </span>
                  <span class="text-xs text-semibold mt-1 d-block <?php echo $color; ?>">
                    Bakiye: <?php echo Helper::formattedMoney($balance); ?>
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
$(document).ready(function() {
  // Search
  $('#personSearchInput').on('input', function() {
    const term = $(this).val().toLowerCase();
    $('.person-card').each(function() {
      const name = $(this).data('name');
      $(this).toggle(name.includes(term));
    });
  });

  // Swipe support
  let touchStartX = 0;
  let touchMoveX = 0;
  const swipeThreshold = 70;

  $(document).on('touchstart', '.person-item-content', function(e) {
      touchStartX = e.originalEvent.touches[0].clientX;
      touchMoveX = touchStartX;
      $('.person-item-content').not(this).css('transform', 'translateX(0)');
  });

  $(document).on('touchmove', '.person-item-content', function(e) {
      touchMoveX = e.originalEvent.touches[0].clientX;
      let diff = touchStartX - touchMoveX;
      if (diff > 0) {
          if (diff > swipeThreshold + 20) diff = swipeThreshold + 20;
          $(this).css('transition', 'none').css('transform', 'translateX(-' + diff + 'px)');
      } else {
          $(this).css('transform', 'translateX(0)');
      }
  });

  $(document).on('touchend', '.person-item-content', function(e) {
      let diff = touchStartX - touchMoveX;
      $(this).css('transition', 'transform 0.2s ease-out');
      if (diff > swipeThreshold / 2) {
          $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
      } else {
          $(this).css('transform', 'translateX(0)');
      }
  });

  // Delete
  $(document).on('click', '.btn-delete-person', function(e) {
    const btn = $(this);
    const id = btn.data('id');
    const name = btn.data('name');
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Emin misiniz?',
            text: name + " silinecektir!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63f3f',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api/persons/person.php', {
                    action: 'deletePerson',
                    id: id
                }, function(res) {
                    if (res.status === 'success') {
                        btn.closest('.person-item-wrapper').fadeOut(300, function() { $(this).remove(); });
                        Swal.fire('Silindi!', res.message, 'success');
                    } else {
                        Swal.fire('Hata!', res.message, 'error');
                    }
                }, 'json').fail(function(xhr, status, error) {
                    console.error("Delete request failed:", error);
                    Swal.fire('Hata!', 'Sunucu ile iletişim kurulamadı. (Hata: ' + status + ')', 'error');
                });
            } else {
                btn.closest('.person-item-wrapper').find('.person-item-content').css('transform', 'translateX(0)');
            }
        });
    }
  });
});
</script>
