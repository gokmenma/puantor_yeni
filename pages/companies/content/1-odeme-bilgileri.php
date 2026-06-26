<?php
require_once "Model/CaseTransactions.php";
require_once "Model/Cases.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/date.php";
require_once "App/Helper/financial.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$ctObj = new CaseTransactions();
$db = $ctObj->getDb();
$financialHelper = new Financial();
$casesObj = new Cases();
$defaultCaseId = $casesObj->getDefaultCaseIdByFirm();

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
        <table class="table table-hover table-vcenter text-nowrap card-table">
            <thead>
                <tr>
                    <th>Tarih</th>
                    <th>Kasa</th>
                    <th>Tür</th>
                    <th>Tutar</th>
                    <th>Açıklama</th>
                    <th style="width:8%"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($transactions) > 0) {
                    foreach ($transactions as $t) {
                        $badgeClass = $t->type_id == 1 ? "bg-success-lt text-success" : "bg-danger-lt text-danger";
                        $typeText   = $t->type_id == 1 ? "Gelir" : "Gider";
                        $encId      = Security::encrypt($t->id);
                        ?>
                        <tr>
                            <td><?php echo Date::dmY($t->date); ?></td>
                            <td><?php echo htmlspecialchars($t->case_name ?? 'Bilinmiyor'); ?></td>
                            <td><span class="badge <?php echo $badgeClass; ?>"><?php echo $typeText; ?></span></td>
                            <td class="fw-bold text-nowrap"><?php echo Helper::formattedMoney($t->amount); ?> ₺</td>
                            <td class="text-secondary" style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($t->description ?? ''); ?>"><?php echo htmlspecialchars($t->description ?? ''); ?></td>
                            <td class="text-end">
                                <a href="#" class="btn btn-sm btn-ghost-secondary edit-company-payment me-1"
                                   data-id="<?php echo $encId; ?>">
                                    <i class="ti ti-pencil icon"></i>
                                </a>
                                <a href="#" class="btn btn-sm btn-ghost-danger delete-company-payment"
                                   data-id="<?php echo $encId; ?>">
                                    <i class="ti ti-trash icon"></i>
                                </a>
                            </td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="6" class="text-center text-secondary py-3">Kayıtlı ödeme hareketi bulunamadı.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<input type="hidden" id="cp_company_id" value="<?php echo $new_id; ?>">

<div class="modal modal-blur fade" id="company-payment-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius:1.25rem;overflow:hidden;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-3 fw-bold text-primary" id="cp-modal-title">Ödeme Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="companyPaymentForm">
                <input type="hidden" name="action" value="saveCompanyPayment">
                <input type="hidden" name="cp_company_id" value="<?php echo $new_id; ?>">
                <input type="hidden" name="cp_id" id="cp_id" value="0">
                <div class="modal-body pt-3">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label required mb-2">İşlem Türü</label>
                            <div class="form-selectgroup w-100">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="cp_type_id" value="1" class="form-selectgroup-input" checked>
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="avatar avatar-sm bg-success-lt me-3">
                                            <i class="ti ti-arrow-up-right text-success"></i>
                                        </span>
                                        <div>
                                            <div class="fw-bold">Gelir</div>
                                            <div class="text-secondary small">Firmadan Alınan</div>
                                        </div>
                                    </div>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="cp_type_id" value="2" class="form-selectgroup-input">
                                    <div class="form-selectgroup-label d-flex align-items-center p-3">
                                        <span class="avatar avatar-sm bg-danger-lt me-3">
                                            <i class="ti ti-arrow-down-left text-danger"></i>
                                        </span>
                                        <div>
                                            <div class="fw-bold">Gider</div>
                                            <div class="text-secondary small">Firmaya Yapılan Ödeme</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Tutar</label>
                            <div class="input-group">
                                <input type="text" name="cp_amount" id="cp_amount" class="form-control" placeholder="0,00" required>
                                <span class="input-group-text">₺</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Tarih</label>
                            <input type="text" name="cp_date" id="cp_date" class="form-control" value="<?php echo date('d.m.Y'); ?>" required autocomplete="off">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Kasa</label>
                            <?php echo $financialHelper->getCasesSelectByUser("cp_case_id", $defaultCaseId); ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Açıklama</label>
                            <textarea name="cp_description" id="cp_description" class="form-control" rows="2" placeholder="Açıklama giriniz"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-lt border-0">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary px-4" id="saveCompanyPayment">
                        <i class="ti ti-device-floppy icon me-2"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-selectgroup-item .form-selectgroup-label {
    border-radius: 0.5rem;
    cursor: pointer;
    border: 2px solid #e6e7e9;
    transition: border-color .15s, background .15s;
}
.form-selectgroup-item .form-selectgroup-input:checked ~ .form-selectgroup-label {
    border-color: #206bc4;
    background-color: rgba(32,107,196,.04);
    z-index: 1;
}
</style>
