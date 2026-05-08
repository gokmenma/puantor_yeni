<?php
session_start();
require_once "../../../../App/Helper/security.php";
require_once "../../../../Model/TodoModel.php";

use App\Helper\Security;

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user'])) {
        throw new Exception("Oturum bulunamadı.");
    }

    $todoModel = new Todo();
    
    $id = isset($_POST['id']) ? Security::decrypt($_POST['id']) : null;
    $status = $_POST['status'] ?? '0';

    if (!$id) {
        throw new Exception("Geçersiz görev ID.");
    }

    $data = [
        'id' => $id,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $todoModel->saveWithAttr($data);

    echo json_encode([
        'status' => 'success',
        'message' => 'Görev durumu güncellendi.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
