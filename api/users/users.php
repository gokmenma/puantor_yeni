<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']);
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/Model/RolesModel.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/App/Helper/date.php";


use App\Helper\Date;
use App\Helper\Security;


$User = new UserModel();
$Roles = new Roles();

header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Oturum süreniz dolmuş.']);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Geçersiz istek.']);
    exit;
}
$sessionCsrf = (string) ($_SESSION['csrf_token'] ?? '');
$requestCsrf = (string) ($_POST['csrf_token'] ?? '');
if ($sessionCsrf === '' || $requestCsrf === '' || !hash_equals($sessionCsrf, $requestCsrf)) {
    http_response_code(419);
    echo json_encode(['status' => 'error', 'message' => 'Güvenlik doğrulaması başarısız.']);
    exit;
}
(new Auths())->hasPermissionReturn('user_add_update');

if ($_POST["action"] == "userSave") {
    $id = Security::safeDecrypt($_POST["id"]);
    if ($id > 0 && (int) ($_SESSION['user']->superadmin ?? 0) !== 1) {
        $targetUser = $User->find($id);
        if (!$targetUser
            || (int) $targetUser->firm_id !== (int) ($_SESSION['firm_id'] ?? 0)
            || (int) ($targetUser->superadmin ?? 0) === 1) {
            echo json_encode(['status' => 'error', 'message' => 'Bu kullanıcı üzerinde işlem yetkiniz yok.']);
            exit;
        }
    }
    //Eğer kayıt yapan kullanıcı ana kullanıcı ise kend id'si, değilse parent_id'si alınır.
    $parent_id = $_SESSION["user"]->parent_id == 0 ? $_SESSION["user"]->id : $_SESSION["user"]->parent_id;
    // If it's a new user registration, check the alt-user limit
    if ($id == 0) {
        $subDetails = $User->getActiveSubscriptionDetails($parent_id);
        $currentSubUsers = $User->getSubUserCount($parent_id);
        if (($_SESSION["user"]->superadmin ?? 0) != 1 && $currentSubUsers >= $subDetails['alt_kullanici_hakki']) {
            $res = [
                "status" => "error",
                "message" => "Paketinizin alt kullanıcı limiti (" . $subDetails['alt_kullanici_hakki'] . ") dolmuştur. Yeni kullanıcı ekleyemezsiniz."
            ];
            echo json_encode($res);
            exit;
        }
    }

    $lastInsertId = 0;

    try {
        //Email adresi ile kayıtlı ana kullanıcı varsa kayıt yapılmaz
        $user = $User->getUserByEmail($_POST["email"]);
        
        if ($user && $user->parent_id == 0 && (int)$user->id !== (int)$id) {
            $status = "error";
            $message = "Bu e-posta adresi ile zaten kayıtlı.";
            $res = [
                "status" => $status,
                "message" => $message,
            ];
            echo json_encode($res);
            exit;
        }

        //Kullanıcı adı ile kayıtlı başka bir kullanıcı varsa kayıt yapılmaz
        $username = trim($_POST["username"] ?? '');
        if (!empty($username)) {
            if ($User->isUsernameExists($username, $id)) {
                $status = "error";
                $message = "Bu kullanıcı adı zaten başka bir kullanıcı tarafından kullanılıyor.";
                $res = [
                    "status" => $status,
                    "message" => $message,
                ];
                echo json_encode($res);
                exit;
            }
        }

        $responsible_projects = isset($_POST["responsible_projects"]) ? implode(',', $_POST["responsible_projects"]) : '';
        $responsible_persons = isset($_POST["responsible_map"]) ? json_encode($_POST["responsible_map"], JSON_UNESCAPED_UNICODE) : '';
        $responsible_modules = '';

        $data = [
            "id" => $id,
            "user_type" => $_SESSION["user"]->user_type,
            "parent_id" => $parent_id,
            "firm_id" => $_SESSION["firm_id"],
            "full_name" => $_POST["full_name"],
            "email" => $_POST["email"],
            "username" => $username,
            "user_roles" => implode(',', array_filter(array_map('intval', (array)($_POST["user_roles"] ?? [])))),
            "phone" => $_POST["phone"],
            "job" => $_POST["job"],
            "responsible_projects" => $responsible_projects,
            "responsible_persons" => $responsible_persons,
            "responsible_modules" => $responsible_modules,
            "status" => 1,
        ];

        if (!empty($_POST['password'])) {
            $data["password"] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }
  

        $lastInsertId = $User->saveWithAttr($data) ?? $id;
        $status = "success";
        if ($id == 0) {
            $message = "Kullanıcı başarıyla kaydedildi.";
        } else {
            $message = "Kullanıcı başarıyla güncellendi.";
        }
    } catch (PDOException $e) {
        $status = "error";
        if ($e->errorInfo[1] == 1062) {
            $message = 'Bu e-posta adresi zaten kayıtlı.';
        } else {
            $message = $e->getMessage();
        }
    }
    $res = [
        "status" => $status,
        "message" => $message,
        "lastid" => $lastInsertId
    ];
    echo json_encode($res);
}


if ($_POST["action"] == "deleteUser") {
    $id = $_POST["id"];
    try {
        $targetUser = $User->find(Security::safeDecrypt($id));
        if (!$targetUser
            || (int) ($targetUser->superadmin ?? 0) === 1
            || ((int) ($_SESSION['user']->superadmin ?? 0) !== 1
                && (int) $targetUser->firm_id !== (int) ($_SESSION['firm_id'] ?? 0))) {
            throw new Exception('Bu kullanıcıyı silme yetkiniz yok.');
        }
        $User->delete($id);
        $status = "success";
        $message = "Kullanıcı başarıyla silindi.";
    } catch (PDOException $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
}


if ($_POST["action"] == "isThereUserRoleGroup") {
    $firm_id = $_POST["firm_id"];
    $roles = $Roles->countRolesByFirm();
    $res = [
        "status" => "success",
        "roles" => $roles->total
    ];
    echo json_encode($res);
}
