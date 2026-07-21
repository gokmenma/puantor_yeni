<?php
define("ROOT", $_SERVER["DOCUMENT_ROOT"]);
require_once ROOT . '/Database/require.php';
require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/Auths.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Oturum açmanız gerekmektedir.']);
    exit;
}

$Auths = new Auths();
$Auths->hasPermissionReturn('aboneler_sayfasi');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek yöntemi.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_ids = $input['user_ids'] ?? [];
$subject  = trim($input['subject'] ?? '');
$body     = trim($input['body'] ?? '');

if (empty($user_ids) || $subject === '' || $body === '') {
    echo json_encode(['success' => false, 'message' => 'Eksik parametreler.']);
    exit;
}

$user_ids = array_values(array_filter(array_map('intval', $user_ids), fn($id) => $id > 0));
if (empty($user_ids)) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz kullanıcı listesi.']);
    exit;
}

// DB bağlantısı ($db require.php'den geliyor)
try {
    $placeholders = implode(',', array_fill(0, count($user_ids), '?'));
    $stmt = $db->prepare(
        "SELECT full_name, email FROM users
         WHERE id IN ($placeholders)
           AND (parent_id = 0 OR parent_id = id)
           AND email IS NOT NULL AND email != ''"
    );
    $stmt->execute($user_ids);
    $recipients = $stmt->fetchAll(PDO::FETCH_OBJ);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı hatası.']);
    exit;
}

if (empty($recipients)) {
    echo json_encode(['success' => false, 'message' => 'Geçerli alıcı bulunamadı.']);
    exit;
}

$mail = new PHPMailer(true);
$errors = [];
$sent = 0;

try {
    $mail->isSMTP();
    $mail->Host       = 'mail.puantor.com.tr';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'sifre@puantor.com.tr';
    $mail->Password   = 'Us(@ixgfPDwt';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';
    $mail->isHTML(true);
    $mail->setFrom('sifre@puantor.com.tr', 'Puantor.com.tr');

    foreach ($recipients as $recipient) {
        try {
            $mail->clearAddresses();
            $mail->addAddress($recipient->email, $recipient->full_name);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
            $sent++;
        } catch (Exception $e) {
            $errors[] = $recipient->email;
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'SMTP bağlantı hatası: ' . $e->getMessage()]);
    exit;
}

if ($sent === 0) {
    echo json_encode(['success' => false, 'message' => 'Hiçbir mail gönderilemedi.']);
    exit;
}

$message = $sent . ' kişiye mail başarıyla gönderildi.';
if (!empty($errors)) {
    $message .= ' ' . count($errors) . ' adrese gönderilemedi: ' . implode(', ', $errors);
}

echo json_encode(['success' => true, 'message' => $message]);
