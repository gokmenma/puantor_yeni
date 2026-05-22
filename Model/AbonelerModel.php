<?php
require_once 'BaseModel.php';

class AbonelerModel extends Model
{
    protected $table = 'users';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Retrieve all main subscribers (parent_id = 0) with their latest subscription information.
     */
    public function getSubscribers()
    {
        $sql = $this->db->prepare("
            SELECT u.*, 
                   ka.baslangic_tarihi, 
                   ka.bitis_tarihi, 
                   ka.durum AS abonelik_durumu, 
                   ap.ad AS paket_adi
            FROM $this->table u
            LEFT JOIN (
                SELECT ka1.*
                FROM kullanici_abonelikleri ka1
                INNER JOIN (
                    SELECT kullanici_id, MAX(id) as max_id
                    FROM kullanici_abonelikleri
                    GROUP BY kullanici_id
                ) ka2 ON ka1.id = ka2.max_id
            ) ka ON ka.kullanici_id = u.id
            LEFT JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
            WHERE u.parent_id = 0
            ORDER BY u.created_at DESC
        ");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
