<?php 
require_once "BaseModel.php";

class UsersPackageModel extends Model{
    protected $table = "mbeyazil_panel.users_packages";

    public function __construct(){
        parent::__construct($this->table);
    }

      //Kullanıcının Seçili Paketini Getir
    public function getSelectUserPackage($user_id){
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = ?");
        $sql->execute(array($user_id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

}