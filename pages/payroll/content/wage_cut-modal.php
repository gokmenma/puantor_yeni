<?php
require_once "App/Helper/financial.php";

$financialHelper = new Financial();

$month = $month ?? (isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m'));
$year = $year ?? (isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y'));
?>

<div class="modal modal-blur fade" id="wage_cut_modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 500px;">
        <div class="modal-content shadow-lg border" style="border-radius: 12px; border-color: #dbe3ec !important; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header bg-white px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-danger-lt text-danger shadow-sm" style="width: 42px; height: 42px;">
                        <i class="ti ti-cut" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.1rem; letter-spacing: -0.3px;">Kesinti Ekle</h4>
                        <div class="text-secondary small mt-0.5" id="person_name_wage_cut" style="font-size: 12.5px;">Personel seçiniz</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-white">
                <form action="" id="wage_cut_modalForm">
                    <input type="hidden" class="form-control" name="wage_cut_id" value="0">
                    <input type="hidden" class="form-control" name="person_id_wage_cut" id="person_id_wage_cut" value="0">
                    <input type="hidden" name="wage_cut_month" value="<?php echo (int) $month; ?>">
                    <input type="hidden" name="wage_cut_year" value="<?php echo (int) $year; ?>">

                    <div class="mb-3">
                        <label class="form-label required fw-semibold text-secondary small mb-1" for="wage_cut_type">Kesinti Adı</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-tag text-muted"></i>
                            </span>
                            <input type="text" name="wage_cut_type" id="wage_cut_type" class="form-control" placeholder="Örn: Avans Kesintisi, Ceza, Eksik Gün">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold text-secondary small mb-1" for="wage_cut_amount">Kesinti Miktarı</label>
                        <div class="input-group input-group-flat">
                            <input type="text" name="wage_cut_amount" id="wage_cut_amount" class="form-control money" placeholder="0,00">
                            <span class="input-group-text bg-light text-muted fw-bold">₺</span>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold text-secondary small mb-1" for="wage_cut_description">Açıklama</label>
                        <textarea name="wage_cut_description" id="wage_cut_description" class="form-control" rows="2" placeholder="Kesinti hakkında varsa açıklama yazınız..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn btn-outline-secondary px-3 py-1.5" data-bs-dismiss="modal" style="height: 34px; font-size: 13px;">Vazgeç</button>
                <button type="button" class="btn btn-danger shadow-sm px-4 py-1.5 d-inline-flex align-items-center gap-1.5" id="wage_cut_addButton" style="height: 34px; font-size: 13px;">
                    <i class="ti ti-check" style="font-size: 16px;"></i> Kesinti Ekle
                </button>
            </div>
        </div>
    </div>
</div>
