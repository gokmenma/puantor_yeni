<?php
ob_start();
// mobile/api/projects/project-person.php -> root/api/projects/project-person.php
$target = __DIR__ . "/../../../api/projects/project-person.php";

file_put_contents(__DIR__ . '/proxy_debug.log', date('Y-m-d H:i:s') . " - Proxy Start. Target: " . $target . " - Exists: " . (file_exists($target) ? 'YES' : 'NO') . "\n", FILE_APPEND);

error_reporting(E_ALL);
ini_set('display_errors', 1);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && ($error['type'] === E_ERROR || $error['type'] === E_PARSE || $error['type'] === E_CORE_ERROR || $error['type'] === E_COMPILE_ERROR)) {
        header('Content-Type: application/json');
        echo json_encode([
            "status" => "error", 
            "message" => "Sistem Hatası: " . $error['message'],
            "file" => $error['file'],
            "line" => $error['line']
        ]);
    }
});

if (file_exists($target)) {
    chdir(dirname($target));
    require $target;
} else {
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "API hedef dosyası bulunamadı: " . $target]);
}

// Tamponu temizleyip çıktı verelim
$output = ob_get_clean();
if (empty($output) && !headers_sent()) {
    echo json_encode(["status" => "error", "message" => "API yanıt vermedi (Boş içerik)."]);
} else {
    echo $output;
}
