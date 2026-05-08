<?php
// Mobil Sabit Alt Menü (Sticky Bottom Nav)
$active_page = $active_page ?? 'home';
?>
<nav class="app-nav">
  <a href="home" class="nav-item <?php echo ($active_page == 'home') ? 'active' : ''; ?>">
    <i class="ti ti-smart-home"></i>
    <span>Ana Sayfa</span>
  </a>

  <a href="persons" class="nav-item <?php echo ($active_page == 'persons') ? 'active' : ''; ?>">
    <i class="ti ti-users"></i>
    <span>Personel</span>
  </a>

  <a href="puantaj" class="nav-item <?php echo ($active_page == 'puantaj') ? 'active' : ''; ?>">
    <i class="ti ti-calendar-event"></i>
    <span>Puantaj</span>
  </a>

  <a href="finance" class="nav-item <?php echo ($active_page == 'more' && strpos($_SERVER['REQUEST_URI'], 'finance') !== false || $active_page == 'finance') ? 'active' : ''; ?>">
    <i class="ti ti-wallet"></i>
    <span>Kasa</span>
  </a>

  <a href="more" class="nav-item <?php echo ($active_page == 'more' && strpos($_SERVER['REQUEST_URI'], 'finance') === false) ? 'active' : ''; ?>">
    <i class="ti ti-grid-pattern"></i>
    <span>Menü</span>
  </a>
</nav>
