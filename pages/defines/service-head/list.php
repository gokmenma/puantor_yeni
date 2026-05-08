<?php
require_once "Model/DefinesModel.php";

$defineObj = new DefinesModel();
$defines = $defineObj->getServiceHeads();


?>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Servis Konusu Listesi</h3>
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn btn-primary route-link" data-page="defines/service-head/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a>
                    </div>
                </div>


                <div class="table-responsive">
                    <table class="table card-table text-nowrap datatable">
                        <thead>
                            <tr>
                                <th style="width:1%">id</th>
                                <th>Servis Konusu</th>
                                <th>Açıklama</th>
                                <th style="width:1%" class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php foreach ($defines as $define) :
                            ?>
                                <tr>
                                    <td><?php echo $define->id ?></td>
                                    <td><?php echo $define->title ?></td>
                                    <td><?php echo $define->description ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link" data-page="defines/service-head/manage&id=<?php echo $define->id ?>" href="#">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-defines" data-id="<?php echo $define->id?>" href="#">
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