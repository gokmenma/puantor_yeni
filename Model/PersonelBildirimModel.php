<?php

class PersonelBildirimModel
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function kaydet(int $personelId, int $firmaId, string $baslik, string $icerik, string $url = ''): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO personel_bildirimler (personel_id, firma_id, baslik, icerik, url) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$personelId, $firmaId, $baslik, $icerik, $url]);
        return (int) $this->db->lastInsertId();
    }

    public function getByPersonel(int $personelId, int $firmaId, int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM personel_bildirimler WHERE personel_id = ? AND firma_id = ? ORDER BY created_at DESC LIMIT ?"
        );
        $stmt->execute([$personelId, $firmaId, $limit]);
        return $stmt->fetchAll(\PDO::FETCH_OBJ);
    }

    public function okunmamisSayisi(int $personelId, int $firmaId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM personel_bildirimler WHERE personel_id = ? AND firma_id = ? AND okundu = 0"
        );
        $stmt->execute([$personelId, $firmaId]);
        return (int) $stmt->fetchColumn();
    }

    public function okunduIsaretle(int $id, int $personelId): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE personel_bildirimler SET okundu = 1 WHERE id = ? AND personel_id = ?"
        );
        $stmt->execute([$id, $personelId]);
        return $stmt->rowCount() > 0;
    }

    public function tumunuOkunduIsaretle(int $personelId, int $firmaId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE personel_bildirimler SET okundu = 1 WHERE personel_id = ? AND firma_id = ?"
        );
        $stmt->execute([$personelId, $firmaId]);
    }

    public function sil(int $id, int $personelId): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM personel_bildirimler WHERE id = ? AND personel_id = ?"
        );
        $stmt->execute([$id, $personelId]);
        return $stmt->rowCount() > 0;
    }
}
