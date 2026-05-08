<?php 
require_once "Database/db.php";

use Database\Db;

class Jobs extends Db
{
    public function jobGroupsSelect($name = "job_groups", $id = null)
    {
        try {

            $firm_id = $_SESSION['firm_id'];
            $query = $this->db->prepare("SELECT * FROM job_groups where firm_id = ?"); // Tüm sütunları seç
            $query->execute([$firm_id]);
            $results = $query->fetchAll(PDO::FETCH_OBJ); // Tüm sonuçları al

            $select = '<select name="' . $name . '" class="form-select select2" id="' . $name . '" style="width:100%">';
            $select .= '<option value="">İş Grubu Seçiniz</option>';
            foreach ($results as $row) { // $results üzerinde döngü
                $selected = $id == $row->id ? ' selected' : ''; // Eğer id varsa seçili yap
                $select .= '<option value="' . $row->id . '"'  . $selected . '>' . $row->group_name . '</option>'; // $row->title yerine $row->name kullanıldı
            }
            $select .= '</select>';
            return $select;
        } catch (PDOException $e) {
            return "Veritabanı hatası: " . $e->getMessage();
        }
    }


    public function getJobGroupName($id)
    {
        try {
            $query = $this->db->prepare("SELECT company_name FROM companies WHERE id = :id");
            $query->execute(array("id" => $id));
            $result = $query->fetch(PDO::FETCH_OBJ);
            if ($result) {
                return $result->company;
            } else {
                return "";
            }
        } catch (PDOException $e) {
            return "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
