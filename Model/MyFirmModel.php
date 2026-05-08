<?php
require_once "BaseModel.php";

class MyFirmModel extends Model
{
    protected $table = 'myfirms';
    protected $db;
    public function __construct()
    {
        parent::__construct($this->table);
    }


    //all myfirms
    public function allByUser($user_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = :user_id");
        $sql->execute(['user_id' => $user_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
    //User'ın ,parent_id'si 0 ise kendi firmasını, 0 değilse email adresine göre firmasını getirir
    public function getMyFirmByUserId()
    {
        $parent_id = $_SESSION["user"]->parent_id ;
        if($parent_id == 0){
            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = :user_id");
            $sql->execute(['user_id' => $_SESSION["user"]->id]);
            return $sql->fetchAll(PDO::FETCH_OBJ);
        }else{
            $sql = $this->db->prepare("SELECT u.id,
                                                mf.id,
                                                u.parent_id,
                                                u.user_type,
                                                u.user_roles,
                                                mf.firm_name,
                                                u.email,mf.phone,mf.description,
                                                mf.created_at
                                        FROM $this->table mf
                                        LEFT JOIN users u ON u.firm_id = mf.id
                                        WHERE u.email = :email");
            $sql->execute(['email' => $_SESSION["user"]->email]);
            return $sql->fetchAll(PDO::FETCH_OBJ);
        }

      
    }

    public function getAuthorizedMyFirmsByEmail($email)
    {
        $sql = $this->db->prepare("SELECT DISTINCT id,firm_name FROM (
                                        SELECT id, user_id, firm_name, '' AS email FROM myfirms 
                                        UNION ALL
                                    SELECT firm_id,u.id,firm_name,u.email FROM users u
                                    LEFT JOIN myfirms mf ON mf.id = u.firm_id
                                    ) AS authorize_firm
                                    WHERE email = :email;");
        $sql->execute(['email' => $email]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
  
}