<?php 

require_once 'BaseModel.php';

class PuantajTuruModel extends Model {

    protected $table = 'puantajturu';

    public function __construct() {
        parent::__construct($this->table);
    }
}
