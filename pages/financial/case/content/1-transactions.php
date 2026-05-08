<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/financial.php';
require_once 'Model/Cases.php';
require_once 'Model/CaseTransactions.php';


use App\Helper\Security;
use App\Helper\Date;
// Eğer personel beyaz yaka ise ve içinde bulunduğu ayda gelir tablosuna maaş eklenmediyse git o tabloya personelin aylık ücretini ekle
use App\Helper\Helper;

$Cases = new Cases();
$Actions = new CaseTransactions();
$financialHelper = new Financial();

// Gelir gider bilgierini getir
$actions = $Actions->allTransactionByCaseId($id);


// maas_gelir_gider tablosunda personelin toplam gelir, gider ve ödeme bilgilerini getir
// ********************************************************************************* */
$summary = $Actions->sumAllIncomeExpense($id);

$incomes = $summary->income; // Gelir
$expense = $summary->expense; // Gider
$balance = $incomes - $expense;// Bakiye 
// ********************************************************************************* */



?>
<div class="container-xl">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gelir Gider Listesi</h3>
                    <div class="d-flex col-auto ms-auto">


                        <a href="#" class="btn btn-icon me-2" id="export_excel" data-tooltip="Excele Aktar">
                            <i class="ti ti-file-excel icon"></i>
                        </a>

                        <div class="dropdown me-2">
                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                <i class="ti ti-list-details icon me-2"></i>
                                İşlemler</button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item add-payment" data-tooltip="Personellere Ödeme yapın"
                                    data-tooltip-location="left" data-id="<?php echo $id; ?>" href="#">
                                    <i class="ti ti-upload icon me-3"></i> Ödeme Yap
                                </a>
                                <a class="dropdown-item add-income" href="#" data-id="<?php echo $id; ?>">
                                    <i class="ti ti-download icon me-3"></i> Gelir Ekle
                                </a>
                                <a class="dropdown-item add-wage-cut" href="#" data-id="<?php echo $id; ?>">
                                    <i class="ti ti-cut icon me-3"></i> Kesinti Ekle
                                </a>

                            </div>
                        </div>

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
                                                <i class="ti ti-download icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Gelir Toplamı
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="total_income">
                                                    <?php echo Helper::formattedMoney($incomes ?? 0); ?>
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
                                            <span class="bg-red text-white avatar">
                                                <i class="ti ti-trending-down icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Kesinti/Gider Toplamı
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="total_expense">
                                                    <?php echo Helper::formattedMoney($expense ?? 0); ?>
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
                                                <i class="ti ti-wallet icon"></i>
                                            </span>
                                        </div>
                                        <div class="col">
                                            <div class="font-weight-medium">
                                                Bakiye
                                            </div>
                                            <div class="text-secondary">
                                                <label for="" id="balance">
                                                    <?php echo Helper::formattedMoney($balance); ?>
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
                    <table class="table card-table text-nowrap datatable" id="person_paymentTable">
                        <thead>
                            <tr>
                                <th style="width:2%">Sıra</th>
                                <th style="width:7%">Tarih</th>
                                <th>Adı</th>
                                <th>İşlem Türü</th>
                                <th>Tutar</th>
                                <th>Açıklama</th>
                                <th>İşlem Tarihi</th>
                                <th class="no-export" style="width:2%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 1;
                            foreach ($actions as $item):
                                ?>
                                <tr>
                                    <td><?php echo $i; ?></td>
                                    <td class="text-center"><?php echo Date::dmY($item->date); ?></td>
                                    <td><?php echo $item->type_id; ?></td>


                                    <td><?php
                                    if ($item->type_id == 1) {
                                        echo "<i class='ti ti-download icon color-green me-1' ></i>";
                                    } elseif ($item->type_id == 2) {
                                        echo "<i class='ti ti-trending-down icon color-red me-1' ></i> ";
                                    }
                                    ;
                                    ;
                                    echo $financialHelper->getTransactionType($item->sub_type); ?>

                                    </td>
                                    <td><?php echo Helper::formattedMoney($item->amount); ?></td>
                                    <td><?php echo Helper::short($item->description); ?></td>
                                    <td><?php echo $item->created_at; ?></td>

                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item edit-payment">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-payment" href="#"
                                                    data-id="<?php echo $item->id ?>">
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
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/pages/payroll/content/wage_cut-modal.php' ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/pages/payroll/content/income-modal.php' ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/pages/payroll/content/payment-modal.php' ?>