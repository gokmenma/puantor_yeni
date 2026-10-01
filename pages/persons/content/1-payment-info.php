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

// Gelir gider bilgilerini getir
$income_expenses_raw = $bordro->getPersonWorkTransactions($id);

// Yürüyen bakiye hesaplamak için eskiden yeniye sırala
usort($income_expenses_raw, function($a, $b) {
    $dateA = str_replace('-', '', $a->gun);
    $dateB = str_replace('-', '', $b->gun);
    if ($dateA == $dateB) {
        return (int)$a->id - (int)$b->id;
    }
    return strcmp($dateA, $dateB);
});

$running_balance = 0;
foreach ($income_expenses_raw as &$item) {
    $type = $financialHelper->getTransactionTypeById($item->kategori);
    if ($type && $type->type_id == 1) {
        $running_balance += $item->tutar;
    } else {
        $running_balance -= $item->tutar;
    }
    $item->running_balance = $running_balance;
}
unset($item);

// Şimdi yeniden eskiye sırala (Görünüm için)
usort($income_expenses_raw, function($a, $b) {
    $dateA = str_replace('-', '', $a->gun);
    $dateB = str_replace('-', '', $b->gun);
    if ($dateA == $dateB) {
        return (int)$b->id - (int)$a->id;
    }
    return strcmp($dateB, $dateA);
});

$income_expenses = $income_expenses_raw;
$month = Date::getMonth();

// maas_gelir_gider tablosunda personelin toplam gelir, gider ve ödeme bilgilerini getir
$summary = $bordro->sumAllIncomeExpense($id);
$total_income = (float)($summary->total_income ?? 0);
$total_expense = (float)($summary->total_expense ?? 0);
$balance = $total_income - $total_expense;
$total_count = count($income_expenses);

$encrypted_person_id = Security::encrypt($id);
if (!$Auths->Authorize("person_page_income_expence_info")) {
    Helper::authorizePage();
    return;
}
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'payment-summary-collapsed',
            localStorage.getItem('payment_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>

<style>
html.payment-summary-collapsed #paymentSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}

.payment-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.payment-header-icon-action,
.payment-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.payment-header-icon-action i,
.payment-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

#paymentTabContainer .payment-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
    overflow: hidden;
}

#paymentSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

#paymentTabContainer .payment-table-card {
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
    overflow: hidden;
    background: #ffffff;
}

.payment-table-card > .payment-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.payment-table-card > .card-header {
    border-bottom: 0 !important;
}

.payment-search-wrap { position: relative; }
#payment-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.payment-search-wrap,
.payment-search-wrap.input-icon {
    height: 32px !important;
}
.payment-search-clear {
    position: absolute;
    top: 50%;
    right: 6px;
    z-index: 3;
    display: inline-flex;
    width: 22px;
    height: 22px;
    padding: 0;
    align-items: center;
    justify-content: center;
    transform: translateY(-50%);
    border: 0;
    border-radius: 50%;
    color: #64748b;
    background: #f1f5f9;
    cursor: pointer;
}
.payment-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

/* Tek Çerçeve (Kart ile Bütünleşik Tablo) */
table#person_paymentTable.data-table,
table#person_paymentTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#person_paymentTable.data-table tbody,
table#person_paymentTable.dataTable tbody,
table#person_paymentTable.data-table tbody tr:last-child,
table#person_paymentTable.dataTable tbody tr:last-child,
#person_paymentTable_wrapper .dt-layout-table,
#person_paymentTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#person_paymentTable.data-table thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 11.5px !important;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}
table#person_paymentTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Sütun Başlığı İçi Filtre Butonu ve Düzeni */
table#person_paymentTable.data-table thead th .dt-header-content {
    min-height: 24px;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#person_paymentTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#person_paymentTable.data-table td.actions-column .btn.btn-sm.btn-icon {
    width: 28px !important;
    min-width: 28px !important;
    height: 28px !important;
    min-height: 28px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
table#person_paymentTable.data-table td.actions-column .btn.btn-sm.btn-icon i {
    width: auto !important;
    height: auto !important;
    margin: 0 !important;
    font-size: 13px !important;
}
table#person_paymentTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#person_paymentTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#person_paymentTable.dataTable > tbody > tr:last-child > *,
table#person_paymentTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#person_paymentTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

#person_paymentTable_wrapper .dt-layout-row:last-child,
div#person_paymentTable_wrapper .dt-layout-row:has(.dt-paging),
div#person_paymentTable_wrapper .dt-layout-row:has(.dt-info) {
    margin: 0 !important;
    margin-top: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
}

.puantaj-row:hover {
    background-color: rgba(var(--tblr-primary-rgb), 0.05) !important;
    transition: background-color 0.2s ease;
}
.cursor-pointer {
    cursor: pointer !important;
}

/* Dark Mode */
[data-bs-theme="dark"] table#person_paymentTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#person_paymentTable.data-table,
[data-bs-theme="dark"] table#person_paymentTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#person_paymentTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#person_paymentTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#person_paymentTable.data-table tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] .payment-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] #paymentTabContainer .payment-summary-card,
[data-bs-theme="dark"] #paymentTabContainer .payment-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}
</style>

<div id="paymentTabContainer">
    <!-- KPI / İstatistik Özet Kartları (4'lü Grid) -->
    <div class="row row-cards g-3 mb-3" id="paymentSummaryCards">
        <!-- Kart 1: Gelir Toplamı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border payment-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">GELİR TOPLAMI</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-down-left" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="total_income" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_income) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">Puantaj & Ek Gelirler</span>
                        <span class="badge bg-success-lt fw-semibold" style="font-size: 10px;">Gelir</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Kesinti/Gider Toplamı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border payment-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">KESİNTİ / ÖDEME</span>
                        <div class="avatar avatar-sm rounded-2 bg-danger-lt text-danger" style="width: 32px; height: 32px;">
                            <i class="ti ti-arrow-up-right" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="total_expense" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($total_expense) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">Avans, İcra & Kesintiler</span>
                        <span class="badge bg-danger-lt fw-semibold" style="font-size: 10px;">Çıkış</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Güncel Bakiye -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border payment-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">GÜNCEL BAKİYE</span>
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-wallet" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold <?= $balance >= 0 ? 'text-success' : 'text-danger' ?>" id="balance" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= Helper::formattedMoney($balance) ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">Hesap Durumu</span>
                        <span class="badge <?= $balance >= 0 ? 'bg-success-lt' : 'bg-danger-lt' ?> fw-semibold" style="font-size: 10px;">
                            <?= $balance >= 0 ? 'Alacaklı' : 'Borçlu' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Toplam Hareket Sayısı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border payment-summary-card" style="border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM HAREKET</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-receipt" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" id="total_payment_count" style="font-size: 1.35rem; font-weight: 700; letter-spacing: -0.3px; line-height: 1.25;">
                        <?= number_format($total_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">Kayıtlı İşlem</span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;">Ekstre</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card payment-table-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-calculator text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Gelir Gider Listesi</h4>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Personel hesap hareketleri, puantaj hak edişleri, ödeme ve kesintiler</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Sütunlar Butonu -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary btn-icon payment-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                                <i class="ti ti-layout-columns"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" id="paymentColvisMenu" style="min-width: 200px; max-height: 350px; overflow-y: auto;">
                                <!-- Checkboxlar JS ile doldurulacak -->
                            </div>
                        </div>

                        <!-- Fast Instant Search -->
                        <div class="input-icon payment-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="payment-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="payment-search-clear" class="payment-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <!-- Summary Toggle Button -->
                        <button type="button" id="togglePaymentSummary" class="btn btn-sm btn-outline-secondary btn-icon payment-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>

                        <!-- Primary Quick Action: Ödeme Yap -->
                        <button type="button" class="btn btn-sm btn-dark shadow-sm payment-header-action add-payment" data-id="<?php echo $encrypted_person_id; ?>" style="background-color: #1e293b; border-color: #1e293b;">
                            <i class="ti ti-plus me-1"></i> Ödeme Yap
                        </button>

                        <!-- İşlemler Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle payment-header-action" data-bs-toggle="dropdown">
                                <i class="ti ti-settings me-1"></i> İşlemler
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item add-income" href="#" data-id="<?php echo $encrypted_person_id; ?>">
                                    <i class="ti ti-download icon me-2 text-success"></i> Gelir Ekle
                                </a>
                                <a class="dropdown-item add-wage-cut" href="#" data-id="<?php echo $encrypted_person_id; ?>">
                                    <i class="ti ti-cut icon me-2 text-danger"></i> Kesinti Ekle
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="#" class="dropdown-item" id="export_payment_excel">
                                    <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                                </a>
                                <a class="dropdown-item" href="pages/persons/statement.php?id=<?php echo $encrypted_person_id; ?>" target="_blank">
                                    <i class="ti ti-printer icon me-2 text-primary"></i> <span class="text-primary fw-semibold">Ekstre Yazdır</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table Responsive Container -->
                <div class="table-responsive payment-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="person_paymentTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 45px;" class="text-center">ID</th>
                                <th style="width: 95px;">Tarih</th>
                                <th>İşlem Türü</th>
                                <th>Adı</th>
                                <th style="width: 50px;" class="text-center">Ay</th>
                                <th style="width: 60px;" class="text-center">Yıl</th>
                                <th class="text-end">Tutar</th>
                                <th class="text-end">Bakiye</th>
                                <th>Açıklama</th>
                                <th>İşlem Tarihi</th>
                                <th class="no-export text-end" style="width: 80px; min-width: 80px;">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($income_expenses as $item):
                                $item_id = Security::encrypt($item->id);
                                $is_puantaj = ($item->kategori == 14);
                                $row_class = $is_puantaj ? 'puantaj-row cursor-pointer' : '';
                                $type = $financialHelper->getTransactionTypeById($item->kategori);
                                $is_income = ($type && $type->type_id == 1);
                                $badge_class = $is_income ? 'badge bg-success-lt' : 'badge bg-danger-lt';
                                if ($is_puantaj) {
                                    $badge_class = 'badge bg-blue-lt';
                                }
                            ?>
                                <tr class="<?php echo $row_class; ?>" 
                                    data-id="<?php echo $item->id; ?>" 
                                    data-person="<?php echo $item->person_id; ?>"
                                    data-ay="<?php echo $item->ay; ?>"
                                    data-yil="<?php echo $item->yil; ?>"
                                    data-tablename="<?php echo htmlspecialchars($item->tablename ?? ''); ?>"
                                    data-type="<?php echo $is_puantaj ? 'puantaj' : htmlspecialchars($item->tablename ?? ''); ?>">
                                    <td class="text-center text-muted"><?php echo $item->id; ?></td>
                                    <td>
                                        <?php 
                                        if ($is_puantaj) {
                                            echo '<strong>' . Date::monthName($item->ay) . " " . $item->yil . '</strong>';
                                        } else {
                                            echo Date::dmY($item->gun);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="<?php echo $badge_class; ?>">
                                            <?php
                                            $icon = $financialHelper->getTransactionIcon($item->kategori) ?? '';
                                            echo $icon;
                                            echo $type ? htmlspecialchars($type->name) : 'İşlem';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($is_puantaj) {
                                            echo "<strong>Puantaj Hak Edişi</strong>";
                                            if (!empty($item->saat)) {
                                                echo " <span class='badge bg-azure-lt ms-1'>" . number_format($item->saat, 1, ',', '.') . " Saat</span>";
                                            }
                                        } else {
                                            echo htmlspecialchars($item->turu ?? '');
                                        }
                                        ?>
                                    </td>
                                    <td class="text-center"><?php echo $item->ay; ?></td>
                                    <td class="text-center"><?php echo $item->yil; ?></td>
                                    <td class="text-end fw-bold <?php echo $is_income ? 'text-success' : 'text-danger'; ?>">
                                         <?php 
                                         $sign = $is_income ? '+' : '-';
                                         echo $sign . Helper::formattedMoney($item->tutar); 
                                         ?>
                                    </td>
                                    <td class="text-end fw-bold <?php echo $item->running_balance >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo Helper::formattedMoney($item->running_balance); ?>
                                    </td>
                                    <td><?php echo $is_puantaj ? '' : htmlspecialchars(Helper::short($item->aciklama, 40)); ?></td>
                                    <td class="text-secondary small"><?php echo $is_puantaj ? '' : $item->created_at; ?></td>
                                    <td class="text-end actions-column">
                                        <?php if ($item->kategori != 14): ?>
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-secondary edit-payment"
                                                   title="Düzenle"
                                                   data-id="<?php echo $item_id; ?>"
                                                   data-person="<?php echo Security::encrypt($item->person_id); ?>"
                                                   data-kategori="<?php echo $item->kategori; ?>"
                                                   data-turu="<?php echo htmlspecialchars($item->turu); ?>"
                                                   data-tutar="<?php echo $item->tutar; ?>"
                                                   data-ay="<?php echo $item->ay; ?>"
                                                   data-yil="<?php echo $item->yil; ?>"
                                                   data-aciklama="<?php echo htmlspecialchars($item->aciklama); ?>"
                                                   data-case="<?php echo isset($item->case_id) ? Security::encrypt($item->case_id) : ''; ?>"
                                                   data-tablename="<?php echo $item->tablename; ?>">
                                                    <i class="ti ti-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-payment-btn"
                                                   title="Sil"
                                                   data-id="<?php echo $item_id; ?>"
                                                   data-typename="<?php echo htmlspecialchars($item->turu ?? 'İşlem'); ?>"
                                                   data-tablename="<?php echo $item->tablename; ?>"
                                                   data-person="<?php echo $encrypted_person_id; ?>">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            </div>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-sm btn-icon btn-outline-primary puantaj-detail-btn" title="Puantaj Detayını Gör">
                                                <i class="ti ti-eye"></i>
                                            </button>
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

<?php include_once ROOT . '/pages/payroll/content/wage_cut-modal.php' ?>
<?php include_once ROOT . '/pages/payroll/content/income-modal.php' ?>
<?php include_once ROOT . '/pages/payroll/content/payment-modal.php' ?>

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

<script>
$(document).ready(function() {
    // Summary Card Toggle Logic
    var $summaryToggle = $('#togglePaymentSummary');

    function syncPaymentSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('payment-summary-collapsed');
        $summaryToggle
            .attr('aria-expanded', String(!isCollapsed))
            .attr('aria-label', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle')
            .attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
        $summaryToggle.find('i')
            .toggleClass('ti-chevron-up', !isCollapsed)
            .toggleClass('ti-chevron-down', isCollapsed);
    }

    syncPaymentSummaryToggle();

    $summaryToggle.on('click', function() {
        var isCollapsed = document.documentElement.classList.toggle('payment-summary-collapsed');
        try {
            localStorage.setItem('payment_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncPaymentSummaryToggle();
    });

    // Destroy existing datatable instance on this table if already initialized
    if ($.fn.DataTable.isDataTable('#person_paymentTable')) {
        $('#person_paymentTable').DataTable().destroy();
    }

    // Initialize Payment DataTable
    var paymentTable = $('#person_paymentTable').DataTable({
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [], // Sunucudan gelen yürüyen bakiye sıralamasını koru
        dom: 'Brtip',
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'd-none',
                title: 'Personel_Gelir_Gider_Listesi',
                exportOptions: {
                    columns: ':visible:not(.no-export)'
                }
            }
        ],
        columnDefs: [
            { targets: [10], orderable: false, searchable: false, className: 'no-export text-end actions-column' },
            { targets: [0, 4, 5], className: 'text-center' },
            { targets: [6, 7], className: 'text-end' }
        ],
        layout: {
            bottomStart: ["info", "pageLength"],
            bottomEnd: "paging",
            topStart: null,
            topEnd: null
        },
        language: {
            url: 'src/tr.json',
            paginate: {
                first: '<i class="ti ti-chevrons-left"></i>',
                previous: '<i class="ti ti-chevron-left"></i>',
                next: '<i class="ti ti-chevron-right"></i>',
                last: '<i class="ti ti-chevrons-right"></i>'
            }
        },
        initComplete: function() {
            var api = this.api();
            if (typeof window.initDataTableColumnFilters === 'function') {
                window.initDataTableColumnFilters($('#person_paymentTable'), api);
            }
        }
    });

    // Excel Export Trigger
    $('#export_payment_excel').on('click', function(e) {
        e.preventDefault();
        paymentTable.button('.buttons-excel').trigger();
    });

    // Sütun Görünürlük Ayarları
    var paymentColumnConfig = {
        0: { label: 'ID', default: true },
        1: { label: 'Tarih', default: true },
        2: { label: 'İşlem Türü', default: true },
        3: { label: 'Adı', default: true },
        4: { label: 'Ay', default: true },
        5: { label: 'Yıl', default: true },
        6: { label: 'Tutar', default: true },
        7: { label: 'Bakiye', default: true },
        8: { label: 'Açıklama', default: true },
        9: { label: 'İşlem Tarihi', default: true }
    };

    var savedPaymentVisibility = localStorage.getItem('payment_column_visibility');
    var paymentVisibilityState = savedPaymentVisibility ? JSON.parse(savedPaymentVisibility) : {};

    var paymentMenuHtml = '';
    $.each(paymentColumnConfig, function(idx, conf) {
        var isVisible = paymentVisibilityState.hasOwnProperty(idx) ? paymentVisibilityState[idx] : conf.default;
        paymentTable.column(idx).visible(isVisible, false);

        paymentMenuHtml += `
            <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size: 0.85rem;">
                <div class="form-check mb-0 w-100">
                    <input class="form-check-input payment-col-trigger" type="checkbox" id="colPayCheck_${idx}" data-column="${idx}" ${isVisible ? "checked" : ""}>
                    <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">
                        ${conf.label}
                    </span>
                </div>
            </label>`;
    });

    $('#paymentColvisMenu').html(paymentMenuHtml);
    paymentTable.columns.adjust();

    $(document).on('change', '.payment-col-trigger', function() {
        var colIdx = parseInt($(this).data('column'));
        var isChecked = this.checked;
        paymentTable.column(colIdx).visible(isChecked);
        paymentVisibilityState[colIdx] = isChecked;
        localStorage.setItem('payment_column_visibility', JSON.stringify(paymentVisibilityState));
    });

    $(document).on('click', '#paymentColvisMenu', function(e) {
        e.stopPropagation();
    });

    // Fast Instant Search
    var paymentSearchTimer = null;
    $('#payment-fast-search').on('input', function() {
        var val = this.value;
        $('#payment-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(paymentSearchTimer);
        paymentSearchTimer = setTimeout(function() {
            paymentTable.search(val).draw();
        }, 300);
    });

    $('#payment-search-clear').on('click', function() {
        clearTimeout(paymentSearchTimer);
        $('#payment-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        paymentTable.search('').draw();
    });

    // Puantaj Row Click Handler
    $(document).on('click', '.puantaj-row, .puantaj-detail-btn', function(e) {
        if ($(e.target).closest('.actions-column').length && !$(e.target).closest('.puantaj-detail-btn').length) {
            return;
        }
        var $tr = $(this).closest('tr');
        var personId = $tr.data('person');
        var ay = $tr.data('ay');
        var yil = $tr.data('yil');
        
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

    // Delete Payment Action Handler
    $(document).on('click', '.delete-payment-btn', async function(e) {
        e.preventDefault();
        var $btn = $(this);
        var typeName = $btn.data('typename') || 'İşlem';
        var tableType = $btn.data('tablename') || 'maas_gelir_kesinti';
        var personId = $btn.data('person') || '<?php echo $encrypted_person_id; ?>';
        var action = "deletePayment";
        var confirmMessage = typeName + " silinecektir!";
        var url = "api/persons/person.php?person_id=" + personId + "&type=" + tableType;

        var result = await deleteRecordByReturn(this, action, confirmMessage, url);
        if (result && result.status === 'success') {
            if (result.income_expense) {
                var ie = result.income_expense;
                $('#total_income').text(ie.total_income);
                $('#total_expense').text(ie.total_expense);
                $('#balance').text(ie.balance);
            }
            setTimeout(function() {
                location.reload();
            }, 500);
        }
    });

    // Edit Payment Handler
    $(document).on('click', '.edit-payment', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const personId = $(this).data('person');
        const kategori = parseInt($(this).data('kategori'));
        const turu = $(this).data('turu');
        const tutar = $(this).data('tutar');
        const ay = $(this).data('ay');
        const parsedAy = ay ? parseInt(ay, 10) : '';
        const yil = $(this).data('yil');
        const aciklama = $(this).data('aciklama');
        const caseId = $(this).data('case');
        const tablename = $(this).data('tablename') || 'maas_gelir_kesinti';
        
        const personName = $('.full-name').text().trim();
        
        if (kategori === 7) { // Ödeme Yap
            const modal = $('#payment-modal');
            const form = $('#payment_modalForm');
            modal.find('#person_name_payment').text(personName);
            form.find('[name="id"]').val(id);
            form.find('[name="person_id_payment"]').val(personId);
            if (form.find('[name="tablename"]').length === 0) {
                form.append('<input type="hidden" name="tablename" value="">');
            }
            form.find('[name="tablename"]').val(tablename);
            form.find('[name="payment_type"]').val(turu);
            form.find('[name="payment_amount"]').val(tutar);
            form.find('[name="payment_month"]').val(parsedAy).trigger('change');
            form.find('[name="payment_year"]').val(yil).trigger('change');
            form.find('[name="payment_cases"]').val(caseId).trigger('change');
            form.find('[name="payment_description"]').val(aciklama);
            modal.find('#payment_addButton').text('Ödeme Güncelle');
            modal.modal('show');
        } else if (kategori === 1 || kategori === 16) { // Gelir Ekle
            const modal = $('#income_modal');
            const form = $('#income_modalForm');
            modal.find('#person_name_income').text(personName);
            form.find('[name="id"]').val(id);
            form.find('[name="person_id_income"]').val(personId);
            if (form.find('[name="tablename"]').length === 0) {
                form.append('<input type="hidden" name="tablename" value="">');
            }
            form.find('[name="tablename"]').val(tablename);
            form.find('[name="income_type"]').val(turu);
            form.find('[name="income_amount"]').val(tutar);
            form.find('[name="income_month"]').val(parsedAy).trigger('change');
            form.find('[name="income_year"]').val(yil).trigger('change');
            form.find('[name="income_description"]').val(aciklama);
            modal.find('#income_addButton').text('Gelir Güncelle');
            modal.modal('show');
        } else if (kategori === 15) { // Kesinti Ekle
            const modal = $('#wage_cut_modal');
            const form = $('#wage_cut_modalForm');
            modal.find('#person_name_wage_cut').text(personName);
            form.find('[name="wage_cut_id"]').val(id);
            form.find('[name="person_id_wage_cut"]').val(personId);
            if (form.find('[name="tablename"]').length === 0) {
                form.append('<input type="hidden" name="tablename" value="">');
            }
            form.find('[name="tablename"]').val(tablename);
            form.find('[name="wage_cut_type"]').val(turu);
            form.find('[name="wage_cut_amount"]').val(tutar);
            form.find('[name="wage_cut_month"]').val(parsedAy).trigger('change');
            form.find('[name="wage_cut_year"]').val(yil).trigger('change');
            form.find('[name="wage_cut_description"]').val(aciklama);
            modal.find('#wage_cut_addButton').text('Kesinti Güncelle');
            modal.modal('show');
        }
    });

    // Reset modal forms for Add operations
    $(document).on('click', '.add-payment', function() {
        const form = $('#payment_modalForm');
        form.trigger('reset');
        form.find('[name="id"]').val(0);
        if (form.find('[name="tablename"]').length === 0) {
            form.append('<input type="hidden" name="tablename" value="maas_gelir_kesinti">');
        } else {
            form.find('[name="tablename"]').val('maas_gelir_kesinti');
        }
        $('#payment_addButton').text('Ödeme Yap');
    });
    $(document).on('click', '.add-income', function() {
        const form = $('#income_modalForm');
        form.trigger('reset');
        form.find('[name="id"]').val(0);
        if (form.find('[name="tablename"]').length === 0) {
            form.append('<input type="hidden" name="tablename" value="maas_gelir_kesinti">');
        } else {
            form.find('[name="tablename"]').val('maas_gelir_kesinti');
        }
        $('#income_addButton').text('Gelir Ekle');
    });
    $(document).on('click', '.add-wage-cut', function() {
        const form = $('#wage_cut_modalForm');
        form.trigger('reset');
        form.find('[name="wage_cut_id"]').val(0);
        if (form.find('[name="tablename"]').length === 0) {
            form.append('<input type="hidden" name="tablename" value="maas_gelir_kesinti">');
        } else {
            form.find('[name="tablename"]').val('maas_gelir_kesinti');
        }
        $('#wage_cut_addButton').text('Kesinti Ekle');
    });
});
</script>