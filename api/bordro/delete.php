<?php
require_once '../../Model/Bordro.php';
require_once '../../Database/require.php';
require_once '../../App/Helper/security.php';

use App\Helper\Security;

if (isset($_POST['id']) && isset($_POST['month']) && isset($_POST['year'])) {
    $bordro = new Bordro();
    
    $encrypted_id = $_POST['id'];
    $person_id = Security::decrypt($encrypted_id);
    $month = $_POST['month'];
    $year = $_POST['year'];
    $project_id = !empty($_POST['project_id']) ? $_POST['project_id'] : 0;
    
    $result = $bordro->deletePersonMonthlyRecords($person_id, $month, $year, $project_id, $encrypted_id);
    
    if ($result) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Bordro kayıtları başarıyla silindi.'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Kayıtlar silinirken bir hata oluştu.'
        ]);
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Eksik parametre.'
    ]);
}
