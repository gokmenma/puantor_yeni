<?php

require_once "BaseModel.php";

class AdvanceRequest extends Model
{
    public function __construct()
    {
        parent::__construct("personel_avans_talepleri");
    }

    public function getRequestsByFirm($firm_id)
    {
        $sql = $this->db->prepare("SELECT a.*, p.full_name, DATE_FORMAT(a.created_at, '%d.%m.%Y %H:%i') as formatted_date 
                                   FROM personel_avans_talepleri a 
                                   JOIN persons p ON a.person_id = p.id 
                                   WHERE p.firm_id = ? 
                                   ORDER BY a.id DESC");
        $sql->execute([$firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function getStats($firm_id)
    {
        $sql = $this->db->prepare("SELECT 
                                    SUM(CASE WHEN durum = 0 THEN 1 ELSE 0 END) as pending_count,
                                    SUM(CASE WHEN durum = 1 THEN 1 ELSE 0 END) as approved_count,
                                    SUM(CASE WHEN durum = 1 THEN tutar ELSE 0 END) as approved_amount,
                                    SUM(CASE WHEN durum = 2 THEN 1 ELSE 0 END) as rejected_count
                                   FROM personel_avans_talepleri a
                                   JOIN persons p ON a.person_id = p.id
                                   WHERE p.firm_id = ?");
        $sql->execute([$firm_id]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }
}
