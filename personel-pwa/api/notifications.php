<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/PersonelBildirimModel.php';

if (!isset($_SESSION['personel_id'])) {
    ob_clean();
    echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$person_id = (int) $_SESSION['personel_id'];
$firma_id  = (int) ($_SESSION['personel_user']->firma_id ?? 0);

try {
    $model  = new PersonelBildirimModel($db);
    $action = $_REQUEST['action'] ?? '';

    if ($action === 'list') {
        $list = $model->getByPersonel($person_id, $firma_id);
        ob_clean();
        echo json_encode(['status' => 'success', 'list' => $list], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'unread_count') {
        $count = $model->okunmamisSayisi($person_id, $firma_id);
        ob_clean();
        echo json_encode(['status' => 'success', 'count' => $count], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'mark_read') {
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) throw new Exception('Geçersiz ID.');
        $model->okunduIsaretle($id, $person_id);
        ob_clean();
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'mark_all_read') {
        $model->tumunuOkunduIsaretle($person_id, $firma_id);
        ob_clean();
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) throw new Exception('Geçersiz ID.');
        $model->sil($id, $person_id);
        ob_clean();
        echo json_encode(['status' => 'success'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (\Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
