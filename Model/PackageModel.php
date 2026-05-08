<?php 
require_once "BaseModel.php";

class PackageModel extends Model{
    protected $table = "mbeyazil_panel.packages";

    public function __construct(){
        parent::__construct($this->table);
    }

    //Paket Bilgilerini Getir
    public function getPackage($id){
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE id = ?");
        $sql->execute(array($id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    //Kullanıcının Seçili Paketini Getir
    public function getSelectedPackage($user_id){
        $sql = $this->db->prepare("SELECT * FROM panel.user_packages WHERE user_id = ?");
        $sql->execute(array($user_id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    //paketleri getir
    public function getPackages(){
        $sql = $this->db->prepare("SELECT * FROM $this->table where program_name = ?");
        $sql->execute(["puantor"]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

}