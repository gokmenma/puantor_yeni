<?php
define('ROOT', dirname(__DIR__, 2));
require_once ROOT . "/Database/require.php";
require_once ROOT . "/Model/NationalHolidaysModel.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/Model/ActivityLogModel.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Security;

header('Content-Type: application/json');

try {
    $Auths = new Auths();
    $Auths->checkFirmReturn();

    $NationalHolidays = new NationalHolidaysModel();

    if ($_POST['action'] == "saveNationalHoliday") {
        $Auths->hasPermissionReturn("national_holidays_add_update");

        $id = isset($_POST['id']) ? $_POST['id'] : 0;
        $decrypted_id = !empty($id) ? Security::decrypt($id) : 0;
        if ($decrypted_id === false) {
            $decrypted_id = 0;
        }

        // Tarih formatını d.m.Y formatından Y-m-d formatına çevirelim
        $raw_date = $_POST['holiday_date'] ?? '';
        $date_formatted = '';
        if (!empty($raw_date)) {
            $parts = explode('.', $raw_date);
            if (count($parts) == 3) {
                $date_formatted = $parts[2] . '-' . $parts[1] . '-' . $parts[0];
            } else {
                $date_formatted = $raw_date;
            }
        }

        $data = [
            'id' => $decrypted_id,
            'holiday_name' => $_POST['holiday_name'] ?? '',
            'holiday_date' => $date_formatted,
            'holiday_type' => in_array($_POST['holiday_type'] ?? '', ['national', 'religious', 'other'], true)
                ? $_POST['holiday_type']
                : 'national',
            'day_ratio' => in_array((string) ($_POST['day_ratio'] ?? '1.00'), ['1', '1.00', '0.5', '0.50'], true)
                ? (float) $_POST['day_ratio']
                : 1.00,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'description' => $_POST['description'] ?? ''
        ];

        $lastInsertId = $NationalHolidays->saveWithAttr($data) ?? $decrypted_id;
        $message = $decrypted_id == 0 ? "Resmi tatil başarıyla eklendi" : "Resmi tatil başarıyla güncellendi";

        ActivityLogModel::log(
            'national_holiday', 
            $decrypted_id == 0 ? 'create' : 'update', 
            "Resmi tatil kaydedildi: " . ($_POST['holiday_name'] ?? '') . " Tarih: " . ($raw_date)
        );

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'id' => is_numeric($lastInsertId) ? Security::encrypt($lastInsertId) : $lastInsertId
        ]);
        exit;
    }

    if ($_POST['action'] == "deleteNationalHoliday") {
        $Auths->hasPermissionReturn("national_holidays_add_update");

        $id = $_POST['id'];
        $decrypted_id = Security::decrypt($id);

        $holidayDetails = $NationalHolidays->find($decrypted_id);
        $holidayName = $holidayDetails ? $holidayDetails->holiday_name : '';
        $holidayDate = $holidayDetails ? date('d.m.Y', strtotime($holidayDetails->holiday_date)) : '';

        $NationalHolidays->delete($id);

        ActivityLogModel::log(
            'national_holiday', 
            'delete', 
            "Resmi tatil silindi: " . $holidayName . " Tarih: " . $holidayDate
        );

        echo json_encode([
            'status' => 'success',
            'message' => "Resmi tatil başarıyla silindi"
        ]);
        exit;
    }

    echo json_encode(['status' => 'error', 'message' => 'Geçersiz işlem']);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
} catch (Error $e) {
    echo json_encode(['status' => 'error', 'message' => 'Sistem hatası: ' . $e->getMessage()]);
}
