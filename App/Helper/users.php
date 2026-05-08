<?php
require_once "Database/db.php";

use Database\Db;


class UserHelper extends Db
{

    public function userSelect($name = "users")
    {
        $query = $this->db->prepare("SELECT * FROM users"); // Tüm sütunları seç
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ); // Tüm sonuçları al

        $id = str_replace('[]', ' ', $name);
        $id = $id . uniqid();
        $select = '<select name="' . $name . '" class="form-select select2" id="' . $id . '" style="width:100%">';
        $select .= '<option value="">Personel Seçiniz</option>';
        foreach ($results as $row) { // $results üzerinde döngü
            $select .= '<option value="' . $row->id . '">' . $row->full_name . '</option>'; // $row->title yerine $row->name kullanıldı
        }
        $select .= '</select>';
        return $select;
    }

    public function userSelectMultiple($name = "users[]", $selectedIds = [])
    {
        $firm_id = $_SESSION["firm_id"];
        $user_id = $_SESSION["user"]->id;
        $query = $this->db->prepare("SELECT * FROM users where (firm_id = ? or id = ?)"); // Tüm sütunları seç
        $query->execute([$firm_id, $user_id]);
        $results = $query->fetchAll(PDO::FETCH_OBJ); // Tüm sonuçları al

        $id = str_replace('[]', ' ', $name);
        $id = $id . uniqid();
        $select = '<select name="' . $name . '" class="form-select select2" id="' . $id . '" style="width:100%" multiple>';
        //$select .= '<option value="">Personel Seçiniz</option>';
        foreach ($results as $row) { // $results üzerinde döngü
            $selected = in_array($row->id, $selectedIds) ? ' selected' : ''; // Eğer id varsa seçili yap
            $select .= '<option value="' . $row->id . '"' . $selected . '>' . $row->full_name . '</option>'; // $row->title yerine $row->name kullanıldı
        }
        $select .= '</select>';
        return $select;
    }


//Get user info
   

    

    //user id'leri aralarında virgül olan bir string alır ve bu id'lerin karşılık geldiği kullanıcıların isimlerini döndürür    
    public function getUsersName($user_ids)
    {
        $user_ids = explode(',', $user_ids);
              $users = [];
        foreach ($user_ids as $id) {
            $query = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $query->execute(['id' => $id]);
            $result = $query->fetch(PDO::FETCH_OBJ);
            if ($result) {
                $users[] = $result->full_name;
            } else {
                $users[] = "";
            }
        }
        return implode(',', $users);
    }

    public function userSelectwithJob($name = "userwithJob", $selectedId = null)
    {
        $query = $this->db->prepare("SELECT * FROM users "); // Tüm sütunları seç
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ); // Tüm sonuçları al

        // Benzersiz bir ID oluşturmak için uniqid() kullanılıyor.
        $selectId = $name . "_" . uniqid();
        $select = '<select name="' . $name . '" class="form-select select2" id="' . $selectId . '" style="width:100%">';
        $select .= '<option value="">Personel Seçiniz</option>';
        foreach ($results as $row) { // $results üzerinde döngü
            // Kullanıcıdan gelen $selectedId ile karşılaştırma yapılıyor.
            $selected = $selectedId == $row->id ? ' selected' : ''; // Eğer id varsa seçili yap
            $select .= '<option value="' . $row->id . '"'  . $selected . '>' . $row->full_name . " - " . $row->job . '</option>';
        }
        $select .= '</select>';
        return $select;
    }

    public function userRoles($name = "user_roles", $id = null)
    {
        $firm_id = $_SESSION["firm_id"];
        $query = $this->db->prepare("SELECT * FROM userroles where firm_id = ? "); // Tüm sütunları seç
        $query->execute([$firm_id]);
        $results = $query->fetchAll(PDO::FETCH_OBJ); // Tüm sonuçları al

        // Benzersiz bir ID oluşturmak için uniqid() kullanılıyor.
        $selectId = $name . "_" . uniqid();
        $select = '<select name="' . $name . '" class="form-select select2" id="' . $selectId . '" style="width:100%">';
        $select .= '<option value="">Rol Seçiniz</option>';
        foreach ($results as $row) { // $results üzerinde döngü
            // Kullanıcıdan gelen $selectedId ile karşılaştırma yapılıyor.
            $selected = $id == $row->id ? ' selected' : ''; // Eğer id varsa seçili yap
            $select .= '<option value="' . $row->id . '"'  . $selected . '>' . $row->roleName . '</option>';
        }
        $select .= '</select>';
        return $select;
    }
}
