<?php

namespace Service;

class PushBildirimService
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;

        if (!defined('VAPID_PUBLIC_KEY')) {
            require_once dirname(__DIR__) . '/configs/push_config.php';
        }
    }

    public function yoneticilereGonder(int $firma_id, string $baslik, string $icerik, array $ekVeri = []): void
    {
        $subscriptions = $this->getSubscriptions('user', null, $firma_id);
        $this->gonder($subscriptions, $baslik, $icerik, $ekVeri);
    }

    public function personeleGonder(int $personel_id, int $firma_id, string $baslik, string $icerik, array $ekVeri = []): void
    {
        $subscriptions = $this->getSubscriptions('personel', $personel_id, $firma_id);
        $this->gonder($subscriptions, $baslik, $icerik, $ekVeri);
    }

    public function kullaniciyaGonder(int $kullanici_id, int $firma_id, string $baslik, string $icerik, array $ekVeri = []): void
    {
        $subscriptions = $this->getSubscriptions('user', $kullanici_id, $firma_id);
        $this->gonder($subscriptions, $baslik, $icerik, $ekVeri);
    }

    private function getSubscriptions(string $userType, ?int $userId, int $firmaId): array
    {
        if ($userId !== null) {
            $stmt = $this->db->prepare(
                "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_type = ? AND user_id = ? AND firma_id = ?"
            );
            $stmt->execute([$userType, $userId, $firmaId]);
        } else {
            $stmt = $this->db->prepare(
                "SELECT endpoint, p256dh, auth FROM push_subscriptions WHERE user_type = ? AND firma_id = ?"
            );
            $stmt->execute([$userType, $firmaId]);
        }
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    private function gonder(array $subscriptions, string $baslik, string $icerik, array $ekVeri = []): void
    {
        if (empty($subscriptions)) return;

        $payload = json_encode(['title' => $baslik, 'body' => $icerik, 'data' => $ekVeri], JSON_UNESCAPED_UNICODE);

        if (class_exists('\Minishlink\WebPush\WebPush')) {
            $this->gonderMinishlink($subscriptions, $payload);
        } else {
            $this->gonderNative($subscriptions, $payload);
        }
    }

    private function gonderMinishlink(array $subscriptions, string $payload): void
    {
        $webPush = new \Minishlink\WebPush\WebPush([
            'VAPID' => [
                'subject'    => VAPID_SUBJECT,
                'publicKey'  => VAPID_PUBLIC_KEY,
                'privateKey' => VAPID_PRIVATE_KEY,
            ],
        ]);
        $webPush->setDefaultOptions(['TTL' => 2419200]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                \Minishlink\WebPush\Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys'     => ['p256dh' => $sub->p256dh, 'auth' => $sub->auth],
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if (!$report->isSuccess()) {
                $this->temizleGecersizEndpoint($report->getEndpoint());
            }
        }
    }

    private function gonderNative(array $subscriptions, string $payload): void
    {
        $sender = new WebPushSender(VAPID_PUBLIC_KEY, VAPID_PRIVATE_KEY, VAPID_SUBJECT);

        foreach ($subscriptions as $sub) {
            try {
                if (!$sender->send($sub->endpoint, $sub->p256dh, $sub->auth, $payload)) {
                    $this->temizleGecersizEndpoint($sub->endpoint);
                }
            } catch (\Throwable $e) {
                $this->temizleGecersizEndpoint($sub->endpoint);
            }
        }
    }

    private function temizleGecersizEndpoint(string $endpoint): void
    {
        $stmt = $this->db->prepare("DELETE FROM push_subscriptions WHERE endpoint = ?");
        $stmt->execute([$endpoint]);
    }

    public function aboneKaydet(string $userType, int $userId, int $firmaId, string $endpoint, string $p256dh, string $auth): void
    {
        $stmt = $this->db->prepare("
            INSERT INTO push_subscriptions (user_type, user_id, firma_id, endpoint, p256dh, auth)
            VALUES (:user_type, :user_id, :firma_id, :endpoint, :p256dh, :auth)
            ON DUPLICATE KEY UPDATE p256dh = VALUES(p256dh), auth = VALUES(auth), updated_at = NOW()
        ");
        $stmt->execute([
            'user_type' => $userType,
            'user_id'   => $userId,
            'firma_id'  => $firmaId,
            'endpoint'  => $endpoint,
            'p256dh'    => $p256dh,
            'auth'      => $auth,
        ]);
    }
}
