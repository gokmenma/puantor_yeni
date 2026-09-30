<?php
$initThemePreset = $_COOKIE['app_theme_preset'] ?? 'kode';
$initThemeFont = $_COOKIE['app_theme_font'] ?? 'inter';
$initThemeWeight = $_COOKIE['app_theme_weight'] ?? '400';
$initThemeRadius = $_COOKIE['app_theme_radius'] ?? 'default';
$initTableDensity = $_COOKIE['app_table_density'] ?? 'normal';
$initFontScale = $_COOKIE['app_font_scale'] ?? '100';
$initIconStroke = $_COOKIE['app_icon_stroke'] ?? '1.5';
$initTopbarTheme = $_COOKIE['app_topbar_theme'] ?? 'beyaz';
$initSidebarTheme = $_COOKIE['app_sidebar_theme'] ?? 'klasik-koyu';
$initSidebarActive = $_COOKIE['app_sidebar_active_name'] ?? 'soft-white';
$darkPresetsList = ['koyu-gece', 'gece-altini', 'cyber-neon', 'tokyo-gece', 'dracula-pro', 'midnight-sapphire'];
$initBsTheme = in_array($initThemePreset, $darkPresetsList, true) ? 'dark' : ($_COOKIE['app_theme'] ?? ($_COOKIE['theme'] ?? 'light'));
?>
<!doctype html>
<html lang="tr" data-theme-preset="<?php echo htmlspecialchars($initThemePreset, ENT_QUOTES, 'UTF-8'); ?>" data-theme-font="<?php echo htmlspecialchars($initThemeFont, ENT_QUOTES, 'UTF-8'); ?>" data-theme-weight="<?php echo htmlspecialchars($initThemeWeight, ENT_QUOTES, 'UTF-8'); ?>" data-theme-radius="<?php echo htmlspecialchars($initThemeRadius, ENT_QUOTES, 'UTF-8'); ?>" data-table-density="<?php echo htmlspecialchars($initTableDensity, ENT_QUOTES, 'UTF-8'); ?>" data-font-size-scale="<?php echo htmlspecialchars($initFontScale, ENT_QUOTES, 'UTF-8'); ?>" data-icon-stroke="<?php echo htmlspecialchars($initIconStroke, ENT_QUOTES, 'UTF-8'); ?>" data-topbar-theme="<?php echo htmlspecialchars($initTopbarTheme, ENT_QUOTES, 'UTF-8'); ?>" data-sidebar-theme="<?php echo htmlspecialchars($initSidebarTheme, ENT_QUOTES, 'UTF-8'); ?>" data-sidebar-active="<?php echo htmlspecialchars($initSidebarActive, ENT_QUOTES, 'UTF-8'); ?>" data-bs-theme="<?php echo htmlspecialchars($initBsTheme, ENT_QUOTES, 'UTF-8'); ?>">

<head>
  <meta name="csrf-token" content="<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <?php

  //Aktif sayfadan menü linki veritabanında aranır ve sayfa ismi alınır
  $system_title = $Settings->getSystemSetting("system_title") ?? "Puantor";
  $title = $menu_name->page_name ?? ($system_title . " | Puantaj Takip Uygulaması");

  ?>
  <title><?php echo $title; ?></title>

  <link rel="icon" href="./static/favicon.ico" type="image/x-icon" />

  <!-- Meta Açıklama -->
  <meta name="description"
    content="Puantor, çalışanlarınızın puantajını, maaş hesaplamalarını ve proje takibini kolayca yapmanızı sağlar. Hızlı, güvenilir ve kullanıcı dostu bir platform ile iş süreçlerinizi optimize edin. Daha fazla verimlilik için hemen keşfedin!" />

  <!-- Anahtar Kelimeler -->
  <meta name="keywords"
    content="puantaj yazılımı, maaş hesaplama aracı, proje takibi, gelir gider takibi, personel yönetimi, işletme yönetim yazılımı, verimli iş yönetimi" />

  <!-- Early Theme Initialization (Zero Flicker) -->
  <script src="./dist/js/theme-manager.js?v=<?php echo filemtime("./dist/js/theme-manager.js"); ?>"></script>
  <script>
    (function() {
      try {
        var html = document.documentElement;
        var savedPreset = localStorage.getItem('app_theme_preset') || 'kode';
        html.setAttribute('data-theme-preset', savedPreset);

        var defaultMap = (window.presetTopbarSidebarMap && window.presetTopbarSidebarMap[savedPreset]) ? window.presetTopbarSidebarMap[savedPreset] : { topbar: 'beyaz', sidebar: 'klasik-koyu' };
        var savedTopbar = localStorage.getItem('app_topbar_theme') || defaultMap.topbar;
        var savedSidebar = localStorage.getItem('app_sidebar_theme') || defaultMap.sidebar;
        html.setAttribute('data-topbar-theme', savedTopbar);
        html.setAttribute('data-sidebar-theme', savedSidebar);

        var savedSidebarActiveName = localStorage.getItem('app_sidebar_active_name') || 'soft-white';
        var savedSidebarActiveBg = localStorage.getItem('app_sidebar_active_bg') || 'rgba(255, 255, 255, 0.18)';
        var savedSidebarActiveColor = localStorage.getItem('app_sidebar_active_color') || '#ffffff';
        html.setAttribute('data-sidebar-active', savedSidebarActiveName);
        if (savedSidebarActiveBg) {
          html.style.setProperty('--sidebar-active-bg', savedSidebarActiveBg);
          html.style.setProperty('--sidebar-active-color', savedSidebarActiveColor);
        }

        var savedFont = localStorage.getItem('app_theme_font') || ((window.themePresetFonts && window.themePresetFonts[savedPreset]) ? window.themePresetFonts[savedPreset] : 'inter');
        html.setAttribute('data-theme-font', savedFont);

        var savedWeight = localStorage.getItem('app_theme_weight') || '400';
        html.setAttribute('data-theme-weight', savedWeight);

        var savedRadius = localStorage.getItem('app_theme_radius') || 'default';
        html.setAttribute('data-theme-radius', savedRadius);

        var savedDensity = localStorage.getItem('app_table_density') || 'normal';
        html.setAttribute('data-table-density', savedDensity);

        var savedScale = localStorage.getItem('app_font_scale') || '100';
        html.setAttribute('data-font-size-scale', savedScale);

        var savedStroke = localStorage.getItem('app_icon_stroke') || '1.5';
        html.setAttribute('data-icon-stroke', savedStroke);

        var darkPresets = ['koyu-gece', 'gece-altini', 'cyber-neon', 'tokyo-gece', 'dracula-pro', 'midnight-sapphire'];
        var isDarkPreset = darkPresets.indexOf(savedPreset) !== -1;
        var savedTheme = isDarkPreset ? 'dark' : (localStorage.getItem('theme') || 'light');
        if (!isDarkPreset && localStorage.getItem('app_theme_preset') && localStorage.getItem('theme') !== 'dark') {
          savedTheme = 'light';
        }
        html.setAttribute('data-bs-theme', savedTheme);

        var savedPrimaryColor = localStorage.getItem('app_primary_color');
        var savedPrimaryManual = localStorage.getItem('app_primary_manual');
        if (savedPrimaryColor && savedPrimaryManual === 'true') {
          html.style.setProperty('--theme-primary', savedPrimaryColor);
          html.style.setProperty('--theme-primary-hover', savedPrimaryColor);
          html.style.setProperty('--focus-color', savedPrimaryColor);
          html.style.setProperty('--tblr-primary', savedPrimaryColor);
        }
      } catch(e) {}
    })();
  </script>

  <?php
  if (!empty($_SESSION['user']->id)) {
      try {
          require_once __DIR__ . '/../Model/UserDatatableStateModel.php';
          $dtStateModel = new UserDatatableStateModel();
          $preloadedStates = $dtStateModel->getAllStatesForUser((int)$_SESSION['user']->id);
          echo '<script>window.__PRELOADED_DT_STATES__ = ' . json_encode($preloadedStates, JSON_UNESCAPED_UNICODE) . ';</script>';
      } catch (\Throwable $t) {
          echo '<script>window.__PRELOADED_DT_STATES__ = {};</script>';
      }
  } else {
      echo '<script>window.__PRELOADED_DT_STATES__ = {};</script>';
  }
  ?>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
  <link href="./dist/css/style.css?v=<?php echo filemtime("./dist/css/style.css"); ?>" rel="stylesheet" />
  <link href="./dist/css/menu.css?v=<?php echo filemtime("./dist/css/menu.css"); ?>" rel="stylesheet" />
  <link href="./dist/css/premium-theme.css?v=<?php echo filemtime("./dist/css/premium-theme.css"); ?>" rel="stylesheet" />
  <link href="./dist/libs/select2/css/select2.min.css?v=<?php echo filemtime("./dist/libs/select2/css/select2.min.css"); ?>" rel="stylesheet" />

  <link href="./dist/css/flatpickr.min.css?v=<?php echo filemtime("./dist/css/flatpickr.min.css"); ?>" rel="stylesheet" />
  <link href="./dist/css/flatpickr.monthSelect.css?v=<?php echo filemtime("./dist/css/flatpickr.monthSelect.css"); ?>" rel="stylesheet" />

  <!-- jQuery UI CSS -->
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">

  <!-- manifest.json -->
  <link rel="manifest" href="/manifest.json">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@event-calendar/build@3.7.2/event-calendar.min.css">
  <script src="https://cdn.jsdelivr.net/npm/@event-calendar/build@3.7.2/event-calendar.min.js"></script>

  <?php
  $page = isset($_GET["p"]) ? $_GET["p"] : "";

  if (
    $page == "missions/manage" || $page == "feedback/list"
    || $page == "supports/tickets" || $page == "supports/ticket-view"
    || $page == "supports/admin-tickets" || $page == "supports/admin-ticket-view"
    || $page == "abonelik-islemleri/list"
    || $page == "duyurular/list"
    || $page == "mail-islemleri/index"
  ) {
    echo '<link href="./dist/libs/summernote/summernote-lite.min.css" rel="stylesheet">';
  }

  if (
    $page == "companies/list" || $page == "offers/list" || $page == "reports/list"
    || $page == "users/list" || $page == "users/roles/list" || $page == "products/list"
    || $page == "defines/service-head/list"
    || $page == "persons/list" || $page == "persons/manage"
    || $page == "mycompany/list" || $page == "financial/case/list"
    || $page == "financial/transactions/list" || $page == "financial/transactions/manage"
    || $page == "projects/list" || $page == 'projects/manage'
    || $page == "puantaj/list" || $page == "payroll/list" || $page == "defines/incexp/list"
    || $page == "missions/list" || $page == "missions/process/list" ||
    $page == 'missions/headers/manage' || $page == 'missions/headers/list' ||
    $page == 'defines/job-groups/list' || $page == 'defines/job-groups/manage' ||
    $page == "financial/case/manage" || $page == 'defines/project-status/list' ||
    $page == 'todos/list' || $page == 'raporlar/list' || $page == 'activities/index' || 
    $page == 'abonelik-islemleri/list' || $page == 'abonelik-islemleri/paketler' || $page == 'abonelik-islemleri/satin-alma-islemleri' ||
    $page == 'bildirimler/push' || $page == 'mail-islemleri/index' || $page == 'izin/list' || $page == 'izin/hakedis' ||
    $page == 'supports/tickets' || $page == 'supports/admin-tickets' ||
    $page == 'defines/icra-daireleri/list' || $page == 'persons/icra-list' ||
    strpos($page, 'kvkk/') === 0 || $page == 'kvkk/index' || $page == 'kvkk/ihlaller' || $page == 'kvkk/talepler'
  ) {
    echo '<link href="./dist/libs/datatable/datatables.min.css" rel="stylesheet" />';
  }

  if ($page == "supports/ticket-view" || $page == "supports/admin-ticket-view") {
    echo '<link href="./dist/css/tickets.css" rel="stylesheet" />';
  }

  if ($page == 'projects/manage' || $page == 'home') {
    echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.css">';
    echo '<style>
      /* Bar renkleri */
      .gantt .bar                                         { fill: #206bc4; }
      .gantt .bar-progress                                { fill: #1a5699; }
      .gantt .bar-wrapper.bar-in-progress .bar            { fill: #f59f00 !important; }
      .gantt .bar-wrapper.bar-in-progress .bar-progress   { fill: #c97d00 !important; }
      .gantt .bar-wrapper.bar-done .bar                   { fill: #2fb344 !important; }
      .gantt .bar-wrapper.bar-done .bar-progress          { fill: #229132 !important; }
      /* Hover */
      .gantt .bar-wrapper:hover .bar,
      .gantt .bar-wrapper:hover .bar-progress             { opacity: .82; cursor: grab; }
      .gantt .bar-wrapper:active .bar                     { cursor: grabbing; }
      /* Etiketler */
      .gantt .bar-label                                   { fill: #fff; font-size: 11px; font-weight: 500; letter-spacing: .2px; }
      .gantt .bar-label.big                               { fill: #374151; }
      /* Grid */
      .gantt .grid-header                                 { fill: #f8fafc; stroke: #e9ecef; }
      .gantt .grid-row:nth-child(even)                    { fill: rgba(248,250,252,.6); }
      .gantt .row-line                                    { stroke: #f1f3f5; }
      .gantt .tick                                        { stroke: #e9ecef; }
      .gantt .tick.thick                                  { stroke: #d0d5de; }
      .gantt .upper-text                                  { fill: #374151; font-weight: 600; }
      .gantt .lower-text                                  { fill: #6b7280; }
      .gantt .today-highlight                             { fill: rgba(32,107,196,.07); }
      /* Popup */
      #tasks-gantt-container .popup-wrapper,
      #home-gantt-container .popup-wrapper {
        background: #fff !important;
        color: #333 !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
        box-shadow: 0 10px 30px rgba(0,0,0,.13) !important;
        padding: 0 !important;
        min-width: 220px !important;
        width: auto !important;
        max-width: 300px !important;
        overflow: hidden !important;
      }
      #tasks-gantt-container .pointer,
      #home-gantt-container .pointer { display: none !important; }
    </style>';
  }
  ?>

  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
  <style>
    body {
      font-feature-settings: "cv03", "cv04", "cv11";
    }

    html body.swal2-height-auto {
      height: 100% !important;
    }
  </style>
  <script src="./dist/js/jquery.3.7.1.min.js"></script>
</head>
