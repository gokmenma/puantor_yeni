<?php
require_once 'App/Helper/date.php';
require_once "App/Helper/company.php";
use App\Helper\Date;

$CompanyHelper = new CompanyHelper();
?>

<div class="modal modal-blur fade" id="pay_to_company-modal" tabindex="-1" aria-hidden="true"
    style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Firma Ödemesi Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="payToCompanyForm">

                    <div class="row d-flex">
                        <div class="col-md-5 vertical-center justify-content-center">

                            <div class="text-center">
                                <img src="static/png/wait.png" alt="Image" class="img-fluid mt-2"
                                    style="width:100%">
                            </div>
                        </div>
                        <div class="col-md-7">

                            <div class="row mb-3 mt-5">

                                <div class="col">
                                    <label class="form-label">Firma Adı</label>
                                     <!-- tc: to company -->
                                    <?php echo $CompanyHelper->getCompanySelect(name: "tc_company_name") ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Ödeme Tutarı</label>
                                    <input type="text" name="tc_amount" class="form-control money">

                                </div>
                                <div class="col">
                                    <label class="form-label">Ödeme Tarihi</label>
                                    <input type="text" name="tc_action_date" class="form-control flatpickr"
                                        value="<?php echo date("d.m.Y") ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Ödemenin Aktarılacağı Kasa</label>
                                    <?php echo $financialHelper->getCasesSelectByUser("tc_cases",$case_id); ?>
                                    
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" name="tc_description" style="min-height:100px"
                                    placeholder="Açıklama giriniz!"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Çık</button>
                <button type="button" class="btn btn-primary" id="savePayToCompany">Kaydet</button>
            </div>
        </div>
    </div>
</div>