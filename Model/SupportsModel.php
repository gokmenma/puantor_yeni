<?php 
require_once 'BaseModel.php';

class SupportsModel extends Model
{
    protected $table = 'mbeyazil_panel.supports';

    public function __construct(){
        parent::__construct($this->table);
    }

    //Kullanıcının destek taleplerini getirir
    public function getSupportsByUser(){
        $user_id = $_SESSION['user']->id;
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = :user_id and program_name = :program_name ORDER BY id DESC");
        $sql->execute([
            'user_id' => $user_id,
            'program_name' => 'puantor'
        ]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
