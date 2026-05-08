<?php

require_once "BaseModel.php";

class RoleAuthsModel extends Model
{
    protected $table = "role_auths";
    public function __construct()
    {
        parent::__construct($this->table);
    }

    //getAuthsByRoleId
    public function getAuthsByRoleId($role_id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE role_id = ?");
        $sql->execute([$role_id]);
        return $sql->fetch(PDO::FETCH_OBJ) ?? 0;
    }
}

