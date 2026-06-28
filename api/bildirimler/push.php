<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/Database/require.php';
if (file_exists(ROOT . '/vendor/autoload.php')) require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/Service/WebPushSender.php';
require_once ROOT . '/Service/PushBildirimService.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/GonderilenBildirimlerModel.php';
require_once ROOT . '/Model/PersonelBildirimModel.php';

use Service\PushBildirimService;

if (!isset($_SESSION['user'])) {
    ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.'], JSON_UNESCAPED_UNICODE); exit;
}

$firma_id      = (int) ($_SESSION['firm_id'] ?? 0);
$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;

if (!$is_superadmin) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Bu işlem için yetkiniz yok.'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $action = $_REQUEST['action'] ?? '';

    if ($action === 'stats') {
        $Person = new Persons();
        $total  = count($Person->getPersonsByFirm($firma_id));

        $stmt = $db->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM push_subscriptions WHERE user_type = 'personel' AND firma_id = ?"
        );
        $stmt->execute([$firma_id]);
        $abone = (int) $stmt->fetchColumn();

        ob_clean();
        echo json_encode([
            'status'      => 'success',
            'toplam'      => $total,
            'abone'       => $abone,
            'abone_degil' => max(0, $total - $abone),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'list') {
        $gModel = new GonderilenBildirimlerModel();
        $list = $gModel->getList($firma_id);

        $Person = new Persons();
        $persons = $Person->getPersonsByFirm($firma_id);
        $person_map = [];
        foreach ($persons as $p) {
            $person_map[$p->id] = $p->full_name;
        }

        foreach ($list as $row) {
            if ($row->hedef === 'hepsi') {
                $row->hedef_aciklama = 'Tüm Personeller';
            } else {
                $ids = array_filter(explode(',', $row->personel_ids ?? ''));
                $names = [];
                foreach ($ids as $id) {
                    if (isset($person_map[$id])) {
                        $names[] = $person_map[$id];
                    }
                }
                $row->hedef_aciklama = empty($names) ? 'Seçili Personeller' : implode(', ', $names);
            }
        }

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'list' => $list
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'gonder') {
        $hedef   = $_POST['hedef'] ?? 'hepsi';
        $baslik  = trim($_POST['baslik'] ?? '');
        $icerik  = trim($_POST['icerik'] ?? '');
        $url     = trim($_POST['url'] ?? '');

        if (!$baslik || !$icerik) throw new Exception('Başlık ve içerik zorunludur.');

        $service = new PushBildirimService($db);
        $gonderilen = 0;

        if ($hedef === 'hepsi') {
            $stmt = $db->prepare(
                "SELECT DISTINCT user_id FROM push_subscriptions WHERE user_type = 'personel' AND firma_id = ?"
            );
            $stmt->execute([$firma_id]);
            $personel_ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);

            $Person = new Persons();
            $all_firm_persons = $Person->getPersonsByFirm($firma_id);
            $all_personel_ids = [];
            foreach ($all_firm_persons as $p) {
                $all_personel_ids[] = (int) $p->id;
            }
        } else {
            $raw_ids = $_POST['personel_ids'] ?? [];
            $personel_ids = array_map('intval', (array) $raw_ids);
            $all_personel_ids = $personel_ids;
        }

        // Save to history table
        $gModel = new GonderilenBildirimlerModel();
        $sender_id = (int) $_SESSION['user']->id;
        $gModel->kaydet($firma_id, $sender_id, $hedef, $hedef === 'hepsi' ? null : $personel_ids, $baslik, $icerik, $url);

        // Save to personnel notifications inbox table
        $pBildirimModel = new PersonelBildirimModel($db);

        // Send push notification
        foreach ($personel_ids as $pid) {
            $service->personeleGonder(
                $pid,
                $firma_id,
                $baslik,
                $icerik,
                $url ? ['url' => $url] : []
            );
        }

        // Insert PWA inbox records
        foreach ($all_personel_ids as $pid) {
            $pBildirimModel->kaydet($pid, $firma_id, $baslik, $icerik, $url);
            $gonderilen++;
        }

        require_once ROOT . '/Model/ActivityLogModel.php';
        $target_desc = $hedef === 'hepsi' ? 'Tüm Personeller' : count($all_personel_ids) . ' Seçili Personel';
        ActivityLogModel::log('push_notification', 'send', "Push bildirim gönderildi. Başlık: \"{$baslik}\". Hedef: {$target_desc}.");

        ob_clean();
        echo json_encode([
            'status'    => 'success',
            'message'   => $hedef === 'hepsi' ? "Tüm personellerin bildirim kutusuna ve push abonelerine gönderildi." : "{$gonderilen} personele bildirim gönderildi.",
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
