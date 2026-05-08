<?php
require_once "App/Helper/helper.php";
require_once "App/Helper/date.php";
require_once "Model/Wages.php";

use App\Helper\Date;

use App\Helper\Helper;
use App\Helper\Security;

// $id = isset($_GET['id']) ? $_GET['id'] : 0;
$wagesObj = new Wages();
$wages = $wagesObj->getWageByPersonId($person->id);

if (!$Auths->Authorize("person_page_wage_defines_info")) {
    Helper::authorizePage();
    return;
}
?>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">

                <div class="card-header">
                    <h3 class="card-title">Ücret Tanımları</h3>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" id="add_wage_row">
                            <i class="ti ti-plus icon me-2"></i> Yeni

                        </button>


                    </div>
                </div>
                <style>
                    .input-error {
                        border-color: red;

                    }
                </style>
                <div class="alert alert-info bg-white border-start border-info border-3 p-3 m-3" role="alert">
                    <div class="d-flex align-items-start">
                        <span class="bg-info text-white avatar rounded-circle me-3">
                            <i class="ti ti-info-circle icon" style="font-size: 1.4rem;"></i>
                        </span>
                        <div>
                            <h4 class="alert-title fw-bold text-info mb-1">Önemli Bilgilendirme</h4>
                            <div class="text-secondary small" style="line-height: 1.5;">
                                Başlangıç ve bitiş tarihleri arasındaki çalışmalar bu tabloda ekleyeceğiniz ücrete göre hesaplanacaktır. 
                                Lütfen tanımladığınız tarih aralıklarının birbiriyle çakışmadığından ve ilgili döneme ait ücret bilgisinin doğru girildiğinden emin olunuz.
                            </div>
                        </div>
                    </div>
                </div>
                <form action="" id="personWageForm">
                    <div class="row d-none">

                        <input type="text" class="form-control" value="<?php echo $person->id ?? 0 ?>" name="wage_person_id">
                        <input type="text" class="form-control" id="wage_id" name="wage_id">
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-sm text-nowrap datatable table-hover" id="personWageTable">
                            <thead>
                                <tr>

                                    <th style="width:2%">Sıra</th>
                                    <th class="text-start">Adı</th>
                                    <th class="text-start">Başlama Tarihi</th>
                                    <th class="text-start">Bitiş Tarihi</th>
                                    <th class="text-start">Tutar</th>
                                    <th>Açıklama</th>
                                    <th style="width:1%">Tarih</th>
                                    <th style="width:1%">İşlem</th>

                                </tr>
                            </thead>
                            <tbody>


                                <?php
                                $i = 1;
                                foreach ($wages as $wage):
                                    $id = Security::encrypt($wage->id);
                                ?>
                                    <tr>

                                        <td class="text-center"><?php echo $i; ?></td>
                                        <td class="text-start"><?php echo $wage->wage_name; ?></td>
                                        <td class="text-start"><?php echo Date::dmY($wage->start_date); ?></td>
                                        <td class="text-start"><?php echo Date::dmY($wage->end_date); ?></td>
                                        <td class="text-start"><?php echo Helper::formattedMoney($wage->amount ?? 0); ?></td>
                                        <td class="text-start"><?php echo $wage->description; ?></td>
                                        <td class="text-start"><?php echo $wage->created_at; ?></td>

                                        <td class="text-end">
                                            <div class="dropdown">
                                                <button class="btn dropdown-toggle align-text-top"
                                                    data-bs-toggle="dropdown">İşlem</button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item update-wage" data-id="<?php echo $id ?>"
                                                        href="#">
                                                        <i class="ti ti-edit icon me-3"></i> Güncelle
                                                    </a>
                                                    <a class="dropdown-item delete-wage" href="#"
                                                        data-id="<?php echo $id ?>">
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
                </form>
            </div>
        </div>
    </div>
</div>