<?php

require_once __DIR__ . '/BaseModel.php';

class KvkkAydinlatmaModel extends Model
{
    protected $table = 'kvkk_aydinlatma';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function kaydet(int $person_id, int $firma_id, int $onaylayan_id, string $versiyon = 'v1.0'): bool
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $sql = $this->db->prepare("
            INSERT INTO kvkk_aydinlatma (person_id, firma_id, metin_versiyonu, onaylayan_kullanici, ip_adresi)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $sql->execute([$person_id, $firma_id, $versiyon, $onaylayan_id, $ip]);
    }

    public function onayVarMi(int $person_id, string $versiyon = 'v1.0'): bool
    {
        $sql = $this->db->prepare("
            SELECT id FROM kvkk_aydinlatma WHERE person_id = ? AND metin_versiyonu = ? LIMIT 1
        ");
        $sql->execute([$person_id, $versiyon]);
        return (bool) $sql->fetch();
    }

    public function getByFirm(int $firma_id): array
    {
        $sql = $this->db->prepare("
            SELECT a.*, p.full_name AS personel_ad, u.full_name AS onaylayan_ad
            FROM kvkk_aydinlatma a
            JOIN persons p ON p.id = a.person_id
            JOIN users u ON u.id = a.onaylayan_kullanici
            WHERE a.firma_id = ?
            ORDER BY a.onay_tarihi DESC
        ");
        $sql->execute([$firma_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
