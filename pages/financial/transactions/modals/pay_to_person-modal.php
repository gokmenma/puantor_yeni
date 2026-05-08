<?php
require_once 'App/Helper/date.php';
require_once "App/Helper/person.php";
use App\Helper\Date;

$personHelper = new PersonHelper();
?>

<div class="modal modal-blur fade" id="pay_to_person-modal" aria-hidden="true"
    style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Personel Ödemesi Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="payToPersonForm">
                    <input type="hidden" class="form-control" id="tp_id" name="tp_id" value="0">

                    <div class="row d-flex">
                        <div class="col-md-5 vertical-center justify-content-center">

                            <div class="text-center">
                                <img src="static/png/boy.png" alt="Image" class="img-fluid mt-2"
                                    style="width:100%">
                            </div>
                        </div>
                        <div class="col-md-7">

                            <div class="row mb-3 mt-5">

                                <div class="col">
                                    <label class="form-label">Personel Adı</label>
                                    <!-- Tüm Personeli getir -->
                                    <?php echo $personHelper->getPersonSelect(name: "tp_person_name") ?>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Ödeme Tutarı</label>
                                    <input type="text" name="tp_amount" id="tp_amount" class="form-control money">

                                </div>
                                <div class="col">
                                    <label class="form-label">Ödeme Tarihi</label>
                                    <input type="text" name="tp_action_date" class="form-control flatpickr"
                                        value="<?php echo date("d.m.Y") ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Ödeme Yapılacak Kasa</label>
                                    <?php echo $financialHelper->getCasesSelectByUser("tp_cases",$case_id); ?>
                                    
                                </div>
                            </div>

                            <div>
                                <label class="form-label">Açıklama</label>
                                <textarea class="form-control" name="tp_description" style="min-height:100px"
                                    placeholder="Açıklama giriniz!"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Çık</button>
                <button type="button" class="btn btn-primary" id="savePayToPerson">Kaydet</button>
            </div>
        </div>
    </div>
</div>