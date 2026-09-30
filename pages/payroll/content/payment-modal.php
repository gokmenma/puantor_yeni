<?php
require_once "App/Helper/financial.php";

$financialHelper = new Financial();

$month = $month ?? (isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m'));
$year = $year ?? (isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y'));
$case_id = $case_id ?? 0;
?>

<div class="modal modal-blur fade" id="payment-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
        <div class="modal-content shadow-lg border" style="border-radius: 12px; border-color: #dbe3ec !important; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header bg-white px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-success-lt text-success shadow-sm" style="width: 42px; height: 42px;">
                        <i class="ti ti-cash-banknote" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.1rem; letter-spacing: -0.3px;">Personel Ödemesi</h4>
                        <div class="text-secondary small mt-0.5" id="person_name_payment" style="font-size: 12.5px;">Personel seçiniz</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-white">
                <!-- Güncel Bakiye Bilgisi Banner -->
                <div class="d-flex align-items-center justify-content-between p-2.5 px-3 rounded-2 bg-light border border-slate-200 mb-3" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="ti ti-info-circle text-primary" style="font-size: 18px;"></i>
                        <span class="text-muted small fw-medium">Kalan Bakiye:</span>
                    </div>
                    <a href="javascript:void(0);" id="person_payment_balance" class="badge bg-success-lt text-success fw-bold px-2.5 py-1.5 cursor-pointer text-decoration-none" title="Bakiyeyi ödeme tutarına aktarmak için tıklayın" style="font-size: 13px;">
                        0,00 ₺
                    </a>
                </div>

                <form action="" id="payment_modalForm">
                    <input type="hidden" class="form-control" name="id" value="0">
                    <input type="hidden" class="form-control" name="person_id_payment" id="person_id_payment" value="0">
                    <input type="hidden" name="payment_month" value="<?php echo (int) $month; ?>">
                    <input type="hidden" name="payment_year" value="<?php echo (int) $year; ?>">

                    <div class="mb-3">
                        <label class="form-label required fw-semibold text-secondary small mb-1" for="payment_type">Ödeme Adı</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-tag text-muted"></i>
                            </span>
                            <input type="text" name="payment_type" id="payment_type" class="form-control" placeholder="Örn: Maaş Ödemesi, Avans, Prim">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label required fw-semibold text-secondary small mb-1" for="payment_amount">Ödeme Tutarı</label>
                            <div class="input-group input-group-flat">
                                <input type="text" name="payment_amount" id="payment_amount" class="form-control money" placeholder="0,00">
                                <span class="input-group-text bg-light text-muted fw-bold">₺</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label required fw-semibold text-secondary small mb-1">Çıkış Yapılacak Kasa</label>
                            <?php echo $financialHelper->getCasesSelectByUser("payment_cases", $case_id); ?>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold text-secondary small mb-1" for="payment_description">Açıklama</label>
                        <textarea name="payment_description" id="payment_description" class="form-control" rows="2" placeholder="Ödeme hakkında varsa açıklama yazınız..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn btn-outline-secondary px-3 py-1.5" data-bs-dismiss="modal" style="height: 34px; font-size: 13px;">Vazgeç</button>
                <button type="button" class="btn btn-success shadow-sm px-4 py-1.5 d-inline-flex align-items-center gap-1.5" id="payment_addButton" style="height: 34px; font-size: 13px;">
                    <i class="ti ti-check" style="font-size: 16px;"></i> Ödeme Yap
                </button>
            </div>
        </div>
    </div>
</div>
