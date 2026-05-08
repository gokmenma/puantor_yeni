<?php
require_once 'App/Helper/helper.php';
require_once 'Model/Bordro.php';
require_once 'Model/Puantaj.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/security.php';
require_once "App/Helper/financial.php";
require_once 'Model/DefinesModel.php';


use App\Helper\Security;
use App\Helper\Date;
use App\Helper\Helper;

$puantaj = new Puantaj();
$bordro = new Bordro();
$financialHelper = new Financial();
$Defines = new DefinesModel();

// Eğer personel beyaz yaka ise ve içinde bulunduğu ayda gelir tablosuna maaş eklenmediyse git o tabloya personelin aylık ücretini ekle

// Gelir gider bilgierini getir
$income_expenses = $bordro->getPersonWorkTransactions($id);

$month = Date::getMonth();

//$gelir = $Defines->getExpenseTypes(1);
//$kesinti = $Defines->getExpenseTypes(2);


// maas_gelir_gider tablosunda personelin toplam gelir, gider ve ödeme bilgilerini getir
// ********************************************************************************* */
$summary = $bordro->sumAllIncomeExpense($id);

// Toplam Gelir(Puantaj + Eklenen Gelirler+ veya maaş)
$total_income = $summary->total_income;

$total_expense = $summary->total_expense;

// Bakiye hesaplanacak
$balance = $total_income - $total_expense;

// ********************************************************************************* */

$encrypted_person_id = Security::encrypt($id);
if (!$Auths->Authorize("person_page_income_expence_info")) {
    Helper::authorizePage();
    return;
}

?>
<div class="container-xl">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gelir Gider Listesi  </h3>
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
                                    data-tooltip-location="left" data-id="<?php echo $encrypted_person_id; ?>" href="#">
                                    <i class="ti ti-upload icon me-3"></i> Ödeme Yap
                                </a>
                                <a class="dropdown-item add-income" href="#" data-id="<?php echo $encrypted_person_id; ?>">
                                    <i class="ti ti-download icon me-3"></i> Gelir Ekle
                                </a>
                                <a class="dropdown-item add-wage-cut" href="#" data-id="<?php echo $encrypted_person_id; ?>">
                                    <i class="ti ti-cut icon me-3"></i> Kesinti Ekle
                                </a>

                            </div>
                        </div>

                    </div>
                </div>
                <div class="card-header">
                    <div class="row row-cards">

                        <div class="col-md-4 col-xl-4">
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
                                                    <?php echo Helper::formattedMoney($total_income ?? 0); ?>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 col-xl-4">
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
                                                Kesinti/Gider/Ödeme Toplamı
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



                        <div class="col-md-4 col-xl-4">
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
                    <table class="table card-table table-sm text-nowrap datatable table-hover" id="person_paymentTable">
                        <thead>
                            <tr>
                                <th style="width:2%">id</th>
                                <th>Tarih</th>
                                <th>İşlem Türü</th>
                                <th>Adı</th>
                                <th>Ay</th>
                                <th>Yıl</th>
                                
                                <th>Tutar</th>
                                <th>Açıklama</th>
                                <th>İşlem Tarihi</th>
                                <th class="no-export" style="width:2%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            foreach ($income_expenses as $item):
                                $item_id = Security::encrypt($item->id);
                                ?>
                                <tr>
                                    <td><?php echo $item->id; ?></td>
                                    <td><?php echo Date::dmY($item->gun); ?></td>
                                    <td><?php
                                    // İşlem türüne göre icon ve renk belirle
                                    echo $financialHelper->getTransactionIcon($item->kategori) ?? '';

                                    $type = $financialHelper->getTransactionTypeById($item->kategori);

                                    // İşlem türüne göre renk belirle
                                    if($type->type_id == 1){
                                        echo "<label class='text-success'>$type->name</label>";
                                    }else if($type->type_id == 2){
                                        echo "<label class='text-danger'>$type->name</label>";
                                    }
                                    ?>
                                    </td>
                                    <td><?php echo $item->turu; ?></td>
                                    <td><?php echo $item->ay; ?></td>
                                    <td><?php echo $item->yil; ?></td>

                                  
                                    <td><?php echo Helper::formattedMoney($item->tutar); ?></td>
                                    <td><?php echo Helper::short($item->aciklama); ?></td>
                                    <td><?php echo $item->created_at; ?></td>





                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php
                                                //Eğer ödeme, gelir veya kesinti ise işlem yapılabilir
                                                if ($item->kategori != 14): ?>
                                                    <a class="dropdown-item edit-payment">
                                                        <i class="ti ti-edit icon me-3"></i> Güncelle
                                                    </a>
                                                <?php endif ?>
                                                <a class="dropdown-item delete-payment" href="#"
                                                    data-id="<?php echo $item_id ?>">
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
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/pages/payroll/content/wage_cut-modal.php' ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/pages/payroll/content/income-modal.php' ?>
<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/pages/payroll/content/payment-modal.php' ?>