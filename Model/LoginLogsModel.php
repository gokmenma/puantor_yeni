
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

    /**
     * Firmaya ait kullanıcıların son giriş kayıtlarını döner.
     */
    public function getRecentByFirmId(int $firm_id, int $limit = 10): array
    {
        $sql = $this->db->prepare(
            "SELECT l.id, l.user_id, l.ip_address, l.user_agent, l.login_time, u.full_name
             FROM login_logs l
             JOIN users u ON l.user_id = u.id
             WHERE u.firm_id = :firm_id
             ORDER BY l.login_time DESC
             LIMIT :lim"
        );
        $sql->bindValue(':firm_id', $firm_id, PDO::PARAM_INT);
        $sql->bindValue(':lim', $limit, PDO::PARAM_INT);
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}