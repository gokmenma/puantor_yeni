<?php


require_once "BaseModel.php";

class Missions extends Model
{
    protected $table = "missions";
    protected $table_process = "mission_process";
    public function __construct()
    {
        parent::__construct($this->table);
    }

   
    public function getMissionsFirm($firm_id)
    {
        $sql =  $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ? order by id desc");
        $sql->execute([$firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
    
    public function getHeaderFromMissionsFirm($firm_id)
    {
        $sql =  $this->db->prepare("SELECT m.header_id, mh.header_order
                                            FROM $this->table m
                                            LEFT JOIN mission_headers mh ON mh.id = m.header_id
                                            WHERE m.firm_id = ? 
                                            GROUP BY m.header_id, mh.header_order
                                            ORDER BY mh.header_order;");
        $sql->execute([$firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getMissionsByHeader($header_id)
    {
        $sql =  $this->db->prepare("SELECT * FROM $this->table WHERE header_id = ? order by status asc");
        $sql->execute([$header_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //görev Tamamlandı veya tamamlanmadı olarak işaretlenir
    public function updateMissionStatus($id, $status)
    {
        $sql =  $this->db->prepare("UPDATE $this->table SET status = ? WHERE id = ?");
        return $sql->execute([$status, $id]);
    }

    //Görevin ait olduğu bağlığı değiştir
    public function updateMissionHeader($id, $header_id)
    {
        $sql =  $this->db->prepare("UPDATE $this->table SET header_id = ? WHERE id = ?");
        return $sql->execute([$header_id, $id]);
    }

    //Tamamlanmamış görev sayısını getir
    public function getUncompletedMissions($header_id)
    {
        $sql =  $this->db->prepare("SELECT COUNT(*) as count FROM $this->table WHERE header_id = ? AND status = 0");
        $sql->execute([$header_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

  
    
    

}