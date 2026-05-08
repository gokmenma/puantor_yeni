<?php
require_once 'App/Helper/date.php';
require_once "App/Helper/person.php";
require_once "Model/Persons.php";

use App\Helper\Date;

$Persons = new Persons();
$personHelper = new PersonHelper();

$persons = $Persons->getPersonsByActive();

?>
<style>
 

    .sticky {
        position: sticky;
        /* Başlıkları sabit yapar */
        top: 0;
        /* Sabitlenecek yer (üstte) */
        background-color: #fff;
        /* Arka plan rengi */
        z-index: 1;
        padding: 0 0 0 20px;

    }


</style>
<div class="modal modal-blur fade" id="pay_to_persons-modal" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Personel Ödemesi Yap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="payToPersonsForm">
                    <div class="container sticky">
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Ödeme Yapılacak Kasa</label>
                                <?php echo $financialHelper->getCasesSelectByUser("tps_cases", $case_id); ?>

                            </div>
                            <div class="col">
                                <label class="form-label">Ödeme Tarihi</label>
                                <input type="text" name="tps_action_date" id="tps_action_date" class="form-control flatpickr"
                                    value="<?php echo date("d.m.Y") ?>">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Ödeme Açıklama</label>
                                <input type="text" name="tps_amount_description" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="container pt-0">


                        <table class="table datatable" id="payToPersons">

                            <thead class="sticky-header">
                                <th>Personel</th>
                                <th style="width:30%">Ödeme Tutarı</th>
                            </thead>

                            <tbody>
                                <?php foreach ($persons as $person): ?>
                                    <tr>

                                        <td data-id="<?= $person->id ?>"><?= $person->full_name ?></td>
                                        <td><input type="text" class="form-control money"></td>

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Çık</button>
                <button type="button" class="btn btn-primary" id="savePayToPersons">Kaydet</button>
            </div>
        </div>
    </div>
</div>