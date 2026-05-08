<?php
require_once 'App/Helper/date.php';
require_once "App/Helper/projects.php";
require_once "Model/Cases.php";

use App\Helper\Date;
$projectHelper = new ProjectHelper();

$Cases = new Cases();
$case_id = $Cases->getDefaultCaseIdByFirm();

?>

<div class="modal modal-blur fade" id="get_payment_from_project-modal" tabindex="-1" aria-hidden="true"
    style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Projeden Ödeme Al</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="paymentFromProjectForm">

                    <div class="row d-flex">
                        <div class="col-md-5 vertical-center justify-content-center">

                            <div class="text-center">
                                <img src="static/png/folders.png" alt="Image" class="img-fluid mt-2"
                                    style="width: 100%">
                            </div>
                        </div>
                        <div class="col-md-7">

                            <div class="row mb-3 mt-5">

                                <div class="col">
                                    <label class="form-label">Proje Adı</label>
                                    <!-- Alınan projeleri getir -->
                                    <?php echo $projectHelper->getProjectSelectByType(name: "fp_project_name", type:1) ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Ödeme Tutarı</label>
                                    <input type="text" name="fp_amount" class="form-control money">

                                </div>
                                <div class="col">
                                    <label class="form-label">Ödeme Tarihi</label>
                                    <input type="text" name="fp_action_date" class="form-control flatpickr"
                                        value="<?php echo date("d.m.Y") ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Ödemenin Aktarılacağı Kasa</label>
                                    <?php echo $financialHelper->getCasesSelectByUser("fp_cases",$case_id); ?>
                                    
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" name="fp_description" style="min-height:100px"
                                    placeholder="Açıklama giriniz!"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Çık</button>
                <button type="button" class="btn btn-primary" id="savePaymentFromProject">Kaydet</button>
            </div>
        </div>
    </div>
</div>