<?php
require_once 'BaseModel.php';

class IzinTur extends Model
{
    protected $table = 'izin_turler';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getAktifTurler()
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE aktif = 1 ORDER BY ad");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPersonelTurler()
    {
        $sql = $this->db->prepare("
            SELECT it.* 
            FROM {$this->table} it
            INNER JOIN puantajturu pt ON it.puantaj_turu_id = pt.id
            WHERE it.aktif = 1 AND pt.personel_gorsun = 1
            ORDER BY it.ad
        ");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getByKod($kod)
    {
        $sql = $this->db->prepare("SELECT * FROM {$this->table} WHERE kod = ?");
        $sql->execute([$kod]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function getCakismaKontrolIds()
    {
        $sql = $this->db->prepare("SELECT id FROM {$this->table} WHERE cakisma_kontrol = 1 AND aktif = 1");
        $sql->execute();
        return array_column($sql->fetchAll(PDO::FETCH_OBJ), 'id');
    }
}
