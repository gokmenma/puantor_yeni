<?php

namespace Service;

require_once ROOT . '/vendor/autoload.php';
require_once ROOT . '/Model/SettingsModel.php';

use PHPMailer\PHPMailer\PHPMailer;
use RuntimeException;
use SettingsModel;

class MailGonderimService
{
    private SettingsModel $settings;

    public function __construct(SettingsModel $settings)
    {
        $this->settings = $settings;
    }

    public function getAccountEmail(string $account): string
    {
        $key = $account === 'support' ? 'smtp_support_username' : 'smtp_info_username';
        return trim((string) $this->settings->getSystemSetting($key));
    }

    public function createMailer(string $account): PHPMailer
    {
        $host = trim((string) $this->settings->getSystemSetting('smtp_host'));
        $port = (int) $this->settings->getSystemSetting('smtp_port');
        $encryption = trim((string) $this->settings->getSystemSetting('smtp_encryption'));
        $fromName = trim((string) $this->settings->getSystemSetting('smtp_from_name'));
        $usernameKey = $account === 'support' ? 'smtp_support_username' : 'smtp_info_username';
        $passwordKey = $account === 'support' ? 'smtp_support_password' : 'smtp_info_password';
        $username = trim((string) $this->settings->getSystemSetting($usernameKey));
        $password = (string) $this->settings->getSystemSetting($passwordKey);

        if ($host === '' || $port < 1 || $username === '' || $password === '') {
            throw new RuntimeException('SMTP ayarları eksik.');
        }

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;
        $mail->SMTPAuth = true;
        $mail->Username = $username;
        $mail->Password = $password;
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->setFrom($username, $fromName !== '' ? $fromName : 'Puantor');

        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAuth = false;
        }

        return $mail;
    }
}
