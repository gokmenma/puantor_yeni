<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'Model/Persons.php';
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

    .datatable thead th {
        background: #f8fafc;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        color: #475569;
        letter-spacing: 0.025em;
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

        <!-- Right Column: Main Content -->
        <div class="col-lg-9 col-md-8">
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
                    pr.project_name,
                    t.team_name,
                    SUM(CASE WHEN pt.Turu = 'Normal Çalışma' THEN 1 ELSE 0 END) as n_calisma,
                    SUM(CASE WHEN pt.Turu = 'Saatlik' THEN pua.saat ELSE 0 END) as s_calisma,
                    SUM(CASE WHEN pt.Turu = 'Fazla Çalışma' THEN pua.saat ELSE 0 END) as f_mesai,
                    SUM(CASE WHEN pt.Turu = 'Ücretli İzin' THEN 1 ELSE 0 END) as u_izin,
                    SUM(CASE WHEN pt.PuantajKod = 'Uİ' THEN 1 ELSE 0 END) as ucr_izin,
                    SUM(CASE WHEN pt.PuantajKod = 'DVZ' THEN 1 ELSE 0 END) as dvz,
                    SUM(CASE WHEN pt.PuantajKod IN ('R', 'R-', 'R+') THEN 1 ELSE 0 END) as rapor
                FROM persons p
                LEFT JOIN projects pr ON p.project_id = pr.id
                LEFT JOIN teams t ON p.team_id = t.id
                LEFT JOIN puantaj pua ON p.id = pua.person AND ((pua.gun >= ? AND pua.gun <= ?) OR (pua.gun >= ? AND pua.gun <= ?))
                LEFT JOIN puantajturu pt ON pua.puantaj_id = pt.id
                WHERE p.firm_id = ? AND p.deleted_at IS NULL
                GROUP BY p.id
                ORDER BY p.full_name ASC
                ";
                $stmt = $db->prepare($queryStr);
                $stmt->execute([$start_dash, $end_dash, $start_nodash, $end_nodash, $firm_id]);
                $raporData = $stmt->fetchAll(PDO::FETCH_OBJ);
                ?>

                <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-light">
                        <div>
                            <h3 class="card-title fw-bold mb-0">Puantaj İcmal Verileri</h3>
                            <span class="badge bg-blue-lt mt-1"><?= $displayMonth ?> <?= $year ?> Dönemi</span>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <!-- Premium UX Action Bar -->
                            <div id="customReportActions" class="d-none gap-2 d-md-flex">
                                <!-- Sütunlar Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-ghost-secondary btn-sm border-0 shadow-none dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-layout-columns fs-2 me-1 text-muted"></i> Görünüm
                                    </button>
                                    <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-2" id="customColvisMenu" style="min-width: 180px; border-radius: 10px;">
                                        <!-- Populated by JS -->
                                    </div>
                                </div>

                                <!-- Excel & PDF Triggers -->
                                <button type="button" id="customBtnExcel" class="btn btn-sm btn-emerald-lt border border-emerald-subtle fw-bold d-flex align-items-center" style="border-radius: 8px; transition: all 0.2s ease;">
                                    <i class="ti ti-file-spreadsheet fs-2 me-1"></i> Excel
                                </button>
                                
                                <button type="button" id="customBtnPdf" class="btn btn-sm btn-red-lt border border-red-subtle fw-bold d-flex align-items-center" style="border-radius: 8px; transition: all 0.2s ease;">
                                    <i class="ti ti-file-type-pdf fs-2 me-1"></i> PDF
                                </button>
                            </div>

                            <!-- Spacer -->
                            <div class="vr text-secondary mx-1 d-none d-md-block" style="opacity: 0.2; height: 24px;"></div>

                            <a href="index.php?p=raporlar/list&year=<?= $year ?>&months=<?= $month ?>" class="btn btn-light btn-sm border shadow-sm">
                                <i class="ti ti-arrow-left me-1"></i> Geri
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <!-- Changed class name from 'datatable' to 'report-data-table' to prevent unwanted automatic global initialization conflicts -->
                        <table class="table table-hover table-vcenter text-nowrap mb-0" id="puantajDataTable">
                            <thead>
                                <tr>
                                    <th>Personel Adı</th>
                                    <th>TC Kimlik</th>
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
                                    <td><?= htmlspecialchars(Security::safeDecrypt($r->kimlik_no ?? '')) ?></td>
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
                <script>
                    (function() {
                        var initAttempts = 0;
                        var maxAttempts = 50;
                        
                        var initDataTable = function() {
                            if (typeof $.fn.DataTable === 'undefined' || typeof $.fn.DataTable.Buttons === 'undefined') {
                                if (initAttempts < maxAttempts) {
                                    initAttempts++;
                                    setTimeout(initDataTable, 100); 
                                } else {
                                    console.error('Raporlar: DataTable/Buttons kütüphanesi yüklenemedi!');
                                }
                                return;
                            }

                            // Safety net to ensure Excel logic works robustly
                            if (typeof window.JSZip === 'undefined' && typeof JSZip !== 'undefined') {
                                window.JSZip = JSZip;
                            }

                            try {
                                if ($.fn.DataTable.isDataTable('#puantajDataTable')) {
                                    $('#puantajDataTable').DataTable().destroy();
                                }

                                // Standard Clean Data Export Formatting
                                var commonExportOptions = {
                                    columns: ':visible',
                                    format: {
                                        body: function (data, row, column, node) {
                                            var $node = $(node);
                                            // Find specific Name span inside first column for clean extraction
                                            var nameSpan = $node.find('.fw-semibold');
                                            if(nameSpan.length > 0) {
                                                return nameSpan.text().trim();
                                            }
                                            
                                            // Standard cell content extraction
                                            var text = $node.text().trim();
                                            // Replace hyphens and empty zeroes with empty strings for formula ease
                                            return (text === '-' || text === '0') ? '' : text;
                                        },
                                        header: function(data, column, node) {
                                            return $(node).text().trim();
                                        }
                                    }
                                };

                                var table = $('#puantajDataTable').DataTable({
                                    language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/tr.json' },
                                    pageLength: 50,
                                    responsive: false,
                                    scrollX: true,
                                    layout: {
                                        topStart: null, 
                                        topEnd: null,
                                        bottomStart: 'info',
                                        bottomEnd: 'paging'
                                    },
                                    buttons: [
                                        {
                                            extend: 'excelHtml5',
                                            className: 'd-none', // Background implementation only
                                            title: 'Puantaj_Raporu_<?= $year ?>_<?= $month ?>',
                                            exportOptions: commonExportOptions
                                        },
                                        {
                                            extend: 'pdfHtml5',
                                            className: 'd-none',
                                            orientation: 'landscape',
                                            pageSize: 'A4',
                                            title: 'Puantaj Raporu - <?= $displayMonth ?> <?= $year ?>',
                                            exportOptions: commonExportOptions,
                                            customize: function (doc) {
                                                // MAXIMIZE REAL ESTATE SYSTEM
                                                doc.defaultStyle.fontSize = 8; // Dropped from 9 for optimal squeezing
                                                doc.styles.tableHeader.fontSize = 8.5;
                                                doc.styles.tableHeader.bold = true;
                                                doc.styles.tableHeader.fillColor = '#1e293b'; 
                                                doc.styles.tableHeader.color = 'white';
                                                doc.styles.tableHeader.alignment = 'center';
                                                
                                                // Narrow down page margins to gain massive horizontal workspace
                                                doc.pageMargins = [15, 20, 15, 20];
                                                
                                                // Auto-distribute system ensures balanced flow
                                                doc.content[1].table.widths = Array(doc.content[1].table.body[0].length + 1).join('*').split('');
                                                
                                                var rowCount = doc.content[1].table.body.length;
                                                for (var i = 1; i < rowCount; i++) {
                                                    var rowData = doc.content[1].table.body[i];
                                                    for (var j = 0; j < rowData.length; j++) {
                                                        // High efficiency row aligners
                                                        rowData[j].alignment = (j === 0 || j === 4) ? 'left' : 'center';
                                                        
                                                        // Elegant subtle background striping
                                                        if (i % 2 === 0) {
                                                            rowData[j].fillColor = '#f8fafc';
                                                        }
                                                    }
                                                }
                                                
                                                // Minimalist Ultra-Condensed Table Space Layout
                                                var objLayout = {};
                                                objLayout['hLineWidth'] = function(i) { return .5; };
                                                objLayout['vLineWidth'] = function(i) { return .5; };
                                                objLayout['hLineColor'] = function(i) { return '#e2e8f0'; };
                                                objLayout['vLineColor'] = function(i) { return '#e2e8f0'; };
                                                objLayout['paddingLeft'] = function(i) { return 3; }; // Drastically reduced from 8
                                                objLayout['paddingRight'] = function(i) { return 3; }; // Drastically reduced from 8
                                                objLayout['paddingTop'] = function(i) { return 4; };
                                                objLayout['paddingBottom'] = function(i) { return 4; };
                                                doc.content[1].layout = objLayout;
                                                doc.content[1].layout = objLayout;
                                            }
                                        }
                                    ],
                                    columnDefs: [{ targets: [1, 2, 3], visible: false }]
                                });

                                // DYNAMIC CUSTOM CONTROLS GENERATION
                                // 1. Construct Premium Custom Column Visibility Dropdown
                                $('#customColvisMenu').empty();
                                
                                // Directly scrape TH nodes using jQuery to bypass library iteration inconsistencies
                                $('#puantajDataTable thead th').each(function(index) {
                                    if(index === 0) return; // Do not toggle Personnel Name
                                    
                                    var title = $(this).text().trim();
                                    // Safe retrieval of visibility state via API call
                                    var isVisible = true;
                                    try {
                                        isVisible = table.column(index).visible();
                                    } catch(err) {
                                        // Fallback: If columns haven't resolved fully yet, assume initial defaults
                                        isVisible = ![1, 2, 3].includes(index); 
                                    }
                                    
                                    var id = 'colCheck_' + index;
                                    var itemHtml = `
                                        <label class="dropdown-item d-flex align-items-center cursor-pointer py-2 px-3 rounded-2 hover-bg-light" style="font-size: 0.85rem;">
                                            <div class="form-check mb-0">
                                                <input class="form-check-input col-visibility-trigger" type="checkbox" value="" id="${id}" data-column="${index}" ${isVisible ? 'checked' : ''}>
                                                <span class="form-check-label fw-medium ms-1 text-secondary" for="${id}">
                                                    ${title}
                                                </span>
                                            </div>
                                        </label>`;
                                    $('#customColvisMenu').append(itemHtml);
                                });

                                // 2. Unhide Custom ActionBar container since DataTable is active
                                $('#customReportActions').removeClass('d-none d-md-none').addClass('d-flex');

                                // 3. Bind Premium Button Events to Background Table Triggers
                                $('#customBtnExcel').off('click').on('click', function() {
                                    table.button('.buttons-excel').trigger();
                                });
                                
                                $('#customBtnPdf').off('click').on('click', function() {
                                    table.button('.buttons-pdf').trigger();
                                });

                                // 4. Bind Dynamic Column Visibility Toggles
                                $(document).off('change', '.col-visibility-trigger').on('change', '.col-visibility-trigger', function() {
                                    var colIdx = $(this).data('column');
                                    table.column(colIdx).visible(this.checked);
                                });

                                console.log("Premium Rapor Arayüzü Hazır.");

                            } catch (e) {
                                console.error("Premium Init Exception:", e);
                            }
                        };

                        $(document).ready(function() {
                            initDataTable();
                            // GARANTİ YÖNTEM: Delegated Listener ve Select2 Entegrasyonu
                            $(document).on('change select2:select', '#year, #months, select[name="year"], select[name="months"]', function() {
                                $(this).closest('form').submit();
                            });
                        });
                    })();
                </script>

            <?php else: ?>
                <!-- --- DASHBOARD CARDS RENDERING --- -->
                <div class="row row-cards g-3">
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("İcmal Raporu", "Dönem bazlı personel maaş özet raporu. Brüt maaş, kesintiler ve net bilgileri.", "ti-chart-bar", "bg-dark-lt text-dark") ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("Bordro", "Personel bazlı detaylı bordro çıktısı. Yazdırılabilir ve PDF formatında.", "ti-file-invoice", "bg-teal-lt text-teal") ?>
                    </div>
                    <div class="col-xl-4 col-md-6">
                        <?php renderReportCard("Banka Listesi", "Bankaya gönderilecek ödeme listesi. IBAN ve hesap tutarlarını içerir.", "ti-building-bank", "bg-info-lt text-info") ?>
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
                        <?php renderReportCard("Kesinti Raporu", "Personel kesintilerinin (İcra, Avans vb.) detaylı filtrelemeli listesi.", "ti-scissors", "bg-dark-lt text-muted") ?>
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
                                <a href="#" class="btn btn-outline-teal w-100 text-nowrap"><i class="ti ti-file-spreadsheet me-1"></i> İcmal (Excel)</a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="#" class="btn btn-outline-info w-100 text-nowrap"><i class="ti ti-building-bank me-1"></i> Banka (Excel)</a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="#" class="btn btn-outline-success w-100 text-nowrap"><i class="ti ti-tools-kitchen-2 me-1"></i> Sodexo (Excel)</a>
                            </div>
                            <div class="col-md-3 col-6">
                                <a href="#" class="btn btn-outline-dark w-100 text-nowrap"><i class="ti ti-printer me-1"></i> Bordroları Yazdır</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
