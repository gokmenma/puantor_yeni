<?php

namespace Service;

class WebPushSender
{
    public function __construct(
        private string $vapidPublicKey,
        private string $vapidPrivateKey,
        private string $subject
    ) {}

    public function send(string $endpoint, string $p256dh, string $auth, string $payload): bool
    {
        $audience  = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
        $jwt       = $this->createJWT($audience);
        $encrypted = $this->encrypt($p256dh, $auth, $payload);

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $encrypted,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/octet-stream',
                'Content-Encoding: aes128gcm',
                'Authorization: vapid t=' . $jwt . ',k=' . $this->vapidPublicKey,
                'TTL: 86400',
                'Content-Length: ' . strlen($encrypted),
            ],
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $code >= 200 && $code < 300;
    }

    private function createJWT(string $audience): string
    {
        $header  = $this->b64u('{"typ":"JWT","alg":"ES256"}');
        $payload = $this->b64u(json_encode([
            'aud' => $audience,
            'exp' => time() + 43200,
            'sub' => $this->subject,
        ]));
        $input = $header . '.' . $payload;

        $pkey = openssl_pkey_get_private($this->privKeyToPEM($this->vapidPrivateKey));
        openssl_sign($input, $derSig, $pkey, OPENSSL_ALGO_SHA256);

        return $input . '.' . $this->b64u($this->derSigToRaw($derSig));
    }

    private function encrypt(string $p256dh, string $auth, string $plaintext): string
    {
        $eph = openssl_pkey_new(['curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC]);
        $det = openssl_pkey_get_details($eph);

        $asPublic = chr(4)
            . str_pad($det['ec']['x'], 32, "\0", STR_PAD_LEFT)
            . str_pad($det['ec']['y'], 32, "\0", STR_PAD_LEFT);

        $uaPublic   = $this->b64decode($p256dh);
        $authSecret = $this->b64decode($auth);

        $clientKey  = openssl_pkey_get_public($this->pubKeyToPEM($uaPublic));
        $ecdhSecret = openssl_pkey_derive($clientKey, $eph);
        if ($ecdhSecret === false) throw new \RuntimeException('ECDH failed.');
        $ecdhSecret = str_pad($ecdhSecret, 32, "\0", STR_PAD_LEFT);

        $prkCombine = hash_hmac('sha256', $ecdhSecret, $authSecret, true);
        $ikm        = $this->hkdfExpand($prkCombine, "WebPush: info\x00" . $uaPublic . $asPublic, 32);

        $salt  = random_bytes(16);
        $prk   = hash_hmac('sha256', $ikm, $salt, true);
        $cek   = $this->hkdfExpand($prk, "Content-Encoding: aes128gcm\x00", 16);
        $nonce = $this->hkdfExpand($prk, "Content-Encoding: nonce\x00", 12);

        $tag = '';
        $ct  = openssl_encrypt($plaintext . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag, '', 16);

        return $salt . pack('N', 4096) . chr(65) . $asPublic . $ct . $tag;
    }

    private function hkdfExpand(string $prk, string $info, int $len): string
    {
        $t = ''; $okm = '';
        for ($i = 1; strlen($okm) < $len; $i++) {
            $t = hash_hmac('sha256', $t . $info . chr($i), $prk, true);
            $okm .= $t;
        }
        return substr($okm, 0, $len);
    }

    private function privKeyToPEM(string $b64u): string
    {
        $raw   = str_pad($this->b64decode($b64u), 32, "\0", STR_PAD_LEFT);
        $inner = "\x02\x01\x01" . "\x04\x20" . $raw . "\xa0\x0a\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07";
        $der   = "\x30" . chr(strlen($inner)) . $inner;
        return "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END EC PRIVATE KEY-----\n";
    }

    private function pubKeyToPEM(string $raw65): string
    {
        $spki = "\x30\x59\x30\x13\x06\x07\x2a\x86\x48\xce\x3d\x02\x01\x06\x08\x2a\x86\x48\xce\x3d\x03\x01\x07\x03\x42\x00" . $raw65;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($spki), 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private function derSigToRaw(string $der): string
    {
        $o = 2;
        $o++; $rLen = ord($der[$o++]);
        if ($rLen >= 0x80) { $ll = $rLen & 0x7f; $rLen = 0; for ($i=0;$i<$ll;$i++) $rLen=($rLen<<8)|ord($der[$o++]); }
        $r = substr($der, $o, $rLen); $o += $rLen;
        $o++; $sLen = ord($der[$o++]);
        if ($sLen >= 0x80) { $ll = $sLen & 0x7f; $sLen = 0; for ($i=0;$i<$ll;$i++) $sLen=($sLen<<8)|ord($der[$o++]); }
        $s = substr($der, $o, $sLen);

        return str_pad(ltrim($r, "\x00"), 32, "\x00", STR_PAD_LEFT)
             . str_pad(ltrim($s, "\x00"), 32, "\x00", STR_PAD_LEFT);
    }

    private function b64u(string $data): string { return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); }

    private function b64decode(string $b64u): string
    {
        $pad = (4 - strlen($b64u) % 4) % 4;
        return base64_decode(strtr($b64u, '-_', '+/') . str_repeat('=', $pad));
    }
}
