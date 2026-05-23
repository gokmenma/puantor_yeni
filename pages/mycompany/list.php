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
                            <a href="#" class="btn btn-primary route-link" data-page="mycompany/manage">
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