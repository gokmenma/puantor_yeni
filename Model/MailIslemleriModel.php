<?php

require_once ROOT . '/Model/BaseModel.php';

class MailIslemleriModel extends Model
{
    protected $table = 'mail_gonderimleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getSystemUsers(): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.full_name, u.email, u.status, mf.firm_name
             FROM users u
             LEFT JOIN myfirms mf ON mf.id = u.firm_id
             WHERE u.deleted_at IS NULL
               AND u.email IS NOT NULL
               AND TRIM(u.email) <> ''
             ORDER BY u.full_name ASC, u.email ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    public function getSystemRecipients(array $userIds = [], bool $all = false): array
    {
        $params = [];
        $where = '';

        if (!$all) {
            $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds), fn($id) => $id > 0)));
            if (!$userIds) {
                return [];
            }
            $where = ' AND id IN (' . implode(',', array_fill(0, count($userIds), '?')) . ')';
            $params = $userIds;
        }

        $stmt = $this->db->prepare(
            "SELECT id AS kullanici_id, full_name AS alici_adi, LOWER(TRIM(email)) AS email
             FROM users
             WHERE deleted_at IS NULL
               AND email IS NOT NULL
               AND TRIM(email) <> ''{$where}
             ORDER BY id ASC"
        );
        $stmt->execute($params);
        return $this->deduplicateRecipients($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function createSend(int $senderId, string $account, string $senderEmail, string $recipientType, string $subject, string $body, array $recipients): int
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO mail_gonderimleri
                 (gonderen_id, gonderen_hesabi, gonderen_email, alici_turu, konu, icerik, toplam_alici, durum)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'gonderiliyor')"
            );
            $stmt->execute([$senderId, $account, $senderEmail, $recipientType, $subject, $body, count($recipients)]);
            $sendId = (int) $this->db->lastInsertId();

            $recipientStmt = $this->db->prepare(
                "INSERT INTO mail_gonderim_alicilari (gonderim_id, kullanici_id, alici_adi, email)
                 VALUES (?, ?, ?, ?)"
            );
            foreach ($recipients as $recipient) {
                $recipientStmt->execute([
                    $sendId,
                    $recipient['kullanici_id'] ?? null,
                    $recipient['alici_adi'] ?? null,
                    $recipient['email'],
                ]);
            }

            $this->db->commit();
            return $sendId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function markRecipient(int $sendId, string $email, bool $success): void
    {
        $stmt = $this->db->prepare(
            "UPDATE mail_gonderim_alicilari
             SET durum = ?, hata_mesaji = ?, gonderilme_tarihi = NOW()
             WHERE gonderim_id = ? AND email = ?"
        );
        $stmt->execute([$success ? 'basarili' : 'basarisiz', $success ? null : 'E-posta sunucusu iletiyi kabul etmedi.', $sendId, $email]);
    }

    public function completeSend(int $sendId, int $successful, int $failed): void
    {
        $status = $successful > 0 && $failed === 0 ? 'tamamlandi' : ($successful > 0 ? 'kismi' : 'basarisiz');
        $stmt = $this->db->prepare(
            "UPDATE mail_gonderimleri
             SET basarili_sayisi = ?, basarisiz_sayisi = ?, durum = ?, tamamlanma_tarihi = NOW()
             WHERE id = ?"
        );
        $stmt->execute([$successful, $failed, $status, $sendId]);
    }

    public function getStats(): array
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS toplam_gonderim,
                    COALESCE(SUM(basarili_sayisi), 0) AS basarili_mail,
                    COALESCE(SUM(basarisiz_sayisi), 0) AS basarisiz_mail,
                    COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN toplam_alici ELSE 0 END), 0) AS bugun_alici
             FROM mail_gonderimleri"
        );
        $stmt->execute();
        return (array) $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getHistory(int $offset, int $limit, string $search = ''): array
    {
        $where = '';
        $params = [];
        if ($search !== '') {
            $where = ' WHERE (mg.konu LIKE ? OR mg.gonderen_email LIKE ? OR u.full_name LIKE ?)';
            $term = '%' . $search . '%';
            $params = [$term, $term, $term];
        }

        $totalStmt = $this->db->prepare("SELECT COUNT(*) FROM mail_gonderimleri");
        $totalStmt->execute();
        $total = (int) $totalStmt->fetchColumn();

        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM mail_gonderimleri mg LEFT JOIN users u ON u.id = mg.gonderen_id{$where}");
        $countStmt->execute($params);
        $filtered = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT mg.id, mg.gonderen_email, mg.alici_turu, mg.konu, mg.toplam_alici,
                    mg.basarili_sayisi, mg.basarisiz_sayisi, mg.durum, mg.created_at,
                    u.full_name AS gonderen_adi
             FROM mail_gonderimleri mg
             LEFT JOIN users u ON u.id = mg.gonderen_id
             {$where}
             ORDER BY mg.created_at DESC, mg.id DESC
             LIMIT ? OFFSET ?"
        );
        $index = 1;
        foreach ($params as $param) {
            $stmt->bindValue($index++, $param, PDO::PARAM_STR);
        }
        $stmt->bindValue($index++, $limit, PDO::PARAM_INT);
        $stmt->bindValue($index, $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'total' => $total, 'filtered' => $filtered];
    }

    public function getRecipients(int $sendId): array
    {
        $stmt = $this->db->prepare(
            "SELECT alici_adi, email, durum, hata_mesaji, gonderilme_tarihi
             FROM mail_gonderim_alicilari
             WHERE gonderim_id = ?
             ORDER BY id ASC"
        );
        $stmt->execute([$sendId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSend(int $sendId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, konu, gonderen_email, alici_turu, toplam_alici, basarili_sayisi, basarisiz_sayisi, durum, created_at
             FROM mail_gonderimleri WHERE id = ?"
        );
        $stmt->execute([$sendId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function deduplicateRecipients(array $recipients): array
    {
        $result = [];
        foreach ($recipients as $recipient) {
            $email = strtolower(trim((string) ($recipient['email'] ?? '')));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || isset($result[$email])) {
                continue;
            }
            $recipient['email'] = $email;
            $result[$email] = $recipient;
        }
        return array_values($result);
    }
}
