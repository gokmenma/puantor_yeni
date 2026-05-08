<?php 

require_once "BaseModel.php";

class FeedBackModel extends Model
{
    public $table = "feedback";

    public function __construct()
    {
        parent::__construct($this->table);
    }

    
   
}