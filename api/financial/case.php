<?php


require_once "../../Database/require.php";
require_once "../../Model/Cases.php";
require_once "../../Model/CaseTransactions.php";
require_once "../../App/Helper/security.php";
require_once "../../Model/Auths.php";

use App\Helper\Security;

$Auths = new Auths();

$Auths->checkFirmReturn();

$caseObj = new Cases();
$CaseTransactions = new CaseTransactions();

if ($_POST["action"] == "saveCase") {

    //
    $Auths->hasPermission("cash_register_add_update");

    $id = $_POST["id"] != 0 ? Security::decrypt($_POST["id"]) : 0;
    $lastInsertedId = 0;


    //eğer varsayılan kasa yapılacaksa diğer kasaların varsayılanlığını kaldır
    if (isset($_POST["default_case"]) && $_POST["default_case"] == 'on') {
        $caseObj->removeDefaultCase();
        $_POST["default_case"] = 1;
    } else {
        //eğer id 0 ve firmanın hiç kasası yoksa varsayılan kasa yap
        if ($id == 0 && $caseObj->countCaseByFirm() == 0) {
            $_POST["default_case"] = 1;
        } else {
            $_POST["default_case"] = 0;
        }
    }

    //Kasanın yetkili olan kullanıcıları 
    $users = isset($_POST["user_ids"]) ? $_POST["user_ids"] : [];
    $user_ids = "";
    if (count($users) > 0) {
        foreach ($users as $user) {
            $user_ids .= $user . ",";
        }
        $user_ids = rtrim($user_ids, ",");
    }


    $data = [
        "id" => $id,
        "case_name" => Security::escape($_POST["case_name"]),
        "account_id" => Security::escape($_SESSION["user"]->id),
        "firm_id" => Security::escape($_SESSION["firm_id"]),
        "bank_name" => Security::escape($_POST["bank_name"]),
        "user_ids" => $user_ids,
        "branch_name" => Security::escape($_POST["branch_name"]),
        "case_money_unit" => Security::escape($_POST["case_money_unit"]),
        "description" => Security::escape($_POST["description"]),
        "isDefault" => Security::escape($_POST["default_case"]),

    ];


    try {
        $lastInsertId = ($caseObj->saveWithAttr($data)) ?? $id;
        $status = "success";
        if ($id == 0) {
            $message = "Kasa başarıyla kaydedildi.";
        } else {
            $message = "Kasa başarıyla güncellendi.";
        }
    } catch (PDOException $ex) {
        $status = "error";
        $message = "Kasa kaydedilirken bir hata oluştu." . $ex->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message,
        "lastid" => $lastInsertId
    ];
    echo json_encode($res);
}

if ($_POST["action"] == "deleteCase") {
    $id = $_POST["id"];

    //Kasa silme yetkisi var mı kontrol et
    $Auths->hasPermission("cash_delete");

    //Eğer kasa varsayılan ise silinemez
    $caseObj->checkDefaultCase(Security::decrypt($id));

    try {
        $db->beginTransaction();
        $caseObj->delete($id);
        $CaseTransactions->deleteCaseTransactions($id);

        $status = "success";
        $message = "Kasa başarıyla silindi.";
        $db->commit();
    } catch (PDOException $ex) {
        $db->rollBack();
        $status = "error";
        $message = "Kasa silinirken bir hata oluştu.";
    }
    $res = [
        "status" => $status,
        "message" => $message,
    ];
    echo json_encode($res);
}

if ($_POST["action"] == "defaultCase") {
    $id = Security::decrypt($_POST["case_id"]);

    $caseObj->setDefaultCase($id);
    $res = [
        "status" => "success",
        "message" => "Varsayılan kasa başarıyla ayarlandı."
    ];
    echo json_encode($res);
}

if ($_POST["action"] == "getCases") {


    //Kasalararası virman yetkisi var mı kontrol et
    $Auths->hasPermissionReturn("intercash_transfer");
    try {
        $id = Security::decrypt($_POST["case_id"]);
        $cases = $caseObj->getCasesExceptId($id);
        $status = "success";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message ?? "",
        "cases" => $cases
    ];

    echo json_encode($res);
}


//
if ($_POST["action"] == "intercashTransfer") {
    //it = intercashTransfer
    //Kasalararası virman yetkisi var mı kontrol et
    $Auths->hasPermission("intercash_transfer");


    $from_case_id = Security::decrypt($_POST["it_from_cases"]);
    $date = $_POST["it_date"];
    $to_case_id = $_POST["it_to_case"];
    $amount = Security::escape($_POST["it_amount"]);
    $description = Security::escape($_POST["it_description"]);

    try {
        $db->beginTransaction();
        $res = $CaseTransactions->transfer(
                                    $from_case_id,
                                    $to_case_id,
                                    $amount,
                                    $description,
                                    $date
        );
        $status = $res["status"];
        $message = $res["message"];
        $db->commit();
    } catch (PDOException $ex) {
        $db->rollBack();
        $status = "error";
        $message = "Kasalar arası transfer sırasında bir hata oluştu." . $ex->getMessage();
    }

    $res = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($res);
}
