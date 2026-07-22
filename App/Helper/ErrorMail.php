<?php

namespace App\Helper;

!defined('ROOT') ? define('ROOT', dirname(__DIR__, 2)) : null;

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/SettingsModel.php';
require_once ROOT . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use SettingsModel;
use PDO;

class ErrorMail
{
    /**
     * Süper yöneticilere (superadmin = 1) sistem hatası bildirimi e-postası gönderir.
     * 
     * @param string $module Hatanın meydana geldiği modül adı (Örn: Puantaj Kaydı, Bordro Hesaplama, Personel İşlemleri)
     * @param string $errorMessage Hata açıklaması veya mesajı
     * @param \Throwable|null $throwable Varsa istisna nesnesi
     * @param array $extraData Ekran veya bağlam bilgileri
     * @return bool Gönderim başarılı ise true, aksi halde false
     */
    public static function notifySuperadmins($module, $errorMessage, $throwable = null, $extraData = [])
    {
        try {
            global $dbInstance;
            $db = null;
            if (isset($dbInstance) && is_object($dbInstance)) {
                $db = $dbInstance->connect();
            } else {
                $db = new PDO("mysql:host=localhost;dbname=mbeyazil_puantoryeni;charset=utf8mb4", "root", "");
            }

            // Superadmin kullanıcıları bul
            $stmt = $db->query("SELECT email, full_name FROM users WHERE superadmin = 1 AND (status = 1 OR status IS NULL) AND deleted_at IS NULL");
            $superadmins = $stmt ? $stmt->fetchAll(PDO::FETCH_OBJ) : [];

            if (empty($superadmins)) {
                return false;
            }

            // Mail Ayarlarını Yükle
            $sysSettings = new SettingsModel();
            $smtp_host = $sysSettings->getSystemSetting("smtp_host") ?? 'mail.puantor.com.tr';
            $smtp_port = $sysSettings->getSystemSetting("smtp_port") ?? 465;
            $smtp_encryption = $sysSettings->getSystemSetting("smtp_encryption") ?? 'ssl';
            $smtp_from_name = $sysSettings->getSystemSetting("smtp_from_name") ?? 'Puantor Sistem Hata Bildirimi';
            $smtp_username = $sysSettings->getSystemSetting("smtp_username") ?? 'sifre@puantor.com.tr';
            $smtp_password = $sysSettings->getSystemSetting("smtp_password") ?? 'Us(@ixgfPDwt';

            // Kullanıcı ve İstek Bilgileri
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $user_name = $_SESSION['user']->full_name ?? 'Sistem / Anonim';
            $user_email = $_SESSION['user']->email ?? 'Bilinmiyor';
            $firm_id = $_SESSION['firm_id'] ?? 'Bilinmiyor';
            $request_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
            $current_time = date('d.m.Y H:i:s');

            $trace_str = '';
            if ($throwable instanceof \Throwable) {
                $trace_str = "Hata Dosyası: " . $throwable->getFile() . " (Satır: " . $throwable->getLine() . ")\n\n" . $throwable->getTraceAsString();
            }

            // HTML E-Posta Gövdesi
            $html_content = "
            <div style='font-family: Arial, sans-serif; background-color: #f4f6f8; padding: 20px; color: #1e293b;'>
                <div style='max-width: 650px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;'>
                    <div style='background-color: #d63939; color: #ffffff; padding: 18px 24px;'>
                        <h2 style='margin: 0; font-size: 18px; font-weight: 700;'>⚠️ Sistem Hata Bildirimi</h2>
                    </div>
                    <div style='padding: 24px;'>
                        <p style='font-size: 14px; margin-top: 0;'>Puantor sisteminde kritik bir işlem sırasında hata meydana geldi. Detaylar aşağıdadır:</p>
                        
                        <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 13px;'>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 8px 0; font-weight: bold; width: 140px; color: #64748b;'>Modül / İşlem:</td>
                                <td style='padding: 8px 0; font-weight: bold; color: #d63939;'>" . htmlspecialchars($module, ENT_QUOTES, 'UTF-8') . "</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 8px 0; font-weight: bold; color: #64748b;'>Hata Mesajı:</td>
                                <td style='padding: 8px 0; color: #1e293b; font-weight: 600;'>" . htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') . "</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 8px 0; font-weight: bold; color: #64748b;'>Kullanıcı:</td>
                                <td style='padding: 8px 0; color: #334155;'>" . htmlspecialchars($user_name, ENT_QUOTES, 'UTF-8') . " (" . htmlspecialchars($user_email, ENT_QUOTES, 'UTF-8') . ")</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 8px 0; font-weight: bold; color: #64748b;'>Firma ID:</td>
                                <td style='padding: 8px 0; color: #334155;'>" . htmlspecialchars($firm_id, ENT_QUOTES, 'UTF-8') . "</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 8px 0; font-weight: bold; color: #64748b;'>Tarih / Saat:</td>
                                <td style='padding: 8px 0; color: #334155;'>" . htmlspecialchars($current_time, ENT_QUOTES, 'UTF-8') . "</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #f1f5f9;'>
                                <td style='padding: 8px 0; font-weight: bold; color: #64748b;'>İstek URL:</td>
                                <td style='padding: 8px 0; color: #0284c7; word-break: break-all;'>" . htmlspecialchars($request_url, ENT_QUOTES, 'UTF-8') . "</td>
                            </tr>
                        </table>";

            if (!empty($trace_str)) {
                $html_content .= "
                        <div style='margin-top: 15px;'>
                            <div style='font-size: 13px; font-weight: bold; color: #64748b; margin-bottom: 6px;'>Teknik İzleme (Stack Trace):</div>
                            <pre style='background-color: #0f172a; color: #f8fafc; padding: 12px; border-radius: 6px; font-size: 11px; overflow-x: auto; white-space: pre-wrap; font-family: monospace;'>" . htmlspecialchars($trace_str, ENT_QUOTES, 'UTF-8') . "</pre>
                        </div>";
            }

            $html_content .= "
                    </div>
                    <div style='background-color: #f8fafc; padding: 12px 24px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8;'>
                        Bu e-posta Puantor Otomatik Hata Bildirim Servisi tarafından süper yöneticilere gönderilmiştir.
                    </div>
                </div>
            </div>";

            // PHPMailer Gönderim
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
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
            $mail->setFrom($smtp_username, 'Puantor Hata Bildirimi');

            foreach ($superadmins as $admin) {
                if (!empty($admin->email) && filter_var($admin->email, FILTER_VALIDATE_EMAIL)) {
                    $mail->addAddress($admin->email, $admin->full_name ?? '');
                }
            }

            $mail->isHTML(true);
            $mail->Subject = "⚠️ Puantor Hata Bildirimi: " . $module;
            $mail->Body = $html_content;
            $mail->AltBody = "Puantor Sistem Hatası ($module): " . $errorMessage;

            $mail->send();
            return true;

        } catch (\Throwable $e) {
            error_log("ErrorMail notification failed: " . $e->getMessage());
            return false;
        }
    }
}
