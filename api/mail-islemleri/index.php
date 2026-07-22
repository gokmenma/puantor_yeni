<?php

header('Content-Type: application/json; charset=utf-8');
ob_start();
error_reporting(0);
ini_set('display_errors', '0');
set_time_limit(0);

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

require_once ROOT . '/Database/require.php';
require_once ROOT . '/Model/MailIslemleriModel.php';
require_once ROOT . '/Model/SettingsModel.php';
require_once ROOT . '/Model/ActivityLogModel.php';
require_once ROOT . '/Service/MailGonderimService.php';

use Service\MailGonderimService;

function mailJson(array $data, int $status = 200): void
{
    if (ob_get_length()) {
        ob_clean();
    }
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (!isset($_SESSION['user'])) {
    mailJson(['status' => 'error', 'message' => 'Oturum süreniz dolmuş.'], 401);
}

if ((int) ($_SESSION['user']->superadmin ?? 0) !== 1) {
    mailJson(['status' => 'error', 'message' => 'Bu işlem için yetkiniz yok.'], 403);
}

$model = new MailIslemleriModel();
$action = $_REQUEST['action'] ?? '';

try {
    if ($action === 'stats') {
        mailJson(['status' => 'success', 'stats' => $model->getStats()]);
    }

    if ($action === 'list') {
        $draw = max(0, (int) ($_GET['draw'] ?? 0));
        $start = max(0, (int) ($_GET['start'] ?? 0));
        $length = min(100, max(10, (int) ($_GET['length'] ?? 25)));
        $search = trim((string) ($_GET['search']['value'] ?? ''));
        $result = $model->getHistory($start, $length, $search);
        mailJson([
            'draw' => $draw,
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $result['rows'],
        ]);
    }

    if ($action === 'detail') {
        $sendId = (int) ($_GET['id'] ?? 0);
        $send = $sendId > 0 ? $model->getSend($sendId) : null;
        if (!$send) {
            mailJson(['status' => 'error', 'message' => 'Gönderim kaydı bulunamadı.'], 404);
        }
        mailJson(['status' => 'success', 'send' => $send, 'recipients' => $model->getRecipients($sendId)]);
    }

    if ($action !== 'send' || $_SERVER['REQUEST_METHOD'] !== 'POST') {
        mailJson(['status' => 'error', 'message' => 'Geçersiz istek.'], 400);
    }

    $sessionToken = (string) ($_SESSION['csrf_token'] ?? '');
    $requestToken = (string) ($_POST['csrf_token'] ?? '');
    if ($sessionToken === '' || $requestToken === '' || !hash_equals($sessionToken, $requestToken)) {
        mailJson(['status' => 'error', 'message' => 'Güvenlik doğrulaması başarısız. Sayfayı yenileyin.'], 419);
    }

    $recipientType = (string) ($_POST['alici_turu'] ?? 'secili');
    $account = (string) ($_POST['gonderen_hesabi'] ?? 'info');
    $subject = trim((string) ($_POST['konu'] ?? ''));
    $body = trim((string) ($_POST['icerik'] ?? ''));

    if (!in_array($recipientType, ['secili', 'tumu', 'harici'], true)) {
        mailJson(['status' => 'error', 'message' => 'Geçersiz alıcı türü.'], 422);
    }
    if (!in_array($account, ['info', 'support'], true)) {
        mailJson(['status' => 'error', 'message' => 'Geçersiz gönderen hesabı.'], 422);
    }
    if ($subject === '' || mb_strlen($subject) > 255 || trim(strip_tags($body)) === '') {
        mailJson(['status' => 'error', 'message' => 'Konu ve mesaj içeriğini eksiksiz girin.'], 422);
    }

    if ($recipientType === 'tumu') {
        $recipients = $model->getSystemRecipients([], true);
    } elseif ($recipientType === 'secili') {
        $recipients = $model->getSystemRecipients((array) ($_POST['kullanici_ids'] ?? []));
    } else {
        $rawEmails = preg_split('/[\s,;]+/', (string) ($_POST['harici_emailler'] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        $recipients = [];
        $seen = [];
        foreach ($rawEmails as $rawEmail) {
            $email = strtolower(trim($rawEmail));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($seen[$email])) {
                continue;
            }
            $seen[$email] = true;
            $recipients[] = ['kullanici_id' => null, 'alici_adi' => null, 'email' => $email];
        }
    }

    if (!$recipients) {
        mailJson(['status' => 'error', 'message' => 'Geçerli bir alıcı bulunamadı.'], 422);
    }
    if (count($recipients) > 500) {
        mailJson(['status' => 'error', 'message' => 'Tek seferde en fazla 500 alıcıya gönderim yapabilirsiniz.'], 422);
    }

    $service = new MailGonderimService(new SettingsModel());
    $senderEmail = $service->getAccountEmail($account);
    if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
        mailJson(['status' => 'error', 'message' => 'Seçilen gönderen hesabı yapılandırılmamış.'], 422);
    }

    try {
        $mailer = $service->createMailer($account);
    } catch (RuntimeException $e) {
        mailJson(['status' => 'error', 'message' => 'SMTP sunucu ayarları eksik. Sistem Ayarları > SMTP E-Posta ekranından ayarları kaydedin.'], 422);
    }

    $sendId = $model->createSend(
        (int) $_SESSION['user']->id,
        $account,
        $senderEmail,
        $recipientType,
        $subject,
        $body,
        $recipients
    );

    $successful = 0;
    $failed = 0;
    $mailer->SMTPKeepAlive = true;
    $mailer->Subject = $subject;
    $mailer->Body = $body;
    $mailer->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));

    foreach ($recipients as $recipient) {
        try {
            $mailer->clearAddresses();
            $mailer->addAddress($recipient['email'], (string) ($recipient['alici_adi'] ?? ''));
            $mailer->send();
            $model->markRecipient($sendId, $recipient['email'], true);
            $successful++;
        } catch (Throwable $e) {
            error_log('Mail operations send error: ' . $e->getMessage());
            $model->markRecipient($sendId, $recipient['email'], false);
            $failed++;
        }
    }
    $mailer->smtpClose();
    $model->completeSend($sendId, $successful, $failed);

    ActivityLogModel::log(
        'mail_islemleri',
        'send',
        "E-posta gönderimi tamamlandı. Kayıt: {$sendId}, Alıcı: " . count($recipients) . ", Başarılı: {$successful}, Başarısız: {$failed}."
    );

    mailJson([
        'status' => $successful > 0 ? 'success' : 'error',
        'message' => "{$successful} e-posta gönderildi, {$failed} e-posta gönderilemedi.",
        'send_id' => $sendId,
    ], $successful > 0 ? 200 : 502);
} catch (Throwable $e) {
    error_log('Mail operations API error: ' . $e->getMessage());
    mailJson(['status' => 'error', 'message' => 'İşlem sırasında bir hata oluştu.'], 500);
}
