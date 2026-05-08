<?php 

require_once "BaseModel.php";

use Database\Db;

class Product extends Model{
    protected $table = "products";
    public function __construct()
    {
        parent::__construct($this->table);
    }
}