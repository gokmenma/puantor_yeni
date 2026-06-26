<?php

use App\Helper\Security;

require_once "Model/Company.php";
require_once "App/Helper/cities.php";

$cities = new Cities();

$companyObj = new Company();
$id = isset($_GET['id']) ? Security::decrypt($_GET["id"]) : 0;
$new_id = $id == 0 ? 0 : $_GET['id'];
$company = $companyObj->find($id);

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
                        <?php echo htmlspecialchars($company->company_name ?? 'Firma Detay'); ?>
                    </h2>
                </div>
                <div class="col-auto ms-auto d-print-none d-flex gap-2">
                    <?php if ($id > 0) { ?>
                    <button type="button" class="btn btn-primary d-none" id="btn-add-company-payment-top">
                        <i class="ti ti-plus icon me-2"></i> Ödeme Ekle
                    </button>
                    <?php } ?>
                    <a href="index.php?p=companies/list" class="btn btn-outline-secondary">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </a>
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
                        <ul class="nav nav-tabs card-header-tabs" id="company-tabs" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#tabs-home-3" class="nav-link active" data-bs-toggle="tab"
                                    data-tab-key="ozet" aria-selected="true" role="tab">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="icon me-2">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M5 12l-2 0l9 -9l9 9l-2 0"></path>
                                        <path d="M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-7"></path>
                                        <path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v6"></path>
                                    </svg>
                                    Firma Özet Bilgileri
                                </a>
                            </li>
                            <?php if ($id > 0) { ?>
                            <li class="nav-item" role="presentation">
                                <a href="#tabs-profile-3" class="nav-link" data-bs-toggle="tab"
                                    data-tab-key="odeme" aria-selected="false" tabindex="-1" role="tab">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                        fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" class="icon me-2">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                        <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                                        <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                                    </svg>
                                    Ödeme Bilgileri
                                </a>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active show" id="tabs-home-3" role="tabpanel">
                                <?php include_once "content/0-home.php" ?>
                            </div>
                            <?php if ($id > 0) { ?>
                            <div class="tab-pane" id="tabs-profile-3" role="tabpanel">
                                <?php include_once "content/1-odeme-bilgileri.php" ?>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var STORAGE_KEY = "company_manage_tab_<?php echo $id; ?>";

    function getActiveTabKey() {
        var active = $("#company-tabs .nav-link.active");
        return active.data("tab-key") || "ozet";
    }

    function activateTabByKey(key) {
        var tab = $("#company-tabs .nav-link[data-tab-key='" + key + "']");
        if (tab.length) {
            tab.tab("show");
        }
    }

    function togglePaymentBtn(key) {
        if (key === "odeme") {
            $("#btn-add-company-payment-top").removeClass("d-none");
        } else {
            $("#btn-add-company-payment-top").addClass("d-none");
        }
    }

    $(document).ready(function () {
        var savedKey = sessionStorage.getItem(STORAGE_KEY) || "ozet";
        activateTabByKey(savedKey);
        togglePaymentBtn(savedKey);
    });

    $(document).on("shown.bs.tab", "#company-tabs .nav-link", function () {
        var key = $(this).data("tab-key");
        sessionStorage.setItem(STORAGE_KEY, key);
        togglePaymentBtn(key);
    });

    $(document).on("click", "#btn-add-company-payment-top", function () {
        $("#btn-add-company-payment").trigger("click");
    });
})();
</script>
