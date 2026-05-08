<?php

require_once '../Database/require.php';
require_once '../App/Helper/cities.php';




$cityObj = new Cities();

if ($_POST['action'] == 'getTowns') {
    $city_id = $_POST['city_id'];

    try {
        $towns = $cityObj->getCityTowns($city_id);
        $status = 'success';
        $message = '';
    } catch (PDOException $exh) {
        $status = 'error';
        $message = $exh->getMessage();
    }

    $res = [
        'status' => $status,
        'message' => $message,
        'towns' => $towns
    ];
    echo json_encode($res);
}
