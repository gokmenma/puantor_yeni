<?php
require_once "Model/Company.php";
require_once "App/Helper/security.php";
require_once "Model/UserModel.php";

use App\Helper\Security;

$companyObj = new Company();
$UserModel = new UserModel();

$perm->checkAuthorize("my_companies_page");
$id = isset($_GET["id"]) ? Security::decrypt($_GET['id']) : 0;
$new_id = isset($_GET["id"]) ? $_GET['id'] : 0;

if($id == null && isset($_GET['id'])) {
    header("Location: /index.php?p=mycompany/list");
    exit;
}

$myfirm = $companyObj->findMyFirm($id);
if (!$myfirm) {
    header("Location: /index.php?p=mycompany/list");
    exit;
}

$pageTitle = "FİRMA DETAYLARI";
?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="text-muted small text-uppercase fw-semibold" style="letter-spacing: 0.5px; font-size: 0.75rem;">FİRMA DETAYLARI</div>
                    <h2 class="page-title fw-bold text-dark mt-1">
                        <?php echo htmlspecialchars($myfirm->firm_name ?? 'Firma Detay'); ?>
                    </h2>
                </div>
                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="mycompany/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#tabs-home-3" class="nav-link active" data-bs-toggle="tab" aria-selected="true" role="tab">
                                    <i class="ti ti-chart-dots icon me-1"></i>
                                    Firma Özet Bilgileri
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tabs-profile-3" class="nav-link" data-bs-toggle="tab" aria-selected="false" tabindex="-1" role="tab">
                                    <i class="ti ti-receipt-tax icon me-1"></i>
                                    Kasa & İşlem Bilgileri
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active show" id="tabs-home-3" role="tabpanel">
                                <?php include_once "content/0-home.php"; ?>
                            </div>
                            <div class="tab-pane" id="tabs-profile-3" role="tabpanel">
                                <?php include_once "content/1-tax-info.php"; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>