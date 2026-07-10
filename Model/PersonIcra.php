<?php

require_once "BaseModel.php";

class PersonIcra extends Model
{
    protected $table = "person_icra_files";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    /**
     * Get all active execution files of a person, ordered by priority
     *
     * @param int $person_id
     * @return array
     */
    public function getByPersonId($person_id)
    {
        $sql = "SELECT * FROM $this->table WHERE person_id = :person_id AND deleted_at IS NULL ORDER BY icra_sirasi ASC";
        $query = $this->db->prepare($sql);
        $query->execute(['person_id' => $person_id]);
        return $query->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Get statistics for a person's execution files
     *
     * @param int $person_id
     * @return array
     */
    public function getStats($person_id)
    {
        $sql = "SELECT 
                    COUNT(id) as total_files,
                    SUM(CASE WHEN durum = 'Kesilen' THEN 1 ELSE 0 END) as active_files,
                    SUM(toplam_borc) as total_debt
                FROM $this->table 
                WHERE person_id = :person_id AND deleted_at IS NULL";
        
        $query = $this->db->prepare($sql);
        $query->execute(['person_id' => $person_id]);
        $res = $query->fetch(PDO::FETCH_OBJ);

        // Kalan borcu hesapla
        // Şimdilik yapılan kesinti sıfır kabul edilerek toplam borçtan çıkarılır.
        // İleride bordro ile entegrasyon yapıldığında kesintiler toplamı buraya yansıtılır.
        $total_debt = $res->total_debt ?? 0.00;
        $total_deductions = $this->getTotalDeductions($person_id);
        $remaining_debt = max(0, $total_debt - $total_deductions);

        return [
            'total_files' => (int)($res->total_files ?? 0),
            'active_files' => (int)($res->active_files ?? 0),
            'total_debt' => (float)$total_debt,
            'total_deductions' => (float)$total_deductions,
            'remaining_debt' => (float)$remaining_debt
        ];
    }

    /**
     * Get total deductions made for a person's execution files from wage_cut or other logs
     *
     * @param int $person_id
     * @return float
     */
    public function getTotalDeductions($person_id)
    {
        try {
            $sql = "SELECT SUM(tutar) as total FROM maas_gelir_kesinti 
                    WHERE person_id = :person_id AND kategori = 15 AND (aciklama LIKE '%İcra%' OR aciklama LIKE '%icra%')";
            $query = $this->db->prepare($sql);
            $query->execute(['person_id' => $person_id]);
            $res = $query->fetch(PDO::FETCH_OBJ);
            return (float)($res->total ?? 0.00);
        } catch (\Throwable $ex) {
            error_log("Failed to fetch total deductions: " . $ex->getMessage());
            return 0.00;
        }
    }
}
