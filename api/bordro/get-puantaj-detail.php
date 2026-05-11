<?php
header('Content-Type: application/json');

!defined('ROOT') ? define('ROOT', $_SERVER['DOCUMENT_ROOT']) : null;
require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/Puantaj.php';
require_once ROOT . '/App/Helper/date.php';
require_once ROOT . '/App/Helper/helper.php';

use App\Helper\Date;
use App\Helper\Helper;

try {
    $person_id = $_POST['person_id'] ?? 0;
    $ay = $_POST['ay'] ?? '';
    $yil = $_POST['yil'] ?? '';

    if (!$person_id || !$ay || !$yil) {
        echo json_encode(['status' => 'error', 'message' => 'Eksik parametreler']);
        exit;
    }

    $db = (new Database\Db())->connect();
    
    // Tarih aralığını belirle
    $firstDay = Date::firstDay($ay, $yil);
    $lastDay = Date::lastDay($ay, $yil);

    // Puantaj kayıtlarını detaylı getir (Proje ve Tür isimleri ile)
    $sql = "SELECT pt.*, p.project_name, tr.PuantajAdi as puantaj_adi 
            FROM puantaj pt 
            LEFT JOIN projects p ON p.id = pt.project_id 
            LEFT JOIN puantajturu tr ON tr.id = pt.puantaj_id 
            WHERE pt.person = :person_id 
            AND pt.gun >= :start_date AND pt.gun <= :end_date 
            ORDER BY pt.gun ASC";
            
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':person_id' => $person_id,
        ':start_date' => $firstDay,
        ':end_date' => $lastDay
    ]);
    
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $formatted_data = [];
    foreach ($results as $row) {
        $row['gun_formatted'] = Date::dmY($row['gun']);
        $row['tutar_formatted'] = Helper::formattedMoney($row['tutar']);
        $formatted_data[] = $row;
    }

    echo json_encode([
        'status' => 'success',
        'data' => $formatted_data
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
