<?php
// Mobil Navbar Header
$user = $_SESSION['user'] ?? null;
$theme = $_SESSION['theme'] ?? 'light';
$toggle_theme = ($theme == 'dark') ? 'light' : 'dark';
?>
<header class="app-header">
  <div class="d-flex align-items-center gap-2">
    <!-- Mini Brand Logo -->
    <svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 112 112" style="width: 30px; height: 30px; fill: var(--tblr-primary);">
      <circle cx="56" cy="56" r="48" fill="rgba(32, 107, 196, 0.1)" stroke="var(--tblr-primary)" stroke-width="4"/>
      <path d="M40 75V37h14.5c8 0 13.5 5 13.5 12s-5.5 12-13.5 12H48v14H40zm16-26c0-3.5-2.5-5.5-6.5-5.5H48v11h1.5c4 0 6.5-2 6.5-5.5z" fill="var(--tblr-primary)"/>
    </svg>
    <div class="d-flex flex-column lh-1">
      <span class="text-semibold" style="font-size: 0.92rem; letter-spacing: -0.3px;">Puantor</span>
      <?php if (isset($_SESSION['firm_id'])): ?>
        <?php
          $hdr_firm_name = "Yükleniyor...";
          if (isset($myFirms)) {
              foreach ($myFirms as $f) {
                  if ($f->id == $_SESSION['firm_id']) {
                      $hdr_firm_name = $f->firm_name;
                      break;
                  }
              }
          }
        ?>
        <span class="text-muted" style="font-size: 0.6rem; font-weight: 500; opacity: 0.8; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 120px;"><?php echo htmlspecialchars($hdr_firm_name); ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="d-flex align-items-center gap-3">
    <!-- Theme Switcher -->
    <?php
    $queryParams = $_GET;
    $queryParams['theme'] = $toggle_theme;
    $toggle_url = '?' . http_build_query($queryParams);
    ?>
    <a href="<?php echo htmlspecialchars($toggle_url); ?>" class="btn-active-scale text-reset text-decoration-none">
      <?php if ($theme == 'dark'): ?>
        <i class="ti ti-sun" style="font-size: 1.35rem; color: #f59e0b;"></i>
      <?php else: ?>
        <i class="ti ti-moon" style="font-size: 1.35rem; color: #626976;"></i>
      <?php endif; ?>
    </a>

    <!-- User Profile Dropdown / Indicator -->
    <a href="?p=more" class="btn-active-scale text-decoration-none d-flex align-items-center">
      <span class="avatar avatar-sm rounded-circle text-uppercase" style="background-color: rgba(32, 107, 196, 0.1); color: var(--tblr-primary); font-size: 0.75rem; font-weight: 700;">
        <?php 
          if ($user) {
            echo mb_substr($user->name ?? $user->email ?? 'U', 0, 2, 'UTF-8');
          } else {
            echo 'U';
          }
        ?>
      </span>
    </a>
  </div>
</header>
