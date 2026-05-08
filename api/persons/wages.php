<?php
require_once "../../Database/require.php";
require_once "../../Model/Wages.php";
require_once "../../App/Helper/helper.php";
require_once "../../App/Helper/date.php";
require_once '../../Model/Bordro.php';

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$wages = new Wages();
$bordro = new Bordro();

if ($_POST["action"] == "saveWage") {
    $id =$_POST["wage_id"] != 0 ? Security::decrypt($_POST["wage_id"]) : 0;
    $person_id = $_POST["wage_person_id"];
   

    $data = [
        "id" => $id,
        "wage_name" => $_POST['wage_name'],
        "start_date" => Date::Ymd($_POST['wage_start_date'],"Ymd"),
        "end_date" => Date::Ymd($_POST['wage_end_date'],"Ymd"),
        "amount" => Helper::formattedMoneyToNumber($_POST['wage_amount']),
        "description" => $_POST['wage_description'],
        "person_id" =>  $person_id,
    ];



    try {
        $lastInsertId = $wages->saveWithAttr($data)  ?? $id;
      

        $last_wage = $wages->find( Security::decrypt($lastInsertId) ) ;
        $last_wage->id =$lastInsertId;
        $last_wage->amount = Helper::formattedMoney($last_wage->amount);
        $last_wage->start_date = Date::Ymd($last_wage->start_date,"d.m.Y");
        $last_wage->end_date = Date::Ymd($last_wage->end_date,"d.m.Y");

        $status = "success";
        if ($id == 0) {
            $message = "Ücret başarıyla kaydedildi.";
         
        } else {
            $message = "Ücret başarıyla güncellendi.";

        }

    } catch (PDOException $e) {
        $status = "error";
        $message = $e->getMessage();
    }
    $res = [
        "status" => $status,
        "message" => $message,
        "data" => $last_wage,
        
    ];


    echo json_encode($res);
}

if ($_POST["action"] == "deleteWage") {
    $id = $_POST["id"];
    try {
        $wages->delete($id);
        $status = "success";
        $message = "Ücret tanımı başarıyla silindi." ;
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

if($_POST["action"] == "getWage"){
    $id = Security::decrypt($_POST["id"]);
    $wage = $wages->find($id);
    $wage->amount = trim(str_replace("TRY","",Helper::formattedMoney($wage->amount)));
    $wage->start_date = Date::dmY($wage->start_date,"d.m.Y");
    $wage->end_date = Date::dmY($wage->end_date,"d.m.Y");
    $res = [
        "status" => "success",
        "data" => $wage
    ];
    echo json_encode($res);
}