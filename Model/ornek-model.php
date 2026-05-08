<?php 
require_once "BaseModel.php";

class Bordro extends Model{
    protected $table = "maas_gelir_gider";

    public function __construct(){
        parent::__construct($this->table);
    }

    

}