
<?php 
$case_id = 0;
?>
<div class="modal modal-blur fade" id="intercash_transfer-modal" tabindex="-1" aria-hidden="true"
    style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kasalar arası Transfer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ">
                <form action="" id="caseTransferForm">
                    <input type="hidden" name="from_case" id="from_case" value="0">
                    <div class="card mb-3">
                        <div class="ribbon ribbon-top bg-yellow">
                            <!-- Download SVG icon from http://tabler-icons.io/i/star -->
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round" class="icon">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path
                                    d="M12 17.75l-6.172 3.245l1.179 -6.873l-5 -4.867l6.9 -1l3.086 -6.253l3.086 6.253l6.9 1l-5 4.867l1.179 6.873z">
                                </path>
                            </svg>
                        </div>
                        <div class="card-body">
                            <h3 class="card-title">Lütfen kasalar arasında para transferi yaparken bilgilerinizi
                                dikkatlice
                                kontrol ediniz.</h3>
                          
                        </div>
                    </div>
                    <div class="row mb-3 align-items-end">

                        <div class="col">
                            <label class="form-label">Çıkış Yapılacak Kasa <font class="text-danger">(*)</font></label>
                            <?php echo $financialHelper->getCasesSelectByUser("it_from_cases", $case_id); ?>
                        </div>
                    </div>
                    <div class="row mb-3 align-items-end">

                        <div class="col">
                            <label class="form-label">Aktarılacak Kasa <font class="text-danger">(*)</font></label>
                            <select name="it_to_case" id="it_to_case" class="form-control select2" style="width:100%">
                                <option value="0">Kasa Seçiniz!</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">

                        <div class="col-md-6">
                            <label class="form-label">Aktarılacak Tutar <font class="text-danger">(*)</font></label>
                            <input type="text" class="form-control money" name="it_amount" id="it_amount"
                                placeholder="Tutar giriniz">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tarih<font class="text-danger">(*)</font></label>
                            <input type="text" class="form-control flatpickr" name="it_date" id="it_date" value="<?php echo date("d.m.Y")?>"
                                placeholder="Tarih giriniz">
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="it_description" id="it_description" rows="3"
                            style="min-height: 100px" placeholder="Açıklama"></textarea>

                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Çık</button>
                <button type="button" class="btn btn-primary" id="add-case-transfer">Transfer
                    Yap</button>
            </div>
        </div>
    </div>
</div>