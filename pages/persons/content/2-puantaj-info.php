<?php
require_once "App/Helper/helper.php";
require_once "App/Helper/date.php";
require_once "Model/Bordro.php";
require_once "Model/Puantaj.php";
require_once "Model/Projects.php";
require_once "App/Helper/security.php";


use App\Helper\Security;
use App\Helper\Helper;
use App\Helper\Date;

$bordro = new Bordro();
$puantajObj = new Puantaj();
$projects = new Projects();

$person_id = $person->id;
$puantaj_info = $puantajObj->getPuantajInfoByPerson($person_id);

if (!$Auths->Authorize("person_page_puantaj_info")) {
    Helper::authorizePage();
    return;
}

// Takvim etkinliklerini hazırla
$events = [];
foreach ($puantaj_info as $item) {
    $puantaj_turu = $puantajObj->getPuantajTuruById($item->puantaj_id);
    if (!$puantaj_turu) continue;

    // Ymd (20260504) formatını Y-m-d (2026-05-04) formatına çevir
    $gun = $item->gun;
    if (strlen($gun) == 8 && is_numeric($gun)) {
        $gun = substr($gun, 0, 4) . '-' . substr($gun, 4, 2) . '-' . substr($gun, 6, 2);
    } else {
        $gun = Date::dmY($gun, 'Y-m-d');
    }

    $events[] = [
        'id' => $item->id,
        'title' => ($puantaj_turu->PuantajKod ?? '') . " (" . ($item->saat ?? 0) . " sa)",
        'start' => $gun,
        'allDay' => true,
        'extendedProps' => [
            'project' => $projects->find($item->project_id)->project_name ?? '',
            'type' => $puantaj_turu->PuantajAdi ?? '',
            'amount' => Helper::formattedMoney($item->tutar ?? 0)
        ],
        'backgroundColor' => $puantaj_turu->ArkaPlanRengi ?? '#206bc4',
        'textColor' => $puantaj_turu->FontRengi ?? '#ffffff',
        'borderColor' => $puantaj_turu->ArkaPlanRengi ?? '#206bc4'
    ];
}

?>
<style>
    table.datatable th,
    table.datatable td {
        text-align: left !important;
    }
    
    /* EventCalendar Styling Override for Premium Theme Compatibility */
    #ec {
        min-height: 600px;
        background: var(--tblr-card-bg, var(--tblr-bg-surface, #fff));
        color: var(--tblr-body-color, #1e293b);
        padding: 24px;
        border-radius: var(--tblr-border-radius-lg, 12px);
        border: 1px solid var(--tblr-border-color, #e2e8f0);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .ec {
        background-color: transparent !important;
        border: none !important;
        font-family: var(--tblr-font-sans-serif, inherit) !important;
    }
    
    .ec-header {
        border-bottom: 1px solid var(--tblr-border-color-translucent, var(--tblr-border-color, #f1f5f9)) !important;
        background: transparent !important;
    }
    
    .ec-day-head {
        font-weight: 700 !important;
        color: var(--tblr-secondary, #64748b) !important;
        font-size: 0.85rem !important;
        padding: 12px 0 !important;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .ec-day {
        background-color: transparent !important;
        border: 1px solid var(--tblr-border-color-translucent, var(--tblr-border-color, #f1f5f9)) !important;
    }
    
    .ec-today {
        background-color: rgba(var(--tblr-primary-rgb, 32, 107, 196), 0.06) !important;
        font-weight: bold;
    }
    
    .ec-button {
        background-color: var(--tblr-bg-surface, #fff) !important;
        border: 1px solid var(--tblr-border-color, #e2e8f0) !important;
        color: var(--tblr-body-color, #1e293b) !important;
        border-radius: 8px !important;
        padding: 6px 14px !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02) !important;
    }
    
    .ec-button:hover {
        background-color: var(--tblr-bg-hover, #f8f9fa) !important;
        border-color: var(--tblr-border-color-hover, #cbd5e1) !important;
        color: var(--tblr-primary, #206bc4) !important;
    }
    
    .ec-button.ec-active {
        background-color: var(--tblr-primary, #206bc4) !important;
        border-color: var(--tblr-primary, #206bc4) !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(var(--tblr-primary-rgb, 32, 107, 196), 0.2) !important;
    }
    
    .ec-title {
        font-size: 1.25rem !important;
        font-weight: 800 !important;
        color: var(--tblr-body-color, #1e293b) !important;
        letter-spacing: -0.02em;
    }
    
    .ec-event {
        border-radius: 6px !important;
        padding: 4px 8px !important;
        font-size: 0.75rem !important;
        font-weight: 700 !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05) !important;
        border: none !important;
        transition: transform 0.2s ease, box-shadow 0.2s ease !important;
    }
    
    .ec-event:hover {
        transform: translateY(-1.5px) scale(1.02) !important;
        box-shadow: 0 6px 12px rgba(0,0,0,0.1) !important;
    }
    
    .ec-event-title {
        font-weight: 700 !important;
    }
    
    .ec-list-day {
        background-color: rgba(var(--tblr-primary-rgb, 32, 107, 196), 0.05) !important;
        color: var(--tblr-primary, #206bc4) !important;
        font-weight: 700 !important;
        border: none !important;
    }
    
    .ec-list-item {
        border-radius: 8px !important;
        border-left: 4px solid var(--ec-event-bg-color, var(--tblr-primary, #206bc4)) !important;
        background-color: var(--tblr-bg-surface, #fff) !important;
        color: var(--tblr-body-color) !important;
        margin: 6px 0 !important;
        padding: 10px 14px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02) !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
        border-top: none !important;
        border-right: none !important;
        border-bottom: none !important;
    }
    
    .ec-list-item:hover {
        transform: translateX(4px) !important;
        box-shadow: 0 4px 10px rgba(0,0,0,0.06) !important;
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
        box-shadow: inset 0 0 0 1px var(--tblr-primary, #206bc4) !important;
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
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Çalışma Bilgileri</h3>
                    <div class="d-flex align-items-center gap-2">
                        <select class="form-select form-select-sm" id="calendar_year_select" style="width: 100px;">
                            <?php
                            $currentYear = date('Y');
                            for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
                                $selected = ($i == $currentYear) ? 'selected' : '';
                                echo "<option value='$i' $selected>$i</option>";
                            }
                            ?>
                        </select>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-primary active" id="view_calendar_btn" title="Takvim Görünümü">
                                <i class="ti ti-calendar icon"></i> Takvim
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="view_table_btn" title="Liste Görünümü">
                                <i class="ti ti-table icon"></i> Liste
                            </button>
                        </div>
                        <a href="#" class="btn btn-icon btn-sm excel text-success" id="export_excel_puantaj_info" data-tooltip="Excele Aktar" title="Excele Aktar">
                            <i class="ti ti-file-excel icon"></i>
                        </a>
                    </div>
                </div>

                <div class="card-body p-0">
                    <!-- Takvim Görünümü -->
                    <div id="puantaj_calendar_view">
                        <div id="ec"></div>
                    </div>

                    <!-- Yıllık Görünüm -->
                    <div id="puantaj_year_view" style="display: none;">
                        <!-- Premium Yearly Summary Section -->
                        <div id="puantaj_year_summary" class="row g-3 p-3 border-bottom bg-surface m-0">
                            <div class="col-sm-4">
                                <div class="card card-sm border-0" style="background: rgba(32, 107, 196, 0.05); border-radius: 12px;">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <span class="bg-primary text-white avatar me-3" style="border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            <i class="ti ti-calendar-event icon" style="font-size: 1.25rem;"></i>
                                        </span>
                                        <div>
                                            <div class="text-muted font-weight-medium" style="font-size: 0.75rem;">Çalışılan Gün Sayısı</div>
                                            <div class="h3 mb-0 font-weight-bold" id="yearly_summary_days" style="color: var(--tblr-primary) !important;">0 Gün</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card card-sm border-0" style="background: rgba(47, 179, 68, 0.05); border-radius: 12px;">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <span class="bg-success text-white avatar me-3" style="border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            <i class="ti ti-clock icon" style="font-size: 1.25rem;"></i>
                                        </span>
                                        <div>
                                            <div class="text-muted font-weight-medium" style="font-size: 0.75rem;">Toplam Çalışma Süresi</div>
                                            <div class="h3 mb-0 font-weight-bold" id="yearly_summary_hours" style="color: var(--tblr-success) !important;">0 Saat</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="card card-sm border-0" style="background: rgba(245, 158, 11, 0.05); border-radius: 12px;">
                                    <div class="card-body d-flex align-items-center p-3">
                                        <span class="bg-warning text-white avatar me-3" style="border-radius: 10px; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;">
                                            <i class="ti ti-coin icon" style="font-size: 1.25rem;"></i>
                                        </span>
                                        <div>
                                            <div class="text-muted font-weight-medium" style="font-size: 0.75rem;">Toplam Hak Ediş Tutarı</div>
                                            <div class="h3 mb-0 font-weight-bold" id="yearly_summary_amount" style="color: var(--tblr-warning) !important;">0,00 TL</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="puantaj_year_view_grid" class="year-grid-container">
                            <!-- JS ile 12 ay takvimi buraya eklenecek -->
                        </div>
                    </div>

                    <!-- Liste Görünümü (Başlangıçta Gizli) -->
                    <div id="puantaj_table_view" style="display: none;">
                        <div class="table-responsive p-3">
                            <table class="table card-table table-hover text-nowrap datatable" id="puantaj_info_table">
                                <thead>
                                    <tr>
                                        <th style="width:7%">id</th>
                                        <th>Proje</th>
                                        <th>Puantaj Türü</th>
                                        <th>Tarih</th>
                                        <th>Saat</th>
                                        <th class="text-start">Tutar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($puantaj_info as $item): ?>
                                        <tr>
                                            <td><?php echo $item->id ?></td>
                                            <td><?php echo $projects->find($item->project_id)->project_name ?? '' ?></td>
                                            <td>
                                                <?php
                                                $puantaj_turu = $puantajObj->getPuantajTuruById($item->puantaj_id);
                                                echo $puantaj_turu->PuantajKod . " - " . $puantaj_turu->PuantajAdi;
                                                ?>
                                            </td>
                                            <td><?php echo Date::ymd($item->gun, "d.m.Y") ?></td>
                                            <td class="text-start"><?php echo $item->saat ?></td>
                                            <td class="text-start"><?php echo Helper::formattedMoney($item->tutar) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Takvim verilerini PHP'den al
    const events = <?php echo json_encode($events); ?>;
    const rawPuantaj = <?php echo json_encode($puantaj_info); ?>;
    
    // Takvimi ilklendir
    const ec = new EventCalendar(document.getElementById('ec'), {
        view: 'dayGridMonth',
        locale: 'tr',
        firstDay: 1,
        headerToolbar: {
            start: 'prev,next today',
            center: 'title',
            end: 'yearView,dayGridMonth,dayGridWeek,listMonth'
        },
        customButtons: {
            yearView: {
                text: 'Yıl',
                click: function() {
                    $('#puantaj_calendar_view').hide();
                    $('#puantaj_year_view').show();
                    const year = $('#calendar_year_select').val();
                    renderYearlyGrid(year);
                    updateYearlySummary(year);
                    // Update active state manually if needed
                    $('.ec-button-yearView').addClass('ec-active');
                    $('.ec-button-dayGridMonth, .ec-button-dayGridWeek, .ec-button-listMonth').removeClass('ec-active');
                }
            }
        },
        buttonText: {
            today: 'Bugün',
            dayGridMonth: 'Ay',
            dayGridWeek: 'Hafta',
            listMonth: 'Ajanda'
        },
        viewDidMount: function(info) {
            if (info.view && info.view.type !== 'yearView') {
                $('#puantaj_year_view').hide();
                $('#puantaj_calendar_view').show();
                $('.ec-button-yearView').removeClass('ec-active');
            }
        },
        events: events,
        eventClick: function(info) {
            const props = info.event.extendedProps;
            Swal.fire({
                title: info.event.title,
                html: `
                    <div class="text-start py-2">
                        <p class="mb-2"><i class="ti ti-briefcase text-primary me-2"></i><strong>Proje:</strong> ${props.project}</p>
                        <p class="mb-2"><i class="ti ti-category text-primary me-2"></i><strong>Tür:</strong> ${props.type}</p>
                        <p class="mb-2"><i class="ti ti-calendar text-primary me-2"></i><strong>Tarih:</strong> ${info.event.start.toLocaleDateString('tr-TR')}</p>
                        <p class="mb-0"><i class="ti ti-coin text-primary me-2"></i><strong>Tutar:</strong> ${props.amount}</p>
                    </div>
                `,
                icon: 'info',
                confirmButtonText: 'Kapat'
            });
        }
    });

    // Premium Yearly Summary Calculations
    function updateYearlySummary(year) {
        let totalDays = 0;
        let totalHours = 0;
        let totalAmount = 0;

        rawPuantaj.forEach(item => {
            let gunStr = item.gun.toString();
            let gunYear = '';
            if (gunStr.length === 8 && !isNaN(gunStr)) {
                gunYear = gunStr.substring(0, 4);
            } else if (gunStr.includes('-')) {
                gunYear = gunStr.split('-')[0];
            }
            
            if (gunYear === year.toString()) {
                totalDays++;
                totalHours += parseFloat(item.saat ?? 0);
                totalAmount += parseFloat(item.tutar ?? 0);
            }
        });

        $('#yearly_summary_days').text(totalDays + ' Gün');
        $('#yearly_summary_hours').text(totalHours.toLocaleString('tr-TR', { minimumFractionDigits: 0, maximumFractionDigits: 1 }) + ' Saat');
        $('#yearly_summary_amount').text(totalAmount.toLocaleString('tr-TR', { style: 'currency', currency: 'TRY' }));
    }

    // Görünüm Değiştirme
    $('#view_calendar_btn').on('click', function() {
        $(this).addClass('active');
        $('#view_table_btn').removeClass('active');
        $('#puantaj_calendar_view').show();
        $('#puantaj_table_view, #puantaj_year_view').hide();
    });

    $('#view_table_btn').on('click', function() {
        $(this).addClass('active');
        $('#view_calendar_btn').removeClass('active');
        $('#puantaj_calendar_view, #puantaj_year_view').hide();
        $('#puantaj_table_view').show();
        
        // DataTable adjustment
        if ($.fn.DataTable.isDataTable('#puantaj_info_table')) {
            const table = $('#puantaj_info_table').DataTable();
            table.columns.adjust().draw();
        } else {
             $("#puantaj_info_table").DataTable({
                autoWidth: false,
                order: [[3, "desc"]],
                language: {
                    url: "src/tr.json"
                },
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'excelHtml5',
                        className: 'd-none',
                        exportOptions: {
                            columns: ':visible'
                        }
                    }
                ]
            });
        }
    });

    // Premium Excel Export Action Integration
    $('#export_excel_puantaj_info').on('click', function(e) {
        e.preventDefault();
        $('#view_table_btn').trigger('click');
        setTimeout(() => {
            if ($.fn.DataTable.isDataTable('#puantaj_info_table')) {
                const table = $('#puantaj_info_table').DataTable();
                table.button('.buttons-excel').trigger();
            }
        }, 150);
    });

    // Yıllık Izgara Oluşturma (Özel Minimalist Versiyon)
    function renderYearlyGrid(year) {
        const container = $('#puantaj_year_view_grid');
        container.empty();
        
        const monthNames = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
        const dayNames = ["P", "S", "Ç", "P", "C", "C", "P"]; // Pzt başlangıçlı ama Pazartesi "P"

        for (let m = 0; m < 12; m++) {
            const monthDiv = $('<div class="mini-month"></div>');
            const header = $(`<div class="mini-month-header">${monthNames[m]} ${year}</div>`);
            monthDiv.append(header);
            
            const daysGrid = $('<div class="mini-month-days"></div>');
            
            // Başlıklar
            dayNames.forEach(d => daysGrid.append(`<div class="mini-day-head">${d}</div>`));
            
            // Ayın ilk günü ve gün sayısı
            const firstDay = new Date(year, m, 1).getDay(); // 0=Sun, 1=Mon...
            const daysInMonth = new Date(year, m + 1, 0).getDate();
            
            // Boşluklar (Pzt başlangıçlı yapmak için ayar)
            let startOffset = firstDay === 0 ? 6 : firstDay - 1;
            for (let i = 0; i < startOffset; i++) {
                daysGrid.append('<div class="mini-day mini-day-empty"></div>');
            }
            
            // Bugünü yerel saat dilimine göre bulalım
            const todayDate = new Date();
            const todayStr = `${todayDate.getFullYear()}-${String(todayDate.getMonth() + 1).padStart(2, '0')}-${String(todayDate.getDate()).padStart(2, '0')}`;

            // Günler
            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = `${year}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const isSunday = (startOffset + d - 1) % 7 === 6;
                const isToday = dateStr === todayStr;
                
                // Tarihte eşleşen tüm etkinlikleri bul (saat formatı içerebilir diye split)
                const dayEvents = events.filter(e => e.start.split(' ')[0] === dateStr);
                const hasEvent = dayEvents.length > 0;
                
                const dayDiv = $(`<div class="mini-day ${isSunday ? 'sunday' : ''} ${isToday ? 'today' : ''} ${hasEvent ? 'has-event' : ''}">${d}</div>`);
                
                if (hasEvent) {
                    const primaryEvent = dayEvents[0];
                    const tooltipText = dayEvents.map(ev => ev.title).join(', ');
                    dayDiv.attr('title', tooltipText);
                    
                    dayDiv.css({
                        'background-color': primaryEvent.backgroundColor || '#206bc4',
                        'color': primaryEvent.textColor || '#ffffff',
                        'font-weight': '700'
                    });

                    dayDiv.on('click', function() {
                        let htmlContent = '';
                        dayEvents.forEach(ev => {
                            htmlContent += `
                                <div class="text-start mb-3 border-bottom pb-2" style="border-color: var(--tblr-border-color) !important;">
                                    <h4 class="mb-2 font-weight-bold" style="color: ${ev.backgroundColor || '#206bc4'}">${ev.title}</h4>
                                    <p class="mb-1"><i class="ti ti-briefcase text-muted me-2"></i><strong>Proje:</strong> ${ev.extendedProps.project}</p>
                                    <p class="mb-1"><i class="ti ti-category text-muted me-2"></i><strong>Tür:</strong> ${ev.extendedProps.type}</p>
                                    <p class="mb-0"><i class="ti ti-coin text-muted me-2"></i><strong>Tutar:</strong> ${ev.extendedProps.amount}</p>
                                </div>
                            `;
                        });

                        Swal.fire({
                            title: `${d} ${monthNames[m]} ${year} Puantaj Kayıtları`,
                            html: `<div class="py-2">${htmlContent}</div>`,
                            icon: 'info',
                            confirmButtonText: 'Kapat'
                        });
                    });
                }
                
                daysGrid.append(dayDiv);
            }
            
            monthDiv.append(daysGrid);
            container.append(monthDiv);
        }
    }

    // Yıllık görünüm seçildiğinde tetikle
    $('#calendar_year_select').on('change', function() {
        const year = $(this).val();
        
        // Ana takvimi güncelle
        const currentDate = ec.getDate();
        ec.gotoDate(new Date(year, currentDate.getMonth(), 1));
        
        // Yıllık görünüm açıksa onu da güncelle
        if ($('#puantaj_year_view').is(':visible')) {
            renderYearlyGrid(year);
            updateYearlySummary(year);
        }
    });

    // Liste / Takvim geçişleri
    $('#view_calendar_btn').on('click', function() {
        $('#puantaj_calendar_view').show();
        $('#puantaj_year_view').hide();
        $('#puantaj_table_view').hide();
        $('.btn-outline-primary').removeClass('active');
        $(this).addClass('active');
    });

    $('#view_table_btn').on('click', function() {
        $('#puantaj_calendar_view').hide();
        $('#puantaj_year_view').hide();
        $('#puantaj_table_view').show();
        $('.btn-outline-primary').removeClass('active');
        $(this).addClass('active');
    });

    // İlk yüklemede varsayılan olarak Yılı göster ve istatistikleri yükle
    $('#puantaj_calendar_view').hide();
    $('#puantaj_year_view').show();
    const currentYear = $('#calendar_year_select').val();
    renderYearlyGrid(currentYear);
    updateYearlySummary(currentYear);
    
    // Yıllık butonu takvim içinde olduğu için, takvim yüklendikten sonra aktifliği set edelim
    setTimeout(() => {
        $('.ec-button-yearView').addClass('ec-active');
        $('.ec-button-dayGridMonth, .ec-button-dayGridWeek, .ec-button-listMonth').removeClass('ec-active');
    }, 100);

});
</script>