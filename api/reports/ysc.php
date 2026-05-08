<?php
require_once "../../Database/db.php";
require_once "../../Model/Report.php";
require_once "../../Model/ReportContent.php";

require_once "../../App/Helper/date.php";

use App\Helper\Date;


use Database\Db;

$dbInstance = new Db(); // Db sınıfının bir örneğini oluşturuyoruz.
$db = $dbInstance->connect(); // Veritabanı bağlantısını alıyoruz.

$report = new Reports();

if ($_POST["action"] == "save_ysc_report") {
    $id = $_POST["id"];
    $lastid = 0;
    $data = [
        "id" => $id,
        "report_number" => $_POST["report_number"],
        "isemrino" => $_POST["job_order_no"], // "isemrino
        "customer_id" => $_POST["customers"],
        "controller_id" => $_POST["controller_id"],
        "company_official" => $_POST["company_official"],
        "control_date" => Date::Ymd($_POST["control_date"]),
        "validity_date" => Date::Ymd($_POST["validity_date"]),
        "control_period" => $_POST["control_period"],
        "standarts" => $_POST["standarts"],
        "equipments" => $_POST["equipment"],
        "warnings" => $_POST["warnings"],
        "notes" => $_POST["notes"],
        "subNotes" => $_POST["subNotes"],


    ];
    try {
        $db->beginTransaction();
        //Yeni kayıt olduğunda geriye eklenen id numarasının döndürür
        $lastInsertedId = $report->saveReports($data) ?? $id;

        $reportContent = new ReportContent();

        // if ($id > 0) {
        //     $reportContent->deleteByReportId($id);
        // }

        $urun_sayisi = count($_POST['cihaz_no']);
        if (isset($_POST['cihaz_no'])) {
            for ($i = 0; $i < $urun_sayisi; $i++) {
                $data = [
                    "id" => $_POST["urun_id"][$i],
                    "report_id" => $lastInsertedId,
                    "cihaz_no" => $_POST['cihaz_no'][$i],
                    "bulundugu_bolge" => $_POST['cihazbolge'][$i],
                    "cinsi" => $_POST['cinsi'][$i],
                    // "cihaz_dolum_tarihi" => $_POST['cihaz_dolum_tarihi'][$i],
                    // "cihaz_sonkullanma_tarihi" => $_POST['cihaz_sonkullanma_tarihi'][$i],
                    // "kontrol_tarihi_1" => $_POST['kontrol_tarihi_1'][$i],
                ];
                $reportContent->saveReportsContent($data);
            }
        }
        $db->commit();

        if ($id == 0) {
            $status = "success";
            $message = "Rapor başarıyla eklendi.";
        } else {
            $status = "success";
            $message = "Rapor başarıyla güncellendi.";
        }
    } catch (PDOException $ex) {
        $status = "error";
        $message = "Rapor kaydedilirken bir hata oluştu. - " . $ex->getMessage();
    } catch (Exception $ex) {
        $status = "error";
        $message =  $ex->getMessage();
    }


    $res = [
        "status" => $status,
        "message" => $message,
        "lastid" => $lastInsertedId
    ];
    echo json_encode($res);
}


if ($_POST["action"] == "delete_ysc_product") {
    $id = $_POST["id"];
    $reportContent = new ReportContent();
    $reportContent->delete($id);
    $res = [
        "status" => "success",
        "message" => "Ürün başarıyla silindi."
    ];
    echo json_encode($res);
}
