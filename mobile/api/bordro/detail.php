<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bu dosya mobile subdomain üzerinde çalışırken ana API'ye erişim sağlar
// mobile/api/bordro/detail.php -> root/api/bordro/detail.php
$target = __DIR__ . "/../../../api/bordro/detail.php";

try {
    if (file_exists($target)) {
        chdir(dirname($target));
        require_once basename($target);
    } else {
        echo "API target not found";
        exit;
    }
} catch (Throwable $e) {
    echo "<div class='alert alert-danger'>Bordro detayı yüklenirken hata oluştu.</div>";
    exit;
}
