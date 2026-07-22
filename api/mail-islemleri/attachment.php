<?php

error_reporting(0);
ini_set('display_errors', '0');

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/SettingsModel.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/Service/MailGelenKutuService.php';

use Service\MailGelenKutuService;

if (!isset($_SESSION['user']) || (int) ($_SESSION['user']->superadmin ?? 0) !== 1) {
    http_response_code(403);
    exit('Bu işlem için yetkiniz yok.');
}

$account = (string) ($_GET['account'] ?? 'info');
$uid = (int) ($_GET['uid'] ?? 0);
$part = (string) ($_GET['part'] ?? '');

try {
    $service = new MailGelenKutuService(new SettingsModel());
    $attachment = $service->getAttachment($account, $uid, $part);
    $filename = $attachment['filename'];
    header('Content-Type: ' . $attachment['mime']);
    header('Content-Length: ' . strlen($attachment['data']));
    header("Content-Disposition: attachment; filename*=UTF-8''" . rawurlencode($filename));
    header('X-Content-Type-Options: nosniff');
    ActivityLogModel::log('mail_islemleri', 'download_attachment', "Gelen mail eki indirildi. Hesap: {$account}, UID: {$uid}.");
    echo $attachment['data'];
} catch (Throwable $e) {
    error_log('Mail attachment error: ' . $e->getMessage());
    http_response_code(404);
    echo 'Ek indirilemedi.';
}
