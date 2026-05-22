<?php
require_once 'BaseModel.php';

class KullaniciAbonelikleriModel extends Model
{
    protected $table = 'kullanici_abonelikleri';

    public function __construct()
    {
        parent::__construct($this->table);
    }
}
