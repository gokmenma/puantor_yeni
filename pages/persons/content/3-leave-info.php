<?php
use App\Helper\Date;

$personId = $person->id ?? 0;
// Modeller global namespace içinde
$puantajObj = new Puantaj();
$projectsObj = new Projects();

// Yıllık İzin Hakediş Modelini ekle
require_once 'Model/IzinHakedis.php';
$hakedisObj = new IzinHakedis();

if ($personId > 0) {
    $firma_id = $person->firm_id ?? $_SESSION['firm_id'] ?? 0;
    if (!empty($person->job_start_date)) {
        $hakedisObj->hesaplaVeKaydet($personId, $firma_id, $person->job_start_date, $person->birth_date ?? null);
    }
}
$hakedisList = $personId > 0 ? $hakedisObj->getByPersonel($personId) : [];

$total_hakedis = 0;
$total_kullanilan = 0;
foreach ($hakedisList as $h) {
    $total_hakedis += $h->gun_sayisi;
    $total_kullanilan += $h->kullanilan_gun;
}
$total_kalan = $total_hakedis - $total_kullanilan;

// Puantaj verilerini çek
$puantaj = $personId > 0 ? $puantajObj->getPuantajInfoByPerson($personId) : [];

// İzin verilerini filtrele ve istatistikleri hesapla
$leave_records = [];
$leave_events = [];
$stats = [
    'ucretli_gun' => 0,
    'ucretsiz_gun' => 0,
    'rapor_gun' => 0,
    'toplam_gun' => 0
];

// Puantaj türlerini tek tek sorgulamak yerine bir kerede veya ihtiyaç anında çekelim
foreach ($puantaj as $item) {
    // Puantaj türü bilgisini al
    $stmt = $puantajObj->getDb()->prepare("SELECT * FROM puantajturu WHERE id = ?");
    $stmt->execute([$item->puantaj_id]);
    $turu = $stmt->fetch(PDO::FETCH_OBJ);
    
    if (!$turu) continue;

    // Sadece izin ve rapor türlerini al
    if ($turu->Turu == 'Ücretli İzin' || $turu->Turu == 'Ücretsiz' || ($item->IzinRapor ?? 0) == 1) {
        $leave_records[] = [
            'item' => $item,
            'turu' => $turu
        ];

        // İstatistikleri güncelle
        if ($turu->Turu == 'Ücretli İzin') {
            $stats['ucretli_gun'] += ($item->gunluk ?? 0);
        } else if ($turu->Turu == 'Ücretsiz') {
            if (stripos($turu->PuantajAdi, 'Rapor') !== false) {
                $stats['rapor_gun'] += ($item->gunluk ?? 0);
            } else {
                $stats['ucretsiz_gun'] += ($item->gunluk ?? 0);
            }
        }
        $stats['toplam_gun'] += ($item->gunluk ?? 0);

        // Takvim için etkinlikleri hazırla
        $gun = $item->gun;
        if (strlen($gun) == 8) {
            $gun = substr($gun, 0, 4) . '-' . substr($gun, 4, 2) . '-' . substr($gun, 6, 2);
        }

        $leave_events[] = [
            'id' => $item->id,
            'title' => ($turu->PuantajKod ?? '') . " - " . ($turu->PuantajAdi ?? ''),
            'start' => $gun,
            'allDay' => true,
            'backgroundColor' => $turu->ArkaPlanRengi ?? '#206bc4',
            'textColor' => $turu->FontRengi ?? '#ffffff',
            'borderColor' => $turu->ArkaPlanRengi ?? '#206bc4',
            'extendedProps' => [
                'project' => $projectsObj->find($item->project_id)->project_name ?? '-',
                'type' => $turu->PuantajAdi ?? '-',
                'amount' => ($item->saat ?? 0) . ' Saat'
            ]
        ];
    }
}
?>

<style>
    /* Takvim ve İzin Tablosu Özel Stilleri */
    #leave_ec {
        background: #fff;
        border-radius: 8px;
        min-height: 600px;
    }
    
    .ec-event {
        border-radius: 6px !important;
        padding: 4px 8px !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        border: none !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
    }

    /* Yıllık Görünüm - Özel Minimalist Takvim */
    .year-grid-container {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 16px;
        padding: 20px;
        align-items: start;
        background: var(--tblr-bg-surface, #fff);
    }
    
    @media (max-width: 1600px) {
        .year-grid-container {
            grid-template-columns: repeat(4, 1fr);
        }
    }
    @media (max-width: 1200px) {
        .year-grid-container {
            grid-template-columns: repeat(3, 1fr);
        }
    }
    @media (max-width: 992px) {
        .year-grid-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    @media (max-width: 576px) {
        .year-grid-container {
            grid-template-columns: repeat(1, 1fr);
        }
    }
    
    .mini-month {
        background: var(--tblr-card-bg, var(--tblr-bg-surface, #fff));
        border: 1px solid var(--tblr-border-color, #e2e8f0);
        border-radius: 12px;
        padding: 14px;
        font-family: inherit;
        box-shadow: 0 2px 8px rgba(0,0,0,0.01);
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.25s;
    }
    
    .mini-month:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.06);
        border-color: var(--tblr-primary, #206bc4);
    }
    
    .mini-month-header {
        text-align: center;
        font-size: 0.9rem;
        font-weight: 800;
        color: var(--tblr-body-color, #0f172a);
        margin-bottom: 12px;
        text-transform: capitalize;
        border-bottom: 1px solid var(--tblr-border-color-translucent, var(--tblr-border-color, #f1f5f9));
        padding-bottom: 8px;
        letter-spacing: -0.01em;
    }
    
    .mini-month-days {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        text-align: center;
        gap: 2px;
    }
    
    .mini-day-head {
        font-size: 0.7rem;
        font-weight: 800;
        color: var(--tblr-secondary, #94a3b8);
        padding: 4px 0;
        text-transform: uppercase;
    }
    
    .mini-day {
        font-size: 0.75rem;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
        border-radius: 6px;
        transition: all 0.15s cubic-bezier(0.4, 0, 0.2, 1);
        color: var(--tblr-body-color, #334155);
        font-weight: 500;
    }
    
    .mini-day:hover:not(.mini-day-empty) {
        background: var(--tblr-bg-hover, #f1f5f9);
        color: var(--tblr-body-color, #0f172a);
        font-weight: 700;
    }
    
    .mini-day-empty {
        cursor: default;
        visibility: hidden;
    }
    
    .mini-day.has-event {
        box-shadow: 0 2px 4px rgba(0,0,0,0.06);
        border-radius: 6px;
        font-weight: 700 !important;
    }
    
    .mini-day.has-event:hover {
        transform: scale(1.2);
        box-shadow: 0 4px 10px rgba(0,0,0,0.15) !important;
        z-index: 5;
    }
    
    .mini-day.sunday {
        color: var(--tblr-red, #ef4444);
    }
    
    .mini-day.today {
        background: rgba(var(--tblr-primary-rgb, 32, 107, 196), 0.1) !important;
        font-weight: 800;
        color: var(--tblr-primary, #206bc4) !important;
        border: 1px solid var(--tblr-primary, #206bc4);
    }

    /* Modern SweetAlert2 overrides to match Tabler Premium theme */
    .swal2-popup {
        background: var(--tblr-card-bg, var(--tblr-bg-surface, #fff)) !important;
        color: var(--tblr-body-color, #1e293b) !important;
        border-radius: 16px !important;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08) !important;
        border: 1px solid var(--tblr-border-color, #e2e8f0) !important;
    }
    .swal2-title {
        color: var(--tblr-body-color, #1e293b) !important;
        font-weight: 800 !important;
        letter-spacing: -0.02em;
    }
    .swal2-html-container {
        color: var(--tblr-secondary, #64748b) !important;
    }
    .swal2-confirm {
        background-color: var(--tblr-primary, #206bc4) !important;
        border-radius: 8px !important;
        padding: 10px 24px !important;
        font-weight: 600 !important;
        box-shadow: 0 4px 12px rgba(var(--tblr-primary-rgb, 32, 107, 196), 0.15) !important;
    }
</style>

<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header d-flex align-items-center justify-content-between bg-white py-3">
                    <h3 class="card-title font-weight-bold">
                        <i class="ti ti-calendar-stats me-2 text-primary"></i> İzin ve Rapor Bilgileri
                    </h3>
                    <div class="d-flex align-items-center gap-2">
                        <div class="input-group input-group-flat input-group-sm" style="width: 160px;">
                            <button type="button" class="btn btn-icon btn-sm btn-outline-primary" id="prev_leave_year_btn" title="Önceki Yıl">
                                <i class="ti ti-chevron-left"></i>
                            </button>
                            <select class="form-select form-select-sm text-center font-weight-bold" id="leave_year_select">
                                <?php
                                $currentYear = date('Y');
                                for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
                                    $selected = ($i == $currentYear) ? 'selected' : '';
                                    echo "<option value='$i' $selected>$i</option>";
                                }
                                ?>
                            </select>
                            <button type="button" class="btn btn-icon btn-sm btn-outline-primary" id="next_leave_year_btn" title="Sonraki Yıl">
                                <i class="ti ti-chevron-right"></i>
                            </button>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary active" id="view_leave_year_btn" title="Yıllık Görünüm">
                                <i class="ti ti-calendar icon"></i> Yıl
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="view_leave_calendar_btn" title="Takvim Görünümü">
                                <i class="ti ti-calendar-event icon"></i> Ay
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="view_leave_table_btn" title="Liste Görünümü">
                                <i class="ti ti-table icon"></i> Liste
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modal_leave_hakedis">
                            <i class="ti ti-checklist icon me-1"></i> Hakediş Bilgileri
                        </button>
                        <button class="btn btn-icon btn-sm excel text-success border-0" onclick="exportLeaveToExcel()" title="Excel'e Aktar">
                            <i class="ti ti-file-spreadsheet icon"></i>
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Özet Kartları (Header altında iç kısım) -->
                    <div class="row g-3 p-3 border-bottom bg-surface m-0">
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-sm border-0" style="background: rgba(32, 107, 196, 0.05); border-radius: 12px;">
                                <div class="card-body d-flex align-items-center p-3">
                                    <span class="bg-primary text-white avatar me-3" style="border-radius: 10px; width: 40px; height: 40px;">
                                        <i class="ti ti-calendar-check icon"></i>
                                    </span>
                                    <div>
                                        <div class="text-muted font-weight-medium" style="font-size: 0.75rem;">Ücretli İzin</div>
                                        <div class="h3 mb-0 font-weight-bold" style="color: var(--tblr-primary) !important;"><?php echo $stats['ucretli_gun']; ?> Gün</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-sm border-0" style="background: rgba(214, 51, 108, 0.05); border-radius: 12px;">
                                <div class="card-body d-flex align-items-center p-3">
                                    <span class="bg-danger text-white avatar me-3" style="border-radius: 10px; width: 40px; height: 40px;">
                                        <i class="ti ti-calendar-minus icon"></i>
                                    </span>
                                    <div>
                                        <div class="text-muted font-weight-medium" style="font-size: 0.75rem;">Ücretsiz İzin</div>
                                        <div class="h3 mb-0 font-weight-bold" style="color: var(--tblr-danger) !important;"><?php echo $stats['ucretsiz_gun']; ?> Gün</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-sm border-0" style="background: rgba(245, 159, 0, 0.05); border-radius: 12px;">
                                <div class="card-body d-flex align-items-center p-3">
                                    <span class="bg-warning text-white avatar me-3" style="border-radius: 10px; width: 40px; height: 40px;">
                                        <i class="ti ti-first-aid-kit icon"></i>
                                    </span>
                                    <div>
                                        <div class="text-muted font-weight-medium" style="font-size: 0.75rem;">Raporlu Gün</div>
                                        <div class="h3 mb-0 font-weight-bold" style="color: var(--tblr-warning) !important;"><?php echo $stats['rapor_gun']; ?> Gün</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-3">
                            <div class="card card-sm border-0" style="background: rgba(47, 179, 68, 0.05); border-radius: 12px;">
                                <div class="card-body d-flex align-items-center p-3">
                                    <span class="bg-success text-white avatar me-3" style="border-radius: 10px; width: 40px; height: 40px;">
                                        <i class="ti ti-sum icon"></i>
                                    </span>
                                    <div>
                                        <div class="text-muted font-weight-medium" style="font-size: 0.75rem;">Toplam İzin</div>
                                        <div class="h3 mb-0 font-weight-bold" style="color: var(--tblr-success) !important;"><?php echo $stats['toplam_gun']; ?> Gün</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Yıllık Görünüm -->
                    <div id="leave_year_view">
                        <div id="leave_year_view_grid" class="year-grid-container">
                            <!-- JS ile 12 ay takvimi buraya eklenecek -->
                        </div>
                    </div>

                    <!-- Takvim Görünümü (Başlangıçta Gizli) -->
                    <div id="leave_calendar_view" style="display: none;">
                        <div id="leave_ec" class="p-3"></div>
                    </div>

                    <!-- Liste Görünümü (Başlangıçta Gizli) -->
                    <div id="leave_table_view" style="display: none;">
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table table-hover" id="leave_history_fixed">
                                <thead>
                                    <tr>
                                        <th class="w-1">ID</th>
                                        <th>Tarih</th>
                                        <th>İzin Türü</th>
                                        <th>Kategori</th>
                                        <th>Proje</th>
                                        <th>Süre</th>
                                        <th>Açıklama</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($leave_records as $record): 
                                            $item = $record['item'];
                                            $turu = $record['turu'];
                                            $projectName = $projectsObj->find($item->project_id)->project_name ?? '-';
                                            
                                            // Kategori badge rengi
                                            $badgeClass = 'bg-secondary-lt';
                                            if ($turu->Turu == 'Ücretli İzin') $badgeClass = 'bg-primary-lt';
                                            else if ($turu->Turu == 'Ücretsiz') {
                                                if (stripos($turu->PuantajAdi, 'Rapor') !== false) $badgeClass = 'bg-warning-lt';
                                                else $badgeClass = 'bg-danger-lt';
                                            }
                                        ?>
                                            <tr>
                                                <td><span class="text-muted"><?php echo $item->id; ?></span></td>
                                                <td class="font-weight-bold"><?php echo Date::dmY($item->gun); ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="avatar avatar-xs me-2" style="background-color:<?php echo $turu->ArkaPlanRengi; ?>; color:<?php echo $turu->FontRengi; ?>; font-size: 0.6rem; font-weight: bold;">
                                                            <?php echo $turu->PuantajKod; ?>
                                                        </span>
                                                        <span><?php echo $turu->PuantajAdi; ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $badgeClass; ?> px-2 py-1">
                                                        <?php echo $turu->Turu; ?>
                                                    </span>
                                                </td>
                                                <td class="text-muted"><?php echo $projectName; ?></td>
                                                <td>
                                                    <span class="font-weight-medium"><?php echo $item->saat; ?> Saat</span>
                                                    <small class="text-muted d-block" style="font-size: 0.65rem;">(1 Gün)</small>
                                                </td>
                                                <td class="text-muted small"><?php echo $item->description ?? '-'; ?></td>
                                            </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- card end -->
        </div> <!-- col-12 end -->
    </div> <!-- row end -->
</div> <!-- container end -->

<!-- Modal: Yıllık İzin Hakedişleri -->
<div class="modal modal-blur fade" id="modal_leave_hakedis" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title font-weight-bold">
                    <i class="ti ti-checklist me-2 text-success"></i> Yıllık İzin Hakedişleri - <?= htmlspecialchars($person->full_name ?? '') ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Hakediş Özet Bilgileri -->
                <div class="row g-2 mb-4">
                    <div class="col-4">
                        <div class="p-3 text-center rounded bg-primary-lt">
                            <div class="text-muted small">Hak Edilen</div>
                            <div class="h2 mb-0 font-weight-bold text-primary"><?= $total_hakedis ?> Gün</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 text-center rounded bg-danger-lt">
                            <div class="text-muted small">Kullanılan</div>
                            <div class="h2 mb-0 font-weight-bold text-danger"><?= $total_kullanilan ?> Gün</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 text-center rounded bg-success-lt">
                            <div class="text-muted small">Kalan</div>
                            <div class="h2 mb-0 font-weight-bold text-success"><?= $total_kalan ?> Gün</div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                    <table class="table table-vcenter card-table table-hover table-striped" id="hakedis_list_table">
                        <thead>
                            <tr>
                                <th>Yıl</th>
                                <th>Tarih</th>
                                <th class="text-center">Hakedilen</th>
                                <th class="text-center">Kullanılan</th>
                                <th class="text-center">Kalan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($hakedisList)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted small py-3">Hakediş kaydı bulunmuyor.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($hakedisList as $h): 
                                    $kalan = $h->gun_sayisi - $h->kullanilan_gun;
                                    ?>
                                    <tr>
                                        <td class="font-weight-bold text-muted"><?= $h->yil ?>. Yıl</td>
                                        <td class="small"><?= Date::dmY($h->hakedis_tarihi) ?></td>
                                        <td class="text-center font-weight-medium"><?= $h->gun_sayisi ?></td>
                                        <td class="text-center text-danger"><?= $h->kullanilan_gun ?></td>
                                        <td class="text-center font-weight-bold text-success"><?= $kalan ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Takvim etkinliklerini JS tarafına aktar
    window.leaveEvents = <?php echo json_encode($leave_events); ?>;
    let leaveCalendar = null;

    // Görünüm Değiştirme Mantığı
    function switchLeaveView(viewName) {
        const views = {
            'year': document.getElementById('leave_year_view'),
            'calendar': document.getElementById('leave_calendar_view'),
            'table': document.getElementById('leave_table_view')
        };
        for (const key in views) {
            if (views[key]) {
                views[key].style.display = 'none';
            }
        }
        if (views[viewName]) {
            views[viewName].style.display = 'block';
        }
    }

    document.getElementById('view_leave_year_btn').addEventListener('click', function() {
        switchLeaveView('year');
        setActiveViewBtn(this);
        renderLeaveYearlyGrid(document.getElementById('leave_year_select').value);
    });

    document.getElementById('view_leave_calendar_btn').addEventListener('click', function() {
        switchLeaveView('calendar');
        setActiveViewBtn(this);
        if (!leaveCalendar) {
            initLeaveCalendar();
        }
        
        // Takvimi seçili yıla götür
        const selectedYear = document.getElementById('leave_year_select').value;
        const currentMonth = new Date().getMonth();
        leaveCalendar.gotoDate(new Date(selectedYear, currentMonth, 1));
    });

    document.getElementById('view_leave_table_btn').addEventListener('click', function() {
        switchLeaveView('table');
        setActiveViewBtn(this);
        initLeaveDataTable();
    });

    function initLeaveDataTable() {
        if (!$.fn.DataTable.isDataTable('#leave_history_fixed')) {
            window.createDataTable('#leave_history_fixed', {
                autoWidth: false,
                responsive: true,
                order: [[1, "desc"]],
                columnDefs: [
                    { targets: "_all", defaultContent: "-" }
                ]
            });
        } else {
            $('#leave_history_fixed').DataTable().columns.adjust().draw();
        }
    }

    function setActiveViewBtn(btn) {
        document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }

    // Yıl Değiştirme Mantığı
    document.getElementById('prev_leave_year_btn').addEventListener('click', function() {
        const select = document.getElementById('leave_year_select');
        select.selectedIndex = Math.max(0, select.selectedIndex - 1);
        select.dispatchEvent(new Event('change'));
    });

    document.getElementById('next_leave_year_btn').addEventListener('click', function() {
        const select = document.getElementById('leave_year_select');
        select.selectedIndex = Math.min(select.options.length - 1, select.selectedIndex + 1);
        select.dispatchEvent(new Event('change'));
    });

    document.getElementById('leave_year_select').addEventListener('change', function() {
        const year = this.value;
        if (document.getElementById('leave_year_view').style.display !== 'none') {
            renderLeaveYearlyGrid(year);
        }
        if (leaveCalendar) {
            const currentMonth = leaveCalendar.getDate().getMonth();
            leaveCalendar.gotoDate(new Date(year, currentMonth, 1));
        }
        if ($.fn.DataTable.isDataTable('#leave_history_fixed')) {
            filterLeaveTableByYear(year);
        }
    });

    function filterLeaveTableByYear(year) {
        if ($.fn.DataTable.isDataTable('#leave_history_fixed')) {
            const table = $('#leave_history_fixed').DataTable();
            // Daha esnek bir arama: Sadece yılı içeren satırları göster
            table.column(1).search(year).draw();
        }
    }

    function renderLeaveYearlyGrid(year) {
        const container = document.getElementById('leave_year_view_grid');
        container.innerHTML = '';
        
        const monthNames = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
        const dayNames = ["P", "S", "Ç", "P", "C", "C", "P"];

        for (let m = 0; m < 12; m++) {
            const monthDiv = document.createElement('div');
            monthDiv.className = 'mini-month';
            
            const header = document.createElement('div');
            header.className = 'mini-month-header';
            header.textContent = `${monthNames[m]} ${year}`;
            monthDiv.appendChild(header);
            
            const daysGrid = document.createElement('div');
            daysGrid.className = 'mini-month-days';
            
            dayNames.forEach(d => {
                const dHead = document.createElement('div');
                dHead.className = 'mini-day-head';
                dHead.textContent = d;
                daysGrid.appendChild(dHead);
            });
            
            const firstDay = new Date(year, m, 1).getDay();
            const daysInMonth = new Date(year, m + 1, 0).getDate();
            let startOffset = firstDay === 0 ? 6 : firstDay - 1;
            
            for (let i = 0; i < startOffset; i++) {
                const emptyDay = document.createElement('div');
                emptyDay.className = 'mini-day mini-day-empty';
                daysGrid.appendChild(emptyDay);
            }
            
            const today = new Date();
            const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = `${year}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const isSunday = (startOffset + d - 1) % 7 === 6;
                const isToday = dateStr === todayStr;
                
                const dayEvents = window.leaveEvents.filter(e => e.start === dateStr);
                const hasEvent = dayEvents.length > 0;
                
                const dayDiv = document.createElement('div');
                dayDiv.className = `mini-day ${isSunday ? 'sunday' : ''} ${isToday ? 'today' : ''} ${hasEvent ? 'has-event' : ''}`;
                dayDiv.textContent = d;
                
                if (hasEvent) {
                    const ev = dayEvents[0];
                    dayDiv.style.backgroundColor = ev.backgroundColor;
                    dayDiv.style.color = ev.textColor;
                    
                    dayDiv.onclick = function() {
                        let htmlContent = '';
                        dayEvents.forEach(e => {
                            htmlContent += `
                                <div class="text-start mb-3 border-bottom pb-2">
                                    <h4 class="mb-2 font-weight-bold" style="color: ${e.backgroundColor}">${e.title}</h4>
                                    <p class="mb-1"><i class="ti ti-briefcase text-muted me-2"></i><strong>Proje:</strong> ${e.extendedProps.project}</p>
                                    <p class="mb-1"><i class="ti ti-category text-muted me-2"></i><strong>Tür:</strong> ${e.extendedProps.type}</p>
                                    <p class="mb-0"><i class="ti ti-clock text-muted me-2"></i><strong>Süre:</strong> ${e.extendedProps.amount}</p>
                                </div>
                            `;
                        });

                        Swal.fire({
                            title: `${d} ${monthNames[m]} ${year} İzin Kayıtları`,
                            html: `<div class="py-2">${htmlContent}</div>`,
                            icon: 'info',
                            confirmButtonText: 'Kapat'
                        });
                    };
                }
                
                daysGrid.appendChild(dayDiv);
            }
            
            monthDiv.appendChild(daysGrid);
            container.appendChild(monthDiv);
        }
    }

    function initLeaveCalendar() {
        leaveCalendar = new EventCalendar(document.getElementById('leave_ec'), {
            view: 'dayGridMonth',
            locale: 'tr',
            firstDay: 1,
            headerToolbar: {
                start: 'prev,next today',
                center: 'title',
                end: 'dayGridMonth,dayGridWeek,listMonth'
            },
            buttonText: {
                today: 'Bugün',
                dayGridMonth: 'Ay',
                dayGridWeek: 'Hafta',
                listMonth: 'Ajanda'
            },
            events: window.leaveEvents,
            eventClick: function(info) {
                const e = info.event;
                Swal.fire({
                    title: e.title,
                    html: `
                        <div class="text-start py-2">
                            <p class="mb-2"><i class="ti ti-briefcase text-primary me-2"></i><strong>Proje:</strong> ${e.extendedProps.project}</p>
                            <p class="mb-2"><i class="ti ti-category text-primary me-2"></i><strong>Tür:</strong> ${e.extendedProps.type}</p>
                            <p class="mb-2"><i class="ti ti-calendar text-primary me-2"></i><strong>Tarih:</strong> ${new Date(e.start).toLocaleDateString('tr-TR')}</p>
                            <p class="mb-0"><i class="ti ti-clock text-primary me-2"></i><strong>Süre:</strong> ${e.extendedProps.amount}</p>
                        </div>
                    `,
                    icon: 'info',
                    confirmButtonText: 'Kapat'
                });
            }
        });
    }

    // İlk Yükleme
    document.addEventListener('DOMContentLoaded', function() {
        const currentYear = document.getElementById('leave_year_select').value;
        renderLeaveYearlyGrid(currentYear);
        // filterLeaveTableByYear(currentYear);
    });

    // Excel export logic
    window.exportLeaveToExcel = function() {
        document.getElementById('view_leave_table_btn').click();
        setTimeout(() => {
            if ($.fn.DataTable.isDataTable('#leave_history_fixed')) {
                const table = $('#leave_history_fixed').DataTable();
                alert('Excel aktarma özelliği hazırlanıyor...');
            }
        }, 200);
    };
</script>
