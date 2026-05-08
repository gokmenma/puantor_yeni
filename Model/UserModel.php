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
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = :firm_id");
        $sql->execute(['firm_id' => $firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
    public function getUserByEmailandPassword($email, $password)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND password = ?");
        $sql->execute(array($email, $password));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    // is there a user with this email and firm_id
    public function getUserByEmailandFirm($email, $firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND firm_id = ?");
        $sql->execute(array($email, $firm_id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }


    public function getUser($id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE id = ?");
        $sql->execute(array($id));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function getUserByEmail($email)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? ");
        $sql->execute(array($email));
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    function getUsersByFirm($firm_id)
    {
        $user_id = $_SESSION["user"]->id;
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE (firm_id = ? or id = ?)");
        $sql->execute(array($firm_id, $user_id));
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //Kullanıcı girişinde bir token oluştur ve kullanıcıya kaydet
    public function setToken($id, $token)
    {
        //$token = bin2hex(random_bytes(32));
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
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE session_token = ?");
        $sql->execute(array($token));
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
        $sql = $this->db->prepare("SELECT * FROM userroles WHERE id = ?");
        $sql->execute(array($id));
        $result = $sql->fetch(PDO::FETCH_OBJ);
        return $result->roleName ?? "Bilinmiyor";
    }

    //Email adresi ve Firma İd'si ile kullanıcıyı getirir
    public function getUserByEmailAndFirmId($email, $firm_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ? AND firm_id = ?");
        $sql->execute([$email, $firm_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function isEmailExists($email)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ?");
        $sql->execute([$email]);
        return $sql->fetch(PDO::FETCH_OBJ);
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
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE email = ?");
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
}
