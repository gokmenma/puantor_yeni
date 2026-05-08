<?php 
require_once "Database/db.php";

use Database\Db;

class Teams extends Db
{
    public function teamsSelect($name = "team_id", $id = null)
    {
        try {
            $firm_id = $_SESSION['firm_id'];
            $query = $this->db->prepare("SELECT * FROM teams where firm_id = ?");
            $query->execute([$firm_id]);
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            $select = '<select name="' . $name . '" class="form-select select2" id="' . $name . '" style="width:100%">';
            $select .= '<option value="">Ekip Seçiniz</option>';
            foreach ($results as $row) {
                $selected = $id == $row->id ? ' selected' : '';
                $select .= '<option value="' . $row->id . '"'  . $selected . '>' . $row->team_name . '</option>';
            }
            $select .= '</select>';
            return $select;
        } catch (PDOException $e) {
            return "Veritabanı hatası: " . $e->getMessage();
        }
    }

    public function getTeamName($id)
    {
        try {
            $query = $this->db->prepare("SELECT team_name FROM teams WHERE id = :id");
            $query->execute(array("id" => $id));
            $result = $query->fetch(PDO::FETCH_OBJ);
            if ($result) {
                return $result->team_name;
            } else {
                return "";
            }
        } catch (PDOException $e) {
            return "Veritabanı hatası: " . $e->getMessage();
        }
    }
}
