<?php
ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
ob_start();

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

require_once ROOT . '/Database/require.php';
if (file_exists(ROOT . '/vendor/autoload.php')) require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/Service/WebPushSender.php';
require_once ROOT . '/Service/PushBildirimService.php';
require_once ROOT . '/Model/IzinTalep.php';
require_once ROOT . '/Model/IzinHakedis.php';
require_once ROOT . '/Model/IzinTur.php';
require_once ROOT . '/Model/DuyuruModel.php';
require_once ROOT . '/Model/UserModel.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/ActivityLogModel.php';

use Service\PushBildirimService;

$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'list') {
        $person_id = (int) ($_GET['person_id'] ?? 0);
        if (!$person_id) throw new Exception('Personel belirtilmedi.');

        $model = new IzinTalep();
        $hakedis = new IzinHakedis();

        $talepler = $model->getByPersonel($person_id);
        $kalan    = $hakedis->getTotalKalan($person_id);

        ob_clean();
        echo json_encode([
            'status'    => 'success',
            'list'      => $talepler,
            'kalan_gun' => $kalan,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'hakedisler') {
        $person_id = (int) ($_GET['person_id'] ?? 0);
        if (!$person_id) throw new Exception('Personel belirtilmedi.');

        $hakedis = new IzinHakedis();
        $liste = $hakedis->getByPersonel($person_id);

        ob_clean();
        echo json_encode([
            'status' => 'success',
            'list'   => $liste
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'turler') {
        $model = new IzinTur();
        $turler = array_values(array_filter($model->getAktifTurler(), function($t) {
            return in_array($t->kod, ['yillik', 'ucretsiz']);
        }));
        ob_clean();
        echo json_encode(['status' => 'success', 'list' => $turler], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'calc_gun') {
        $baslangic = $_GET['baslangic'] ?? '';
        $bitis     = $_GET['bitis'] ?? '';
        if (!$baslangic || !$bitis) throw new Exception('Tarih belirtilmedi.');
        
        $firm_id = (int) ($_SESSION['firm_id'] ?? 0);
        if ($firm_id <= 0) {
            $person_id = (int) ($_GET['person_id'] ?? $_POST['person_id'] ?? 0);
            if ($person_id > 0) {
                $personObj = (new Persons())->find($person_id);
                if ($personObj) {
                    $firm_id = (int) $personObj->firm_id;
                }
            }
        }
        
        $model = new IzinTalep();
        ob_clean();
        echo json_encode(['status' => 'success', 'gun_sayisi' => $model->calcIsGunu($baslangic, $bitis, $firm_id)], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'create') {
        $person_id  = (int) ($_POST['person_id'] ?? 0);
        $firm_id    = (int) ($_POST['firm_id'] ?? 0);
        $tur_id     = (int) ($_POST['tur_id'] ?? 0);
        $baslangic  = trim($_POST['baslangic_tarihi'] ?? '');
        $bitis      = trim($_POST['bitis_tarihi'] ?? '');
        $aciklama   = trim($_POST['aciklama'] ?? '');
        $adres      = trim($_POST['adres'] ?? '');

        if (!$person_id || !$firm_id || !$tur_id || !$baslangic || !$bitis) {
            throw new Exception('Eksik veri.');
        }
        if ($bitis < $baslangic) throw new Exception('Bitiş tarihi başlangıçtan önce olamaz.');

        $model = new IzinTalep();
        $sonuc = $model->olustur([
            'firma_id'         => $firm_id,
            'personel_id'      => $person_id,
            'tur_id'           => $tur_id,
            'baslangic_tarihi' => $baslangic,
            'bitis_tarihi'     => $bitis,
            'aciklama'         => $aciklama,
            'adres'            => $adres,
            'olusturan_id'     => $person_id,
        ]);

        if ($sonuc['success']) {
            try {
                $Duyuru  = new DuyuruModel();
                $User    = new UserModel();
                $Person  = new Persons();
                $TurMdl  = new IzinTur();

                $person  = $Person->find($person_id);
                $tur     = $TurMdl->find($tur_id);
                $isim    = $person ? $person->full_name : "Personel #{$person_id}";
                $tur_adi = $tur ? $tur->ad : 'İzin';

                $gun_sayisi = (new IzinTalep())->calcIsGunu($baslangic, $bitis);
                $mesaj = "{$isim} {$gun_sayisi} günlük {$tur_adi} talebi oluşturdu ({$baslangic} - {$bitis}).";

                $yoneticiler = $User->allByFirms($firm_id);
                foreach ($yoneticiler as $y) {
                    $Duyuru->saveWithAttr([
                        'baslik'          => 'Yeni İzin Talebi',
                        'icerik'          => $mesaj,
                        'oncelik'         => 'normal',
                        'hedef_firma_id'  => $firm_id,
                        'hedef_tip'       => 'bazi_kullanicilar',
                        'olusturan_id'    => $y->id,
                        'is_active'       => 1,
                    ]);
                }

                $pushService = new PushBildirimService($db);
                $pushService->yoneticilereGonder(
                    $firm_id,
                    'Yeni İzin Talebi',
                    $mesaj,
                    ['url' => 'modules/more/leave_requests.php']
                );
            } catch (Exception $ne) {}
        }

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'update') {
        $talep_id   = (int) ($_POST['talep_id'] ?? 0);
        $person_id  = (int) ($_POST['person_id'] ?? 0);
        $tur_id     = (int) ($_POST['tur_id'] ?? 0);
        $baslangic  = trim($_POST['baslangic_tarihi'] ?? '');
        $bitis      = trim($_POST['bitis_tarihi'] ?? '');
        $aciklama   = trim($_POST['aciklama'] ?? '');
        $adres      = trim($_POST['adres'] ?? '');

        if (!$talep_id || !$person_id || !$tur_id || !$baslangic || !$bitis) {
            throw new Exception('Eksik veri.');
        }
        if ($bitis < $baslangic) throw new Exception('Bitiş tarihi başlangıçtan önce olamaz.');

        $model = new IzinTalep();
        $sonuc = $model->guncelle($talep_id, [
            'personel_id'      => $person_id,
            'tur_id'           => $tur_id,
            'baslangic_tarihi' => $baslangic,
            'bitis_tarihi'     => $bitis,
            'aciklama'         => $aciklama,
            'adres'            => $adres,
        ]);

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'cancel') {
        $talep_id  = (int) ($_POST['talep_id'] ?? 0);
        $person_id = (int) ($_POST['person_id'] ?? 0);
        if (!$talep_id || !$person_id) throw new Exception('Eksik veri.');

        $model = new IzinTalep();
        $sonuc = $model->iptalEt($talep_id, $person_id);

        ob_clean();
        echo json_encode(array_merge(['status' => $sonuc['success'] ? 'success' : 'error'], $sonuc), JSON_UNESCAPED_UNICODE);
        exit;
    }

    throw new Exception('Geçersiz işlem.');

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
