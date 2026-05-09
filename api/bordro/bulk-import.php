<?php
require_once '../../Model/Bordro.php';
require_once '../../Database/require.php';
require_once '../../App/Helper/date.php';
require_once '../../App/Helper/helper.php';
require_once '../../Model/Auths.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$Auths = new Auths();

// Giriş yapan kullanıcı ile kullanıcının firmasını kontrol et
$Auths->checkFirmReturn();

// Yetki kontrolü
$Auths->hasPermissionReturn('income_expense_add_update');

$autoload_path = ROOT . '/vendor/autoload.php';
if (!file_exists($autoload_path)) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Sunucuda gerekli kütüphaneler (vendor/autoload.php) bulunamadı. Lütfen sunucuda 'composer install' komutunu çalıştırın."
    ]);
    exit;
}
require $autoload_path;

use PhpOffice\PhpSpreadsheet\IOFactory;

$bordro = new Bordro();

$action = $_POST['action'] ?? '';
$type = $_POST['type'] ?? 'income'; // 'income' or 'wage_cut'
$month = $_POST['month'] ?? date('m');
$year = $_POST['year'] ?? date('Y');

if ($action == 'bulk-import') {
    $file = $_FILES['file'] ?? null;
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Lütfen geçerli bir dosya yükleyin."]);
        exit;
    }

    $file_name = $file['name'];
    $file_tmp = $file['tmp_name'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    $allowed = ["xls", "xlsx"];

    if (!in_array($file_ext, $allowed)) {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Sadece Excel (.xls, .xlsx) dosyaları yüklenebilir."]);
        exit;
    }

    try {
        $spreadsheet = IOFactory::load($file_tmp);
        $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        
        $dateString = sprintf('%04d%02d15', $year, $month);
        $kategori = ($type == 'income') ? 1 : 15; // 1 for Gelir, 15 for Kesinti
        
        $success_count = 0;
        foreach ($sheetData as $key => $row) {
            if ($key == 1) {
                continue; // Header row
            }
            
            $person_id = intval($row['A']);
            $turu = trim($row['C'] ?? '');
            $tutar_raw = trim($row['D'] ?? '0');
            $aciklama = trim($row['E'] ?? '');

            $tutar = Helper::formattedMoneyToNumber($tutar_raw);

            if ($person_id > 0 && $tutar > 0) {
                if (empty($turu)) {
                    $turu = ($type == 'income') ? 'Gelir' : 'Kesinti';
                }
                if (empty($aciklama)) {
                    $aciklama = 'Toplu Excel yükleme';
                }

                $data = [
                    'id' => 0,
                    'user_id' => $_SESSION['user']->id,
                    'person_id' => $person_id,
                    'gun' => (int) $dateString,
                    'ay' => $month,
                    'yil' => $year,
                    'kategori' => $kategori,
                    'turu' => $turu,
                    'tutar' => $tutar,
                    'aciklama' => $aciklama,
                ];

                $bordro->saveWithAttr($data);
                $success_count++;
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            "status" => "success",
            "message" => "{$success_count} adet kayıt başarıyla yüklendi."
        ]);
    } catch (Exception $ex) {
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "message" => "Excel işlenirken hata oluştu: " . $ex->getMessage()]);
    }
    exit;
}
