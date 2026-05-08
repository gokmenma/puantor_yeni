<?php 

require_once "BaseModel.php";

class PasswordModel extends Model{
    protected $table = 'password_resets';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function setPasswordReset($email, $token)
    {
        $sql = $this->db->prepare("INSERT INTO $this->table (email, token) VALUES (?, ?)");
        return $sql->execute(array($email, $token));
    }

    public function getPasswordReset( $token)
    {
        $sql = $this->db->prepare("SELECT email FROM $this->table WHERE token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $sql->execute(array($token));
        return $sql->fetch(PDO::FETCH_OBJ);
    }
 
}