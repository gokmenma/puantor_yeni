<?php
require_once '../../Database/require.php';
require_once '../../Model/Persons.php';
require_once '../../Model/Bordro.php';
require_once '../../Model/MyFirmModel.php';
require_once '../../Model/DefinesModel.php';
require_once '../../App/Helper/security.php';
require_once '../../App/Helper/helper.php';
require_once '../../App/Helper/date.php';

use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

$Persons = new Persons();
$Bordro = new Bordro();
$MyFirm = new MyFirmModel();
$Defines = new DefinesModel();

$firm_id = $_SESSION['firm_id'];
$firm = $MyFirm->find($firm_id);

$personel_id = Security::decrypt($_POST['id']);
$ay = $_POST['month'];
$yil = $_POST['year'];

$person = $Persons->find($personel_id);

// Personel Gelir Bilgileri
$incomes = $Bordro->getPersonIncome($personel_id, $ay, $yil);

// Personel Gider Bilgileri
$expenses = $Bordro->getPersonExpense($personel_id, $ay, $yil);

?>

<div class="row">
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-status-top bg-blue"></div>
            <div class="card-header">
                <h3 class="card-title">Çalışan Bilgileri</h3>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-secondary">Adı Soyadı:</td>
                        <td class="text-end fw-bold"><?php echo $person->full_name; ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">TC Kimlik No:</td>
                        <td class="text-end fw-bold"><?php echo Security::safeDecrypt($person->kimlik_no); ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Dönem:</td>
                        <td class="text-end fw-bold"><?php echo Date::monthName($ay) . ' ' . $yil; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card mb-3">
            <div class="card-status-top bg-green"></div>
            <div class="card-header">
                <h3 class="card-title">Firma Bilgileri</h3>
            </div>
            <div class="card-body p-2">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-secondary">Firma Adı:</td>
                        <td class="text-end fw-bold text-truncate" style="max-width: 150px;"><?php echo $firm->firm_name; ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Telefon:</td>
                        <td class="text-end fw-bold"><?php echo $firm->phone; ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-blue-lt">
                <h3 class="card-title">Gelirler</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <thead>
                        <tr>
                            <th>Türü</th>
                            <th class="text-end">Tutar (₺)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_income = 0;
                        if (!empty($incomes)):
                            foreach ($incomes as $income):
                                $total_income += $income->tutar;
                                ?>
                                <tr>
                                    <td><?php echo $income->turu; ?></td>
                                    <td class="text-end"><?php echo Helper::formattedMoneyWithoutCurrency($income->tutar); ?></td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-secondary">Gelir bulunamadı</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Toplam</td>
                            <td class="text-end"><?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-red-lt">
                <h3 class="card-title">Giderler / Kesintiler</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-sm">
                    <thead>
                        <tr>
                            <th>Türü</th>
                            <th class="text-end">Tutar (₺)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $total_expense = 0;
                        if (!empty($expenses)):
                            foreach ($expenses as $expense):
                                $total_expense += $expense->tutar;
                                ?>
                                <tr>
                                    <td><?php echo $Defines->getTypeNameById($expense->kategori ?? 0) . " - " . $expense->turu; ?></td>
                                    <td class="text-end"><?php echo Helper::formattedMoneyWithoutCurrency($expense->tutar); ?></td>
                                </tr>
                            <?php endforeach;
                        else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-secondary">Kesinti bulunamadı</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td>Toplam</td>
                            <td class="text-end"><?php echo Helper::formattedMoneyWithoutCurrency($total_expense); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-3 bg-primary-lt">
    <div class="card-body p-3">
        <div class="d-flex align-items-center justify-content-between">
            <h3 class="mb-0">Ödenecek Tutar:</h3>
            <h2 class="mb-0 text-primary">₺<?php echo Helper::formattedMoneyWithoutCurrency($total_income - $total_expense); ?></h2>
        </div>
    </div>
</div>
