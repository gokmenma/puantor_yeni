<!doctype html>
<html lang="tr">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <?php

  //Aktif sayfadan menü linki veritabanında aranır ve sayfa ismi alınır
  $title = $menu_name->page_name ?? "Puantor | Puantaj Takip Uygulaması";

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
    || $page == "projects/list" || $page == "projects/add-person" || $page == 'projects/manage'
    || $page == "puantaj/list" || $page == "payroll/list" || $page == "defines/incexp/list"
    || $page == "missions/list" || $page == "missions/process/list" ||
    $page == 'missions/headers/manage' || $page == 'missions/headers/list' ||
    $page == 'defines/job-groups/list' || $page == 'defines/job-groups/manage' ||
    $page == "financial/case/manage" || $page == 'defines/project-status/list' ||
    $page == 'todos/list'

  ) {
    echo '<link href="./dist/libs/datatable/datatables.min.css" rel="stylesheet" />';
  }

  if ($page == "supports/ticket-view") {
    echo '<link href="./dist/css/tickets.css" rel="stylesheet" />';
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