<?php
require_once 'BaseModel.php';

class KullaniciAbonelikleriModel extends Model
{
    protected $table = 'kullanici_abonelikleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Kullanıcının abonelik geçmişini getirir
     */
    public function getSubscriptionHistory($user_id)
    {
        $sql = $this->db->prepare("
            SELECT ka.*, ap.ad as paket_adi, ap.fiyat as paket_fiyati,
                   (SELECT o.tutar FROM odemeler o WHERE o.abonelik_id = ka.id ORDER BY o.id DESC LIMIT 1) as odeme_tutar
            FROM $this->table ka
            LEFT JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
            WHERE ka.kullanici_id = ?
            ORDER BY ka.id DESC
        ");
        $sql->execute([$user_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
