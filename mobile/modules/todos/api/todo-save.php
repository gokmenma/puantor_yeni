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
    
    $id = isset($_POST['id']) && !empty($_POST['id']) ? Security::decrypt($_POST['id']) : null;
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $project_id = $_POST['project_id'] ?? 0;
    $due_date = $_POST['due_date'] ?? '';
    $status = $_POST['status'] ?? '0';
    $firm_id = $_SESSION['firm_id'];

    if (empty($title)) {
        throw new Exception("Başlık boş olamaz.");
    }

    $data = [
        'firm_id' => $firm_id,
        'title' => $title,
        'description' => $description,
        'project_id' => $project_id,
        'due_date' => $due_date,
        'status' => $status,
        'updated_at' => date('Y-m-d H:i:s')
    ];

    if ($id) {
        $data['id'] = $id;
    } else {
        $data['created_at'] = date('Y-m-d H:i:s');
    }

    $result = $todoModel->saveWithAttr($data);

    echo json_encode([
        'status' => 'success',
        'message' => 'Görev başarıyla kaydedildi.',
        'id' => $result
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
