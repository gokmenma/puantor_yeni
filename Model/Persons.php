<?php

require_once 'BaseModel.php';
use App\Helper\Security;

class Persons extends Model
{
    protected $table = 'persons';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        require_once __DIR__ . '/ActivityLogModel.php';
        $action = (isset($data['id']) && $data['id'] > 0) ? 'güncellendi' : 'eklendi';
        $name = $data['full_name'] ?? 'Personel';
        ActivityLogModel::log('personnel', (isset($data['id']) && $data['id'] > 0) ? 'update' : 'add', "Personel {$action}: {$name}");
        return $id;
    }

    public function delete($id)
    {
        $person = $this->find($id);
        if ($person) {
            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('personnel', 'delete', "Personel silindi: {$person->full_name}");
        }
        return parent::delete($id);
    }

    public function getPersonsByFirm($firm_id)
    {
        $query = $this->db->prepare('SELECT * FROM persons WHERE firm_id = ? and deleted_at IS NULL');
        $query->execute([$firm_id]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }
//Personelin ad soyadını getir
    public function getPersonName($person_id)
    {
        $query = $this->db->prepare("SELECT full_name FROM $this->table WHERE id = ?");
        $query->execute([$person_id]);
        return $query->fetch(PDO::FETCH_OBJ);
    }

    //Aktif personelleri getir
    public function getPersonsByActive()
    {
        $query = $this->db->prepare('SELECT * FROM persons WHERE firm_id = ? and job_end_date IS NOT NULL');
        $query->execute([$_SESSION['firm_id']]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPersonIdByFirm($firm_id)
    {
        $query = $this->db->prepare('SELECT id FROM persons WHERE firm_id = ? and deleted_at IS NULL');
        $query->execute([$firm_id]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }
    
    
    public function getPersonIdByFirmCurrentMonth($firm_id, $first_day, $last_day, $show_all = false)
    {
        // Mavi Yaka personellerin listede görünmesi için ya bir projeye atanmış olmaları 
        // ya da bu ay içinde puantaj kayıtlarının olması gerekir.
        // EĞER $show_all true ise (Personelleri Güncelle tıklandıysa) bu kontrolü atla.
        $sql = 'SELECT id FROM persons p 
                WHERE firm_id = ? 
                AND STR_TO_DATE(job_start_date, "%d.%m.%Y") <= ? 
                AND deleted_at IS NULL';
        
        if (!$show_all) {
            $sql .= ' AND (
                        p.wage_type = 1 
                        OR EXISTS (SELECT 1 FROM puantaj WHERE person = p.id AND gun >= ? AND gun <= ?)
                        OR STR_TO_DATE(job_start_date, "%d.%m.%Y") >= STR_TO_DATE(?, "%Y%m%d")
                    )';
        }

        $query = $this->db->prepare($sql);
        
        if (!$show_all) {
            $query->execute([$firm_id, $last_day, $first_day, $last_day, $first_day]);
        } else {
            $query->execute([$firm_id, $last_day]);
        }
        
        return $query->fetchAll(PDO::FETCH_OBJ);
    }
    public function getPersonIdByFirmBlueCollar($firm_id)
    {
        $query = $this->db->prepare('SELECT id FROM persons WHERE firm_id = ? AND wage_type = ? and deleted_at IS NULL');
        $query->execute([$firm_id,2]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }
    public function getPersonIdByFirmBlueCollarCurrentMonth($firm_id, $first_day, $last_day, $job_group = 0, $team_id = 0)
    {
        $sql = 'SELECT id FROM persons p 
                WHERE firm_id = ? AND wage_type = ? 
                AND STR_TO_DATE(job_start_date, "%d.%m.%Y") <= ? 
                AND deleted_at IS NULL
                AND (
                    (p.job_end_date IS NULL OR p.job_end_date = "")
                    OR EXISTS (SELECT 1 FROM project_person WHERE person_id = p.id)
                    OR EXISTS (SELECT 1 FROM puantaj WHERE person = p.id AND gun >= ? AND gun <= ?)
                    OR STR_TO_DATE(job_start_date, "%d.%m.%Y") >= STR_TO_DATE(?, "%Y%m%d")
                )';
        $params = [$firm_id, 2, $last_day, $first_day, $last_day, $first_day];

        if ($job_group > 0) {
            $sql .= ' AND job_group = ?';
            $params[] = $job_group;
        }

        if ($team_id > 0) {
            $sql .= ' AND team_id = ?';
            $params[] = $team_id;
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }



    public function getPersonByField($person_id,$field)
    {
        $query = $this->db->prepare("SELECT * FROM persons WHERE id = ?");
        $query->execute([$person_id]);
        $person = $query->fetch(PDO::FETCH_OBJ);
        if (!$person) {
            return "Personel Silinmiş";
        }
        return $person->$field;
    }
    public function getDailyWages($person_id)
    {
        $query = $this->db->prepare('SELECT daily_wages FROM persons WHERE id = ?');
        $query->execute([$person_id]);
        return $query->fetch(PDO::FETCH_OBJ);
    }

    public function getPersonSalary($person_id, $start_date, $end_date)
    {
        $query = $this->db->prepare('SELECT SUM(TUTAR) as tutar FROM puantaj WHERE person=? AND GUN >= ? AND GUN <= ?');
        $query->execute([$person_id, $start_date, $end_date]);
        return $query->fetch(PDO::FETCH_OBJ)->tutar;
    }

    public function getPersonByKimlikNo($kimlik_no)
    {
        // Since kimlik_no is stored encrypted with a random IV, we cannot search it directly in SQL
        // We fetch all records and compare the decrypted value
        // Note: For large datasets, a hash column should be added to the database for indexing
        $sql = "SELECT * FROM $this->table WHERE deleted_at IS NULL";
        $query = $this->db->prepare($sql);
        $query->execute();
        $persons = $query->fetchAll(PDO::FETCH_OBJ);

        foreach ($persons as $person) {
            $decrypted = Security::safeDecrypt($person->kimlik_no);
            if ($decrypted == $kimlik_no) {
                return $person;
            }
        }
        return null;
    }
}