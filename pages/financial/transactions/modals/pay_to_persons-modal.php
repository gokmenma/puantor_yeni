<?php
require_once 'App/Helper/date.php';
require_once "App/Helper/person.php";
require_once "Model/Persons.php";

use App\Helper\Date;

$Persons = new Persons();
$personHelper = new PersonHelper();

$persons = $Persons->getPersonsByActive();

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
    /* DataTable içindeki otomatik oluşan arama satırını ve gereksiz genişlik kaymalarını gizle */
    #payToPersons_wrapper .search-input-row,
    .dataTables_scrollHead .search-input-row {
        display: none !important;
    }
    
    #payToPersons {
        width: 100% !important;
    }
    
    /* Input odaklandığında border ve gölge */
    #payToPersonsForm .input-group-flat:focus-within {
        border-color: #90bbf9;
        box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, .25);
    }
    
    /* Scroll alanını güzelleştir */
    .dataTables_scrollBody::-webkit-scrollbar {
        width: 6px;
    }
    .dataTables_scrollBody::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    .dataTables_scrollBody::-webkit-scrollbar-thumb {
        background: #cdcdcd;
        border-radius: 4px;
    }
    .dataTables_scrollBody::-webkit-scrollbar-thumb:hover {
        background: #a6a6a6;
    }
</style>
<div class="modal modal-blur fade" id="pay_to_persons-modal" aria-hidden="true" style="display: none;">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content shadow-lg">
            <div class="modal-header bg-light py-3">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center">
                    <i class="ti ti-users icon me-2 text-primary fs-3"></i> Toplu Personel Ödemesi Yap
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <form action="" id="payToPersonsForm">
                    <!-- Form Üst Bilgileri (Kasa, Tarih, Açıklama) -->
                    <div class="card border-0 bg-transparent mb-3">
                        <div class="card-body p-0">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-semibold text-secondary mb-1">
                                        <i class="ti ti-wallet text-muted me-1"></i> Ödeme Yapılacak Kasa
                                    </label>
                                    <?php echo $financialHelper->getCasesSelectByUser("tps_cases", $case_id); ?>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-semibold text-secondary mb-1">
                                        <i class="ti ti-calendar text-muted me-1"></i> Ödeme Tarihi
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-calendar"></i>
                                        </span>
                                        <input type="text" name="tps_action_date" id="tps_action_date" class="form-control flatpickr"
                                            value="<?php echo date("d.m.Y") ?>" placeholder="Tarih seçin">
                                    </div>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-semibold text-secondary mb-1">
                                        <i class="ti ti-note text-muted me-1"></i> Ödeme Açıklaması
                                    </label>
                                    <div class="input-icon">
                                        <span class="input-icon-addon">
                                            <i class="ti ti-file-text"></i>
                                        </span>
                                        <input type="text" name="tps_amount_description" class="form-control" placeholder="Açıklama giriniz (Opsiyonel)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Personel Listesi Tablosu -->
                    <div class="card border shadow-sm">
                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                            <h3 class="card-title fw-semibold text-secondary mb-0" style="font-size: 0.85rem; letter-spacing: 0.05em; text-transform: uppercase;">
                                <i class="ti ti-list me-1 text-primary"></i> Personel Listesi
                            </h3>
                            <div class="input-icon" style="width: 220px;">
                                <span class="input-icon-addon">
                                    <i class="ti ti-search text-muted"></i>
                                </span>
                                <input type="text" id="payToPersonsSearch" class="form-control form-control-sm" placeholder="Personel ara...">
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-vcenter card-table table-striped table-hover mb-0" id="payToPersons">
                                <thead>
                                    <tr>
                                        <th class="fw-bold bg-light py-2 text-dark">Personel</th>
                                        <th class="text-end fw-bold bg-light py-2 text-dark" style="width: 200px;">Ödeme Tutarı</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($persons as $person): 
                                        $color = $colors[$person->id % count($colors)];
                                        $initials = getInitials($person->full_name);
                                    ?>
                                        <tr>
                                            <td data-id="<?= $person->id ?>" class="py-2">
                                                <div class="d-flex align-items-center">
                                                    <span class="avatar avatar-sm rounded-circle bg-<?= $color ?>-lt me-3 fw-bold fs-5"><?= $initials ?></span>
                                                    <div>
                                                        <div class="font-weight-medium text-dark"><?= htmlspecialchars($person->full_name, ENT_QUOTES, 'UTF-8') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-2">
                                                <div class="input-group input-group-flat ms-auto" style="max-width: 160px;">
                                                    <input type="text" class="form-control text-end money pe-2 py-1" placeholder="0,00">
                                                    <span class="input-group-text bg-transparent text-muted fw-bold py-1">₺</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Footer: Dinamik Toplam Göstergesi ve Butonlar -->
            <div class="modal-footer d-flex justify-content-between align-items-center bg-light py-2">
                <div class="d-flex align-items-center bg-white border rounded px-3 py-2 shadow-sm">
                    <span class="text-muted fw-semibold me-2 small">TOPLAM ÖDEME:</span>
                    <span id="payToPersonsTotal" class="text-primary fw-bold fs-3">0,00</span>
                    <span class="text-primary fw-bold fs-4 ms-1">₺</span>
                </div>
                <div>
                    <button type="button" class="btn btn-link link-secondary me-2" data-bs-dismiss="modal">Çık</button>
                    <button type="button" class="btn btn-primary" id="savePayToPersons">
                        <i class="ti ti-check me-1"></i> Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>