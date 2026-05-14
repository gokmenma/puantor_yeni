<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) {
    define("ROOT", dirname(__DIR__));
}

require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/Auths.php";

$Auths = new Auths();

// Oturum ve yetki kontrolü
if (!isset($_SESSION['user'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.']);
    exit;
}

$action = $_REQUEST['action'] ?? '';

if ($action == 'list') {
    try {
        $query = $db->prepare("SELECT a.*, p.full_name, DATE_FORMAT(a.created_at, '%d.%m.%Y %H:%i') as created_at 
                               FROM personel_avans_talepleri a 
                               JOIN persons p ON a.person_id = p.id 
                               ORDER BY a.id DESC");
        $query->execute();
        $list = $query->fetchAll(PDO::FETCH_OBJ);
        
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'list' => $list]);
        exit;
    } catch (Exception $e) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        exit;
    }

} elseif ($action == 'update_status') {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 0; // 1: Approved, 2: Rejected

    $db->beginTransaction();
    try {
        // Get request details
        $query = $db->prepare("SELECT * FROM personel_avans_talepleri WHERE id = ?");
        $query->execute([$id]);
        $request = $query->fetch(PDO::FETCH_OBJ);

        if (!$request) {
            throw new Exception("Talep bulunamadı.");
        }

        // Update status
        $update = $db->prepare("UPDATE personel_avans_talepleri SET durum = ? WHERE id = ?");
        $update->execute([$status, $id]);

        // If approved, create a deduction record
        if ($status == 1) {
            $ay = $request->hedef_ay ?? date('m');
            $yil = $request->hedef_yil ?? date('Y');
            $target_gun = sprintf("%04d%02d15", $yil, $ay); // Defaulting to 15th of target month

            $insert = $db->prepare("INSERT INTO maas_gelir_kesinti (user_id, person_id, gun, ay, yil, tutar, kategori, turu, aciklama) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insert->execute([
                $_SESSION['user']->id ?? 0,
                $request->person_id,
                $target_gun,
                $ay,
                $yil,
                $request->tutar,
                7, // Kategori 7 (Avans)
                'Avans',
                'PWA Üzerinden Talep Edildi: ' . $request->aciklama
            ]);
        }

        $db->commit();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Talep durumu güncellendi.']);
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
        exit;
    }
}

ob_clean();
header('Content-Type: application/json');
echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem.']);
exit;
