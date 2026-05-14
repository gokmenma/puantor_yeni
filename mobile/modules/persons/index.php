<style>
:root {
    --person-card-bg: #ffffff;
    --person-card-border: rgba(0, 0, 0, 0.08);
    --person-text-main: #1d273b;
    --person-text-muted: #64748b;
}

body[data-bs-theme="dark"] {
    --person-card-bg: #1e293b;
    --person-card-border: rgba(255, 255, 255, 0.1);
    --person-text-main: #f4f6fa;
    --person-text-muted: #94a3b8;
}

/* Unified Swipe to Delete Styles matching finance and todos perfectly */
.person-item-wrapper {
    position: relative;
    overflow: hidden;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    user-select: none;
}
body[data-bs-theme="dark"] .person-item-wrapper,
body[data-bs-theme="dark"] .person-item-content {
    background: #1e293b !important;
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
.person-item-actions-left {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    display: flex;
    align-items: center;
    background: #f8fafc;
    z-index: 1;
}
body[data-bs-theme="dark"] .person-item-actions-left {
    background: #1e293b;
}
.person-item-content {
    position: relative;
    background: #fff;
    z-index: 2;
    transition: transform 0.2s ease-out;
    width: 100%;
}
.btn-swipe-delete, .btn-swipe-action {
    color: white;
    width: 70px;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    font-size: 0.65rem;
    font-weight: 600;
    text-decoration: none !important;
}
.btn-swipe-action {
    color: #64748b;
    border-right: 1px solid rgba(0,0,0,0.05);
}
body[data-bs-theme="dark"] .btn-swipe-action {
    color: #94a3b8;
    border-right-color: rgba(255,255,255,0.05);
}
.btn-swipe-action i {
    font-size: 1.2rem;
    margin-bottom: 2px;
}
.btn-swipe-action.active-finance { color: #2fb344; }
.btn-swipe-action.active-puantaj { color: #206bc4; }
.btn-swipe-action.active-documents { color: #f59e0b; }

.btn-swipe-delete i {
    font-size: 1.2rem;
    margin-bottom: 2px;
}

/* Custom Search styling */
.search-container {
    position: relative;
}
.search-input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border: 1px solid rgba(0, 0, 0, 0.08);
    border-radius: 14px;
    background: #fff;
    font-size: 0.9rem;
    color: var(--person-text-main);
    transition: all 0.2s;
}
body[data-bs-theme="dark"] .search-input {
    background: #1e293b;
    border-color: rgba(255,255,255,0.1);
}
.search-input:focus {
    outline: none;
    border-color: var(--mobile-primary);
    box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.15);
}
.search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #64748b;
    font-size: 1.1rem;
    pointer-events: none;
}

/* Animated Buttons */
.btn-animate-icon {
    position: relative;
    transition: all 0.2s ease;
    border-radius: 16px;
    text-decoration: none !important;
    overflow: hidden;
}
.btn-animate-icon:active {
    transform: scale(0.96);
}
.icon-animate-pulse {
    animation: pulse-subtle 2s infinite ease-in-out;
}
@keyframes pulse-subtle {
    0% { transform: scale(1); opacity: 0.6; }
    50% { transform: scale(1.15); opacity: 1; }
    100% { transform: scale(1); opacity: 0.6; }
}
@keyframes rotate-subtle {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
.btn-animate-icon:hover .icon-animate-rotate {
    animation: rotate-subtle 2s infinite linear;
}

body[data-bs-theme="dark"] .text-dark { color: #f4f6fa !important; }
</style>

<?php
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

$white_collar = 0;
$blue_collar = 0;
foreach($persons as $p) {
    if($p->wage_type == 1) $white_collar++;
    else $blue_collar++;
}
?>

<div class="container px-0">
  
  <!-- Üst Başlık Alanı -->
  <div class="mb-4 d-flex align-items-center justify-content-between pt-2">
    <div>
      <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Personeller</h2>
      <p class="text-muted text-xs mb-0">Toplam <?php echo count($persons); ?> çalışan kayıtlı.</p>
    </div>
  </div>

  <!-- Üst Özet Alanı (Premium Dual Cards) -->
  <div class="row g-1 mb-3">
    <div class="col-6">
      <a href="#" class="btn btn-animate-icon w-100 p-3 border-0 shadow-sm d-flex flex-column align-items-start gap-1" style="background: rgba(32, 107, 196, 0.1); color: var(--mobile-primary); border-radius: 16px; text-align: left;">
        <div class="d-flex align-items-center justify-content-between w-100">
           <span class="text-bold h3 mb-0"><?php echo $white_collar; ?> <span class="text-xs text-uppercase opacity-75" style="font-size: 0.7rem;">Kişi</span></span>
           <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-animate-rotate" style="opacity: 0.7;">
              <path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
              <path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1"></path>
              <path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
              <path d="M17 10h2a2 2 0 0 1 2 2v1"></path>
              <path d="M5 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"></path>
              <path d="M3 13v-1a2 2 0 0 1 2 -2h2"></path>
           </svg>
        </div>
        <div class="text-xs text-uppercase font-weight-bold" style="font-size: 0.6rem; opacity: 0.8; letter-spacing: 0.5px;">BEYAZ YAKA</div>
      </a>
    </div>
    <div class="col-6">
      <a href="#" class="btn btn-animate-icon w-100 p-3 border-0 shadow-sm d-flex flex-column align-items-start gap-1" style="background: rgba(47, 179, 68, 0.1); color: #2fb344; border-radius: 16px; text-align: left;">
        <div class="d-flex align-items-center justify-content-between w-100">
           <span class="text-bold h3 mb-0"><?php echo $blue_collar; ?> <span class="text-xs text-uppercase opacity-75" style="font-size: 0.7rem;">Kişi</span></span>
           <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-animate-pulse" style="opacity: 0.7;">
              <path d="M7 5m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v10a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z"></path>
              <path d="M12 11l0 .01"></path>
              <path d="M12 15l0 .01"></path>
              <path d="M12 7l0 .01"></path>
           </svg>
        </div>
        <div class="text-xs text-uppercase font-weight-bold" style="font-size: 0.6rem; opacity: 0.8; letter-spacing: 0.5px;">MAVİ YAKA</div>
      </a>
    </div>
  </div>

  <!-- Arama Çubuğu -->
  <div class="search-container mb-3">
    <i class="ti ti-search search-icon"></i>
    <input type="text" id="personSearchInput" class="search-input shadow-sm" placeholder="Personel ara...">
  </div>

  <!-- Personel Listesi (Clean list group styling with swipe support) -->
  <div class="list-group list-group-mobile shadow-sm" id="personListContainer">
    <?php if (empty($persons)): ?>
      <div class="text-center py-5 bg-white rounded-3 border">
        <i class="ti ti-users-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
        <p class="text-muted text-sm mb-0">Henüz personel eklenmemiş.</p>
      </div>
    <?php else: ?>
      <?php foreach ($persons as $person): 
        $balance = $bordroModel->getBalance($person->id);
        $color = Helper::balanceColor($balance);
        $id_encrypted = Security::encrypt($person->id);
        $initials = mb_substr($person->full_name, 0, 2, 'UTF-8');
      ?>
        <div class="person-item-wrapper" data-name="<?php echo strtolower($person->full_name); ?>">
          <!-- Left Actions (Swipe Right) -->
          <div class="person-item-actions-left">
            <a href="index.php?route=person-edit&id=<?php echo $id_encrypted; ?>&tab=finance" class="btn-swipe-action active-finance">
              <i class="ti ti-receipt"></i>
              <span>Finans</span>
            </a>
            <a href="index.php?route=person-edit&id=<?php echo $id_encrypted; ?>&tab=puantaj" class="btn-swipe-action active-puantaj">
              <i class="ti ti-calendar-event"></i>
              <span>Puantaj</span>
            </a>
            <a href="index.php?route=person-edit&id=<?php echo $id_encrypted; ?>&tab=documents" class="btn-swipe-action active-documents">
              <i class="ti ti-files"></i>
              <span>Evrak</span>
            </a>
          </div>
          
          <!-- Right Actions (Swipe Left) -->
          <div class="person-item-actions">
            <button class="btn-swipe-delete btn-delete-person" data-id="<?php echo $id_encrypted; ?>" data-name="<?php echo htmlspecialchars($person->full_name); ?>">
              <i class="ti ti-trash"></i>
              <span>Sil</span>
            </button>
          </div>
          <div class="person-item-content">
            <a href="index.php?route=person-edit&id=<?php echo $id_encrypted; ?>" class="list-group-item border-0 py-3 px-3 w-100 bg-transparent d-flex align-items-center justify-content-between" style="text-decoration: none; color: inherit;">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(32, 107, 196, 0.12); color: var(--mobile-primary); width: 42px; height: 42px;">
                  <span class="text-bold" style="font-size: 0.85rem;"><?php echo $initials; ?></span>
                </div>
                <div>
                  <div class="text-bold text-sm text-dark"><?php echo htmlspecialchars($person->full_name); ?></div>
                  <div class="text-muted text-xs mt-0.5"><?php echo htmlspecialchars($person->job ?: 'Çalışan'); ?> • <?php echo $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka'; ?></div>
                </div>
              </div>
              <div class="text-end">
                <div class="text-bold text-sm <?php echo $color; ?>" style="font-size: 0.9rem;">
                  ₺ <?php echo Helper::formattedMoneyWithoutCurrency($balance); ?>
                </div>
                <div class="text-muted text-xs font-weight-bold opacity-75" style="font-size: 0.65rem;">BAKİYE</div>
              </div>
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Floating Action Button (FAB) matches finance page FAB perfectly -->
<a href="person-add" class="mobile-fab">
  <i class="ti ti-plus"></i>
</a>

<script>
$(document).ready(function() {
  // Real-time search function (FIXED - search directly on wrapper element data-name)
  $('#personSearchInput').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $('#personListContainer .person-item-wrapper').filter(function() {
      $(this).toggle($(this).data('name').indexOf(value) > -1)
    });
  });

  // Smooth swipe gestures matching finance and todos perfectly
  let touchStartX = 0;
  let touchStartY = 0;
  let touchMoveX = 0;
  let touchMoveY = 0;
  let isHorizontalSwipe = false;
  let isVerticalScroll = false;
  const swipeThresholdLeft = 70;  // For delete
  const swipeThresholdRight = 210; // For 3 buttons (70 * 3)
  const minMovement = 10;

  $(document).on('touchstart', '.person-item-content', function(e) {
      touchStartX = e.originalEvent.touches[0].clientX;
      touchStartY = e.originalEvent.touches[0].clientY;
      touchMoveX = touchStartX;
      touchMoveY = touchStartY;
      isHorizontalSwipe = false;
      isVerticalScroll = false;
      
      $('.person-item-content').not($(this)).css('transition', 'transform 0.2s ease-out').css('transform', 'translateX(0)');
  });

  $(document).on('touchmove', '.person-item-content', function(e) {
      touchMoveX = e.originalEvent.touches[0].clientX;
      touchMoveY = e.originalEvent.touches[0].clientY;
      
      let diffX = touchStartX - touchMoveX;
      let diffY = Math.abs(touchStartY - touchMoveY);

      if (isVerticalScroll) return;

      if (!isHorizontalSwipe && !isVerticalScroll) {
          if (Math.abs(diffX) > minMovement && Math.abs(diffX) > diffY) {
              isHorizontalSwipe = true;
          } else if (diffY > minMovement) {
              isVerticalScroll = true;
              return;
          }
      }

      if (isHorizontalSwipe) {
          if (e.cancelable) e.preventDefault();
          
          $(this).css('transition', 'none');
          if (diffX > 0) { // Swiping Left (reveal Delete)
              let moveAmount = diffX;
              if (moveAmount > swipeThresholdLeft + 20) moveAmount = swipeThresholdLeft + 20;
              $(this).css('transform', 'translateX(-' + moveAmount + 'px)');
          } else { // Swiping Right (reveal Actions)
              let absDiff = Math.abs(diffX);
              if (absDiff > swipeThresholdRight + 20) absDiff = swipeThresholdRight + 20;
              $(this).css('transform', 'translateX(' + absDiff + 'px)');
          }
      }
  });

  $(document).on('touchend', '.person-item-content', function(e) {
      if (!isHorizontalSwipe) {
          if (!isVerticalScroll) {
              $(this).css('transition', 'transform 0.2s ease-out').css('transform', 'translateX(0)');
          }
          return;
      }

      let diffX = touchStartX - touchMoveX;
      $(this).css('transition', 'transform 0.2s ease-out');
      
      if (diffX > swipeThresholdLeft / 2) {
          $(this).css('transform', 'translateX(-' + swipeThresholdLeft + 'px)');
      } else if (diffX < -(swipeThresholdRight / 4)) {
          $(this).css('transform', 'translateX(' + swipeThresholdRight + 'px)');
      } else {
          $(this).css('transform', 'translateX(0)');
      }
  });

  // Close swipe on click elsewhere
  $(document).on('touchstart', function(e) {
      if (!$(e.target).closest('.person-item-wrapper').length) {
          $('.person-item-content').css('transition', 'transform 0.2s ease-out').css('transform', 'translateX(0)');
      }
  });

  // Swipe Action Delete Personnel
  $(document).on('click', '.btn-delete-person', function(e) {
    e.preventDefault();
    const btn = $(this);
    const id = btn.data('id');
    const name = btn.data('name');
    
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'Emin misiniz?',
            text: name + " isimli personeli silmek istediğinize emin misiniz?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d63f3f',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç',
            reverseButtons: true,
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return $.ajax({
                    url: 'api/persons/person.php',
                    type: 'POST',
                    data: {
                        action: 'deletePerson',
                        id: id
                    },
                    dataType: 'json'
                }).then(res => {
                    if (res.status === 'success') {
                        return res;
                    } else {
                        throw new Error(res.message || 'Bir hata oluştu');
                    }
                }).catch(error => {
                    Swal.showValidationMessage(`Hata: ${error.message || error.statusText}`);
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                btn.closest('.person-item-wrapper').fadeOut(300, function() {
                    $(this).remove();
                });
                Swal.fire({
                    title: 'Silindi!',
                    text: result.value.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
            } else {
                // Reset swipe translation
                btn.closest('.person-item-wrapper').find('.person-item-content').css('transform', 'translateX(0)');
            }
        });
    }
  });
});
</script>
