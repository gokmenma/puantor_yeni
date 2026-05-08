<?php


require_once "BaseModel.php";

class DefinesModel extends Model
{
    protected $table = "defines";
    protected $job_table = "job_groups";
    protected $firm_id;

    const TYPES = [
        1 => "Servis Konusu",
        2 => "Gelir-Gider Türü",
        3 => "İş Grubu",
        4 => "Ödeme Türü"
    ];
    public function __construct()
    {
        parent::__construct($this->table);
        $this->firm_id = $_SESSION['firm_id'] ?? 0;
    }


    //Job Groups- İş Grubu tanım : 3
    public function getDefinesByType($type)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ? and type_id = ?");
        $sql->execute([$this->firm_id, $type]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //servis konusu tanım : 1
    public function getServiceHeads()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = ? and statu = ?");
        $sql->execute([$_SESSION['user_id'], 1]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //gelir-gider türü tanım : 2
    public function getIncExpTypesByFirm()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE (firm_id = ? or firm_id = ?)  and type_id IN (1, 2) order by firm_id desc");
        $sql->execute([$this->firm_id,0]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getIncExpTypesByFirmandType($type)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE (firm_id < ? or firm_id = ?) and type_id = ?");
        $sql->execute([1,$this->firm_id, $type]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //Proje Durumu getirme : 5
    public function getProjectStatus()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ? and type_id = ?");
        $sql->execute([$this->firm_id, 5]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Gelir veya Gider türlerini array getirir
     */
    public function getExpenseTypes($type)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE type_id = ?");
        $sql->execute([$type]);
        if ($sql->rowCount() != 0) {

            $result = implode(',', array_map(function ($item) {
                return $item->id;
            }, $sql->fetchAll(PDO::FETCH_OBJ)));
            return $result;

        } else {
            return [];
        }
    }

    //Gelen id'den adını getir
    public function getTypeNameById($id)
    {
        $sql = $this->db->prepare("SELECT name FROM $this->table WHERE id = ?");
        $sql->execute([$id]);

        //eğer bulunamazsa boş döndür, varsa name döndür
        if ($sql->rowCount() == 0) {
            return "";
        } else {
            return $sql->fetch(PDO::FETCH_OBJ)->name;
        }
    }




}