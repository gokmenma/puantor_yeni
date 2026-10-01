<?php
require_once ROOT . "/App/Helper/company.php";
require_once ROOT . "/App/Helper/projects.php";
require_once ROOT . '/App/Helper/cities.php';

$companyHelper = new CompanyHelper();
$projectHelper = new ProjectHelper();
$cityHelper = new Cities();
?>
<div class="modal modal-blur fade" id="projectModal" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
            
            <!-- Modal Header -->
            <div class="modal-header py-3 px-4 bg-white border-bottom align-items-center">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-none" style="width: 42px; height: 42px;">
                        <i class="ti ti-building-community" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="projectModalTitle" style="font-size: 1.15rem; letter-spacing: -0.2px;">
                            Yeni Proje Ekle
                        </h4>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Proje genel bilgileri, tarih, bütçe, lokasyon ve ek ayarlar
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <form id="projectForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="saveProject">
                <input type="hidden" name="id" id="modal_project_id" value="0">
                
                <!-- Nav Tabs Navigation -->
                <div class="px-4 pt-3 pb-0 bg-light-subtle border-bottom">
                    <ul class="nav nav-tabs nav-tabs-alt border-0 gap-2" id="projectModalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active py-2 px-3 fw-semibold d-flex align-items-center gap-2" id="tab-project-general-btn" data-bs-toggle="tab" data-bs-target="#tab-project-general" type="button" role="tab" aria-selected="true">
                                <i class="ti ti-file-analytics text-primary" style="font-size: 17px;"></i>
                                <span>Genel & Finansal</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 px-3 fw-semibold d-flex align-items-center gap-2" id="tab-project-location-btn" data-bs-toggle="tab" data-bs-target="#tab-project-location" type="button" role="tab" aria-selected="false">
                                <i class="ti ti-map-pin text-warning" style="font-size: 17px;"></i>
                                <span>Konum & İletişim</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link py-2 px-3 fw-semibold d-flex align-items-center gap-2" id="tab-project-extra-btn" data-bs-toggle="tab" data-bs-target="#tab-project-extra" type="button" role="tab" aria-selected="false">
                                <i class="ti ti-adjustments-horizontal text-info" style="font-size: 17px;"></i>
                                <span>Notlar & Ayarlar</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <!-- Modal Body with Tab Panes -->
                <div class="modal-body p-4 bg-white" style="min-height: 380px;">
                    <div class="tab-content" id="projectModalTabContent">
                        
                        <!-- TAB 1: Genel & Finansal Bilgiler -->
                        <div class="tab-pane fade show active" id="tab-project-general" role="tabpanel" aria-labelledby="tab-project-general-btn">
                            <div class="row g-3">
                                <!-- Proje Türü -->
                                <div class="col-md-6">
                                    <label class="form-label required fw-semibold" style="font-size: 13px;">Proje Türü</label>
                                    <div class="form-selectgroup w-100">
                                        <label class="form-selectgroup-item flex-fill">
                                            <input type="radio" name="project_type" value="1" class="form-selectgroup-input" checked>
                                            <span class="form-selectgroup-label py-2 d-flex align-items-center justify-content-center gap-1.5">
                                                <i class="ti ti-arrow-down-left text-success" style="font-size: 16px;"></i>
                                                <span class="fw-semibold">Alınan Proje</span>
                                            </span>
                                        </label>
                                        <label class="form-selectgroup-item flex-fill">
                                            <input type="radio" name="project_type" value="2" class="form-selectgroup-input">
                                            <span class="form-selectgroup-label py-2 d-flex align-items-center justify-content-center gap-1.5">
                                                <i class="ti ti-arrow-up-right text-danger" style="font-size: 16px;"></i>
                                                <span class="fw-semibold">Verilen Proje</span>
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Proje Durumu -->
                                <div class="col-md-6">
                                    <label class="form-label required fw-semibold" style="font-size: 13px;">Proje Durumu</label>
                                    <?php echo $projectHelper->projectStatusSelect("project_status", ''); ?>
                                </div>

                                <!-- Proje Adı -->
                                <div class="col-md-6">
                                    <label class="form-label required fw-semibold" style="font-size: 13px;">Proje Adı</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-building text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" name="project_name" placeholder="Örn: Kuzey Plaza İnşaatı" required autocomplete="off">
                                    </div>
                                </div>

                                <!-- Yüklenici Firma -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Yüklenici / Müşteri Firması</label>
                                    <?php echo $companyHelper->getCompanySelect("project_company", ''); ?>
                                </div>

                                <!-- Başlangıç Tarihi -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Başlangıç Tarihi</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control flatpickr" name="start_date" placeholder="GG.AA.YYYY" autocomplete="off">
                                    </div>
                                </div>

                                <!-- Tahmini Bitiş Tarihi -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Tahmini Bitiş Tarihi</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar-event text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control flatpickr" name="end_date" placeholder="GG.AA.YYYY" autocomplete="off">
                                    </div>
                                </div>

                                <!-- Proje Bedeli -->
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Sözleşme / Proje Bedeli</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-currency-lira text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control money" name="budget" value="0" placeholder="0,00">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: Konum & İletişim -->
                        <div class="tab-pane fade" id="tab-project-location" role="tabpanel" aria-labelledby="tab-project-location-btn">
                            <div class="row g-3">
                                <!-- Şehir -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Şehir / İl</label>
                                    <?php echo $cityHelper->citySelect("project_city", '') ?>
                                </div>

                                <!-- İlçe -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">İlçe</label>
                                    <select class="form-control select2" name="project_town" id="modal_project_town" style="width:100%">
                                        <option value="">İlçe seçiniz</option>
                                    </select>
                                </div>

                                <!-- Telefon -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Şantiye / İletişim Telefonu</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-phone text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" name="phone" placeholder="05XX XXX XX XX" autocomplete="off">
                                    </div>
                                </div>

                                <!-- E-posta -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">İletişim E-posta</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-mail text-muted"></i>
                                        </span>
                                        <input type="email" class="form-control" name="email" placeholder="proje@firma.com" autocomplete="off">
                                    </div>
                                </div>

                                <!-- Açık Adres -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Açık Adres / Lokasyon Tarifi</label>
                                    <textarea class="form-control" name="address" rows="3" placeholder="Mahalle, cadde, sokak, kapı no, şantiye lokasyon bilgisi..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: Ek Bilgiler & Ayarlar -->
                        <div class="tab-pane fade" id="tab-project-extra" role="tabpanel" aria-labelledby="tab-project-extra-btn">
                            <div class="row g-3">
                                <!-- IBAN -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Proje Banka Hesabı / IBAN</label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-credit-card text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control" name="account_number" placeholder="TR00 0000 0000 0000 0000 0000 00" autocomplete="off">
                                    </div>
                                </div>

                                <!-- Sözleşme Dosyası -->
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Sözleşme / Teknik Dosya</label>
                                    <input type="file" class="form-control" name="project_file" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg">
                                </div>

                                <!-- Proje Notları -->
                                <div class="col-12">
                                    <label class="form-label fw-semibold" style="font-size: 13px;">Proje Notları & Açıklamalar</label>
                                    <textarea class="form-control" name="project" rows="3" placeholder="Proje hakkında önemli hususlar, özel şartlar, taahhütler..."></textarea>
                                </div>

                                <!-- Dashboard Ayarı Kartı -->
                                <div class="col-12">
                                    <div class="card border bg-light-subtle shadow-none mb-0" style="border-radius: 10px;">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2.5">
                                                    <div class="avatar avatar-sm rounded-2 bg-azure-lt text-azure">
                                                        <i class="ti ti-layout-dashboard" style="font-size: 18px;"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 13px;">Dashboard Varsayılan Gantt Gösterimi</div>
                                                        <div class="text-muted small" style="font-size: 11.5px;">Ana sayfada Gantt şemasında varsayılan olarak bu proje özetlensin mi?</div>
                                                    </div>
                                                </div>
                                                <label class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_home_gantt" id="modal_is_home_gantt" value="1" style="cursor: pointer; width: 38px; height: 20px;">
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer py-2.5 px-4 bg-light-subtle border-top d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link link-secondary px-2 text-decoration-none" data-bs-dismiss="modal">
                        Vazgeç
                    </button>
                    <div class="d-flex align-items-center gap-2">
                        <button type="submit" class="btn btn-primary px-4 shadow-sm fw-semibold" id="btnSaveProject">
                            <i class="ti ti-device-floppy me-1.5" style="font-size: 16px;"></i>
                            Değişiklikleri Kaydet
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>

<style>
/* Project Modal Özel Stilleri */
#projectModal .modal-content {
    border-radius: 14px;
}
#projectModal .form-label.required:after {
    content: " *";
    color: #d63f3f;
    font-weight: bold;
}
#projectModal .nav-tabs-alt .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    color: #64748b;
    border-radius: 0;
    transition: all 0.2s ease;
    background: transparent;
}
#projectModal .nav-tabs-alt .nav-link:hover {
    color: #1e293b;
    border-bottom-color: #cbd5e1;
}
#projectModal .nav-tabs-alt .nav-link.active {
    color: #206bc4;
    font-weight: 700 !important;
    border-bottom-color: #206bc4;
    background: transparent;
}
#projectModal .form-control:focus,
#projectModal .form-select:focus {
    border-color: #206bc4;
    box-shadow: 0 0 0 0.2rem rgba(32, 107, 196, 0.15);
}
#projectModal .select2-container .select2-selection--single {
    height: 36px !important;
    padding: 4px 8px;
    border: 1px solid #d9dbde;
    border-radius: 6px;
}
#projectModal .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 26px !important;
    color: #1e293b;
    font-size: 13px;
}
#projectModal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 34px !important;
}
#projectModal .input-icon-addon {
    min-width: 2.25rem;
}
</style>
