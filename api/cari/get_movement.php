<?php
session_start();
require_once dirname(__DIR__, 2) . "/Model/CariHareketleri.php";
require_once dirname(__DIR__, 2) . "/App/Helper/security.php";
require_once dirname(__DIR__, 2) . "/App/Helper/date.php";

require_once dirname(__DIR__, 2) . "/Model/Auths.php";

use App\Helper\Security;

$Auths = new Auths();
$Auths->checkFirmReturn();
$Auths->hasPermissionReturn("cari_hareketleri");

use App\Helper\Date;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_enc = $_POST['id'] ?? 0;
    if ($id_enc != 0) {
        $id = Security::decrypt($id_enc);
        $moveModel = new CariHareketleri();
        $movement = $moveModel->find($id);
        
        if ($movement) {
            $movement->islem_tarihi_fmt = Date::dmY($movement->islem_tarihi);
            echo json_encode(['status' => 'success', 'movement' => $movement]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Hareket bulunamadı.']);
        }
    }
}
