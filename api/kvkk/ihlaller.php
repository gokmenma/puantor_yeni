<?php
ob_start();
!defined('ROOT') ? define('ROOT', dirname(__DIR__, 2)) : null;
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/VeriIhlalerModel.php';
require_once ROOT . '/Model/Auths.php';

$Auths  = new Auths();
$Model  = new VeriIhlalerModel();
$firma_id = (int) ($_SESSION['firm_id'] ?? 0);
$user_id  = (int) ($_SESSION['user']->id ?? 0);
$action   = $_POST['action'] ?? '';

header('Content-Type: application/json');
ob_end_clean();

if ($action === 'kaydet') {
    $Auths->hasPermissionReturn('kvkk_ihlal_yonet');

    $ihlal_tarihi  = $_POST['ihlal_tarihi'] ?? '';
    $tespit_tarihi = $_POST['tespit_tarihi'] ?? '';
    $ihlal_turu    = trim($_POST['ihlal_turu'] ?? '');
    $etkilenen_veri = trim($_POST['etkilenen_veri'] ?? '');
    $etkilenen_kisi_sayisi = (int)($_POST['etkilenen_kisi_sayisi'] ?? 0);
    $onlem_alinan  = trim($_POST['onlem_alinan'] ?? '');
    $aciklama      = trim($_POST['aciklama'] ?? '');

    if (empty($ihlal_tarihi) || empty($tespit_tarihi) || empty($ihlal_turu)) {
        echo json_encode(['status' => 'error', 'message' => 'İhlal tarihi, tespit tarihi ve ihlal türü zorunludur.']);
        exit;
    }

    $id = $Model->create([
        'firma_id'             => $firma_id,
        'ihlal_tarihi'         => date('Y-m-d H:i:s', strtotime($ihlal_tarihi)),
        'tespit_tarihi'        => date('Y-m-d H:i:s', strtotime($tespit_tarihi)),
        'ihlal_turu'           => $ihlal_turu,
        'etkilenen_veri'       => $etkilenen_veri ?: null,
        'etkilenen_kisi_sayisi' => $etkilenen_kisi_sayisi,
        'onlem_alinan'         => $onlem_alinan ?: null,
        'aciklama'             => $aciklama ?: null,
        'olusturan_id'         => $user_id,
    ]);

    require_once ROOT . '/Model/ActivityLogModel.php';
    ActivityLogModel::log('kvkk', 'ihlal_kaydet', "Veri ihlali kaydedildi: #{$id}");

    echo json_encode(['status' => 'success', 'message' => 'Veri ihlali kaydedildi.', 'id' => $id]);
    exit;
}

if ($action === 'bildir') {
    $Auths->hasPermissionReturn('kvkk_ihlal_yonet');

    $id           = (int)($_POST['id'] ?? 0);
    $referans_no  = trim($_POST['referans_no'] ?? '');

    if ($id <= 0 || empty($referans_no)) {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
        exit;
    }

    $ok = $Model->bildiriGuncelle($id, $firma_id, $referans_no);

    require_once ROOT . '/Model/ActivityLogModel.php';
    ActivityLogModel::log('kvkk', 'kvkk_bildir', "Veri ihlali KVKK'ya bildirildi: #{$id} - Ref: {$referans_no}");

    echo json_encode(['status' => $ok ? 'success' : 'error', 'message' => $ok ? 'Bildirim kaydedildi.' : 'Kayıt bulunamadı.']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem.']);
