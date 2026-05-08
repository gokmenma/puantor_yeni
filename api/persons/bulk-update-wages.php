<?php

session_start();
define('ROOT', dirname(__DIR__, 2));
require ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/App/Helper/helper.php';

use App\Helper\Helper;

header('Content-Type: application/json');

if (!isset($_SESSION['firm_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Yetkisiz erişim.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit;
}

$firm_id = $_SESSION['firm_id'];
$wages = $_POST['wages'] ?? [];
$personObj = new Persons();

$successCount = 0;
$errorCount = 0;

foreach ($wages as $id => $wage) {
    // Güvenlik: Personelin bu firmaya ait olup olmadığını kontrol et
    $person = $personObj->find($id);
    if ($person && $person->firm_id == $firm_id) {
        $formattedWage = Helper::formattedMoneyToNumber($wage);
        
        $data = [
            'id' => $id,
            'daily_wages' => $formattedWage
        ];
        
        try {
            $personObj->saveWithAttr($data);
            $successCount++;
        } catch (Exception $e) {
            $errorCount++;
        }
    } else {
        $errorCount++;
    }
}

if ($successCount > 0) {
    echo json_encode([
        'status' => 'success', 
        'message' => "$successCount personelin ücreti başarıyla güncellendi." . ($errorCount > 0 ? " ($errorCount hata oluştu)" : "")
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Güncelleme yapılamadı.']);
}
