<?php
session_start();
require_once dirname(__DIR__, 2) . "/Model/Cari.php";
require_once dirname(__DIR__, 2) . "/App/Helper/security.php";

use App\Helper\Security;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cariModel = new Cari();
    $data = $_POST;
    
    $id_enc = $data['id'] ?? 0;
    if ($id_enc != 0) {
        $data['id'] = Security::decrypt($id_enc);
    } else {
        unset($data['id']);
        $data['kayit_tarihi'] = date('Y-m-d H:i:s');
        $data['firma'] = $_SESSION['firm_id'];
        $data['Aktif'] = 1;
    }

    try {
        $cariModel->saveWithAttr($data);
        echo json_encode(['status' => 'success', 'message' => 'Cari başarıyla kaydedildi.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
