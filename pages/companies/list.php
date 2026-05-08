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
                            <a href="#" class="btn btn-primary route-link" data-page="companies/manage">
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
                                        <a href="#" class="nav-item route-link"
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
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link"
                                                    data-page="companies/manage&id=<?php echo $id ?>" href="#">
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