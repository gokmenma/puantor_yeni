<?php

require_once 'Model/Auths.php';
require_once 'Model/RolesModel.php';
require_once 'Model/RoleAuthsModel.php';
require_once 'App/Helper/security.php';
use App\Helper\Security;
$authObj = new Auths();
$roleObj = new Roles();
$roleAuthsObj = new RoleAuthsModel();
ob_start(); // Çıktı tamponlamasını başlatın



$id = Security::decrypt($_GET['id']) ?? 0;
// echo "manuel yazılan id :" . $id;
if (!isset($_GET['id']) || $id == 0) {
    header('Location: index.php?p=users/roles/list');
    exit();
}




$auths = $authObj->auths();
$role = $roleObj->find($id);


$roleAuths = $roleAuthsObj->getAuthsByRoleId($id);//Güncelleme yapılacak 
$auth_id = Security::encrypt($roleAuths->id) ?? 0;



//Yetki kontrolü yapılır
$perm->checkAuthorize( "transaction_permissions");


?>

<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Yetkileri Düzenle
                    </h2>



                </div>

                <!-- Page title actions -->
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="users/roles/list">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="authsSave">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .accordion-button:not(.collapsed) {
            background-color: transparent !important;
            color: inherit !important;
            box-shadow: none !important;
        }
        .accordion-button::after {
            background-size: 1rem;
            transition: transform 0.3s ease;
        }
        .accordion-item {
            transition: all 0.3s ease;
            border: 1px solid rgba(0,0,0,.05) !important;
        }
        .accordion-item:hover {
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,.08) !important;
        }
        .form-selectgroup-label {
            transition: all 0.2s ease;
            border-radius: 8px;
        }
        .form-selectgroup-input:checked + .form-selectgroup-label {
            border-color: var(--tblr-primary) !important;
            background-color: rgba(var(--tblr-primary-rgb), 0.03);
        }
        .selection-counter {
            min-width: 60px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .bg-light-lt {
            background-color: #f8fafc !important;
        }
        .accordion-body {
            max-height: 600px;
            overflow-y: auto;
            scrollbar-width: thin;
        }
    </style>
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $role->roleName ?? ''; ?></h3>
                    <div class="col ms-5 me-5">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search icon text-primary"></i>
                            </span>
                            <input type="text" id="authSearch" class="form-control form-control-rounded" placeholder="Yetki veya kategori ara...">
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex align-items-center">
                        <div class="btn-group me-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="expandAll">
                                <i class="ti ti-arrows-maximize icon me-1"></i> Tümünü Aç
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="collapseAll">
                                <i class="ti ti-arrows-minimize icon me-1"></i> Tümünü Kapat
                            </button>
                        </div>
                        <!-- Tümünü seç -->
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                            <label class="form-check-label fw-bold" for="checkAll">Tümünü Seç</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form action="" id="authsForm">
                        <div class="row d-none">
                            <?php 
                            $csrf_token = Security::csrf();
                            ?>
                            <input type="text" name="role_id" class="form-control" value="<?php echo $role->id; ?>">
                            <input type="text" name="action" class="form-control" value="saveAuths">
                            <input type="text" name="auth_id" id="auth_id" class="form-control"
                                value="<?php echo $auth_id; ?>">
                            <input type="text" name="csrf_token" class="form-control"
                                value="<?php echo $csrf_token; ?>">
                        </div>

                        <div class="accordion" id="accordion-auths">
                            <?php
                            // Mevcut yetkileri bir diziye al
                            $auth_ids = $roleAuths->auth_ids ?? '';
                            $auth_id_array = array_filter(explode(',', $auth_ids));
                            
                            foreach ($auths as $auth) {
                                $sub_auths = $authObj->subAuths($auth->id);
                                
                                // Alt yetkilerin kaç tanesi seçili hesapla
                                $checked_count = 0;
                                foreach ($sub_auths as $sub) {
                                    if (in_array($sub->id, $auth_id_array)) {
                                        $checked_count++;
                                    }
                                }
                                
                                // Ana yetki seçili mi?
                                $main_checked = in_array($auth->id, $auth_id_array) ? 'checked' : '';
                                ?>

                                <div class="accordion-item mb-3 border-0 shadow-sm rounded-3 overflow-hidden">
                                    <div class="accordion-header d-flex align-items-center bg-white" id="heading-<?php echo $auth->id; ?>">
                                        <div class="form-check mb-0 me-2 ms-3">
                                            <input class="form-check-input main-category-check" type="checkbox" 
                                                   name="auths[]" value="<?php echo $auth->id; ?>" 
                                                   id="main_auth_<?php echo $auth->id; ?>" <?php echo $main_checked; ?>
                                                   data-group="group-<?php echo $auth->id; ?>">
                                        </div>
                                        <button class="accordion-button collapsed flex-fill py-3 px-2 bg-transparent text-dark shadow-none" 
                                                type="button" data-bs-toggle="collapse" 
                                                data-bs-target="#collapse-<?php echo $auth->id; ?>" 
                                                aria-expanded="false" 
                                                aria-controls="collapse-<?php echo $auth->id; ?>">
                                            <div class="d-flex flex-column text-start">
                                                <div class="h4 mb-0 fw-bold"><?php echo $auth->title; ?></div>
                                                <div class="text-muted small"><?php echo $auth->description; ?></div>
                                            </div>
                                            <div class="ms-auto me-3 d-flex align-items-center">
                                                <span class="badge bg-primary-lt px-2 py-1 rounded-pill fw-medium selection-counter" 
                                                      id="counter-<?php echo $auth->id; ?>"
                                                      data-total="<?php echo count($sub_auths); ?>">
                                                    <?php echo $checked_count; ?> / <?php echo count($sub_auths); ?>
                                                </span>
                                            </div>
                                        </button>
                                    </div>
                                    <div id="collapse-<?php echo $auth->id; ?>" class="accordion-collapse collapse" 
                                         aria-labelledby="heading-<?php echo $auth->id; ?>">
                                        <div class="accordion-body bg-light-lt pt-0 border-top">
                                            <div class="row g-3 mt-1 group-<?php echo $auth->id; ?>">
                                                <?php
                                                foreach ($sub_auths as $sub_auth) {
                                                    $sub_checked = in_array($sub_auth->id, $auth_id_array) ? 'checked' : '';
                                                    ?>
                                                    <div class="col-12 col-md-6 col-lg-4">
                                                        <label class="form-selectgroup-item w-100">
                                                            <input type="checkbox" name="auths[]" <?php echo $sub_checked; ?>
                                                                   value="<?php echo $sub_auth->id; ?>"
                                                                   class="form-selectgroup-input sub-auth-check"
                                                                   data-parent-counter="counter-<?php echo $auth->id; ?>"
                                                                   data-parent-main="main_auth_<?php echo $auth->id; ?>">
                                                            <div class="form-selectgroup-label d-flex align-items-center p-3 bg-white border-2">
                                                                <div class="me-3">
                                                                    <span class="form-selectgroup-check"></span>
                                                                </div>
                                                                <div class="form-selectgroup-label-content d-flex text-start">
                                                                    <div>
                                                                        <div class="font-weight-bold text-dark mb-1">
                                                                            <?php echo $sub_auth->title; ?>
                                                                        </div>
                                                                        <div class="text-muted small lh-sm">
                                                                            <?php echo $sub_auth->description; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </label>
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>