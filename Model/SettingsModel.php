<?php

require_once "BaseModel.php";

class SettingsModel extends Model
{
    protected $table = 'settings';
    public function __construct()
    {
        parent::__construct($this->table);
    }


    public function getSettings($set_name)
    {
        $firm_id = $_SESSION['firm_id'];
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ? AND set_name = ?");
        $sql->execute([$firm_id, $set_name]);
        return $sql->fetch(PDO::FETCH_OBJ) ?? null;
    }

    public function getSettingIdByUserAndAction($user_id, $action_name)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = ? and set_name = ?");
        $sql->execute([$user_id, $action_name]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    //Birden fazla kayıt varsa tüm kayıtları getir
    public function getSettingIdByUserAndActionAll($user_id, $action_name)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE user_id = ? and set_name = ? ORDER BY id DESC");
        $sql->execute([$user_id, $action_name]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }


    //Birden fazla kayıt varsa tüm kayıtları sil
    public function deleteByUserAndAction($user_id, $action_name)
    {
        $sql = $this->db->prepare("DELETE FROM $this->table WHERE user_id = ? and set_name = ?");
        return $sql->execute([$user_id, $action_name]);
    }

    //Program açıldığında tamamlanmamış görevleri getir veya getirme
    public function updateShowCompletedMissions($firm_id, $visible)
    {
        $sql = $this->db->prepare("UPDATE $this->table SET set_value = ? WHERE firm_id = ? and set_name = ?");
        return $sql->execute([$visible, $firm_id, "completed_tasks_visible"]);
    }

    public function upsertSetting($set_name, $set_value)
    {
        $firm_id = $_SESSION['firm_id'];
        $existing = $this->getSettings($set_name);
        if ($existing) {
            $sql = $this->getDb()->prepare("UPDATE $this->table SET set_value = ? WHERE firm_id = ? AND set_name = ?");
            return $sql->execute([$set_value, $firm_id, $set_name]);
        } else {
            $sql = $this->getDb()->prepare("INSERT INTO $this->table (firm_id, set_name, set_value) VALUES (?, ?, ?)");
            return $sql->execute([$firm_id, $set_name, $set_value]);
        }
    }
}