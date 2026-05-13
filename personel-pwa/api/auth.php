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

    // Giriş denemesi kontrolü
    $now = time();
    $attempts = $_SESSION['login_attempts'] ?? 0;
    $last_attempt = $_SESSION['last_attempt_time'] ?? 0;

    if ($attempts >= 3 && ($now - $last_attempt) < 60) {
        $remaining = 60 - ($now - $last_attempt);
        echo json_encode(['status' => 'error', 'message' => "Çok fazla hatalı giriş denemesi. Lütfen $remaining saniye bekleyin."]);
        exit;
    }

    if (empty($kimlik_no) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Kimlik bilgileri ve şifre gereklidir.']);
        exit;
    }

    $Persons = new Persons();
    $person = $Persons->getPersonByAuthField($kimlik_no);

    if (!$person) {
        echo json_encode(['status' => 'error', 'message' => "Girdiğiniz bilgilere ait personel bulunamadı. Lütfen bilgileri kontrol edin."]);
        exit;
    }

    // İşten çıkış kontrolü
    if (!empty($person->job_end_date)) {
        echo json_encode(['status' => 'error', 'message' => 'İşten ayrılmış personeller sisteme giriş yapamaz.']);
        exit;
    }

    if (empty($person->password)) {
        echo json_encode(['status' => 'error', 'message' => "Hesabınız (ID: {$person->id}) için şifre tanımlanmamış. Lütfen yönetici ile iletişime geçin."]);
        exit;
    }

    if (password_verify($password, $person->password)) {
        // Başarılı giriş - denemeleri sıfırla
        $_SESSION['login_attempts'] = 0;
        
        // Remove sensitive data
        unset($person->password);
        
        // Decrypt sensitive fields for frontend display
        $person->kimlik_no = Security::safeDecrypt($person->kimlik_no);
        $person->iban_number = Security::safeDecrypt($person->iban_number);
        
        echo json_encode(['status' => 'success', 'user' => $person]);
        exit;
    } else {
        // Hatalı giriş - denemeyi artır
        $_SESSION['login_attempts'] = ($attempts < 3) ? $attempts + 1 : 1;
        $_SESSION['last_attempt_time'] = $now;
        
        $message = "Şifre hatalı.";
        if ($_SESSION['login_attempts'] >= 3) {
            $message .= " 3 hatalı deneme nedeniyle 1 dakika bekletiliyorsunuz.";
        }

        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
}
