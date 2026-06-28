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

    //Kullanıcının açık destek taleplerinin sayısını getirir
    public function getOpenSupportsCount(){
        $user_id = $_SESSION['user']->id ?? 0;
        if (!$user_id) return 0;
        $sql = $this->db->prepare("SELECT COUNT(*) FROM $this->table WHERE user_id = :user_id and program_name = :program_name and status = 0");
        $sql->execute([
            'user_id' => $user_id,
            'program_name' => 'puantor'
        ]);
        return (int)$sql->fetchColumn();
    }

    // Süperadmin için tüm destek taleplerini getirir (kullanıcı bilgileriyle)
    public function getAllSupportsForAdmin(){
        $sql = $this->db->prepare("
            SELECT s.*, u.full_name as user_name, u.email as user_email 
            FROM $this->table s 
            LEFT JOIN users u ON s.user_id = u.id 
            WHERE s.program_name = :program_name 
            ORDER BY s.status ASC, s.id DESC
        ");
        $sql->execute([
            'program_name' => 'puantor'
        ]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
