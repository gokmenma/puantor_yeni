<?php
require_once 'App/Helper/helper.php';
require_once "App/Helper/person.php";
require_once "App/Helper/company.php";

$personHelper = new PersonHelper();
$CompanyHelper = new CompanyHelper();

use App\Helper\Helper;
?>
<div class="modal modal-blur fade" id="general-modal" tabindex="-1" style="display: none;" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document" style="max-width: 660px;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4 bg-white border-bottom align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-none" style="width: 42px; height: 42px;">
                        <i class="ti ti-arrows-diff" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="generalModalTitle" style="font-size: 1.15rem; letter-spacing: -0.2px;">
                            Yeni Hareket Ekle
                        </h4>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Kasaya gelir veya gider kaydı işleyin
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="transactionModalForm">
                <input type="hidden" class="form-control" id="transaction_id" name="transaction_id" value="0">

                <!-- Modal Body -->
                <div class="modal-body p-4 bg-white">
                    <div class="row g-3">
                        
                        <!-- 1. Muhatap Türü Segmented Switcher & Seçimi -->
                        <div class="col-12">
                            <label class="form-label fw-semibold text-secondary mb-1.5" style="font-size: 11.5px; text-transform: uppercase; letter-spacing: 0.5px;">
                                İlişkili Muhatap / Hesap
                            </label>
                            <div class="p-1 bg-light-subtle rounded-3 border mb-2.5">
                                <ul class="nav nav-pills nav-fill gap-1 p-0 m-0" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-home-7" class="nav-link active py-1.5 px-3 rounded-2 fw-semibold text-dark-emphasis d-flex align-items-center justify-content-center gap-1.5" data-bs-toggle="tab" role="tab" style="font-size: 12.5px;">
                                            <i class="ti ti-building text-primary" style="font-size: 16px;"></i>
                                            <span>Proje</span>
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-profile-7" class="nav-link py-1.5 px-3 rounded-2 fw-semibold text-dark-emphasis d-flex align-items-center justify-content-center gap-1.5" data-bs-toggle="tab" role="tab" style="font-size: 12.5px;">
                                            <i class="ti ti-users text-warning" style="font-size: 16px;"></i>
                                            <span>Personel</span>
                                        </a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-activity-7" class="nav-link py-1.5 px-3 rounded-2 fw-semibold text-dark-emphasis d-flex align-items-center justify-content-center gap-1.5" data-bs-toggle="tab" role="tab" style="font-size: 12.5px;">
                                            <i class="ti ti-briefcase text-info" style="font-size: 16px;"></i>
                                            <span>Firma / Cari</span>
                                        </a>
                                    </li>
                                </ul>
                            </div>

                            <!-- Muhatap Select Panelleri (Input Group formatında) -->
                            <div class="tab-content">
                                <div class="tab-pane active show" id="tabs-home-7" role="tabpanel">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">
                                        Proje Seçimi <span class="text-muted fw-normal small">(Opsiyonel)</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted">
                                            <i class="ti ti-building"></i>
                                        </span>
                                        <?php echo $projectHelper->getProjectSelect("gm_project_id") ?>
                                    </div>
                                </div>
                                <div class="tab-pane" id="tabs-profile-7" role="tabpanel">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">
                                        Personel Seçimi <span class="text-muted fw-normal small">(Opsiyonel)</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted">
                                            <i class="ti ti-user"></i>
                                        </span>
                                        <?php echo $personHelper->getPersonSelect(name: "gm_person_name") ?>
                                    </div>
                                </div>
                                <div class="tab-pane" id="tabs-activity-7" role="tabpanel">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">
                                        Firma / Cari Seçimi <span class="text-muted fw-normal small">(Opsiyonel)</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted">
                                            <i class="ti ti-building-store"></i>
                                        </span>
                                        <?php echo $CompanyHelper->getCompanySelect(name: "gm_company") ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. İşlem Yönü Kartları (Gelir / Gider) -->
                        <div class="col-12">
                            <label class="form-label required fw-semibold" style="font-size: 13px;">
                                İşlem Yönü
                            </label>
                            <div class="row g-2">
                                <!-- Gelir -->
                                <div class="col-sm-6">
                                    <label class="form-selectgroup-item w-100 cursor-pointer">
                                        <input type="radio" name="transaction_type" value="1" class="form-selectgroup-input transaction_type" checked>
                                        <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-2.5 rounded-3 border trx-card trx-card-income">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 34px; height: 34px;">
                                                    <i class="ti ti-arrow-up-right" style="font-size: 18px;"></i>
                                                </div>
                                                <div class="text-start">
                                                    <div class="fw-bold text-dark" style="font-size: 13px;">Gelir</div>
                                                    <div class="text-muted" style="font-size: 11px;">Tahsilat / Giriş</div>
                                                </div>
                                            </div>
                                            <i class="ti ti-circle-check-filled text-success trx-check-icon" style="font-size: 18px;"></i>
                                        </div>
                                    </label>
                                </div>

                                <!-- Gider -->
                                <div class="col-sm-6">
                                    <label class="form-selectgroup-item w-100 cursor-pointer">
                                        <input type="radio" name="transaction_type" value="2" class="form-selectgroup-input transaction_type">
                                        <div class="form-selectgroup-label d-flex align-items-center justify-content-between p-2.5 rounded-3 border trx-card trx-card-expense">
                                            <div class="d-flex align-items-center gap-2.5">
                                                <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 34px; height: 34px;">
                                                    <i class="ti ti-arrow-down-left" style="font-size: 18px;"></i>
                                                </div>
                                                <div class="text-start">
                                                    <div class="fw-bold text-dark" style="font-size: 13px;">Gider</div>
                                                    <div class="text-muted" style="font-size: 11px;">Ödeme / Çıkış</div>
                                                </div>
                                            </div>
                                            <i class="ti ti-circle-check-filled text-danger trx-check-icon" style="font-size: 18px;"></i>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Kasa & Gelir / Gider Türü -->
                        <div class="col-sm-6">
                            <label class="form-label required fw-semibold" style="font-size: 13px;">
                                Kasa
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="ti ti-wallet"></i>
                                </span>
                                <?php echo $financial->getCasesSelectByUser("gm_case_id", $case_id) ?>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label required fw-semibold" style="font-size: 13px;">
                                Gelir / Gider Türü
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="ti ti-category"></i>
                                </span>
                                <?php echo $financial->getIncExpTypeSelect("gm_incexp_type"); ?>
                            </div>
                        </div>

                        <!-- 4. Tutar & İşlem Tarihi -->
                        <div class="col-sm-7">
                            <label class="form-label required fw-semibold" style="font-size: 13px;">
                                Tutar
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="ti ti-coin"></i>
                                </span>
                                <input type="text" name="amount" id="amount" class="form-control money fw-bold text-dark" placeholder="0,00" value="" autocomplete="off">
                                <div class="currency-select-wrapper">
                                    <?php echo Helper::moneySelect("gm_amount_money", ''); ?>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label fw-semibold" style="font-size: 13px;">
                                İşlem Tarihi
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">
                                    <i class="ti ti-calendar"></i>
                                </span>
                                <input type="text" name="transaction_date" class="form-control flatpickr" value="<?php echo date('d.m.Y'); ?>" autocomplete="off">
                            </div>
                        </div>

                        <!-- 5. Açıklama -->
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size: 13px;">
                                Açıklama <span class="text-muted fw-normal small">(İsteğe bağlı)</span>
                            </label>
                            <div class="input-group align-items-stretch">
                                <span class="input-group-text bg-light text-muted is-textarea">
                                    <i class="ti ti-notes"></i>
                                </span>
                                <textarea class="form-control" name="description" id="gm_description" rows="2" style="min-height: 64px; font-size: 13px;" placeholder="İşlem hakkında kısa not veya açıklama giriniz..."></textarea>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">
                        Vazgeç
                    </button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm fw-semibold" id="saveTransaction">
                        <i class="ti ti-device-floppy me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Muhatap Segmented Buttons */
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

/* Standart Input Group Düzeni */
#general-modal .input-group {
    position: relative;
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    width: 100%;
}

/* Standart 38x38 İkon Kutusu ve 16px İkon Boyu */
#general-modal .input-group-text {
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    color: #64748b !important;
    width: 38px !important;
    min-width: 38px !important;
    max-width: 38px !important;
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    border-top-left-radius: 8px !important;
    border-bottom-left-radius: 8px !important;
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: 0 !important;
    flex-shrink: 0 !important;
}

#general-modal .input-group-text i {
    font-size: 16px !important;
    width: 16px !important;
    height: 16px !important;
    line-height: 16px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}

#general-modal .input-group-text.is-textarea {
    height: auto !important;
    min-height: 64px !important;
    max-height: none !important;
    align-items: flex-start !important;
    padding-top: 10px !important;
}

/* Standart 38px Yükseklikte Inputlar */
#general-modal .input-group > .form-control {
    border-color: #cbd5e1 !important;
    font-size: 13px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    height: 38px !important;
    line-height: 1.5 !important;
    padding: 6px 12px !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
}

#general-modal .input-group textarea.form-control {
    min-height: 64px !important;
    max-height: none !important;
    height: auto !important;
}

/* Select2 Input Group Entegrasyonu */
#general-modal .input-group > .select2-container {
    flex: 1 1 auto !important;
    width: 1% !important;
    min-width: 0 !important;
    height: 38px !important;
}

#general-modal .input-group > .select2-container .select2-selection--single {
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    border-color: #cbd5e1 !important;
    font-size: 13px !important;
    display: flex !important;
    align-items: center !important;
    background-color: #ffffff !important;
    padding-left: 10px !important;
}

#general-modal .input-group > .select2-container .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    color: #1e293b !important;
    padding-left: 0 !important;
}

#general-modal .input-group > .select2-container .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
}

/* Tutar Alanı & Para Birimi Dropdown Hizalaması */
#general-modal #amount {
    border-top-right-radius: 0 !important;
    border-bottom-right-radius: 0 !important;
    border-right: 0 !important;
    font-size: 13.5px !important;
}

#general-modal .currency-select-wrapper {
    width: 85px !important;
    min-width: 85px !important;
    max-width: 85px !important;
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    flex-shrink: 0 !important;
}

#general-modal .currency-select-wrapper .select2-container {
    width: 100% !important;
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
}

#general-modal .currency-select-wrapper .select2-selection--single {
    height: 38px !important;
    min-height: 38px !important;
    max-height: 38px !important;
    border-top-left-radius: 0 !important;
    border-bottom-left-radius: 0 !important;
    border-top-right-radius: 8px !important;
    border-bottom-right-radius: 8px !important;
    background-color: #f8fafc !important;
    border-color: #cbd5e1 !important;
    font-size: 12.5px !important;
    font-weight: 600 !important;
    display: flex !important;
    align-items: center !important;
    padding-left: 10px !important;
    padding-right: 22px !important;
}

#general-modal .currency-select-wrapper .select2-selection--single .select2-selection__rendered {
    line-height: 36px !important;
    color: #334155 !important;
    padding-left: 0 !important;
}

#general-modal .currency-select-wrapper .select2-selection--single .select2-selection__arrow {
    height: 36px !important;
    right: 4px !important;
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
[data-bs-theme="dark"] #general-modal .input-group-text {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #94a3b8 !important;
}
[data-bs-theme="dark"] #general-modal .input-group > .form-control,
[data-bs-theme="dark"] #general-modal .input-group > .select2-container .select2-selection--single {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #general-modal .input-group > .select2-container .select2-selection--single .select2-selection__rendered {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #general-modal .currency-select-wrapper .select2-selection--single {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #general-modal .currency-select-wrapper .select2-selection--single .select2-selection__rendered {
    color: #f8fafc !important;
}
</style>