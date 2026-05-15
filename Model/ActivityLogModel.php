<?php

require_once __DIR__ . '/BaseModel.php';

class ActivityLogModel extends Model
{
    protected $table = 'activity_logs';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Aktivite kaydeder
     */
    public static function log($activity_type, $action, $description)
    {
        try {
            $db = (new Model())->getDb();
            $firm_id = $_SESSION['firm_id'] ?? 0;
            $user_id = $_SESSION['user']->id ?? 0;

            $sql = $db->prepare("INSERT INTO activity_logs (firm_id, user_id, activity_type, action, description) VALUES (?, ?, ?, ?, ?)");
            $result = $sql->execute([$firm_id, $user_id, $activity_type, $action, $description]);
            
            if (!$result) {
                error_log("Activity log INSERT failed: " . implode(" ", $sql->errorInfo()));
            }
            return $result;
        } catch (Exception $e) {
            error_log("Activity log EXCEPTION: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Son aktiviteleri getirir
     */
    public function getRecentActivities($limit = 10)
    {
        $firm_id = $_SESSION['firm_id'] ?? 0;
        $user_id = $_SESSION['user']->id ?? 0;

        // Yetki kontrolü: Kullanıcı tüm logları görebilir mi?
        require_once __DIR__ . '/Auths.php';
        $authsModel = new Auths();
        $can_see_all = $authsModel->hasPermission("kritik_islem_loglarini_gor");

        $where_user = "";
        $params = [$firm_id];

        if (!$can_see_all) {
            $where_user = " AND a.user_id = ? ";
            $params[] = $user_id;
        }

        $sql = $this->db->prepare("SELECT a.*, u.full_name as user_name 
                                   FROM activity_logs a 
                                   LEFT JOIN users u ON a.user_id = u.id 
                                   WHERE a.firm_id = ? $where_user
                                   ORDER BY a.created_at DESC 
                                   LIMIT ?");
        
        $i = 1;
        foreach ($params as $param) {
            $sql->bindValue($i++, $param, PDO::PARAM_INT);
        }
        $sql->bindValue($i, $limit, PDO::PARAM_INT);

        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
