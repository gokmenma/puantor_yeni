<?php


require_once "BaseModel.php";
require_once ROOT ."/App/Helper/helper.php";

use App\Helper\Helper;

class Auths extends Model
{
    protected $table = "auths";

    // Request lifetime cache variables
    private static $cachedAuthsByName = [];
    private static $cachedAuthsById = [];
    private static $userAuthIds = null;
    private static $authsLoaded = false;
    private static $packageAllowedAuthIds = null;
    private static $packageRestrictionLoaded = false;

    public function __construct()
    {
        parent::__construct($this->table);
    }

    private function loadAuths()
    {
        if (!self::$authsLoaded) {
            $sql = $this->db->prepare("SELECT id, auth_name, superadmin, parent_id FROM $this->table WHERE is_active = 1");
            $sql->execute();
            $rows = $sql->fetchAll(PDO::FETCH_OBJ);
            foreach ($rows as $row) {
                if ($row->auth_name !== null && $row->auth_name !== '') {
                    self::$cachedAuthsByName[$row->auth_name] = $row;
                }
                self::$cachedAuthsById[$row->id] = $row;
            }
            self::$authsLoaded = true;
        }
    }

    private function loadUserAuthIds()
    {
        if (self::$userAuthIds === null) {
            $user_roles_raw = $_SESSION['user']->user_roles ?? '';
            $role_ids = array_values(array_filter(array_map('intval', explode(',', $user_roles_raw))));

            if (empty($role_ids)) {
                self::$userAuthIds = [];
                return;
            }

            $placeholders = implode(',', array_fill(0, count($role_ids), '?'));
            $sql = $this->db->prepare("SELECT auth_ids FROM role_auths WHERE role_id IN ($placeholders)");
            $sql->execute($role_ids);
            $rows = $sql->fetchAll(PDO::FETCH_OBJ);

            $allAuthIds = [];
            foreach ($rows as $row) {
                if (!empty($row->auth_ids)) {
                    foreach (array_filter(explode(',', $row->auth_ids)) as $aid) {
                        $allAuthIds[$aid] = true;
                    }
                }
            }
            self::$userAuthIds = array_keys($allAuthIds);

            $packageAllowedAuthIds = $this->loadPackageModuleRestriction();
            if ($packageAllowedAuthIds !== null) {
                self::$userAuthIds = array_values(array_intersect(self::$userAuthIds, $packageAllowedAuthIds));
            }
        }
    }

    // Abonenin paketi modül kısıtlıyorsa izin verilen auth id'lerini (alt yetkiler dahil) döner, kısıtlama yoksa null döner
    private function loadPackageModuleRestriction()
    {
        if (self::$packageRestrictionLoaded) {
            return self::$packageAllowedAuthIds;
        }
        self::$packageRestrictionLoaded = true;

        $currentUser = $_SESSION['user'] ?? null;
        if (!$currentUser) {
            return self::$packageAllowedAuthIds;
        }

        $owner_id = $currentUser->id;
        if (!empty($currentUser->parent_id) && $currentUser->parent_id != $currentUser->id) {
            $owner_id = $currentUser->parent_id;
        }

        $sql = $this->db->prepare("
            SELECT ap.modul_auth_ids
            FROM kullanici_abonelikleri ka
            JOIN abonelik_paketleri ap ON ap.id = ka.paket_id
            WHERE ka.kullanici_id = ? AND ka.durum = 'aktif' AND ka.bitis_tarihi >= CURDATE()
            ORDER BY ka.id DESC LIMIT 1
        ");
        $sql->execute([$owner_id]);
        $modul_auth_ids = $sql->fetchColumn();

        if (empty($modul_auth_ids)) {
            return self::$packageAllowedAuthIds;
        }

        $this->loadAuths();

        $allowed = array_fill_keys(array_filter(array_map('intval', explode(',', $modul_auth_ids))), true);
        do {
            $changed = false;
            foreach (self::$cachedAuthsById as $id => $auth) {
                if (isset($allowed[$auth->parent_id]) && !isset($allowed[$id])) {
                    $allowed[$id] = true;
                    $changed = true;
                }
            }
        } while ($changed);

        self::$packageAllowedAuthIds = array_keys($allowed);
        return self::$packageAllowedAuthIds;
    }


    public function auths()
    {
        $superadmin = isset($_SESSION['user']->superadmin) ? $_SESSION['user']->superadmin : 0;
        if ($superadmin == 1) {
            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE parent_id = ? and is_active = 1 ORDER BY title ASC");
        } else {
            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE parent_id = ? and is_active = 1 and (superadmin = 0 or superadmin IS NULL) ORDER BY title ASC");
        }
        $sql->execute([0]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    //alt yetkiler getirilir
    public function subAuths($id)
    {
        $superadmin = isset($_SESSION['user']->superadmin) ? $_SESSION['user']->superadmin : 0;
        if ($superadmin == 1) {
            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE parent_id = ? and is_active = 1 ORDER BY title ASC");
        } else {
            $sql = $this->db->prepare("SELECT * FROM $this->table WHERE parent_id = ? and is_active = 1 and (superadmin = 0 or superadmin IS NULL) ORDER BY title ASC");
        }
        $sql->execute([$id]);
        return $sql->fetchAll(PDO::FETCH_OBJ);
    }

    // Süperadmin yetkilerinin id'lerini getirir
    public function getSuperadminAuthIds()
    {
        $sql = $this->db->prepare("SELECT id FROM $this->table WHERE superadmin = 1");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_COLUMN);
    }

    // Süperadmin olmayan yetkilerin id'lerini getirir
    public function getNonSuperadminAuthIds()
    {
        $sql = $this->db->prepare("SELECT id FROM $this->table WHERE superadmin = 0 OR superadmin IS NULL");
        $sql->execute();
        return $sql->fetchAll(PDO::FETCH_COLUMN);
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
        $isSuperadminUser = isset($_SESSION['user']->superadmin) && $_SESSION['user']->superadmin == 1;

        if ($isSuperadminUser) {
            return true;
        }

        $this->loadAuths();
        $auth = self::$cachedAuthsByName[$auth_name] ?? null;

        if (!$auth) {
            return false;
        }

        // Yetki superadmin yetkisi ise ve giriş yapan kullanıcı superadmin değilse izin verme
        if (isset($auth->superadmin) && $auth->superadmin == 1) {
            return false;
        }

        $this->loadUserAuthIds();
        return in_array($auth->id, self::$userAuthIds);
    }


    public function AuthorizeByAuthId($auth_id)
    {
        $isSuperadminUser = isset($_SESSION['user']->superadmin) && $_SESSION['user']->superadmin == 1;

        if ($isSuperadminUser) {
            return true;
        }

        $this->loadAuths();
        $auth = self::$cachedAuthsById[$auth_id] ?? null;

        if (!$auth) {
            return 0;
        }

        // Yetki superadmin yetkisi ise ve giriş yapan kullanıcı superadmin değilse izin verme
        if (isset($auth->superadmin) && $auth->superadmin == 1) {
            return 0;
        }

        $this->loadUserAuthIds();
        return in_array($auth_id, self::$userAuthIds) ? true : 0;
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
        if (isset($_SESSION['user']->is_main_user) && $_SESSION['user']->is_main_user == 1) {
            return true;
        }

        if ($_SESSION['user']->firm_id != $_SESSION['firm_id']) {
            $res = [
                "status" => "error",
                "message" => "Yetkiniz yok"
            ];
            ob_clean();
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
            ob_clean();
            echo json_encode($res);
            exit;
        }
        return true;
    }
}
