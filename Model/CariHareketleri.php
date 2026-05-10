<?php

require_once 'BaseModel.php';
use App\Helper\Security;

class CariHareketleri extends Model
{
    protected $table = 'cari_hareketleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getMovementsByCari($cari_id)
    {
        $query = $this->db->prepare('SELECT * FROM cari_hareketleri WHERE cari_id = ? AND silinme_tarihi IS NULL ORDER BY islem_tarihi ASC, id ASC');
        $query->execute([$cari_id]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getMovementsByFirm($firm_id)
    {
        $query = $this->db->prepare('SELECT ch.*, c.CariAdi FROM cari_hareketleri ch 
                                    INNER JOIN cari c ON ch.cari_id = c.id 
                                    WHERE c.firma = ? AND ch.silinme_tarihi IS NULL 
                                    ORDER BY ch.islem_tarihi DESC, ch.id DESC');
        $query->execute([$firm_id]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }
}
