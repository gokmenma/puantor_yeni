<?php 

require_once 'BaseModel.php';

class NationalHolidaysModel extends Model {

    protected $table = 'national_holidays';

    public function __construct() {
        parent::__construct($this->table);
    }

    // Resmi tatiller sistem genelinde ortaktır.
    public function all() {
        $sql = $this->db->prepare("SELECT * FROM $this->table ORDER BY holiday_date DESC");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Belirtilen tarih aralığındaki sistem geneli resmi tatilleri getirir.
     */
    public function getByDateRange($startDate, $endDate) {
        $sql = $this->db->prepare(
            "SELECT id, holiday_name, holiday_date, holiday_type, day_ratio, description
             FROM $this->table
             WHERE is_active = 1 AND holiday_date BETWEEN ? AND ?
             ORDER BY holiday_date ASC"
        );
        $sql->execute([$startDate, $endDate]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function findActiveByDate($date) {
        $sql = $this->db->prepare(
            "SELECT id, holiday_name, holiday_date, holiday_type, day_ratio, description
             FROM $this->table
             WHERE is_active = 1 AND holiday_date = ?
             ORDER BY id DESC LIMIT 1"
        );
        $sql->execute([$date]);
        return $sql->fetch(PDO::FETCH_OBJ) ?: null;
    }
}
