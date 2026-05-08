<?php


require_once "BaseModel.php";

class Roles extends Model
{
    protected $table = "userroles";
    protected $firm_id;
    public function __construct()
    {
        parent::__construct($this->table);
        $this->firm_id =isset($_SESSION["firm_id"]) ? $_SESSION["firm_id"] : 0 ;
    }

    public function getRolesByFirm($firm_id){
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ?");
        $sql->execute([$firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //Gelen id hariç diğer rolleri, firmaya göre getir
    public function getRolesByFirmExceptId($id){
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ? AND id != ?");
        $sql->execute([$this->firm_id, $id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //Role grubunu say
    public function countRolesByFirm(){
        $sql = $this->db->prepare("SELECT COUNT(*) as total FROM $this->table WHERE firm_id = ?");
        $sql->execute([$this->firm_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }
}
