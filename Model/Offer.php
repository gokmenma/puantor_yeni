<?php

require_once "BaseModel.php";


require_once $_SERVER["DOCUMENT_ROOT"].  "/App/Helper/helper.php";

class Offer extends Model
{
    protected $table = "offers";
    public function __construct()
    {
        parent::__construct($this->table);
    }

    public function getTeklifler()
    {
        return $this->all();
    }


    public function saveandUpdate($id, $data)
    {
        $this->attributes = $data; // Gelen verileri attributes özelliğine atayın
        if (!empty($id)) {
            if($id > 0){$this->isNew = false;} // Eğer id varsa, yeni bir kayıt oluşturulmamıştır
            $this->attributes[$this->primaryKey] = $id;
            
        }
        return parent::save(); // Model sınıfındaki save metodunu çağırın

       
    }
}
