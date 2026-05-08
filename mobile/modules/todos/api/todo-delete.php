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
    
    $id = isset($_POST['id']) ? $_POST['id'] : null;

    if (!$id) {
        throw new Exception("Geçersiz görev ID.");
    }

    $todoModel->delete($id);

    echo json_encode([
        'status' => 'success',
        'message' => 'Görev başarıyla silindi.'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
