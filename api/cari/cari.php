<?php
session_start();
ob_start();
!defined("ROOT") ? define("ROOT", dirname(dirname(__DIR__))) : null;

require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/Cari.php";
require_once ROOT . "/Model/CariHareketleri.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Security;
use App\Helper\Date;
use App\Helper\Helper;

$Auths = new Auths();
$cariModel = new Cari();
$moveModel = new CariHareketleri();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == 'saveCari') {
    $Auths->checkFirmReturn();
    $Auths->hasPermissionReturn("cari_takip");
    
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
    exit;
}

if ($action == 'deleteCari') {
    $Auths->checkFirmReturn();
    $Auths->hasPermissionReturn("cari_takip");
    
    $id_enc = $_POST['id'] ?? 0;
    if ($id_enc != 0) {
        $id = Security::decrypt($id_enc);
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
    exit;
}

if ($action == 'saveMovement') {
    $Auths->checkFirmReturn();
    $Auths->hasPermissionReturn("cari_hareketleri");
    
    $data = $_POST;
    $id_enc = $data['id'] ?? 0;
    if ($id_enc != 0) {
        $data['id'] = Security::decrypt($id_enc);
    } else {
        unset($data['id']);
        $data['kayit_tarihi'] = date('Y-m-d H:i:s');
    }

    $data['cari_id'] = Security::decrypt($data['cari_id']);
    $data['islem_tarihi'] = Date::yMd($data['islem_tarihi'] ?? date('d.m.Y'));

    try {
        $moveModel->saveWithAttr($data);
        echo json_encode(['status' => 'success', 'message' => 'Hareket başarıyla kaydedildi.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action == 'deleteMovement') {
    $Auths->checkFirmReturn();
    $Auths->hasPermissionReturn("cari_hareketleri");
    
    $id_enc = $_POST['id'] ?? 0;
    if ($id_enc != 0) {
        $id = Security::decrypt($id_enc);
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
    exit;
}

if ($action == 'getCari') {
    $Auths->checkFirmReturn();
    $id_enc = $_POST['id'] ?? 0;
    $id = Security::decrypt($id_enc);
    $cari = $cariModel->find($id);
    if ($cari && $cari->firma == $_SESSION['firm_id']) {
        echo json_encode(['status' => 'success', 'cari' => $cari]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Cari bulunamadı.']);
    }
    exit;
}

// Default response
echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
