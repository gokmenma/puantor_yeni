<?php

require_once "Database/db.php";

use Database\Db;

class Products extends Db
{
    public function productSelect($name = "products")
    {
        $query = $this->db->prepare("SELECT * FROM products"); // Tüm sütunları seç
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_OBJ); // Tüm sonuçları al

        $select = '<select name="' . $name . '" class="form-select select2" id="' . $name . '" style="width:100%">';
        $select .= '<option value="">Ürün Seçiniz</option>';
        foreach ($results as $row) { // $results üzerinde döngü
            $select .= '<option value="' . $row->id . '">' . $row->urun_adi . '</option>'; // $row->title yerine $row->name kullanıldı
        }
        $select .= '</select>';
        return $select;
    }
}
