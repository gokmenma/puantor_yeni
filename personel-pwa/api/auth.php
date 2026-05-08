<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database/require.php';
require_once __DIR__ . '/../../Model/Persons.php';
require_once __DIR__ . '/../../App/Helper/security.php';

use App\Helper\Security;

$action = $_POST['action'] ?? '';

if ($action == 'login') {
    $kimlik_no = $_POST['kimlik_no'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($kimlik_no) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'TC Kimlik No ve şifre gereklidir.']);
        exit;
    }

    $Persons = new Persons();
    $person = $Persons->getPersonByKimlikNo($kimlik_no);

    if ($person && !empty($person->password) && password_verify($password, $person->password)) {
        // Login success
        // Remove sensitive data
        unset($person->password);
        
        // Decrypt sensitive fields for frontend display
        $person->kimlik_no = Security::safeDecrypt($person->kimlik_no);
        $person->iban_number = Security::safeDecrypt($person->iban_number);
        
        echo json_encode(['status' => 'success', 'user' => $person]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Hatalı TC Kimlik No veya şifre.']);
    }
}
