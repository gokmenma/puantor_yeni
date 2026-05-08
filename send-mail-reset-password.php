<?php
define("ROOT", $_SERVER["DOCUMENT_ROOT"]);
require_once ROOT .'/vendor/autoload.php';
require_once ROOT .'/Database/require.php';
require_once ROOT .'/Model/UserModel.php';


$Users = new UserModel();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];

    // E-posta adresi kontrolü
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo 'Geçersiz e-posta adresi';
        exit();
    }

    // E-posta adresi veritabanında var mı kontrolü
    $user = $Users->getUserByEmail($email);
    if (!$user) {
        echo 'Bu e-posta adresi ile kayıtlı bir hesap bulunamadı.';
        exit();
    }

    // Şifre sıfırlama bağlantısı oluşturma
    $token = bin2hex(random_bytes(50));
    $resetLink = "http://puantor.com.tr/reset-password.php?token=" . $token;


    // Veritabanına token kaydetme (örnek veritabanı kodu)
    // $conn = new mysqli('localhost', 'username', 'password', 'database');
    // $stmt = $conn->prepare("INSERT INTO password_resets (email, token) VALUES (?, ?)");
    // $stmt->bind_param("ss", $email, $token);
    // $stmt->execute();
    // $stmt->close();
    // $conn->close();
    ob_start();
    include 'forgot-password-email.php';
    $content = ob_get_clean();
    // PHPMailer ile e-posta gönderme
    $mail = new PHPMailer(true);

    try {
        // Sunucu ayarları
        $mail->isSMTP();
        $mail->Host = 'mail.puantor.com.tr'; // SMTP sunucusu
        $mail->SMTPAuth = true;
        $mail->Username = 'sifre@puantor.com.tr'; // SMTP kullanıcı adı
        $mail->Password = 'Us(@ixgfPDwt'; // SMTP şifresi
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Alıcılar
        $mail->setFrom('sifre@puantor.com.tr', 'Puantor.com.tr');
        $mail->addAddress($email);

        // İçerik
    
        $mail->isHTML(true);
        $mail->Subject = 'Şifre Sıfırlama';
        $mail->Body    = $content;
        $mail->AltBody = strip_tags($content); 
        //Karakter seti
        $mail->CharSet = 'UTF-8';

        $mail->send();
        //reset-password.php sayfasına geri dönüş
        header("Location: forgot-password.php?email-send=true");
       //echo 'Şifre sıfırlama bağlantısı e-posta adresinize gönderildi.';
    } catch (Exception $e) {
        echo "E-posta gönderilemedi. Hata: {$mail->ErrorInfo}";
    }
}
?>