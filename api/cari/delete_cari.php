<?php
session_start();
require_once dirname(__DIR__, 2) . "/Model/Cari.php";
require_once dirname(__DIR__, 2) . "/App/Helper/security.php";

require_once dirname(__DIR__, 2) . "/Model/Auths.php";

use App\Helper\Security;

$Auths = new Auths();
$Auths->checkFirmReturn();
$Auths->hasPermissionReturn("cari_takip");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_enc = $_POST['id'] ?? 0;
    if ($id_enc != 0) {
        $id = Security::decrypt($id_enc);
        $cariModel = new Cari();
        $cari = $cariModel->find($id);
        
        if ($cari && $cari->firma == $_SESSION['firm_id']) {
            $data = [
                'id' => $id,
                'silinme_tarihi' => date('Y-m-d H:i:s'),
                'silen_kullanici' => $_SESSION['user']->id
            ];
            try {
                $cariModel->saveWithAttr($data);
                echo json_encode(['status' => 'success', 'message' => 'Cari başarıyla silindi.']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cari bulunamadı veya yetkiniz yok.']);
        }
    }
}
