<?php
require_once '../../Model/Bordro.php';
require_once '../../Database/require.php';
require_once '../../App/Helper/date.php';
require_once '../../Model/Auths.php';


$firm_id = $_SESSION['firm_id'];

require ROOT . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Helper\Date;
use App\Helper\Security;

$Auths = new Auths();


//Giriş yapan kullanıcı ile kullanıcının firmasını kontrol et
$Auths->checkFirmReturn();

//Kullanıcının yetkisini kontrol et
$Auths->hasPermissionReturn('upload_payment_permission');


$bordro = new Bordro();

if ($_POST["action"] == "payment-load-from-xls") {
    $month = $_POST["months"];
    $year = $_POST["year"];
    $project_id = $_POST["projects"];
    $type = $_POST["inc_exp_type"];
    $file = $_FILES["payment-load-file"];
    $file_name = $file["name"];
    $file_tmp = $file["tmp_name"];
    $file_size = $file["size"];
    $file_error = $file["error"];
    $file_ext = explode(".", $file_name);
    $file_ext = strtolower(end($file_ext));
    $allowed = ["xls", "xlsx"];

    if (in_array($file_ext, $allowed)) {
        try {
            //excel dosyasını okuma
            $spreadsheet = IOFactory::load($file_tmp);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $data = [];
            foreach ($sheetData as $key => $row) {
                if ($key == 1) {
                    continue;
                }
                $data = [
                    "id" => 0,
                    "person_id" => $row["A"],
                    "gun" => Date::Ymd($row["C"]),
                    "tutar" => $row["D"],
                    "ay" => $month,
                    "yil" => $year,
                    "kategori" => 7,
                    "turu" => $type,
                    "aciklama" => "Excel yükleme",
                ];
                $lastInsertedId = $bordro->saveWithAttr($data) ?? 0;
            }

            $status = "success";
            $message = "Dosya başarıyla yüklendi";
        } catch (PDOException $ex) {
            $status = "error";
            $message = $ex->getMessage();
        }

    } else {
        $status = "error";
        $message = "Dosya uzantısı uygun değil";
    }

    $res = [
        "status" => $status,
        "message" => $message,
        //"data" => $data,
    ];

    echo json_encode($res);
}