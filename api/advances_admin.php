<?php
header('Content-Type: application/json');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) {
    define("ROOT", dirname(__DIR__));
}

require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/Auths.php";

$Auths = new Auths();

require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/Model/Cases.php";
use App\Helper\Security;

// Oturum ve yetki kontrolü
if (!isset($_SESSION['user'])) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.']);
    exit;
}

$action = $_REQUEST['func'] ?? '';

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

        // Eğer talep zaten bu durumdaysa (Örn: Başkası tarafından zaten onaylanmışsa) işlemi yapma
        if ($request->durum == $status) {
            $db->commit();
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Talep zaten güncellenmiş.']);
            exit;
        }

        // Always try to clean up existing records for this request
        $identifier = "Talep ID: #" . $id;
        
        // 1. Clean up maas_gelir_kesinti (Payroll)
        $deletePayroll = $db->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND kategori = 7 AND aciklama LIKE ?");
        $deletePayroll->execute([$request->person_id, "%" . $identifier . "%"]);

        // 2. Clean up case_transactions (Account Balance)
        $deleteFinance = $db->prepare("DELETE FROM case_transactions WHERE person_id = ? AND description LIKE ?");
        $deleteFinance->execute([$request->person_id, "%" . $identifier . "%"]);

        // Update status
        $update = $db->prepare("UPDATE personel_avans_talepleri SET durum = ? WHERE id = ?");
        $update->execute([$status, $id]);

        // If approved, create records
        if ($status == 1) {
            $ay = $request->hedef_ay ?? date('m');
            $yil = $request->hedef_yil ?? date('Y');
            $target_gun_db = sprintf("%04d%02d15", $yil, $ay); // YYYYMMDD
            $target_gun_finance = sprintf("%04d-%02d-15", $yil, $ay); // YYYY-MM-DD

            // Get default case for the firm
            $Cases = new Cases();
            $default_case_id = $Cases->getDefaultCaseIdByFirm();

            // A. Create Payroll Record (maas_gelir_kesinti)
            // Kategori 7 (Avans) is automatically handled as a 'Gider' in Kasa views
            $insertPayroll = $db->prepare("INSERT INTO maas_gelir_kesinti (user_id, person_id, case_id, gun, ay, yil, tutar, kategori, turu, aciklama) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insertPayroll->execute([
                $_SESSION['firm_id'] ?? 0,
                $request->person_id,
                $default_case_id,
                $target_gun_db,
                $ay,
                $yil,
                $request->tutar,
                7, // Kategori 7 (Avans)
                'Avans',
                $identifier . " | PWA Üzerinden Talep Edildi: " . $request->aciklama
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
} elseif ($action == 'delete') {
    $id = Security::safeDecrypt($_POST['id'] ?? '');

    if (!$Auths->hasPermission('onayli_avanslarda_islem_yap')) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'error', 'message' => 'Bu işlemi yapmak için yetkiniz bulunmamaktadır.']);
        exit;
    }

    $db->beginTransaction();
    try {
        // Get request details first
        $query = $db->prepare("SELECT * FROM personel_avans_talepleri WHERE id = ?");
        $query->execute([$id]);
        $request = $query->fetch(PDO::FETCH_OBJ);

        if (!$request) {
            throw new Exception("Talep bulunamadı.");
        }

        // Clean up all financial records using Talep ID identifier
        $identifier = "Talep ID: #" . $id;
        
        // 1. Clean up maas_gelir_kesinti (Payroll)
        $deletePayroll = $db->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND kategori = 7 AND aciklama LIKE ?");
        $deletePayroll->execute([$request->person_id, "%" . $identifier . "%"]);

        // 2. Clean up case_transactions (Account Balance)
        $deleteFinance = $db->prepare("DELETE FROM case_transactions WHERE person_id = ? AND description LIKE ?");
        $deleteFinance->execute([$request->person_id, "%" . $identifier . "%"]);

        // 3. Delete the request itself
        $deleteRequest = $db->prepare("DELETE FROM personel_avans_talepleri WHERE id = ?");
        $deleteRequest->execute([$id]);

        $db->commit();
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['status' => 'success', 'message' => 'Talep ve bağlı tüm finansal kayıtlar başarıyla silindi.']);
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
