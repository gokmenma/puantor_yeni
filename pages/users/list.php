<?php
require_once "App/Helper/helper.php";
require_once "Model/UserModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;
use App\Helper\Helper;

$userObj = new UserModel();
$users = $userObj->getUsersByFirm($firm_id);

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
                <h4 class="alert-title">Kullanıcı Listesi!</h4>
                <div class="text-secondary">Seçili firma için dilediğiniz kadar kullanıcı ekleyebilir ve bu
                    kullanıcılara istediğiniz yetkileri verebilirsiniz.
                    <p class="text-muted">Hesap oluşturma aşamasında oluşturulan kullanıcı silinemez!</p>
                </div>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Kullanıcı Listesi</h3>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-primary add-user route-link" data-page="users/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a>

                    </div>
                </div>

                <div class="table-responsive">
                    <table id="userTable" class="table card-table text-nowrap datatable">
                        <thead>
                            <tr>
                                <th style="width:7%">Sıra</th>
                                <th style="width:10%">Pozisyon</th>
                                <th>Adı Soyadı</th>
                                <th style="width:20%">Email</th>
                                <th style="width:10%">Telefon</th>
                                <th style="width:10%">Ana Kullanıcı</th>
                                <th style="width:7%">Durum</th>
                                <th style="width:7%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 1;
                            foreach ($users as $user):
                                $id = Security::encrypt($user->id);
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td><?php echo $userObj->roleName($user->user_roles ?? ''); ?></td>
                                    <td><?php echo $user->full_name; ?></td>
                                    <td><?php echo $user->email; ?></td>
                                    <td class="text-start"><?php echo $user->phone; ?></td>
                                    <td class="text-center">
                                        <?php
                                        if ($user->is_main_user == 1) {
                                            echo "<i class='ti ti-check text-success fs-24'></i>";
                                        }

                                        ?>
                                    </td>
                                    <td><?php echo $user->status; ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link"
                                                    data-page="users/manage&id=<?php echo $id ?>" href="#">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <?php if ($user->is_main_user != 1) { ?>
                                                    <a class="dropdown-item delete_user" data-id="<?php echo $id ?>" href="#">
                                                        <i class="ti ti-trash icon me-3"></i> Sil
                                                    </a>
                                                <?php } ?>
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