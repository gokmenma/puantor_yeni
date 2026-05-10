<?php
session_start();
require_once dirname(__DIR__, 2) . "/Model/CariHareketleri.php";
require_once dirname(__DIR__, 2) . "/App/Helper/security.php";

use App\Helper\Security;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_enc = $_POST['id'] ?? 0;
    if ($id_enc != 0) {
        $id = Security::decrypt($id_enc);
        $moveModel = new CariHareketleri();
        $movement = $moveModel->find($id);
        
        if ($movement) {
            $data = [
                'id' => $id,
                'silinme_tarihi' => date('Y-m-d H:i:s'),
                'silen_kullanici' => $_SESSION['user']->id
            ];
            try {
                $moveModel->saveWithAttr($data);
                echo json_encode(['status' => 'success', 'message' => 'Hareket başarıyla silindi.']);
            } catch (Exception $e) {
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Hareket bulunamadı.']);
        }
    }
}
