<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

$status = [];

$status['webpush_installed'] = class_exists('Minishlink\WebPush\WebPush') ? false : null;
try {
    require_once ROOT . '/vendor/autoload.php';
    $status['autoload'] = 'ok';
    $status['webpush_installed'] = class_exists('Minishlink\WebPush\WebPush');
} catch (Throwable $e) {
    $status['autoload'] = $e->getMessage();
}

try {
    require_once ROOT . '/Service/PushBildirimService.php';
    $status['service_file'] = 'ok';
} catch (Throwable $e) {
    $status['service_file'] = $e->getMessage();
}

try {
    require_once ROOT . '/Database/require.php';
    $status['db'] = 'ok';

    $r = $db->query("SHOW TABLES LIKE 'push_subscriptions'");
    $status['table_exists'] = $r->rowCount() > 0;

    if ($status['table_exists']) {
        $r2 = $db->query("SELECT COUNT(*) FROM push_subscriptions");
        $status['subscription_count'] = (int) $r2->fetchColumn();

        $r3 = $db->query("SELECT user_type, COUNT(*) as c FROM push_subscriptions GROUP BY user_type");
        $status['subscriptions_by_type'] = $r3->fetchAll(PDO::FETCH_OBJ);
    }
} catch (Throwable $e) {
    $status['db'] = $e->getMessage();
}

require_once ROOT . '/configs/push_config.php';
$status['vapid_public_key'] = defined('VAPID_PUBLIC_KEY') ? substr(VAPID_PUBLIC_KEY, 0, 20) . '...' : 'TANIMLI DEĞİL';

$status['session_personel_id'] = $_SESSION['personel_id'] ?? null;
$status['session_firm_id']     = $_SESSION['firm_id'] ?? null;

echo json_encode($status, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
