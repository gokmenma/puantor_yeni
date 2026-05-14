<?php
require_once ROOT . "/Database/db.php";
require_once ROOT . "/Model/Cases.php";
require_once ROOT . "/Model/DefinesModel.php";

require_once ROOT . "/App/Helper/security.php";


use Database\Db;
use App\Helper\Security;

class Financial extends Db
{


    const TYPE = [
        1 => "Gelir",
        2 => "Gider",
        3 => "Virman",
        4 => "Diğer",
        5 => "Proje(Alınan Ödeme)",
        6 => "Proje(Yapılan Ödeme)",
        7 => "Personel Ödemesi",
        8 => "Firma Ödemesi",
        9 => "Alınan Proje Masrafı",
        10 => "Hakediş",
        11 => "Proje Masrafı",
        12 => "Proje Kesinti",
        13 => "Masraf",
        14 => "Puantaj Çalışma",
        15 => "Kesinti",
        16 => "Maaş",
    ];
    protected $caseObj;
    protected $Defines;


    public function __construct()
    {
        parent::__construct();
        $this->caseObj = new Cases();
        $this->Defines = new DefinesModel();
    }


    //firma id'ye göre gelir veya gider türlerini getirir
    public function getIncExpTypeSelect($name = "inc_exp_type", $type_id = 1)
    {
        //Gelen type_id boş ise varsayılan olarak gelir türlerini getir
        $results = $this->Defines->getIncExpTypesByFirmandType($type_id);


        $select = "<select name=\"{$name}\" class=\"form-select modal-select select2\" id=\"{$name}\" style=\"width:100%\">";
        $select .= '<option value="">Tür Seçiniz</option>';
        foreach ($results as $row) {
            $select .= "<option value=\"{$row->id}\">{$row->name}</option>";
        }
        $select .= '</select>';
        return $select;
    }

    //CaseSelect
    public function getCasesSelectByFirm($name = "case_id", $case_id = "")
    {

        //case_id boş ise firmanın varsayılan kasa id'sini al
        if (empty($case_id) && $case_id != 0) {
            $case_id = $this->caseObj->getDefaultCaseIdByFirm();
        }

        $cases = $this->caseObj->allCaseWithFirmId();
        $select = "<select name='" . $name . "' class=\"form-control select2\" id='" . $name . "' style='width:100%'>";
        $select .= "<option value='0'>Kasa Seçiniz</option>";

        foreach ($cases as $case) {
            $selectedAttr = $case_id == $case->id ? 'selected' : '';
            $select .= "<option value=\"" . Security::encrypt($case->id) . "\" {$selectedAttr}>{$case->case_name}-{$case->bank_name}/{$case->branch_name}</option>";
        }
        $select .= '</select>';
        return $select;
    }

    //Kullanıcıya göre kasaları getir
    public function getCasesSelectByUser($name = "case_id", $case_id = "")
    {
        $is_main_user = $_SESSION['user']->parent_id;
        if ($is_main_user == 0) {
            $cases = $this->caseObj->allCaseWithFirmId();

        } else {
            $cases = $this->caseObj->getCasesByUserIds();
        }

        $select = "<select name='" . $name . "' class=\"form-control select2\" id='" . $name . "' style='width:100%'>";
        $select .= "<option value='0'>Kasa Seçiniz</option>";
        foreach ($cases as $case) {
            $selectedAttr = $case_id == $case->id ? 'selected' : '';
            $select .= "<option value=\"" . Security::encrypt($case->id) . "\" {$selectedAttr}>{$case->case_name}-{$case->bank_name}/{$case->branch_name}</option>";
        }
        $select .= '</select>';
        return $select;
    }

    //Hareketin type bilgisini döndürür
    public static function getTransactionType($type_id)
    {
        if (!isset(self::TYPE[$type_id])) {
            return "";
        }
        return self::TYPE[$type_id];
    }

    //Defines tablosuından id'ye göre name değeri döndürür
    public function getTransactionTypeById($id)
    {
        if (empty($id)) return null;
        $query = $this->db->prepare("SELECT * FROM defines WHERE id = ?");
        $query->execute([$id]);
        $result = $query->fetch(PDO::FETCH_OBJ);
        
        if (!$result) {
            // Fallback for core types not in defines table
            $name = self::getTransactionType($id);
            if ($name) {
                return (object)[
                    'id' => $id,
                    'name' => $name,
                    'type_id' => ($id == 1 || $id == 14 || $id == 16) ? 1 : 2 // 1: Income, 2: Expense
                ];
            }
            return null;
        }
        return $result;
    }

    //Gelen id'ye göre defines tablosundan icon_code ve icon_color değerini döndürür
    // return "<i class='ti $icon icon $color me-1'></i>";
    public function getTransactionIcon($id)
    {
        if (empty($id)) return "";
        $query = $this->db->prepare("SELECT * FROM defines WHERE id = ?");
        $query->execute([$id]);
        $result = $query->fetch(PDO::FETCH_OBJ);
        
        if (!$result || empty($result->icon_code)) {
            // Default icons for core types
            if ($id == 14) return "<i class='ti ti-calendar-event icon text-azure me-1'></i>";
            if ($id == 16) return "<i class='ti ti-cash icon text-green me-1'></i>";
            if ($id == 7) return "<i class='ti ti-user-check icon text-orange me-1'></i>";
            return "<i class='ti ti-help icon text-muted me-1'></i>";
        }
        return "<i class='ti $result->icon_code icon $result->icon_color me-1'></i>";    
    }

    //defines tablosundan id'ye gore sorgula
    public function getUsersTransactionType($id)
    {
        $query = $this->db->prepare("SELECT * FROM defines WHERE id = ?");
        $query->execute([$id]);
        return $query->fetch(PDO::FETCH_OBJ)->name;
    }

    //gelen case_id değerini kontrol etmek için
    public static function caseControl($case_id)
    {
        if (!isset($case_id) || $case_id == 0) {
            $res = [
                "status" => "error",
                "message" => "Kasa seçimi yapınız"
            ];
            echo json_encode($res);
            exit();

        }
    }

    //Gelen tutarın kontrolü
    public static function amountControl($amount)
    {
        if (!isset($amount) || $amount == 0 || $amount == '') {
            $res = [
                "status" => "error",
                "message" => "Geçerli bir tutar giriniz!"
            ];
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode($res);
            exit();

        }
    }
    //Gelen işlem türünün kontrolü
    public static function typeControl($type)
    {
        if (!isset($type) || $type == 0 || $type == '') {
            $res = [
                "status" => "error",
                "message" => "İşlem Türünü seçiniz!"
            ];
            ob_clean();
            header('Content-Type: application/json');
            echo json_encode($res);
            exit();

        }
    }



}
