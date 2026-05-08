<?php

require_once ROOT . '/vendor/autoload.php';


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
// PHPMailer ile e-posta gönderme
$mail = new PHPMailer(true);

 // Hata ayıklama modunu etkinleştirin
//  $mail->SMTPDebug = 2; // Hata ayıklama seviyesini 2 olarak ayarlayın (1 veya 3 de kullanılabilir)
//  $mail->Debugoutput = 'html'; // Hata ayıklama çıktısını HTML olarak ayarlayın

// Sunucu ayarları
$mail->isSMTP();
$mail->Host = 'mail.puantor.com.tr'; // SMTP sunucusu
$mail->SMTPAuth = true;
$mail->Username = 'sifre@puantor.com.tr'; // SMTP kullanıcı adı
$mail->Password = 'Us(@ixgfPDwt'; // SMTP şifresi
$mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
$mail->Port = 465;
