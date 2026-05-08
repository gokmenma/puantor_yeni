<?php
require_once "Model/RolesModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;
$roleObj = new Roles();

//Sayfa başlarında eklenecek alanlar
$perm->checkAuthorize("permission_group_add_update");
$id = isset($_GET["id"]) ? Security::decrypt($_GET['id']) : 0;
$new_id = isset($_GET["id"]) ? $_GET['id'] : 0;

//Eğer url'den id yazılmışsa veya id boş ise projeler sayfasına gider
if($id == null && isset($_GET['id'])) {
    header("Location: /index.php?p=users/roles/list");
    exit;
}

$roles = $roleObj->find($id);

$pageTitle = $id > 0 ? "Yetki Grubu Düzenle" : "Yeni Yetki Grubu";
?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <?php echo $pageTitle; ?>
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
                    <button type="button" class="btn btn-primary" id="rol_kaydet">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form action="" id="roleForm">
                            <div class="row mt-3">
                                <input type="hidden" class="form-control mb-3" id="role_id" value="<?php echo $new_id ?>">
                                <div class="col-md-2">
                                    <label class="form-label">Pozisyon Adı</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" class="form-control" name="role_name" value="<?php echo $roles->roleName ?? "" ?>">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-2">
                                    <label class="form-label">Pozisyon Açıklama</label>
                                </div>
                                <div class="col-md-10">
                                    <input type="text" class="form-control" name="role_description" value="<?php echo $roles->roleDescription ?? "" ?>">
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>