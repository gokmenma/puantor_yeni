<?php


require_once "BaseModel.php";

class MissionHeaders extends Model
{
    protected $table = "mission_headers";
    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getMissionHeadersFirm($firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ?  order by header_order asc");
        $sql->execute([$firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getMissionHeader($header_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE id = ?");
        $sql->execute([$header_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }
   


  

}