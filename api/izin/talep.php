<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

use App\Helper\Security;

try {
    require_once ROOT . '/Database/require.php';
    require_once ROOT . '/Model/Auths.php';
    require_once ROOT . '/App/Helper/security.php';
    require_once ROOT . '/Model/IzinTalep.php';
    require_once ROOT . '/Model/IzinTur.php';
    if (file_exists(ROOT . '/vendor/autoload.php')) require_once ROOT . '/vendor/autoload.php';
    require_once ROOT . '/Service/WebPushSender.php';
    require_once ROOT . '/Service/PushBildirimService.php';

    $Auths = new Auths();
    if (!isset($_SESSION['user'])) {
        ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.'], JSON_UNESCAPED_UNICODE); exit;
    }

    $action   = $_REQUEST['action'] ?? '';
    $firma_id = (int) ($_SESSION['firm_id'] ?? 0);
    $user_id  = is_object($_SESSION['user']) ? (int)($_SESSION['user']->id ?? 0) : (int)$_SESSION['user'];
    $model    = new IzinTalep();

    if ($action === 'list') {
        $filters = [
            'durum'       => $_GET['durum'] ?? '',
            'personel_id' => !empty($_GET['personel_id']) ? (int) Security::safeDecrypt($_GET['personel_id']) : null,
            'baslangic'   => $_GET['baslangic'] ?? '',
            'bitis'       => $_GET['bitis'] ?? '',
        ];
        $liste = $model->getByFirma($firma_id, array_filter($filters));
        ob_clean();
        echo json_encode(['status' => 'success', 'list' => $liste], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'calc_gun') {
        $baslangic = $_GET['baslangic'] ?? '';
        $bitis     = $_GET['bitis'] ?? '';
        if (!$baslangic || !$bitis) throw new Exception('Tarih belirtilmedi.');
        $gun = $model->calcIsGunu($baslangic, $bitis);
        ob_clean();
        echo json_encode(['status' => 'success', 'gun_sayisi' => $gun], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'add') {
        $personel_id = (int) Security::safeDecrypt($_POST['personel_id'] ?? '');
        $tur_id      = (int) ($_POST['tur_id'] ?? 0);
        $baslangic   = trim($_POST['baslangic_tarihi'] ?? '');
        $bitis       = trim($_POST['bitis_tarihi'] ?? '');
        $aciklama    = trim($_POST['aciklama'] ?? '');
        $adres       = trim($_POST['adres'] ?? '');

        if (!$personel_id || !$tur_id || !$baslangic || !$bitis) throw new Exception('Eksik veri.');
        if ($bitis < $baslangic) throw new Exception('Bitiş tarihi başlangıçtan önce olamaz.');

        $sonuc = $model->olustur([
            'firma_id'        => $firma_id,
            'personel_id'     => $personel_id,
            'tur_id'          => $tur_id,
            'baslangic_tarihi'=> $baslangic,
            'bitis_tarihi'    => $bitis,
            'aciklama'        => $aciklama,
            'adres'           => $adres,
            'olusturan_id'    => $user_id,
        ]);

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'approve') {
        $id = (int) Security::safeDecrypt($_POST['id'] ?? '');
        if (!$id) throw new Exception('Geçersiz ID.');

        $talep = $model->find($id);
        if (!$talep || (int) $talep->firma_id !== $firma_id) throw new Exception('Talep bulunamadı.');

        $sonuc = $model->onayla($id, $user_id);

        if ($sonuc['success']) {
            try {
                $mesaj_onay = "İzin talebiniz onaylandı ({$talep->baslangic_tarihi} - {$talep->bitis_tarihi}).";
                $pushService = new \Service\PushBildirimService($db);
                $pushService->personeleGonder((int) $talep->personel_id, $firma_id, 'İzin Talebiniz Onaylandı', $mesaj_onay, []);
                require_once ROOT . '/Model/PersonelBildirimModel.php';
                (new PersonelBildirimModel($db))->kaydet((int) $talep->personel_id, $firma_id, 'İzin Talebiniz Onaylandı', $mesaj_onay, 'leave');
            } catch (\Throwable $pe) {}
        }

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'partial_approve') {
        $id         = (int) Security::safeDecrypt($_POST['id'] ?? '');
        $yeni_bitis = trim($_POST['yeni_bitis'] ?? '');
        if (!$id || !$yeni_bitis) throw new Exception('Eksik veri.');

        $talep = $model->find($id);
        if (!$talep || (int) $talep->firma_id !== $firma_id) throw new Exception('Talep bulunamadı.');

        $sonuc = $model->kismiOnayla($id, $user_id, $yeni_bitis);

        if ($sonuc['success']) {
            try {
                $mesaj_kismi = "İzin talebiniz kısmi onaylandı ({$talep->baslangic_tarihi} - {$yeni_bitis}).";
                $pushService = new \Service\PushBildirimService($db);
                $pushService->personeleGonder((int) $talep->personel_id, $firma_id, 'İzin Talebiniz Kısmi Onaylandı', $mesaj_kismi, []);
                require_once ROOT . '/Model/PersonelBildirimModel.php';
                (new PersonelBildirimModel($db))->kaydet((int) $talep->personel_id, $firma_id, 'İzin Talebiniz Kısmi Onaylandı', $mesaj_kismi, 'leave');
            } catch (\Throwable $pe) {}
        }

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'reject') {
        $id  = (int) Security::safeDecrypt($_POST['id'] ?? '');
        $not = trim($_POST['not'] ?? '');
        if (!$id) throw new Exception('Geçersiz ID.');

        $talep = $model->find($id);
        if (!$talep || (int) $talep->firma_id !== $firma_id) throw new Exception('Talep bulunamadı.');

        $sonuc = $model->reddet($id, $user_id, $not);

        if ($sonuc['success']) {
            try {
                $mesaj_red = "İzin talebiniz reddedildi ({$talep->baslangic_tarihi} - {$talep->bitis_tarihi}).";
                $pushService = new \Service\PushBildirimService($db);
                $pushService->personeleGonder((int) $talep->personel_id, $firma_id, 'İzin Talebiniz Reddedildi', $mesaj_red, []);
                require_once ROOT . '/Model/PersonelBildirimModel.php';
                (new PersonelBildirimModel($db))->kaydet((int) $talep->personel_id, $firma_id, 'İzin Talebiniz Reddedildi', $mesaj_red, 'leave');
            } catch (\Throwable $pe) {}
        }

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'cancel') {
        $id = (int) Security::safeDecrypt($_POST['id'] ?? '');
        if (!$id) throw new Exception('Geçersiz ID.');

        $talep = $model->find($id);
        if (!$talep || (int) $talep->firma_id !== $firma_id) throw new Exception('Talep bulunamadı.');

        $sonuc = $model->iptalEt($id, (int) $talep->personel_id);

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'delete') {
        $Auths->hasPermissionReturn('onayli_izinleri_sil');

        $id = (int) Security::safeDecrypt($_POST['id'] ?? '');
        if (!$id) throw new Exception('Geçersiz ID.');

        $talep = $model->find($id);
        if (!$talep || (int) $talep->firma_id !== $firma_id) throw new Exception('Talep bulunamadı.');

        $sonuc = $model->sil($id);

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'detail') {
        $id = (int) Security::safeDecrypt($_GET['id'] ?? '');
        if (!$id) throw new Exception('Geçersiz ID.');
        $talep = $model->find($id);
        if (!$talep || (int) $talep->firma_id !== $firma_id) throw new Exception('Bulunamadı.');
        ob_clean();
        echo json_encode(['status' => 'success', 'talep' => $talep], JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (\Throwable $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
