<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/Database/require.php';
require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/configs/push_config.php';

use Service\PushBildirimService;

try {
    $action    = $_REQUEST['action'] ?? '';
    $person_id = (int) ($_REQUEST['person_id'] ?? 0);
    $firm_id   = (int) ($_REQUEST['firm_id'] ?? 0);

    if (!$person_id || !$firm_id) {
        throw new Exception('Oturum geçersiz.');
    }

    if ($action === 'vapid-public-key') {
        echo json_encode(['status' => 'success', 'key' => VAPID_PUBLIC_KEY], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'subscribe') {
        $endpoint = trim($_POST['endpoint'] ?? '');
        $p256dh   = trim($_POST['p256dh'] ?? '');
        $auth     = trim($_POST['auth'] ?? '');

        if (!$endpoint || !$p256dh || !$auth) {
            throw new Exception('Eksik subscription verisi.');
        }

        $service = new PushBildirimService($db);
        $service->aboneKaydet('personel', $person_id, $firm_id, $endpoint, $p256dh, $auth);

        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
