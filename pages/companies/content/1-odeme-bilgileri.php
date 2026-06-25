<?php
require_once "Model/CaseTransactions.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/date.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$ctObj = new CaseTransactions();
$db = $ctObj->getDb();

$stmt = $db->prepare("SELECT ct.*, c.case_name FROM case_transactions ct LEFT JOIN cases c ON ct.case_id = c.id WHERE ct.company_id = ? ORDER BY ct.date DESC, ct.id DESC");
$stmt->execute([$id]);
$transactions = $stmt->fetchAll(PDO::FETCH_OBJ);

$totalIncome = 0;
$totalExpense = 0;

foreach ($transactions as $t) {
    if ($t->type_id == 1) {
        $totalIncome += $t->amount;
    } else if ($t->type_id == 2) {
        $totalExpense += $t->amount;
    }
}
$balance = $totalIncome - $totalExpense;
?>

<div class="row row-cards mb-4">
    <div class="col-sm-4 col-lg-4">
        <div class="card card-sm border-0 bg-success-lt p-3" style="border-radius: 12px;">
            <div class="d-flex align-items-center">
                <span class="avatar bg-success text-white me-3" style="border-radius: 8px;">
                    <i class="ti ti-arrow-up-right icon" style="font-size: 1.5rem;"></i>
                </span>
                <div>
                    <div class="text-secondary fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Toplam Alınan (Gelir)</div>
                    <div class="h2 mb-0 fw-bold text-success-emphasis"><?php echo Helper::formattedMoney($totalIncome); ?> ₺</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4 col-lg-4">
        <div class="card card-sm border-0 bg-danger-lt p-3" style="border-radius: 12px;">
            <div class="d-flex align-items-center">
                <span class="avatar bg-danger text-white me-3" style="border-radius: 8px;">
                    <i class="ti ti-arrow-down-left icon" style="font-size: 1.5rem;"></i>
                </span>
                <div>
                    <div class="text-secondary fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Toplam Ödenen (Gider)</div>
                    <div class="h2 mb-0 fw-bold text-danger-emphasis"><?php echo Helper::formattedMoney($totalExpense); ?> ₺</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-4 col-lg-4">
        <div class="card card-sm border-0 bg-primary-lt p-3" style="border-radius: 12px;">
            <div class="d-flex align-items-center">
                <span class="avatar bg-primary text-white me-3" style="border-radius: 8px;">
                    <i class="ti ti-scale icon" style="font-size: 1.5rem;"></i>
                </span>
                <div>
                    <div class="text-secondary fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">Bakiye (Net Durum)</div>
                    <div class="h2 mb-0 fw-bold text-primary-emphasis"><?php echo Helper::formattedMoney($balance); ?> ₺</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">Gelir Gider Listesi</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-hover table-vcenter text-nowrap card-table datatable">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kasa</th>
                    <th>Tür</th>
                    <th>Tutar</th>
                    <th>Açıklama</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) > 0) {
                    foreach ($transactions as $t) {
                        $badgeClass = $t->type_id == 1 ? "bg-success-lt" : "bg-danger-lt";
                        $typeText = $t->type_id == 1 ? "Gelir" : "Gider";
                        ?>
                        <tr>
                            <td><?php echo Date::dmY($t->date); ?></td>
                            <td><?php echo $t->case_name ?? 'Bilinmiyor'; ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $typeText; ?></span></td>
                            <td class="font-weight-bold text-nowrap text-dark"><?php echo Helper::formattedMoney($t->amount); ?> ₺</td>
                            <td class="text-secondary" style="max-width: 350px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($t->description); ?>"><?php echo $t->description; ?></td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="5" class="text-center text-secondary py-3">Kayıtlı ödeme hareketi bulunamadı.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>