<?php
require_once ROOT . '/Model/BaseModel.php';

class GonderilenBildirimlerModel extends Model
{
    protected $table = 'gonderilen_bildirimler';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function kaydet(int $firmaId, int $gonderenId, string $hedef, ?array $personelIds, string $baslik, string $icerik, string $url = ''): int
    {
        $personelIdsStr = $personelIds ? implode(',', $personelIds) : null;
        
        $stmt = $this->db->prepare(
            "INSERT INTO gonderilen_bildirimler (firma_id, gonderen_id, hedef, personel_ids, baslik, icerik, url) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$firmaId, $gonderenId, $hedef, $personelIdsStr, $baslik, $icerik, $url]);
        return (int) $this->db->lastInsertId();
    }

    public function getList(int $firmaId, int $limit = 100): array
    {
        $stmt = $this->db->prepare(
            "SELECT gb.*, u.full_name as gonderen_adi 
             FROM gonderilen_bildirimler gb
             LEFT JOIN users u ON u.id = gb.gonderen_id
             WHERE gb.firma_id = ? 
             ORDER BY gb.created_at DESC 
             LIMIT ?"
        );
        $stmt->bindValue(1, $firmaId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
