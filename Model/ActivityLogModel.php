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
     * Otomatik mobil / masaüstü cihaz tespiti yapar
     */
    public static function detectPlatform(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        if (strpos($uri, '/personel-pwa/') !== false || strpos($uri, '/mobile/') !== false) {
            return 'Mobil';
        }

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'com.puantor.app') {
            return 'Mobil Uygulama';
        }

        if (!empty($userAgent) && preg_match('/(android|bb\d+|meego).+mobile|avail|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od|ad)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i', $userAgent)) {
            return 'Mobil';
        }

        return 'Masaüstü';
    }

    /**
     * Aktivite kaydeder
     */
    public static function log($activity_type, $action, $description, $platform = null)
    {
        try {
            $db = (new Model())->getDb();
            $firm_id = $_SESSION['firm_id'] ?? 0;
            $user_id = $_SESSION['user']->id ?? 0;

            if (empty($platform)) {
                $platform = self::detectPlatform();
            }

            $sql = $db->prepare("INSERT INTO activity_logs (firm_id, user_id, activity_type, action, description, platform) VALUES (?, ?, ?, ?, ?, ?)");
            $result = $sql->execute([$firm_id, $user_id, $activity_type, $action, $description, $platform]);
            
            if (!$result) {
                system_log_error("Activity log INSERT failed: " . implode(" ", $sql->errorInfo()), ['operation' => 'activity_log_insert']);
            }
            return $result;
        } catch (\Throwable $e) {
            system_log_exception($e, ['operation' => 'activity_log_insert']);
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
