<?php 
require_once "Database/db.php";

use Database\Db;

class Teams extends Db
{
    public function teamsSelect($name = "team_id", $id = null)
    {
        try {
            $firm_id = $_SESSION['firm_id'];
            
            // Sadece persons tablosundaki ekip kolonunu kullan
            $query = $this->db->prepare("
                SELECT DISTINCT ekip AS team_name FROM persons WHERE firm_id = ? AND deleted_at IS NULL AND ekip IS NOT NULL AND ekip != ''
                ORDER BY ekip ASC
            ");
            $query->execute([$firm_id]);
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            $select = '<select name="' . $name . '" class="form-select select2" id="' . $name . '" style="width:100%">';
            $select .= '<option value="">Ekip Seçiniz</option>';
            foreach ($results as $row) {
                $selected = $id == $row->team_name ? ' selected' : '';
                $select .= '<option value="' . $row->team_name . '"'  . $selected . '>' . $row->team_name . '</option>';
            }
            $select .= '</select>';
            return $select;
        } catch (PDOException $e) {
            // Eğer hata olursa en azından dropdown boş gelmesin ve sistem çalışmaya devam etsin
            return '<select name="' . $name . '" class="form-select" id="' . $name . '"><option value="">Ekip Seçiniz</option></select>';
        }
    }

    public function getTeamName($id)
    {
        // Artık string (ekip ismi) tutulduğu için kendisini dönderiyoruz
        return $id;
    }
}
