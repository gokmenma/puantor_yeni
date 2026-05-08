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
        .form-check-label {
            display: inline-block;
            white-space: nowrap;
        }

        /* .datagrid-item{
            max-height: 800px;
            overflow: auto;
            scrollbar-width: thin;
        } */
    </style>
    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?php echo $role->roleName ?? ''; ?></h3>
                    <div class="col-auto ms-auto">
                        <!-- Tümünü seç -->
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                            <label class="form-check-label" for="checkAll">Tümünü Seç</label>

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

                        <div class="row g-2 mt-3">
                            <div class="datagrid">
                                <?php
                                foreach ($auths as $auth) {
                                    // role_id ile auths_id'leri getir
                                    $auth_ids = $roleAuths->auth_ids ?? '';
                                    // auths_ids içinde varsa checked yap
                                    $checked = in_array($auth->id, explode(',', $auth_ids)) ? 'checked' : '';
                                    ?>

                                    <div class="datagrid-item">
                                        <div class="datagrid-title font-weight-900 mb-3">
                                            <label class="form-check main-check">
                                                <input class="form-check-input main" name="auths[]"
                                                    value="<?php echo $auth->id; ?>" type="checkbox" <?php echo $checked; ?>
                                                    id="auth_<?php echo $auth->id; ?>">
                                                <span class="form-check-label"
                                                    data-tooltip="<?php echo $auth->description; ?> "><?php echo $auth->title; ?>
                                                </span>
                                            </label>
                                        </div>
                                        <div class="datagrid-content">
                                            <?php
                                            $sub_auths = $authObj->subAuths($auth->id);
                                            foreach ($sub_auths as $sub_auth) {
                                                $checked = in_array($sub_auth->id, explode(',', $auth_ids)) ? 'checked' : '';
                                                ?>
                                                <div class="form-color">
                                                    <div>

                                                        <label class="form-selectgroup-item flex-fill mb-2">
                                                            <input type="checkbox" name="auths[]" <?php echo $checked; ?>
                                                                value="<?php echo $sub_auth->id; ?>"
                                                                class="form-selectgroup-input">
                                                            <div class="form-selectgroup-label d-flex align-items-center p-3">
                                                                <div class="me-3">
                                                                    <span class="form-selectgroup-check"></span>
                                                                </div>
                                                                <div class="form-selectgroup-label-content d-flex text-start">

                                                                    <div>
                                                                        <div class="font-weight-500">
                                                                            <?php echo $sub_auth->title; ?>
                                                                        </div>
                                                                        <div class="form-check-description">
                                                                            <?php echo $sub_auth->description; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </label>


                                                    </div>

                                                </div>

                                            <?php } ?>
                                        </div>

                                    </div>

                                <?php } ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>