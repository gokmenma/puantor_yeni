<?php
// Puantor Mobil - Puantaj Silme API
header('Content-Type: application/json');

try {
    define("ROOT", dirname(dirname(dirname(dirname(__DIR__)))));
    require_once ROOT . "/Database/require.php";
    require_once ROOT . "/Model/Puantaj.php";
    require_once ROOT . "/Model/Persons.php";

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user'])) {
        echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim']);
        exit();
    }

    $person_id = intval($_POST['person_id'] ?? 0);
    $date = $_POST['date'] ?? ''; 
    $project_id = intval($_POST['project_id'] ?? -1); // -1 means search in any project

    if (!$person_id || !$date) {
        echo json_encode(['status' => 'error', 'message' => 'Eksik parametreler']);
        exit();
    }

    $puantajObj = new Puantaj();
    
    // Find the record ID
    $id = $puantajObj->getPuantajId($person_id, $date, $project_id);

    if ($id) {
        $puantajObj->deletePuantajGun($id);
        echo json_encode(['status' => 'success', 'message' => 'Puantaj başarıyla silindi']);
    } else {
        echo json_encode(['status' => 'info', 'message' => 'Silinecek puantaj bulunamadı']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
