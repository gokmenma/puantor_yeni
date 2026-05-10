<?php
session_start();
require_once dirname(__DIR__, 2) . "/Model/Cari.php";
require_once dirname(__DIR__, 2) . "/App/Helper/security.php";

use App\Helper\Security;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_enc = $_POST['id'] ?? 0;
    if ($id_enc != 0) {
        $id = Security::decrypt($id_enc);
        $cariModel = new Cari();
        $cari = $cariModel->find($id);
        
        if ($cari && $cari->firma == $_SESSION['firm_id']) {
            echo json_encode(['status' => 'success', 'cari' => $cari]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Cari bulunamadı.']);
        }
    }
}
