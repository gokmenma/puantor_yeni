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
$income_expenses_raw = $bordro->getPersonWorkTransactions($id);

// Yürüyen bakiye hesaplamak için eskiden yeniye sırala
usort($income_expenses_raw, function($a, $b) {
    if ($a->gun == $b->gun) {
        return (int)$a->id - (int)$b->id;
    }
    return strcmp($a->gun, $b->gun);
});

$running_balance = 0;
foreach ($income_expenses_raw as &$item) {
    $type = $financialHelper->getTransactionTypeById($item->kategori);
    if ($type->type_id == 1) {
        $running_balance += $item->tutar;
    } else {
        $running_balance -= $item->tutar;
    }
    $item->running_balance = $running_balance;
}
unset($item);

// Şimdi yeniden eskiye sırala (Görünüm için)
usort($income_expenses_raw, function($a, $b) {
    if ($a->gun == $b->gun) {
        return (int)$b->id - (int)$a->id;
    }
    return strcmp($b->gun, $a->gun);
});

$income_expenses = $income_expenses_raw;

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
                                                <label for="" id="balance" class="<?php echo $balance >= 0 ? 'text-success' : 'text-danger'; ?>">
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
                                <th>Bakiye</th>
                                <th>Açıklama</th>
                                <th>İşlem Tarihi</th>
                                <th class="no-export" style="width:2%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            foreach ($income_expenses as $item):
                                $item_id = Security::encrypt($item->id);
                                $is_puantaj = ($item->kategori == 14);
                                $row_class = $is_puantaj ? 'puantaj-row cursor-pointer' : '';
                            ?>
                                <tr class="<?php echo $row_class; ?>" 
                                    data-id="<?php echo $item->id; ?>" 
                                    data-person="<?php echo $item->person_id; ?>"
                                    data-ay="<?php echo $item->ay; ?>"
                                    data-yil="<?php echo $item->yil; ?>"
                                    data-type="puantaj">
                                    <td><?php echo $item->id; ?></td>
                                    <td>
                                        <?php 
                                        if ($is_puantaj) {
                                            echo Date::monthName($item->ay) . " " . $item->yil;
                                        } else {
                                            echo Date::dmY($item->gun);
                                        }
                                        ?>
                                    </td>
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
                                    <td>
                                        <?php 
                                        if ($is_puantaj) {
                                            echo "<strong>Puantaj Hak Edişi</strong>";
                                            if (!empty($item->saat)) {
                                                echo " <span class='badge bg-azure-lt ms-2'>" . number_format($item->saat, 1, ',', '.') . " Saat</span>";
                                            }
                                        } else {
                                            echo $item->turu;
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo $item->ay; ?></td>
                                    <td><?php echo $item->yil; ?></td>

                                  
                                    <td><?php echo Helper::formattedMoney($item->tutar); ?></td>
                                    <td class="font-weight-bold <?php echo $item->running_balance >= 0 ? 'text-success' : 'text-danger'; ?>"><?php echo Helper::formattedMoney($item->running_balance); ?></td>
                                    <td><?php echo $is_puantaj ? '' : Helper::short($item->aciklama); ?></td>
                                    <td><?php echo $is_puantaj ? '' : $item->created_at; ?></td>





                                    <td class="text-end">
                                        <?php if ($item->kategori != 14): ?>
                                            <div class="dropdown">
                                                <button class="btn dropdown-toggle align-text-top"
                                                    data-bs-toggle="dropdown">İşlem</button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item edit-payment">
                                                        <i class="ti ti-edit icon me-3"></i> Güncelle
                                                    </a>
                                                    <a class="dropdown-item delete-payment" href="#"
                                                        data-id="<?php echo $item_id ?>">
                                                        <i class="ti ti-trash icon me-3"></i> Sil
                                                    </a>
                                                </div>
                                            </div>
                                        <?php endif; ?>
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

<!-- Puantaj Detay Modal -->
<div class="modal modal-blur fade" id="puantajDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white border-0 py-3">
                <h5 class="modal-title font-weight-bold">
                    <i class="ti ti-calendar-stats me-2"></i>
                    Puantaj Detayları - <span id="modal_period_label">...</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive" style="max-height: 450px;">
                    <table class="table table-vcenter card-table table-hover mb-0">
                        <thead class="sticky-top bg-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Proje</th>
                                <th>Tür</th>
                                <th class="text-end">Saat</th>
                                <th class="text-end">Tutar</th>
                            </tr>
                        </thead>
                        <tbody id="puantaj_detail_rows">
                            <!-- JS ile doldurulacak -->
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td colspan="3">Toplam</td>
                                <td class="text-end" id="modal_total_hours">0</td>
                                <td class="text-end" id="modal_total_amount">0,00 TL</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-outline-secondary font-weight-medium px-4" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<style>
    .puantaj-row:hover {
        background-color: rgba(var(--tblr-primary-rgb), 0.04) !important;
        transition: background-color 0.2s ease;
    }
    .cursor-pointer {
        cursor: pointer !important;
    }
</style>

<script>
$(document).ready(function() {
    $('.puantaj-row').on('click', function() {
        const personId = $(this).data('person');
        const ay = $(this).data('ay');
        const yil = $(this).data('yil');
        
        if (!personId || !ay || !yil) return;

        $('#modal_period_label').text(ay + '/' + yil);
        $('#puantaj_detail_rows').html('<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary"></div></td></tr>');
        $('#puantajDetailModal').modal('show');

        $.ajax({
            url: '/api/bordro/get-puantaj-detail.php',
            type: 'POST',
            data: { 
                person_id: personId,
                ay: ay,
                yil: yil
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    let html = '';
                    let totalHours = 0;
                    let totalAmount = 0;

                    response.data.forEach(item => {
                        totalHours += parseFloat(item.saat || 0);
                        totalAmount += parseFloat(item.tutar || 0);
                        
                        html += `
                            <tr>
                                <td>${item.gun_formatted}</td>
                                <td>${item.project_name || '-'}</td>
                                <td><span class="badge bg-blue-lt">${item.puantaj_adi}</span></td>
                                <td class="text-end font-weight-medium">${parseFloat(item.saat).toFixed(1)}</td>
                                <td class="text-end text-primary font-weight-bold">${item.tutar_formatted}</td>
                            </tr>
                        `;
                    });

                    $('#puantaj_detail_rows').html(html);
                    $('#modal_total_hours').text(totalHours.toFixed(1).replace('.', ',') + ' Saat');
                    $('#modal_total_amount').text(totalAmount.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' }));
                } else {
                    $('#puantaj_detail_rows').html('<tr><td colspan="5" class="text-center text-danger py-4">' + (response.message || 'Hata oluştu') + '</td></tr>');
                }
            },
            error: function() {
                $('#puantaj_detail_rows').html('<tr><td colspan="5" class="text-center text-danger py-4">Sistem hatası</td></tr>');
            }
        });
    });
});
</script>