<?php

require_once $_SERVER["DOCUMENT_ROOT"]. "/Database/db.php";

use Database\Db;

class MissionsHelper extends Db
{
    public function getMissionsSelect($name = "mission_id", $firm_id = null) 
    {
        $query = $this->db->prepare("SELECT * FROM missions WHERE firm_id = ?");
        $query->execute([$firm_id]);
        $results = $query->fetchAll(PDO::FETCH_OBJ);

        $select = '<select name="' . $name . '" class="form-select modal-select select2" id="' . $name . '" style="width:100%">';
        $select .= '<option value="">Görev Seçiniz</option>';
        foreach ($results as $row) {
            $selected = $row->isDefault == 1 ? "selected" : "";
            $select .= '<option value="' . $row->id . '" ' . $selected . ' >' . $row->mission_name . '</option>';
        }
        $select .= '</select>';
        return $select;
    }


    public function getMissionHeaderSelect($name = "header_id", $header_id = null) 
    {
        $firm_id = $_SESSION['firm_id'];
        $query = $this->db->prepare("SELECT * FROM mission_headers WHERE firm_id = ?");
        $query->execute([$firm_id]);
        $results = $query->fetchAll(PDO::FETCH_OBJ);

        $select = '<select name="' . $name . '" class="form-select modal-select select2" id="' . $name . '" style="width:100%">';
        $select .= '<option value="">Görev Başlığı Seçiniz</option>';
        foreach ($results as $row) {
            $selected = $row->id == $header_id ? "selected" : "";
            $select .= '<option value="' . $row->id . '" ' . $selected . ' >' . $row->header_name . '</option>';
        }
        $select .= '</select>';
        return $select;
    }



    public function getMissionName($id)
    {
        $query = $this->db->prepare("SELECT mission_name FROM missions WHERE id = :id");
        $query->execute(array("id" => $id));
        $result = $query->fetch(PDO::FETCH_OBJ);
        if ($result) {
            return $result->mission_name;
        } else {
            return "";
        }
    }
}