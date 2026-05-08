<?php

use App\Helper\Security;

require_once "Model/Projects.php";
require_once "App/Helper/company.php";
require_once "App/Helper/cities.php";
require_once "Model/Cases.php";

$companyHelper = new CompanyHelper();
$cityHelper = new Cities();
$Cases = new Cases();

$perm->checkAuthorize("project_add_update");

$projectObj = new Projects();

//id decrypt edilir
$id = isset($_GET['id']) ? Security::decrypt($_GET['id']) : 0;

//yeni kayıt için id 0 olmalı
$new_id=isset($_GET["id"]) ? ($_GET['id']) : 0;

//Eğer url'den id yazılmışsa veya id boş ise projeler sayfasına gider
if($id == null && isset($_GET['id'])) {
    header("Location: /index.php?p=projects/list");
    exit;
}

//id'ye göre kayıt getirilir
$project = $projectObj->find($id);
$type = $project->type ?? 1;

$pageTitle = $id > 0 ? "Proje Detay/Güncelle" : "Yeni Proje";

$case_id = $Cases->getDefaultCaseIdByFirm();

?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
            <div class="col">
                        <!-- Page pre-title -->
                        <div class="page-pretitle">
                       <?php echo $pageTitle; ?>
                    </div>
                    <h2 class="page-title">
                        <?php echo $project->project_name ?? 'Yeni Proje'?>
                        </h2>
                    </div>

                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="projects/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="saveProject">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="col-md-12">
                <?php if ($companyHelper->countCompanies() == 0): ?>
                    <div class="alert alert-warning alert-dismissible" role="alert">
                        <div class="d-flex">
                            <div>
                                <i class="ti ti-alert-triangle icon alert-icon"></i>
                            </div>
                            <div>
                                <h4 class="alert-title">Uyarı!</h4>
                                <div class="text-secondary">Proje oluşturabilmek için öncelikle <b>Yüklenici firma</b> tanımlaması yapmalısınız.
                                    <br>
                                    <a href="index.php?p=companies/manage" class="btn btn-sm btn-link p-0 mt-2">
                                        <i class="ti ti-plus icon me-1"></i>Yeni Yüklenici Firma Ekle
                                    </a>
                                </div>
                            </div>
                        </div>
                        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
                    </div>
                <?php endif; ?>

                <form action="" id="projectForm">
                    <!-- HIDDEN ROW -->
                    <div class="row d-none ">
                        <div class="col-md-4">
                            <input type="text" name="id" id="id" class="form-control" value="<?php echo $new_id ?>">
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="action" value="saveProject" class="form-control">
                        </div>
                    </div>
                    <!-- HIDDEN ROW -->

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-home-3" class="nav-link active" data-bs-toggle="tab"
                                            aria-selected="true"
                                            role="tab">
                                            <i class="ti ti-home icon me-1"></i>
                                            Genel Bilgiler
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-profile-3" class="nav-link" data-bs-toggle="tab"
                                            aria-selected="false" tabindex="-1"
                                            role="tab">
                                            <i class="ti ti-clipboard-text icon me-1"></i>
                                            Diğer Bilgiler
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-payment-3" class="nav-link" data-bs-toggle="tab"
                                            aria-selected="false" tabindex="-1"
                                            role="tab">
                                            <i class="ti ti-cash-register icon me-1"></i>
                                            Hakediş/Ödeme Bilgileri
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-puantaj-3" class="nav-link" data-bs-toggle="tab"
                                            aria-selected="false" tabindex="-1"
                                            role="tab">
                                            <i class="ti ti-calendar-month icon me-1"></i>
                                            Çalışma/Puantaj Bilgileri
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-summary-3" class="nav-link" data-bs-toggle="tab"
                                            aria-selected="false" tabindex="-1"
                                            role="tab">
                                            <i class="ti ti-chart-dots icon me-1"></i>
                                           Proje Özet Bilgileri
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <div class="tab-pane active show" id="tabs-home-3" role="tabpanel">
                                        <?php include_once "content/0-home.php" ?>

                                    </div>
                                    <div class="tab-pane" id="tabs-profile-3" role="tabpanel">
                                        <?php include_once "content/1-other-info.php" ?>
                                    </div>
                                    <div class="tab-pane" id="tabs-payment-3" role="tabpanel">
                                        <?php include_once "content/2-payment-info.php" ?>

                                    </div>
                                    <div class="tab-pane" id="tabs-puantaj-3" role="tabpanel">
                                        <?php include_once "content/3-works-puantaj-info.php" ?>

                                    </div>
                                    <div class="tab-pane" id="tabs-summary-3" role="tabpanel">
                                        <?php include_once "content/4-project-summary.php" ?>

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<?php include_once 'modals/payment-modal.php' ?>
<?php include_once 'modals/progress-payment-modal.php' ?>
<?php include_once 'modals/deduction-modal.php' ?>