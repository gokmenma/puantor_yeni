<?php
require_once "App/Helper/helper.php";
require_once "App/Helper/users.php";
require_once "Model/Missions.php";
require_once "Model/MissionHeaders.php";

$missionObj = new Missions();
$headers = new MissionHeaders();
$userHelper = new UserHelper();

use App\Helper\Helper;
use App\Helper\Security;

$missions = $missionObj->getMissionsFirm($firm_id);

?>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Görevler Listesi</h3>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-primary route-link" data-page="missions/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a>
                    </div>
                </div>


                <div class="table-responsive">
                    <table class="table card-table text-nowrap datatable">
                        <thead>
                            <tr>
                                <th style="width:7%">ID</th>
                                <th>Görev Başlığı</th>
                                <th>Görev Adı</th>
                                <th>Atanan Kullanıcı</th>
                                <th>Önem Derecesi</th>
                                <th>Başlama Tarihi</th>
                                <th>Bitiş Tarihi</th>
                                <th>Süreci</th>
                                <th>İşlem</th>

                            </tr>
                        </thead>
                        <tbody>


                            <?php 
                            $i = 1;
                            foreach ($missions as $item) :
                            $id = Security::encrypt($item->id);
                            ?>
                                <tr>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td>
                                        <?php
                                        $header = $headers->getMissionHeader($item->header_id);
                                        echo $header->header_name;
                                        ?>
                                    </td>
                                    <td><?php echo $item->name; ?></td>
                                    <td>
                                        <?php
                                        $users = $userHelper->getUsersName($item->user_ids);
                                        echo $users;
                                        ?>
                                    </td>
                                    <td><?php echo Helper::getPriority($item->priority); ?></td>
                                    <td><?php echo Helper::short($item->start_date); ?></td>
                                    <td><?php echo Helper::short($item->end_date); ?></td>
                                    <td></td>

                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link" data-page="missions/manage&id=<?php echo $id ?>" href="#">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-mission" href="#" data-id="<?php echo $id ?>">
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