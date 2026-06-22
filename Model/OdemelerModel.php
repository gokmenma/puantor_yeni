<?php
require_once 'BaseModel.php';

class OdemelerModel extends Model
{
    protected $table = 'odemeler';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Retrieve all payments with subscriber, subscription, and package information.
     */
    public function getPayments()
    {
        $sql = $this->db->prepare("
            SELECT o.*, 
                   u.full_name AS subscriber_name, 
                   u.email AS subscriber_email,
                   ap.ad AS paket_adi,
                   ka.paket_id,
                   ka.firma_hakki,
                   ka.alt_kullanici_hakki,
                   ka.baslangic_tarihi,
                   ka.bitis_tarihi
            FROM $this->table o
            LEFT JOIN users u ON u.id = o.kullanici_id
            LEFT JOIN kullanici_abonelikleri ka ON ka.id = o.abonelik_id
            LEFT JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
            ORDER BY o.odeme_tarihi DESC
        ");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Update payment status.
     */
    public function updateStatus($id, $status)
    {
        $id = App\Helper\Security::safeDecrypt($id);
        $sql = $this->db->prepare("UPDATE $this->table SET durum = ? WHERE id = ?");
        return $sql->execute([$status, $id]);
    }
}
