<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

use App\Helper\Security;

try {
    if (session_status() === PHP_SESSION_NONE) session_start();
    require_once __DIR__ . '/../../Database/require.php';
    require_once __DIR__ . '/../../Model/Persons.php';
    require_once __DIR__ . '/../../App/Helper/security.php';

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

        // Şifre kontrolü - Hem modern password_verify hem de legacy MD5 desteği
        $is_valid = false;
        if (password_verify($password, $person->password)) {
            $is_valid = true;
        } else if (md5($password) === $person->password) {
            $is_valid = true;
        } else if ($password === $person->password) {
            $is_valid = true;
        }

        if ($is_valid) {
            // Başarılı giriş - denemeleri sıfırla
            $_SESSION['login_attempts'] = 0;
            
            // Session'a kullanıcı bilgilerini yaz
            $_SESSION['personel_user'] = $person;
            $_SESSION['personel_id'] = $person->id;
            $_SESSION['firm_id'] = $person->firm_id;
            
            // Remove sensitive data for JSON response
            $person_response = clone $person;
            unset($person_response->password);
            
            // Decrypt sensitive fields for frontend display
            $person_response->kimlik_no = Security::safeDecrypt($person->kimlik_no);
            $person_response->iban_number = Security::safeDecrypt($person->iban_number);
            
            echo json_encode(['status' => 'success', 'user' => $person_response]);
            exit;
        } else {
            // Hatalı giriş - denemeyi artır
            $_SESSION['login_attempts'] = ($attempts < 3) ? $attempts + 1 : 1;
            $_SESSION['last_attempt_time'] = $now;
            
            echo json_encode(['status' => 'error', 'message' => "Şifre hatalı."]);
            exit;
        }
    } else if ($action == 'logout') {
        session_destroy();
        echo json_encode(['status' => 'success']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Sistem hatası: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    echo json_encode(['status' => 'error', 'message' => 'Kritik hata: ' . $e->getMessage()]);
    exit;
}

