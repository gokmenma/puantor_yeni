<?php
require_once 'App/Helper/date.php';
require_once "App/Helper/projects.php";
require_once "App/Helper/financial.php";

use App\Helper\Date;

$projectHelper = new ProjectHelper();
if (!isset($financialHelper)) {
    $financialHelper = new Financial();
}
if (!isset($case_id)) {
    $case_id = 0;
}
?>

<div class="modal modal-blur fade" id="add_expense_received_project-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 540px;">
        <div class="modal-content shadow-sm border-0" style="border-radius: 12px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header px-4 py-3 bg-body border-bottom">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-warning-lt text-warning shadow-none" style="width: 40px; height: 40px; min-width: 40px;">
                        <i class="ti ti-receipt" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" style="font-size: 15px; letter-spacing: -0.2px;">Alınan Proje Masrafı Ekle</h5>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">Proje için yapılan harcama veya masraf kaydı işleyin</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <form action="" id="addExpenseReceivedProjectForm" autocomplete="off">
                <input type="hidden" id="rp_id" name="rp_id" value="0">

                <!-- Modal Body -->
                <div class="modal-body px-4 py-3.5">
                    <!-- Proje Seçimi -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;">
                            Proje <span class="text-danger ms-0.5">*</span>
                        </label>
                        <div class="input-icon">
                            <span class="input-icon-addon text-muted">
                                <i class="ti ti-building" style="font-size: 17px;"></i>
                            </span>
                            <?php echo $projectHelper->getProjectSelectByType(name: "rp_project_name", type: 1) ?>
                        </div>
                    </div>

                    <!-- Masrafın Çıkacağı Kasa -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;">
                            Masrafın Çıkacağı Kasa <span class="text-danger ms-0.5">*</span>
                        </label>
                        <div class="input-icon">
                            <span class="input-icon-addon text-muted">
                                <i class="ti ti-wallet" style="font-size: 17px;"></i>
                            </span>
                            <?php echo $financialHelper->getCasesSelectByUser("rp_cases", $case_id); ?>
                        </div>
                    </div>

                    <!-- Tutar ve Tarih (Yan Yana) -->
                    <div class="row g-3 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;">
                                Masraf Tutarı <span class="text-danger ms-0.5">*</span>
                            </label>
                            <div class="input-icon">
                                <span class="input-icon-addon text-muted">
                                    <i class="ti ti-currency-lira" style="font-size: 17px;"></i>
                                </span>
                                <input type="text" name="rp_amount" id="rp_amount" class="form-control money fw-bold text-dark" placeholder="0,00" autocomplete="off" style="font-size: 13.5px; height: 38px;">
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;">
                                Masraf Tarihi <span class="text-danger ms-0.5">*</span>
                            </label>
                            <div class="input-icon">
                                <span class="input-icon-addon text-muted">
                                    <i class="ti ti-calendar" style="font-size: 17px;"></i>
                                </span>
                                <input type="text" name="rp_action_date" id="rp_action_date" class="form-control flatpickr" value="<?php echo date("d.m.Y") ?>" autocomplete="off" style="font-size: 13.5px; height: 38px;">
                            </div>
                        </div>
                    </div>

                    <!-- Açıklama -->
                    <div class="mb-1">
                        <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;">
                            Açıklama <span class="text-muted fw-normal small">(İsteğe bağlı)</span>
                        </label>
                        <textarea class="form-control" name="rp_description" id="rp_description" rows="2" style="min-height: 70px; font-size: 13px; resize: vertical;" placeholder="Masraf hakkında açıklama veya fiş detayı..."></textarea>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer px-4 py-2.5 bg-body border-top d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="height: 36px; padding: 6px 18px; font-size: 13px;">
                        Vazgeç
                    </button>
                    <button type="button" class="btn btn-dark" id="saveAddExpenseReceivedProject" style="height: 36px; padding: 6px 20px; font-size: 13px; background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-check me-1.5" style="font-size: 16px;"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
#add_expense_received_project-modal .modal-content {
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12) !important;
}

#add_expense_received_project-modal .form-control {
    border-color: #dbe3ec;
    border-radius: 8px;
    font-size: 13px;
    padding: 6px 12px;
    color: #1e293b;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
#add_expense_received_project-modal .form-control:focus {
    border-color: #206bc4;
    box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.12);
}

#add_expense_received_project-modal .input-icon {
    position: relative;
    width: 100%;
}
#add_expense_received_project-modal .input-icon .form-control {
    padding-left: 38px !important;
}
#add_expense_received_project-modal .input-icon-addon {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 38px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #64748b;
    pointer-events: none;
    z-index: 3;
}

#add_expense_received_project-modal .select2-container {
    width: 100% !important;
}
#add_expense_received_project-modal .select2-container--default .select2-selection--single {
    height: 38px !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    background-color: #ffffff !important;
    padding: 5px 12px 5px 38px !important;
    display: flex !important;
    align-items: center !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}
#add_expense_received_project-modal .select2-container--default.select2-container--open .select2-selection--single,
#add_expense_received_project-modal .select2-container--default.select2-container--focus .select2-selection--single {
    border-color: #206bc4 !important;
    box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.12) !important;
}
#add_expense_received_project-modal .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #1e293b !important;
    font-size: 13px !important;
    line-height: normal !important;
    padding-left: 0 !important;
    padding-right: 24px !important;
    font-weight: 500;
}
#add_expense_received_project-modal .select2-container--default .select2-selection--single .select2-selection__placeholder {
    color: #94a3b8 !important;
}
#add_expense_received_project-modal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    right: 8px !important;
    top: 1px !important;
}
#add_expense_received_project-modal .select2-container--default .select2-selection--single .select2-selection__arrow b {
    border-color: #64748b transparent transparent transparent !important;
    border-width: 5px 4px 0 4px !important;
}
#add_expense_received_project-modal .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
    border-color: transparent transparent #64748b transparent !important;
    border-width: 0 4px 5px 4px !important;
}

/* Dark Mode */
[data-bs-theme="dark"] #add_expense_received_project-modal .modal-content {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4) !important;
}
[data-bs-theme="dark"] #add_expense_received_project-modal .modal-header,
[data-bs-theme="dark"] #add_expense_received_project-modal .modal-footer {
    background: #1e293b !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #add_expense_received_project-modal .form-label {
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] #add_expense_received_project-modal .form-control {
    background: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #add_expense_received_project-modal .select2-container--default .select2-selection--single {
    background-color: #0f172a !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #add_expense_received_project-modal .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #f8fafc !important;
}
</style>

<script>
$(document).ready(function() {
    $('#add_expense_received_project-modal').on('shown.bs.modal', function () {
        if ($.fn.select2) {
            $('#add_expense_received_project-modal .select2').select2({
                dropdownParent: $('#add_expense_received_project-modal'),
                width: '100%'
            });
        }
        if (typeof flatpickr !== 'undefined') {
            flatpickr('#add_expense_received_project-modal .flatpickr', {
                dateFormat: 'd.m.Y',
                locale: 'tr'
            });
        }
    });
});
</script>