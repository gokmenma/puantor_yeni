<?php

require_once "BaseModel.php";


class Wages extends Model
{

    protected $tableName = "person_daily_wages";
    public function __construct()
    {
        parent::__construct($this->tableName);
    }

    public function getAll($person_id, $firstDay, $lastDay)
    {
        $sql = "SELECT * FROM $this->table WHERE person_id = :person_id AND date BETWEEN :firstDay AND :lastDay";
        $query = $this->db->prepare($sql);
        $query->execute([
            'person_id' => $person_id,
            'firstDay' => $firstDay,
            'lastDay' => $lastDay
        ]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getWage($person_id, $date)
    {
        $sql = "SELECT * FROM $this->table WHERE person_id = :person_id AND date = :date";
        $query = $this->db->prepare($sql);
        $query->execute([
            'person_id' => $person_id,
            'date' => $date
        ]);
        return $query->fetch(PDO::FETCH_OBJ);
    }

    public function getWageById($id)
    {
        $sql = "SELECT * FROM $this->table WHERE id = :id";
        $query = $this->db->prepare($sql);
        $query->execute([
            'id' => $id
        ]);
        return $query->fetch(PDO::FETCH_OBJ);
    }

    public function getWageByPersonId($person_id)
    {
        $sql = "SELECT * FROM $this->table WHERE person_id = :person_id";
        $query = $this->db->prepare($sql);
        $query->execute([
            'person_id' => $person_id
        ]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    public function getWageByDate($date)
    {
        $sql = "SELECT * FROM $this->table WHERE date = :date";
        $query = $this->db->prepare($sql);
        $query->execute([
            'date' => $date
        ]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }


    public function getWageByPersonIdAndDate($person_id, $date)
    {
        require_once __DIR__ . '/../App/Helper/date.php';
        $targetDate = $date ? \App\Helper\Date::Ymd($date, 'Ymd') : date('Ymd');
        $sql = $this->db->prepare("SELECT * FROM $this->table
                WHERE person_id = :person_id
                AND REPLACE(start_date, '-', '') <= :date 
                AND (end_date IS NULL OR end_date = '' OR REPLACE(end_date, '-', '') >= :date)
                ORDER BY REPLACE(start_date, '-', '') DESC, id DESC LIMIT 1");
        $sql->execute([
            'person_id' => $person_id,
            'date' => $targetDate
        ]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function getCurrentWage($person_id, $date = null)
    {
        require_once __DIR__ . '/../App/Helper/date.php';
        $targetDate = $date ? \App\Helper\Date::Ymd($date, 'Ymd') : date('Ymd');
        $sql = $this->db->prepare("SELECT * FROM $this->table
                WHERE person_id = :person_id
                AND REPLACE(start_date, '-', '') <= :today 
                AND (end_date IS NULL OR end_date = '' OR REPLACE(end_date, '-', '') >= :today)
                ORDER BY REPLACE(start_date, '-', '') DESC, id DESC LIMIT 1");
        $sql->execute(['person_id' => $person_id, 'today' => $targetDate]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    public function bulkInsertWages(array $records)
    {
        $sql = "INSERT INTO $this->table (person_id, wage_name, start_date, end_date, amount, description) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $count = 0;
        foreach ($records as $r) {
            $stmt->execute([$r['person_id'], $r['wage_name'], $r['start_date'], $r['end_date'], $r['amount'], $r['description']]);
            $count++;
        }
        return $count;
    }

}