<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/Database/require.php';
require_once ROOT . '/configs/push_config.php';

try {
    if (!isset($_SESSION['user'])) throw new Exception('Oturum kapalı.');

    $action  = $_REQUEST['action'] ?? '';
    $user_id = (int) ($_SESSION['user']->id ?? 0);
    $firm_id = (int) ($_SESSION['firm_id'] ?? 0);

    if (!$user_id || !$firm_id) throw new Exception('Oturum geçersiz.');

    if ($action === 'vapid-public-key') {
        echo json_encode(['status' => 'success', 'key' => VAPID_PUBLIC_KEY], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'subscribe') {
        $endpoint = trim($_POST['endpoint'] ?? '');
        $p256dh   = trim($_POST['p256dh'] ?? '');
        $auth     = trim($_POST['auth'] ?? '');

        if (!$endpoint || !$p256dh || !$auth) throw new Exception('Eksik subscription verisi.');

        $stmt = $db->prepare("
            INSERT INTO push_subscriptions (user_type, user_id, firma_id, endpoint, p256dh, auth)
            VALUES ('user', :user_id, :firma_id, :endpoint, :p256dh, :auth)
            ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth), updated_at = NOW()
        ");
        $stmt->execute([
            'user_id'  => $user_id,
            'firma_id' => $firm_id,
            'endpoint' => $endpoint,
            'p256dh'   => $p256dh,
            'auth'     => $auth,
        ]);

        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
