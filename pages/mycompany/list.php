<?php
$user_id = $_SESSION['user']->id;
require_once "Model/MyFirmModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;


$perm->checkAuthorize("my_companies_page");
$Auths->checkFirmReturn();


$MyFirmModel = new MyFirmModel();
$myfirms = $MyFirmModel->getMyFirmByUserId();

?>
<div class="container-xl">
       <!-- Alert component'i dahil et -->
       <?php
        $title = "Firmalarım Listesi!";
        $text = "Sahip olduğunuz firmaları buradan yönetebilirsiniz.";
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
                        <a href="#" class="btn btn-primary route-link" data-page="mycompany/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a>
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
                            $i = 1;
                            foreach ($myfirms as $myfirm):
                            $id = Security::encrypt($myfirm->id);
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td><a class="btn route-link" data-page="mycompany/manage&id=<?php echo $id ?>"
                                            href="#">
                                            <?php echo $myfirm->firm_name; ?>
                                        </a></td>
                                    <td class="text-start"><?php echo $myfirm->phone; ?></td>
                                    <td><?php echo $myfirm->email; ?></td>
                                    <td><?php echo $myfirm->description; ?></td>
                                    <td><?php echo $myfirm->created_at; ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link"
                                                    data-page="mycompany/manage&id=<?php echo $id ?>" href="#">
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