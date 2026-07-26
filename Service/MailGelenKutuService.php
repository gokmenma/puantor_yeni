<?php

namespace Service;

require_once ROOT . '/Model/SettingsModel.php';

use DOMDocument;
use DOMElement;
use RuntimeException;
use SettingsModel;

class MailGelenKutuService
{
    private SettingsModel $settings;
    private $mailbox = null;

    public function __construct(SettingsModel $settings)
    {
        $this->settings = $settings;
    }

    public function getAccountEmail(string $account): string
    {
        $key = $account === 'support' ? 'smtp_support_username' : 'smtp_info_username';
        $default = $account === 'support' ? 'destek@puantor.com.tr' : 'bilgi@puantor.com.tr';
        return trim((string) ($this->settings->getSystemSetting($key) ?? $default));
    }

    public function getInbox(string $account, int $page, int $perPage, string $search = ''): array
    {
        $mailbox = $this->connect($account);
        $allUids = imap_sort($mailbox, SORTARRIVAL, 1, SE_UID) ?: [];

        if ($search !== '') {
            $safeSearch = preg_replace('/[^\pL\pN\s@._-]/u', ' ', mb_substr($search, 0, 100)) ?? '';
            $matched = imap_search($mailbox, 'TEXT "' . $safeSearch . '"', SE_UID, 'UTF-8') ?: [];
            $matchedMap = array_fill_keys(array_map('intval', $matched), true);
            $allUids = array_values(array_filter($allUids, fn($uid) => isset($matchedMap[(int) $uid])));
        }

        $total = count($allUids);
        $page = max(1, $page);
        $perPage = min(100, max(10, $perPage));
        $pageCount = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pageCount);
        $uids = array_slice($allUids, ($page - 1) * $perPage, $perPage);

        if (!$uids) {
            return ['rows' => [], 'total' => $total, 'page' => $page, 'page_count' => $pageCount];
        }

        $overviews = imap_fetch_overview($mailbox, implode(',', $uids), FT_UID) ?: [];
        $overviewMap = [];
        foreach ($overviews as $overview) {
            $overviewMap[(int) ($overview->uid ?? 0)] = $overview;
        }

        $rows = [];
        foreach ($uids as $uid) {
            $overview = $overviewMap[(int) $uid] ?? null;
            if (!$overview) {
                continue;
            }
            $from = $this->parseAddress((string) ($overview->from ?? ''));
            $rows[] = [
                'uid' => (int) $uid,
                'subject' => $this->decodeHeader((string) ($overview->subject ?? '(Konu yok)')),
                'from_name' => $from['name'],
                'from_email' => $from['email'],
                'date' => $this->normalizeDate((string) ($overview->date ?? '')),
                'seen' => !empty($overview->seen),
                'answered' => !empty($overview->answered),
                'size' => (int) ($overview->size ?? 0),
            ];
        }

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'page_count' => $pageCount];
    }

    public function getMessage(string $account, int $uid): array
    {
        $mailbox = $this->connect($account);
        $messageNumber = imap_msgno($mailbox, $uid);
        if ($messageNumber < 1) {
            throw new RuntimeException('Mail bulunamadı.');
        }

        $header = imap_headerinfo($mailbox, $messageNumber);
        $structure = imap_fetchstructure($mailbox, $uid, FT_UID);
        if (!$header || !$structure) {
            throw new RuntimeException('Mail okunamadı.');
        }

        $content = ['html' => '', 'plain' => '', 'attachments' => []];
        if (empty($structure->parts)) {
            $raw = imap_body($mailbox, $uid, FT_UID | FT_PEEK);
            $decoded = $this->decodePart($raw, (int) ($structure->encoding ?? 0));
            $decoded = $this->convertCharset($decoded, $this->getPartParameter($structure, 'charset'));
            if (strtoupper((string) ($structure->subtype ?? '')) === 'HTML') {
                $content['html'] = $decoded;
            } else {
                $content['plain'] = $decoded;
            }
        } else {
            $this->collectParts($mailbox, $uid, $structure, '', $content);
        }

        $from = $this->headerAddress($header->from[0] ?? null);
        $to = [];
        foreach ((array) ($header->to ?? []) as $address) {
            $parsed = $this->headerAddress($address);
            if ($parsed['email'] !== '') {
                $to[] = $parsed;
            }
        }

        $body = $content['html'] !== ''
            ? $this->sanitizeHtml($content['html'])
            : '<div style="white-space:pre-wrap">' . htmlspecialchars($content['plain'], ENT_QUOTES, 'UTF-8') . '</div>';

        return [
            'uid' => $uid,
            'subject' => $this->decodeHeader((string) ($header->subject ?? '(Konu yok)')),
            'from_name' => $from['name'],
            'from_email' => $from['email'],
            'to' => $to,
            'date' => $this->normalizeDate((string) ($header->date ?? '')),
            'body' => $body,
            'attachments' => $content['attachments'],
        ];
    }

    public function setSeen(string $account, int $uid, bool $seen): void
    {
        $mailbox = $this->connect($account);
        $result = $seen
            ? imap_setflag_full($mailbox, (string) $uid, '\\Seen', ST_UID)
            : imap_clearflag_full($mailbox, (string) $uid, '\\Seen', ST_UID);
        if (!$result) {
            throw new RuntimeException('Mail durumu güncellenemedi.');
        }
    }

    public function deleteMessage(string $account, int $uid): void
    {
        $mailbox = $this->connect($account);
        if (imap_msgno($mailbox, $uid) < 1) {
            throw new RuntimeException('Silinecek mail bulunamadı.');
        }
        if (!imap_delete($mailbox, (string) $uid, FT_UID) || !imap_expunge($mailbox)) {
            throw new RuntimeException('Mail silinemedi.');
        }
    }

    public function getAttachment(string $account, int $uid, string $partNumber): array
    {
        if (!preg_match('/^\d+(\.\d+)*$/', $partNumber)) {
            throw new RuntimeException('Geçersiz ek bilgisi.');
        }

        $mailbox = $this->connect($account);
        $structure = imap_fetchstructure($mailbox, $uid, FT_UID);
        if (!$structure) {
            throw new RuntimeException('Mail bulunamadı.');
        }

        $part = $this->findPart($structure, $partNumber);
        if (!$part) {
            throw new RuntimeException('Ek bulunamadı.');
        }

        $filename = $this->getPartParameter($part, 'filename') ?: $this->getPartParameter($part, 'name');
        if ($filename === '') {
            throw new RuntimeException('Ek bulunamadı.');
        }
        if ((int) ($part->bytes ?? 0) > 26214400) {
            throw new RuntimeException('25 MB üzerindeki ekler indirilemez.');
        }

        $raw = imap_fetchbody($mailbox, $uid, $partNumber, FT_UID | FT_PEEK);
        $data = $this->decodePart($raw, (int) ($part->encoding ?? 0));
        $mime = $this->getMimeType($part);

        return [
            'filename' => $this->safeFilename($this->decodeHeader($filename)),
            'mime' => $mime,
            'data' => $data,
        ];
    }

    public function close(): void
    {
        if ($this->mailbox) {
            imap_close($this->mailbox);
            $this->mailbox = null;
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    private function connect(string $account)
    {
        if ($this->mailbox) {
            return $this->mailbox;
        }
        if (!in_array($account, ['info', 'support'], true)) {
            throw new RuntimeException('Geçersiz mail hesabı.');
        }

        $smtpHost = trim((string) ($this->settings->getSystemSetting('smtp_host') ?? 'mail.puantor.com.tr'));
        $host = trim((string) ($this->settings->getSystemSetting('imap_host') ?? $smtpHost));
        $port = (int) ($this->settings->getSystemSetting('imap_port') ?? 993);
        $encryption = strtolower(trim((string) ($this->settings->getSystemSetting('imap_encryption') ?? 'ssl')));
        $usernameKey = $account === 'support' ? 'smtp_support_username' : 'smtp_info_username';
        $passwordKey = $account === 'support' ? 'smtp_support_password' : 'smtp_info_password';
        $defaultUsername = $account === 'support' ? 'destek@puantor.com.tr' : 'bilgi@puantor.com.tr';
        $username = trim((string) ($this->settings->getSystemSetting($usernameKey) ?? $defaultUsername));
        $password = (string) $this->settings->getSystemSetting($passwordKey);

        $missing = [];
        if ($host === '') {
            $missing[] = 'IMAP sunucu adresi';
        }
        if ($port < 1) {
            $missing[] = 'IMAP portu';
        }
        if ($username === '') {
            $missing[] = $account === 'support' ? 'Destek hesabı adresi' : 'Bilgilendirme hesabı adresi';
        }
        if ($password === '') {
            $missing[] = $account === 'support' ? 'Destek hesabı parolası' : 'Bilgilendirme hesabı parolası';
        }
        if ($missing) {
            throw new RuntimeException('Eksik IMAP bilgileri: ' . implode(', ', $missing) . '.');
        }

        $flags = '/imap';
        if ($encryption === 'ssl') {
            $flags .= '/ssl';
        } elseif ($encryption === 'tls') {
            $flags .= '/tls';
        } else {
            $flags .= '/notls';
        }

        $mailboxName = '{' . $host . ':' . $port . $flags . '}INBOX';
        imap_errors();
        $this->mailbox = @imap_open($mailboxName, $username, $password, 0, 1);
        if (!$this->mailbox) {
            $errors = imap_errors() ?: [];
            system_log_error('IMAP connection error: ' . implode(' | ', $errors), ['operation' => 'imap_connect']);
            throw new RuntimeException('Gelen kutusuna bağlanılamadı.');
        }

        return $this->mailbox;
    }

    private function collectParts($mailbox, int $uid, object $structure, string $prefix, array &$content): void
    {
        foreach ((array) ($structure->parts ?? []) as $index => $part) {
            $partNumber = $prefix === '' ? (string) ($index + 1) : $prefix . '.' . ($index + 1);
            $filename = $this->getPartParameter($part, 'filename') ?: $this->getPartParameter($part, 'name');
            $disposition = strtoupper((string) ($part->disposition ?? ''));
            $isAttachment = $filename !== '' || $disposition === 'ATTACHMENT';

            if ($isAttachment) {
                $content['attachments'][] = [
                    'part' => $partNumber,
                    'filename' => $this->safeFilename($this->decodeHeader($filename !== '' ? $filename : 'ek')),
                    'mime' => $this->getMimeType($part),
                    'size' => (int) ($part->bytes ?? 0),
                ];
                continue;
            }

            if ((int) ($part->type ?? -1) === 0) {
                $raw = imap_fetchbody($mailbox, $uid, $partNumber, FT_UID | FT_PEEK);
                $decoded = $this->decodePart($raw, (int) ($part->encoding ?? 0));
                $decoded = $this->convertCharset($decoded, $this->getPartParameter($part, 'charset'));
                if (strtoupper((string) ($part->subtype ?? '')) === 'HTML' && $content['html'] === '') {
                    $content['html'] = $decoded;
                } elseif ($content['plain'] === '') {
                    $content['plain'] = $decoded;
                }
            }

            if (!empty($part->parts)) {
                $this->collectParts($mailbox, $uid, $part, $partNumber, $content);
            }
        }
    }

    private function findPart(object $structure, string $partNumber): ?object
    {
        $current = $structure;
        foreach (explode('.', $partNumber) as $segment) {
            $index = (int) $segment - 1;
            if ($index < 0 || !isset($current->parts[$index])) {
                return null;
            }
            $current = $current->parts[$index];
        }
        return $current;
    }

    private function getPartParameter(object $part, string $name): string
    {
        foreach (['parameters', 'dparameters'] as $property) {
            foreach ((array) ($part->{$property} ?? []) as $parameter) {
                if (strtolower((string) ($parameter->attribute ?? '')) === strtolower($name)) {
                    return (string) ($parameter->value ?? '');
                }
            }
        }
        return '';
    }

    private function decodePart(string $data, int $encoding): string
    {
        if ($encoding === 3) {
            return base64_decode($data, true) ?: '';
        }
        if ($encoding === 4) {
            return quoted_printable_decode($data);
        }
        return $data;
    }

    private function convertCharset(string $value, string $charset): string
    {
        $charset = trim($charset);
        if ($charset === '' || strtoupper($charset) === 'UTF-8') {
            return $value;
        }
        $converted = @mb_convert_encoding($value, 'UTF-8', $charset);
        return $converted !== false ? $converted : $value;
    }

    private function decodeHeader(string $value): string
    {
        $parts = imap_mime_header_decode($value);
        if (!is_array($parts)) {
            return trim($value);
        }
        $decoded = '';
        foreach ($parts as $part) {
            $text = (string) ($part->text ?? '');
            $charset = (string) ($part->charset ?? 'default');
            $decoded .= ($charset !== 'default' && strtoupper($charset) !== 'UTF-8')
                ? $this->convertCharset($text, $charset)
                : $text;
        }
        return trim($decoded);
    }

    private function parseAddress(string $value): array
    {
        $decoded = $this->decodeHeader($value);
        $addresses = imap_rfc822_parse_adrlist($decoded, '');
        if (!is_array($addresses)) {
            return ['name' => $decoded, 'email' => ''];
        }
        return $this->headerAddress($addresses[0] ?? null);
    }

    private function headerAddress($address): array
    {
        if (!$address || empty($address->mailbox) || empty($address->host)) {
            return ['name' => '', 'email' => ''];
        }
        $email = strtolower((string) $address->mailbox . '@' . (string) $address->host);
        $name = $this->decodeHeader((string) ($address->personal ?? ''));
        return ['name' => $name !== '' ? $name : $email, 'email' => $email];
    }

    private function normalizeDate(string $value): ?string
    {
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d H:i:s', $timestamp) : null;
    }

    private function getMimeType(object $part): string
    {
        $types = ['text', 'multipart', 'message', 'application', 'audio', 'image', 'video', 'other'];
        $type = $types[(int) ($part->type ?? 7)] ?? 'application';
        $subtype = preg_replace('/[^a-z0-9.+-]/', '', strtolower((string) ($part->subtype ?? 'octet-stream'))) ?: 'octet-stream';
        return $type . '/' . $subtype;
    }

    private function safeFilename(string $filename): string
    {
        $filename = str_replace(["\0", '/', '\\'], '_', trim($filename));
        return $filename !== '' ? mb_substr($filename, 0, 180) : 'ek';
    }

    private function sanitizeHtml(string $html): string
    {
        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><body>' . $html . '</body>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $forbidden = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'meta', 'link', 'base', 'svg', 'math', 'video', 'audio', 'img'];
        foreach ($forbidden as $tag) {
            $nodes = [];
            foreach ($document->getElementsByTagName($tag) as $node) {
                $nodes[] = $node;
            }
            foreach ($nodes as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        foreach ($document->getElementsByTagName('*') as $element) {
            if (!$element instanceof DOMElement) {
                continue;
            }
            $remove = [];
            foreach ($element->attributes as $attribute) {
                $name = strtolower($attribute->name);
                if (str_starts_with($name, 'on') || in_array($name, ['style', 'srcdoc', 'formaction'], true)) {
                    $remove[] = $attribute->name;
                }
            }
            foreach ($remove as $attributeName) {
                $element->removeAttribute($attributeName);
            }
            if ($element->hasAttribute('href')) {
                $href = trim($element->getAttribute('href'));
                if (!preg_match('#^(https?://|mailto:)#i', $href)) {
                    $element->removeAttribute('href');
                } else {
                    $element->setAttribute('target', '_blank');
                    $element->setAttribute('rel', 'noopener noreferrer');
                }
            }
        }

        $body = $document->getElementsByTagName('body')->item(0);
        if (!$body) {
            return '';
        }
        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $document->saveHTML($child);
        }
        return $result;
    }
}
