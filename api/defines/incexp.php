<?php
define('ROOT', dirname(__DIR__, 2));
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/DefinesModel.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/Model/ActivityLogModel.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Security;

header('Content-Type: application/json');

try {
    $Auths = new Auths();
    $Auths->checkFirmReturn();
    $incexp = new DefinesModel();

    if ($action === "saveIncExpType") {
        $Auths->hasPermissionReturn("income_expense_type_add_update");
        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $decrypted_id = !empty($id) ? Security::decrypt($id) : 0;
        if ($decrypted_id === false || !is_numeric($decrypted_id)) {
            $decrypted_id = is_numeric($id) ? (int)$id : 0;
        }

        $name = trim($_POST["incexp_name"] ?? '');
        if (empty($name)) {
            echo json_encode(["status" => "error", "message" => "Gelir/Gider adı boş bırakılamaz."]);
            exit;
        }

        $firm_id = (int)($_SESSION["firm_id"] ?? 0);
        $user_id = (int)($_SESSION["user"]->id ?? 0);
        $type_id = (int)($_POST["incexp_type"] ?? 1);
        $description = trim($_POST["description"] ?? '');

        // Mevcut kayıt güncelleniyorsa, sistem tanımı veya başka firmanın kaydı olmamalı
        if ($decrypted_id > 0) {
            $existing = $incexp->find($decrypted_id);
            if (!$existing) {
                echo json_encode(["status" => "error", "message" => "Güncellenecek kayıt bulunamadı."]);
                exit;
            }
            if ($existing->firm_id == 0 || $existing->firm_id != $firm_id) {
                echo json_encode(["status" => "error", "message" => "Sistem tanımları veya başka firmaya ait tanımlar düzenlenemez."]);
                exit;
            }
        }

        $data = [
            "id" => $decrypted_id,
            "name" => $name,
            "user_id" => $user_id,
            "firm_id" => $firm_id,
            "type_id" => $type_id,
            "description" => $description
        ];

        $lastInsertId = $incexp->saveWithAttr($data) ?? $decrypted_id;
        $message = $decrypted_id > 0 ? "Gelir/Gider türü başarıyla güncellendi." : "Gelir/Gider türü başarıyla eklendi.";

        ActivityLogModel::log(
            'defines_incexp',
            $decrypted_id > 0 ? 'update' : 'create',
            "Gelir/Gider türü kaydedildi: " . $name
        );

        echo json_encode([
            "status" => "success",
            "message" => $message,
            "id" => is_numeric($lastInsertId) ? Security::encrypt($lastInsertId) : $lastInsertId
        ]);
        exit;
    }

    if ($action === "deleteIncExpType") {
        $Auths->hasPermissionReturn("income_expense_type_add_update");
        $id = $_POST["id"] ?? 0;
        $decrypted_id = Security::decrypt($id);
        if ($decrypted_id === false || !is_numeric($decrypted_id)) {
            $decrypted_id = is_numeric($id) ? (int)$id : 0;
        }

        $firm_id = (int)($_SESSION["firm_id"] ?? 0);
        $existing = $incexp->find($decrypted_id);

        if (!$existing) {
            echo json_encode(["status" => "error", "message" => "Silinecek kayıt bulunamadı."]);
            exit;
        }

        if ($existing->firm_id == 0 || $existing->firm_id != $firm_id) {
            echo json_encode(["status" => "error", "message" => "Sistem tanımları veya başka firmaya ait tanımlar silinemez."]);
            exit;
        }

        $incexp->delete($decrypted_id);

        ActivityLogModel::log(
            'defines_incexp',
            'delete',
            "Gelir/Gider türü silindi: " . ($existing->name ?? '')
        );

        echo json_encode([
            "status" => "success",
            "message" => "Gelir/Gider tanımı başarıyla silindi."
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);

} catch (Exception $e) {
    if (function_exists('system_log_exception')) {
        system_log_exception($e, ['operation' => 'defines_incexp_api']);
    } else {
        error_log($e->getMessage());
    }
    echo json_encode(['status' => 'error', 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
} catch (Error $e) {
    if (function_exists('system_log_exception')) {
        system_log_exception($e, ['operation' => 'defines_incexp_api']);
    } else {
        error_log($e->getMessage());
    }
    echo json_encode(['status' => 'error', 'message' => 'Sistem hatası meydana geldi.']);
}