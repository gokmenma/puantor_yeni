<?php

require_once "Database/db.php";

use Database\Db;

class ReportHelper extends Db
{
    const ISLEMLER = [
        "BASINÇ" => "BASINÇ",
        "KONTROL" => "KONTROL",
        "DOLUM" => "DOLUM",
    ];

    const STATUS = [
        "1" => "UYGUN",
        "0" => "UYGUN DEĞİL",
    ];


    public static function islemSelect($name = "islem", $selected = 'BASINÇ')
    {
        $selected = strtoupper($selected);
        $id = str_replace('[]', ' ', $name);
        $id = $id . uniqid();
        $select = '<select id="' . $id . '" name="' . $name . '" class="select2 islem form-control" style="width:100%">';
        foreach (self::ISLEMLER as $key => $value) {
            $selectedAttr = $selected == $key ? 'selected' : '';
            $select .= "<option value='$key' $selectedAttr>$value</option>";
        }
        $select .= '</select>';
        return $select;
    }

    public static function statusSelect($name = "status", $selected = '1')
    {
        $id = str_replace('[]', ' ', $name);
        $id = $id . uniqid();
        $select = '<select id="' . $id . '" name="' . $name . '" class="select2 status form-control" style="width:100%">';
        foreach (self::STATUS as $key => $value) {
            $selectedAttr = $selected == $key ? 'selected' : '';
            $select .= "<option value='$key' $selectedAttr>$value</option>";
        }
        $select .= '</select>';
        return $select;
    }

    public function getNumber($type = null): string
    {
        $sql = $this->db->prepare("SELECT " . $type . " FROM define_numbers ORDER BY id DESC LIMIT 1");
        $sql->execute();
        $result = $sql->fetch(PDO::FETCH_OBJ);
        return strtoupper($type) . str_pad($result->$type, 4, "0", STR_PAD_LEFT);
    }
}
