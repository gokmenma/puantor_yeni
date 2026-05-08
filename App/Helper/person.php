<?php
require_once "Database/db.php";

use App\Helper\Security;
use Database\Db;


class PersonHelper extends Db
{

    //Firmaya ait personelleri getirir
    public function getPersonSelect($name = "person_id", $person_id = null)
    {
        $query = $this->db->prepare("SELECT * FROM persons where firm_id = ?"); // Tüm sütunları seç
        $query->execute([$_SESSION["firm_id"]]); // Sorguyu çalıştır
        $results = $query->fetchAll(PDO::FETCH_OBJ); // Tüm sonuçları al

        $select = '<select name="' . $name . '" class="form-select select2" id="' . $name . '" style="width:100%">';
        $select .= '<option value="0">Personel Seçiniz</option>';
        foreach ($results as $row) { // $results üzerinde döngü
            $selected = $person_id == $row->id ? ' selected' : ''; // Eğer id varsa seçili yap
            $select .= '<option value="' . Security::encrypt($row->id) . '"'  . $selected . '>' . $row->full_name . '</option>'; // $row->title yerine $row->name kullanıldı
        }
        $select .= '</select>';
        return $select;
    }

}