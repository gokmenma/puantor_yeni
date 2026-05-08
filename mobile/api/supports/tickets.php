<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

$log_file = __DIR__ . '/debug.log';
$log_data = [
    'time' => date('Y-m-d H:i:s'),
    'post' => $_POST,
    'session_exists' => isset($_SESSION),
    'session_user' => $_SESSION['user'] ?? null,
    'session_firm' => $_SESSION['firm_id'] ?? null
];

// ROOT sabitini tanımlayalım ki içerilen dosyalarda (örneğin ticket-mail.php) ROOT kullanımı hata vermesin
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 3));
}

$log_data['root'] = ROOT;

// Bu dosya mobile subdomain üzerinde çalışırken ana API'ye erişim sağlar
// mobile/api/supports/tickets.php -> root/api/supports/tickets.php
$target = __DIR__ . "/../../../api/supports/tickets.php";
$log_data['target'] = $target;
$log_data['target_exists'] = file_exists($target);

try {
    if (file_exists($target)) {
        // Çalışma dizinini hedef API'nin dizini olarak ayarlayalım ki relative require'lar hata vermesin
        chdir(dirname($target));
        
        // Output buffering to capture any output
        ob_start();
        require $target;
        $output = ob_get_clean();
        
        $log_data['api_output'] = $output;
        echo $output;
    } else {
        ob_clean();
        header('Content-Type: application/json');
        $err = ["status" => "error", "message" => "API target not found"];
        $log_data['error'] = "Target not found";
        echo json_encode($err);
    }
} catch (Throwable $t) {
    $log_data['exception'] = [
        'message' => $t->getMessage(),
        'file' => $t->getFile(),
        'line' => $t->getLine()
    ];
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(["status" => "error", "message" => "System error: " . $t->getMessage()]);
}

file_put_contents($log_file, print_r($log_data, true) . "\n====================\n", FILE_APPEND);

if (ob_get_length()) {
    ob_end_flush();
}
