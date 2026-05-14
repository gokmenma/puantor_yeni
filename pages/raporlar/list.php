<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'Model/Persons.php';
require_once 'App/Helper/security.php';

require_once 'Model/Bordro.php';
require_once 'App/Helper/security.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$year = isset($_REQUEST['year']) ? $_REQUEST['year'] : ($_COOKIE['p_year'] ?? date('Y'));
$month = isset($_REQUEST['months']) ? $_REQUEST['months'] : ($_COOKIE['p_months'] ?? date('m'));
$firm_id = $_SESSION['firm_id'];
$report_type = $_GET['report'] ?? '';

$personObj = new Persons();
$firstDayStr = Date::firstDay($month, $year);
$lastDayStr = Date::lastDay($month, $year);

// DB compatibility formats
$startDate = date('Y-m-d', strtotime($firstDayStr));
$endDate = date('Y-m-d', strtotime($lastDayStr));

// Get personnel counts for context box
$personList = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
$personCount = count($personList);

$bordroObj = new Bordro();

$displayMonth = mb_strtoupper(Date::monthName($month), 'UTF-8');
$displayTitle = "Raporlar";

// Function to render report card identically to dashboard picture styles
function renderReportCard($title, $desc, $icon, $colorClass, $viewUrl = "#", $isActive = false) {
    $btnClass = "btn-primary"; // Standardized consistent button color
    
    $cardOpacity = $isActive ? '' : 'opacity-75 border-dashed';
    $linkUrl = $isActive ? $viewUrl : 'javascript:void(0);';
    $badge = $isActive ? '' : '<span class="badge bg-secondary-lt ms-auto">Çok Yakında</span>';
    $disabledClass = $isActive ? '' : 'disabled bg-dark bg-opacity-10 text-muted border-0 shadow-none';
    $cursorStyle = $isActive ? '' : 'cursor: not-allowed; pointer-events: none;';

    echo '
    <div class="card report-card h-100 shadow-sm border-light ' . $cardOpacity . '" style="' . (!$isActive ? 'background:#fcfcfd;' : '') . '">
        <div class="card-body d-flex flex-column position-relative">
            <div class="d-flex align-items-center mb-3">
                <div class="avatar avatar-md rounded-circle ' . $colorClass . ' me-3">
                    <i class="ti ' . $icon . ' fs-2"></i>
                </div>
                <div class="d-flex flex-column">
                    <h3 class="card-title text-dark fw-bold mb-0">' . $title . '</h3>
                </div>
                ' . $badge . '
            </div>
            <p class="text-muted small mb-4 flex-grow-1">' . $desc . '</p>
            <div class="d-flex gap-2 mt-auto pt-3 border-top border-light">
                <a href="' . $linkUrl . '" class="btn ' . $btnClass . ' ' . $disabledClass . ' flex-fill fw-bold shadow-sm" style="' . $cursorStyle . '">
                    <i class="ti ti-eye me-1"></i> ' . ($isActive ? 'Görüntüle' : 'Hazırlanıyor') . '
                </a>';
                if ($isActive) {
                    echo '<a href="#" class="btn btn-outline-primary btn-icon shadow-sm" title="İndir">
                            <i class="ti ti-download"></i>
                          </a>';
                }
    echo '</div>
        </div>
    </div>';
}
?>

<style>
    /* Report Page Custom Styling mirroring design preferences */
    .report-card {
        border: 1px solid rgba(0,0,0,0.04);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        border-radius: 12px;
    }
    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 12px 24px rgba(0,0,0,0.08) !important;
    }
    .sidebar-dark-header {
        background: #232e3c;
        color: #ffffff;
        border-top-left-radius: 10px;
        border-top-right-radius: 10px;
    }
    .bg-light-period {
        background-color: #f8fafc;
        border: 1px solid #edf2f7;
    }
    .avatar.bg-primary-lt { background-color: #e0f2fe !important; color: #0284c7 !important; }
    .avatar.bg-success-lt { background-color: #dcfce7 !important; color: #15803d !important; }
    .avatar.bg-warning-lt { background-color: #fef3c7 !important; color: #b45309 !important; }
    .avatar.bg-danger-lt { background-color: #fee2e2 !important; color: #b91c1c !important; }
    .avatar.bg-dark-lt { background-color: #f1f5f9 !important; color: #1e293b !important; }
    .avatar.bg-purple-lt { background-color: #f3e8ff !important; color: #7e22ce !important; }
    .avatar.bg-info-lt { background-color: #e0f2fe !important; color: #0369a1 !important; }
    .avatar.bg-teal-lt { background-color: #ccfbf1 !important; color: #0f766e !important; }
    
    .btn-pill-custom {
        border-radius: 50px;
    }

    .datatable thead th, #puantajDataTable thead th {
        background: #f8fafc;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        color: #475569;
        letter-spacing: 0.025em;
        cursor: grab !important; /* Indicates column reorder capacity */
        user-select: none;
    }
    
    #puantajDataTable thead th:active {
        cursor: grabbing !important;
    }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-0"><?= $report_type == 'puantaj' ? "Puantaj Raporu" : "Raporlar" ?></h2>
            <p class="text-muted small mb-0">Tüm raporlama ve veri analitiği merkezine hoş geldiniz.</p>
        </div>
        <div class="text-muted d-flex align-items-center small">
            <a href="#" class="text-secondary text-decoration-none">Bordro</a>
            <i class="ti ti-chevron-right mx-2 text-secondary" style="font-size: 10px;"></i>
            <span class="text-dark font-weight-bold">Raporlar</span>
        </div>
    </div>

    <div class="row g-4">
        <?php if(empty($report_type)): ?>
        <!-- Left Column: Periodic Settings -->
        <div class="col-lg-3 col-md-4">
            <div class="card border-0 shadow-sm overflow-hidden" style="border-radius: 12px;">
                <div class="card-header sidebar-dark-header py-3 border-0">
                    <h3 class="card-title mb-0 text-white d-flex align-items-center">
                        <i class="ti ti-calendar-event me-2 fs-3"></i> Dönem Seçimi
                    </h3>
                </div>
                <div class="card-body p-3">
                    <form method="GET" action="index.php">
                        <input type="hidden" name="p" value="raporlar/list">
                        <?php if(!empty($report_type)): ?>
                            <input type="hidden" name="report" value="<?= htmlspecialchars($report_type) ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold"><i class="ti ti-calendar me-1"></i> Yıl Seçiniz</label>
                            <?= Date::getYearsSelect('year', $year) ?>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold"><i class="ti ti-clock-hour-4 me-1"></i> Dönem Seçiniz</label>
                            <?= Date::getMonthsSelect('months', $month) ?>
                        </div>

                        <button type="submit" class="btn btn-dark w-100 fw-bold mb-3 shadow-sm">
                            <i class="ti ti-refresh me-2"></i> Verileri Yenile
                        </button>
                    </form>

                    <div class="bg-light-period rounded-3 p-3">
                        <div class="d-flex align-items-center mb-3 pb-2 border-bottom border-light">
                            <div class="bg-blue-lt p-2 rounded-circle me-2 d-flex align-items-center justify-content-center">
                                <i class="ti ti-info-circle text-blue fs-4"></i>
                            </div>
                            <h4 class="mb-0 fw-bold text-uppercase tracking-wide" style="font-size: 13px;"><?= $displayMonth ?> <?= $year ?></h4>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary small">Başlangıç:</span>
                            <span class="small fw-bold"><?= date('d.m.Y', strtotime($startDate)) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary small">Bitiş:</span>
                            <span class="small fw-bold"><?= date('d.m.Y', strtotime($endDate)) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary small">Personel:</span>
                            <span class="small fw-bold text-success"><?= $personCount ?> kişi</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="text-secondary small">Durum:</span>
                            <span class="badge bg-success-lt border border-success border-opacity-10 px-2 py-1 rounded-pill">Açık</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Right Column: Main Content -->
        <div class="<?= empty($report_type) ? 'col-lg-9 col-md-8' : 'col-12' ?>">
            <?php if($report_type == 'puantaj'): ?>
                <!-- --- PUANTAJ REPORT RENDERING --- -->
                <?php
                $start_dash = $startDate;
                $end_dash = $endDate;
                $start_nodash = str_replace('-', '', $startDate);
                $end_nodash = str_replace('-', '', $endDate);

                $db = $personObj->connect();
                $queryStr = "
                SELECT 
                    p.id, 
                    p.full_name, 
                    p.job,
                    p.kimlik_no,
                    p.iban_number,
                    p.job_start_date,
                    p.job_end_date,
                    p.ekip as team_name,
                    pr.project_name,
                    SUM(CASE WHEN pt.Turu = 'Normal Çalışma' THEN 1 ELSE 0 END) as n_calisma,
                    SUM(CASE WHEN pt.Turu = 'Saatlik' THEN pua.saat ELSE 0 END) as s_calisma,
                    SUM(CASE WHEN pt.Turu = 'Fazla Çalışma' THEN pua.saat ELSE 0 END) as f_mesai,
                    SUM(CASE WHEN pt.Turu = 'Ücretli İzin' THEN 1 ELSE 0 END) as u_izin,
                    SUM(CASE WHEN pt.PuantajKod = 'Uİ' THEN 1 ELSE 0 END) as ucr_izin,
                    SUM(CASE WHEN pt.PuantajKod = 'DVZ' THEN 1 ELSE 0 END) as dvz,
                    SUM(CASE WHEN pt.PuantajKod IN ('R', 'R-', 'R+') THEN 1 ELSE 0 END) as rapor
                FROM persons p
                LEFT JOIN projects pr ON p.project_id = pr.id
                LEFT JOIN puantaj pua ON p.id = pua.person AND ((pua.gun >= ? AND pua.gun <= ?) OR (pua.gun >= ? AND pua.gun <= ?))
                LEFT JOIN puantajturu pt ON pua.puantaj_id = pt.id
                WHERE p.firm_id = ? AND p.deleted_at IS NULL
                GROUP BY p.id
                ORDER BY p.full_name ASC
                ";
                $stmt = $db->prepare($queryStr);
                $stmt->execute([$start_dash, $end_dash, $start_nodash, $end_nodash, $firm_id]);
                $raporData = $stmt->fetchAll(PDO::FETCH_OBJ);
                
                // Verileri işleme (Şifreli alanları çözme)
                foreach($raporData as $row) {
                    $row->iban_number = Security::safeDecrypt($row->iban_number ?? '');
                    $row->kimlik_no = Security::safeDecrypt($row->kimlik_no ?? '');
                }
                ?>

                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-light">
                        <div>
                            <h3 class="card-title fw-bold mb-0">Puantaj İcmal Verileri</h3>
                            <span class="badge bg-blue-lt mt-1"><?= $displayMonth ?> <?= $year ?> Dönemi</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <!-- Unified Toolbar Group -->
                            <div id="customReportActions" class="btn-group shadow-sm d-none" role="group" style="border-radius: 8px; border: 1px solid #e2e8f0;">
                                <!-- Geri Butonu (Far Left) -->
                                <a href="index.php?p=raporlar/list&year=<?= $year ?>&months=<?= $month ?>" class="btn btn-white px-3" style="border-top-left-radius: 8px; border-bottom-left-radius: 8px;" title="Geri Dön" data-bs-toggle="tooltip">
                                    <i class="ti ti-arrow-left fs-2 text-secondary"></i>
                                </a>

                                <!-- Görünüm Dropdown -->
                                <div class="dropdown" role="group">
                                    <button class="btn btn-white px-3 dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-haspopup="true" aria-expanded="false" title="Sütun Görünümü" data-bs-toggle="tooltip">
                                        <i class="ti ti-layout-columns fs-2 text-muted me-1"></i>
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" id="customColvisMenu" style="min-width: 200px; border-radius: 10px; z-index: 1060;">
                                        <!-- Populated by app.js -->
                                    </div>
                                </div>

                                <!-- Excel Dışa Aktar -->
                                <button type="button" id="customBtnExcel" class="btn btn-white px-3 text-success" title="Excel'e Aktar" data-bs-toggle="tooltip">
                                    <i class="ti ti-file-spreadsheet fs-2"></i>
                                </button>
                                
                                <!-- PDF Dışa Aktar -->
                                <button type="button" id="customBtnPdf" class="btn btn-white px-3 text-danger" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;" title="PDF Olarak İndir" data-bs-toggle="tooltip">
                                    <i class="ti ti-file-type-pdf fs-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <!-- Changed class name from 'datatable' to 'report-data-table' to prevent unwanted automatic global initialization conflicts -->
                        <table class="table table-hover table-vcenter text-nowrap mb-0" id="puantajDataTable">
                            <thead>
                                <tr>
                                    <th>Personel Adı</th>
                                    <th>TC Kimlik No</th>
                                    <th>IBAN No</th>
                                    <th>İşe Giriş</th>
                                    <th>İşten Çıkış</th>
                                    <th>Ekip</th>
                                    <th>Proje</th>
                                    <th>Ünvan / Meslek</th>
                                    <th class="text-center">Çalışma (Gün)</th>
                                    <th class="text-center">Saatlik Çal. (Saat)</th>
                                    <th class="text-center">Fazla Mesai (Saat)</th>
                                    <th class="text-center">Ücretli İzin</th>
                                    <th class="text-center">Ücretsiz İzin</th>
                                    <th class="text-center">Rapor</th>
                                    <th class="text-center">Devamsız</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($raporData as $r): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-xs rounded-circle bg-blue-lt text-blue font-weight-bold me-2">
                                                <?= mb_substr($r->full_name, 0, 1, 'UTF-8') ?>
                                            </span>
                                            <span class="fw-semibold text-dark"><?= htmlspecialchars($r->full_name) ?></span>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($r->kimlik_no ?? '') ?></td>
                                    <td><?= htmlspecialchars($r->iban_number ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r->job_start_date ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r->job_end_date ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r->team_name ?? '-') ?></td>
                                    <td><?= htmlspecialchars($r->project_name ?? '-') ?></td>
                                    <td class="small text-secondary"><?= htmlspecialchars($r->job ?? '-') ?></td>
                                    <td class="text-center"><span class="badge bg-azure-lt"><?= (float)$r->n_calisma ?></span></td>
                                    <td class="text-center fw-medium"><?= (float)$r->s_calisma ?: '-' ?></td>
                                    <td class="text-center text-danger fw-bold"><?= (float)$r->f_mesai ?: '-' ?></td>
                                    <td class="text-center text-info"><?= (float)$r->u_izin ?: '-' ?></td>
                                    <td class="text-center text-warning"><?= (float)$r->ucr_izin ?: '-' ?></td>
                                    <td class="text-center text-purple"><?= (float)$r->rapor ?: '-' ?></td>
                                    <td class="text-center text-red"><?= (float)$r->dvz ?: '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php elseif($report_type == 'banka'): ?>
                <!-- --- BANK LIST REPORT RENDERING --- -->
                <?php
                // Get all personnel for this month
                $personsForBank = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
                $bankData = [];
                foreach($personsForBank as $p_item) {
                    $p = $personObj->find($p_item->id);
                    // Use the same logic as payroll/list.php
                    $res = $bordroObj->getPersonSalaryAndWageCut($p->id, $firstDayStr, $lastDayStr);
                    $netPay = ($res->gelir ?? 0) - ($res->odeme ?? 0);
                    
                    if($netPay > 0) {
                        $bankData[] = (object)[
                            'id' => $p->id,
                            'full_name' => $p->full_name,
                            'kimlik_no' => Security::safeDecrypt($p->kimlik_no ?? ''),
                            'iban_number' => Security::safeDecrypt($p->iban_number ?? ''),
                            'amount' => $netPay
                        ];
                    }
                }
                ?>
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-light">
                        <div>
                            <h3 class="card-title fw-bold mb-0">Banka Ödeme Listesi</h3>
                            <span class="badge bg-info-lt mt-1"><?= $displayMonth ?> <?= $year ?> Dönemi</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="btn-group shadow-sm" role="group" style="border-radius: 8px; border: 1px solid #e2e8f0;">
                                <a href="index.php?p=raporlar/list&year=<?= $year ?>&months=<?= $month ?>" class="btn btn-white px-3" style="border-top-left-radius: 8px; border-bottom-left-radius: 8px;" title="Geri Dön">
                                    <i class="ti ti-arrow-left fs-2 text-secondary"></i>
                                </a>
                                <button type="button" class="btn btn-white px-3 text-success" title="Excel'e Aktar" onclick="exportBankList('excel')">
                                    <i class="ti ti-file-spreadsheet fs-2"></i>
                                </button>
                                <button type="button" class="btn btn-white px-3 text-danger" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;" title="PDF Olarak İndir" onclick="exportBankList('pdf')">
                                    <i class="ti ti-file-type-pdf fs-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter text-nowrap mb-0" id="bankDataTable">
                            <thead>
                                <tr>
                                    <th>Personel Adı</th>
                                    <th>TC Kimlik No</th>
                                    <th>IBAN No</th>
                                    <th class="text-end">Ödenecek Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($bankData)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">Bu dönem için ödeme verisi bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($bankData as $b): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-xs rounded-circle bg-info-lt text-info font-weight-bold me-2">
                                                    <?= mb_substr($b->full_name, 0, 1, 'UTF-8') ?>
                                                </span>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($b->full_name) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars($b->kimlik_no ?? '-') ?></td>
                                        <td><?= htmlspecialchars($b->iban_number ?? '-') ?></td>
                                        <td class="text-end fw-bold text-success"><?= Helper::formattedMoney($b->amount) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    function exportBankList(type) {
                        if(type === 'excel') {
                            window.location.href = 'pages/raporlar/bank-list-excel.php?month=<?= $month ?>&year=<?= $year ?>';
                        } else {
                            window.print();
                        }
                    }
                </script>

            <?php elseif($report_type == 'bordro'): ?>
                <!-- --- BORDRO SELECTION RENDERING --- -->
                <?php
                $personsForBordro = $personObj->getPersonIdByFirmCurrentMonth($firm_id, $firstDayStr, $lastDayStr);
                ?>
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-light">
                        <div>
                            <h3 class="card-title fw-bold mb-0">Bordro Yazdırma Listesi</h3>
                            <span class="badge bg-teal-lt mt-1">Lütfen yazdırılacak personelleri seçiniz</span>
                        </div>
                        <div class="d-flex gap-2">
                             <a href="index.php?p=raporlar/list&year=<?= $year ?>&months=<?= $month ?>" class="btn btn-white shadow-sm">
                                <i class="ti ti-arrow-left me-1"></i> Geri
                            </a>
                            <button type="button" class="btn btn-primary shadow-sm" id="btnPrintSelectedBordro">
                                <i class="ti ti-printer me-1"></i> Seçilileri Yazdır
                            </button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter text-nowrap mb-0" id="bordroSelectionTable">
                            <thead>
                                <tr>
                                    <th style="width: 1%"><input type="checkbox" class="form-check-input" id="selectAllBordro"></th>
                                    <th>Personel Adı</th>
                                    <th>TC Kimlik No</th>
                                    <th>Ünvan</th>
                                    <th class="text-center">İşlem</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($personsForBordro)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">Bu dönem için personel bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($personsForBordro as $p_item): 
                                        $p = $personObj->find($p_item->id);
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" class="form-check-input row-check" value="<?= Security::encrypt($p->id) ?>"></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-xs rounded-circle bg-teal-lt text-teal font-weight-bold me-2">
                                                    <?= mb_substr($p->full_name, 0, 1, 'UTF-8') ?>
                                                </span>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($p->full_name) ?></span>
                                            </div>
                                        </td>
                                        <td><?= htmlspecialchars(Security::safeDecrypt($p->kimlik_no ?? '')) ?></td>
                                        <td class="small text-secondary"><?= htmlspecialchars($p->job ?? '-') ?></td>
                                        <td class="text-center">
                                            <a href="index.php?p=payroll/pay-slip&id=<?= Security::encrypt($p->id) ?>&month=<?= Security::encrypt($month) ?>&year=<?= Security::encrypt($year) ?>" target="_blank" class="btn btn-sm btn-icon btn-ghost-primary" title="Görüntüle">
                                                <i class="ti ti-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <script>
                    document.getElementById('selectAllBordro').addEventListener('change', function() {
                        document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
                    });
                    
                    document.getElementById('btnPrintSelectedBordro').addEventListener('click', function() {
                        const selected = Array.from(document.querySelectorAll('.row-check:checked')).map(cb => cb.value);
                        if(selected.length === 0) {
                            alert('Lütfen en az bir personel seçiniz.');
                            return;
                        }
                        
                        const ids = selected.join(',');
                        const url = `index.php?p=raporlar/bordro-yazdir&ids=${ids}&month=<?= Security::encrypt($month) ?>&year=<?= Security::encrypt($year) ?>`;
                        window.open(url, '_blank');
                    });
                </script>
            <?php elseif($report_type == 'kesinti'): ?>
                <!-- --- KESİNTİ REPORT RENDERING --- -->
                <?php
                require_once 'Model/DefinesModel.php';
                $definesObj = new DefinesModel();
                $db = $personObj->connect();
                $kesinti_ids = $definesObj->getExpenseTypes(2); // Get deduction IDs
                
                $queryStr = "
                SELECT 
                    p.full_name,
                    mgk.turu,
                    mgk.tutar,
                    mgk.gun,
                    mgk.aciklama,
                    dt.name as kategori_adi
                FROM maas_gelir_kesinti mgk
                JOIN persons p ON mgk.person_id = p.id
                LEFT JOIN defines dt ON mgk.kategori = dt.id
                WHERE p.firm_id = ? 
                  AND mgk.kategori IN ($kesinti_ids)
                  AND CAST(REPLACE(mgk.gun, '-', '') AS UNSIGNED) >= ? 
                  AND CAST(REPLACE(mgk.gun, '-', '') AS UNSIGNED) <= ?
                ORDER BY mgk.gun DESC
                ";
                $stmt = $db->prepare($queryStr);
                $stmt->execute([$firm_id, $firstDayStr, $lastDayStr]);
                $kesintiData = $stmt->fetchAll(PDO::FETCH_OBJ);
                ?>
                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-light">
                        <div>
                            <h3 class="card-title fw-bold mb-0">Kesinti Detay Raporu</h3>
                            <span class="badge bg-danger-lt mt-1"><?= $displayMonth ?> <?= $year ?> Dönemi</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="btn-group shadow-sm" role="group" style="border-radius: 8px; border: 1px solid #e2e8f0;">
                                <a href="index.php?p=raporlar/list&year=<?= $year ?>&months=<?= $month ?>" class="btn btn-white px-3" style="border-top-left-radius: 8px; border-bottom-left-radius: 8px;" title="Geri Dön">
                                    <i class="ti ti-arrow-left fs-2 text-secondary"></i>
                                </a>
                                <button type="button" class="btn btn-white px-3 text-success" title="Excel'e Aktar" onclick="exportKesintiList('excel')">
                                    <i class="ti ti-file-spreadsheet fs-2"></i>
                                </button>
                                <button type="button" class="btn btn-white px-3 text-danger" style="border-top-right-radius: 8px; border-bottom-right-radius: 8px;" title="PDF Olarak İndir" onclick="exportKesintiList('pdf')">
                                    <i class="ti ti-file-type-pdf fs-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-vcenter text-nowrap mb-0" id="kesintiDataTable">
                            <thead>
                                <tr>
                                    <th>Personel Adı</th>
                                    <th>Tarih</th>
                                    <th>Kategori</th>
                                    <th>Kesinti Türü</th>
                                    <th>Açıklama</th>
                                    <th class="text-end">Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($kesintiData)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">Bu dönem için kesinti kaydı bulunamadı.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($kesintiData as $k): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="avatar avatar-xs rounded-circle bg-danger-lt text-danger font-weight-bold me-2">
                                                    <?= mb_substr($k->full_name, 0, 1, 'UTF-8') ?>
                                                </span>
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($k->full_name) ?></span>
                                            </div>
                                        </td>
                                        <td><?= date('d.m.Y', strtotime($k->gun)) ?></td>
                                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($k->kategori_adi ?? 'Diğer') ?></span></td>
                                        <td class="small text-secondary"><?= htmlspecialchars($k->turu) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($k->aciklama ?? '-') ?></td>
                                        <td class="text-end fw-bold text-danger">-<?= Helper::formattedMoney($k->tutar) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    function exportKesintiList(type) {
                        if(type === 'excel') {
                             window.location.href = 'pages/raporlar/kesinti-list-excel.php?month=<?= $month ?>&year=<?= $year ?>';
                        } else {
                            window.print();
                        }
                    }
                </script>
            <?php else: ?>
                <!-- --- DASHBOARD CARDS RENDERING --- -->
                <div class="row row-cards g-3">
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("İcmal Raporu", "Dönem bazlı personel maaş özet raporu. Brüt maaş, kesintiler ve net bilgileri.", "ti-chart-bar", "bg-dark-lt text-dark") ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("Bordro", "Personel bazlı detaylı bordro çıktısı. Yazdırılabilir ve PDF formatında.", "ti-file-invoice", "bg-teal-lt text-teal", "index.php?p=raporlar/list&report=bordro&year=$year&months=$month", true) ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("Banka Listesi", "Bankaya gönderilecek ödeme listesi. IBAN ve hesap tutarlarını içerir.", "ti-building-bank", "bg-info-lt text-info", "index.php?p=raporlar/list&report=banka&year=$year&months=$month", true) ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <!-- Activated Module -->
                        <?php renderReportCard("Puantaj Raporu", "Toplam çalışma, fazla mesai, izin ve devamsızlık verilerini derleyen toplu rapor.", "ti-clock-check", "bg-purple-lt text-purple", "index.php?p=raporlar/list&report=puantaj&year=$year&months=$month", true) ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("SGK Bildirge", "SGK prim bildirge raporu. Personel prim tutarları ve işveren payları.", "ti-shield-check", "bg-warning-lt text-warning") ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("Vergi Raporu", "Gelir vergisi ve damga vergisi detaylı raporu. Vergi matrahları.", "ti-receipt-2", "bg-danger-lt text-danger") ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("Maliyet Raporu", "İşveren maliyet analizi raporu. Toplam personel maliyeti dağılımı.", "ti-chart-pie", "bg-dark-lt text-secondary") ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("Kesinti Raporu", "Personel kesintilerinin (İcra, Avans vb.) detaylı filtrelemeli listesi.", "ti-scissors", "bg-dark-lt text-muted", "index.php?p=raporlar/list&report=kesinti&year=$year&months=$month", true) ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("Sodexo Raporu", "Personel yemek (Sodexo) ödemelerinin listesi ve dışa aktarımı.", "ti-tools-kitchen-2", "bg-success-lt text-success") ?>
                    </div>
                </div>

                <!-- Quick Download Actions -->
                <div class="card border-0 shadow-sm mt-4" style="border-radius: 12px;">
                    <div class="card-header bg-light py-2 border-light">
                        <h4 class="card-title text-muted mb-0 small fw-bold"><i class="ti ti-download me-1"></i> Hızlı İndirme</h4>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-md-3 col-6">
                                <a href="#" class="btn btn-outline-teal w-100 text-nowrap disabled opacity-50"><i class="ti ti-file-spreadsheet me-1"></i> İcmal (Excel)</a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="pages/raporlar/bank-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-info w-100 text-nowrap"><i class="ti ti-building-bank me-1"></i> Banka (Excel)</a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="pages/raporlar/kesinti-list-excel.php?month=<?= $month ?>&year=<?= $year ?>" class="btn btn-outline-danger w-100 text-nowrap"><i class="ti ti-scissors me-1"></i> Kesinti (Excel)</a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="index.php?p=raporlar/list&report=bordro&year=<?= $year ?>&months=<?= $month ?>" class="btn btn-outline-dark w-100 text-nowrap"><i class="ti ti-printer me-1"></i> Bordroları Yazdır</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
