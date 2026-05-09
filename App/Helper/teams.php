<?php 
require_once "Database/db.php";

use Database\Db;

class Teams extends Db
{
    public function teamsSelect($name = "team_id", $id = null)
    {
        try {
            $firm_id = $_SESSION['firm_id'];
            
            // If $id is numeric, resolve it to the team name from teams table
            if (!empty($id) && is_numeric($id)) {
                $q = $this->db->prepare("SELECT team_name FROM teams WHERE id = ?");
                $q->execute([$id]);
                $r = $q->fetch(PDO::FETCH_OBJ);
                if ($r) {
                    $id = $r->team_name;
                }
            }
            
            // Fetch distinct teams from both persons table (ekip column) and teams table for compatibility
            $query = $this->db->prepare("
                SELECT DISTINCT ekip AS team_name FROM persons WHERE firm_id = ? AND deleted_at IS NULL AND ekip IS NOT NULL AND ekip != ''
                UNION
                SELECT team_name FROM teams WHERE firm_id = ? AND team_name IS NOT NULL AND team_name != ''
            ");
            $query->execute([$firm_id, $firm_id]);
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
            return "Veritabanı hatası: " . $e->getMessage();
        }
    }

    public function getTeamName($id)
    {
        if (empty($id)) {
            return "";
        }
        
        // If it's a numeric ID, try to get from teams table, otherwise return the string itself
        if (is_numeric($id)) {
            try {
                $query = $this->db->prepare("SELECT team_name FROM teams WHERE id = :id");
                $query->execute(array("id" => $id));
                $result = $query->fetch(PDO::FETCH_OBJ);
                if ($result) {
                    return $result->team_name;
                }
            } catch (PDOException $e) {
                // Silently ignore DB errors
            }
        }
        return $id;
    }
}
