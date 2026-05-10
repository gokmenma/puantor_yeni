<?php

require_once 'BaseModel.php';
use App\Helper\Security;

class Cari extends Model
{
    protected $table = 'cari';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getCariByFirm($firm_id)
    {
        $query = $this->db->prepare('SELECT * FROM cari WHERE firma = ? AND silinme_tarihi IS NULL ORDER BY CariAdi ASC');
        $query->execute([$firm_id]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getCariById($id)
    {
        $id = Security::safeDecrypt($id);
        $query = $this->db->prepare('SELECT * FROM cari WHERE id = ?');
        $query->execute([$id]);
        return $query->fetch(PDO::FETCH_OBJ);
    }

    public function getBalance($cari_id)
    {
        $query = $this->db->prepare('SELECT SUM(borc) as toplam_borc, SUM(alacak) as toplam_alacak FROM cari_hareketleri WHERE cari_id = ? AND silinme_tarihi IS NULL');
        $query->execute([$cari_id]);
        $res = $query->fetch(PDO::FETCH_OBJ);
        return ($res->toplam_borc ?? 0) - ($res->toplam_alacak ?? 0);
    }

    public function getFirmTotals($firm_id)
    {
        $query = $this->db->prepare('
            SELECT 
                SUM(h.borc) as total_borc, 
                SUM(h.alacak) as total_alacak 
            FROM cari_hareketleri h
            JOIN cari c ON h.cari_id = c.id
            WHERE c.firma = ? AND h.silinme_tarihi IS NULL AND c.silinme_tarihi IS NULL
        ');
        $query->execute([$firm_id]);
        return $query->fetch(PDO::FETCH_OBJ);
    }
}
