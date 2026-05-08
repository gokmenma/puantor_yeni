<?php

require_once "BaseModel.php";

class Company extends Model
{
    protected $table = 'companies';
    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        require_once __DIR__ . '/ActivityLogModel.php';
        $action = (isset($data['id']) && $data['id'] > 0) ? 'güncellendi' : 'eklendi';
        $name = $data['company_name'] ?? 'Firma';
        ActivityLogModel::log('company', (isset($data['id']) && $data['id'] > 0) ? 'update' : 'add', "Firma {$action}: {$name}");
        return $id;
    }

    public function delete($id)
    {
        $company = $this->find($id);
        if ($company) {
            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('company', 'delete', "Firma silindi: " . ($company->company_name ?? 'Firma'));
        }
        return parent::delete($id);
    }

    public function allWithUserId()
    {
        $query = $this->db->prepare("SELECT * FROM companies WHERE user_id = ?");
        $query->execute([$_SESSION["user"]->id]);
        $result = $query->fetchAll(PDO::FETCH_OBJ);
        return $result;
    }
    
    public function getMyCompanies($user_id)
    {
        $query = $this->db->prepare("SELECT * FROM myfirms WHERE user_id  = ?");
        $query->execute([$user_id]);
        $result = $query->fetchAll(PDO::FETCH_OBJ);
        return $result;
    }

    public function findMyFirm($id)
    {
        $query = $this->db->prepare("SELECT * FROM myfirms WHERE id = ?");
        $query->execute([$id]);
        $result = $query->fetch(PDO::FETCH_OBJ);
        return $result;
    }
    public function findMyFirmLogoName($id)
    {
        $query = $this->db->prepare("SELECT brand_logo FROM myfirms WHERE id = ?");
        $query->execute([$id]);
        $result = $query->fetch(PDO::FETCH_OBJ);
        return $result;
    }

    public function saveMyFirms($data)
    {
        $table = 'myfirms';
        parent::__construct($table);
        return parent::saveWithAttr($data);
    }
    public function deleteMyFirm($id)
    {
        $table = 'myfirms';
        parent::__construct($table);
        $this->delete($id);
    }

    //Firmayı say
    public function countMyFirms($user_id)
    {
        $query = $this->db->prepare("SELECT COUNT(*) as count FROM myfirms WHERE user_id = ?");
        $query->execute([$user_id]);
        $result = $query->fetch(PDO::FETCH_OBJ)->count;
        return $result;
    }
}
