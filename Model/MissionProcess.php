<?php


require_once "BaseModel.php";

class MissionProcess extends Model
{
    protected $table = "mission_process";
    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getMissionProcessFirm($firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ?  order by process_order asc");
        $sql->execute([$firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getMissionProcess($mission_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE mission_id = ?  order by process_order asc");
        $sql->execute([$mission_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //Mission Process Mapping tablosunda olan süreçleri var olan en büyük process_id'ye göre getirir
    public function getMissionProcessFromMapping($process_id)
    {
        $sql = $this->db->prepare("SELECT *
                        FROM mission_process_mapping
                        WHERE (mission_id, process_id) IN (
                            SELECT mission_id, MAX(process_id) AS max_process_id
                            FROM mission_process_mapping
                            GROUP BY mission_id
                        ) AND process_id = ?     ");
        $sql->execute([$process_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    
    public function getMissionProcessFirst($firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ? ORDER BY process_order asc LIMIT 1");
        $sql->execute([$firm_id]);
        return $sql->fetch(PDO::FETCH_OBJ) ?? [];
    }

}