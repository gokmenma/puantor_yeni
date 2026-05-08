<?php

define("ROOT", $_SERVER['DOCUMENT_ROOT']);
require_once ROOT."/Database/require.php";
require_once ROOT. "/Model/PackageModel.php";

use App\Helper\Security;
$Packages = new PackageModel();

if($_GET['action'] == 'getPackage'){
    $id= Security::decrypt($_GET['id']);
     $package = $Packages->find($id);

    $res =[
        'status' => 'success',
        'message' => 'Paketler başarıyla getirildi',
        'data' => $package
    ];
    echo json_encode($res);
}