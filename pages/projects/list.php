<?php
require_once 'Model/Projects.php';
require_once 'Model/ProjectIncomeExpense.php';
require_once 'App/Helper/helper.php';
require_once 'App/Helper/cities.php';
require_once 'App/Helper/company.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/financial.php';
require_once "Model/Cases.php";


use App\Helper\Helper;
use App\Helper\Security;
use Random\Engine\Secure;
use App\Helper\Date;

$perm->checkAuthorize("project_add_update");

$projectObj = new Projects();
$incexpObj = new ProjectIncomeExpense();
$cities = new Cities();
$projects = $projectObj->getProjectsByFirm($firm_id);
$companyHelper = new CompanyHelper();
$Cases = new Cases();
$financialHelper = new Financial();

$case_id = $Cases->getDefaultCaseIdByFirm();


?>

<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Proje Listesi</h3>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-icon me-2" id="export_excel" data-tooltip="Excele Aktar">
                            <i class="ti ti-file-excel icon"></i>
                        </a>
                        <div class="form-selectgroup">

                            <label class="form-selectgroup-item">
                                <input type="radio" name="icons" value="user" data-type="Tümü"
                                    class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-list-check icon me-2"></i>
                                    Tümü
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="icons" value="circle" data-type="Alınan"
                                    class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-download icon me-2"></i>
                                    Alınan Projeler
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="icons" value="square" data-type="Verilen"
                                    class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-upload icon me-2"></i>
                                    Verilen Projeler
                                </span>
                            </label>
                        </div>
                        <a href="#" class="btn btn-primary route-link" data-page="projects/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a>
                    </div>
                </div>


                <div class="table-responsive">
                    <table class="table card-table text-nowrap table-hover datatable" id="projectTable">
                        <thead>
                            <tr>
                                <th style="width:7%">Sıra</th>
                                <th>Türü</th>
                                <th>Firma Adı</th>
                                <th>Proje Adı</th>
                                <th>Proje Bedeli</th>
                                <th>Şehir</th>
                                <th>İlçe</th>
                                <th style="width:10%">Başlama Tarihi</th>
                                <th style="width:10%">Tahmini Bitiş Tarihi</th>
                                <th style="width:10%">Kalan Gün</th>
                               
                                <th class="no-export" style="width:7%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 1;
                            foreach ($projects as $project):
                                $id = Security::encrypt($project->id);
                                // Projenin bakiyesini hesapla
                                $balance = $incexpObj->getBalance($project->id);

                                $proje_gunu = Date::getDateDiff($project->start_date, $project->end_date ?? $project->start_date) ?? 0;
                                $kalan_gun = Date::getRemainingDays($project->end_date);
                                $date_range = is_numeric($kalan_gun) && is_numeric($proje_gunu) ? 100 - (($proje_gunu - $kalan_gun)) : 0;
                                $date_range = $project->end_date == null ? 100 : $date_range;

                                //proje gunu 0'dan büyük ve kalan gün 0 ise proje tamamlandı
                                if ($proje_gunu > 0 && $kalan_gun <= 0) {
                                    $date_range = 100;
                                    $progress_color = "bg-success";
                                    $sub_text = "Proje Tamamlandı";
                                } else {
                                    if($kalan_gun < 10 ){
                                        $progress_color = "bg-danger";
                                    }else if($kalan_gun < 30){
                                        $progress_color = "bg-warning";
                                    }else{
                                    $progress_color = "bg-primary";
                                    }
                                    $sub_text = "Proje Devam Ediyor";
                                }
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i ?></td>
                                    <td><?php echo $project->type == 1 ? 'Alınan' : 'Verilen' ?></td>
                                    <td><?php echo $companyHelper->getCompanyName($project->company_id) ?></td>
                                    <td>
                                        <a class="link route-link nav-item" data-tooltip="Detay"
                                            data-page="projects/manage&id=<?php echo $id ?>" data-tooltip-location="top">
                                            <?php echo $project->project_name ?>
                                        </a>
                                    </td>
                                    <td class="text-end"><?php echo Helper::formattedMoney($project->budget ?? 0) ?></td>
                                    <td><?php echo $cities->getCityName($project->city) ?></td>
                                    <td><?php echo $cities->getTownName($project->town) ?></td>
                                    <td class="text-center"><?php echo $project->start_date ?></td>
                                    <td class="text-center"><?php echo $project->end_date ?></td>
                                    <td class="text-center">
                                        <div class="progress progress-xs">
                                            <div class="progress-bar <?php echo $progress_color ?>"
                                                style="width:  <?php echo $date_range ?>%"></div>
                                        </div>
                                        <?php if ($kalan_gun > 0) {
                                            echo $kalan_gun . ' Gün';
                                        } else {
                                            echo '<span class="text-muted">' . $sub_text . '</span>';
                                        }
                                        ?>

                                    </td>

                                    <!-- Bakiye rengini belirle ve göster 
                                    <td class="<?php //echo Helper::balanceColor($balance) ?>">-->
                                        <!-- //Bakiyesini yazdır 
                                        <?php //echo Helper::formattedMoney($balance) ?>
                                    </td>-->
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>

                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php if ($perm->hasPermission("project_add_update")) { ?>
                                                    <a class="dropdown-item route-link"
                                                        data-page="projects/manage&id=<?php echo $id ?>" href="#">
                                                        <i class="ti ti-edit icon me-3"></i> Güncelle/Detay
                                                    </a>
                                                <?php } ?>
                                                <a class="dropdown-item route-link"
                                                    data-page="projects/add-person&id=<?php echo $id ?>" href="#">
                                                    <i class="ti ti-users-plus icon me-3"></i> Projeye Personel Ekle
                                                </a>
                                                <!-- Proje Alınan proje ise -->
                                                <?php if ($project->type == 1) { ?>
                                                    <a class="dropdown-item add-progress-payment" href="#"
                                                         data-id="<?php echo $id; ?>">
                                                        <i class="ti ti-upload icon me-3"></i> Hakediş Ekle
                                                    </a>
                                                <?php } ?>
                                                <!-- Proje Verilen proje ise -->
                                                <?php if ($project->type == 2) { ?>
                                                    <a class="dropdown-item add-payment" href="#"
                                                        data-id="<?php echo $id; ?>">
                                                        <i class="ti ti-download icon me-3"></i> Ödeme Ekle
                                                    </a>
                                                <?php } ?>
                                                <a class="dropdown-item add-expense" href="#"
                                                 data-id="<?php echo $id; ?>">
                                                    <i class="ti ti-license icon me-3"></i> Masraf Ekle
                                                </a>

                                                <a class="dropdown-item delete-project" href="#"
                                                    data-id="<?php echo $id; ?>">
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

<?php include_once 'modals/progress-payment-modal.php' ?>
<?php include_once 'modals/payment-modal.php' ?>
<?php include_once 'modals/expense-modal.php' ?>