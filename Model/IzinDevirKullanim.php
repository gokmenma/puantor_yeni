<?php
require_once 'BaseModel.php';
require_once __DIR__ . '/ActivityLogModel.php';

class IzinDevirKullanim extends Model
{
    protected $table = 'izin_devir_kullanim';

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Personelin devir kullanımlarını getirir.
     */
    public function getByPersonel(int $personel_id, int $firma_id): array
    {
        $sql = $this->db->prepare(
            "SELECT d.*, u.full_name AS olusturan_adi
             FROM {$this->table} d
             LEFT JOIN users u ON u.id = d.olusturan_id
             WHERE d.personel_id = ? AND d.firma_id = ?
             ORDER BY d.id ASC"
        );
        $sql->execute([$personel_id, $firma_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Personelin toplam devir kullanım gün sayısını döner.
     */
    public function getTotalDevirByPersonel(int $personel_id, int $firma_id): int
    {
        $sql = $this->db->prepare(
            "SELECT COALESCE(SUM(kullanilan_gun), 0) AS total
             FROM {$this->table}
             WHERE personel_id = ? AND firma_id = ?"
        );
        $sql->execute([$personel_id, $firma_id]);
        $res = $sql->fetch(PDO::FETCH_OBJ);
        return (int) ($res->total ?? 0);
    }

    /**
     * Firmadaki tüm personellerin devir kullanım toplamlarını personel_id bazlı dizi olarak döner.
     * [personel_id => total_devir_gun]
     */
    public function getTotalsByFirma(int $firma_id): array
    {
        $sql = $this->db->prepare(
            "SELECT personel_id, SUM(kullanilan_gun) AS total_devir
             FROM {$this->table}
             WHERE firma_id = ?
             GROUP BY personel_id"
        );
        $sql->execute([$firma_id]);
        $rows = $sql->fetchAll(PDO::FETCH_OBJ);
        $result = [];
        foreach ($rows as $r) {
            $result[(int)$r->personel_id] = (int)$r->total_devir;
        }
        return $result;
    }

    public function saveWithAttr($data)
    {
        $id = parent::saveWithAttr($data);
        $action = isset($data['id']) && $data['id'] > 0 ? 'update' : 'add';
        ActivityLogModel::log('izin_devir_kullanim', $action, "Personel ID {$data['personel_id']} için {$data['kullanilan_gun']} gün devir kullanımı " . ($action === 'add' ? 'eklendi' : 'güncellendi') . ".");
        return $id;
    }

    public function deleteById(int $id, int $firma_id): bool
    {
        $kayit = $this->find($id);
        if ($kayit && (int)$kayit->firma_id === $firma_id) {
            ActivityLogModel::log('izin_devir_kullanim', 'delete', "Personel ID {$kayit->personel_id} için ID {$id} devir kullanımı ({$kayit->kullanilan_gun} gün) silindi.");
            $sql = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ? AND firma_id = ?");
            $sql->execute([$id, $firma_id]);
            return $sql->rowCount() > 0;
        }
        return false;
    }
}
