<?php

require_once "Model/Company.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/cities.php";

use App\Helper\Helper;
use App\Helper\Security;

$helper = new Helper();
$cities = new Cities();


$companyObj = new Company();
$companies = $companyObj->allWithUserId();

?>

<style>

</style>
<div class="container-xl">
     <!-- Alert component'i dahil et -->
     <?php
        $title = "Firma Listesi!";
        $text = "Çalıştığınız firmaları buradan yönetebilirsiniz.";
        require_once 'pages/components/alert.php'
    ?>
    <!-- Alert  -->
    <div class="row row-deck row-cards">

        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="col-auto">

                        <h2 class="card-title">Firma Listesi </h2>
                    </div>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-icon me-2" data-tooltip="Excele Aktar">
                            <i class="ti ti-file-excel icon"></i>
                        </a>
                        <!-- Firma Ekleme ve güncelleme yetkisi -->
                        <?php if ($Auths->hasPermission('company_add_update')) { ?>
                            <a href="#" class="btn btn-primary" id="btn-new-company">
                                <i class="ti ti-plus icon me-2"></i> Yeni
                            </a>
                        <?php } ?>
                    
                    </div>
                </div>

                <div class="table-responsive" style="overflow:auto">
                    <table class="table card-table table-hover datatable ">
                        <thead>
                            <tr>
                                <th style="width:7%">id</th>
                                <th>Firma Adı</th>
                                <th>Yetkili</th>
                                <th style="width:10%">Şehir</th>
                                <th style="width:10%">İlçe</th>
                                <th style="width:10%">Telefon</th>
                                <th style="width:15%">Email</th>
                                <th>Adres</th>
                                <th style="width:7%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>

                            <?php 
                            $i = 0;
                            foreach ($companies as $company) { 
                                $i++;
                                $id =Security::encrypt($company->id);
                                ?>
                                <tr>
                                    <td class="text-center"><span class="text-secondary"><?php echo $i; ?></span></td>
                                    <td>
                                        <a href="#" class="nav-item route-link text-primary fw-bold"
                                            data-page="companies/manage&id=<?php echo $id ?>">
                                            <?php echo Helper::short($company->company_name, 32) ?>
                                        </a>
                                    </td>

                                    <td>
                                        <?php echo $company->yetkili; ?>
                                    </td>
                                    <td>
                                        <?php echo $cities->getCityName($company->city); ?>
                                    </td>
                                    <td>
                                        <?php echo $cities->getTownName($company->town); ?>
                                    </td>
                                    <td>
                                        <?php echo $company->phone; ?>
                                    </td>
                                    <td>
                                        <?php echo $company->email; ?>
                                    </td>
                                    <td>
                                        <?php echo $company->address; ?>
                                    </td>


                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown" data-bs-boundary="viewport">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link"
                                                    data-page="companies/manage&id=<?php echo $id ?>" href="#">
                                                    <i class="ti ti-eye icon me-3"></i> Firma Detayları
                                                </a>
                                                <a class="dropdown-item company-edit-btn"
                                                    data-id="<?php echo $id ?>" href="#">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-company" data-id="<?php echo $id ?>"
                                                    href="#">
                                                    <i class="ti ti-trash icon me-3"></i> Sil
                                                </a>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            <?php } ?>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Yeni & Düzenleme Firma Modalı -->
<div class="modal modal-blur fade" id="company-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-3 fw-bold text-primary" id="company-modal-title">Yeni Firma Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="companyForm">
                <input type="hidden" name="id" id="company_id" value="0">
                <input type="hidden" name="action" value="saveCompany">
                
                <div class="modal-body pt-2">
                    <!-- Bölüm 1: Temel Firma Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary-lt p-2 rounded-2 me-2">
                                <i class="ti ti-info-circle text-primary fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Temel Firma Bilgileri</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Firma Adı</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-building"></i>
                                    </span>
                                    <input type="text" class="form-control" name="company_name" id="company_name" placeholder="Firma adını giriniz" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Yetkilisi</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="yetkili" id="yetkili" placeholder="Yetkili ad soyad">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 2: İletişim Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success-lt p-2 rounded-2 me-2">
                                <i class="ti ti-phone text-success fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">İletişim Bilgileri</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Telefon</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-phone"></i>
                                    </span>
                                    <input type="text" class="form-control" name="phone" id="phone" placeholder="Telefon numarası">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-posta</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-mail"></i>
                                    </span>
                                    <input type="email" class="form-control" name="email" id="email" placeholder="E-posta adresi">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 3: Vergi ve Hesap Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning-lt p-2 rounded-2 me-2">
                                <i class="ti ti-wallet text-warning fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Vergi ve Hesap Bilgileri</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Vergi Dairesi</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-building-bank"></i>
                                    </span>
                                    <input type="text" class="form-control" name="tax_office" id="tax_office" placeholder="Vergi dairesi">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vergi Numarası</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-file-text"></i>
                                    </span>
                                    <input type="text" class="form-control" name="tax_number" id="tax_number" placeholder="Vergi no">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hesap Numarası</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-credit-card"></i>
                                    </span>
                                    <input type="text" class="form-control" name="account_number" id="account_number" placeholder="Hesap no">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 4: Konum ve Diğer Bilgiler -->
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info-lt p-2 rounded-2 me-2">
                                <i class="ti ti-map-2 text-info fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Konum ve Diğer Bilgiler</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Şehir</label>
                                <?php echo $cities->citySelect("firm_cities", null); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">İlçe</label>
                                <select name="firm_towns" id="firm_towns" class="form-control select2" style="width:100%">
                                    <option value="">İlçe Seçiniz</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Açıklama</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-notes"></i>
                                    </span>
                                    <input type="text" class="form-control" name="description" id="description" placeholder="Açıklama giriniz">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Adres</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-map-pin"></i>
                                    </span>
                                    <input type="text" class="form-control" name="address" id="address" placeholder="Firma adresi">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-lt border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary px-4 shadow-sm" id="saveCompany">
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
#company-modal .modal-content {
    border-radius: 1.25rem;
    overflow: hidden;
}
#company-modal .form-label.required:after {
    content: " *";
    color: #d63f3f;
}
#company-modal .bg-primary-lt, #company-modal .bg-success-lt, #company-modal .bg-warning-lt, #company-modal .bg-info-lt {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
#company-modal .input-icon-addon {
    color: #94a3b8;
}
#company-modal .form-control:focus {
    border-color: #206bc4;
    box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, 0.15);
}
#company-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
/* Scrollbar özelleştirme */
#company-modal .modal-body::-webkit-scrollbar {
    width: 6px;
}
#company-modal .modal-body::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
#company-modal .modal-body::-webkit-scrollbar-track {
    background: transparent;
}
</style>