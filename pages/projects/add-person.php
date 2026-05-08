<?php
require_once "Model/Projects.php";
require_once "Model/Persons.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/company.php";

use App\Helper\Helper;
use App\Helper\Security;

$projects = new Projects();
$person = new Persons();

$id = Security::decrypt($_GET['id']) ?? 0;

$project = $projects->find(($id));
$persons = $projects->getPersontoProject($firm_id,$id);
$company = new CompanyHelper();



?>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="col">
                        <!-- Page pre-title -->
                        <div class="page-pretitle">
                        Projeye Personel Ekle
                    </div>
                    <h2 class="page-title">
                        <?php echo $project->project_name ?>
                        </h2>
                    </div>
                    <!-- <h3 class="card-title"></h3>
                    <p>Ankara Projesi</p> -->
                    <div class="col-auto ms-auto">
                        <a href="#" class="btn route-link" data-page="projects/list">
                            <i class="ti ti-arrow-left icon me-2"></i> Listeye Dön
                        </a>
                        <a href="#" class="btn btn-primary" id="savePersontoProject">
                            <i class="ti ti-device-floppy icon me-2"></i> Kaydet
                        </a>
                    </div>
                </div>
                <div class="col-md-4">
                    <input type="hidden" class="form-control" id="project_id" value="<?php echo $id ?>">
                </div>

                <div class="table-responsive">
                    <table id="addPersontoProject" class="table card-table table-hover text-nowrap datatable">
                        <thead>
                            <tr>

                                <th style="width:1%" class="no-sorting">
                                    <input class="form-check-input" type="checkbox" id="allPersonCheck">
                                </th>
                                <th style="width:1%">ID</th>
                                <th>Adı Soyadı</th>
                                <th>Ücret Türü</th>
                                <th>Görevi</th>
                                <th style="width:1%">Durumu</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php foreach ($persons as $person) :
                            $checked = $person->is_added == 1 ? "checked" : "";
                            ?>
                                <tr>
                                    <td>
                                        <input class="form-check-input" name="person_checked[<?php echo $person->id ?>]" <?php echo $checked ;?> type="checkbox" value="<?php echo $person->id ?>">
                                    </td>
                                    <td><?php echo $person->id; ?></td>
                                    <td><?php echo $person->full_name; ?></td>
                                    <td><?php echo $person->wage_type == 1 ? "Beyaz Yaka" : "Mavi Yaka"; ?></td>
                                    <td><?php echo $person->is_added; ?></td>
                                    <td><?php echo $person->state ?></td>

                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>