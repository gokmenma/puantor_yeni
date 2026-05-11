<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no" />
  <meta http-equiv="X-UA-Compatible" content="ie=edge" />
  <title><?php echo $title ?? "Puantor Mobil | Puantaj Takip Uygulaması"; ?></title>

  <!-- PWA & Favicon -->
  <link rel="icon" href="/static/favicon.ico" type="image/x-icon" />
  <link rel="manifest" href="manifest.json">
  <meta name="theme-color" content="#206bc4">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="apple-mobile-web-app-title" content="Puantor">
  <link rel="apple-touch-icon" href="../static/png/icon-192x192.png">

  <!-- Service Worker Registration -->
  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('sw.js')
          .then(reg => console.log('Service Worker registered', reg))
          .catch(err => console.log('Service Worker registration failed', err));
      });
    }
  </script>

  <!-- CSS CDN Libraries (Consistent with Desktop) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
  <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Google Fonts (Inter Font Pairings) -->
  <link rel="stylesheet" href="https://rsms.me/inter/inter.css">

  <!-- Mobile Custom Premium CSS -->
  <link rel="stylesheet" href="./css/mobile.css?v=<?php echo filemtime(__DIR__ . '/../css/mobile.css'); ?>" />

  <!-- jQuery (Required for shared logic/APIs) -->
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script>window.$ = window.jQuery;</script>

  <style>
    :root {
      --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, sans-serif;
    }
    
    /* Global Premium Modal Styling for Mobile */
    .modal-content {
        border-radius: 24px !important;
        border: none !important;
        overflow: hidden !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.15) !important;
    }
    .modal-header {
        border-bottom: 1px solid rgba(0,0,0,0.05) !important;
        padding: 1.25rem 1.5rem !important;
    }
    .modal-footer {
        border-top: 1px solid rgba(0,0,0,0.05) !important;
        padding: 1rem 1.5rem !important;
    }
    body[data-bs-theme="dark"] .modal-content {
        background-color: #1a2234 !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.4) !important;
    }
    body[data-bs-theme="dark"] .modal-header,
    body[data-bs-theme="dark"] .modal-footer {
        border-color: rgba(255,255,255,0.05) !important;
    }
  </style>
  <script>
    window.API_PATH = 'api/';
  </script>
</head>
