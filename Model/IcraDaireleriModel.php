<?php

require_once 'BaseModel.php';

class IcraDaireleriModel extends Model
{

    protected $table = 'icra_daireleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function all()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE silinme_tarihi IS NULL ORDER BY sehir ASC, daire_adi ASC");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function delete($id)
    {
        $id = Security::safeDecrypt($id);
        $sql = $this->db->prepare("UPDATE $this->table SET silinme_tarihi = NOW(), silen_kullanici = ? WHERE $this->primaryKey = ? AND silinme_tarihi IS NULL");
        $sql->execute([$_SESSION['user']->id ?? 0, $id]);

        if ($sql->rowCount() === 0) {
            throw new \Exception('Kayıt bulunamadı veya silinemedi.');
        }
        return true;
    }
}
