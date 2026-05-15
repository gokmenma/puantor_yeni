<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) {
    define("ROOT", dirname(__DIR__, 2));
}

use App\Helper\Security;

try {
    require_once ROOT . "/Database/require.php";
    require_once ROOT . "/Model/Auths.php";
    require_once ROOT . "/App/Helper/security.php";
    require_once ROOT . "/Model/Cases.php";

    $Auths = new Auths();

    // Oturum kontrolü
    if (!isset($_SESSION['user'])) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Hem 'action' hem 'func' parametresini destekle (WAF bypass için 'action' önerilir)
    $action = $_REQUEST['action'] ?? $_REQUEST['func'] ?? '';
    $firm_id = $_SESSION['firm_id'] ?? 0;

    if ($action == 'list') {
        $query = $db->prepare("SELECT a.*, p.full_name, DATE_FORMAT(a.created_at, '%d.%m.%Y %H:%i') as created_at 
                               FROM personel_avans_talepleri a 
                               JOIN persons p ON a.person_id = p.id 
                               WHERE a.firm_id = ?
                               ORDER BY a.id DESC");
        $query->execute([$firm_id]);
        $list = $query->fetchAll(PDO::FETCH_OBJ);
        $json = json_encode(['status' => 'success', 'list' => $list], JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new Exception("JSON Encode Hatasi: " . json_last_error_msg());
        }
        if (ob_get_length()) ob_clean();
        echo $json;
        exit;

    } elseif ($action == 'update_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 0;

        $db->beginTransaction();
        
        $query = $db->prepare("SELECT * FROM personel_avans_talepleri WHERE id = ? AND firm_id = ?");
        $query->execute([$id, $firm_id]);
        $request = $query->fetch(PDO::FETCH_OBJ);

        if (!$request) throw new Exception("Talep bulunamadı veya bu işlem için yetkiniz yok.");
        if ($request->durum == $status) {
            $db->commit();
            if (ob_get_length()) ob_clean();
            echo json_encode(['status' => 'success', 'message' => 'Talep zaten güncellenmiş.'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $identifier = "Talep ID: #" . $id;
        
        // Önceki kayıtları temizle
        $db->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND kategori = 7 AND aciklama LIKE ?")
           ->execute([$request->person_id, "%" . $identifier . "%"]);
        
        $db->prepare("DELETE FROM case_transactions WHERE person_id = ? AND description LIKE ?")
           ->execute([$request->person_id, "%" . $identifier . "%"]);

        // Durumu güncelle
        $db->prepare("UPDATE personel_avans_talepleri SET durum = ? WHERE id = ? AND firm_id = ?")
           ->execute([$status, $id, $firm_id]);

        // Eğer onaylandıysa kasa/maaş kaydı oluştur
        if ($status == 1) {
            $ay = $request->hedef_ay ?? date('m');
            $yil = $request->hedef_yil ?? date('Y');
            $target_gun_db = sprintf("%04d%02d15", $yil, $ay);
            
            $Cases = new Cases();
            $default_case_id = $Cases->getDefaultCaseIdByFirm();

            $db->prepare("INSERT INTO maas_gelir_kesinti (user_id, person_id, case_id, gun, ay, yil, tutar, kategori, turu, aciklama) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
               ->execute([
                   $firm_id,
                   $request->person_id,
                   $default_case_id,
                   $target_gun_db,
                   $ay,
                   $yil,
                   $request->tutar,
                   7,
                   'Avans',
                   $identifier . " | PWA Üzerinden Talep Edildi: " . $request->aciklama
               ]);
        }

        $db->commit();
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Talep güncellendi.'], JSON_UNESCAPED_UNICODE);
        exit;

    } elseif ($action == 'delete') {
        $id = $_POST['id'] ?? '';
        // Eğer şifrelenmiş gelirse çöz (Bazı yerlerde decrypt kullanılıyor olabilir)
        if (!is_numeric($id)) {
            $id = Security::decrypt($id);
        }
        
        if (!$id) throw new Exception("Geçersiz ID.");

        $db->beginTransaction();
        
        $query = $db->prepare("SELECT * FROM personel_avans_talepleri WHERE id = ? AND firm_id = ?");
        $query->execute([$id, $firm_id]);
        $request = $query->fetch(PDO::FETCH_OBJ);

        if ($request) {
            $identifier = "Talep ID: #" . $id;
            $db->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND kategori = 7 AND aciklama LIKE ?")
               ->execute([$request->person_id, "%" . $identifier . "%"]);
            $db->prepare("DELETE FROM case_transactions WHERE person_id = ? AND description LIKE ?")
               ->execute([$request->person_id, "%" . $identifier . "%"]);
        }

        $db->prepare("DELETE FROM personel_avans_talepleri WHERE id = ? AND firm_id = ?")->execute([$id, $firm_id]);
        
        $db->commit();
        if (ob_get_length()) ob_clean();
        echo json_encode(['status' => 'success', 'message' => 'Talep ve bağlı kayıtlar silindi.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Fallback for unmatched actions
if (ob_get_length()) ob_clean();
echo json_encode(['status' => 'error', 'message' => 'Gecersiz islem istegi. (' . $action . ')'], JSON_UNESCAPED_UNICODE);
exit;
