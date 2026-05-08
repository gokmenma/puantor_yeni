<?php 

require_once 'BaseModel.php';

class JobGroupsModel extends Model{

    protected $table = 'job_groups';
    protected $firm_id;

    public function __construct(){
        parent::__construct($this->table);
        $this->firm_id = $_SESSION['firm_id'];
    }

    //firma id'sine göre tüm iş gruplarını getirir
    public function all(){
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ?");
        $sql->execute([$this->firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

}