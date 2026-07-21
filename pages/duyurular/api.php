<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));
require_once ROOT . '/Database/db.php';
require_once ROOT . '/Model/DuyuruModel.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum bulunamadı.']);
    exit;
}

$kullanici_id  = $_SESSION['user']->id ?? 0;
$firma_id      = $_SESSION['firm_id'] ?? 0;
$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$is_main_user  = !$is_superadmin && (($_SESSION['user']->parent_id ?? 1) == 0);

require_once ROOT . '/Model/Auths.php';
$_api_auths = new Auths();
$_api_auths->hasPermissionReturn('duyurular');
$can_add = $is_superadmin || $_api_auths->hasPermission('duyuru_ekle');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$Duyuru = new DuyuruModel();

try {
    switch ($action) {

        case 'ekle':
            if (!$can_add) throw new Exception('Duyuru ekleme yetkiniz yok.');
            $baslik = trim($_POST['baslik'] ?? '');
            $icerik = trim($_POST['icerik'] ?? '');
            if (empty($baslik) || empty($icerik)) {
                throw new Exception('Başlık ve içerik zorunludur.');
            }

            $hedef_tip      = $_POST['hedef_tip'] ?? 'herkese';

            $allowed_types = ['herkese', 'firma_kullanicilari', 'firma_personelleri', 'bazi_kullanicilar', 'bazi_personeller'];
            if ($is_superadmin) {
                $allowed_types[] = 'aboneler';
                $allowed_types[] = 'bazi_firmalar';
            }

            if (!in_array($hedef_tip, $allowed_types)) {
                $hedef_tip = 'herkese';
            }

            $hedef_firma_id = ($hedef_tip === 'aboneler' || $hedef_tip === 'bazi_firmalar') ? null : $firma_id;

            // Extract target IDs and mapping type
            $target_ids = [];
            $db_hedef_tip = '';
            if ($hedef_tip === 'bazi_firmalar') {
                $target_ids = $_POST['hedef_firmalar'] ?? [];
                $db_hedef_tip = 'firma';
            } elseif ($hedef_tip === 'bazi_kullanicilar') {
                $target_ids = $_POST['hedef_kullanicilar'] ?? [];
                $db_hedef_tip = 'kullanici';
            } elseif ($hedef_tip === 'bazi_personeller') {
                $target_ids = $_POST['hedef_personeller'] ?? [];
                $db_hedef_tip = 'personel';
            }

            $id = $Duyuru->ekle([
                'baslik'           => $baslik,
                'icerik'           => $icerik,
                'olusturan_id'     => $kullanici_id,
                'hedef_tip'        => $hedef_tip,
                'hedef_firma_id'   => $hedef_firma_id,
                'baslangic_tarihi' => $_POST['baslangic_tarihi'] ?? '',
                'bitis_tarihi'     => $_POST['bitis_tarihi'] ?? '',
                'oncelik'          => $_POST['oncelik'] ?? 'normal',
            ]);

            if (!$id) throw new Exception('Duyuru oluşturulamadı.');

            if (!empty($db_hedef_tip)) {
                $Duyuru->setHedefler($id, $db_hedef_tip, $target_ids);
            } else {
                $Duyuru->setHedefler($id, '', []);
            }

            ActivityLogModel::log('duyuru', 'ekle', "Duyuru eklendi: {$baslik}");
            echo json_encode(['success' => true, 'message' => 'Duyuru oluşturuldu.', 'id' => Security::encrypt($id)]);
            break;

        case 'guncelle':
            if (!$can_add) throw new Exception('Duyuru güncelleme yetkiniz yok.');
            $id = Security::decrypt($_POST['id'] ?? '');
            if (!$id) throw new Exception('Geçersiz duyuru.');

            $duyuru = $Duyuru->find($id);
            if (!$duyuru) throw new Exception('Duyuru bulunamadı.');
            if (!$is_superadmin && $duyuru->olusturan_id != $kullanici_id) {
                throw new Exception('Bu duyuruyu düzenleme yetkiniz yok.');
            }

            $baslik = trim($_POST['baslik'] ?? '');
            $icerik = trim($_POST['icerik'] ?? '');
            if (empty($baslik) || empty($icerik)) {
                throw new Exception('Başlık ve içerik zorunludur.');
            }

            $hedef_tip      = $_POST['hedef_tip'] ?? 'herkese';

            $allowed_types = ['herkese', 'firma_kullanicilari', 'firma_personelleri', 'bazi_kullanicilar', 'bazi_personeller'];
            if ($is_superadmin) {
                $allowed_types[] = 'aboneler';
                $allowed_types[] = 'bazi_firmalar';
            }

            if (!in_array($hedef_tip, $allowed_types)) {
                $hedef_tip = 'herkese';
            }

            $hedef_firma_id = ($hedef_tip === 'aboneler' || $hedef_tip === 'bazi_firmalar') ? null : $firma_id;

            // Extract target IDs and mapping type
            $target_ids = [];
            $db_hedef_tip = '';
            if ($hedef_tip === 'bazi_firmalar') {
                $target_ids = $_POST['hedef_firmalar'] ?? [];
                $db_hedef_tip = 'firma';
            } elseif ($hedef_tip === 'bazi_kullanicilar') {
                $target_ids = $_POST['hedef_kullanicilar'] ?? [];
                $db_hedef_tip = 'kullanici';
            } elseif ($hedef_tip === 'bazi_personeller') {
                $target_ids = $_POST['hedef_personeller'] ?? [];
                $db_hedef_tip = 'personel';
            }

            $Duyuru->guncelle($id, [
                'baslik'           => $baslik,
                'icerik'           => $icerik,
                'hedef_tip'        => $hedef_tip,
                'hedef_firma_id'   => $hedef_firma_id,
                'baslangic_tarihi' => $_POST['baslangic_tarihi'] ?? '',
                'bitis_tarihi'     => $_POST['bitis_tarihi'] ?? '',
                'oncelik'          => $_POST['oncelik'] ?? 'normal',
                'is_active'        => (int) ($_POST['is_active'] ?? 0),
            ]);

            if (!empty($db_hedef_tip)) {
                $Duyuru->setHedefler($id, $db_hedef_tip, $target_ids);
            } else {
                $Duyuru->setHedefler($id, '', []);
            }

            ActivityLogModel::log('duyuru', 'guncelle', "Duyuru güncellendi: {$baslik}");
            echo json_encode(['success' => true, 'message' => 'Duyuru güncellendi.']);
            break;

        case 'sil':
            $id = Security::decrypt($_POST['id'] ?? '');
            if (!$id) throw new Exception('Geçersiz duyuru.');

            $duyuru = $Duyuru->find($id);
            if (!$duyuru) throw new Exception('Duyuru bulunamadı.');
            if (!$is_superadmin && $duyuru->olusturan_id != $kullanici_id) {
                throw new Exception('Bu duyuruyu silme yetkiniz yok.');
            }

            $Duyuru->sil($id);
            ActivityLogModel::log('duyuru', 'sil', "Duyuru silindi: {$duyuru->baslik}");
            echo json_encode(['success' => true, 'message' => 'Duyuru silindi.']);
            break;

        case 'detay':
            $id = Security::decrypt($_POST['id'] ?? '');
            if (!$id) throw new Exception('Geçersiz duyuru.');

            $duyuru = $Duyuru->find($id);
            if (!$duyuru) throw new Exception('Duyuru bulunamadı.');

            $duyuru->created_at_fmt = date('d.m.Y H:i', strtotime($duyuru->created_at));

            $db = $Duyuru->getDb();
            $stmtUser = $db->prepare("SELECT full_name FROM users WHERE id = :id");
            $stmtUser->execute([':id' => $duyuru->olusturan_id]);
            $duyuru->olusturan_adi = $stmtUser->fetch(PDO::FETCH_OBJ)->full_name ?? '-';

            $okundu_stmt = $db->prepare(
                "SELECT id FROM duyuru_okundu WHERE duyuru_id = :did AND kullanici_id = :uid"
            );
            $okundu_stmt->execute([':did' => $id, ':uid' => $kullanici_id]);
            $duyuru->okundu = (bool) $okundu_stmt->fetch();

            $duyuru->hedef_ids = [];
            if (in_array($duyuru->hedef_tip, ['bazi_firmalar', 'bazi_kullanicilar', 'bazi_personeller'])) {
                $hedefler = $Duyuru->getHedefler($id);
                foreach ($hedefler as $h) {
                    $duyuru->hedef_ids[] = (int) $h->hedef_id;
                }
            }

            $response = ['success' => true, 'data' => $duyuru];

            if ($is_superadmin) {
                $response['okuyanlar'] = $Duyuru->getOkunmaDetayi($id);
            }

            echo json_encode($response);
            break;

        case 'okundu':
            $id = Security::decrypt($_POST['id'] ?? '');
            if (!$id) throw new Exception('Geçersiz duyuru.');

            $Duyuru->okunduIsaretle($id, $kullanici_id);
            echo json_encode(['success' => true]);
            break;

        case 'tumunu_okundu':
            $Duyuru->tumunuOkunduIsaretle($kullanici_id, $firma_id);
            echo json_encode(['success' => true]);
            break;

        case 'okunmamis_sayisi':
            $sayi = $Duyuru->getOkunmamisSayisi($kullanici_id, $firma_id, $is_main_user);
            echo json_encode(['success' => true, 'sayi' => $sayi]);
            break;

        default:
            throw new Exception('Geçersiz işlem.');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
