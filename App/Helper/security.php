<?php

namespace App\Helper;

class Security
{
    public static function escape($data)
    {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }

    // CSRF Token
    public static function csrf()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $token = bin2hex(random_bytes(48));
            $_SESSION['csrf_token'] = $token;
        } else {
            $token = $_SESSION['csrf_token'];
        }
        return $token;
    }

    public static function checkCsrfToken()
    {
        //kullaNıcının session_token alanı ile Session'daki csrf_token alanını karşılaştırır
        $token = $_SESSION['user']->session_token ?? null;
        return hash_equals($_SESSION['csrf_token'], $token);

   
    }

    public static function generatePassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function passwordControl($password, $hash)
    {
        return password_verify($password, $hash);
    }

private static function encryptionKey(): string
{
    $key = $_ENV['ENCRYPTION_KEY'] ?? '';
    if ($key === '') {
        throw new \RuntimeException('ENCRYPTION_KEY ortam değişkeni tanımlı değil.');
    }
    return hash('sha256', $key, true);
}

public static function encrypt($data)
{
    if (empty($data)) {
        return '';
    }

    $method = "AES-256-GCM";
    $key = self::encryptionKey();
    $iv = openssl_random_pseudo_bytes(12);
    $tag = null;
    $encrypted_data = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);

    $result = base64_encode($iv . $tag . $encrypted_data);
    return strtr($result, '+/=', '-_*');
}

public static function decrypt($data)
{
    if (empty($data)) return '';
    $data = urldecode($data);
    $method = "AES-256-GCM";
    $key = self::encryptionKey();
    
    // Reverse URL-safe Base64
    $data = strtr($data, '-_*', '+/=');
    $data = base64_decode($data);
    
    if (strlen($data) < 28) {
        return false;
    }
    
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $encrypted_data = substr($data, 28);
    return openssl_decrypt($encrypted_data, $method, $key, OPENSSL_RAW_DATA, $iv, $tag);
}

    public static function safeDecrypt($data)
    {
        if (empty($data)) return '';
        $decrypted = self::decrypt($data);
        return $decrypted === false ? $data : $decrypted;
    }
}
