<?php
require_once ROOT . '/Model/BaseModel.php';

class GonderilenBildirimlerModel extends Model
{
    protected $table = 'gonderilen_bildirimler';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function kaydet(
        int $firmaId,
        int $gonderenId,
        string $hedefTuru,
        string $hedef,
        ?array $hedefIds,
        string $baslik,
        string $icerik,
        string $url = ''
    ): int
    {
        $hedefIdsStr = $hedefIds ? implode(',', $hedefIds) : null;

        $stmt = $this->db->prepare(
            "INSERT INTO gonderilen_bildirimler
                (firma_id, gonderen_id, hedef_turu, hedef, personel_ids, kullanici_ids, baslik, icerik, url)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $firmaId,
            $gonderenId,
            $hedefTuru,
            $hedef,
            $hedefTuru === 'personel' ? $hedefIdsStr : null,
            $hedefTuru === 'kullanici' ? $hedefIdsStr : null,
            $baslik,
            $icerik,
            $url,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function getList(?int $firmaId, int $limit = 100): array
    {
        $where = $firmaId === null ? '' : 'WHERE gb.firma_id = :firma_id';
        $stmt = $this->db->prepare(
            "SELECT gb.*, u.full_name AS gonderen_adi, mf.firm_name AS firma_adi
             FROM gonderilen_bildirimler gb
             LEFT JOIN users u ON u.id = gb.gonderen_id
             LEFT JOIN myfirms mf ON mf.id = gb.firma_id
             {$where}
             ORDER BY gb.created_at DESC
             LIMIT :limit"
        );
        if ($firmaId !== null) {
            $stmt->bindValue(':firma_id', $firmaId, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
