<?php
/**
 * Mobile Cari API Proxy
 * Avoids WAF/reCAPTCHA issues by using a standardized endpoint in the mobile directory
 */
header('Content-Type: application/json');
$func = $_REQUEST['func'] ?? '';

$target_dir = __DIR__ . '/../../api/cari/';

switch($func) {
    case 'save_cari':
        $target = $target_dir . 'save_cari.php';
        break;
    case 'delete_cari':
        $target = $target_dir . 'delete_cari.php';
        break;
    case 'save_movement':
        $target = $target_dir . 'save_movement.php';
        break;
    case 'delete_movement':
        $target = $target_dir . 'delete_movement.php';
        break;
    case 'list':
        $target = $target_dir . 'get_cari.php';
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid function: ' . $func]);
        exit;
}

if (file_exists($target)) {
    require_once $target;
} else {
    echo json_encode(['status' => 'error', 'message' => 'API target not found: ' . $func]);
}
