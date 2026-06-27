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
