<?php
define('ROOT', dirname(__DIR__, 2));
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/IcraDaireleriModel.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/Model/ActivityLogModel.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Security;

header('Content-Type: application/json');

try {
    $Auths = new Auths();
    $Auths->checkFirmReturn();

    $IcraDaireleri = new IcraDaireleriModel();

    if ($_POST['action'] == "saveIcraDairesi") {
        $Auths->hasPermissionReturn("icra_daireleri_add_update");

        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $decrypted_id = !empty($id) ? Security::decrypt($id) : 0;
        if ($decrypted_id === false) {
            $decrypted_id = 0;
        }

        $iban = strtoupper(str_replace(' ', '', $_POST['iban'] ?? ''));

        $data = [
            'id' => $decrypted_id,
            'admin_id' => $_SESSION['user']->id ?? 0,
            'daire_adi' => $_POST['daire_adi'] ?? '',
            'sehir' => $_POST['sehir'] ?? '',
            'iban' => $iban !== '' ? $iban : null,
            'durum' => $_POST['durum'] ?? 'Aktif'
        ];

        $lastInsertId = $IcraDaireleri->saveWithAttr($data) ?? $decrypted_id;
        $message = $decrypted_id == 0 ? "İcra dairesi başarıyla eklendi" : "İcra dairesi başarıyla güncellendi";

        ActivityLogModel::log(
            'icra_dairesi',
            $decrypted_id == 0 ? 'create' : 'update',
            "İcra dairesi kaydedildi: " . ($_POST['daire_adi'] ?? '')
        );

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'id' => is_numeric($lastInsertId) ? Security::encrypt($lastInsertId) : $lastInsertId
        ]);
        exit;
    }

    if ($_POST['action'] == "deleteIcraDairesi") {
        $Auths->hasPermissionReturn("icra_daireleri_add_update");

        $id = $_POST['id'];
        $decrypted_id = Security::decrypt($id);

        $daireDetails = $IcraDaireleri->find($decrypted_id);
        $daireAdi = $daireDetails ? $daireDetails->daire_adi : '';

        $IcraDaireleri->delete($id);

        ActivityLogModel::log(
            'icra_dairesi',
            'delete',
            "İcra dairesi silindi: " . $daireAdi
        );

        echo json_encode([
            'status' => 'success',
            'message' => "İcra dairesi başarıyla silindi"
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);

} catch (Exception $e) {
    system_log_exception($e, ['operation' => 'icra_daireleri_api']);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Error $e) {
    system_log_exception($e, ['operation' => 'icra_daireleri_api']);
    echo json_encode(['status' => 'error', 'message' => 'Sistem hatası']);
}
