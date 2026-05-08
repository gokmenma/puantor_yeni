<?php
require_once "App/Helper/financial.php";

$financialHelper = new Financial();
use App\Helper\Date;

// $case_id = $_POST['case_id'] ?? 0;
?>


<div class="modal modal-blur fade" id="payment-modal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-success"></div>
            <div class="modal-body text-center pb-0">
                <!-- Download SVG icon from http://tabler-icons.io/i/alert-triangle -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon mb-2 text-green icon-lg">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"></path>
                    <path d="M9 12l2 2l4 -4"></path>
                </svg>
                <h3 id="person_name_payment">
                </h3>
                <p>
                    <small class="text-success">Ödeme yapmak için aşağıdaki bilgileri doldurunuz</small>
                </p>
                <h3 class="link" data-tooltip="Tümünü Öde">
                    <span id="person_payment_balance"></span>
                </h3>
            </div>
            <div class="container ps-4 pe-4 py-4">
                <form action="" id="payment_modalForm">



                    <input type="hidden" class="form-control" name="id" value="0">
                    <input type="hidden" class="form-control" name="person_id_payment" id="person_id_payment" value="0">

                    <div class="text-secondary mt-3">
                        <label for="payment_type">Ödeme Adı</label>
                        <input type="text" name="payment_type" id="payment_type" class="form-control mt-1">
                    </div>
                    <div class="row d-flex">
                        <div class="col-6">

                            <div class="text-secondary mt-3">
                                <label for="payment_amount">Ödeme Tutarı</label>
                                <input type="text" name="payment_amount" id="payment_amount"
                                    class="form-control mt-1 money">
                            </div>
                        </div>
                        <div class="col-6">

                            <div class="text-secondary mt-3">
                                <label class="mb-1">Ödemenin çıkacağı Kasa</label>
                                <?php echo $financialHelper->getCasesSelectByUser("payment_cases", $case_id); ?>

                            </div>
                        </div>
                    </div>

                    <div class="text-secondary mt-3">

                        <label class="mb-1">Ödeme Dönemi</label>
                        <div class="row d-flex">
                            <div class="col-6">

                                <?php echo Date::getMonthsSelect("payment_month", $month); ?>
                            </div>
                            <div class="col-6">

                                <?php echo Date::getYearsSelect("payment_year"); ?>

                            </div>
                        </div>
                    </div>


                    <div class="text-secondary mt-3">
                        <label for="">Açıklama</label>
                        <textarea name="payment_description" class="form-control mt-1"
                            placeholder="Ödeme hakkında açıklama yazınız"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><a href="#" class="btn w-100" data-bs-dismiss="modal">
                                Vazgeç
                            </a></div>
                        <div class="col">
                            <a href="#" class="btn btn-success w-100" id="payment_addButton">
                                Ödeme Yap
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
          </div>
    </div>
</div>