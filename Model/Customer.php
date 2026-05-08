<?php
require_once "BaseModel.php";

require_once "App/Helper/helper.php";

class Customer extends Model
{
    protected $table = "customers";

    public function getCustomers()
    {
        return $this->all();
    }

    public function getCustomerGroupByGrp($grp)
    {
        $query = $this->db->prepare("SELECT title FROM cgroups WHERE id = ?");
        $query->execute([$grp]);
        $result = $query->fetch(PDO::FETCH_OBJ);
        return $result ? $result->title : null;
    }

}
