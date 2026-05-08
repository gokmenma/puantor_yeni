<?php

require_once $_SERVER["DOCUMENT_ROOT"]. "/Database/db.php";

use Database\Db;

class BordroHelper extends Db
{

    //Gelir gider tipleri select
    public function getIncExpSelectByFirmAndType($name = "inc_exp_type",$type = 1, $id = null) 
    {
        $firm_id = $_SESSION['firm_id'];
        $query = $this->db->prepare("SELECT * FROM defines where firm_id = ? and  type_id = ?");
        $query->execute([$firm_id, $type]);
        $results = $query->fetchAll(PDO::FETCH_OBJ);

        $select = '<select name="' . $name . '" class="form-select modal-select select2" id="' . $name . '" style="width:100%">';
        $select .= '<option value="">Tür Seçiniz</option>';
        foreach ($results as $row) {
            $selected = $row->id == $id ? "selected" : "";
            $select .= '<option value="' . $row->name . '" ' . $selected . ' >' . $row->name . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

}