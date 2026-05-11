<?php
session_start();
require_once dirname(__DIR__, 2) . "/Model/CariHareketleri.php";
require_once dirname(__DIR__, 2) . "/App/Helper/security.php";
require_once dirname(__DIR__, 2) . "/App/Helper/date.php";

require_once dirname(__DIR__, 2) . "/Model/Auths.php";

use App\Helper\Security;
use App\Helper\Date;

$Auths = new Auths();
$Auths->checkFirmReturn();
$Auths->hasPermissionReturn("cari_hareketleri");


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moveModel = new CariHareketleri();
    $data = $_POST;
    
    $id_enc = $data['id'] ?? 0;
    if ($id_enc != 0) {
        $data['id'] = Security::decrypt($id_enc);
    } else {
        unset($data['id']);
        $data['kayit_tarihi'] = date('Y-m-d H:i:s');
    }

    $data['cari_id'] = Security::decrypt($data['cari_id']);
    $data['islem_tarihi'] = Date::yMd($data['islem_tarihi']);

    try {
        $moveModel->saveWithAttr($data);
        echo json_encode(['status' => 'success', 'message' => 'Hareket başarıyla kaydedildi.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
