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
        $sql = $this->db->prepare("SELECT * FROM $this->table 
                WHERE person_id = :person_id 
                AND start_date <= :date AND end_date >= :date
                ORDER BY amount DESC LIMIT 1");
        $sql->execute([
            'person_id' => $person_id,
            'date' => $date
        ]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

}