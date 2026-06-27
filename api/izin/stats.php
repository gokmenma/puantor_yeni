<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (!defined('ROOT')) define('ROOT', dirname(__DIR__, 2));

try {
    require_once ROOT . '/Database/require.php';
    require_once ROOT . '/App/Helper/security.php';
    require_once ROOT . '/Model/IzinTalep.php';
    require_once ROOT . '/Model/IzinHakedis.php';

    if (!isset($_SESSION['user'])) {
        ob_clean(); echo json_encode(['status' => 'error', 'message' => 'Oturum kapalı.'], JSON_UNESCAPED_UNICODE); exit;
    }

    $firma_id  = (int) ($_SESSION['firm_id'] ?? 0);
    $talep     = new IzinTalep();
    $hakedis   = new IzinHakedis();

    $data = [
        'bugun_izinliler'     => $talep->getBugunIzinliler($firma_id),
        'bekleyen_sayi'       => $talep->getBekleyenSayisi($firma_id),
        'bu_ay_kullanilan_gun'=> $talep->getBuAyKullanilanGun($firma_id),
        'en_cok_kullananlar'  => $talep->getEnCokKullananlar($firma_id),
        'yaklasan_hakedisler' => $hakedis->getYaklasanHakedisler($firma_id),
    ];

    ob_clean();
    echo json_encode(['status' => 'success', 'data' => $data], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
