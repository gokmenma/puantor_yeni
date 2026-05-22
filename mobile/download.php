<?php
// Puantor Mobil - Excel Indirme Yönlendirici (PWA Scope Uyumlu)
session_start();

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__));
}

$type = $_GET['type'] ?? '';
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Modüle göre ilgili rapor oluşturma betiğini dahil et
if ($type === 'puantaj') {
    require_once ROOT . '/pages/raporlar/puantaj-list-excel.php';
} elseif ($type === 'banka') {
    require_once ROOT . '/pages/raporlar/bank-list-excel.php';
} elseif ($type === 'kesinti') {
    require_once ROOT . '/pages/raporlar/kesinti-list-excel.php';
} else {
    header("Location: index.php");
    exit();
}
