<?php
// Puantor Mobil - Bordro Listesi (Kasa Tasarımı Uyumlu)
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/Projects.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$bordroModel = new Bordro();
$personModel = new Persons();
$projectModel = new Projects();

$view = $_GET['view'] ?? 'periods';
$year = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('m'));
$firm_id = $_SESSION['firm_id'];

// Başlık ve Geri Butonu Kontrolü
$back_url = "index.php?route=payroll";
if ($view == 'personnel') {
    $title = Date::monthName($month) . " " . $year;
    $back_url = "index.php?route=payroll&view=periods&year=$year";
} elseif ($view == 'detail') {
    $person_id = Security::decrypt($_GET['id']);
    $person = $personModel->find($person_id);
    $title = "Bordro Özeti";
    $back_url = "index.php?route=payroll&view=personnel&year=$year&month=$month";
} else {
    $title = "Bordrolar";
}
?>

<div class="container px-2">
  
  <!-- Üst Başlık Alanı -->
  <div class="mb-2 d-flex align-items-center justify-content-between pt-2 px-1">
    <div class="d-flex align-items-center">
      <?php if ($view != 'periods'): ?>
        <a href="<?php echo $back_url; ?>" class="btn btn-icon btn-ghost-secondary me-2 rounded-circle btn-active-scale">
          <i class="ti ti-chevron-left fs-2"></i>
        </a>
      <?php endif; ?>
      <div>
        <h2 class="mb-0 text-bold" style="letter-spacing: -0.8px; font-size: 1.5rem;"><?php echo $title; ?></h2>
        <p class="text-muted text-xs mb-0">
          <?php if ($view == 'periods'): ?>Hakediş ve ödeme dökümleri.<?php elseif ($view == 'personnel'): ?>Dönem bazlı çalışan listesi.<?php elseif ($view == 'detail'): ?><?php echo $person->full_name; ?><?php endif; ?>
        </p>
      </div>
    </div>
    
    <?php if ($view == 'periods'): ?>
      <div class="dropdown">
        <button class="btn btn-sm btn-white border shadow-sm rounded-pill px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown">
          <i class="ti ti-calendar-event me-1 text-primary"></i> <?php echo $year; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4">
          <?php for($y = date('Y'); $y >= 2022; $y--): ?>
            <li><a class="dropdown-item py-2" href="index.php?route=payroll&year=<?php echo $y; ?>"><?php echo $y; ?></a></li>
          <?php endfor; ?>
        </ul>
      </div>
    <?php elseif ($view == 'personnel'): ?>
       <button class="btn btn-sm btn-primary shadow-sm rounded-pill px-3 btn-active-scale" id="btn-recalculate">
         <i class="ti ti-refresh me-1"></i> Hesapla
       </button>
    <?php endif; ?>
  </div>

  <?php if ($view == 'periods'): ?>
    <!-- DÖNEM LİSTESİ -->
    <?php
    // Kasa Tasarımı Uyumlu Aktif Dönem Özeti Hesaplaması
    $active_month = ($year == date('Y')) ? date('n') : 12;
    $active_lastDay = Date::lastDay($active_month, $year);
    $active_firstDay = Date::firstDay($active_month, $year);
    $active_persons = $personModel->getPersonIdByFirmCurrentMonth($firm_id, $active_lastDay);
    
    $active_total_gelir = 0;
    $active_total_gider = 0;
    foreach($active_persons as $p) {
        $s = $bordroModel->getPersonSalaryAndWageCut($p->id, $active_firstDay, $active_lastDay);
        $active_total_gelir += $s->gelir ?? 0;
        $active_total_gider += $s->odeme ?? 0;
    }
    $active_total_kalan = $active_total_gelir - $active_total_gider;
    ?>

    <!-- Aktif Dönem Özeti Kartı (Kasa Bakiyesi Kartı Tasarımı) -->
    <div class="mobile-card bg-primary text-white p-4 mb-4 position-relative overflow-hidden" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #206bc4 0%, #104b8c 100%) !important;">
      <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
        <i class="ti ti-calendar-event"></i>
      </div>
      <div class="d-flex align-items-center justify-content-between mb-2">
        <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;"><?php echo Date::monthName($active_month); ?> <?php echo $year; ?> (Aktif Dönem)</span>
        <i class="ti ti-calendar-event" style="font-size: 1.5rem; opacity: 0.8;"></i>
      </div>
      <h3 class="mb-0 text-bold" style="font-size: 2.2rem; letter-spacing: -1px;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($active_total_gelir); ?></h3>
      
      <div class="mt-2 d-flex align-items-center justify-content-between">
        <span class="badge bg-white-10 text-white text-xs d-flex align-items-center gap-1" style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 4px 10px;">
          <i class="ti ti-users"></i>
          <?php echo count($active_persons); ?> Personel
        </span>
      </div>

      <!-- Entegre Toplam Ödeme ve Kalan Ödeme Bilgileri -->
      <div class="row g-2 mt-3 pt-3" style="border-top: 1px solid rgba(255,255,255,0.15) !important;">
        <div class="col-6">
          <div class="text-white-50 text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.6rem; opacity: 0.85;">TOPLAM ÖDENEN</div>
          <div class="h4 mb-0 text-bold text-white">₺ <?php echo Helper::formattedMoneyWithoutCurrency($active_total_gider); ?></div>
        </div>
        <div class="col-6 ps-3" style="border-left: 1px solid rgba(255,255,255,0.15) !important;">
          <div class="text-white-50 text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.6rem; opacity: 0.85;">KALAN ÖDEME</div>
          <div class="h4 mb-0 text-bold text-white">₺ <?php echo Helper::formattedMoneyWithoutCurrency($active_total_kalan); ?></div>
        </div>
      </div>
    </div>

    <h4 class="mb-3 text-semibold px-1" style="font-size: 0.95rem;">Dönem Listesi</h4>
    <div class="list-group list-group-mobile shadow-sm mb-4">
      <?php 
      $current_month = date('n');
      $current_year = date('Y');
      for($m = 12; $m >= 1; $m--): 
        if ($year == $current_year && $m > $current_month) continue;
        $lastDay = Date::lastDay($m, $year);
        $person_count = count($personModel->getPersonIdByFirmCurrentMonth($firm_id, $lastDay));
        $is_active = ($m == $current_month && $year == $current_year);
      ?>
        <a href="index.php?route=payroll&view=personnel&year=<?php echo $year; ?>&month=<?php echo $m; ?>" 
           class="list-group-item d-flex align-items-center justify-content-between py-3 text-decoration-none">
          <div class="d-flex align-items-center gap-3">
            <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center bg-primary-lt text-primary" style="width: 40px; height: 40px;">
              <i class="ti ti-calendar-event fs-2"></i>
            </div>
            <div>
              <div class="text-bold text-sm text-dark"><?php echo Date::monthName($m); ?></div>
              <div class="text-muted text-xs mt-0.5"><?php echo $year; ?> Finansal Dönemi</div>
            </div>
          </div>
          <div class="text-end">
            <div class="text-bold text-primary text-sm"><?php echo $person_count; ?></div>
            <div class="text-muted text-xs font-weight-bold opacity-75" style="font-size: 0.65rem;">PERSONEL</div>
          </div>
        </a>
      <?php endfor; ?>
    </div>
  <?php elseif ($view == 'personnel'): ?>
    <!-- PERSONEL LİSTESİ -->
    <?php
    $lastDay = Date::lastDay($month, $year);
    $firstDay = Date::firstDay($month, $year);
    $persons = $personModel->getPersonIdByFirmCurrentMonth($firm_id, $lastDay);
    
    // Toplam Hakediş ve Kesinti Hesapla (Özet Kartı İçin)
    $total_all_gelir = 0;
    $total_all_gider = 0;
    foreach($persons as $p) {
        $s = $bordroModel->getPersonSalaryAndWageCut($p->id, $firstDay, $lastDay);
        $total_all_gelir += $s->gelir ?? 0;
        $total_all_gider += $s->odeme ?? 0;
    }
    ?>

    <!-- Kasa Tasarımı Özet Kartları -->
    <div class="row g-1 mb-2">
      <div class="col-6">
        <div class="mobile-card p-3 mb-0 border-0 shadow-sm" style="background: rgba(47, 179, 68, 0.1); color: #2fb344; border-radius: 16px;">
          <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">TOPLAM HAKEDİŞ</div>
          <div class="text-bold h3 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_all_gelir); ?></div>
        </div>
      </div>
      <div class="col-6">
        <div class="mobile-card p-3 mb-0 border-0 shadow-sm" style="background: rgba(214, 63, 63, 0.1); color: #d63f3f; border-radius: 16px;">
          <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">TOPLAM KESİNTİ</div>
          <div class="text-bold h3 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_all_gider); ?></div>
        </div>
      </div>
    </div>
    
    <div class="search-container mb-3 px-1">
      <i class="ti ti-search search-icon"></i>
      <input type="text" id="person-search" class="search-input shadow-sm" placeholder="Personel ara...">
    </div>

    <div class="list-group list-group-mobile shadow-sm" id="person-list">
      <?php foreach ($persons as $item): 
        $person = $personModel->find($item->id);
        $summary = $bordroModel->getPersonSalaryAndWageCut($person->id, $firstDay, $lastDay);
        $kalan = ($summary->gelir ?? 0) - ($summary->odeme ?? 0);
      ?>
        <a href="index.php?route=payroll&view=detail&year=<?php echo $year; ?>&month=<?php echo $month; ?>&id=<?php echo Security::encrypt($person->id); ?>" 
           class="list-group-item person-item border-0 border-bottom py-3 px-3" data-name="<?php echo strtolower($person->full_name); ?>">
          <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center border border-white shadow-sm" style="background: rgba(32, 107, 196, 0.1); color: var(--mobile-primary); width: 42px; height: 42px;">
                <span class="text-bold" style="font-size: 0.85rem;"><?php echo Helper::getInitials($person->full_name); ?></span>
              </div>
              <div>
                <div class="text-bold text-sm text-dark"><?php echo $person->full_name; ?></div>
                <div class="text-muted text-xs mt-0.5"><?php echo $person->job; ?></div>
              </div>
            </div>
            <div class="text-end">
              <div class="text-bold text-sm <?php echo $kalan > 0 ? 'text-danger' : ($kalan < 0 ? 'text-green' : 'text-muted'); ?>">
                ₺ <?php echo Helper::formattedMoneyWithoutCurrency($kalan); ?>
              </div>
              <div class="text-muted text-xs font-weight-bold opacity-75" style="font-size: 0.65rem;">KALAN</div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

  <?php elseif ($view == 'detail'): ?>
    <!-- BORDRO DETAYI -->
    <?php
    $person_id = Security::decrypt($_GET['id']);
    $person = $personModel->find($person_id);
    $gelir_list = $bordroModel->getPersonIncome($person_id, $month, $year);
    $gider_list = $bordroModel->getPersonExpense($person_id, $month, $year);
    $total_gelir = array_sum(array_column($gelir_list, 'tutar'));
    $total_gider = array_sum(array_column($gider_list, 'tutar'));
    ?>

    <div class="mobile-card bg-primary text-white p-4 mb-4 position-relative overflow-hidden shadow-lg border-0" style="border-radius: 20px; background: linear-gradient(135deg, #206bc4 0%, #104b8c 100%) !important;">
      <div class="d-flex align-items-center gap-3 mb-4">
        <div class="avatar avatar-lg rounded-circle bg-white text-primary text-bold shadow-sm border border-white-subtle">
          <?php echo Helper::getInitials($person->full_name); ?>
        </div>
        <div>
          <h3 class="mb-0 text-white text-bold" style="font-size: 1.4rem;"><?php echo $person->full_name; ?></h3>
          <div class="text-white opacity-75 text-xs"><?php echo $person->job; ?></div>
        </div>
      </div>
      <div class="row g-2">
        <div class="col-4">
          <div class="text-white-50 text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.6rem;">HAKEDİŞ</div>
          <div class="h4 mb-0 text-bold">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_gelir); ?></div>
        </div>
        <div class="col-4 border-start border-white-subtle ps-3">
          <div class="text-white-50 text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.6rem;">KESİNTİ</div>
          <div class="h4 mb-0 text-bold">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_gider); ?></div>
        </div>
        <div class="col-4 border-start border-white-subtle ps-3">
          <div class="text-white-50 text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.6rem;">KALAN</div>
          <div class="h4 mb-0 text-bold" style="color: #93c5fd;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_gelir - $total_gider); ?></div>
        </div>
      </div>
    </div>

    <!-- Hakedişler -->
    <div class="mb-4">
      <h4 class="mb-2 text-semibold px-1" style="font-size: 0.95rem;">Hakedişler</h4>
      <div class="list-group list-group-mobile shadow-sm">
        <?php foreach($gelir_list as $g): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 border-bottom">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(47, 179, 68, 0.1); color: #2fb344;">
                <i class="ti ti-arrow-up-right" style="font-size: 1.1rem;"></i>
              </div>
              <div>
                <div class="text-bold text-sm text-dark"><?php echo $g->turu; ?></div>
                <div class="text-muted text-xs mt-0.5"><?php echo Date::monthName($g->ay ?: $month); ?> <?php echo $g->yil ?: $year; ?></div>
              </div>
            </div>
            <div class="text-green text-bold text-sm">+ ₺ <?php echo Helper::formattedMoneyWithoutCurrency($g->tutar); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Kesintiler -->
    <div class="mb-5">
      <h4 class="mb-2 text-semibold px-1" style="font-size: 0.95rem;">Kesinti ve Ödemeler</h4>
      <div class="list-group list-group-mobile shadow-sm">
        <?php foreach($gider_list as $g): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0 border-bottom">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(214, 63, 63, 0.1); color: #d63f3f;">
                <i class="ti ti-arrow-down-left" style="font-size: 1.1rem;"></i>
              </div>
              <div>
                <div class="text-bold text-sm text-dark"><?php echo $g->turu; ?></div>
                <div class="text-muted text-xs mt-0.5"><?php echo Date::monthName($g->ay ?: $month); ?> <?php echo $g->yil ?: $year; ?></div>
              </div>
            </div>
            <div class="text-red text-bold text-sm">- ₺ <?php echo Helper::formattedMoneyWithoutCurrency($g->tutar); ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>
$(document).ready(function() {
  $('#person-search').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $('#person-list .person-item').filter(function() {
      $(this).toggle($(this).data('name').indexOf(value) > -1)
    });
  });

  $('#btn-recalculate').on('click', function() {
    Swal.fire({
      title: 'Hesaplanıyor...',
      text: 'Lütfen bekleyiniz, dönem bordrosu güncelleniyor.',
      allowOutsideClick: false,
      showConfirmButton: false,
      didOpen: () => { 
        Swal.showLoading(); 
        setTimeout(() => { location.reload(); }, 1500); 
      }
    });
  });
});
</script>

<style>
.text-bold { font-weight: 700 !important; }
.text-semibold { font-weight: 600 !important; }
.bg-primary-lt { background-color: rgba(32, 107, 196, 0.08) !important; }
.border-white-subtle { border-color: rgba(255,255,255,0.15) !important; }
.avatar-list-stacked .avatar { margin-right: -8px; }
.text-green { color: #2fb344 !important; }
.text-red { color: #d63f3f !important; }
</style>
