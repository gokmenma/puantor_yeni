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

    public function find($id)
    {
        $person = parent::find($id);
        if ($person) {
            $this->attachCurrentWageToPerson($person);
        }
        return $person;
    }

    public function attachCurrentWages($persons, $date = null)
    {
        if (empty($persons) || !is_array($persons)) {
            return $persons;
        }

        $person_ids = [];
        foreach ($persons as $p) {
            if (is_object($p) && isset($p->id)) {
                $person_ids[] = (int)$p->id;
            }
        }
        $person_ids = array_unique(array_filter($person_ids));

        if (empty($person_ids)) {
            return $persons;
        }

        $targetDate = $date ? date('Ymd', strtotime($date)) : date('Ymd');
        $placeholders = implode(',', array_fill(0, count($person_ids), '?'));

        $sql = "SELECT person_id, amount, start_date, id 
                FROM person_daily_wages 
                WHERE person_id IN ($placeholders)
                  AND REPLACE(start_date, '-', '') <= ? 
                  AND (end_date IS NULL OR end_date = '' OR REPLACE(end_date, '-', '') >= ?)
                ORDER BY REPLACE(start_date, '-', '') DESC, id DESC";

        $params = array_merge($person_ids, [$targetDate, $targetDate]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $activeWages = $stmt->fetchAll(PDO::FETCH_OBJ);

        $wageMap = [];
        foreach ($activeWages as $w) {
            if (!isset($wageMap[$w->person_id])) {
                $wageMap[$w->person_id] = $w->amount;
            }
        }

        foreach ($persons as &$p) {
            if (is_object($p) && isset($p->id) && isset($wageMap[$p->id])) {
                $p->daily_wages = $wageMap[$p->id];
            }
        }

        return $persons;
    }

    public function attachCurrentWageToPerson($person, $date = null)
    {
        if (!$person || !is_object($person) || !isset($person->id)) {
            return $person;
        }
        $arr = [$person];
        $arr = $this->attachCurrentWages($arr, $date);
        return $arr[0];
    }

    public function syncDailyWage($person_id)
    {
        if (empty($person_id)) {
            return;
        }
        require_once __DIR__ . '/Wages.php';
        $wagesModel = new Wages();
        $activeWage = $wagesModel->getCurrentWage($person_id);
        if ($activeWage && isset($activeWage->amount)) {
            $stmt = $this->db->prepare("UPDATE persons SET daily_wages = ? WHERE id = ?");
            $stmt->execute([$activeWage->amount, $person_id]);
        }
    }

    public function getPersonsByFirm($firm_id)
    {
        $query = $this->db->prepare('SELECT * FROM persons WHERE firm_id = ? and deleted_at IS NULL');
        $query->execute([$firm_id]);
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        $results = $this->attachCurrentWages($results);
        return $this->filterPersons($results);
    }

    public function getPersonsByIds(array $person_ids)
    {
        $person_ids = array_values(array_unique(array_filter(array_map('intval', $person_ids))));
        if (empty($person_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($person_ids), '?'));
        $query = $this->db->prepare("SELECT * FROM persons WHERE id IN ($placeholders) AND deleted_at IS NULL");
        $query->execute($person_ids);
        $results = $this->attachCurrentWages($query->fetchAll(PDO::FETCH_OBJ));

        $personMap = [];
        foreach ($this->filterPersons($results) as $person) {
            $personMap[(int) $person->id] = $person;
        }

        $ordered = [];
        foreach ($person_ids as $person_id) {
            if (isset($personMap[$person_id])) {
                $ordered[] = $personMap[$person_id];
            }
        }
        return $ordered;
    }

    public function getPersonsServerSideCounts($firm_id, array $person_ids, string $status = ''): array
    {
        $person_ids = array_values(array_unique(array_filter(array_map('intval', $person_ids))));
        if (empty($person_ids)) {
            return ['total' => 0, 'filtered' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($person_ids), '?'));
        $baseSql = " FROM persons WHERE firm_id = ? AND deleted_at IS NULL AND id IN ($placeholders)";
        $params = array_merge([(int) $firm_id], $person_ids);

        $totalQuery = $this->db->prepare("SELECT COUNT(*)" . $baseSql);
        $totalQuery->execute($params);
        $total = (int) $totalQuery->fetchColumn();

        $statusSql = '';
        if ($status === 'active') {
            $statusSql = " AND (job_end_date IS NULL OR job_end_date = '')";
        } elseif ($status === 'passive') {
            $statusSql = " AND (job_end_date IS NOT NULL AND job_end_date != '')";
        }

        $filteredQuery = $this->db->prepare("SELECT COUNT(*)" . $baseSql . $statusSql);
        $filteredQuery->execute($params);

        return [
            'total' => $total,
            'filtered' => (int) $filteredQuery->fetchColumn(),
        ];
    }

    public function getPersonsServerSidePage(
        $firm_id,
        array $person_ids,
        int $start,
        int $length,
        string $status = '',
        string $order_field = 'id',
        string $order_direction = 'asc'
    ): array {
        $person_ids = array_values(array_unique(array_filter(array_map('intval', $person_ids))));
        if (empty($person_ids)) {
            return [];
        }

        $allowedOrderFields = [
            'id', 'full_name', 'wage_type', 'job_start_date', 'job_end_date',
            'job_group', 'job', 'ekip', 'address', 'description',
        ];
        if (!in_array($order_field, $allowedOrderFields, true)) {
            $order_field = 'id';
        }
        $order_direction = strtolower($order_direction) === 'desc' ? 'DESC' : 'ASC';
        $start = max(0, $start);
        $length = max(10, min(100, $length));

        $placeholders = implode(',', array_fill(0, count($person_ids), '?'));
        $sql = "SELECT * FROM persons
                WHERE firm_id = ? AND deleted_at IS NULL AND id IN ($placeholders)";
        $params = array_merge([(int) $firm_id], $person_ids);

        if ($status === 'active') {
            $sql .= " AND (job_end_date IS NULL OR job_end_date = '')";
        } elseif ($status === 'passive') {
            $sql .= " AND (job_end_date IS NOT NULL AND job_end_date != '')";
        }

        $sql .= " ORDER BY $order_field $order_direction, id ASC LIMIT $length OFFSET $start";
        $query = $this->db->prepare($sql);
        $query->execute($params);
        return $this->attachCurrentWages($query->fetchAll(PDO::FETCH_OBJ));
    }

    public function getPayrollPersonsServerSideCount($firm_id, array $person_ids, string $first_day): int
    {
        $person_ids = array_values(array_unique(array_filter(array_map('intval', $person_ids))));
        if (empty($person_ids)) {
            return 0;
        }

        $firstDay = strlen($first_day) === 8
            ? substr($first_day, 0, 4) . '-' . substr($first_day, 4, 2) . '-' . substr($first_day, 6, 2)
            : $first_day;
        $placeholders = implode(',', array_fill(0, count($person_ids), '?'));
        $query = $this->db->prepare("
            SELECT COUNT(*)
            FROM persons
            WHERE firm_id = ?
              AND deleted_at IS NULL
              AND id IN ($placeholders)
              AND (
                  job_end_date IS NULL
                  OR job_end_date = ''
                  OR COALESCE(
                      STR_TO_DATE(job_end_date, '%d.%m.%Y'),
                      STR_TO_DATE(job_end_date, '%Y-%m-%d')
                  ) >= ?
              )
        ");
        $query->execute(array_merge([(int) $firm_id], $person_ids, [$firstDay]));
        return (int) $query->fetchColumn();
    }

    public function getPayrollPersonsServerSidePage(
        $firm_id,
        array $person_ids,
        string $first_day,
        int $start,
        int $length,
        string $order_field = 'full_name',
        string $order_direction = 'asc'
    ): array {
        $person_ids = array_values(array_unique(array_filter(array_map('intval', $person_ids))));
        if (empty($person_ids)) {
            return [];
        }

        $allowedOrderFields = [
            'id', 'full_name', 'wage_type', 'job', 'ekip', 'iban_number', 'job_start_date',
        ];
        if (!in_array($order_field, $allowedOrderFields, true)) {
            $order_field = 'full_name';
        }
        $orderDirection = strtolower($order_direction) === 'desc' ? 'DESC' : 'ASC';
        $start = max(0, $start);
        $length = max(10, min(100, $length));
        $firstDay = strlen($first_day) === 8
            ? substr($first_day, 0, 4) . '-' . substr($first_day, 4, 2) . '-' . substr($first_day, 6, 2)
            : $first_day;
        $placeholders = implode(',', array_fill(0, count($person_ids), '?'));
        $query = $this->db->prepare("
            SELECT *
            FROM persons
            WHERE firm_id = ?
              AND deleted_at IS NULL
              AND id IN ($placeholders)
              AND (
                  job_end_date IS NULL
                  OR job_end_date = ''
                  OR COALESCE(
                      STR_TO_DATE(job_end_date, '%d.%m.%Y'),
                      STR_TO_DATE(job_end_date, '%Y-%m-%d')
                  ) >= ?
              )
            ORDER BY $order_field $orderDirection, id ASC
            LIMIT $length OFFSET $start
        ");
        $query->execute(array_merge([(int) $firm_id], $person_ids, [$firstDay]));
        return $this->attachCurrentWages($query->fetchAll(PDO::FETCH_OBJ));
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
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        $results = $this->attachCurrentWages($results);
        return $this->filterPersons($results);
    }

    public function getPersonIdByFirm($firm_id)
    {
        $query = $this->db->prepare('SELECT id FROM persons WHERE firm_id = ? and deleted_at IS NULL');
        $query->execute([$firm_id]);
        return $this->filterPersons($query->fetchAll(PDO::FETCH_OBJ));
    }
    
    
    public function getPersonIdByFirmCurrentMonth($firm_id, $first_day, $last_day, $show_all = false, $team_id = '')
    {
        $sql = 'SELECT id FROM persons p
                WHERE firm_id = ?
                AND deleted_at IS NULL';
        $params = [$firm_id];

        if (!empty($team_id)) {
            $sql .= ' AND p.ekip = ?';
            $params[] = $team_id;
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);

        return $this->filterPersons($query->fetchAll(PDO::FETCH_OBJ));
    }
    public function getPersonIdByFirmBlueCollar($firm_id)
    {
        $query = $this->db->prepare('SELECT id FROM persons WHERE firm_id = ? AND wage_type = ? and deleted_at IS NULL');
        $query->execute([$firm_id,2]);
        return $this->filterPersons($query->fetchAll(PDO::FETCH_OBJ));
    }
    public function getPersonIdByFirmBlueCollarCurrentMonth($firm_id, $first_day, $last_day, $job_group = 0, $team_id = 0, $include_white_collar = false, $person_status = 'active')
    {
        $first_day_formatted = substr($first_day, 0, 4) . '-' . substr($first_day, 4, 2) . '-' . substr($first_day, 6, 2);
        $last_day_formatted = substr($last_day, 0, 4) . '-' . substr($last_day, 4, 2) . '-' . substr($last_day, 6, 2);

        $wage_type_sql = $include_white_collar ? 'p.wage_type IN (1, 2)' : 'p.wage_type = 2';
        $sql = "SELECT p.* FROM persons p 
                WHERE p.firm_id = ? AND $wage_type_sql 
                AND p.deleted_at IS NULL
                AND (p.job_start_date IS NULL OR p.job_start_date = '' OR STR_TO_DATE(p.job_start_date, '%d.%m.%Y') <= ?)";
        $params = [$firm_id, $last_day_formatted];

        if ($person_status === 'active') {
            $sql .= " AND (p.job_end_date IS NULL OR p.job_end_date = '' OR STR_TO_DATE(p.job_end_date, '%d.%m.%Y') >= ?)";
            $params[] = $first_day_formatted;
        } elseif ($person_status === 'passive') {
            $sql .= " AND (p.job_end_date IS NOT NULL AND p.job_end_date != '')";
        } else {
            // For 'all' status: align with request and only show active
            $sql .= " AND (p.job_end_date IS NULL OR p.job_end_date = '' OR STR_TO_DATE(p.job_end_date, '%d.%m.%Y') >= ?)";
            $params[] = $first_day_formatted;
        }

        if ($job_group > 0) {
            $sql .= ' AND p.job_group = ?';
            $params[] = $job_group;
        }

        if (!empty($team_id)) {
            $sql .= ' AND p.ekip = ?';
            $params[] = $team_id;
        }

        $query = $this->db->prepare($sql);
        $query->execute($params);
        $results = $query->fetchAll(PDO::FETCH_OBJ);
        $results = $this->attachCurrentWages($results);
        return $this->filterPersons($results);
    }



    public function getPersonByField($person_id,$field)
    {
        $query = $this->db->prepare("SELECT * FROM persons WHERE id = ?");
        $query->execute([$person_id]);
        $person = $query->fetch(PDO::FETCH_OBJ);
        if (!$person) {
            return "Personel Silinmiş";
        }
        if ($field === 'daily_wages') {
            $this->attachCurrentWageToPerson($person);
        }
        return $person->$field;
    }
    public function getDailyWages($person_id, $date = null)
    {
        require_once __DIR__ . '/Wages.php';
        $wagesModel = new Wages();
        $activeWage = $wagesModel->getCurrentWage($person_id, $date);
        if ($activeWage && isset($activeWage->amount)) {
            return (object)['daily_wages' => $activeWage->amount];
        }
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

    public function getPersonByAuthField($value)
    {
        $value = trim($value);
        if (empty($value)) {
            return null;
        }

        $sql = "SELECT * FROM $this->table WHERE deleted_at IS NULL";
        $query = $this->db->prepare($sql);
        $query->execute();
        $persons = $query->fetchAll(PDO::FETCH_OBJ);

        $matches = [];
        
        $cleanValuePhone = preg_replace('/[^0-9]/', '', $value);
        $valueLower = mb_strtolower($value, 'UTF-8');

        foreach ($persons as $person) {
            $decryptedKimlik = Security::safeDecrypt($person->kimlik_no);
            $decryptedPhone = Security::safeDecrypt($person->phone);
            $decryptedEmail = Security::safeDecrypt($person->email);
            $fullNameLower = mb_strtolower($person->full_name, 'UTF-8');

            $cleanKimlik = preg_replace('/[^0-9]/', '', $decryptedKimlik);
            $cleanPhone = preg_replace('/[^0-9]/', '', $decryptedPhone);
            $emailLower = mb_strtolower($decryptedEmail, 'UTF-8');

            $isMatch = false;

            // 1. T.C. Kimlik veya Telefon Kontrolü (Girişte rakam varsa)
            if (!empty($cleanValuePhone)) {
                if ($cleanKimlik && $cleanKimlik === $cleanValuePhone) {
                    $isMatch = true;
                }
                if ($cleanPhone) {
                    $basePhoneInput = ltrim($cleanValuePhone, '0');
                    if (substr($basePhoneInput, 0, 2) === '90' && strlen($basePhoneInput) > 10) {
                        $basePhoneInput = substr($basePhoneInput, 2);
                    }
                    
                    $basePhoneDB = ltrim($cleanPhone, '0');
                    if (substr($basePhoneDB, 0, 2) === '90' && strlen($basePhoneDB) > 10) {
                        $basePhoneDB = substr($basePhoneDB, 2);
                    }

                    if ($basePhoneDB === $basePhoneInput || $cleanPhone === $cleanValuePhone) {
                        $isMatch = true;
                    }
                }
            }

            // 2. E-posta Kontrolü
            if ($emailLower && $emailLower === $valueLower) {
                $isMatch = true;
            }

            // 3. Kullanıcı Adı (Ad Soyad) Kontrolü
            if ($fullNameLower && $fullNameLower === $valueLower) {
                $isMatch = true;
            }

            if ($isMatch) {
                // Eğer şifresi olan bir kayıt bulursak hemen döndür (en öncelikli)
                if (!empty($person->password)) {
                    return $person;
                }
                $matches[] = $person;
            }
        }
        
        // Şifreli kayıt bulunamadıysa ilk eşleşen kaydı döndür
        return !empty($matches) ? $matches[0] : null;
    }

    public function getPersonByKimlikNo($kimlik_no)
    {
        return $this->getPersonByAuthField($kimlik_no);
    }

    public function filterPersons($results)
    {
        if (!isset($_SESSION["user"])) {
            return $results;
        }

        $user_id = $_SESSION["user"]->id;
        try {
            $stmt = $this->db->prepare('SELECT id, responsible_persons FROM users WHERE id = ?');
            $stmt->execute([$user_id]);
            $u = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$u || empty($u->responsible_persons)) {
                return $results;
            }
        } catch (PDOException $e) {
            // Self-healing migration: Try to add the column automatically if it is missing
            try {
                $this->db->exec("ALTER TABLE users ADD COLUMN responsible_persons LONGTEXT NULL;");
            } catch (PDOException $alterEx) {
                // If alter fails (permissions, etc.), just gracefully return original results
            }
            return $results;
        }

        $saved_map = json_decode($u->responsible_persons, true);
        if (!is_array($saved_map) || empty($saved_map)) {
            return $results;
        }

        $page = isset($_GET['p']) ? $_GET['p'] : (isset($_GET['route']) ? $_GET['route'] : '');

        $module_key = null;
        if (strpos($page, 'puantaj') !== false) {
            $module_key = 'puantaj';
        } elseif (strpos($page, 'payroll') !== false || strpos($page, 'bordro') !== false) {
            $module_key = 'bordro';
        } elseif (strpos($page, 'person') !== false) {
            $module_key = 'personel';
        }

        if ($module_key !== null) {
            $filtered = [];
            foreach ($results as $row) {
                $id = isset($row->id) ? $row->id : null;
                if ($id !== null) {
                    if (isset($saved_map[$id]) && in_array($module_key, $saved_map[$id])) {
                        $filtered[] = $row;
                    }
                }
            }
            return $filtered;
        }

        return $results;
    }

    public function getPersonByKimlikNoAndFirm($kimlik_no, $firm_id)
    {
        $kimlik_no = trim($kimlik_no);
        if (empty($kimlik_no)) {
            return null;
        }

        $sql = "SELECT * FROM persons WHERE firm_id = ? AND deleted_at IS NULL";
        $query = $this->db->prepare($sql);
        $query->execute([$firm_id]);
        $persons = $query->fetchAll(PDO::FETCH_OBJ);

        $cleanValue = preg_replace('/[^0-9]/', '', $kimlik_no);

        foreach ($persons as $person) {
            $decryptedKimlik = Security::safeDecrypt($person->kimlik_no);
            $cleanKimlik = preg_replace('/[^0-9]/', '', $decryptedKimlik);

            if ($cleanKimlik && $cleanKimlik === $cleanValue) {
                return $person;
            }
        }
        return null;
    }

    public function setSessionToken($id, $token)
    {
        $sql = $this->db->prepare("UPDATE $this->table SET session_token = ? WHERE id = ?");
        $sql->execute([$token, $id]);
        return $token;
    }

    public function getPersonBySessionToken($token)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE session_token = ? AND deleted_at IS NULL");
        $sql->execute([$token]);
        $person = $sql->fetch(PDO::FETCH_OBJ);
        if ($person) {
            $person->kimlik_no = Security::safeDecrypt($person->kimlik_no);
            $person->phone = Security::safeDecrypt($person->phone);
            $person->email = Security::safeDecrypt($person->email);
            $person->iban_number = Security::safeDecrypt($person->iban_number);
            return $person;
        }
        return null;
    }
}
