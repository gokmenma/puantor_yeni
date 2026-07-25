<!doctype html>
<html lang="tr">

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

  <!-- Your code -->
  <!-- CSS files -->
  <!-- Meta Başlık -->

  <!-- Meta Açıklama -->
  <meta name="description"
    content="Puantor, çalışanlarınızın puantajını, maaş hesaplamalarını ve proje takibini kolayca yapmanızı sağlar. Hızlı, güvenilir ve kullanıcı dostu bir platform ile iş süreçlerinizi optimize edin. Daha fazla verimlilik için hemen keşfedin!" />

  <!-- Anahtar Kelimeler -->
  <meta name="keywords"
    content="puantaj yazılımı, maaş hesaplama aracı, proje takibi, gelir gider takibi, personel yönetimi, işletme yönetim yazılımı, verimli iş yönetimi" />

    <!-- <link href="./dist/css/tabler.min.css?1692870487" rel="stylesheet" /> -->
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
  <link href="./dist/css/style.css?v=<?php echo filemtime("./dist/css/style.css"); ?>" rel="stylesheet" />
  <link href="./dist/css/menu.css?v=<?php echo filemtime("./dist/css/menu.css"); ?>" rel="stylesheet" />
  <link href="./dist/libs/select2/css/select2.min.css?v=<?php echo filemtime("./dist/libs/select2/css/select2.min.css"); ?>" rel="stylesheet" />


  <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css"> -->
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
  ;

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
    $page == 'defines/icra-daireleri/list'
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
    @import url('https://rsms.me/inter/inter.css');

    :root {
      --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif;
    }

    body {
      font-feature-settings: "cv03", "cv04", "cv11";
    }

    html body.swal2-height-auto {
      height: 100% !important;
    }
  </style>
  <script src="./dist/js/jquery.3.7.1.min.js"></script>
</head>
