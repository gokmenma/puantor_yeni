<?php

function puantorIsHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

function puantorStartSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'lifetime' => 0, 'path' => '/', 'secure' => puantorIsHttps(),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_start();
}

function puantorExpireCookie(string $name): void
{
    setcookie($name, '', [
        'expires' => time() - 3600, 'path' => '/', 'secure' => puantorIsHttps(),
        'httponly' => true, 'samesite' => 'Lax',
    ]);
}

function puantorEnforceSessionTimeout(): void
{
    if (empty($_SESSION['user'])) return;
    $timeout = (int) ($_SESSION['user']->superadmin ?? 0) === 1 ? 900 : 3600;
    if (time() - (int) ($_SESSION['last_activity_at'] ?? time()) > $timeout) {
        $_SESSION = [];
        puantorExpireCookie(session_name());
        session_destroy();
        $scriptName = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
        $isJsonRequest = strpos($scriptName, '/api/') !== false
            || strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
        if ($isJsonRequest) {
            http_response_code(401);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['status' => 'error', 'message' => 'Oturum süreniz dolmuş.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        header('Location: sign-in.php?expired=1');
        exit;
    }
    $_SESSION['last_activity_at'] = time();
}
