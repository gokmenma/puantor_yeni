<?php


require_once 'Model/Persons.php';
require_once 'Model/Cases.php';
require_once 'Model/Projects.php';
require_once 'App/Helper/projects.php';

use App\Helper\Security;
$Cases = new Cases();
$Projects = new Projects();
$projectHelper = new ProjectHelper();


// Yetki kontrolü yapılır
$perm->checkAuthorize("personnel_add_update");

$id = isset($_GET["id"]) ? Security::decrypt($_GET['id']) : 0;
$new_id = isset($_GET["id"]) ? $_GET['id'] : 0;


//Eğer manuel id yazılmışsa personel sayfasına gönder
if ($id == null && isset($_GET["id"])) {
    header('Location: index.php?p=persons/list');
    exit();
}



$personObj = new Persons();
$person = $personObj->find($id);

$pageTitle = $id > 0 ? 'Personel Güncelle' : 'Yeni Personel';

//Varsayılan kasayı getir
$case_id = $Cases->getDefaultCaseIdByFirm();

//personelin kayıtlı olduğu projeleri getir
$personProjects = $Projects->getPersonProjects($id);


//Personelin kayıtlı olduğu projeleri virgül ile birleştir
$personProjectsIds = '';
foreach ($personProjects as $key => $value) {
    $personProjectsIds .= $value->project_id . ',';
}
//Sonundaki virgülü sil
$personProjectsIds = rtrim($personProjectsIds, ',');

// echo "<pre>";
// print_r($personProjects);
// echo "</pre>";

?>
<style>
    .person-image:hover {
        cursor: pointer;
    }

    .person-image:hover+.person-image-hover {
        display: block;
    }

    .person-image-hover {
        display: none;
        position: absolute;
        z-index: 1;
        width: 200px;
        height: 200px;
        border: 1px solid #ccc;
        background-color: #fff;
        border-radius: 6px;
        box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.1);
    }
</style>

<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="col-12">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">

                            <div class="col-auto">
                                <?php
                                $noback_shape = '';
                                $wage_type = $person->wage_type ?? 1;
                                if ($wage_type == 1) {
                                    $noback_shape = '-noback-shape';
                                }
                                ;
                                ?>
                                <span class="avatar"
                                    style="background-image: url('./static/hard-hat<?php echo $noback_shape; ?>.svg')">
                                </span>
                            </div>
                            <div class="col-auto">
                                <div class="avatar person-image">
                                    <!-- <img src="../../uploads/<?php echo $myfirm->brand_logo ?? '' ?>" alt=""> -->
                                </div>
                                <span class="person-image-hover mt-1">

                                    <!-- <img src="../../uploads/<?php echo $myfirm->brand_logo ?? '' ?>" alt=""> -->
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-700">
                                    <?php echo $pageTitle; ?>
                                </div>
                                <div class="text-secondary full-name">
                                    <?php echo $person->full_name ?? ''; ?>
                                </div>
                            </div>
                            <div class="col-auto d-flex">
                                <!-- Page title actions -->
                                <!-- <div class="col-auto ms-auto d-print-none me-2">
                                    <a href="#" class="btn btn-teal route-link" data-page="persons/manage">
                                        <i class="ti ti-plus icon me-2"></i> Yeni
                                    </a>
                                </div> -->
                                <div class="col-auto ms-auto d-print-none me-2">
                                    <button type="button" class="btn btn-outline-secondary route-link"
                                        data-page="persons/list">
                                        <i class="ti ti-list icon me-2"></i>
                                        Listeye Dön
                                    </button>
                                </div>
                                <!-- <div class="col-auto ms-auto d-print-none">
                                    <button type="button" class="btn btn-primary" id="savePerson">
                                        <i class="ti ti-device-floppy icon me-2"></i>
                                        Kaydet
                                    </button>
                                </div> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="col-md-12">

                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-home-3" class="nav-link active" data-bs-toggle="tab"
                                        aria-selected="true" role="tab">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/home -->
                                        <i class="ti ti-home icon me-1"></i>
                                        Genel Bilgiler
                                    </a>
                                </li>
                                <?php if ($id > 0): ?>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-payment-3" class="nav-link" data-bs-toggle="tab"
                                        aria-selected="false" tabindex="-1" role="tab">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/user -->
                                        <i class="ti ti-calculator icon me-1"></i>
                                        Gelir-Gider Bilgileri
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-puantaj-3" class="nav-link" data-bs-toggle="tab"
                                        aria-selected="false" tabindex="-1" role="tab">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/user -->
                                        <i class="ti ti-calendar icon me-1"></i>
                                        Puantaj Bilgileri
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-leave-3" class="nav-link" data-bs-toggle="tab" aria-selected="false"
                                        tabindex="-1" role="tab">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/user -->
                                        <i class="ti ti-caravan icon me-1"></i>
                                        İzin Bilgileri
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-documents-3" class="nav-link" data-bs-toggle="tab"
                                        aria-selected="false" tabindex="-1" role="tab">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/user -->
                                        <i class="ti ti-checklist icon me-1"></i>
                                        Belgeler
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-wages-3" class="nav-link" data-bs-toggle="tab" aria-selected="false"
                                        tabindex="-1" role="tab">
                                        <!-- Download SVG icon from http://tabler-icons.io/i/user -->
                                        <i class="ti ti-wallet icon me-1"></i>
                                        Ücret Tanımları
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tabs-home-3" role="tabpanel">
                                    <?php include_once 'content/0-home.php' ?>
                                </div>
                                <?php if ($id > 0): ?>
                                <div class="tab-pane" id="tabs-payment-3" role="tabpanel">
                                    <?php include_once 'content/1-payment-info.php' ?>
                                </div>
                                <div class="tab-pane" id="tabs-puantaj-3" role="tabpanel">
                                    <?php include_once 'content/2-puantaj-info.php' ?>
                                </div>
                                <div class="tab-pane" id="tabs-leave-3" role="tabpanel">

                                    <?php include_once 'content/3-leave-info.php' ?>
                                </div>
                                <div class="tab-pane" id="tabs-documents-3" role="tabpanel">
                                    <?php include_once 'content/4-documents.php' ?>
                                </div>
                                <div class="tab-pane" id="tabs-wages-3" role="tabpanel">
                                    <?php include_once 'content/5-wage-defines.php' ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>