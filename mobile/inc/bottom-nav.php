<?php
// Mobil Sabit Alt Menü (Sticky Bottom Nav)
$active_page = $active_page ?? 'home';
?>
<nav class="app-nav">
  <?php if (!empty($is_superadmin)): ?>
  <a href="dashboard" class="nav-item <?php echo ($active_page == 'home') ? 'active' : ''; ?>">
    <i class="ti ti-shield-lock"></i>
    <span>Sistem</span>
  </a>
  <a href="tickets" class="nav-item <?php echo ($route === 'tickets' || $route === 'ticket-view') ? 'active' : ''; ?>">
    <i class="ti ti-headset"></i>
    <span>Destek</span>
  </a>
  <a href="abonelik-islemleri" class="nav-item <?php echo ($route === 'abonelik-islemleri') ? 'active' : ''; ?>">
    <i class="ti ti-building-community"></i>
    <span>Abonelik</span>
  </a>
  <a href="notifications" class="nav-item <?php echo ($route === 'notifications') ? 'active' : ''; ?>">
    <i class="ti ti-bell"></i>
    <span>Bildirim</span>
  </a>
  <a href="more" class="nav-item <?php echo ($route === 'more' || $route === 'settings' || $route === 'profile') ? 'active' : ''; ?>">
    <i class="ti ti-settings"></i>
    <span>Menü</span>
  </a>
  <?php else: ?>
  <a href="home" class="nav-item <?php echo ($active_page == 'home') ? 'active' : ''; ?>">
    <i class="ti ti-smart-home"></i>
    <span>Ana Sayfa</span>
  </a>

  <?php 
  $persons_auth = $Auths->getAuthIdByTitle("Personeller");
  if (!$persons_auth || $Auths->AuthorizeByAuthId($persons_auth->id)): ?>
  <a href="persons" class="nav-item <?php echo ($active_page == 'persons') ? 'active' : ''; ?>">
    <i class="ti ti-users"></i>
    <span>Personel</span>
  </a>
  <?php endif; ?>

  <?php 
  $puantaj_auth = $Auths->getAuthIdByTitle("Puantaj");
  if (!$puantaj_auth || $Auths->AuthorizeByAuthId($puantaj_auth->id)): ?>
  <a href="puantaj" class="nav-item <?php echo ($active_page == 'puantaj') ? 'active' : ''; ?>">
    <i class="ti ti-calendar-event"></i>
    <span>Puantaj</span>
  </a>
  <?php endif; ?>

  <?php 
  $finance_auth = $Auths->getAuthIdByTitle("Finans");
  if (!$finance_auth || $Auths->AuthorizeByAuthId($finance_auth->id)): ?>
  <a href="finance" class="nav-item <?php echo ($active_page == 'more' && strpos($_SERVER['REQUEST_URI'], 'finance') !== false || $active_page == 'finance') ? 'active' : ''; ?>">
    <i class="ti ti-wallet"></i>
    <span>Kasa</span>
  </a>
  <?php endif; ?>

  <a href="more" class="nav-item <?php echo ($active_page == 'more' && strpos($_SERVER['REQUEST_URI'], 'finance') === false) ? 'active' : ''; ?>">
    <i class="ti ti-grid-pattern"></i>
    <span>Menü</span>
  </a>
  <?php endif; ?>
</nav>
