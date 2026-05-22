<?php
require_once 'BaseModel.php';

class AbonelikPaketleriModel extends Model
{
    protected $table = 'abonelik_paketleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Retrieve all non-deleted packages (both active and inactive).
     */
    public function getPackages()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE silinme_tarihi IS NULL OR silinme_tarihi = '' ORDER BY id ASC");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Soft delete a package by setting silinme_tarihi.
     */
    public function softDeletePackage($id)
    {
        $id = App\Helper\Security::safeDecrypt($id);
        $sql = $this->db->prepare("UPDATE $this->table SET silinme_tarihi = ? WHERE id = ?");
        return $sql->execute([date('Y-m-d H:i:s'), $id]);
    }
}
