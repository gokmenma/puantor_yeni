<?php


require_once "BaseModel.php";
require_once ROOT ."/App/Helper/helper.php";

use App\Helper\Helper;

class Auths extends Model
{
    protected $table = "auths";
    public function __construct()
    {
        parent::__construct($this->table);
    }


    public function auths()
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE parent_id = ? and is_active = 1");
        $sql->execute([0]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //alt yetkiler getirilir
    public function subAuths($id)
    {
        $sql = $this->db->prepare("SELECT * FROM $this->table WHERE parent_id = ? and is_active = 1");
        $sql->execute([$id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //Yetki title'dan yetki id getirilir
    public function getAuthIdByTitle($auth_title)
    {
        $sql = $this->db->prepare("SELECT id FROM $this->table WHERE title = ?");
        $sql->execute([$auth_title]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }


    //Yetki adından yetki id getirilir
    public function getAuthIdByName($auth_name)
    {
        $sql = $this->db->prepare("SELECT id FROM $this->table WHERE auth_name = ?");
        $sql->execute([$auth_name]);
        return $sql->fetch(PDO::FETCH_OBJ);
    }

    // Bu yetki id'si role_auths tablosunda role_id ile sorgulanır
    public function Authorize($auth_name)
    {
        //Yetki adından yetki id getirilir
        $auth_id = $this->getAuthIdByName($auth_name)->id ?? 0;
        if (!$auth_id) {
            return false;
        }
        //Giriş yapan kullanıcının hangi role grubunda olduğu alınır
        $role_id = $_SESSION['user']->user_roles;


        //role_auts tablosunda role_id ile sorgulanır,auth_ids içinde var mı yok mu kontrol edilir varsa true döner değilse false döner
        $sql = $this->db->prepare("SELECT * FROM role_auths WHERE role_id = ? and FIND_IN_SET(?,auth_ids)");
        $sql->execute([$role_id, $auth_id]);
        $result = $sql->fetch(PDO::FETCH_OBJ);
        if (!$result) {
            return false;
        }
        return true;

    }


    public function AuthorizeByAuthId($auth_id)
    {
        //role_auts tablosunda role_id ile sorgulanır,auth_ids içinde var mı yok mu kontrol edilir varsa true döner değilse false döner
        $role_id = $_SESSION['user']->user_roles;
        $sql = $this->db->prepare("SELECT * FROM role_auths WHERE role_id = ? and FIND_IN_SET(?,auth_ids)");
        $sql->execute([$role_id, $auth_id]);
        $result = $sql->fetch(PDO::FETCH_OBJ);
        if (!$result) {
            return 0;
        }
        return true;

    }


    function checkPermissionSwal($auth_name)
    {

        if (!$this->hasPermission($auth_name)) {
            echo "
            <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
            <script>swal.fire('Hata','Yetkiniz yok','error')</script>";

        }
    }
    // Yetki kontrolü yapar ve sadece yetki olup olmadığını döner
    public function hasPermission($auth_name)
    {
        return $this->Authorize($auth_name);
    }

    // Yetki kontrolü yapar ve yetki yoksa authorize sayfasına yönlendirir
    function checkAuthorize($auth_name)
    {
        //user'in firm_id'si ve Session firm_id'si aynı mı kontrolü yapılır
        if ($_SESSION['user']->firm_id != $_SESSION['firm_id']) {
            header("Location: index.php?p=authorize");
            exit();
        }
        if (!$this->Authorize($auth_name)) {
            header("Location: index.php?p=authorize");
            exit();
        }
    }

    //Kullanıcının firm_id'si ve sessiondaki firmayı karşılaştır
    function checkFirm()
    {
        if ($_SESSION['user']->firm_id != $_SESSION['firm_id']) {
            header("Location: index.php?p=authorize");
            exit();
        }
        return true;
    }

    //Kullanıcının firm_id'si ve sessiondaki firmayı karşılaştır, eğer farklı ise false döner
    public function checkFirmReturn()
    {
        if ($_SESSION['user']->firm_id != $_SESSION['firm_id']) {
            $res = [
                "status" => "error",
                "message" => "Yetkiniz yok"
            ];
            echo json_encode($res);
            exit;
        }
        return true;
    }

    public function hasPermissionReturn($auth_name)
    {
        if (!$this->Authorize($auth_name)) {
            $res = [
                "status" => "error",
                "message" => "Bu işlemi yapma yetkiniz yok"
            ];
            echo json_encode($res);
            exit;
        }
        return true;
    }
}
