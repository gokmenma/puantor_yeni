<?php
require_once 'App/Helper/date.php';
require_once "App/Helper/person.php";
require_once "Model/Persons.php";
require_once "App/Helper/financial.php";
require_once "Model/Cases.php";
require_once "App/Helper/helper.php";

use App\Helper\Date;
use App\Helper\Helper;

if (!isset($Persons)) {
    $Persons = new Persons();
}
if (!isset($personHelper)) {
    $personHelper = new PersonHelper();
}
if (!isset($financialHelper)) {
    if (isset($FinancialHelper)) {
        $financialHelper = $FinancialHelper;
    } else {
        $financialHelper = new Financial();
    }
}
if (!isset($Cases)) {
    $Cases = new Cases();
}
if (!isset($case_id)) {
    $case_id = $Cases->getDefaultCaseIdByFirm();
}

$curMonth = isset($month) ? (int)$month : (int)date('m');
$curYear = isset($year) ? (int)$year : (int)date('Y');
$periodTitle = Date::monthName($curMonth) . ' ' . $curYear;

// Eğer bordro sayfasından $payrollRows hazır gelmişse onu kullan, yoksa aktif personelleri çek
$modalPersonList = [];

if (isset($payrollRows) && is_array($payrollRows) && count($payrollRows) > 0) {
    foreach ($payrollRows as $pId => $row) {
        $pObj = $row['person'];
        $gelir = (float)($row['gelir'] ?? 0);
        $odeme = (float)(($row['odeme'] ?? 0) - ($row['icra'] ?? 0));
        $icra = (float)($row['icra'] ?? 0);
        $toplamKesintiVeOdeme = $odeme + $icra;
        $kalan = max(0, $gelir - $toplamKesintiVeOdeme);

        $modalPersonList[] = [
            'id' => $pObj->id,
            'full_name' => $pObj->full_name ?? '',
            'tc_no' => $pObj->tc_no ?? '',
            'job_name' => $pObj->job_name ?? $pObj->duty_name ?? '',
            'gelir' => $gelir,
            'odenen' => $toplamKesintiVeOdeme,
            'kalan' => $kalan
        ];
    }
} else {
    $rawPersons = $Persons->getPersonsByActive();
    foreach ($rawPersons as $pObj) {
        $modalPersonList[] = [
            'id' => $pObj->id,
            'full_name' => $pObj->full_name ?? '',
            'tc_no' => $pObj->tc_no ?? '',
            'job_name' => $pObj->job_name ?? $pObj->duty_name ?? '',
            'gelir' => 0,
            'odenen' => 0,
            'kalan' => 0
        ];
    }
}

// Harflerden oluşan avatar için baş harfleri çekme fonksiyonu
if (!function_exists('getInitials')) {
    function getInitials($name) {
        $words = preg_split('/\s+/', trim($name));
        $initials = "";
        foreach ($words as $w) {
            if (!empty($w)) {
                $initials .= mb_substr($w, 0, 1, 'UTF-8');
            }
        }
        return mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'), 'UTF-8');
    }
}

$colors = ['primary', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];
?>
<style>
    /* Modal Genel ve Özel Scroll Tablo Stilleri */
    #pay_to_persons-modal .modal-dialog {
        max-width: 960px;
    }
    
    #pay_to_persons-modal .table-responsive-custom {
        max-height: 380px;
        overflow-y: auto;
        border-radius: 0 0 8px 8px;
    }
    
    #pay_to_persons-modal .table-sticky-header thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc !important;
        box-shadow: inset 0 -1px 0 #e2e8f0;
    }
    
    #pay_to_persons-modal .table-responsive-custom::-webkit-scrollbar {
        width: 6px;
    }
    #pay_to_persons-modal .table-responsive-custom::-webkit-scrollbar-track {
        background: #f1f5f9;
    }
    #pay_to_persons-modal .table-responsive-custom::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    #pay_to_persons-modal .table-responsive-custom::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    #pay_to_persons-modal .bulk-pay-row.row-has-amount {
        background-color: #f0fdf4 !important;
    }
    
    #pay_to_persons-modal .bulk-pay-input:focus {
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.15) !important;
    }

    #pay_to_persons-modal .btn-transfer-balance {
        transition: all 0.15s ease;
    }
    #pay_to_persons-modal .btn-transfer-balance:hover {
        background-color: #dbeafe !important;
        color: #1d4ed8 !important;
        border-color: #bfdbfe !important;
    }
    
    #pay_to_persons-modal .search-clear-btn {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        cursor: pointer;
        color: #94a3b8;
        display: none;
        z-index: 5;
    }
    #pay_to_persons-modal .search-clear-btn:hover {
        color: #ef4444;
    }
</style>

<div class="modal modal-blur fade" id="pay_to_persons-modal" tabindex="-1" role="dialog" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content shadow-lg border" style="border-radius: 12px; border-color: #dbe3ec !important; overflow: hidden;">
            
            <!-- Modal Header -->
            <div class="modal-header bg-white px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 42px; height: 42px;">
                        <i class="ti ti-cash-banknote" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <h4 class="modal-title fw-bold text-dark mb-0" style="font-size: 1.15rem; letter-spacing: -0.3px;">Toplu Personel Ödemesi Yap</h4>
                            <span class="badge bg-blue-lt text-primary fw-semibold px-2 py-0.5 rounded-pill" style="font-size: 11px;">
                                <i class="ti ti-calendar me-1"></i><?= htmlspecialchars($periodTitle, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </div>
                        <div class="text-secondary small mt-0.5" style="font-size: 12.5px;">Birden fazla personele tek seferde kasa çıkışlı maaş / avans ödemesi gerçekleştirin</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-white">
                <form action="" id="payToPersonsForm" onsubmit="return false;">
                    
                    <!-- Form Üst Parametreleri (Kasa, Tarih, Açıklama) -->
                    <div class="card border mb-3 shadow-xs" style="border-radius: 10px; border-color: #e2e8f0; background: #f8fafc;">
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label required fw-semibold text-secondary small mb-1">
                                        <i class="ti ti-wallet text-muted me-1"></i> Çıkış Yapılacak Kasa
                                    </label>
                                    <?php echo $financialHelper->getCasesSelectByUser("tps_cases", $case_id); ?>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label required fw-semibold text-secondary small mb-1">
                                        <i class="ti ti-calendar text-muted me-1"></i> Ödeme Tarihi
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar text-muted"></i>
                                        </span>
                                        <input type="text" name="tps_action_date" id="tps_action_date" class="form-control flatpickr"
                                            value="<?php echo date("d.m.Y") ?>" placeholder="Tarih seçin">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold text-secondary small mb-1">
                                        <i class="ti ti-notes text-muted me-1"></i> Ödeme Açıklaması
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-file-description text-muted"></i>
                                        </span>
                                        <input type="text" name="tps_amount_description" id="tps_amount_description" class="form-control" 
                                            value="<?= htmlspecialchars($periodTitle . ' Maaş Ödemesi', ENT_QUOTES, 'UTF-8') ?>" 
                                            placeholder="Açıklama giriniz...">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personel Listesi Tablosu -->
                    <div class="card border shadow-xs" style="border-radius: 10px; border-color: #e2e8f0;">
                        
                        <!-- Tablo Başlık & Hızlı Aksiyon Araç Çubuğu -->
                        <div class="card-header bg-light py-2 px-3 d-flex flex-wrap justify-content-between align-items-center gap-2" style="border-bottom: 1px solid #e2e8f0;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="ti ti-users text-primary" style="font-size: 17px;"></i>
                                <span class="fw-bold text-dark small text-uppercase" style="letter-spacing: 0.4px;">Personel Listesi</span>
                                <span class="badge bg-secondary-lt text-dark rounded-pill fw-semibold px-2 py-0.5" id="totalPersonBadge" style="font-size: 11px;">
                                    <?= count($modalPersonList) ?> Personel
                                </span>
                            </div>

                            <div class="d-flex align-items-center gap-2 flex-wrap ms-auto">
                                <!-- Filtre: Yalnızca Bakiyesi Olanlar -->
                                <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-1.5" id="btnToggleBalanceFilter" style="height: 32px; font-size: 12px;">
                                    <i class="ti ti-filter text-muted"></i>
                                    <span id="filterBtnText">Yalnızca Alacağı Olanlar</span>
                                </button>

                                <!-- Aksiyon: Tüm Bakiyeleri Doldur -->
                                <button type="button" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1.5" id="btnFillAllBalances" style="height: 32px; font-size: 12px;" title="Alacağı olan tüm personellerin tutar alanını otomatik doldur">
                                    <i class="ti ti-bolt text-primary"></i>
                                    <span>Tüm Bakiyeleri Doldur</span>
                                </button>

                                <!-- Aksiyon: Tutarları Temizle -->
                                <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" id="btnResetAllAmounts" style="height: 32px; font-size: 12px;" title="Girilen tüm tutarları sıfırla">
                                    <i class="ti ti-trash"></i>
                                    <span>Sıfırla</span>
                                </button>

                                <!-- Canlı Arama Inputu -->
                                <div class="position-relative" style="width: 200px;">
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-search text-muted"></i>
                                        </span>
                                        <input type="text" id="payToPersonsSearch" class="form-control form-control-sm" placeholder="Personel ara..." style="height: 32px; padding-right: 26px; font-size: 12.5px;">
                                    </div>
                                    <i class="ti ti-x search-clear-btn" id="clearPaySearch" title="Aramayı Temizle"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Tablo Gövdesi -->
                        <div class="card-body p-0">
                            <div class="table-responsive-custom">
                                <table class="table table-vcenter card-table table-hover table-striped mb-0 table-sticky-header" id="payToPersonsTableCustom">
                                    <thead>
                                        <tr>
                                            <th class="fw-semibold text-secondary small ps-3 py-2" style="min-width: 240px;">PERSONEL</th>
                                            <th class="text-end fw-semibold text-secondary small py-2 d-none d-md-table-cell" style="width: 130px;">DÖNEM GELİRİ</th>
                                            <th class="text-end fw-semibold text-secondary small py-2 d-none d-md-table-cell" style="width: 140px;">ÖDENEN / KESİNTİ</th>
                                            <th class="text-end fw-semibold text-secondary small py-2" style="width: 170px;">KALAN HAKEDİŞ</th>
                                            <th class="text-end fw-semibold text-secondary small pe-3 py-2" style="width: 180px;">ÖDENECEK TUTAR</th>
                                        </tr>
                                    </thead>
                                    <tbody id="payToPersonsTableBody">
                                        <?php if (empty($modalPersonList)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted small">
                                                    <i class="ti ti-info-circle fs-2 d-block mb-1 text-secondary"></i>
                                                    Bu dönem için listelenecek personel bulunamadı.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($modalPersonList as $idx => $person): 
                                                $color = $colors[$person['id'] % count($colors)];
                                                $initials = getInitials($person['full_name']);
                                                $kalan = $person['kalan'];
                                                $gelir = $person['gelir'];
                                                $odenen = $person['odenen'];
                                                $hasBalance = ($kalan > 0);
                                                $searchData = mb_strtolower($person['full_name'] . ' ' . $person['tc_no'] . ' ' . $person['job_name'], 'UTF-8');
                                            ?>
                                                <tr class="bulk-pay-row" 
                                                    data-person-id="<?= (int)$person['id'] ?>" 
                                                    data-balance="<?= number_format($kalan, 2, '.', '') ?>"
                                                    data-has-balance="<?= $hasBalance ? '1' : '0' ?>"
                                                    data-search="<?= htmlspecialchars($searchData, ENT_QUOTES, 'UTF-8') ?>">
                                                    
                                                    <!-- Personel Bilgisi -->
                                                    <td class="ps-3 py-2">
                                                        <div class="d-flex align-items-center">
                                                            <span class="avatar avatar-sm rounded-circle bg-<?= $color ?>-lt me-2.5 fw-bold text-uppercase shadow-xs" style="width: 32px; height: 32px; font-size: 11.5px; flex-shrink: 0;">
                                                                <?= $initials ?>
                                                            </span>
                                                            <div style="line-height: 1.2;">
                                                                <div class="fw-semibold text-dark person-name" style="font-size: 13px;">
                                                                    <?= htmlspecialchars($person['full_name'], ENT_QUOTES, 'UTF-8') ?>
                                                                </div>
                                                                <?php if (!empty($person['job_name'])): ?>
                                                                    <div class="text-muted small mt-0.5" style="font-size: 11px;">
                                                                        <?= htmlspecialchars($person['job_name'], ENT_QUOTES, 'UTF-8') ?>
                                                                    </div>
                                                                <?php elseif (!empty($person['tc_no'])): ?>
                                                                    <div class="text-muted small mt-0.5" style="font-size: 11px;">
                                                                        TC: <?= htmlspecialchars($person['tc_no'], ENT_QUOTES, 'UTF-8') ?>
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>

                                                    <!-- Dönem Geliri -->
                                                    <td class="text-end py-2 text-muted small d-none d-md-table-cell" style="font-size: 12.5px;">
                                                        <?= Helper::formattedMoney($gelir) ?>
                                                    </td>

                                                    <!-- Ödenen / Kesinti -->
                                                    <td class="text-end py-2 text-muted small d-none d-md-table-cell" style="font-size: 12.5px;">
                                                        <?= Helper::formattedMoney($odenen) ?>
                                                    </td>

                                                    <!-- Kalan Hakediş ve Hızlı Aktar Butonu -->
                                                    <td class="text-end py-2">
                                                        <div class="d-flex align-items-center justify-content-end gap-1.5">
                                                            <span class="balance-text <?= $hasBalance ? 'fw-bold text-dark' : 'text-muted' ?>" style="font-size: 12.5px;">
                                                                <?= Helper::formattedMoney($kalan) ?>
                                                            </span>
                                                            <?php if ($hasBalance): ?>
                                                                <button type="button" class="btn btn-xs btn-outline-primary btn-transfer-balance py-0.5 px-1.5 shadow-none" 
                                                                    title="Bu bakiyeyi ödeme tutarına aktar" 
                                                                    style="border-radius: 4px; font-size: 10.5px; height: 22px;">
                                                                    <i class="ti ti-arrow-right" style="font-size: 12px;"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>

                                                    <!-- Ödenecek Tutar Giriş Kutusu -->
                                                    <td class="pe-3 py-2">
                                                        <div class="input-group input-group-flat input-group-sm ms-auto" style="max-width: 155px; border-radius: 6px; overflow: hidden;">
                                                            <input type="text" class="form-control text-end money bulk-pay-input py-1 pe-2" 
                                                                placeholder="0,00" 
                                                                data-person-id="<?= (int)$person['id'] ?>"
                                                                data-raw-balance="<?= number_format($kalan, 2, '.', '') ?>"
                                                                style="font-size: 13px; font-weight: 600;">
                                                            <span class="input-group-text bg-light text-muted fw-bold py-1 px-2" style="font-size: 12px;">₺</span>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tablo Alt Bilgisi -->
                        <div class="card-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center text-muted small" style="border-top: 1px solid #e2e8f0; font-size: 11.5px;">
                            <div>
                                <span id="visibleRowCount"><?= count($modalPersonList) ?></span> / <?= count($modalPersonList) ?> kayıt gösteriliyor
                            </div>
                            <div>
                                <i class="ti ti-info-circle me-1"></i> Tutar girilen satırlar yeşil renk ile vurgulanır.
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Modal Footer: Dinamik Toplam Göstergesi ve Aksiyon Butonları -->
            <div class="modal-footer d-flex justify-content-between align-items-center bg-light px-4 py-3" style="border-top: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-3">
                    <div class="d-flex align-items-center bg-white border rounded-3 px-3 py-1.5 shadow-xs" style="border-color: #dbe3ec !important;">
                        <span class="badge bg-blue-lt text-primary fw-semibold px-2 py-1 rounded-2 me-2" style="font-size: 11.5px;">
                            <i class="ti ti-user-check me-1"></i><span id="selectedPersonCount">0</span> Personel
                        </span>
                        <div class="vr me-2" style="height: 18px; opacity: 0.15;"></div>
                        <span class="text-secondary fw-semibold small me-2" style="font-size: 11.5px; letter-spacing: 0.3px;">TOPLAM ÖDEME:</span>
                        <span id="payToPersonsTotal" class="text-primary fw-bold" style="font-size: 1.25rem; letter-spacing: -0.5px;">0,00</span>
                        <span class="text-primary fw-bold ms-1" style="font-size: 1.05rem;">₺</span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-secondary px-3 py-1.5" data-bs-dismiss="modal" style="height: 36px; font-size: 13px;">Vazgeç</button>
                    <button type="button" class="btn btn-primary shadow-sm px-4 py-1.5 d-inline-flex align-items-center gap-1.5" id="savePayToPersons" style="height: 36px; font-size: 13px; font-weight: 600;">
                        <i class="ti ti-check" style="font-size: 16px;"></i> Ödemeleri Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
