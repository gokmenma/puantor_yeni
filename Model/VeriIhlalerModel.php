<?php

require_once __DIR__ . '/BaseModel.php';

class VeriIhlalerModel extends Model
{
    protected $table = 'veri_ihlalleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getByFirm(int $firma_id): array
    {
        $sql = $this->db->prepare("
            SELECT v.*, u.full_name AS olusturan_ad
            FROM veri_ihlalleri v
            LEFT JOIN users u ON u.id = v.olusturan_id
            WHERE v.firma_id = ?
            ORDER BY v.ihlal_tarihi DESC
        ");
        $sql->execute([$firma_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function create(array $data): int
    {
        $sql = $this->db->prepare("
            INSERT INTO veri_ihlalleri
                (firma_id, ihlal_tarihi, tespit_tarihi, ihlal_turu, etkilenen_veri,
                 etkilenen_kisi_sayisi, onlem_alinan, aciklama, olusturan_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $sql->execute([
            $data['firma_id'],
            $data['ihlal_tarihi'],
            $data['tespit_tarihi'],
            $data['ihlal_turu'],
            $data['etkilenen_veri'] ?? null,
            (int)($data['etkilenen_kisi_sayisi'] ?? 0),
            $data['onlem_alinan'] ?? null,
            $data['aciklama'] ?? null,
            $data['olusturan_id'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function bildiriGuncelle(int $id, int $firma_id, string $referans_no): bool
    {
        $sql = $this->db->prepare("
            UPDATE veri_ihlalleri
            SET bildiri_durum = 'kvkk_bildirildi', bildiri_tarihi = NOW(), kvkk_referans_no = ?
            WHERE id = ? AND firma_id = ?
        ");
        $sql->execute([$referans_no, $id, $firma_id]);
        return $sql->rowCount() > 0;
    }

    public function getBekleyenBildirimler(int $firma_id): array
    {
        $sql = $this->db->prepare("
            SELECT * FROM veri_ihlalleri
            WHERE firma_id = ? AND bildiri_durum = 'bekliyor'
              AND TIMESTAMPDIFF(HOUR, tespit_tarihi, NOW()) >= 60
            ORDER BY tespit_tarihi ASC
        ");
        $sql->execute([$firma_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
