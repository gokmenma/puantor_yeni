<?php
$user_id = $_SESSION['user']->id;
require_once "Model/MyFirmModel.php";
require_once "App/Helper/security.php";
require_once "Model/Company.php";

use App\Helper\Security;

$perm->checkAuthorize("my_companies_page");
$Auths->checkFirmReturn();

$MyFirmModel = new MyFirmModel();
$myfirms = $MyFirmModel->getMyFirmByUserId();

$companyObj = new Company();
$owner_id = $_SESSION["user"]->parent_id == 0 ? $_SESSION["user"]->id : $_SESSION["user"]->parent_id;
$subDetails = $User->getActiveSubscriptionDetails($owner_id);
$current_firm_count = $companyObj->countMyFirms($owner_id);
$isSuperadmin = ($_SESSION["user"]->superadmin ?? 0) == 1;

$limitReached = !$isSuperadmin && ($current_firm_count >= $subDetails['firma_hakki']);
?>
<div class="container-xl mt-3">
    <?php if (isset($_GET['limit_reached']) && $_GET['limit_reached'] == 1): ?>
        <div class="alert alert-warning alert-dismissible mb-3" role="alert" style="border-radius: 12px; font-weight: 500;">
            <div class="d-flex align-items-center">
                <i class="ti ti-alert-triangle icon me-3" style="font-size: 1.5rem;"></i>
                <div>Paketinizin firma limiti dolduğu için yeni firma ekleme sayfasına erişiminiz engellenmiştir.</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    <?php endif; ?>

    <!-- Alert component'i dahil et -->
    <?php
    $title = "Firmalarım Listesi!";
    if ($isSuperadmin) {
        $text = "Sahip olduğunuz firmaları buradan yönetebilirsiniz. Sınırsız firma oluşturma hakkınız bulunmaktadır.";
    } else {
        $text = "Sahip olduğunuz firmaları buradan yönetebilirsiniz. Paketiniz kapsamında en fazla <strong>" . $subDetails['firma_hakki'] . "</strong> adet firma ekleyebilirsiniz. Şu anda <strong>" . $current_firm_count . "</strong> adet firma eklenmiş durumda. (Kullanılan: " . $current_firm_count . " / " . $subDetails['firma_hakki'] . ")";
    }
    require_once 'pages/components/alert.php'
    ?>
    <!-- Alert  -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Firmalarım Listesi</h3>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-icon me-2" data-tooltip="Excele Aktar">
                            <i class="ti ti-file-excel icon"></i>
                        </a>
                        <?php if ($limitReached): ?>
                            <button type="button" class="btn btn-primary btn-new-firm-limit" data-limit="<?php echo $subDetails['firma_hakki']; ?>">
                                <i class="ti ti-plus icon me-2"></i> Yeni
                            </button>
                        <?php else: ?>
                            <a href="#" class="btn btn-primary" id="btn-new-mycompany">
                                <i class="ti ti-plus icon me-2"></i> Yeni
                            </a>
                        <?php endif; ?>
                    </div>
                </div>


                <div class="table-responsive">
                    <table class="table card-table text-nowrap datatable">
                        <thead>
                            <tr>
                                <th style="width:7%">Sıra</th>
                                <th>Firma Adı</th>
                                <th style="width:10%">Telefon</th>
                                <th>Mail Adresi</th>
                                <th>Açıklama</th>
                                <th style="width:10%">Oluşturulma Tarihi</th>
                                <th style="width:7%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php 
                            $default_firm_id = (int)($_SESSION['user']->default_firm_id ?? 0);
                            $i = 1;
                            foreach ($myfirms as $myfirm):
                            $id = Security::encrypt($myfirm->id);
                            $isDefault = ((int)$myfirm->id === $default_firm_id);
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td>
                                        <a class="btn route-link text-primary fw-bold" data-page="mycompany/manage&id=<?php echo $id ?>" href="#">
                                            <?php echo htmlspecialchars($myfirm->firm_name, ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <?php if ($isDefault): ?>
                                            <span class="badge bg-amber-lt text-amber fw-medium ms-2" title="Varsayılan Firma">
                                                <i class="ti ti-star-filled text-warning me-1"></i> Varsayılan
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-start"><?php echo htmlspecialchars($myfirm->phone ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($myfirm->email ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($myfirm->description ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($myfirm->created_at ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown" data-bs-boundary="viewport">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link"
                                                    data-page="mycompany/manage&id=<?php echo $id ?>" href="#">
                                                    <i class="ti ti-eye icon me-3"></i> Firma Detayları
                                                </a>
                                                <?php if ($isDefault): ?>
                                                    <a class="dropdown-item btn-unset-default-firm text-muted"
                                                        data-id="<?php echo $id ?>" href="#">
                                                        <i class="ti ti-star-off icon me-3 text-secondary"></i> Varsayılanı Kaldır
                                                    </a>
                                                <?php else: ?>
                                                    <a class="dropdown-item btn-set-default-firm text-amber"
                                                        data-id="<?php echo $id ?>" href="#">
                                                        <i class="ti ti-star icon me-3 text-warning"></i> Varsayılan Yap
                                                    </a>
                                                <?php endif; ?>
                                                <a class="dropdown-item mycompany-edit-btn"
                                                    data-id="<?php echo $id ?>" href="#">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-mycompany"
                                                    data-id="<?php echo $id ?>" href="#">
                                                    <i class="ti ti-trash icon me-3"></i> Sil
                                                </a>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            <?php 
                            $i++;
                        endforeach; ?>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Yeni & Düzenleme Firma Modalı -->
<div class="modal modal-blur fade" id="mycompany-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-3 fw-bold text-primary" id="mycompany-modal-title">Yeni Firma Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="myFirmForm" enctype="multipart/form-data">
                <input type="hidden" name="id" id="myfirm_id" value="0">
                <input type="hidden" name="action" value="saveMyCompany">
                
                <div class="modal-body pt-2">
                    <!-- Bölüm 1: Temel Firma Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary-lt p-2 rounded-2 me-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
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
                                    <input type="text" class="form-control" name="firm_name" id="firm_name" placeholder="Firma adını giriniz" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Yetkili Adı</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="yetkili_adi" id="yetkili_adi" placeholder="Yetkili ad soyad" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 2: İletişim Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success-lt p-2 rounded-2 me-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
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

                    <!-- Bölüm 3: Vergi Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning-lt p-2 rounded-2 me-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="ti ti-file-text text-warning fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Vergi Bilgileri</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Vergi Dairesi</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-building-bank"></i>
                                    </span>
                                    <input type="text" class="form-control" name="vergi_dairesi" id="vergi_dairesi" placeholder="Vergi dairesi">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vergi Numarası</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-file-text"></i>
                                    </span>
                                    <input type="text" class="form-control" name="vergi_no" id="vergi_no" placeholder="Vergi no">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 4: Logo ve Açıklama -->
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info-lt p-2 rounded-2 me-2" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                <i class="ti ti-photo text-info fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Logo ve Açıklama</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Açıklama</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-notes"></i>
                                    </span>
                                    <input type="text" class="form-control" name="description" id="description" placeholder="Firma açıklaması">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Firma Logosu</label>
                                <input type="file" class="form-control" name="brand_logo" id="brand_logo" onchange="previewImage(event)">
                            </div>
                            <div class="col-md-2 text-center d-flex align-items-end justify-content-center">
                                <div class="brand-img border rounded p-1" style="width: 64px; height: 64px; display: flex; align-items: center; justify-content: center; overflow: hidden; background-color: #f8fafc;">
                                    <img src="" id="logo-preview-img" style="max-width: 100%; max-height: 100%; object-fit: contain; display: none;" alt="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-lt border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" id="saveMyFirm">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Modal responsive and styling improvements */
#mycompany-modal .modal-content {
    border-radius: 1.25rem;
    overflow: hidden;
}
#mycompany-modal .form-label.required:after {
    content: " *";
    color: #d63f3f;
}
#mycompany-modal .input-icon-addon {
    color: #94a3b8;
}
#mycompany-modal .form-control:focus {
    border-color: #206bc4;
    box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, 0.15);
}
#mycompany-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
#mycompany-modal .modal-body::-webkit-scrollbar {
    width: 6px;
}
#mycompany-modal .modal-body::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
#mycompany-modal .modal-body::-webkit-scrollbar-track {
    background: transparent;
}
</style>