<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Bu dosya mobile subdomain üzerinde çalışırken ana API'ye erişim sağlar
// mobile/api/bordro/detail.php -> root/api/bordro/detail.php
$target = __DIR__ . "/../../../api/bordro/detail.php";

// Detaylı debug logu yazalım
$log_data = [
    'time' => date('Y-m-d H:i:s'),
    'POST' => $_POST,
    'SESSION' => $_SESSION,
    'TARGET_PATH' => $target,
    'TARGET_EXISTS' => file_exists($target)
];
file_put_contents(__DIR__ . '/debug_log.txt', print_r($log_data, true));

try {
    if (file_exists($target)) {
        // Çalışma dizinini hedef API'nin dizini olarak ayarlayalım ki relative require'lar hata vermesin
        chdir(dirname($target));
        require_once basename($target);
    } else {
        echo "API target not found";
        exit;
    }
} catch (Throwable $e) {
    echo "<h3>Sistem Hatası:</h3>";
    echo "<p>Hata Mesajı: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Dosya: " . htmlspecialchars($e->getFile()) . " (Satır " . $e->getLine() . ")</p>";
    
    $log_data['error'] = [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    file_put_contents(__DIR__ . '/debug_log.txt', print_r($log_data, true));
    exit;
}
