<?php

// use App\Helper\Date;

?>


<div class="modal modal-blur fade" id="payment-modal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center pb-0">
                <i class="ti ti-cash-register icon-lg text-danger"></i>
                <h3 id="payment_project_name">
                </h3>
                <p>
                    <small class="text-danger">Ödeme yapmak için aşağıdaki bilgileri doldurunuz</small>
                </p>
                <h3 class="link" data-tooltip="Tümünü Öde">
                    <span id="project_payment_balance"></span>
                </h3>
            </div>
            <div class="container ps-4 pe-4 py-4">
                <form action="" id="payment_modalForm">

                    <input type="hidden" class="form-control" name="payment_id" value="0">
                    <input type="hidden" class="form-control" name="payment_project_id" id="payment_project_id"
                        value="0">
                    <div class="text-secondary mt-3">
                        <label for="">Ödeme Tutarı</label>
                        <input type="text" name="payment_amount" id="payment_amount" class="form-control mt-1 money" autofocus="true">
                    </div>

                    <div class="text-secondary mt-3">
                        <label for="">Ödeme Tarihi</label>
                        <input type="text" name="payment_date" id="payment_date" class="form-control mt-1 flatpickr" value="<?php echo date('d.m.Y') ?>">
                    </div>

                    <!-- Kasa seçiniz -->
                    <div class="text-secondary mt-3">
                    <label for="">Kasa</label>
                        <?php  echo $financialHelper->getCasesSelectByUser("payment_cases",$case_id); ?>
                    </div>

                    <div class="text-secondary mt-3">
                        <label for="">Açıklama</label>
                        <textarea name="payment_description" class="form-control mt-1" style="min-height:100px"
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
                            <a href="#" class="btn btn-danger w-100" id="payment_addButton">
                                Ödeme Ekle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>