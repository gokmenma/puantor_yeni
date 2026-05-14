<?php
header('Content-Type: application/json; charset=utf-8');

if (!defined('ROOT')) {
    define("ROOT", dirname(__DIR__));
}

require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/Model/Cases.php";

use App\Helper\Security;

$Auths = new Auths();

// Oturum kontrolü
if (!isset($_SESSION['user'])) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.']);
    exit;
}

$func = $_REQUEST['func'] ?? '';
$firm_id = $_SESSION['firm_id'] ?? 0;

try {
    if ($func == 'list') {
        $query = $db->prepare("SELECT a.*, p.full_name, DATE_FORMAT(a.created_at, '%d.%m.%Y %H:%i') as created_at 
                               FROM personel_avans_talepleri a 
                               JOIN persons p ON a.person_id = p.id 
                               WHERE a.firm_id = ?
                               ORDER BY a.id DESC");
        $query->execute([$firm_id]);
        $list = $query->fetchAll(PDO::FETCH_OBJ);
        echo json_encode(['status' => 'success', 'list' => $list]);
        exit;

    } elseif ($func == 'update_status') {
        $id = $_POST['id'] ?? 0;
        $status = $_POST['status'] ?? 0;

        $db->beginTransaction();
        
        $query = $db->prepare("SELECT * FROM personel_avans_talepleri WHERE id = ? AND firm_id = ?");
        $query->execute([$id, $firm_id]);
        $request = $query->fetch(PDO::FETCH_OBJ);

        if (!$request) throw new Exception("Talep bulunamadı veya bu işlem için yetkiniz yok.");
        if ($request->durum == $status) {
            $db->commit();
            echo json_encode(['status' => 'success', 'message' => 'Talep zaten güncellenmiş.']);
            exit;
        }

        $identifier = "Talep ID: #" . $id;
        
        $db->prepare("DELETE FROM maas_gelir_kesinti WHERE person_id = ? AND kategori = 7 AND aciklama LIKE ?")
           ->execute([$request->person_id, "%" . $identifier . "%"]);
        
        $db->prepare("DELETE FROM case_transactions WHERE person_id = ? AND description LIKE ?")
           ->execute([$request->person_id, "%" . $identifier . "%"]);

        $db->prepare("UPDATE personel_avans_talepleri SET durum = ? WHERE id = ? AND firm_id = ?")
           ->execute([$status, $id, $firm_id]);

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
        echo json_encode(['status' => 'success', 'message' => 'Talep güncellendi.']);
        exit;

    } elseif ($func == 'delete') {
        $id = Security::decrypt($_POST['id'] ?? '');
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
        echo json_encode(['status' => 'success', 'message' => 'Talep ve bağlı kayıtlar silindi.']);
        exit;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
