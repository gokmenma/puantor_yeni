<?php
if (!defined("ROOT") || !isset($_SESSION['user'])) {
    header("HTTP/1.1 403 Forbidden");
    exit("Erişim Engellendi");
}
?>
<div class="modal modal-blur fade" id="intercash_transfer-modal" tabindex="-1" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2">
                    <i class="ti ti-arrows-left-right text-warning"></i>
                    <span>Kasalar Arası Virman / Transfer</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="caseTransferForm">
                    <input type="hidden" name="it_from_cases" id="it_from_cases" value="0">
                    
                    <div class="alert alert-info d-flex align-items-center mb-3" role="alert">
                        <i class="ti ti-info-circle fs-2 me-2"></i>
                        <div style="font-size: 13px;">
                            Lütfen kasalar arasında para transferi yaparken aktarılacak kasa ve tutar bilgilerini dikkatlice kontrol ediniz.
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-12">
                            <label class="form-label">Hedef Kasa (Aktarılacak Kasa) <span class="text-danger">(*)</span></label>
                            <select name="it_to_case" id="it_to_case" class="form-select select2" style="width: 100%;">
                                <option value="0">Kasa Seçiniz...</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Aktarılacak Tutar <span class="text-danger">(*)</span></label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-currency-lira"></i></span>
                                <input type="text" class="form-control money" name="it_amount" id="it_amount" placeholder="0,00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">İşlem Tarihi <span class="text-danger">(*)</span></label>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                <input type="text" class="form-control flatpickr" name="it_date" id="it_date" value="<?php echo date('d.m.Y'); ?>" placeholder="Tarih seçiniz" required>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-1">
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea class="form-control" name="it_description" id="it_description" rows="3" placeholder="Transfer ile ilgili açıklama veya dekont numarası giriniz..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i> Vazgeç
                </button>
                <button type="button" class="btn btn-primary" id="add-case-transfer">
                    <i class="ti ti-arrows-left-right me-1"></i> Transferi Tamamla
                </button>
            </div>
        </div>
    </div>
</div>