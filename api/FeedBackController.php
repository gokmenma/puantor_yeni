<?php
require_once ROOT . "/Database/require.php";
require_once ROOT. "/Model/FeedBackModel.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Security;
$FeedBackModel = new FeedBackModel();


if ($_POST["action"] == "saveFeedBack") {
    $id = $_POST['id'] ?? 0;

    $data = [
        "id" => $id,
        "user_id" => $_SESSION['user']->id,
        "firm_id" => $_SESSION['firm_id'],
        "subject" => Security::escape($_POST['subject']),
        "message" => Security::escape($_POST['message']),
        "status" => 1
    ];

    try {
        $FeedBackModel->saveWithAttr($data);
        $status = "success";
        $message = "Görüş ve öneriniz başarıyla kaydedildi.";
    } catch (PDOException $ex) {
        $status = "error";
        $message = $ex->getMessage();
    }
    $response = [
        "status" => $status,
        "message" => $message
    ];
    echo json_encode($response);
}