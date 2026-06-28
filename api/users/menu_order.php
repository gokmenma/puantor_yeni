<?php
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/Menus.php";
require_once ROOT . "/Model/ActivityLogModel.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_SESSION['user']->id;

    if ($action === 'save_order') {
        $order = json_decode($_POST['order'] ?? '[]', true);
        
        if (empty($order)) {
            echo json_encode(['success' => false, 'message' => 'Geçersiz sıralma verisi.']);
            exit;
        }

        try {
            $menusModel = new Menus();
            $result = $menusModel->saveUserMenuOrder($userId, $order);
            if ($result) {
                // Her işlem için log kaydı oluşturulur
                ActivityLogModel::log('menu', 'reorder', 'Kullanıcı menü sırasını güncelledi.');
                echo json_encode(['success' => true, 'message' => 'Menü sırası başarıyla güncellendi.']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Menü sırası güncellenirken hata oluştu.']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
