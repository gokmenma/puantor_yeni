<?php
require_once 'App/Helper/helper.php';
require_once "App/Helper/person.php";
require_once "App/Helper/company.php";


$personHelper = new PersonHelper();
$CompanyHelper = new CompanyHelper();

use App\Helper\Helper;

?>
<div class="modal modal-blur fade" id="general-modal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Hareket Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="transactionModalForm">
                    <input type="hidden" class="form-control" id="transaction_id" name="transaction_id" value="0">


                    <div class="card">
                        <div class="card-header">
                            <ul class="nav nav-tabs card-header-tabs nav-fill" data-bs-toggle="tabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-home-7" class="nav-link active" data-bs-toggle="tab"
                                        aria-selected="true"
                                        role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                                        <i class="ti ti-buildings icon me-3"></i>
                                        Proje</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-profile-7" class="nav-link" data-bs-toggle="tab"
                                        aria-selected="false" tabindex="-1"
                                        role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/user -->
                                        <i class="ti ti-users icon me-3"></i>
                                        Personel</a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-activity-7" class="nav-link" data-bs-toggle="tab"
                                        aria-selected="false" tabindex="-1"
                                        role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/activity -->
                                        <i class="ti ti-home-stats icon me-3"></i>
                                        Firma</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tabs-home-7" role="tabpanel">
                                    <div class="mb-3 w-100">
                                        <label class="form-label">Proje Seçiniz(Zorunlu Değil)</label>
                                        <?php echo $projectHelper->getProjectSelect("gm_project_id") ?>
                                    </div>
                                </div>
                                <div class="tab-pane" id="tabs-profile-7" role="tabpanel">
                                    <div class="mb-3 w-100">
                                        <label class="form-label">Personel Adı</label>
                                        <!-- Tüm Personeli getir -->
                                        <?php echo $personHelper->getPersonSelect(name: "gm_person_name") ?>
                                    </div>
                                </div>
                                <div class="tab-pane" id="tabs-activity-7" role="tabpanel">
                                    <div class="mb-3 w-100">
                                        <label class="form-label">Firma Adı</label>
                                        <!-- tc: to company -->
                                        <?php echo $CompanyHelper->getCompanySelect(name: "gm_company") ?>
                                    </div>
                                </div>
                            </div>



                            <label class="form-label">Türü</label>
                            <div class="form-selectgroup-boxes row mb-3">
                                <div class="col-lg-6">
                                    <label class="form-selectgroup-item">
                                        <input type="radio" name="transaction_type" value="1"
                                            class="form-selectgroup-input transaction_type " checked="">
                                        <span class="form-selectgroup-label d-flex align-items-center p-3">
                                            <span class="me-3">
                                                <span class="form-selectgroup-check"></span>
                                            </span>
                                            <span class="form-selectgroup-label-content">
                                                <span class="form-selectgroup-title strong mb-1">Gelir</span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-selectgroup-item">
                                        <input type="radio" name="transaction_type" value="2"
                                            class="form-selectgroup-input transaction_type">
                                        <span class="form-selectgroup-label d-flex align-items-center p-3">
                                            <span class="me-3">
                                                <span class="form-selectgroup-check"></span>
                                            </span>
                                            <span class="form-selectgroup-label-content">
                                                <span class="form-selectgroup-title strong mb-1">Gider</span>
                                            </span>
                                        </span>
                                    </label>
                                </div>
                            </div>


                            <div class="mb-3 w-100">
                                <label class="form-label">Kasa<font style="color:red">(*)</font></label>
                                <?php echo $financial->getCasesSelectByUser("gm_case_id", $case_id) ?>
                            </div>




                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label class="form-label">Tutar<font style="color:red">(*)</font></label>
                                        <input type="text" name="amount" id="amount" class="form-control money" value=""
                                            autocomplete="off">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Tutar</label>
                                        <?php echo Helper::moneySelect("gm_amount_money", ''); ?>
                                    </div>
                                </div>


                            </div>
                            <div class="row mb-3">

                                <div class="col-md-6">

                                    <label class="form-label">İşlem Tarihi</label>
                                    <input type="text" name="transaction_date" class="form-control flatpickr"
                                        value="<?php echo date('d.m.Y'); ?>">

                                </div>
                                <div class="col-md-6">

                                    <label class="form-label">Gelir/Gider Türü</label>
                                    <?php echo $financial->getIncExpTypeSelect("gm_incexp_type"); ?>
                                </div>

                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label class="form-label">Açıklama</label>
                                        <div class="input-group input-group-flat">

                                            <textarea class="form-control" name="description" id="gm_description"
                                                style="min-height: 100px;" placeholder="Açıklama giriniz"></textarea>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </form>
            </div>


            <div class="modal-footer">
                <a href="#" class="btn btn-link link-secondary" data-bs-dismiss="modal">
                    Vazgeç
                </a>
                <a href="#" class="btn btn-primary ms-auto" id="saveTransaction">
                    <!-- Download SVG icon from http://tabler-icons.io/i/plus -->
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="icon">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                        <path d="M12 5l0 14"></path>
                        <path d="M5 12l14 0"></path>
                    </svg>
                    Kaydet
                </a>
            </div>
        </div>
    </div>
</div>