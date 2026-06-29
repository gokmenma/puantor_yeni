<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

try {
    require_once ROOT . '/Database/require.php';
    require_once ROOT . '/Model/Auths.php';
    require_once ROOT . '/Model/IzinFormSecenekler.php';

    $Auths = new Auths();
    if (!isset($_SESSION['user'])) {
        throw new Exception('Oturum kapalı.');
    }

    $action   = $_REQUEST['action'] ?? '';
    $firma_id = (int) ($_SESSION['firm_id'] ?? 0);
    $model    = new IzinFormSecenekler();

    if ($action === 'list') {
        $unvanlar = $model->getOptions($firma_id, 'unvan');
        $isimler = $model->getOptions($firma_id, 'isim');
        $last_selections = $model->getLastSelections($firma_id);

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'unvanlar' => $unvanlar,
            'isimler' => $isimler,
            'last_selections' => $last_selections
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'add') {
        $type = $_POST['type'] ?? '';
        $val = $_POST['val'] ?? '';
        if (!in_array($type, ['unvan', 'isim']) || empty($val)) {
            throw new Exception('Eksik veya geçersiz veri.');
        }

        $id = $model->addOption($firma_id, $type, $val);
        ob_clean();
        echo json_encode(['status' => 'success', 'id' => $id], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            throw new Exception('Geçersiz ID.');
        }

        $model->deleteOption($id);
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Seçenek silindi.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'save_last') {
        $selections = $_POST['selections'] ?? [];
        if (empty($selections) || !is_array($selections)) {
            throw new Exception('Geçersiz seçim verileri.');
        }

        $model->saveLastSelections($firma_id, $selections);
        ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Tercihler kaydedildi.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (\Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
