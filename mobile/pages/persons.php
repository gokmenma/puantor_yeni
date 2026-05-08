<?php
// Puantor Mobil - Personel Listesi
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Bordro.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;
use App\Helper\Security;

$personModel = new Persons();
$bordroModel = new Bordro();

$firm_id = $_SESSION['firm_id'] ?? 0;
$persons = $personModel->getPersonsByFirm($firm_id);
?>

<div class="container px-0">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Personeller</h2>
    <a href="?p=person_add" class="mobile-fab" style="position: static; width: 36px; height: 36px; border-radius: 12px; transform: none; box-shadow: none;">
      <i class="ti ti-plus" style="font-size: 1.2rem;"></i>
    </a>
  </div>

  <!-- Arama Çubuğu -->
  <div class="search-container">
    <i class="ti ti-search search-icon"></i>
    <input type="text" id="personSearchInput" class="search-input" placeholder="Personel ara...">
  </div>

  <!-- Personel Listesi -->
  <div class="person-list pb-4" id="personListContainer">
    <?php 
    if(empty($persons)): 
    ?>
      <div class="mobile-card text-center py-5">
        <i class="ti ti-users text-muted mb-2" style="font-size: 2rem;"></i>
        <p class="text-muted text-sm mb-0">Henüz personel eklenmemiş.</p>
      </div>
    <?php 
    else:
      foreach ($persons as $person):
        $balance = $bordroModel->getBalance($person->id);
        $color = Helper::balanceColor($balance);
        $id_encrypted = Security::encrypt($person->id);
        $phone_clean = preg_replace('/[^0-9]/', '', $person->phone ?? '');
        $initials = mb_substr($person->full_name, 0, 2, 'UTF-8');
    ?>
        <div class="mobile-card p-3 mb-2 person-card" data-name="<?php echo strtolower($person->full_name); ?>">
          <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3" style="width: calc(100% - 100px);">
              <!-- Profil İkonu -->
              <div class="avatar avatar-md rounded-circle text-uppercase bg-primary-lt">
                <?php echo $initials; ?>
              </div>
              
              <!-- Personel Bilgileri -->
              <div class="text-truncate">
                <a href="/index.php?p=persons/manage&id=<?php echo $id_encrypted; ?>" class="text-bold text-decoration-none text-reset d-block text-truncate" style="font-size: 0.95rem;">
                  <?php echo htmlspecialchars($person->full_name); ?>
                </a>
                <span class="text-muted text-xs d-block text-truncate">
                  <?php echo $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka'; ?> • <?php echo htmlspecialchars($person->job ?? 'Görevi Yok'); ?>
                </span>
                <span class="text-xs text-semibold mt-1 d-block <?php echo $color; ?>">
                  Bakiye: <?php echo Helper::formattedMoney($balance); ?>
                </span>
              </div>
            </div>

            <!-- Aksiyon Butonları -->
            <div class="d-flex gap-2">
              <?php if (!empty($phone_clean)): ?>
                <a href="tel:<?php echo $phone_clean; ?>" class="btn-active-scale btn btn-icon btn-sm btn-outline-success border-0 bg-success-lt rounded-circle" style="width: 36px; height: 36px;">
                  <i class="ti ti-phone"></i>
                </a>
                <a href="https://wa.me/<?php echo $phone_clean; ?>" target="_blank" class="btn-active-scale btn btn-icon btn-sm btn-outline-success border-0 bg-success-lt rounded-circle" style="width: 36px; height: 36px;">
                  <i class="ti ti-brand-whatsapp"></i>
                </a>
              <?php else: ?>
                <span class="btn btn-icon btn-sm btn-outline-secondary border-0 bg-secondary-lt rounded-circle" style="width: 36px; height: 36px; opacity: 0.5;">
                  <i class="ti ti-phone-off"></i>
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>
    <?php 
      endforeach;
    endif; 
    ?>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const searchInput = document.getElementById('personSearchInput');
  const cards = document.querySelectorAll('.person-card');

  if (searchInput) {
    searchInput.addEventListener('input', function(e) {
      const term = e.target.value.toLowerCase();
      cards.forEach(card => {
        const name = card.getAttribute('data-name');
        if (name.includes(term)) {
          card.style.display = 'block';
        } else {
          card.style.display = 'none';
        }
      });
    });
  }
});
</script>
