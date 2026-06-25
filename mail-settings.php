<?php

require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/SettingsModel.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$sysSettings = new SettingsModel();

$script = basename($_SERVER['SCRIPT_NAME']);

$smtp_host = $sysSettings->getSystemSetting("smtp_host") ?? 'mail.puantor.com.tr';
$smtp_port = $sysSettings->getSystemSetting("smtp_port") ?? 465;
$smtp_encryption = $sysSettings->getSystemSetting("smtp_encryption") ?? 'ssl';
$smtp_from_name = $sysSettings->getSystemSetting("smtp_from_name") ?? 'İşçi Maaş';

if (in_array($script, ['register.php', 'sign-in.php', 'register-activate.php', 'register-success.php'])) {
    $smtp_username = $sysSettings->getSystemSetting("smtp_info_username") ?? 'bilgi@puantor.com.tr';
    $smtp_password = $sysSettings->getSystemSetting("smtp_info_password") ?? 'Us(@ixgfPDwt';
} else {
    $smtp_username = $sysSettings->getSystemSetting("smtp_username") ?? 'sifre@puantor.com.tr';
    $smtp_password = $sysSettings->getSystemSetting("smtp_password") ?? 'Us(@ixgfPDwt';
}

// PHPMailer ile e-posta gönderme
$mail = new PHPMailer(true);

// Hata ayıklama modunu etkinleştirin
//  $mail->SMTPDebug = 2; // Hata ayıklama seviyesini 2 olarak ayarlayın (1 veya 3 de kullanılabilir)
//  $mail->Debugoutput = 'html'; // Hata ayıklama çıktısını HTML olarak ayarlayın

// Sunucu ayarları
$mail->isSMTP();
$mail->Host = $smtp_host;
$mail->SMTPAuth = !empty($smtp_password);
$mail->Username = $smtp_username;
$mail->Password = $smtp_password;

if ($smtp_encryption === 'ssl') {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
} elseif ($smtp_encryption === 'tls') {
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
} else {
    $mail->SMTPSecure = '';
    $mail->SMTPAuth = false;
}

$mail->Port = (int)$smtp_port;
$mail->setFrom($smtp_username, $smtp_from_name);
$mail->CharSet = 'UTF-8';
