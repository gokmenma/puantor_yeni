<?php
define('ROOT', dirname(__DIR__, 2));
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/PuantajTuruModel.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/Model/ActivityLogModel.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Security;

header('Content-Type: application/json');

try {
    $Auths = new Auths();
    $Auths->checkFirmReturn();

    $PuantajTuru = new PuantajTuruModel();

    if ($_POST['action'] == "saveTimesheetType") {
        $Auths->hasPermissionReturn("timesheet_types_add_update");

        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $decrypted_id = !empty($id) ? Security::decrypt($id) : 0;
        if ($decrypted_id === false) {
            $decrypted_id = 0;
        }

        $data = [
            'id' => $decrypted_id,
            'PuantajAdi' => $_POST['puantaj_name'] ?? '',
            'PuantajKod' => $_POST['puantaj_code'] ?? '',
            'PuantajSaati' => ($_POST['puantaj_hours'] !== '') ? floatval($_POST['puantaj_hours']) : null,
            'operant' => !empty($_POST['operant']) ? $_POST['operant'] : null,
            'EklenecekSaat' => ($_POST['added_hours'] !== '') ? floatval($_POST['added_hours']) : null,
            'Turu' => $_POST['type'] ?? '',
            'FontRengi' => $_POST['font_color'] ?? '#000000',
            'ArkaPlanRengi' => $_POST['background_color'] ?? '#ffffff',
            'isActive' => isset($_POST['isActive']) ? 1 : 0,
            'IzinRapor' => isset($_POST['IzinRapor']) ? 1 : 0,
            'is_deductable' => isset($_POST['is_deductable']) ? 1 : 0,
            'beyaz_yaka_kesinti' => isset($_POST['beyaz_yaka_kesinti']) ? 1 : 0,
            'personel_gorsun' => isset($_POST['personel_gorsun']) ? 1 : 0,
            'counts_as_work' => isset($_POST['counts_as_work']) ? 1 : 0
        ];

        $lastInsertId = $PuantajTuru->saveWithAttr($data) ?? $decrypted_id;
        $message = $decrypted_id == 0 ? "Puantaj türü başarıyla eklendi" : "Puantaj türü başarıyla güncellendi";

        ActivityLogModel::log(
            'puantaj_turu', 
            $decrypted_id == 0 ? 'create' : 'update', 
            "Puantaj türü kaydedildi: " . ($_POST['puantaj_name'] ?? '') . " (Kod: " . ($_POST['puantaj_code'] ?? '') . ")"
        );

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'id' => is_numeric($lastInsertId) ? Security::encrypt($lastInsertId) : $lastInsertId
        ]);
        exit;
    }

    if ($_POST['action'] == "deleteTimesheetType") {
        $Auths->hasPermissionReturn("timesheet_types_add_update");

        $id = $_POST['id'];
        $decrypted_id = Security::decrypt($id);

        $typeDetails = $PuantajTuru->find($decrypted_id);
        $typeName = $typeDetails ? $typeDetails->PuantajAdi : '';
        $typeCode = $typeDetails ? $typeDetails->PuantajKod : '';

        $PuantajTuru->delete($id);

        ActivityLogModel::log(
            'puantaj_turu', 
            'delete', 
            "Puantaj türü silindi: " . $typeName . " (Kod: " . $typeCode . ")"
        );

        echo json_encode([
            'status' => 'success',
            'message' => "Puantaj türü başarıyla silindi"
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['status' => 'error', 'message' => 'Sistem hatası: ' . $e->getMessage()]);
}
