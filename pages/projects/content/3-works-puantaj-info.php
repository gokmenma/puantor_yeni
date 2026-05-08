<?php
require_once "App/Helper/helper.php";
require_once "App/Helper/date.php";
require_once "Model/Bordro.php";
require_once "Model/Puantaj.php";
require_once "Model/Projects.php";
require_once "App/Helper/security.php";
require_once "Model/Persons.php";


use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

$bordro = new Bordro();
$Persons = new Persons();
$puantajObj = new Puantaj();
$projectHelper = new Projects();

//Projenin puantaj tablosundaki çalışma bilgilerini getirir
$puantaj_info = $puantajObj->getPuantajInfoByProject($id);

//Projenin toplam çalışan personel sayısını getirir
$total_person = $puantajObj->getTotalWorksPersonByProject($id);

//Projenin toplam çalışma saatini getirir
$total_hours = $puantajObj->getTotalWorksHourByProject($id);

//Projenin toplam çalışma tutarını getirir
$total_amount = $puantajObj->getTotalWorksBalanceByProject($id);


if (!$Auths->Authorize("person_page_puantaj_info")) {
    Helper::authorizePage();
    return;
}

?>
<style>
    table.datatable th,
    table.datatable td {
        text-align: left !important;
    }
</style>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Proje Çalışma Bilgileri</h3>
                    <div class="d-flex col-auto ms-auto">
                        <a href="#" class="btn btn-icon me-2 excel" id="export_excel_puantaj_info" data-tooltip="Excele Aktar">
                            <i class="ti ti-file-excel icon"></i>
                        </a>
                    </div>
                </div>
                <div class="card-header">
                    <div class="row row-cards">

                        <div class="col-md-4 col-sm-12">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-green text-white avatar">
                                                <i class="ti ti-users icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                               Toplam Çalışan Personel
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="total_income">
                                                    <?php echo ($total_person ?? 0) . " Kişi" ; ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-12">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-yellow text-white avatar">
                                                <i class="ti ti-clock-24 icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Toplam Çalışma Saati
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="total_expense">
                                                    <?php echo ($total_hours ?? 0) . " Saat"; ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-sm-12">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-primary text-white avatar">
                                                <i class="ti ti-basket-dollar icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Toplam Çalışma Tutarı
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="balance">
                                                    <?php echo Helper::formattedMoney($total_amount ); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table table-hover text-nowrap datatable" id="puantaj_info_table">
                        <thead>
                            <tr>
                                <th style="width:7%">id</th>
                                <th>Personel</th>
                                <th>Puantaj Türü</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th class="text-start">Tutar</th>

                            </tr>
                        </thead>
                        <tbody>


                            <?php foreach ($puantaj_info as $item):
                                ?>
                                <tr>
                                    <td><?php echo $item->id ?></td>
                                    <td><?php echo $Persons->getPersonByField($item->person,"full_name") ?? '' ?></td>
                                    <td>
                                        <?php
                                        $puantaj_turu = $puantajObj->getPuantajTuruById($item->puantaj_id);
                                        echo $puantaj_turu->PuantajKod . " - " . $puantaj_turu->PuantajAdi;
                                        ?>
                                    </td>
                                    <td><?php echo Date::ymd($item->gun, "d.m.Y") ?></td>
                                    <td class="text-start"><?php echo $item->saat ?></td>
                                    <td class="text-start"><?php echo Helper::formattedMoney($item->tutar) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>