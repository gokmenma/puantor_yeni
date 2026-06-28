<?php 

require_once 'BaseModel.php';

class NationalHolidaysModel extends Model {

    protected $table = 'national_holidays';
    protected $firm_id;

    public function __construct() {
        parent::__construct($this->table);
        $this->firm_id = $_SESSION['firm_id'] ?? 0;
    }

    // Firma id'sine göre tüm resmi tatilleri getirir
    public function all() {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE firm_id = ? ORDER BY holiday_date DESC");
        $sql->execute([$this->firm_id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }
}
