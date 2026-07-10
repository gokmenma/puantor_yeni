<?php

use App\Helper\Security;

require_once "Model/Cases.php";
require_once "App/Helper/company.php";


$company = new CompanyHelper();
$caseObj = new Cases();


//Sayfa başlarında eklenecek alanlar
$perm->checkAuthorize("cash_register_list");
$id = isset($_GET["id"]) ? Security::decrypt($_GET['id']) : 0;
$new_id = isset($_GET["id"]) ? $_GET['id'] : 0;

//Eğer url'den id yazılmışsa veya id boş ise projeler sayfasına gider
if($id == null && isset($_GET['id'])) {
    header("Location: /index.php?p=financial/case/list");
    exit;
}

$case = $caseObj->find($id);
$pageTitle = $case ? "Kasa Hareketleri: " . htmlspecialchars($case->case_name, ENT_QUOTES, 'UTF-8') : "Kasa Hareketleri";

?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <?php echo $pageTitle; ?>
                    </h2>
                </div>

                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="financial/case/list">
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
                    <div class="card-body">
                        <?php include_once 'content/1-transactions.php' ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>