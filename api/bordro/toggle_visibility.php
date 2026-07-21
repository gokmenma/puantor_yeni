<?php
header('Content-Type: application/json');
!defined("ROOT") ? define("ROOT", dirname(dirname(__DIR__))) : false;
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Bordro.php';
require_once ROOT . '/Model/Auths.php';
require_once ROOT . '/Model/ActivityLogModel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$Auths = new Auths();
if (!isset($_SESSION['firm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Oturum bulunamadı.']);
    exit;
}

// Yetki Kontrolü: Bordro dönemini kapatma/açma yetkisi
if (!$Auths->hasPermission('toggle_payroll_period_status') && !$Auths->Authorize('toggle_payroll_period_status')) {
    echo json_encode(['status' => 'error', 'message' => 'Bu işlemi yapmak için yetkiniz bulunmamaktadır.']);
    exit;
}

$firm_id = (int)$_SESSION['firm_id'];
$year = isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y');
$month = isset($_POST['month']) ? (int)$_POST['month'] : (int)date('m');
$is_closed = isset($_POST['is_closed']) ? (int)$_POST['is_closed'] : (isset($_POST['is_visible']) ? (int)$_POST['is_visible'] : 0);

if ($year <= 0 || $month <= 0 || $month > 12) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz dönem.']);
    exit;
}

$bordro = new Bordro();
$success = $bordro->setPeriodVisibility($firm_id, $year, $month, $is_closed);

if ($success) {
    $statusText = $is_closed === 1 ? 'Kapalı (Kilitli)' : 'Açık';
    ActivityLogModel::log('payroll', 'toggle_period_status', "Bordro dönem durumu değiştirildi. Yıl: {$year}, Ay: {$month}, Durum: {$statusText}");
    echo json_encode([
        'status' => 'success',
        'is_closed' => $is_closed,
        'message' => "{$year}/{$month} dönemi bordrosu " . ($is_closed === 1 ? 'kapatıldı (PWA personellere açıldı, puantaj kilitlendi).' : 'açık konuma getirildi.')
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Dönem durumu güncellenemedi.']);
}
