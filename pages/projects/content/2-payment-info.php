<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/financial.php';
require_once 'App/Helper/security.php';

require_once 'Model/ProjectIncomeExpense.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;
$financialHelper = new Financial();

$incexp = new ProjectIncomeExpense();

// Gelir gider bilgierini getir
$income_expenses = $incexp->getAllIncomeExpenseByProject($id);

// Özet Bilgileri getir
$summary = $incexp->sumAllIncomeExpense($id);
$hakedis = $summary->hakedis;
$total_income = $summary->gelir;
$total_expense = $summary->kesinti;
$total_payment = $summary->odeme;
$balance = $hakedis - $total_income - $total_expense - $total_payment;

$enc_id = isset($_GET['id']) ? ($_GET['id']) : 0;

?>


<style>
    .pay-header {
        display: flex !important;
        justify-content: space-between;
    }

    .card-title {
        display: grid;
        place-items: center;
    }
</style>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-inline">
                    <div class="pay-header">
                        <h3 class="card-title">Gelir Gider Listesi</h3>
                        <div class="d-flex col-auto ms-auto">


                            <a href="#" class="btn btn-icon me-2" data-tooltip="Excele Aktar">
                                <i class="ti ti-file-excel icon"></i>
                            </a>

                            <div class="dropdown me-2">
                                <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                    <i class="ti ti-list-details icon me-2"></i>
                                    İşlemler</button>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item add-payment" data-tooltip="Projeye Ödeme yapın"
                                        data-tooltip-location="left" data-id="<?php echo $enc_id; ?>" href="#">
                                        <i class="ti ti-upload icon me-3"></i>
                                        <?php
                                        $type = $project->type ?? 0;
                                        echo ($type == 1) ? 'Ödeme Al' : 'Ödeme Yap';
                                        ?>
                                    </a>
                                    <a class="dropdown-item add-progress-payment" href="#"
                                        data-id="<?php echo $enc_id; ?>">
                                        <i class="ti ti-download icon me-3"></i> Hakediş Ekle
                                    </a>
                                    <a class="dropdown-item add-deduction" href="#" data-id="<?php echo $enc_id; ?>">
                                        <i class="ti ti-cut icon me-3"></i> Kesinti Ekle
                                    </a>

                                </div>
                            </div>

                        </div>
                    </div>


                    <?php
                    $budget = $project->budget ?? 0;
                    if ($hakedis > $budget) {
                        $range = 100;
                    } else {
                        $range = ($hakedis != 0) ? number_format(($hakedis / ($budget ?? 1)) * 100, 0) : 0;
                    }
                    ?>
                    <div class="row mt-3">
                        <div class="mb-1">
                            <div class="progress mb-2">
                                <div class="progress-bar" style="width: <?php echo $range ?>%" role="progressbar"
                                    aria-valuenow="38" aria-valuemin="0" aria-valuemax="100" aria-label="38% Complete">
                                    <span class="visually-hidden"><?php echo $range ?>% Complete</span>
                                </div>
                            </div>
                            <label class="form-label text-muted">Hakediş Tamamlanma Durumu :
                                <span id="progress-bar"> %<?php echo $range ?></span>

                            </label>

                        </div>
                    </div>
                </div>

                <!-- Gelir Gider Özet Bilgileri -->
                <div class="card-header">

                    <div class="row row-cards">

                        <div class="col-md-6 col-xl-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-green text-white avatar">
                                                <i class="ti ti-download icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Hakediş Toplamı
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="total_income">
                                                    <?php echo Helper::formattedMoney($hakedis ?? 0); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-yellow text-white avatar">
                                                <i class="ti ti-upload icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Alınan Ödemeler Toplamı
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="total_payment">
                                                    <?php echo Helper::formattedMoney($total_income ?? 0); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-red text-white avatar">
                                                <i class="ti ti-trending-down icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Kesinti/Gider/Masraf Toplamı
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="total_expense">
                                                    <?php echo Helper::formattedMoney($total_expense ?? 0); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>


                        <div class="col-md-6 col-xl-3">
                            <div class="card card-sm">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <span class="bg-primary text-white avatar">
                                                <i class="ti ti-wallet icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Bakiye
                                            </div>
                                            <div class="<?php echo Helper::balanceColor($balance) ?>">
                                                <label for="" id="balance">
                                                    <?php echo Helper::formattedMoney($balance ?? 0); ?>
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
                    <table class="table card-table text-nowrap datatable" id="project_paymentTable">
                        <thead>
                            <tr>
                                <th style="width:2%">id</th>
                                <th>Tarih</th>
                                <th>İşlem Türü</th>
                                <th>Ay</th>
                                <th>Yıl</th>
                                <th>Tutar</th>
                                <th>Açıklama</th>
                                <th>İşlem Tarihi</th>
                                <th style="width:2%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 0;
                            foreach ($income_expenses as $item):
                                $i++;
                                $item_id = Security::encrypt($item->id);
                                $project_id = Security::encrypt($item->project_id);
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td><?php echo Date::dmY($item->tarih); ?></td>
                                    <td><?php
                                    // İşlem türüne göre icon ve renk belirle
                                    echo Helper::getIconWithColorByType($item->turu) ?? ''
                                    ;
                                    echo $financialHelper::getTransactionType($item->turu) ?? '';
                                    ; ?></td>
                                    <td><?php echo $item->ay; ?></td>
                                    <td><?php echo $item->yil; ?></td>
                                    <td><?php echo Helper::formattedMoney($item->tutar); ?></td>
                                    <td data-tooltip="<?php echo $item->aciklama; ?>">
                                        <?php echo Helper::short($item->aciklama,30); ?>
                                    </td>
                                    <td><?php echo $item->created_at; ?></td>

                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <?php if ($item->turu != 14) { ?>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item route-link"
                                                        data-page="reports/ysc&id=<?php echo $item->id ?>" href="#">
                                                        <i class="ti ti-edit icon me-3"></i> Güncelle
                                                    </a>
                                                    <a class="dropdown-item delete-project-action" href="#"
                                                        data-id="<?php echo $item_id ?>" data-project="<?php echo $project_id ?>">
                                                        <i class="ti ti-trash icon me-3"></i> Sil
                                                    </a>
                                                </div>
                                            <?php } ?>
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