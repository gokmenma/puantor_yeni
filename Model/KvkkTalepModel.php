<?php

require_once __DIR__ . '/BaseModel.php';

class KvkkTalepModel extends Model
{
    protected $table = 'kvkk_talepler';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getByFirm(int $firma_id): array
    {
        $sql = $this->db->prepare("
            SELECT t.*, u.full_name AS atanan_kullanici_ad
            FROM kvkk_talepler t
            LEFT JOIN users u ON u.id = t.atanan_kullanici
            WHERE t.firma_id = ?
            ORDER BY t.talep_tarihi DESC
        ");
        $sql->execute([$firma_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getById(int $id, int $firma_id): ?object
    {
        $sql = $this->db->prepare("SELECT * FROM kvkk_talepler WHERE id = ? AND firma_id = ? LIMIT 1");
        $sql->execute([$id, $firma_id]);
        return $sql->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function create(array $data): int
    {
        $sql = $this->db->prepare("
            INSERT INTO kvkk_talepler
                (firma_id, talep_turu, basvuran_ad, basvuran_email, basvuran_tc, aciklama, durum, atanan_kullanici, son_yanit_tarihi, olusturan_id)
            VALUES (?, ?, ?, ?, ?, ?, 'bekliyor', ?, DATE_ADD(NOW(), INTERVAL 30 DAY), ?)
        ");
        $sql->execute([
            $data['firma_id'],
            $data['talep_turu'],
            $data['basvuran_ad'],
            $data['basvuran_email'] ?? null,
            $data['basvuran_tc'] ?? null,
            $data['aciklama'] ?? null,
            $data['atanan_kullanici'] ?? null,
            $data['olusturan_id'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function updateDurum(int $id, int $firma_id, string $durum, ?string $yanit_notu): bool
    {
        $yanit_tarihi = ($durum === 'tamamlandi' || $durum === 'reddedildi') ? date('Y-m-d H:i:s') : null;
        $sql = $this->db->prepare("
            UPDATE kvkk_talepler
            SET durum = ?, yanit_notu = ?, yanit_tarihi = ?
            WHERE id = ? AND firma_id = ?
        ");
        $sql->execute([$durum, $yanit_notu, $yanit_tarihi, $id, $firma_id]);
        return $sql->rowCount() > 0;
    }

    public function getSuresiGecenler(int $firma_id): array
    {
        $sql = $this->db->prepare("
            SELECT * FROM kvkk_talepler
            WHERE firma_id = ?
              AND durum IN ('bekliyor','isleniyor')
              AND son_yanit_tarihi < NOW()
            ORDER BY son_yanit_tarihi ASC
        ");
        $sql->execute([$firma_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getOzet(int $firma_id): object
    {
        $sql = $this->db->prepare("
            SELECT
                COUNT(*) AS toplam,
                SUM(durum = 'bekliyor') AS bekliyor,
                SUM(durum = 'isleniyor') AS isleniyor,
                SUM(durum = 'tamamlandi') AS tamamlandi,
                SUM(durum = 'reddedildi') AS reddedildi,
                SUM(durum IN ('bekliyor','isleniyor') AND son_yanit_tarihi < NOW()) AS suresi_gecen
            FROM kvkk_talepler
            WHERE firma_id = ?
        ");
        $sql->execute([$firma_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }
}
