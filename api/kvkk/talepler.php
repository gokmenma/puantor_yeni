<?php
ob_start();
!defined('ROOT') ? define('ROOT', dirname(__DIR__, 2)) : null;
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/KvkkTalepModel.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

$Auths = new Auths();
$Model = new KvkkTalepModel();
$firma_id = (int) ($_SESSION['firm_id'] ?? 0);
$user_id  = (int) ($_SESSION['user']->id ?? 0);
$action   = $_POST['action'] ?? '';

header('Content-Type: application/json');
ob_end_clean();

if ($action === 'kaydet') {
    $Auths->hasPermissionReturn('kvkk_talepler_yonet');

    $talep_turu    = $_POST['talep_turu'] ?? '';
    $basvuran_ad   = trim($_POST['basvuran_ad'] ?? '');
    $basvuran_email = trim($_POST['basvuran_email'] ?? '');
    $basvuran_tc   = preg_replace('/[^0-9]/', '', $_POST['basvuran_tc'] ?? '');
    $aciklama      = trim($_POST['aciklama'] ?? '');
    $atanan        = !empty($_POST['atanan_kullanici']) ? (int) $_POST['atanan_kullanici'] : null;

    $turler = ['erisim', 'duzeltme', 'silme', 'itiraz', 'aktarim'];
    if (empty($basvuran_ad) || !in_array($talep_turu, $turler)) {
        echo json_encode(['status' => 'error', 'message' => 'Başvuran adı ve talep türü zorunludur.']);
        exit;
    }

    $id = $Model->create([
        'firma_id'        => $firma_id,
        'talep_turu'      => $talep_turu,
        'basvuran_ad'     => $basvuran_ad,
        'basvuran_email'  => $basvuran_email ?: null,
        'basvuran_tc'     => $basvuran_tc ?: null,
        'aciklama'        => $aciklama ?: null,
        'atanan_kullanici' => $atanan,
        'olusturan_id'    => $user_id,
    ]);

    require_once ROOT . '/Model/ActivityLogModel.php';
    ActivityLogModel::log('kvkk', 'talep_olustur', "KVKK talebi oluşturuldu: #{$id} - {$basvuran_ad}");

    echo json_encode(['status' => 'success', 'message' => 'Talep başarıyla kaydedildi.', 'id' => $id]);
    exit;
}

if ($action === 'durum_guncelle') {
    $Auths->hasPermissionReturn('kvkk_talepler_yonet');

    $id          = (int) ($_POST['id'] ?? 0);
    $durum       = $_POST['durum'] ?? '';
    $yanit_notu  = trim($_POST['yanit_notu'] ?? '');

    $durumlar = ['bekliyor', 'isleniyor', 'tamamlandi', 'reddedildi'];
    if ($id <= 0 || !in_array($durum, $durumlar)) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
        exit;
    }

    $ok = $Model->updateDurum($id, $firma_id, $durum, $yanit_notu ?: null);

    require_once ROOT . '/Model/ActivityLogModel.php';
    ActivityLogModel::log('kvkk', 'durum_guncelle', "KVKK talep durumu güncellendi: #{$id} -> {$durum}");

    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Durum güncellendi.' : 'Kayıt bulunamadı.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem.']);
