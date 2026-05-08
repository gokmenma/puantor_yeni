<?php
require_once "Model/RolesModel.php";
require_once "App/Helper/security.php";



use App\Helper\Security;
$roleObj = new Roles();
$roles = $roleObj->getRolesByFirm($firm_id);
$perm->checkAuthorize("permission_groups");


?>
<div class="container-xl mt-3">
    <div class="alert alert-info bg-white alert-dismissible" role="alert">
        <div class="d-flex">
            <div>
                <!-- Download SVG icon from http://tabler-icons.io/i/info-circle -->
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon alert-icon">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                    <path d="M12 9h.01"></path>
                    <path d="M11 12h1v4h1"></path>
                </svg>
            </div>
            <div>
                <h4 class="alert-title">Rol Listesi!</h4>
                <div class="text-secondary">Seçili firma için dilediğiniz kadar rol ekleyebilir ve kullanıcılara bu
                    rolleri atayabilirsiniz.
                </div>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>

    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Yetki Grupları</h3>
                    <div class="col-auto ms-auto">

                        <?php if ($Auths->hasPermission('permission_group_add_update')) { ?>
                            <a href="#" class="btn btn-primary route-link" data-page="users/roles/manage">
                                <i class="ti ti-plus icon me-2"></i> Yeni
                            </a>
                        <?php } ?>


                    </div>
                </div>

                <div class="table-responsive">
                    <table id="roleTable" class="table card-table table-responsive text-nowrap datatable">
                        <thead>
                            <tr>
                                <th style="width:7%">Sıra</th>
                                <th style="width:27%">Pozisyon Adı</th>
                                <th>Açıklama</th>
                                <th style="width:7%">Durumu</th>
                                <th style="width:7%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 1;
                            if ($Auths->checkFirm()) {
                                foreach ($roles as $role):
                                    $id = Security::encrypt($role->id);
                                    ?>
                                    <tr>
                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td><?php echo $role->roleName; ?></td>
                                        <td><?php echo $role->roleDescription; ?></td>
                                        <td><?php echo $role->isActive; ?></td>

                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn dropdown-toggle align-text-top"
                                                    data-bs-toggle="dropdown">İşlem</button>
                                                <div class="dropdown-menu dropdown-menu-end">

                                                    <!-- İşlem Yetkilerini düzenleme ve başka bir yetki grubuna kopyalama işlemleri  -->

                                                    <?php if ($Auths->hasPermission('transaction_permissions')) { ?>
                                                        <a class="dropdown-item route-link"
                                                            data-page="users/auths/auths&id=<?php echo $id ?>" href="#">
                                                            <i class="ti ti-lock icon me-3"></i> Yetkileri Düzenle
                                                        </a>
                                                        <!-- Başka yetkilerin ana kullanıcı rölüne kopyalanmasını engellemek için -->
                                                        <?php if ($role->main_role != 1) { ?>
                                                            <a class="dropdown-item copy-roles" data-bs-toggle="modal"
                                                                data-id="<?php echo $id ?>" data-name="<?php echo $role->roleName ?>"
                                                                data-bs-target="#modal-small" href="#">
                                                                <i class="ti ti-copy icon me-3"></i> Yetkileri Kopyala
                                                            </a>
                                                        <?php } ?>
                                                    <?php } ?>

                                                    <!-- Yetki grubunu güncelleme işlemleri -->
                                                    <?php if ($Auths->hasPermission('permission_group_add_update')) { ?>
                                                        <a class="dropdown-item route-link"
                                                            data-page="users/roles/manage&id=<?php echo $id ?>" href="#">
                                                            <i class="ti ti-edit icon me-3"></i> Güncelle
                                                        </a>
                                                    <?php } ?>

                                                    <?php if ($role->main_role != 1) { ?>
                                                        <!-- Yetki grubunu silme işlemleri -->
                                                        <?php if ($Auths->hasPermission('permission_group_delete')) { ?>
                                                            <a class="dropdown-item delete_role" href="#" data-id="<?php echo $id ?>">
                                                                <i class="ti ti-trash icon me-3"></i> Sil
                                                            </a>
                                                        <?php } ?>
                                                    <?php } ?>

                                                </div>

                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                    $i++;
                                endforeach;
                            } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modal-small" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <form action="" id="copyRoleForm">
                <input type="hidden" id="copy_role_id" name="copy_role_id" class="form-control">
                <input type="hidden" name="action" value="copyRolesModal">

                <div class="modal-body">
                    <div class="modal-title">Emin misiniz?</div>
                    <div><strong id="role_name">Admin</strong> İsimli yetki grubuna aşağıdaki yetki grubunun yetkileri
                        kopyalanacaktır!
                    </div>
                    <div class="col mt-5 ">
                        <label class="form-label">Yetkileri Kopyalanacak Grubu Seçin</label>
                        <select name="role_to_copy" id="role_to_copy" class="form-control select2" style="width:100%">
                        </select>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto"
                        data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" id="copy_roles" class="btn btn-danger" data-bs-dismiss="modal">Evet,
                        Kopyala!
                    </button>
            </form>
        </div>
    </div>
</div>