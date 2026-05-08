<?php
// Puantor Mobil - Kasa & Finans Özeti ve Yönetimi
require_once ROOT . "/Model/Cases.php";
require_once ROOT . "/Model/CaseTransactions.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/DefinesModel.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/financial.php";
require_once ROOT . "/App/Helper/company.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$firm_id = $_SESSION['firm_id'] ?? 0;

$caseObj = new Cases();
$ct = new CaseTransactions();
$projectsModel = new Projects();
$personsModel = new Persons();
$financial = new Financial();
$define = new DefinesModel();
$CompanyHelper = new CompanyHelper();

// Get transactions for the firm
$transactions = $ct->allTransactionByFirm($firm_id);

// Personel filtresi kontrolü
$person_filter_id = 0;
if (isset($_GET['person_id'])) {
    $person_filter_id = Security::decrypt($_GET['person_id']);
}

if ($person_filter_id > 0) {
    // Sadece bu personele ait işlemleri filtrele
    $transactions = array_filter($transactions, function($t) use ($person_filter_id) {
        return $t->person_id == $person_filter_id;
    });

    $total_income = 0;
    $total_expense = 0;
    foreach ($transactions as $t) {
        if ($t->type_id == 1) $total_income += $t->amount;
        else $total_expense += $t->amount;
    }
    $current_balance = $total_income - $total_expense;
} else {
    // Get overall firm balance
    $balanceData = $ct->getFirmBalance($firm_id);
    $total_income = $balanceData->total_income ?? 0;
    $total_expense = $balanceData->total_expense ?? 0;
    $current_balance = $total_income - $total_expense;
}

// Calculate today's net activity
$today_net = 0;
foreach ($transactions as $t) {
    if (date('Y-m-d', strtotime($t->date)) == date('Y-m-d')) {
        if ($t->type_id == 1) {
            $today_net += $t->amount;
        } elseif ($t->type_id == 2) {
            $today_net -= $t->amount;
        }
    }
}

// Fetch active cases, projects, persons and companies for form selection options
$active_cases = $caseObj->allCaseWithFirmId();
$active_projects = $projectsModel->getProjectsByFirm($firm_id);
$active_persons = $personsModel->getPersonsByFirm($firm_id);

// Cache Cases
$casesCache = [];
foreach ($active_cases as $c) {
    $casesCache[$c->id] = $c;
}
?>
<?php $apiUrl = '/api/financial/transaction.php'; ?>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>

<style>
/* Custom styled tabs for premium mobile feel */
.nav-pills .nav-link {
    color: #626976;
    background: transparent;
    transition: all 0.25s;
}
.nav-pills .nav-link.active {
    color: var(--mobile-primary) !important;
    background: #ffffff !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
body[data-bs-theme="dark"] .nav-pills .nav-link.active {
    color: #fff !important;
    background: #1e293b !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}
.form-selectgroup-input:checked + .form-selectgroup-label {
    border-color: var(--mobile-primary) !important;
    background: rgba(32, 107, 196, 0.04) !important;
}
body[data-bs-theme="dark"] .form-selectgroup-input:checked + .form-selectgroup-label {
    background: rgba(32, 107, 196, 0.15) !important;
}
.custom-toast {
    display: none;
}
.transaction-item {
    transition: background-color 0.2s;
}
.transaction-item:hover {
    background-color: rgba(0,0,0,0.01);
}
body[data-bs-theme="dark"] .transaction-item:hover {
    background-color: rgba(255,255,255,0.01);
}
.btn-delete-transaction:hover {
    color: #d63f3f !important;
}
select.form-select {
    border-radius: 10px !important;
    border-color: rgba(0, 0, 0, 0.1) !important;
    padding: 0.5rem 0.75rem !important;
    height: auto !important;
    font-size: 0.85rem !important;
}
body[data-bs-theme="dark"] select.form-select {
    border-color: var(--mobile-card-border-dark) !important;
    background-color: #1e293b !important;
    color: #f4f6fa !important;
}
/* Custom Select2 Styling for Mobile */
.select2-container--default .select2-selection--single {
    border-radius: 10px !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    height: 44px !important;
    padding: 0.5rem 0.75rem !important;
    background-color: #fff !important;
    display: flex !important;
    align-items: center !important;
}
body[data-bs-theme="dark"] .select2-container--default .select2-selection--single {
    border-color: var(--mobile-card-border-dark) !important;
    background-color: #1e293b !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: inherit !important;
    padding-left: 0 !important;
    line-height: normal !important;
    font-size: 0.85rem !important;
}
body[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #f4f6fa !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 44px !important;
    right: 12px !important;
    display: flex !important;
    align-items: center !important;
}
.select2-dropdown {
    border-radius: 12px !important;
    border-color: rgba(0, 0, 0, 0.08) !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
    background-color: #fff !important;
    overflow: hidden !important;
    z-index: 1060 !important;
}
body[data-bs-theme="dark"] .select2-dropdown {
    background-color: #1e293b !important;
    border-color: var(--mobile-card-border-dark) !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--mobile-primary) !important;
}
.select2-container {
    width: 100% !important;
}
.form-selectgroup-input:checked + .btn-type-income {
    border-color: #2fb344 !important;
    background: rgba(47, 179, 68, 0.08) !important;
    color: #2fb344 !important;
}
.form-selectgroup-input:checked + .btn-type-expense {
    border-color: #d63f3f !important;
    background: rgba(214, 63, 63, 0.08) !important;
    color: #d63f3f !important;
}

/* Swipe to Delete Styles */
.transaction-item-wrapper {
    position: relative;
    overflow: hidden;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    user-select: none;
}
body[data-bs-theme="dark"] .transaction-item-wrapper,
body[data-bs-theme="dark"] .transaction-item-content {
    background: #1e293b !important;
}
.transaction-item-actions {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    display: flex;
    align-items: center;
    background: #d63f3f;
    z-index: 1;
}
.transaction-item-content {
    position: relative;
    background: #fff;
    z-index: 2;
    transition: transform 0.2s ease-out;
    width: 100%;
    padding: 1rem;
}
.btn-swipe-delete {
    color: white;
    width: 70px;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    font-size: 0.7rem;
    font-weight: 600;
}
.btn-swipe-delete i {
    font-size: 1.2rem;
    margin-bottom: 2px;
}
</style>

<div class="container px-0">
  <div class="mb-4 d-flex align-items-center justify-content-between">
    <div>
      <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Kasa & Finans</h2>
      <p class="text-muted text-xs mb-0">Bugünkü mali durumunuzun özeti.</p>
    </div>
  </div>

  <!-- Kasa Bakiyesi Kartı -->
  <div class="mobile-card bg-primary text-white p-4 mb-4 position-relative overflow-hidden" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #206bc4 0%, #104b8c 100%) !important;">
    <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
      <i class="ti ti-wallet"></i>
    </div>
    <div class="d-flex align-items-center justify-content-between mb-2">
      <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;">Toplam Kasa Bakiyesi</span>
      <i class="ti ti-wallet" style="font-size: 1.5rem; opacity: 0.8;"></i>
    </div>
    <h3 class="mb-0 text-bold" style="font-size: 2.2rem; letter-spacing: -1px;">₺ <?php echo Helper::formattedMoneyWithoutCurrency($current_balance); ?></h3>
    <div class="mt-3 d-flex gap-2">
      <span class="badge bg-white-10 text-white text-xs d-flex align-items-center gap-1" style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 4px 10px;">
        <i class="ti <?php echo $today_net >= 0 ? 'ti-trending-up' : 'ti-trending-down'; ?>"></i>
        <?php echo ($today_net >= 0 ? '+ ' : '- ') . '₺ ' . Helper::formattedMoneyWithoutCurrency(abs($today_net)) . ' Bugün'; ?>
      </span>
    </div>
  </div>

  <div class="row g-1 mb-2">
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(47, 179, 68, 0.1); color: #2fb344; border-radius: 16px;">
        <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Gelirler</div>
        <div class="text-bold h3 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_income); ?></div>
      </div>
    </div>
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(214, 63, 63, 0.1); color: #d63f3f; border-radius: 16px;">
        <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Giderler</div>
        <div class="text-bold h3 mb-0">₺ <?php echo Helper::formattedMoneyWithoutCurrency($total_expense); ?></div>
      </div>
    </div>
  </div>

  <!-- İşlem Filtreleri -->
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="mb-0 text-semibold" style="font-size: 0.95rem;">Son İşlemler</h4>
    <div class="btn-group btn-group-sm" role="group" style="border-radius: 8px; overflow: hidden; background: rgba(0,0,0,0.03);">
      <button type="button" class="btn btn-light btn-filter active" data-filter="all" style="font-size: 0.7rem; padding: 4px 8px;">Tümü</button>
      <button type="button" class="btn btn-light btn-filter" data-filter="income" style="font-size: 0.7rem; padding: 4px 8px;">Gelir</button>
      <button type="button" class="btn btn-light btn-filter" data-filter="expense" style="font-size: 0.7rem; padding: 4px 8px;">Gider</button>
    </div>
  </div>

  <div class="list-group list-group-mobile mb-4" id="transactions-list">
    <?php if (empty($transactions)): ?>
      <div class="text-center py-5 bg-white rounded-3 border">
        <i class="ti ti-receipt-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
        <p class="text-muted text-sm mb-0">Kasa hareketi bulunamadı.</p>
      </div>
    <?php else: ?>
      <?php foreach ($transactions as $t): 
        $is_income = $t->type_id == 1;
        $sub_type_name = $define->getTypeNameById($t->users_type_id);
        $case_name = $casesCache[$t->case_id]->case_name ?? 'Kasa';
        $item_date = date('d.m.Y', strtotime($t->date));
      ?>
        <div class="transaction-item-wrapper transaction-item" data-type="<?php echo $is_income ? 'income' : 'expense'; ?>">
          <div class="transaction-item-actions">
            <button class="btn-swipe-delete btn-delete-transaction" data-id="<?php echo Security::encrypt($t->id); ?>">
              <i class="ti ti-trash"></i>
              <span>Sil</span>
            </button>
          </div>
          <div class="transaction-item-content d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: <?php echo $is_income ? 'rgba(47, 179, 68, 0.15)' : 'rgba(214, 63, 63, 0.15)'; ?>; color: <?php echo $is_income ? '#2fb344' : '#d63f3f'; ?>;">
                <i class="ti <?php echo $is_income ? 'ti-arrow-up-right' : 'ti-arrow-down-left'; ?>" style="font-size: 1.25rem;"></i>
              </div>
              <div>
                <div class="text-bold text-sm" style="color: var(--tblr-body-color, #1d273b);"><?php echo htmlspecialchars($t->account_name ?: ($sub_type_name ?: ($is_income ? 'Gelir' : 'Gider'))); ?></div>
                <div class="text-muted text-xs d-flex align-items-center gap-1 mt-0.5">
                  <span><?php echo htmlspecialchars($case_name); ?></span>
                  <span class="text-muted-50">•</span>
                  <span><?php echo $item_date; ?></span>
                </div>
                <?php if (!empty($t->description)): ?>
                  <div class="text-muted text-xs font-italic mt-1" style="font-size: 0.7rem; opacity: 0.85;">"<?php echo htmlspecialchars($t->description); ?>"</div>
                <?php endif; ?>
              </div>
            </div>
            <div class="text-bold text-sm <?php echo $is_income ? 'text-green' : 'text-red'; ?>" style="font-size: 0.9rem;">
              <?php echo ($is_income ? '+' : '-') . ' ₺' . Helper::formattedMoneyWithoutCurrency($t->amount); ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Floating Action Button (FAB) -->
<a href="#" class="mobile-fab" data-bs-toggle="modal" data-bs-target="#add-transaction-modal">
  <i class="ti ti-plus"></i>
</a>

<!-- Add Transaction Modal -->
<div class="modal modal-blur fade" id="add-transaction-modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
    <div class="modal-content" style="border-radius: 20px; border: none;">
      <div class="modal-header py-3" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
        <h5 class="modal-title text-semibold" style="font-size: 1.05rem;">Yeni İşlem Ekle</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <form id="add-transaction-form">
          <input type="hidden" name="transaction_id" value="0">
          <input type="hidden" name="gm_amount_money" value="1">

          <!-- İlişkilendirme Türü Tabs -->
          <div class="mb-3">
            <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">İlişki Türü</label>
            <ul class="nav nav-pills nav-fill bg-light p-1 rounded-3" role="tablist" style="border: 1px solid rgba(0,0,0,0.05);">
              <li class="nav-item" role="presentation">
                <a href="#tab-project" class="nav-link active py-2 text-xs text-semibold" data-bs-toggle="pill" role="tab" style="border-radius: 6px;">Proje</a>
              </li>
              <li class="nav-item" role="presentation">
                <a href="#tab-person" class="nav-link py-2 text-xs text-semibold" data-bs-toggle="pill" role="tab" style="border-radius: 6px;">Personel</a>
              </li>
              <li class="nav-item" role="presentation">
                <a href="#tab-company" class="nav-link py-2 text-xs text-semibold" data-bs-toggle="pill" role="tab" style="border-radius: 6px;">Firma</a>
              </li>
            </ul>
          </div>

          <!-- Tab Contents -->
          <div class="tab-content mb-4">
            <!-- Proje Tab -->
            <div class="tab-pane fade show active" id="tab-project" role="tabpanel">
              <div class="form-floating">
                <select name="gm_project_id" id="floatingProjectSelect" class="form-select select2-init">
                  <option value="0">Proje Yok</option>
                  <?php foreach ($active_projects as $p): ?>
                    <option value="<?php echo $p->id; ?>"><?php echo htmlspecialchars($p->project_name); ?></option>
                  <?php endforeach; ?>
                </select>
                <label for="floatingProjectSelect">Proje Seçimi</label>
              </div>
            </div>

            <!-- Personel Tab -->
            <div class="tab-pane fade" id="tab-person" role="tabpanel">
              <div class="form-floating">
                <select name="gm_person_name" id="floatingPersonSelect" class="form-select select2-init">
                  <option value="0">Personel Yok</option>
                  <?php foreach ($active_persons as $p): ?>
                    <option value="<?php echo Security::encrypt($p->id); ?>"><?php echo htmlspecialchars($p->full_name); ?></option>
                  <?php endforeach; ?>
                </select>
                <label for="floatingPersonSelect">Personel Seçimi</label>
              </div>
            </div>

            <!-- Firma Tab -->
            <div class="tab-pane fade" id="tab-company" role="tabpanel">
              <div class="form-floating">
                <?php echo $CompanyHelper->getCompanySelect(name: "gm_company"); ?>
                <label for="gm_company">Firma Seçimi</label>
              </div>
            </div>
          </div>

          <!-- Gelir / Gider Tipi Seçimi -->
          <div class="mb-3">
            <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">İşlem Yönü</label>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-selectgroup-item w-100">
                  <input type="radio" name="transaction_type" value="1" class="form-selectgroup-input" checked>
                  <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2.5 rounded-3 text-semibold text-xs border btn-type-income" style="cursor: pointer; transition: all 0.2s;">
                    <i class="ti ti-arrow-up-right text-success me-1"></i> Gelir
                  </span>
                </label>
              </div>
              <div class="col-6">
                <label class="form-selectgroup-item w-100">
                  <input type="radio" name="transaction_type" value="2" class="form-selectgroup-input">
                  <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2.5 rounded-3 text-semibold text-xs border btn-type-expense" style="cursor: pointer; transition: all 0.2s;">
                    <i class="ti ti-arrow-down-left text-danger me-1"></i> Gider
                  </span>
                </label>
              </div>
            </div>
          </div>

          <!-- Kasa Seçimi -->
          <div class="form-floating mb-3">
            <select name="gm_case_id" id="gm_case_id" class="form-select select2-init" required>
              <option value="0">Kasa Seçiniz</option>
              <?php foreach ($active_cases as $c): ?>
                <option value="<?php echo Security::encrypt($c->id); ?>" <?php echo $c->isDefault ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c->case_name . " - " . $c->bank_name . " / " . $c->branch_name); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <label for="gm_case_id">Kasa <span class="text-danger">*</span></label>
          </div>

          <!-- Tutar -->
          <div class="form-floating mb-3">
            <input type="text" name="amount" id="amount-input" class="form-control text-bold" placeholder="0,00" required autocomplete="off">
            <label for="amount-input">Tutar (₺) <span class="text-danger">*</span></label>
          </div>

          <!-- Tarih & Tür -->
          <div class="row g-2 mb-3">
            <div class="col-6">
              <div class="form-floating">
                <input type="text" name="transaction_date" id="transaction_date" class="form-control" value="<?php echo date('d.m.Y'); ?>" placeholder="İşlem Tarihi">
                <label for="transaction_date">İşlem Tarihi</label>
              </div>
            </div>
            <div class="col-6">
              <div class="form-floating">
                <select name="gm_incexp_type" id="gm_incexp_type" class="form-select select2-init" required>
                  <option value="">Yükleniyor...</option>
                </select>
                <label for="gm_incexp_type">İşlem Türü <span class="text-danger">*</span></label>
              </div>
            </div>
          </div>

          <!-- Açıklama -->
          <div class="form-floating mb-3">
            <textarea name="description" id="floatingDescription" class="form-control" placeholder="İşlem açıklaması yazınız..." style="height: 100px; resize: none;"></textarea>
            <label for="floatingDescription">Açıklama</label>
          </div>

        </form>
      </div>
      <div class="modal-footer py-2.5 bg-light d-flex justify-content-between" style="border-top: 1px solid rgba(0,0,0,0.06); border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
        <button type="button" class="btn btn-link text-muted text-xs text-semibold text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
        <button type="button" class="btn btn-primary px-4 py-2 text-xs text-semibold" id="submit-transaction" style="border-radius: 10px; background: var(--mobile-primary); border: none;">
          <i class="ti ti-plus me-1"></i> Kaydet
        </button>
      </div>
    </div>
  </div>
</div>



<script>
$(document).ready(function() {
    const apiUrl = '<?php echo $apiUrl; ?>';

    // Initialize Select2 with dropdownParent to fix modal focus issues
    if (jQuery.fn && jQuery.fn.select2) {
        jQuery('.select2-init, select.select2').select2({
            dropdownParent: jQuery('#add-transaction-modal')
        });
    }

    // 1. Initialize Flatpickr with global Turkish locale
    if (typeof flatpickr !== 'undefined') {
        flatpickr("#transaction_date", {
            dateFormat: "d.m.Y",
            locale: "tr",
            disableMobile: "true"
        });
    }

    // 2. Custom Toast Function (Updated to SweetAlert2)
    function showToast(message, isError = false) {
        Swal.fire({
            title: isError ? 'Hata!' : 'Başarılı!',
            text: message,
            icon: isError ? 'error' : 'success',
            showConfirmButton: false,
            timer: 2000,
            toast: true,
            position: 'top',
            timerProgressBar: true,
            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
        });
    }

    // 3. Load Sub-Types on load and on change
    function fetchSubTypes(type) {
        jQuery.post('/api/financial/transaction.php', {
            action: 'getSubTypes',
            type: type
        }, function(response) {
            try {
                var res = typeof response === 'object' ? response : JSON.parse(response);
                var select = $('#gm_incexp_type');
                select.empty();
                select.append('<option value="">Tür Seçiniz</option>');
                if (res.subTypes && res.subTypes.length > 0) {
                    res.subTypes.forEach(function(item) {
                        select.append('<option value="' + item.id + '">' + item.name + '</option>');
                    });
                }
                if ($.fn.select2) {
                    select.trigger('change');
                }
            } catch (e) {
                console.error("Error parsing sub-types", e);
            }
        });
    }

    // Fetch Gelir sub-types initially (default is Gelir = 1)
    fetchSubTypes(1);

    // Watch transaction type radios
    $('input[name="transaction_type"]').change(function() {
        fetchSubTypes($(this).val());
    });

    // 4. Filter Transactions instantly
    $('.btn-filter').click(function() {
        $('.btn-filter').removeClass('active');
        $(this).addClass('active');
        var filter = $(this).data('filter');
        
        if (filter === 'all') {
            $('.transaction-item').fadeIn(200);
        } else {
            $('.transaction-item').hide();
            $('.transaction-item[data-type="' + filter + '"]').fadeIn(200);
        }
    });

    // 5. Submit New Transaction
    $('#submit-transaction').click(function(e) {
        e.preventDefault();
        
        var form = $('#add-transaction-form');
        var caseId = $('#gm_case_id').val();
        var amount = $('#amount-input').val();
        var incExpType = $('#gm_incexp_type').val();
        
        if (caseId == '0' || !caseId) {
            showToast('Lütfen bir kasa seçiniz.', true);
            return;
        }
        if (!amount || amount == '0' || amount == '') {
            showToast('Lütfen geçerli bir tutar giriniz.', true);
            return;
        }
        if (!incExpType) {
            showToast('Lütfen işlem türü seçiniz.', true);
            return;
        }

        // Active tab detection for project/person/company
        var activeTab = $('.nav-pills .nav-link.active').attr('href');
        // Clear non-active tab values to avoid sending conflicting data
        if (activeTab !== '#tab-project') {
            form.find('[name="gm_project_id"]').val(0);
        }
        if (activeTab !== '#tab-person') {
            form.find('[name="gm_person_name"]').val(0);
        }
        if (activeTab !== '#tab-company') {
            form.find('[name="gm_company"]').val(0);
        }

        var formData = form.serializeArray();
        formData.push({ name: 'action', value: 'saveTransaction' });

        jQuery.ajax({
            type: 'POST',
            url: '/api/financial/transaction.php',
            data: formData,
            success: function(response) {
                try {
                    var res = typeof response === 'object' ? response : JSON.parse(response);
                    if (res.status === 'success') {
                        showToast(res.message, false);
                        $('#add-transaction-modal').modal('hide');
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        showToast(res.message || 'Bir hata oluştu.', true);
                    }
                } catch (err) {
                    showToast('Beklenmeyen bir cevap alındı.', true);
                }
            },
            error: function() {
                showToast('İşlem gerçekleştirilemedi. Sunucu hatası.', true);
            }
        });
    });

    // 6. Delete Transaction
    $(document).on('click', '.btn-delete-transaction', function(e) {
        e.preventDefault();
        var btn = $(this);
        var id = btn.data('id');
        
        if (confirm('Bu kasa hareketini silmek istediğinize emin misiniz?')) {
            jQuery.post('/api/financial/transaction.php?type=1', {
                action: 'deleteTransaction',
                id: id
            }, function(response) {
                try {
                    var res = typeof response === 'object' ? response : JSON.parse(response);
                    if (res.status === 'success') {
                        showToast(res.message, false);
                        btn.closest('.transaction-item').fadeOut(300, function() {
                            $(this).remove();
                            // If no items left, show empty state
                            if ($('#transactions-list .transaction-item').length === 0) {
                                $('#transactions-list').html(
                                    '<div class="text-center py-5 bg-white rounded-3 border">' +
                                    '<i class="ti ti-receipt-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>' +
                                    '<p class="text-muted text-sm mb-0">Kasa hareketi bulunamadı.</p>' +
                                    '</div>'
                                );
                            }
                        });
                    } else {
                        showToast(res.message || 'Silme işlemi gerçekleştirilemedi.', true);
                    }
                } catch (err) {
                    showToast('Cevap işlenemedi.', true);
                }
            });
        }
    });

    // 8. Swipe to delete functionality
    let touchStartX = 0;
    let touchMoveX = 0;
    let currentSwipeItem = null;
    const swipeThreshold = 70;

    $(document).on('touchstart', '.transaction-item-content', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
        currentSwipeItem = $(this);
        
        // Reset other open items
        $('.transaction-item-content').not(currentSwipeItem).css('transform', 'translateX(0)');
    });

    $(document).on('touchmove', '.transaction-item-content', function(e) {
        touchMoveX = e.originalEvent.touches[0].clientX;
        let diff = touchStartX - touchMoveX;
        
        // Only swipe left
        if (diff > 0) {
            if (diff > swipeThreshold + 20) diff = swipeThreshold + 20; // Limit over-swipe
            $(this).css('transition', 'none');
            $(this).css('transform', 'translateX(-' + diff + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    $(document).on('touchend', '.transaction-item-content', function(e) {
        let diff = touchStartX - touchMoveX;
        $(this).css('transition', 'transform 0.2s ease-out');
        
        if (diff > swipeThreshold / 2) {
            $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    // Close swipe on click elsewhere
    $(document).on('touchstart', function(e) {
        if (!$(e.target).closest('.transaction-item-wrapper').length) {
            $('.transaction-item-content').css('transform', 'translateX(0)');
        }
    });
});
</script>
