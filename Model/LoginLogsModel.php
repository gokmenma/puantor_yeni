
<?php 
require_once 'BaseModel.php';

class LoginLogsModel extends Model
{
    protected $table = 'login_logs';

    public function __construct(){
        parent::__construct($this->table);
    }

    //Tüm login loglarını getirir
    public function all(){
        $sql = $this->db->prepare("SELECT * FROM $this->table  ORDER BY login_time DESC");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function panelLoginLog($user)
    {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $user_id = $user->id;
        $user_name = $user->full_name;

        $sql = $this->db->prepare("INSERT INTO mbeyazil_panel.login_logs (db_name,user_id,user_name, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $sql->execute(["mbeyazil_puantoryeni",$user_id, $user_name, $ip_address, $user_agent]);
        return $this->db->lastInsertId();
    }
}