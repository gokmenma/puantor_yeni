<head>
    <meta charset="utf-8"/>
    <script>
        // Apply theme before rendering
        (function() {
            const theme = localStorage.getItem('puantor_theme') || 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
        })();
    </script>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover" name="viewport"/>
    <title><?php echo $title ?? 'Puantör Personel PWA'; ?></title>
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="resources/Logo-ai.svg">
    <meta name="theme-color" content="#206bc4">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- CSS Dependencies -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/css/tabler.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css" />
    <link rel="stylesheet" href="https://rsms.me/inter/inter.css">
    <link rel="stylesheet" href="css/app.css?v=<?php echo filemtime(__DIR__ . '/../css/app.css'); ?>">
    
    <style>
        :root {
            --safe-area-top: env(safe-area-inset-top, 0px);
            --safe-area-bottom: env(safe-area-inset-bottom, 0px);
        }
    </style>
</head>
