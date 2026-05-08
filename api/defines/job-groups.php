<?php
define('ROOT', dirname(__DIR__, 2));
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/JobGroupsModel.php";

header('Content-Type: application/json');

try {
    $JobGroups = new JobGroupsModel();

    if ($_POST['action'] == "saveJobGroups") {
        $id = $_POST['id'];
        $data = [
            'id' => $id,
            'firm_id' => $_SESSION['firm_id'] ?? 0,
            'group_name' => $_POST['job_group_name'] ?? '',
            'description' => $_POST['description'] ?? ''
        ];
        
        $lastInsertId = $JobGroups->saveWithAttr($data) ?? $id;
        $message = $id == 0 ? "İş Grubu başarıyla eklendi" : "İş Grubu başarı ile güncellendi";
        
        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'id' => is_numeric($lastInsertId) ? $lastInsertId : Security::decrypt($lastInsertId)
        ]);
        exit;
    }

    if ($_POST['action'] == "deleteJobGroups") {
        $id = $_POST['id'];
        $JobGroups->delete($id);
        echo json_encode([
            'status' => 'success',
            'message' => "İş Grubu başarıyla silindi"
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Invalid action']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['status' => 'error', 'message' => 'Sistem hatası: ' . $e->getMessage()]);
}