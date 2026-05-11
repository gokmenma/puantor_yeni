<?php
require_once 'BaseModel.php';

class Projects extends Model
{
    protected $table = 'projects';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        require_once __DIR__ . '/ActivityLogModel.php';
        $action = (isset($data['id']) && $data['id'] > 0) ? 'güncellendi' : 'eklendi';
        $name = $data['project_name'] ?? 'Proje';
        ActivityLogModel::log('project', (isset($data['id']) && $data['id'] > 0) ? 'update' : 'add', "Proje {$action}: {$name}");
        return $id;
    }

    public function delete($id)
    {
        $project = $this->find($id);
        if ($project) {
            require_once __DIR__ . '/ActivityLogModel.php';
            ActivityLogModel::log('project', 'delete', "Proje silindi: " . ($project->project_name ?? 'Proje'));
        }
        return parent::delete($id);
    }

    public function getProjectsByFirm($firm_id)
    {
        $sql = "SELECT * FROM projects WHERE firm_id = ?";
        $params = [$firm_id];

        // Yetki kontrolü: Kullanıcı ana kullanıcı değilse ve sorumlu olduğu projeler tanımlanmışsa filtrele
        $user = $_SESSION['user'] ?? null;
        $is_main_user = (isset($user->is_main_user) && $user->is_main_user == 1) || (isset($user->parent_id) && $user->parent_id == 0);
        
        if ($user && !$is_main_user && !empty($user->responsible_projects)) {
            $project_ids = explode(',', $user->responsible_projects);
            if (!empty($project_ids)) {
                $placeholders = implode(',', array_fill(0, count($project_ids), '?'));
                $sql .= " AND id IN ($placeholders)";
                $params = array_merge($params, $project_ids);
            }
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function addPersontoProject($data)
    {
        $this->table = 'project_person';
        return $this->saveWithAttr($data);
    }

    // Proje ve firma id'sine göre personelleri getirir
    public function getPersontoProject($firm_id, $project_id)
    {
        $sql = $this->db->prepare('SELECT 
                                            p.*, 
                                            (CASE 
                                                WHEN EXISTS (SELECT 1 FROM project_person pp WHERE pp.project_id = ? AND pp.person_id = p.id) THEN 1 
                                                ELSE 0 
                                            END) AS is_added
                                        FROM 
                                            persons p
                                        WHERE 
                                            p.firm_id = ? AND p.deleted_at IS NULL;');
        $sql->execute([$project_id, $firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    // Proje id'sine göre personelleri getirir
    public function getPersonsByProject($project_id)
    {
        $sql = $this->db->prepare('SELECT 
                                            p.*, 
                                            (CASE 
                                                WHEN EXISTS (SELECT 1 FROM project_person pp WHERE pp.project_id = ? AND pp.person_id = p.id) THEN 1 
                                                ELSE 0 
                                            END) AS is_added
                                        FROM 
                                            persons p
                                        WHERE 
                                            p.deleted_at IS NULL;');
        $sql->execute([$project_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPersonFromProject($project_id)
    {
        $sql = $this->db->prepare('SELECT pp.*
                                            FROM project_person pp
                                            JOIN persons p ON p.id = pp.person_id
                                            WHERE pp.project_id = ? AND p.deleted_at IS NULL');
        $sql->execute([$project_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //Personel, gelen projede çalışıyor mu kontrol et
    //örnek: project_id = 1, person_id alanı = 275,278,279
    public function isExistPersonInProject($project_id, $person_id)
    {
        $sql = $this->db->prepare('SELECT COUNT(*) as total
                                            FROM project_person
                                            WHERE project_id = ?
                                            AND person_id = ?');
        $sql->execute([$project_id, $person_id]);
        return $sql->fetch(PDO::FETCH_OBJ)->total;
    }



    //Personelin kayıtlı olduğu projeleri getir
    public function getPersonProjects($person_id)
    {
        $sql = $this->db->prepare('SELECT project_id
                                            FROM project_person
                                            WHERE FIND_IN_SET(?, person_id);');
        $sql->execute([$person_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //Personelin kayıtlı olduğu dizileri getir
    public function getProjectsByPerson($person_id)
    {
        $sql = $this->db->prepare('SELECT project_id
                                            FROM project_person
                                            WHERE person_id = ?;');
        $sql->execute([$person_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getPersonIdByFromProjectCurrentMonth($project_id, $first_day, $last_day, $job_group = 0, $team_id = 0, $include_white_collar = false)
    {
        // Yetki kontrolü
        $user = $_SESSION['user'] ?? null;
        $is_main_user = (isset($user->is_main_user) && $user->is_main_user == 1) || (isset($user->parent_id) && $user->parent_id == 0);
        if ($user && !$is_main_user && !empty($user->responsible_projects)) {
            $allowed_projects = explode(',', $user->responsible_projects);
            if (!in_array($project_id, $allowed_projects)) {
                return []; // Yetkisi yoksa boş dön
            }
        }

        $wage_type_sql = $include_white_collar ? 'wage_type IN (1, 2)' : 'wage_type = 2';
        $sql = "SELECT p.*
                        FROM persons p
                        WHERE $wage_type_sql
                        AND (
                            EXISTS (SELECT 1 FROM project_person WHERE project_id = ? and person_id = p.id) 
                            OR EXISTS (SELECT 1 FROM puantaj WHERE project_id = ? AND person = p.id AND gun >= ? AND gun <= ?)
                        )
                        AND p.deleted_at IS NULL";
        $params = [$project_id, $project_id, $first_day, $last_day];

        if ($job_group > 0) {
            $sql .= ' AND job_group = ?';
            $params[] = $job_group;
        }

        if (!empty($team_id)) {
            $sql .= ' AND p.ekip = ?';
            $params[] = $team_id;
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function findById($id)
    {
        $sql = $this->db->prepare('SELECT id FROM project_person WHERE project_id = ?');
        $sql->execute([$id]);
        $result = $sql->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : 0;
    }

    function saveProgressPayment($data)
    {
        $this->table = 'project_gelir_gider';
        return $this->saveWithAttr($data);
    }


    //projede kayıtlı çalışma var mı kontrol et
    public function isExistPuantaj($id)
    {
        $sql = $this->db->prepare("SELECT COUNT(*) as total FROM puantaj WHERE project_id = ?");
        $sql->execute([$id]);
        return $sql->fetch(PDO::FETCH_OBJ)->total;
    }

    //Personeli projelere kaydet

    public function savePersonProjects($person_id, $projects)
    {
        $this->table = 'project_person';

        // Personelin kayıtlı olduğu projeleri getir
        $projects_by_person = $this->getProjectsByPerson($person_id);
        $existing_project_ids = array_map(function ($project) {
            return $project->project_id;
        }, $projects_by_person);

        // Silinecek projeleri belirle
        $projects_to_delete = array_diff($existing_project_ids, $projects);

        // Eklenecek projeleri belirle
        $projects_to_add = array_diff($projects, $existing_project_ids);

        // Silinecek projeleri sil
        foreach ($projects_to_delete as $project_id) {
            $sql = $this->db->prepare('DELETE FROM project_person WHERE project_id = ? AND person_id = ?');
            $sql->execute([$project_id, $person_id]);
        }

        // Eklenecek projeleri ekle
        foreach ($projects_to_add as $project_id) {
            //personel id boş ise ekleme yapma
            if ($person_id == 0 || $person_id == "") {
                continue;
            }
            //personel projede kayıtlı değilse ekle
            if ($this->isExistPersonInProject($project_id, $person_id) == 0) {
                $data = [
                    'project_id' => $project_id,
                    'person_id' => $person_id,
                    "state" => 1,
                    "user_id" => $_SESSION['user']->id,
                ];
                $this->saveWithAttr($data);
            }

        }
    }

    //Personeli projelerden sil
    public function deletePersonFromProjects($person_id, $project_id)
    {
        $sql = $this->db->prepare('DELETE FROM project_person WHERE person_id = ? and project_id = ?');
        $sql->execute([$person_id, $project_id]);
    }

}
