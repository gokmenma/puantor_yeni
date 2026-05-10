<?php
require_once ROOT . "/App/Helper/company.php";
require_once ROOT . "/App/Helper/projects.php";
require_once ROOT . '/App/Helper/cities.php';

$companyHelper = new CompanyHelper();
$projectHelper = new ProjectHelper();
$cityHelper = new Cities();
?>
<div class="modal modal-blur fade" id="projectModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-3 fw-bold text-primary" id="projectModalTitle">Yeni Proje Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="projectForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="saveProject">
                <input type="hidden" name="id" id="modal_project_id" value="0">
                <div class="modal-body pt-2">
                    
                    <!-- Bölüm 1: Temel Proje Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary-lt p-2 rounded-2 me-2">
                                <i class="ti ti-info-circle text-primary fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Temel Proje Bilgileri</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Proje Türü</label>
                                <div class="form-selectgroup w-100">
                                    <label class="form-selectgroup-item flex-fill">
                                        <input type="radio" name="project_type" value="1" class="form-selectgroup-input" checked>
                                        <span class="form-selectgroup-label py-2">
                                            <i class="ti ti-arrow-down-left text-success me-1"></i> Alınan
                                        </span>
                                    </label>
                                    <label class="form-selectgroup-item flex-fill">
                                        <input type="radio" name="project_type" value="2" class="form-selectgroup-input">
                                        <span class="form-selectgroup-label py-2">
                                            <i class="ti ti-arrow-up-right text-danger me-1"></i> Verilen
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Proje Durumu</label>
                                <?php echo $projectHelper->projectStatusSelect("project_status", ''); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Proje Adı</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-building"></i>
                                    </span>
                                    <input type="text" class="form-control" name="project_name" placeholder="Proje adını giriniz">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Yüklenici Firması</label>
                                <?php echo $companyHelper->getCompanySelect("project_company", ''); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 2: Tarih ve Bütçe -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success-lt p-2 rounded-2 me-2">
                                <i class="ti ti-calendar-stats text-success fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Tarih ve Bütçe</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Başlangıç Tarihi</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar"></i>
                                    </span>
                                    <input type="text" class="form-control flatpickr" name="start_date" placeholder="d.m.Y">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Tahmini Bitiş Tarihi</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-calendar-event"></i>
                                    </span>
                                    <input type="text" class="form-control flatpickr" name="end_date" placeholder="d.m.Y">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Proje Bedeli</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-currency-lira"></i>
                                    </span>
                                    <input type="text" class="form-control money" name="budget" value="0">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 3: Konum ve İletişim -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning-lt p-2 rounded-2 me-2">
                                <i class="ti ti-map-2 text-warning fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Konum ve İletişim</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Şehir</label>
                                <?php echo $cityHelper->citySelect("project_city", '') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">İlçe</label>
                                <select class="form-control select2" name="project_town" id="modal_project_town" style="width:100%">
                                    <option value="">İlçe seçiniz</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-posta</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-mail"></i>
                                    </span>
                                    <input type="email" class="form-control" name="email" placeholder="ornek@mail.com">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Telefon</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-phone"></i>
                                    </span>
                                    <input type="text" class="form-control" name="phone" placeholder="05XX XXX XX XX">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 4: Ek Bilgiler -->
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info-lt p-2 rounded-2 me-2">
                                <i class="ti ti-notes text-info fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Ek Bilgiler ve Dosyalar</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Hesap Numarası / IBAN</label>
                                <input type="text" class="form-control" name="account_number" placeholder="TR00...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Sözleşme Dosyası</label>
                                <input type="file" class="form-control" name="project_file">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Açık Adres</label>
                                <textarea class="form-control" name="address" rows="2" placeholder="Mahalle, sokak, no..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label">Proje Notları</label>
                                <textarea class="form-control" name="project" rows="2" placeholder="Proje hakkında önemli notlar..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-lt border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal tasarım iyileştirmeleri */
#projectModal .modal-content {
    border-radius: 1.25rem;
    overflow: hidden;
}
#projectModal .form-label.required:after {
    content: " *";
    color: #d63f3f;
}
#projectModal .bg-primary-lt, #projectModal .bg-success-lt, #projectModal .bg-warning-lt, #projectModal .bg-info-lt {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
#projectModal .input-icon-addon {
    color: #94a3b8;
}
#projectModal .form-control:focus {
    border-color: #206bc4;
    box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, 0.15);
}
#projectModal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
}
/* Scrollbar özelleştirme */
#projectModal .modal-body::-webkit-scrollbar {
    width: 6px;
}
#projectModal .modal-body::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
#projectModal .modal-body::-webkit-scrollbar-track {
    background: transparent;
}
</style>
