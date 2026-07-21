<?php
define('ROOT', $_SERVER['DOCUMENT_ROOT']);
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/App/Helper/helper.php";


use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;


$User = new UserModel();
$Settings = new SettingsModel();

if ($_POST["action"] == "userSave") {
    $id = $_SESSION["user"]->id;
    $lastInsertId = 0;

    try {
        $currentUser = $User->find($id);
        if (!$currentUser) {
            throw new Exception("Kullanıcı bulunamadı.");
        }

        $data = [
            "id" => $id
        ];

        if (isset($_POST["full_name"])) {
            $data["full_name"] = trim($_POST["full_name"]);
        }

        if (isset($_POST["username"])) {
            $username = trim($_POST["username"]);
            if (!empty($username)) {
                if ($User->isUsernameExists($username, $id)) {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Bu kullanıcı adı zaten kullanılmaktadır. Lütfen başka bir kullanıcı adı seçin."
                    ]);
                    exit();
                }
                $data["username"] = $username;
            } else {
                $data["username"] = null;
            }
        }

        if (isset($_POST["phone"])) {
            $data["phone"] = trim($_POST["phone"]);
        }

        if (isset($_POST["job"])) {
            $data["job"] = trim($_POST["job"]);
        }

        if (isset($_POST["user_roles"])) {
            $data["user_roles"] = $_POST["user_roles"];
        }

        if (isset($_POST["password"]) && !empty($_POST["password"])) {
            $data["password"] = password_hash($_POST['password'], PASSWORD_DEFAULT);
        }

        $lastInsertId = $User->saveWithAttr($data) ?? $id;
        
        // Güncellenmiş kullanıcı bilgisini oturuma kaydet
        $_SESSION["user"] = $User->find($id);
        
        $status = "success";
        $message = "Profil Bilgileriniz başarıyla güncellendi.";
    } catch (Exception $e) {
        $status = "error";
        if ($e instanceof PDOException && $e->errorInfo[1] == 1062) {
            $message = 'Bu e-posta veya kullanıcı adı zaten kayıtlı.';
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
    exit();
}

//Kullanıcı girişinde mail göndermek için
if ($_POST["action"] == "send_email_on_login") {
    //Eğer birden fazla kayıt varsa son kayıt üzerinden işlem yapılır,diğerleri silinir
    $record = $Settings->getSettingIdByUserAndActionAll($_SESSION["user"]->id, "loginde_mail_gonder");
    if (count($record) > 1) {
        foreach ($record as $key => $value) {
            if ($key != 0) {
                $Settings->deleteByUserAndAction($value->user_id, $value->set_name);
            }
        }
    }

    //Kayıt yoksa yeni kayıt oluşturulur
    $id = $Settings->getSettingIdByUserAndAction($_SESSION["user"]->id, "loginde_mail_gonder")->id ?? 0;

    $input_val = isset($_POST["send_email_on_login"]) ? 1 : 0;
    $data = [
        "id" => $id,
        "firm_id" => $_SESSION["firm_id"],
        "user_id" => $_SESSION["user"]->id,
        "set_name" => "loginde_mail_gonder",
        "set_value" => $input_val
    ];
    try {
        $lastInsertId = $Settings->saveWithAttr($data) ?? $id;
        $status = "success";
        $message = "Ayarlar başarıyla tamamlandı.";
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

//Genel ayarlar
if ($_POST["action"] == "homeSettings") {

    $work_hour = $_POST["work_hour"];
    $overtime_rate = isset($_POST["overtime_rate"]) ? floatval($_POST["overtime_rate"]) : 50;
    if ($overtime_rate < 50) {
        $res = [
            "status" => "error",
            "message" => "Fazla mesai oranı en az %50 olmalıdır."
        ];
        echo json_encode($res);
        exit();
    }
    $show_white_collar = isset($_POST["show_white_collar_in_puantaj"]) ? 1 : 0;
    $yillik_izin_dusmeyecek_gunler = $_POST["yillik_izin_dusmeyecek_gunler"] ?? "6,7";

    try {
        $Settings->upsertSetting("work_hour", $work_hour);
        $Settings->upsertSetting("overtime_rate", $overtime_rate);
        $Settings->upsertSetting("show_white_collar_in_puantaj", $show_white_collar);
        $Settings->upsertSetting("yillik_izin_dusmeyecek_gunler", $yillik_izin_dusmeyecek_gunler);
        
        require_once ROOT . "/Model/ActivityLogModel.php";
        ActivityLogModel::log("program_settings", "update_general", "Program genel ayarları güncellendi.");
        
        $status = "success";
        $message = "Ayarlar başarıyla tamamlandı.";
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

//Genel ayarlar
if ($_POST["action"] == "financialSettings") {

    $sub_limit = $_POST["sub_limit"];
    $personnel_advance_request_visible = isset($_POST["personnel_advance_request_visible"]) ? 1 : 0;

    try {
        $Settings->upsertSetting("cases_sub_limit", Helper::formattedMoneyToNumber($sub_limit));
        $Settings->upsertSetting("personnel_advance_request_visible", $personnel_advance_request_visible);
        $status = "success";
        $message = "Ayarlar başarıyla tamamlandı.";
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

// Hesap Silme
if ($_POST["action"] == "deleteAccount") {
    $id = $_SESSION["user"]->id;
    try {
        $User->softDelete(Security::encrypt($id));
        $status = "success";
        $message = "Hesabınız başarıyla silindi.";
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit();
}

// Sistem Genel Ayarları Kaydet
if ($_POST["action"] == "systemGeneralSave") {
    $is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;

    if (!$is_superadmin) {
        echo json_encode([
            "status" => "error",
            "message" => "Bu işlemi gerçekleştirmek için yetkiniz bulunmamaktadır."
        ]);
        exit();
    }

    try {
        $system_title = trim($_POST["system_title"] ?? '');
        $system_email = trim($_POST["system_email"] ?? '');
        $system_language = trim($_POST["system_language"] ?? 'tr');
        $maintenance_mode = isset($_POST["maintenance_mode"]) ? "1" : "0";
        $kvkk_consent = isset($_POST["kvkk_consent"]) ? "1" : "0";

        if (empty($system_title)) {
            throw new Exception("Sistem başlığı boş bırakılamaz.");
        }

        $Settings->upsertSystemSetting("system_title", $system_title);
        $Settings->upsertSystemSetting("system_email", $system_email);
        $Settings->upsertSystemSetting("system_language", $system_language);
        $Settings->upsertSystemSetting("maintenance_mode", $maintenance_mode);
        $Settings->upsertSystemSetting("kvkk_consent", $kvkk_consent);

        require_once ROOT . "/Model/ActivityLogModel.php";
        ActivityLogModel::log("system_settings", "update_general", "Sistem genel ayarları güncellendi.");

        $status = "success";
        $message = "Sistem genel ayarları başarıyla güncellendi.";
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
    }

    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit();
}

// SMTP Ayarlarını Kaydet
if ($_POST["action"] == "systemSmtpSave") {
    $is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;

    if (!$is_superadmin) {
        echo json_encode([
            "status" => "error",
            "message" => "Bu işlemi gerçekleştirmek için yetkiniz bulunmamaktadır."
        ]);
        exit();
    }

    try {
        $smtp_host = trim($_POST["smtp_host"] ?? '');
        $smtp_port = trim($_POST["smtp_port"] ?? '');
        $smtp_encryption = trim($_POST["smtp_encryption"] ?? 'ssl');
        $smtp_from_name = trim($_POST["smtp_from_name"] ?? '');

        if (empty($smtp_host) || empty($smtp_port)) {
            throw new Exception("Sunucu adresi ve port numarası alanları zorunludur.");
        }

        $Settings->upsertSystemSetting("smtp_host", $smtp_host);
        $Settings->upsertSystemSetting("smtp_port", $smtp_port);
        $Settings->upsertSystemSetting("smtp_encryption", $smtp_encryption);
        $Settings->upsertSystemSetting("smtp_from_name", $smtp_from_name);

        // Account 1: sifre@...
        $smtp_username = trim($_POST["smtp_username"] ?? '');
        $smtp_password = trim($_POST["smtp_password"] ?? '');
        $Settings->upsertSystemSetting("smtp_username", $smtp_username);
        if (!empty($smtp_password) && $smtp_password !== '********') {
            $Settings->upsertSystemSetting("smtp_password", $smtp_password);
        }

        // Account 2: bilgi@...
        $smtp_info_username = trim($_POST["smtp_info_username"] ?? '');
        $smtp_info_password = trim($_POST["smtp_info_password"] ?? '');
        $Settings->upsertSystemSetting("smtp_info_username", $smtp_info_username);
        if (!empty($smtp_info_password) && $smtp_info_password !== '********') {
            $Settings->upsertSystemSetting("smtp_info_password", $smtp_info_password);
        }

        // Account 3: destek@...
        $smtp_support_username = trim($_POST["smtp_support_username"] ?? '');
        $smtp_support_password = trim($_POST["smtp_support_password"] ?? '');
        $Settings->upsertSystemSetting("smtp_support_username", $smtp_support_username);
        if (!empty($smtp_support_password) && $smtp_support_password !== '********') {
            $Settings->upsertSystemSetting("smtp_support_password", $smtp_support_password);
        }

        require_once ROOT . "/Model/ActivityLogModel.php";
        ActivityLogModel::log("system_settings", "update_smtp", "SMTP e-posta hesapları ve ayarları güncellendi.");

        $status = "success";
        $message = "SMTP e-posta ayarları başarıyla güncellendi.";
    } catch (Exception $e) {
        $status = "error";
        $message = $e->getMessage();
    }

    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit();
}

// SMTP Bağlantı Testi (Real-time Test)
if ($_POST["action"] == "systemSmtpTest") {
    $is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;

    if (!$is_superadmin) {
        echo json_encode([
            "status" => "error",
            "message" => "Bu işlemi gerçekleştirmek için yetkiniz bulunmamaktadır."
        ]);
        exit();
    }

    try {
        $smtp_host = trim($_POST["smtp_host"] ?? '');
        $smtp_port = trim($_POST["smtp_port"] ?? '');
        $smtp_encryption = trim($_POST["smtp_encryption"] ?? 'ssl');
        $smtp_from_name = trim($_POST["smtp_from_name"] ?? '');
        $test_account = trim($_POST["test_account"] ?? 'default');
        $test_email = trim($_POST["test_email"] ?? '');

        if (empty($test_email)) {
            throw new Exception("Lütfen test e-postasının gönderileceği bir adres giriniz.");
        }

        // Determine which mailbox details to load
        if ($test_account === 'info') {
            $smtp_username = trim($_POST["smtp_info_username"] ?? '');
            $smtp_password = trim($_POST["smtp_info_password"] ?? '');
            if ($smtp_password === '********' || empty($smtp_password)) {
                $smtp_password = $Settings->getSystemSetting("smtp_info_password");
            }
        } elseif ($test_account === 'support') {
            $smtp_username = trim($_POST["smtp_support_username"] ?? '');
            $smtp_password = trim($_POST["smtp_support_password"] ?? '');
            if ($smtp_password === '********' || empty($smtp_password)) {
                $smtp_password = $Settings->getSystemSetting("smtp_support_password");
            }
        } else {
            // default/sifre
            $smtp_username = trim($_POST["smtp_username"] ?? '');
            $smtp_password = trim($_POST["smtp_password"] ?? '');
            if ($smtp_password === '********' || empty($smtp_password)) {
                $smtp_password = $Settings->getSystemSetting("smtp_password");
            }
        }

        if (empty($smtp_username)) {
            throw new Exception("Test edilecek hesabın e-posta adresi boş olamaz.");
        }

        require_once ROOT . '/vendor/autoload.php';
        $mailTest = new PHPMailer\PHPMailer\PHPMailer(true);

        $mailTest->isSMTP();
        $mailTest->Host = $smtp_host;
        $mailTest->SMTPAuth = !empty($smtp_password);
        $mailTest->Username = $smtp_username;
        $mailTest->Password = $smtp_password;

        if ($smtp_encryption === 'ssl') {
            $mailTest->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp_encryption === 'tls') {
            $mailTest->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mailTest->SMTPSecure = '';
            $mailTest->SMTPAuth = false;
        }

        $mailTest->Port = (int)$smtp_port;
        $mailTest->setFrom($smtp_username, $smtp_from_name);
        $mailTest->addAddress($test_email);
        $mailTest->CharSet = 'UTF-8';
        $mailTest->isHTML(true);
        
        $mailTest->Subject = 'Puantor Sistem Ayarları SMTP Test E-Postası';
        $mailTest->Body    = 'Merhaba,<br><br>Bu e-posta, Puantor platformundaki SMTP ayarlarınızın doğruluğunu ve bağlantının başarıyla kurulduğunu test etmek amacıyla gönderilmiştir.<br><br>Ayarlarınız çalışmaktadır.<br><br>İyi çalışmalar.';

        $mailTest->send();

        require_once ROOT . "/Model/ActivityLogModel.php";
        ActivityLogModel::log("system_settings", "test_smtp", "SMTP bağlantı testi gerçekleştirildi. (Test edilen hesap: {$smtp_username}, Alıcı: {$test_email})");

        $status = "success";
        $message = "Test e-postası başarıyla gönderildi. Lütfen gelen kutunuzu (ve spam klasörünü) kontrol ediniz.";
    } catch (Exception $e) {
        $status = "error";
        $message = "Bağlantı hatası: " . $e->getMessage();
    }

    echo json_encode([
        "status" => $status,
        "message" => $message
    ]);
    exit();
}