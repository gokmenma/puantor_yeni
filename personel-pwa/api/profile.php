<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../Database/require.php';
require_once __DIR__ . '/../../Model/Persons.php';
require_once __DIR__ . '/../../App/Helper/security.php';

use App\Helper\Security;

$action = $_POST['action'] ?? '';
$person_id = $_POST['person_id'] ?? 0;

if (!$person_id) {
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz personel.']);
    exit;
}

if ($action == 'update') {
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $iban = $_POST['iban_number'] ?? '';
    $password = $_POST['password'] ?? '';

    $data = [
        'id' => $person_id,
        'phone' => $phone,
        'email' => $email,
        'iban_number' => Security::encrypt($iban)
    ];

    if (!empty($password)) {
        $data['password'] = password_hash($password, PASSWORD_DEFAULT);
    }

    $Persons = new Persons();
    try {
        $Persons->saveWithAttr($data);
        echo json_encode(['status' => 'success', 'message' => 'Profil güncellendi.']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Hata: ' . $e->getMessage()]);
    }
} elseif ($action == 'change_password') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';

    $query = $db->prepare("SELECT password FROM persons WHERE id = ?");
    $query->execute([$person_id]);
    $person = $query->fetch(PDO::FETCH_OBJ);

    if ($person && password_verify($current_password, $person->password)) {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $db->prepare("UPDATE persons SET password = ? WHERE id = ?");
        $update->execute([$hashed, $person_id]);
        echo json_encode(['status' => 'success', 'message' => 'Şifre güncellendi.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Mevcut şifre hatalı.']);
    }
}
