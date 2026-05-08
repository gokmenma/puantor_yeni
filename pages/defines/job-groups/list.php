<?php
require_once "App/Helper/helper.php";
require_once "Model/JobGroupsModel.php";


use App\Helper\Helper;
use App\Helper\Security;

$JobGroups = new JobGroupsModel();
$jobGroups = $JobGroups->all();


?>
<div class="container-xl mt-3">
    <div class="alert alert-info bg-white alert-dismissible d-flex">
        <div class="d-flex">
            <div>
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
                <h4 class="alert-title">İş Grubu Tanımlama!</h4>
                <div class="text-secondary">Firmanız için iş grupları tanımlayabilir ve raporlarınızı bu iş gruplarına
                    göre alabilirsiniz!</div>
            </div>
        </div>

        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">İş Grubu Listesi</h3>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-primary route-link" data-page="defines/job-groups/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a>
                    </div>
                </div>


                <div class="table-responsive">
                    <table class="table card-table text-nowrap datatable">
                        <thead>
                            <tr>
                                <th style="width:7%">Sıra</th>
                                <th>Grup Adı</th>
                                <th>Açıklama</th>
                                <th>Eklenme Tarihi</th>
                                <th style="width:7%">İşlem</th>

                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 1;
                            foreach ($jobGroups as $jobs):
                                $id = Security::encrypt($jobs->id);
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td>
                                        <a class="btn" data-page="defines/job-groups/manage&id=<?php echo $id ?>" href="#">
                                            
                                            <?php echo $jobs->group_name; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $jobs->description; ?></td>
                                    <td class="text-start"><?php echo $jobs->created_at; ?></td>

                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link"
                                                    data-page="defines/job-groups/manage&id=<?php echo $id ?>" href="#">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-job-groups" href="#"
                                                    data-id="<?php echo $id ?>">
                                                    <i class="ti ti-trash icon me-3"></i> Sil
                                                </a>
                                            </div>
                                        </div>

                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>