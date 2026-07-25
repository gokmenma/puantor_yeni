<?php
require_once 'BaseModel.php';   
class UserModel extends Model
{
    protected $table = 'users';
    public function __construct()
    {
        parent::__construct($this->table);
    }



    public function allByFirms($firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = :firm_id AND deleted_at IS NULL");
        $sql->execute(['firm_id' => $firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
    public function getUserByEmailandPassword($email, $password)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND password = ? AND deleted_at IS NULL");
        $sql->execute(array($email, $password));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    // is there a user with this email and firm_id
    public function getUserByEmailandFirm($email, $firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND firm_id = ? AND deleted_at IS NULL");
        $sql->execute(array($email, $firm_id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }


    public function getUser($id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE id = ? AND deleted_at IS NULL");
        $sql->execute(array($id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function getUserByEmail($email)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND deleted_at IS NULL");
        $sql->execute(array($email));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function find($id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE id = ? AND deleted_at IS NULL");
        $sql->execute(array($id));
        return $sql->fetch(PDO::FETCH_OBJ) ?? null;
    }

    public function getUserByLoginField($identifier)
    {
        $identifier = trim($identifier);
        if (empty($identifier)) {
            return null;
        }

        // 1. Email check (if it contains '@')
        if (strpos($identifier, '@') !== false) {
            return $this->getUserByEmail($identifier);
        }

        // 2. Phone check (if it has digits)
        $cleanPhone = preg_replace('/[^0-9]/', '', $identifier);
        if (!empty($cleanPhone) && strlen($cleanPhone) >= 7) {
            $basePhone = ltrim($cleanPhone, '0');
            if (substr($basePhone, 0, 2) === '90' && strlen($basePhone) > 10) {
                $basePhone = substr($basePhone, 2);
            }

            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE 
                (email = :raw OR 
                username = :raw OR
                full_name = :raw OR
                phone = :raw OR
                REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', ''), '+', '') LIKE :phoneMatch)
                AND deleted_at IS NULL
            ");
            
            $phoneMatch = '%' . $basePhone;
            $sql->execute([
                'raw' => $identifier,
                'phoneMatch' => $phoneMatch
            ]);
            $user = $sql->fetch(PDO::FETCH_OBJ);
            if ($user) {
                return $user;
            }
        }

        // 3. Fallback to searching by email, username, full_name, or phone directly
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE (email = :raw OR username = :raw OR full_name = :raw OR phone = :raw) AND deleted_at IS NULL");
        $sql->execute(['raw' => $identifier]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    function getUsersByFirm($firm_id)
    {
        $user_id = $_SESSION["user"]->id;
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE (firm_id = ? or id = ?) AND deleted_at IS NULL");
        $sql->execute(array($firm_id, $user_id));
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //Kullanıcı girişinde bir token oluştur ve kullanıcıya kaydet
    public function setToken($id, $token)
    {
        $token = hash('sha256', $token);
        $sql = $this->db->prepare("UPDATE $this->table SET session_token = ? WHERE id = ?");
        $sql->execute(array($token, $id));
        return $token;
    }

    public function getToken($token)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE token = ? ");
        $sql->execute(array($token));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function getUserBySessionToken($token)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE session_token IN (?, ?)");
        $sql->execute([hash('sha256', $token), $token]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function setMobileToken($id, $token)
    {
        $token = hash('sha256', $token);
        $sql = $this->db->prepare("UPDATE $this->table SET mobile_session_token = ? WHERE id = ?");
        $sql->execute(array($token, $id));
        return $token;
    }

    public function getUserByMobileSessionToken($token)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE mobile_session_token IN (?, ?)");
        $sql->execute([hash('sha256', $token), $token]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function getUserByResetToken($token)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE reset_token = ? ");
        $sql->execute(array($token));
        return $sql->fetch(PDO::FETCH_OBJ);
    }




    public function roleName($id)
    {
        $ids = array_values(array_filter(array_map('intval', explode(',', $id))));
        if (empty($ids)) return "Bilinmiyor";
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = $this->db->prepare("SELECT roleName FROM userroles WHERE id IN ($placeholders)");
        $sql->execute($ids);
        $names = $sql->fetchAll(PDO::FETCH_COLUMN);
        return !empty($names) ? implode(', ', $names) : "Bilinmiyor";
    }

    //Email adresi ve Firma İd'si ile kullanıcıyı getirir
    public function getUserByEmailAndFirmId($email, $firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND firm_id = ? AND deleted_at IS NULL");
        $sql->execute([$email, $firm_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function isEmailExists($email)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND deleted_at IS NULL");
        $sql->execute([$email]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function isUsernameExists($username, $excludeId = 0)
    {
        $username = trim($username);
        if (empty($username)) {
            return false;
        }
        if ($excludeId > 0) {
            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE username = :username AND id != :excludeId AND deleted_at IS NULL");
            $sql->execute([
                'username' => $username,
                'excludeId' => $excludeId
            ]);
        } else {
            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE username = :username AND deleted_at IS NULL");
            $sql->execute(['username' => $username]);
        }
        return $sql->fetch(PDO::FETCH_OBJ) ? true : false;
    }

    //Giriş işlemleri kayıt altına alınıyor
    public function loginLog($user_id)
    {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $sql = $this->db->prepare("INSERT INTO login_logs (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
        $sql->execute([$user_id, $ip_address, $user_agent]);
        return $this->db->lastInsertId();
    }

    //Çıkış işlemi yapıldığında log kaydı yapılır
    public function logoutLog($id)
    {
        $sql = $this->db->prepare("UPDATE login_logs SET logout_time = NOW() WHERE id = ?");
        $sql->execute([$id]);
    }

    //Token sorgulama


    public function updateUserPassword($email, $password)
    {
        $sql = $this->db->prepare("UPDATE $this->table SET password = ? WHERE email = ?");
        $sql->execute([$password, $email]);
    }

    //Activate Token kaydetme
    public function setActivateToken($data)
    {
        $this->saveWithAttr($data);
    }

    //Activate Token sorgulama
    public function checkToken($email)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND deleted_at IS NULL");
        $sql->execute([$email]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    //Kullanıcıyı aktif etme
    public function ActivateUser($email)
    {
        $sql = $this->db->prepare("UPDATE $this->table SET status = 1 WHERE email = ?");
        $sql->execute([$email]);
        //eğer başarılı ise geriye değer döndür
        return $sql->rowCount();
    }

    //Kullanıcının seçtiği paketi getirme
    public function getSelectedPackage($user_id)
    {
        $sql = $this->db->prepare("SELECT p.package_id FROM users u
                                            LEFT JOIN mbeyazil_panel.users_packages p ON p.user_id= u.id WHERE user_id = ?");
        $sql->execute([$user_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    //Giriş kayıtlarını getir
    public function getLoginLogs($user_id)
    {
        $sql = $this->db->prepare("SELECT * FROM login_logs WHERE user_id = ? ORDER BY login_time DESC");
        $sql->execute([$user_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get the active subscription details and limits for the main user.
     */
    public function getActiveSubscriptionDetails($owner_id) {
        require_once ROOT . "/App/Helper/date.php";

        $db = $this->getDb();
        
        // 0. If owner is a superadmin, they have unlimited rights
        $stmtOwner = $db->prepare("SELECT superadmin FROM users WHERE id = ?");
        $stmtOwner->execute([$owner_id]);
        $owner = $stmtOwner->fetch(PDO::FETCH_OBJ);
        if ($owner && ($owner->superadmin ?? 0) == 1) {
            return [
                'has_sub' => true,
                'paket_adi' => 'Süper Yönetici Paketi',
                'alt_kullanici_hakki' => 9999,
                'firma_hakki' => 9999
            ];
        }

        // 1. Check for active subscription in kullanici_abonelikleri
        $stmt = $db->prepare("
            SELECT ka.*, ap.ad as paket_adi
            FROM kullanici_abonelikleri ka
            LEFT JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
            WHERE ka.kullanici_id = ? AND ka.durum = 'aktif' AND ka.bitis_tarihi >= ?
            ORDER BY ka.id DESC LIMIT 1
        ");
        $stmt->execute([$owner_id, date('Y-m-d')]);
        $sub = $stmt->fetch(PDO::FETCH_OBJ);
        
        if ($sub) {
            return [
                'has_sub' => true,
                'paket_adi' => $sub->paket_adi,
                'alt_kullanici_hakki' => (int)$sub->alt_kullanici_hakki,
                'firma_hakki' => (int)$sub->firma_hakki
            ];
        }
        
        // 2. If no active sub row, check if they are in the 15-day registration trial
        $stmtUser = $db->prepare("SELECT created_at, user_type FROM users WHERE id = ?");
        $stmtUser->execute([$owner_id]);
        $ownerData = $stmtUser->fetch(PDO::FETCH_OBJ);
        if ($ownerData && $ownerData->user_type == 1) {
            $days = \App\Helper\Date::getDateDiff($ownerData->created_at);
            if ($days < 15) {
                // Return default trial limits: 10 Alt Kullanıcı, 1 Firma
                return [
                    'has_sub' => true,
                    'paket_adi' => '15 Günlük Deneme Süresi',
                    'alt_kullanici_hakki' => 10,
                    'firma_hakki' => 1
                ];
            }
        }
        
        return [
            'has_sub' => false,
            'paket_adi' => 'Paket Yok',
            'alt_kullanici_hakki' => 0,
            'firma_hakki' => 0
        ];
    }

    /**
     * Count sub-users of a given main owner ID.
     */
    public function getSubUserCount($owner_id) {
        $sql = $this->db->prepare("SELECT COUNT(*) FROM $this->table WHERE parent_id = ?");
        $sql->execute([$owner_id]);
        return (int)$sql->fetchColumn();
    }

    /**
     * Get managers of a given firm ID who have access to advance requests.
     */
    public function getManagersByFirm($firm_id)
    {
        $sql = $this->db->prepare("
            SELECT u.id, u.full_name, u.email
            FROM $this->table u
            WHERE u.firm_id = :firm_id
              AND u.deleted_at IS NULL
              AND u.status = 1
              AND (
                u.parent_id = 0
                OR u.is_main_user = 1
                OR u.superadmin = 1
                OR EXISTS (
                    SELECT 1 FROM role_auths ra
                    WHERE FIND_IN_SET(ra.role_id, u.user_roles)
                      AND FIND_IN_SET((SELECT id FROM auths WHERE auth_name = 'avans_talepleri'), ra.auth_ids)
                )
              )
        ");
        $sql->execute(['firm_id' => $firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        require_once __DIR__ . '/ActivityLogModel.php';
        $action = (isset($data['id']) && $data['id'] > 0) ? 'güncellendi' : 'eklendi';
        $name = $data['full_name'] ?? ($data['email'] ?? 'Kullanıcı');
        ActivityLogModel::log('user', (isset($data['id']) && $data['id'] > 0) ? 'update' : 'add', "Kullanıcı {$action}: {$name}");
        return $id;
    }

    public function delete($id)
    {
        $decryptedId = \App\Helper\Security::safeDecrypt($id);
        $user = $this->find($decryptedId);
        if ($user) {
            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('user', 'delete', "Kullanıcı silindi: {$user->full_name}");
        }
        return parent::delete($id);
    }

    public function softDelete($id)
    {
        $decryptedId = \App\Helper\Security::safeDecrypt($id);
        $user = $this->find($decryptedId);
        if ($user) {
            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('user', 'delete', "Kullanıcı silindi (arşive taşındı): {$user->full_name}");
        }
        return parent::softDelete($id);
    }
}
