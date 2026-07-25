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
        $query = $this->db->prepare("SELECT * FROM myfirms WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
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
        $raw_id = (int) \App\Helper\Security::safeDecrypt($id);
        if ($raw_id <= 0) {
            throw new \Exception('Geçersiz firma ID');
        }

        $firm = $this->findMyFirm($raw_id);
        if (!$firm) {
            throw new \Exception('Firma bulunamadı.');
        }

        $this->db->beginTransaction();
        try {
            $now = date('Y-m-d H:i:s');

            // 1. Firm's own soft delete
            $stmt = $this->db->prepare("UPDATE myfirms SET deleted_at = ? WHERE id = ?");
            $stmt->execute([$now, $raw_id]);

            // 2. Persons of the firm
            $stmt = $this->db->prepare("UPDATE persons SET deleted_at = ? WHERE firm_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            // 3. Projects of the firm
            $stmt = $this->db->prepare("UPDATE projects SET deleted_at = ? WHERE firm_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            // 4. Cases (Kasalar) of the firm
            $stmt = $this->db->prepare("UPDATE cases SET deleted_at = ? WHERE firm_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            // 5. Puantaj (for persons or projects of the firm)
            $stmt = $this->db->prepare("UPDATE puantaj SET deleted_at = ? WHERE (person IN (SELECT id FROM persons WHERE firm_id = ?) OR project_id IN (SELECT id FROM projects WHERE firm_id = ?)) AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id, $raw_id]);

            // 6. Maas Gelir Kesinti
            $stmt = $this->db->prepare("UPDATE maas_gelir_kesinti SET deleted_at = ? WHERE (person_id IN (SELECT id FROM persons WHERE firm_id = ?) OR project_id IN (SELECT id FROM projects WHERE firm_id = ?)) AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id, $raw_id]);

            // 7. Personel Avans Talepleri
            $stmt = $this->db->prepare("UPDATE personel_avans_talepleri SET deleted_at = ? WHERE firm_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            // 8. Izin Talepleri
            $stmt = $this->db->prepare("UPDATE izin_talepler SET deleted_at = ? WHERE firma_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            // 9. Gorevler
            $stmt = $this->db->prepare("UPDATE gorevler SET deleted_at = ? WHERE firma_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            // 10. Teams
            $stmt = $this->db->prepare("UPDATE teams SET deleted_at = ? WHERE firm_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            // 11. Project Gelir Gider
            $stmt = $this->db->prepare("UPDATE project_gelir_gider SET deleted_at = ? WHERE (firm_id = ? OR project_id IN (SELECT id FROM projects WHERE firm_id = ?)) AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id, $raw_id]);

            // 12. Project Tasks
            $stmt = $this->db->prepare("UPDATE project_tasks SET deleted_at = ? WHERE (firm_id = ? OR project_id IN (SELECT id FROM projects WHERE firm_id = ?)) AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id, $raw_id]);

            // 13. User Roles
            $stmt = $this->db->prepare("UPDATE userroles SET deleted_at = ? WHERE firm_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            // 14. Sub-users of the firm (not main user)
            $stmt = $this->db->prepare("UPDATE users SET deleted_at = ? WHERE firm_id = ? AND is_main_user = 0 AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
            $stmt->execute([$now, $raw_id]);

            $this->db->commit();

            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('company', 'delete', "Firma ve bağlı tüm veriler soft-delete yapıldı: " . ($firm->firm_name ?? 'Firma'));

            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    //Firmayı say
    public function countMyFirms($user_id)
    {
        $query = $this->db->prepare("SELECT COUNT(*) as count FROM myfirms WHERE user_id = ? AND (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '')");
        $query->execute([$user_id]);
        $result = $query->fetch(PDO::FETCH_OBJ)->count;
        return $result;
    }
}
