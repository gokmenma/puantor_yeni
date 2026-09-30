<?php 
use App\Helper\Date;
?> 

<div class="modal modal-blur fade" id="income_modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 520px;">
        <div class="modal-content shadow-lg border" style="border-radius: 12px; border-color: #dbe3ec !important; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header bg-white px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 42px; height: 42px;">
                        <i class="ti ti-circle-plus" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.1rem; letter-spacing: -0.3px;">Gelir / Ek Kazanç Ekle</h4>
                        <div class="text-secondary small mt-0.5" id="person_name_income" style="font-size: 12.5px;">Personel seçiniz</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-white">
                <form action="" id="income_modalForm">
                    <input type="hidden" class="form-control" name="id" value="0">
                    <input type="hidden" class="form-control" name="person_id_income" id="person_id_income" value="0">

                    <div class="mb-3">
                        <label class="form-label required fw-semibold text-secondary small mb-1" for="income_type">Gelir Türü</label>
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-tag text-muted"></i>
                            </span>
                            <input type="text" name="income_type" id="income_type" class="form-control" placeholder="Örn: Prim, İkramiye, Yol/Yemek Bedeli">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold text-secondary small mb-1" for="income_amount">Gelir Tutarı</label>
                        <div class="input-group input-group-flat">
                            <input type="text" name="income_amount" id="income_amount" class="form-control money" placeholder="0,00">
                            <span class="input-group-text bg-light text-muted fw-bold">₺</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required fw-semibold text-secondary small mb-1">Gelir Eklenecek Dönem</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <?php echo Date::getMonthsSelect("income_month"); ?>
                            </div>
                            <div class="col-6">
                                <?php echo Date::getYearsSelect("income_year"); ?>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label fw-semibold text-secondary small mb-1" for="income_description">Açıklama</label>
                        <textarea name="income_description" id="income_description" class="form-control" rows="2" placeholder="Gelir hakkında varsa açıklama yazınız..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn btn-outline-secondary px-3 py-1.5" data-bs-dismiss="modal" style="height: 34px; font-size: 13px;">Vazgeç</button>
                <button type="button" class="btn btn-primary shadow-sm px-4 py-1.5 d-inline-flex align-items-center gap-1.5" id="income_addButton" style="height: 34px; font-size: 13px;">
                    <i class="ti ti-check" style="font-size: 16px;"></i> Gelir Ekle
                </button>
            </div>
        </div>
    </div>
</div>