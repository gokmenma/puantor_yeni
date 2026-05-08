<?php


require_once "BaseModel.php";

class MissionProcessMapping extends Model
{
    protected $table = "mission_process_mapping";
    public function __construct()
    {
        parent::__construct($this->table);
    }
    //first mission process mapping

    //Gelen Sürec id'sinde olan görevleri getirir
    public function getMissionProcessMapByLastProcessId($mission_id)
    {
        $sql =$this->db->prepare("SELECT * FROM $this->table WHERE mission_id = ? ORDER BY process_id desc LIMIT 1");
        $sql->execute([$mission_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ) ?? [];
    }


}