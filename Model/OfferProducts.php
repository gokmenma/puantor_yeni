

<?php

require_once "BaseModel.php";

class OfferProducts extends Model
{
    protected $table = 'offer_products';
    public function __construct()
    {
        parent::__construct($this->table);
    }


public function getProducts($offer_id = null)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE oid = ?");
        $sql->execute(array($offer_id));
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    public function deleteByOfferId($id)
    {
        $sql = $this->db->prepare("DELETE FROM $this->table WHERE oid = ?");
        $sql->execute(array($id));
    }


    public function saveOfferProducts($data)
    {
        $this->attributes = $data;
        $this->isNew = true;
        parent::save();
    }
}
