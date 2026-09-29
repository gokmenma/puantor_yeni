<?php
require_once 'App/Helper/helper.php';
require_once "App/Helper/person.php";
require_once "App/Helper/company.php";

$personHelper = new PersonHelper();
$CompanyHelper = new CompanyHelper();

use App\Helper\Helper;
?>
<div class="modal modal-blur fade" id="general-modal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 620px;">
        <div class="modal-content shadow-lg border-0 rounded-3">
            <!-- Modal Header -->
            <div class="modal-header py-2.5 px-3 bg-light-subtle border-bottom">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 34px; height: 34px;">
                        <i class="ti ti-arrows-diff" style="font-size: 18px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" style="font-size: 15px; letter-spacing: -0.2px;">Yeni Hareket Ekle</h5>
                        <div class="text-muted font-11" style="font-size: 11.5px; line-height: 1.2;">Kasaya gelir veya gider kaydı işleyin</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3">
                <form id="transactionModalForm">
                    <input type="hidden" class="form-control" id="transaction_id" name="transaction_id" value="0">

                    <!-- 1. Muhatap Türü Segmented Tabs -->
                    <div class="mb-3">
                        <label class="form-label text-muted fw-semibold text-uppercase mb-1.5" style="font-size: 11px; letter-spacing: 0.5px;">
                            İlişkili Muhatap / Hesap
                        </label>
                        <div class="p-1 bg-surface-secondary rounded-3 border" style="background: #f8fafc;">
                            <ul class="nav nav-pills nav-fill gap-1 p-0 m-0" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-home-7" class="nav-link active py-1.5 px-3 rounded-2 fw-medium text-dark-emphasis" data-bs-toggle="tab" role="tab" style="font-size: 12.5px;">
                                        <i class="ti ti-building me-1 text-primary"></i> Proje
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-profile-7" class="nav-link py-1.5 px-3 rounded-2 fw-medium text-dark-emphasis" data-bs-toggle="tab" role="tab" style="font-size: 12.5px;">
                                        <i class="ti ti-users me-1 text-warning"></i> Personel
                                    </a>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <a href="#tabs-activity-7" class="nav-link py-1.5 px-3 rounded-2 fw-medium text-dark-emphasis" data-bs-toggle="tab" role="tab" style="font-size: 12.5px;">
                                        <i class="ti ti-briefcase me-1 text-info"></i> Firma / Cari
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Muhatap Select Tab Panes -->
                    <div class="tab-content mb-3">
                        <div class="tab-pane active show" id="tabs-home-7" role="tabpanel">
                            <label class="form-label fw-medium text-secondary small mb-1" style="font-size: 12px;">Proje Seçimi <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                            <?php echo $projectHelper->getProjectSelect("gm_project_id") ?>
                        </div>
                        <div class="tab-pane" id="tabs-profile-7" role="tabpanel">
                            <label class="form-label fw-medium text-secondary small mb-1" style="font-size: 12px;">Personel Seçimi <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                            <?php echo $personHelper->getPersonSelect(name: "gm_person_name") ?>
                        </div>
                        <div class="tab-pane" id="tabs-activity-7" role="tabpanel">
                            <label class="form-label fw-medium text-secondary small mb-1" style="font-size: 12px;">Firma / Cari Seçimi <span class="text-muted fw-normal">(Opsiyonel)</span></label>
                            <?php echo $CompanyHelper->getCompanySelect(name: "gm_company") ?>
                        </div>
                    </div>

                    <!-- 2. İşlem Türü Kartları (Gelir / Gider) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;">
                            İşlem Yönü <span class="text-danger">*</span>
                        </label>
                        <div class="row g-2">
                            <!-- Gelir -->
                            <div class="col-6">
                                <label class="form-selectgroup-item w-100 cursor-pointer">
                                    <input type="radio" name="transaction_type" value="1" class="form-selectgroup-input transaction_type" checked>
                                    <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-2 rounded-2 border trx-card trx-card-income">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 30px; height: 30px;">
                                                <i class="ti ti-arrow-up-right" style="font-size: 16px;"></i>
                                            </div>
                                            <div class="text-start">
                                                <div class="fw-bold text-dark" style="font-size: 13px;">Gelir</div>
                                                <div class="text-muted font-11" style="font-size: 10.5px;">Tahsilat / Giriş</div>
                                            </div>
                                        </div>
                                        <i class="ti ti-circle-check-filled text-success trx-check-icon font-18"></i>
                                    </div>
                                </label>
                            </div>

                            <!-- Gider -->
                            <div class="col-6">
                                <label class="form-selectgroup-item w-100 cursor-pointer">
                                    <input type="radio" name="transaction_type" value="2" class="form-selectgroup-input transaction_type">
                                    <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-2 rounded-2 border trx-card trx-card-expense">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 30px; height: 30px;">
                                                <i class="ti ti-arrow-down-left" style="font-size: 16px;"></i>
                                            </div>
                                            <div class="text-start">
                                                <div class="fw-bold text-dark" style="font-size: 13px;">Gider</div>
                                                <div class="text-muted font-11" style="font-size: 10.5px;">Ödeme / Çıkış</div>
                                            </div>
                                        </div>
                                        <i class="ti ti-circle-check-filled text-danger trx-check-icon font-18"></i>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Kasa & İşlem Alt Türü -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                                Kasa <span class="text-danger">*</span>
                            </label>
                            <?php echo $financial->getCasesSelectByUser("gm_case_id", $case_id) ?>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                                Gelir / Gider Türü <span class="text-danger">*</span>
                            </label>
                            <?php echo $financial->getIncExpTypeSelect("gm_incexp_type"); ?>
                        </div>
                    </div>

                    <!-- 4. Tutar, Para Birimi & Tarih -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                                Tutar <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted px-2.5" style="border-color: #dbe3ec;">
                                    <i class="ti ti-coin"></i>
                                </span>
                                <input type="text" name="amount" id="amount" class="form-control money fw-bold text-dark" placeholder="0,00" value="" autocomplete="off" style="font-size: 14px;">
                                <div style="max-width: 90px;">
                                    <?php echo Helper::moneySelect("gm_amount_money", ''); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                                İşlem Tarihi
                            </label>
                            <div class="input-icon">
                                <span class="input-icon-addon text-muted">
                                    <i class="ti ti-calendar"></i>
                                </span>
                                <input type="text" name="transaction_date" class="form-control flatpickr" value="<?php echo date('d.m.Y'); ?>" autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- 5. Açıklama -->
                    <div class="mb-1">
                        <label class="form-label fw-semibold text-dark mb-1" style="font-size: 12.5px;">
                            Açıklama <span class="text-muted fw-normal small">(İsteğe bağlı)</span>
                        </label>
                        <textarea class="form-control" name="description" id="gm_description" rows="2" style="min-height: 60px; font-size: 12.5px;" placeholder="İşlem hakkında kısa not veya açıklama giriniz..."></textarea>
                    </div>
                </form>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer py-2 px-3 bg-light-subtle border-top d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal" style="height: 32px; padding: 4px 14px;">
                    Vazgeç
                </button>
                <button type="button" class="btn btn-sm btn-dark" id="saveTransaction" style="height: 32px; padding: 4px 16px; background-color: #1e293b; border-color: #1e293b;">
                    <i class="ti ti-check me-1.5"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Modal Tab Segmented Buttons */
#general-modal .nav-pills .nav-link {
    color: #64748b;
    background: transparent;
    transition: all 0.15s ease-in-out;
    border: 1px solid transparent;
}
#general-modal .nav-pills .nav-link:hover {
    color: #1e293b;
    background: rgba(255, 255, 255, 0.6);
}
#general-modal .nav-pills .nav-link.active {
    color: #0f172a !important;
    background: #ffffff !important;
    border-color: #e2e8f0 !important;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08) !important;
}

/* Gelir / Gider Radio Kartları */
#general-modal .trx-card {
    background: #ffffff;
    border-color: #e2e8f0 !important;
    transition: all 0.15s ease-in-out;
    position: relative;
}
#general-modal .trx-card:hover {
    border-color: #cbd5e1 !important;
    background: #f8fafc;
}
#general-modal .trx-check-icon {
    display: none;
    font-size: 18px;
}
#general-modal .form-selectgroup-input:checked + .trx-card-income {
    border-color: #2fb344 !important;
    background: #f0fdf4 !important;
    box-shadow: 0 0 0 1px #2fb344;
}
#general-modal .form-selectgroup-input:checked + .trx-card-income .trx-check-icon {
    display: inline-block;
}

#general-modal .form-selectgroup-input:checked + .trx-card-expense {
    border-color: #d63939 !important;
    background: #fef2f2 !important;
    box-shadow: 0 0 0 1px #d63939;
}
#general-modal .form-selectgroup-input:checked + .trx-card-expense .trx-check-icon {
    display: inline-block;
}

/* Select2 in General Modal */
#general-modal .select2-container--default .select2-selection--single {
    height: 34px !important;
    padding: 3px 8px !important;
    font-size: 13px !important;
    border-radius: 6px !important;
    border-color: #dbe3ec !important;
}
#general-modal .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 26px !important;
}
#general-modal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 32px !important;
}

/* Dark Mode */
[data-bs-theme="dark"] #general-modal .modal-content {
    background: #182433;
    border-color: #334155;
}
[data-bs-theme="dark"] #general-modal .modal-header,
[data-bs-theme="dark"] #general-modal .modal-footer {
    background: #151f2c !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #general-modal .nav-pills .nav-link.active {
    background: #1e293b !important;
    color: #f8fafc !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #general-modal .trx-card {
    background: #1e293b;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #general-modal .form-selectgroup-input:checked + .trx-card-income {
    background: rgba(47, 179, 68, 0.15) !important;
    border-color: #2fb344 !important;
}
[data-bs-theme="dark"] #general-modal .form-selectgroup-input:checked + .trx-card-expense {
    background: rgba(214, 57, 57, 0.15) !important;
    border-color: #d63939 !important;
}
</style>