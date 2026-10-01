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

$colors = ['primary', 'azure', 'indigo', 'purple', 'pink', 'red', 'orange', 'yellow', 'lime', 'green', 'teal', 'cyan'];
?>
<style>
    /* Modal Genel ve Özel Scroll Tablo Stilleri */
    #pay_to_persons-modal .modal-dialog {
        max-width: 980px;
    }
    
    #pay_to_persons-modal .table-responsive-custom {
        max-height: 420px;
        overflow-y: auto;
        border-radius: 0 0 8px 8px;
    }
    
    #pay_to_persons-modal .table-sticky-header thead th {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f8fafc !important;
        box-shadow: inset 0 -1px 0 #e2e8f0;
        padding-top: 10px;
        padding-bottom: 10px;
    }
    
    #pay_to_persons-modal .bulk-pay-row td {
        padding-top: 10px !important;
        padding-bottom: 10px !important;
        vertical-align: middle;
    }

    #pay_to_persons-modal .person-avatar {
        width: 38px;
        height: 38px;
        font-size: 13px;
        font-weight: 700;
        flex-shrink: 0;
    }

    #pay_to_persons-modal .person-info {
        display: flex;
        flex-direction: column;
        justify-content: center;
        gap: 2px;
    }

    #pay_to_persons-modal .person-name {
        font-size: 13.5px;
        font-weight: 600;
        color: #1e293b;
        line-height: 1.3;
    }

    #pay_to_persons-modal .person-subtext {
        font-size: 11.5px;
        color: #64748b;
        line-height: 1.2;
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
                            <span class="badge bg-blue-lt text-primary fw-semibold px-2 py-0.5 rounded-pill" id="payPeriodBadge" style="font-size: 11px;">
                                <i class="ti ti-wallet me-1"></i><span id="payPeriodBadgeText">Genel Bakiye</span>
                            </span>
                        </div>
                        <div class="text-secondary small mt-0.5" style="font-size: 12.5px;">Personellerin güncel toplam bakiyelerine göre toplu kasa çıkışlı ödeme gerçekleştirin</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 bg-white">
                <form action="" id="payToPersonsForm" onsubmit="return false;">
                    <input type="hidden" name="is_overall" value="1">
                    
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
                                            value="Personel Bakiye Ödemesi" 
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
                            <!-- Sol Taraf: Canlı Arama Inputu -->
                            <div class="position-relative" style="width: 240px;">
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-search text-muted"></i>
                                    </span>
                                    <input type="text" id="payToPersonsSearch" class="form-control form-control-sm" placeholder="Personel ara..." style="height: 32px; padding-right: 26px; font-size: 12.5px;">
                                </div>
                                <i class="ti ti-x search-clear-btn" id="clearPaySearch" title="Aramayı Temizle"></i>
                            </div>

                            <!-- Sağ Taraf: Filtre & Aksiyon Butonları (Sağa Yaslı) -->
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
                            </div>
                        </div>

                        <!-- Tablo Gövdesi -->
                        <div class="card-body p-0">
                            <div class="table-responsive-custom">
                                <table class="table table-vcenter card-table table-hover table-striped mb-0 table-sticky-header" id="payToPersonsTableCustom">
                                    <thead>
                                        <tr>
                                            <th class="fw-semibold text-secondary small ps-3.5 py-2.5" style="min-width: 240px;">PERSONEL</th>
                                            <th class="text-end fw-semibold text-secondary small py-2.5 d-none d-md-table-cell" style="width: 140px;">TOPLAM HAKEDİŞ</th>
                                            <th class="text-end fw-semibold text-secondary small py-2.5 d-none d-md-table-cell" style="width: 140px;">TOPLAM ÖDENEN</th>
                                            <th class="text-end fw-semibold text-secondary small py-2.5" style="width: 170px;">GÜNCEL BAKİYE</th>
                                            <th class="text-end fw-semibold text-secondary small pe-3 py-2.5" style="width: 180px;">ÖDENECEK TUTAR</th>
                                        </tr>
                                    </thead>
                                    <tbody id="payToPersonsTableBody">
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted small">
                                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                                Personel ve bakiye bilgileri yükleniyor...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Tablo Alt Bilgisi -->
                        <div class="card-footer bg-light py-2 px-3 d-flex justify-content-between align-items-center text-muted small" style="border-top: 1px solid #e2e8f0; font-size: 11.5px;">
                            <div>
                                <span id="visibleRowCount">0</span> / <span id="totalRowCount">0</span> kayıt gösteriliyor
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