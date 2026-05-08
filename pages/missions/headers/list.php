<?php
require_once "App/Helper/helper.php";
require_once "Model/MissionHeaders.php";
require_once "App/Helper/date.php";

use App\Helper\Date;

$headerObj = new MissionHeaders();


use App\Helper\Helper;

$headers = $headerObj->getMissionHeadersFirm($firm_id);

?>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Görevler Listesi</h3>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-primary route-link" data-page="missions/headers/manage">
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
                                <th style="width:7%">Sırası</th>
                                <th>Açıklama</th>
                                <th style="width:12%">Eklenme Tarihi</th>
                                <th style="width:7%">Statu</th>
                                <th style="width:7%">İşlem</th>

                            </tr>
                        </thead>
                        <tbody>


                            <?php foreach ($headers as $item) :
                            ?>
                                <tr>
                                    <td><?php echo $item->id; ?></td>   
                                    <td><?php echo $item->header_name; ?></td>
                                    <td class="text-center"><?php echo $item->header_order; ?></td>
                                    <td><?php echo $item->description; ?></td>
                                    <td><?php echo $item->created_at; ?></td>
                                    <td class="text-center">
                                        <?php echo $item->status == 0 ? '<span class="badge bg-danger text-white">Pasif</span>' : '' ?>
                                        <?php echo $item->status == 1 ? '<span class="badge bg-success text-white">Aktif</span>' : '' ?>
                                    </td>
                                 

                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link" data-page="missions/headers/manage&id=<?php echo $item->id ?>" href="#">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-mission-headers" href="#" data-id="<?php echo $item->id ?>">
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