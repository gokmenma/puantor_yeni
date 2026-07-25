<?php
// Puantor Mobil - Hızlı Puantaj Girişi (Masaüstü Pratikliğinde)
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Puantaj.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/App/Helper/jobs.php";
require_once ROOT . "/App/Helper/teams.php";
require_once ROOT . "/Model/IzinTalep.php";

use App\Helper\Date;
use App\Helper\Security;

$personsModel = new Persons();
$puantajModel = new Puantaj();
$projectHelper = new ProjectHelper();
$projectsModel = new Projects();
$settingsModel = new SettingsModel();
$jobsHelper = new Jobs();
$teamsHelper = new Teams();

$firm_id = $_SESSION['firm_id'] ?? 0;
$view_mode = $_GET['view'] ?? 'monthly'; // daily, monthly
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_project_id = intval($_GET['project_id'] ?? 0);
$selected_job_group = $_GET['job_group'] ?? 0;
$selected_team_id = $_GET['team_id'] ?? 0;
$selected_collar_type = $_GET['collar_type'] ?? 'all'; // all, blue, white
$selected_person_status = $_GET['person_status'] ?? 'active'; // active, passive, all

// Filtre uygulanmış mı kontrol et
$isFiltered = ($selected_project_id != 0 || $selected_job_group != 0 || $selected_team_id != 0 || $selected_collar_type != 'all' || $selected_person_status != 'active');

// Filtreye göre beyaz yakalıları dahil etme durumu
$showWhiteCollar = ($selected_collar_type === 'white' || $selected_collar_type === 'all') ? 1 : 0;
// Eğer filtre 'all' ise ama sistem ayarı kapalıysa, sadece mavileri getir (Masaüstü davranışı)
$showWhiteCollarSetting = $settingsModel->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;
if ($selected_collar_type === 'all' && $showWhiteCollarSetting == 0) $showWhiteCollar = 0;


// Masaüstü ile %100 aynı personelleri getirmek için ortak fonksiyonu kullanıyoruz
if ($view_mode === 'monthly') {
    $first_day_ymd = "$year{$month}01";
    $last_day_ymd = date('Ymd', strtotime(date('Y-m-t', strtotime("$year-$month-01"))));
    // Set selected_date to a valid date within the month for fallback calculations
    if (date('Y-m') === "$year-$month") {
        $selected_date = date('Y-m-d');
    } else {
        $selected_date = "$year-$month-01";
    }
} else {
    $month = date('m', strtotime($selected_date));
    $year = date('Y', strtotime($selected_date));
    $first_day_ymd = date('Ymd', strtotime($selected_date . ' -0 days'));
    $last_day_ymd = date('Ymd', strtotime(date('Y-m-t', strtotime($selected_date))));
}

// Masaüstü listesi bu mantığı kullanır:
$all_projects = $projectsModel->getProjectsByFirm($firm_id);

if ($selected_project_id == 0) {
    $persons = $personsModel->getPersonIdByFirmBlueCollarCurrentMonth($firm_id, $first_day_ymd, $last_day_ymd, $selected_job_group, $selected_team_id, $showWhiteCollar, $selected_person_status);
} else {
    $persons = $projectsModel->getPersonIdByFromProjectCurrentMonth($selected_project_id, $first_day_ymd, $last_day_ymd, $selected_job_group, $selected_team_id, $showWhiteCollar, $selected_person_status);
}

$conn = $puantajModel->getDb();
$stmt = $conn->prepare("SELECT * FROM puantajturu ORDER BY Turu, PuantajSaati ASC");
$stmt->execute();
$puantaj_types = $stmt->fetchAll(PDO::FETCH_OBJ);

$grouped_types = [];
foreach ($puantaj_types as $type) {
    $grouped_types[$type->Turu][] = $type;
}

// Tarih navigasyonu için hesaplamalar
$prev_date = date('Y-m-d', strtotime($selected_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($selected_date . ' +1 day'));
$today = date('Y-m-d');
$is_today_or_future = ($selected_date >= $today);

// Aylık navigasyon hesaplamaları
$prev_month_date = date('Y-m-d', strtotime("$year-$month-01 -1 month"));
$prev_month_year = date('Y', strtotime($prev_month_date));
$prev_month_val = date('m', strtotime($prev_month_date));

$next_month_date = date('Y-m-d', strtotime("$year-$month-01 +1 month"));
$next_month_year = date('Y', strtotime($next_month_date));
$next_month_val = date('m', strtotime($next_month_date));

// OPTİMİZASYON: Toplu veri çekme (N+1 query problemini çözer)
$person_ids = array_map(function($p) { return $p->id; }, $persons);

$izinModel = new IzinTalep();
$onayliIzinGunleri = [];
if (!empty($person_ids)) {
    $onayliIzinGunleri = $izinModel->getOnayliIzinGunleriToplu($person_ids, $first_day_ymd, $last_day_ymd);
}

if ($view_mode === 'monthly') {
    $start_date = "$year-$month-01";
    $end_date = date("Y-m-t", strtotime($start_date));
    $all_puantaj_data = $puantajModel->getAllPuantajForPersons($person_ids, $start_date, $end_date);
} else {
    $all_puantaj_data = $puantajModel->getAllPuantajForPersons($person_ids, $selected_date, $selected_date);
}

$all_puantaj_types = $puantajModel->getAllPuantajTurleri();
$date_nodash_global = str_replace('-', '', $selected_date);

// Proje isimlerini indexle (N+1 query'den kurtulmak için)
$project_names_indexed = [];
foreach ($all_projects as $proj) {
    $project_names_indexed[$proj->id] = $proj->project_name;
}

$months = [
    '01' => 'Ocak', '02' => 'Şubat', '03' => 'Mart', '04' => 'Nisan',
    '05' => 'Mayıs', '06' => 'Haziran', '07' => 'Temmuz', '08' => 'Ağustos',
    '09' => 'Eylül', '10' => 'Ekim', '11' => 'Kasım', '12' => 'Aralık'
];
?>

<style>
    /* Swipe to Action Styles */
    .person-item-wrapper {
        position: relative;
        overflow: hidden;
        background: #fff;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        user-select: none;
    }
    body[data-bs-theme="dark"] .person-item-wrapper {
        background: #1e293b !important;
        border-color: rgba(255, 255, 255, 0.05);
    }
    .person-item-actions {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        display: flex;
        align-items: center;
        background: #f1f5f9;
        z-index: 1;
        visibility: hidden; /* Hide by default to prevent flashing */
    }
    body[data-bs-theme="dark"] .person-item-actions {
        background: #1e293b;
    }
    .person-item-content {
        position: relative;
        background: #fff;
        z-index: 2;
        transition: transform 0.2s ease-out;
        width: 100%;
    }
    body[data-bs-theme="dark"] .person-item-content {
        background: #1e293b !important;
    }
    .btn-swipe-clear {
        color: #d63f3f;
        width: 60px;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border: none;
        background: #fef2f2;
        font-size: 0.7rem;
        font-weight: 600;
        transition: all 0.2s;
    }
    body[data-bs-theme="dark"] .btn-swipe-clear {
        background: rgba(214, 63, 63, 0.1);
        color: #ef4444;
    }
    .btn-swipe-clear:active {
        background: #fee2e2;
    }
    .btn-swipe-clear i {
        font-size: 1rem;
        margin-bottom: 2px;
    }

    /* Filtre Select Tweaks */
    #filterModal .form-select {
        height: 52px;
        font-size: 0.88rem;
        border-radius: 12px;
        padding-top: 1.1rem;
    }

    #filterModal .btn-group .btn {
        font-size: 0.78rem;
        font-weight: 500;
        padding: 8px;
        border-radius: 10px !important;
    }
    #filterModal .btn-check:checked + .btn {
        background-color: var(--mobile-primary) !important;
        color: white !important;
        font-weight: 600;
    }

    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .person-row.saved { background-color: rgba(47, 179, 68, 0.04) !important; transition: background 0.3s; }

    /* Floating Select2 Styling */
    .form-floating-select2 {
        position: relative;
        height: 52px;
    }
    .form-floating-select2 .select2-container--default .select2-selection--single {
        height: 52px !important;
        padding-top: 1.1rem !important;
        border-radius: 12px !important;
        border: 1px solid rgba(0,0,0,0.08) !important;
        background-color: #fff !important;
    }
    body[data-bs-theme="dark"] .form-floating-select2 .select2-container--default .select2-selection--single {
        background-color: #1e293b !important;
        border-color: rgba(255,255,255,0.08) !important;
    }
    .form-floating-select2 .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.4 !important;
        padding-left: 12px !important;
        padding-top: 6px !important;
        font-size: 0.88rem !important;
        font-weight: 500 !important;
        color: var(--tblr-body-color) !important;
    }
    .form-floating-select2 .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 52px !important;
    }
    .form-floating-select2 label {
        position: absolute;
        top: 0;
        left: 0;
        z-index: 5;
        height: 100%;
        padding: 0.85rem 0.75rem;
        pointer-events: none;
        transform-origin: 0 0;
        transition: opacity .1s ease-in-out, transform .1s ease-in-out;
        color: rgba(var(--tblr-body-color-rgb), .5);
        font-size: 0.82rem;
        opacity: 1;
        font-weight: 500;
    }
    .form-floating-select2.has-value label,
    .form-floating-select2.is-focused label {
        transform: scale(.8) translateY(-.5rem) translateX(.15rem);
        opacity: .8;
        color: var(--mobile-primary);
    }


    
    /* Option styling */
    .type-option-row {
        border-color: #f1f5f9 !important;
        background-color: #f8fafc;
    }
    .type-option-row:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1 !important;
    }
    .type-option-row.selected {
        background-color: rgba(32, 107, 196, 0.08);
        border-color: var(--mobile-primary) !important;
    }
    .type-option-row.selected .select-check-icon {
        display: block !important;
    }
    .nav-pills .nav-link.active {
        background-color: var(--mobile-primary);
        color: white !important;
    }
    .nav-pills .nav-link {
        color: #64748b;
    }
    .nav-pills .nav-link:hover {
        background-color: #f1f5f9;
    }
    .nav-pills .nav-link.active:hover {
        background-color: var(--mobile-primary);
    }
    
    /* Search Bar Tweaks */
    .search-container {
        position: relative;
    }
    .search-container .search-icon {
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #9299a6;
        font-size: 1.1rem;
    }
    .search-input {
        width: 100%;
        padding: 10px 16px 10px 42px;
        border-radius: 14px;
        border: 1px solid rgba(0,0,0,0.06);
        background-color: #f8fafc;
        outline: none;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }
    .search-input:focus {
        background-color: #ffffff;
        border-color: var(--mobile-primary);
        box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.15);
    }

    /* PREMIUM DARK MODE TWEAKS */
    body[data-bs-theme="dark"] .type-option-row {
        border-color: var(--mobile-card-border-dark) !important;
        background-color: #1e293b;
    }
    body[data-bs-theme="dark"] .type-option-row:hover {
        background-color: #243049;
    }
    body[data-bs-theme="dark"] .type-option-row.selected {
        background-color: rgba(32, 107, 196, 0.15);
        border-color: var(--mobile-primary) !important;
    }
    body[data-bs-theme="dark"] .nav-pills .nav-link {
        color: #94a3b8;
    }
    body[data-bs-theme="dark"] .nav-pills .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.05);
    }
    body[data-bs-theme="dark"] .search-input {
        background-color: #1e293b;
        border-color: var(--mobile-card-border-dark);
        color: #f4f6fa;
    }
    body[data-bs-theme="dark"] .search-input:focus {
        background-color: #1e293b;
        border-color: var(--mobile-primary);
        box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.25);
    }
    body[data-bs-theme="dark"] .text-dark {
        color: #f4f6fa !important;
    }
    body[data-bs-theme="dark"] .avatar-md {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }
    body[data-bs-theme="dark"] .avatar-md {
        background-color: #1e293b !important;
        color: #94a3b8 !important;
    }
    body[data-bs-theme="dark"] .border-end {
        border-color: var(--mobile-card-border-dark) !important;
    }

    /* Checkbox & Selection */
    .person-row.selected {
        background-color: rgba(32, 107, 196, 0.05) !important;
    }
    .person-row.selected .selection-indicator {
        display: block !important;
    }
    .person-row.selected .person-avatar-container {
        display: none !important;
    }
    .selection-indicator {
        margin-right: 8px;
    }

    #bulkActionBar {
        transition: transform 0.3s ease-in-out;
        transform: translateY(0);
        box-shadow: 0 -10px 25px rgba(0,0,0,0.05);
    }
    #bulkActionBar.d-none {
        transform: translateY(100%);
        display: none !important;
    }
    #clearSearchBtn {
        transition: all 0.2s ease;
    }
    #clearSearchBtn:active {
        transform: scale(0.95);
    }

    /* Spinner Animation */
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .loading-spinner-inner {
        width: 18px;
        height: 18px;
        border: 2px solid rgba(0,0,0,0.1);
        border-top-color: var(--mobile-primary);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
    }
    body[data-bs-theme="dark"] .loading-spinner-inner {
        border-color: rgba(255,255,255,0.1);
        border-top-color: var(--mobile-primary);
    }

    /* Aylık Düzenleme Takvimi Stilleri */
    .calendar-day-edit {
        aspect-ratio: 1.1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: #f8fafc;
        border: 1.5px solid rgba(0,0,0,0.03);
        cursor: pointer;
        transition: all 0.2s ease;
        padding: 2px;
    }
    .calendar-day-edit .day-num {
        font-size: 0.65rem;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 1px;
    }
    .calendar-day-edit .day-code {
        font-size: 0.72rem;
        font-weight: 800;
        line-height: 1;
    }
    .calendar-day-edit.empty {
        background: transparent;
        border: none;
        pointer-events: none;
    }
    .calendar-day-edit.active-day {
        border-color: var(--mobile-primary) !important;
        box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.2);
        transform: scale(1.05);
        z-index: 5;
    }
    body[data-bs-theme="dark"] .calendar-day-edit {
        background: #1e293b;
        border-color: rgba(255,255,255,0.05);
    }
    body[data-bs-theme="dark"] .calendar-day-edit .day-num {
        color: #94a3b8;
    }
    body[data-bs-theme="dark"] .calendar-day-edit.active-day {
        border-color: var(--mobile-primary) !important;
        box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.4);
    }

    /* Aylık Modal içi seçim stilleri */
    .m-type-option-row {
        border-color: #f1f5f9 !important;
        background-color: #f8fafc;
    }
    .m-type-option-row:hover {
        background-color: #f1f5f9;
        border-color: #cbd5e1 !important;
    }
    .m-type-option-row.selected {
        background-color: rgba(32, 107, 196, 0.08);
        border-color: var(--mobile-primary) !important;
    }
    .m-type-option-row.selected .m-select-check-icon {
        display: block !important;
    }
    body[data-bs-theme="dark"] .m-type-option-row {
        border-color: var(--mobile-card-border-dark) !important;
        background-color: #1e293b;
    }
    body[data-bs-theme="dark"] .m-type-option-row:hover {
        background-color: #243049;
    }
    body[data-bs-theme="dark"] .m-type-option-row.selected {
        background-color: rgba(32, 107, 196, 0.15);
        border-color: var(--mobile-primary) !important;
    }

    /* Floating View Switcher Button styles */
    .fab-switch-view {
        box-shadow: 0 8px 16px rgba(32, 107, 196, 0.25) !important;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
    }
    .fab-switch-view:active {
        transform: scale(0.9) translateY(2px);
        box-shadow: 0 4px 8px rgba(32, 107, 196, 0.15) !important;
    }
    body.selection-active .fab-switch-view {
        transform: translateY(-60px); /* shift up when selection mode is active to avoid overlapping bar */
    }
    
    /* Calendar day selected for bulk assignment style */
    .calendar-day-edit.selected-for-bulk {
        border: 2px solid var(--mobile-primary) !important;
        background-color: rgba(32, 107, 196, 0.12) !important;
        position: relative;
    }
    .calendar-day-edit.selected-for-bulk::after {
        content: '\eab5'; /* Checkmark icon using Tabler font */
        font-family: 'tabler-icons' !important;
        position: absolute;
        bottom: 2px;
        right: 4px;
        font-size: 0.62rem;
        font-weight: bold;
        color: var(--mobile-primary);
    }
</style>

<div class="container px-0">
    <div class="mb-2">
        <?php 
        $base_params = $_GET;
        unset($base_params['date']);
        unset($base_params['view']);
        unset($base_params['month']);
        unset($base_params['year']);
        $query_str = http_build_query($base_params);
        $prev_url = "puantaj?date=$prev_date" . ($query_str ? "&$query_str" : "");
        $next_url = "puantaj?date=$next_date" . ($query_str ? "&$query_str" : "");
        $today_url = "puantaj?date=" . date('Y-m-d') . ($query_str ? "&$query_str" : "");
        $yesterday_url = "puantaj?date=" . date('Y-m-d', strtotime('-1 day')) . ($query_str ? "&$query_str" : "");
        ?>
        <div class="d-flex align-items-center justify-content-between mb-2 px-2">
            <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Hızlı Puantaj</h2>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-2">
            <?php if ($view_mode === 'daily'): ?>
                <!-- Günlük Görünüm Navigasyon & Butonları -->
                <div class="d-flex gap-2 overflow-auto pb-1 no-scrollbar flex-grow-1" style="max-width: calc(100% - 150px);">
                    <button class="btn btn-sm btn-pill <?php echo $selected_date == date('Y-m-d') ? 'btn-primary' : 'btn-outline-primary'; ?>" 
                            onclick="location.href='<?php echo $today_url; ?>'">Bugün</button>
                    <button class="btn btn-sm btn-pill <?php echo $selected_date == date('Y-m-d', strtotime('-1 day')) ? 'btn-primary' : 'btn-outline-primary'; ?>"
                            onclick="location.href='<?php echo $yesterday_url; ?>'">Dün</button>
                    <button class="btn btn-sm btn-pill btn-outline-secondary" onclick="openBulkPuantajModal()">Tümünü işaretle</button>
                    <button class="btn btn-sm btn-icon <?php echo $isFiltered ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill" data-bs-toggle="modal" data-bs-target="#filterModal" style="width: 32px; height: 32px; min-height: auto !important; flex-shrink: 0;">
                        <i class="ti ti-filter fs-3"></i>
                    </button>
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <a href="<?php echo $prev_url; ?>" class="btn btn-icon bg-secondary-lt border-0 text-secondary rounded-3 p-0" style="width: 34px; height: 34px; min-height: auto !important; display: flex; align-items: center; justify-content: center;" title="Önceki Gün">
                        <i class="ti ti-chevron-left fs-3"></i>
                    </a>
                    <div class="position-relative d-inline-block">
                        <input type="text" id="datePicker" class="form-control form-control-sm border-0 bg-secondary-lt text-bold text-center" 
                                 value="<?php echo date('d.m.Y', strtotime($selected_date)); ?>" 
                                 style="width: 100px; height: 34px; border-radius: 10px; cursor: pointer; padding-right: 1.6rem; font-size: 0.82rem; color: #1d273b !important; min-height: auto !important;">
                         <i class="ti ti-calendar position-absolute text-muted" style="right: 6px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 0.85rem;"></i>
                    </div>
                    <?php if (!$is_today_or_future): ?>
                        <a href="<?php echo $next_url; ?>" class="btn btn-icon bg-secondary-lt border-0 text-secondary rounded-3 p-0" style="width: 34px; height: 34px; min-height: auto !important; display: flex; align-items: center; justify-content: center;" title="Sonraki Gün">
                             <i class="ti ti-chevron-right fs-3"></i>
                        </a>
                    <?php else: ?>
                        <button class="btn btn-icon bg-secondary-lt border-0 text-secondary rounded-3 p-0 disabled" style="width: 34px; height: 34px; min-height: auto !important; opacity: 0.3; display: flex; align-items: center; justify-content: center;" disabled>
                            <i class="ti ti-chevron-right fs-3"></i>
                        </button>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Aylık Görünüm Navigasyon & Butonları -->
                <div class="d-flex align-items-center justify-content-between w-100 px-2">
                    <div>
                        <button class="btn btn-sm btn-icon <?php echo $isFiltered ? 'btn-primary' : 'btn-outline-secondary'; ?> rounded-pill" data-bs-toggle="modal" data-bs-target="#filterModal" style="width: 32px; height: 32px; min-height: auto !important;">
                            <i class="ti ti-filter fs-3"></i>
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-1">
                        <?php 
                        $prev_month_url = "puantaj?view=monthly&month=$prev_month_val&year=$prev_month_year" . ($query_str ? "&$query_str" : "");
                        $next_month_url = "puantaj?view=monthly&month=$next_month_val&year=$next_month_year" . ($query_str ? "&$query_str" : "");
                        ?>
                        <a href="<?php echo $prev_month_url; ?>" class="btn btn-icon bg-secondary-lt border-0 text-secondary rounded-3 p-0" style="width: 34px; height: 34px; min-height: auto !important; display: flex; align-items: center; justify-content: center;" title="Önceki Ay">
                            <i class="ti ti-chevron-left fs-3"></i>
                        </a>
                        <div class="d-flex gap-1">
                            <select id="monthSelector" class="form-select form-select-sm border-0 bg-secondary-lt text-bold text-center" style="width: 110px; height: 34px; border-radius: 10px; font-size: 0.82rem; color: #1d273b !important; padding: 0 8px; min-height: auto !important;">
                                <?php foreach ($months as $m => $name): ?>
                                    <option value="<?php echo $m; ?>" <?php echo $m == $month ? 'selected' : ''; ?>><?php echo $name; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select id="yearSelector" class="form-select form-select-sm border-0 bg-secondary-lt text-bold text-center" style="width: 80px; height: 34px; border-radius: 10px; font-size: 0.82rem; color: #1d273b !important; padding: 0 8px; min-height: auto !important;">
                                <?php for ($y = date('Y') - 5; $y <= date('Y') + 2; $y++): ?>
                                    <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <a href="<?php echo $next_month_url; ?>" class="btn btn-icon bg-secondary-lt border-0 text-secondary rounded-3 p-0" style="width: 34px; height: 34px; min-height: auto !important; display: flex; align-items: center; justify-content: center;" title="Sonraki Ay">
                            <i class="ti ti-chevron-right fs-3"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

    <!-- Hafta Sonu Bilgilendirmesi -->
    <?php 
    $day_num = date('N', strtotime($selected_date));
    $is_weekend = ($day_num >= 6); // 6: Cumartesi, 7: Pazar
    $day_name = Date::gunadi($selected_date);
    if ($is_weekend): 
    ?>
        <div class="alert alert-warning border-0 rounded-3 mb-2 d-flex align-items-center gap-2 py-2 px-3" style="background-color: rgba(245, 158, 11, 0.1); color: #d97706; font-size: 0.82rem; font-weight: 500;">
            <i class="ti ti-info-circle fs-3"></i>
            <span>Seçili gün hafta sonudur (<strong><?php echo $day_name; ?></strong>).</span>
        </div>
    <?php endif; ?>

    <!-- Arama Çubuğu -->
    <div class="d-flex align-items-center gap-2 mb-2">
        <button id="clearSearchBtn" class="btn btn-icon btn-outline-secondary border-0 bg-secondary-lt d-none" style="border-radius: 14px; height: 44px; width: 44px; flex-shrink: 0;" title="Temizle">
            <i class="ti ti-trash-x"></i>
        </button>
        <div class="search-container flex-grow-1">
            <i class="ti ti-search search-icon"></i>
            <input type="text" id="puantajSearchInput" class="search-input" placeholder="Personel ara...">
        </div>
    </div>

    <div class="list-group list-group-mobile mb-5" id="puantajListContainer">
        <?php foreach ($persons as $person): 
            // Collar Type Filtreleme (Model 'include' mantığında çalıştığı için burada net filtreleme yapıyoruz)
            if ($selected_collar_type == 'blue' && $person->wage_type != 2) continue;
            if ($selected_collar_type == 'white' && $person->wage_type != 1) continue;

            // İş başlama ve ayrılış tarihlerine göre filtreleme
            $start_dt = !empty($person->job_start_date) ? date('Y-m-d', strtotime($person->job_start_date)) : null;
            $end_dt = !empty($person->job_end_date) ? date('Y-m-d', strtotime($person->job_end_date)) : null;
            
            if ($start_dt && $selected_date < $start_dt) continue;
            if ($end_dt && $selected_date > $end_dt) continue;

            // Veri çekme mantığını esnetiyoruz: Hem tireli hem tiresiz formatı kontrol et
            $date_dash = $selected_date; // 2026-05-08
            $date_nodash = str_replace('-', '', $selected_date); // 20260508
            
            $stats = [];
            if ($view_mode === 'monthly') {
                if (isset($all_puantaj_data[$person->id])) {
                    foreach ($all_puantaj_data[$person->id] as $p_row) {
                        $type = $all_puantaj_types[$p_row->puantaj_id] ?? null;
                        if ($type) {
                            $cat = $type->Turu;
                            $color = $type->ArkaPlanRengi;
                            $textColor = $type->FontRengi;
                            
                            // Kısaltma oluştur (Normal Çalışma -> NÇ)
                            $words = explode(' ', $cat);
                            $short = '';
                            foreach($words as $w) {
                                if (!empty($w)) $short .= mb_substr($w, 0, 1, 'UTF-8');
                            }
                            
                            if (!isset($stats[$cat])) {
                                $stats[$cat] = (object)[
                                    'count' => 0,
                                    'short' => $short,
                                    'color' => $color,
                                    'textColor' => $textColor
                                ];
                            }
                            $stats[$cat]->count++;
                        }
                    }
                }
                
                // Önem sırasına göre sırala
                uksort($stats, function($a, $b) {
                    if ($a == 'Normal Çalışma') return -1;
                    if ($b == 'Normal Çalışma') return 1;
                    return strcmp($a, $b);
                });
            } else {
                // TOPLU VERİDEN ÇEK (Eski N+1 metodları yerine)
                $person_puantaj = $all_puantaj_data[$person->id][$date_nodash] ?? null;
                
                $current_status_id = $person_puantaj->puantaj_id ?? '';
                $puantaj_project_id = $person_puantaj->project_id ?? 0;

                // Hafta sonu (Pazar) HT otomatik gösterme (Sadece hiç kayıt yoksa)
                if (empty($current_status_id) && Date::isWeekend($selected_date)) {
                    $current_status_id = 53; // HT ID
                }
                
                $is_disabled = false;
                $disabled_project_name = '';
                if ($selected_project_id > 0 && $puantaj_project_id > 0 && $puantaj_project_id != $selected_project_id) {
                    $is_disabled = true;
                    $disabled_project_name = $project_names_indexed[$puantaj_project_id] ?? 'Bilinmeyen Proje';
                }

                $current_type = null;
                if (!empty($current_status_id)) {
                    $current_type = $all_puantaj_types[$current_status_id] ?? null;
                }

                // Onaylı izin kontrolü
                $has_onayli_izin = isset($onayliIzinGunleri[$person->id][$selected_date]);
                if ($has_onayli_izin) {
                    $izinBilgi = $onayliIzinGunleri[$person->id][$selected_date];
                    $is_disabled = true;
                    $disabled_project_name = 'Onaylı İzin';
                    $current_status_id = $izinBilgi->puantaj_turu_id;
                    $current_type = (object)[
                        'ArkaPlanRengi' => $izinBilgi->arkaplan,
                        'FontRengi' => $izinBilgi->font,
                        'PuantajKod' => $izinBilgi->kod,
                        'Turu' => $izinBilgi->turu
                    ];
                }
            }
        ?>
            <div class="person-item-wrapper" data-name="<?php echo mb_strtolower($person->full_name, 'UTF-8'); ?>">
                <div class="person-item-actions">
                    <button class="btn-swipe-clear" onclick="clearPuantaj('<?php echo $person->id; ?>', '<?php echo htmlspecialchars($person->full_name); ?>')" <?php echo ($view_mode === 'daily' && $is_disabled) ? 'disabled style="opacity: 0.5;"' : ''; ?>>
                        <i class="ti ti-rotate-clockwise-2"></i>
                        <span>Temizle</span>
                    </button>
                </div>
                <div class="person-item-content">
                    <div class="list-group-item list-group-item-action py-2.5 person-row cursor-pointer d-flex align-items-center justify-content-between" 
                         role="button"
                         tabindex="0"
                         data-person-id="<?php echo $person->id; ?>" 
                         data-person-key="<?php echo Security::encrypt($person->id); ?>"
                         data-person-name="<?php echo htmlspecialchars($person->full_name); ?>"
                         data-current-type-id="<?php echo ($view_mode === 'daily') ? $current_status_id : ''; ?>"
                         data-name="<?php echo mb_strtolower($person->full_name, 'UTF-8'); ?>"
                         data-is-disabled="<?php echo ($view_mode === 'daily' && $is_disabled) ? 'true' : 'false'; ?>"
                         data-disabled-project-name="<?php echo ($view_mode === 'daily') ? htmlspecialchars($disabled_project_name) : ''; ?>"
                         style="gap: 12px; border-radius: 0; border: none; <?php echo ($view_mode === 'daily' && $is_disabled) ? 'opacity: 0.7; background-color: rgba(241, 245, 249, 0.4); pointer-events: auto;' : ''; ?>">
                        <div class="d-flex align-items-center gap-2">
                            <div class="selection-indicator d-none">
                                <input class="form-check-input m-0" type="checkbox" style="width: 22px; height: 22px; border-radius: 6px; border: 2px solid #cbd5e1; pointer-events: none;">
                            </div>
                            <div class="person-avatar-container">
                                <!-- Avatar or initials could go here if needed, but keeping it clean like screenshot -->
                            </div>
                        </div>
                        <div style="min-width: 0; flex: 1;">
                            <div class="text-semibold text-dark mb-0" style="font-size: 0.92rem; letter-spacing: -0.2px; line-height: 1.2;">
                                <?php echo htmlspecialchars($person->full_name); ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem; opacity: 0.7; font-weight: 500; margin-top: 2px;">
                                <?php if ($view_mode === 'daily' && $is_disabled): ?>
                                    <span class="text-danger" style="font-weight: 600;"><i class="ti ti-lock me-1"></i><?php echo htmlspecialchars($disabled_project_name); ?> (Kilitli)</span>
                                <?php else: ?>
                                    <?php 
                                    if ($view_mode === 'daily' && $puantaj_project_id > 0 && $selected_project_id == 0) {
                                        $proj_name = $project_names_indexed[$puantaj_project_id] ?? 'Bilinmeyen Proje';
                                        echo '<span class="text-primary" style="font-weight: 600;"><i class="ti ti-subtask me-1"></i>' . htmlspecialchars($proj_name) . '</span>';
                                    } else {
                                        echo !empty($person->job) ? htmlspecialchars($person->job) : 'Görev eklenmedi'; 
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Sağ Taraf: Minimal Badge (Günlük) veya İstatistikler (Aylık) -->
                        <div style="flex-shrink: 0;">
                            <?php if ($view_mode === 'daily'): ?>
                                <?php if ($current_type): ?>
                                    <div id="status-badge-<?php echo $person->id; ?>" class="avatar avatar-sm rounded-circle font-weight-bold" 
                                         style="background-color: <?php echo htmlspecialchars($current_type->ArkaPlanRengi); ?>; color: <?php echo htmlspecialchars($current_type->FontRengi); ?>; width: 36px; height: 36px; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.06); text-transform: uppercase; border: 1.5px solid rgba(255,255,255,0.2);">
                                        <?php echo htmlspecialchars($current_type->PuantajKod); ?>
                                    </div>
                                <?php else: ?>
                                    <div id="status-badge-<?php echo $person->id; ?>" class="avatar avatar-sm rounded-circle" 
                                         style="background-color: #f8fafc; color: #94a3b8; width: 36px; height: 36px; font-size: 0.8rem; display: flex; align-items: center; justify-content: center; border: 1px dashed #e2e8f0; text-transform: uppercase;">
                                        -
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="d-flex gap-1 flex-wrap justify-content-end align-items-center" style="max-width: 150px;" id="monthly-stats-<?php echo $person->id; ?>">
                                  <?php 
                                  $limit = 3;
                                  $i = 0;
                                  foreach ($stats as $catName => $stat): 
                                      if ($i >= $limit) break;
                                      if ($stat->count == 0) continue;
                                  ?>
                                    <div class="text-center px-1.5 py-1" style="min-width: 33px; background-color: <?php echo $stat->color; ?>18; border: 1px solid <?php echo $stat->color; ?>38; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
                                      <div class="fw-bold mb-0" style="font-size: 0.78rem; color: <?php echo $stat->color; ?>; line-height: 1.1;"><?php echo $stat->count; ?></div>
                                      <div class="fw-bold" style="font-size: 0.62rem; color: <?php echo $stat->color; ?>; opacity: 0.9; line-height: 1; margin-top: 1px; letter-spacing: 0.2px; text-transform: uppercase;"><?php echo $stat->short; ?></div>
                                    </div>
                                  <?php 
                                    $i++;
                                  endforeach; 
                                  if (empty($stats)):
                                  ?>
                                    <span class="text-muted text-xs" style="font-size: 0.75rem;">Giriş yok</span>
                                  <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Floating View Mode Switcher -->
<?php if ($view_mode === 'daily'): ?>
    <a href="puantaj?view=monthly<?php echo $query_str ? "&$query_str" : ""; ?>" class="fab-switch-view btn btn-primary btn-icon rounded-circle shadow-lg" style="position: fixed; bottom: 75px; right: 20px; width: 52px; height: 52px; z-index: 1040; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255,255,255,0.2);" title="Aylık Görünüm">
        <i class="ti ti-calendar-stats" style="font-size: 1.55rem;"></i>
    </a>
<?php else: ?>
    <a href="puantaj?view=daily<?php echo $query_str ? "&$query_str" : ""; ?>" class="fab-switch-view btn btn-primary btn-icon rounded-circle shadow-lg" style="position: fixed; bottom: 75px; right: 20px; width: 52px; height: 52px; z-index: 1040; display: flex; align-items: center; justify-content: center; border: 2px solid rgba(255,255,255,0.2);" title="Günlük Görünüm">
        <i class="ti ti-calendar-event" style="font-size: 1.55rem;"></i>
    </a>
<?php endif; ?>

<!-- Toplu İşlem Barı -->
<div id="bulkActionBar" class="fixed-bottom bg-white shadow-lg p-3 d-none" style="border-radius: 24px 24px 0 0; z-index: 1050; border-top: 1px solid rgba(0,0,0,0.05);">
    <div class="d-flex align-items-center justify-content-between container">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-icon btn-sm btn-ghost-danger rounded-circle" onclick="cancelSelection()">
                <i class="ti ti-x"></i>
            </button>
            <span class="text-bold text-dark" id="selectedCountText">0 kişi seçildi</span>
        </div>
        <button class="btn btn-primary px-4 py-2" style="border-radius: 12px;" onclick="openBulkPuantajModal(true)">
            <i class="ti ti-check me-1"></i> Toplu Ata
        </button>
    </div>
</div>

<!-- Puantaj Seçim Modalı -->
<div class="modal modal-blur fade" id="puantajModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title font-weight-bold text-dark mb-1" id="modalPersonName" style="font-size: 1.15rem;">Personel Adı</h5>
                    <p class="text-muted text-xs mb-0" id="puantajModalDateSubtitle" style="font-weight: 500;">
                        <i class="ti ti-calendar me-1"></i><?php echo date('d.m.Y', strtotime($selected_date)); ?> Tarihli Puantaj Girişi
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body py-4">
                <div class="row h-100 g-0">
                    <!-- Sol Liste: Kategoriler -->
                    <div class="col-4 border-end pe-2" style="max-height: 380px; overflow-y: auto;">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <?php 
                            $has_normal_calisma = array_key_exists('Normal Çalışma', $grouped_types);
                            $is_first = true;
                            foreach ($grouped_types as $category => $items): 
                                $cat_id = md5($category);
                                $is_active = $has_normal_calisma ? ($category === 'Normal Çalışma') : $is_first;
                            ?>
                                <button class="nav-link text-start text-xs font-weight-bold py-2 px-3 mb-1 text-truncate <?php echo $is_active ? 'active' : ''; ?>" 
                                        id="v-pills-<?php echo $cat_id; ?>-tab" 
                                        data-bs-toggle="pill" 
                                        data-bs-target="#v-pills-<?php echo $cat_id; ?>" 
                                        type="button" 
                                        role="tab" 
                                        aria-controls="v-pills-<?php echo $cat_id; ?>" 
                                        aria-selected="<?php echo $is_active ? 'true' : 'false'; ?>"
                                        style="border-radius: 12px; font-size: 0.8rem; transition: all 0.2s;">
                                    <?php echo htmlspecialchars($category); ?>
                                </button>
                            <?php 
                                $is_first = false;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                    <!-- Sağ Liste: Elemanlar -->
                    <div class="col-8 ps-3" style="max-height: 380px; overflow-y: auto;">
                        <div class="tab-content" id="v-pills-tabContent">
                            <?php 
                            $is_first = true;
                            foreach ($grouped_types as $category => $items): 
                                $cat_id = md5($category);
                                $is_active = $has_normal_calisma ? ($category === 'Normal Çalışma') : $is_first;
                            ?>
                                <div class="tab-pane fade <?php echo $is_active ? 'show active' : ''; ?>" 
                                     id="v-pills-<?php echo $cat_id; ?>" 
                                     role="tabpanel" 
                                     aria-labelledby="v-pills-<?php echo $cat_id; ?>-tab">
                                    <div class="d-flex flex-column gap-2">
                                        <?php foreach ($items as $type): ?>
                                            <div class="d-flex align-items-center justify-content-between p-2.5 border rounded-3 position-relative cursor-pointer type-option-row" 
                                                 role="button"
                                                 tabindex="0"
                                                 data-type-id="<?php echo $type->id; ?>"
                                                 data-type-code="<?php echo htmlspecialchars($type->PuantajKod); ?>"
                                                 data-type-label="<?php echo htmlspecialchars($type->PuantajAdi); ?>"
                                                 data-type-color="<?php echo htmlspecialchars($type->ArkaPlanRengi); ?>"
                                                 data-type-text-color="<?php echo htmlspecialchars($type->FontRengi); ?>"
                                                 style="border-radius: 14px; transition: all 0.2s ease;">
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="avatar avatar-sm font-weight-bold" 
                                                          style="background-color: <?php echo htmlspecialchars($type->ArkaPlanRengi); ?>; color: <?php echo htmlspecialchars($type->FontRengi); ?>; border-radius: 10px; width: 36px; height: 36px; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                                        <?php echo htmlspecialchars($type->PuantajKod); ?>
                                                    </span>
                                                    <div>
                                                        <div class="text-bold text-sm text-dark"><?php echo htmlspecialchars($type->PuantajAdi); ?></div>
                                                        <div class="text-muted text-xs"><?php echo htmlspecialchars($type->Turu); ?></div>
                                                    </div>
                                                </div>
                                                <i class="ti ti-circle-check text-primary fs-2 d-none select-check-icon"></i>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php 
                                $is_first = false;
                            endforeach; 
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between w-100">
                <button type="button" class="btn btn-link text-muted px-0 text-decoration-none text-xs font-weight-bold" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-outline-danger btn-sm d-none" id="btnMonthlyModalClearDay" style="border-radius: 10px; font-weight: 600; font-size: 0.75rem;" onclick="clearActiveCalendarDay()">Temizle</button>
            </div>
        </div>
    </div>
</div>

<!-- Filtre Modalı -->
<div class="modal modal-blur fade" id="filterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold">Filtrele</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="form-floating mb-3 form-floating-select2">
                        <select name="project_id" class="form-select border-0 bg-secondary-lt" id="project_id" style="border-radius: 12px;">
                            <option value="0" <?php echo ($selected_project_id == 0) ? 'selected' : ''; ?>>Tüm Projeler</option>
                            <?php foreach ($all_projects as $proj): ?>
                                <option value="<?php echo $proj->id; ?>" <?php echo ($selected_project_id == $proj->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proj->project_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="project_id">PROJE</label>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="text-muted font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">PERSONEL TİPİ</label>
                        </div>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="collar_type" id="collar_all" value="all" <?php echo $selected_collar_type == 'all' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="collar_all">Hepsi</label>

                            <input type="radio" class="btn-check" name="collar_type" id="collar_blue" value="blue" <?php echo $selected_collar_type == 'blue' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="collar_blue">Mavi Yaka</label>

                            <input type="radio" class="btn-check" name="collar_type" id="collar_white" value="white" <?php echo $selected_collar_type == 'white' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="collar_white">Beyaz Yaka</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="text-muted font-weight-bold" style="font-size: 0.7rem; letter-spacing: 0.05em;">PERSONEL DURUMU</label>
                        </div>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="person_status" id="status_active" value="active" <?php echo $selected_person_status == 'active' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="status_active"><i class="ti ti-user-check me-1"></i> Aktif</label>

                            <input type="radio" class="btn-check" name="person_status" id="status_passive" value="passive" <?php echo $selected_person_status == 'passive' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="status_passive"><i class="ti ti-user-x me-1"></i> Pasif</label>

                            <input type="radio" class="btn-check" name="person_status" id="status_all" value="all" <?php echo $selected_person_status == 'all' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="status_all"><i class="ti ti-users me-1"></i> Tümü</label>
                        </div>
                    </div>


                    <div class="form-floating mb-3 form-floating-select2">
                        <?php echo $jobsHelper->jobGroupsSelect('job_group', $selected_job_group); ?>
                        <label for="job_group">İŞ GRUBU / GÖREV</label>
                    </div>

                    <div class="form-floating mb-3 form-floating-select2">
                        <?php echo $teamsHelper->teamsSelect('team_id', $selected_team_id); ?>
                        <label for="team_id">EKİBİ</label>
                    </div>

                    <div class="mt-4">
                        <button type="button" class="btn btn-primary w-100 py-2" onclick="applyFilters()" style="border-radius: 12px; font-weight: 600; font-size: 0.9rem;">Filtreleri Uygula</button>
                        <button type="button" class="btn btn-link w-100 mt-1 text-muted text-decoration-none" onclick="clearFilters()" style="font-size: 0.75rem;">Seçimleri Temizle</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<!-- Aylık İnteraktif Takvim Düzenleme Modalı -->
<div class="modal modal-blur fade" id="monthlyPuantajModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header border-0 pb-0 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="modal-title font-weight-bold" id="monthlyModalTitle">Aylık Puantaj</h5>
                    <p class="text-muted text-xs mb-0" id="monthlyModalSubtitle" style="font-weight: 500;">
                        Personel takvimi düzenleniyor
                    </p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body py-3 pb-1">
                <!-- Takvim Grid'i -->
                <div id="monthlyCalendarGrid" class="d-grid mb-1" style="grid-template-columns: repeat(7, 1fr); gap: 4px;">
                    <!-- Gün başlıkları ve günler JS ile doldurulacak -->
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between align-items-center w-100">
                <div id="monthlyModalNormalFooter" class="w-100 d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-link text-muted px-0 text-decoration-none text-xs font-weight-bold" data-bs-dismiss="modal">Kapat</button>
                    <small class="text-muted" style="font-size: 0.68rem; font-weight: 500; opacity: 0.85;">Seçmek için günlere dokunun. Çoklu seçim için basılı tutun.</small>
                </div>
                <div id="monthlyModalBulkFooter" class="w-100 d-none justify-content-between align-items-center">
                    <span class="text-bold text-dark text-xs" id="monthlySelectedDaysCount" style="font-weight: 600;">0 gün seçildi</span>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-xs btn-outline-secondary" onclick="cancelMonthlyDaySelection()" style="border-radius: 8px; font-weight: 600; font-size: 0.72rem; padding: 4px 10px;">Vazgeç</button>
                        <button type="button" class="btn btn-xs btn-primary shadow-sm" onclick="openMonthlyBulkTypeSelector()" style="border-radius: 8px; font-weight: 600; font-size: 0.72rem; padding: 4px 10px;">Toplu Ata</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// jQuery'nin $ olarak tanımlandığından emin olalım
if (typeof $ === 'undefined' && typeof jQuery !== 'undefined') {
    var $ = jQuery;
}

document.addEventListener('DOMContentLoaded', function() {
    // Move modals to body to prevent backdrop stacking context issues on iOS/mobile
    $('#puantajModal, #filterModal, #monthlyPuantajModal').appendTo('body');
    $('.fab-switch-view').appendTo('body');

    // Re-open monthly calendar modal when daily type selector is closed
    document.getElementById('puantajModal').addEventListener('hidden.bs.modal', function () {
        if (isMonthlyCalendarMode) {
            const monthlyModal = bootstrap.Modal.getOrCreateInstance(document.getElementById('monthlyPuantajModal'));
            monthlyModal.show();
            isMonthlyCalendarMode = false;
        }
    });

    // Month/Year Selector change handler
    const monthSelector = document.getElementById('monthSelector');
    const yearSelector = document.getElementById('yearSelector');
    if (monthSelector && yearSelector) {
        const handleMonthYearChange = function() {
            const m = monthSelector.value;
            const y = yearSelector.value;
            const url = new URL(window.location.href);
            url.searchParams.set('view', 'monthly');
            url.searchParams.set('month', m);
            url.searchParams.set('year', y);
            location.href = url.toString();
        };
        monthSelector.addEventListener('change', handleMonthYearChange);
        yearSelector.addEventListener('change', handleMonthYearChange);
    }

    // Search Filtering
    const searchInput = document.getElementById('puantajSearchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const items = document.querySelectorAll('.person-item-wrapper');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            
            if (term.length > 0) {
                clearSearchBtn.classList.remove('d-none');
            } else {
                clearSearchBtn.classList.add('d-none');
            }

            items.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(term)) {
                    item.style.setProperty('display', 'block', 'important');
                } else {
                    item.style.setProperty('display', 'none', 'important');
                }
            });
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });
    }

    // Long Press & Row Click
    let longPressTimer;
    $('.person-row').on('touchstart', function(e) {
        if ($(this).attr('data-is-disabled') === 'true') return;
        
        longPressTimer = setTimeout(() => {
            if (!isSelectionMode) {
                startSelectionMode($(this));
            }
        }, 600);
    }).on('touchend touchmove', function() {
        clearTimeout(longPressTimer);
    });

    // Explicit row click handler for iOS and all devices
    $(document).on('click', '.person-row', function(e) {
        if ($(this).attr('data-is-disabled') === 'true') {
            const disabledProjectName = $(this).attr('data-disabled-project-name') || 'Bilinmeyen Proje';
            let alertText = `Bu personelin bu tarihteki puantajı başka bir projede (${disabledProjectName}) girilmiştir. Değiştirilemez.`;
            if (disabledProjectName === 'Onaylı İzin') {
                alertText = 'Bu personelin bu tarihte onaylı bir izin talebi bulunmaktadır. Değiştirilemez.';
            }
            Swal.fire({
                icon: 'info',
                title: 'Puantaj Kilitli',
                text: alertText,
                confirmButtonText: 'Tamam'
            });
        } else {
            handleRowClick(this);
        }
    });

    // Explicit type option selection click handler
    $(document).on('click', '.type-option-row', function(e) {
        selectTypeOption(this);
    });

    // Explicit category pill tab selection with manual fallback for iOS Safari compatibility
    $(document).on('click', '[data-bs-toggle="pill"]', function(e) {
        e.preventDefault();
        try {
            const tab = bootstrap.Tab.getOrCreateInstance(this);
            tab.show();
        } catch (err) {
            // Manual fallback if bootstrap class fails or fails to initialize
            const target = $(this).attr('data-bs-target') || $(this).attr('href');
            if (target) {
                const parentTabContainer = $(this).closest('.nav-pills');
                const targetContentContainer = $(target).closest('.tab-content');
                
                parentTabContainer.find('.nav-link').removeClass('active').attr('aria-selected', 'false');
                targetContentContainer.find('.tab-pane').removeClass('show active');
                
                $(this).addClass('active').attr('aria-selected', 'true');
                $(target).addClass('show active');
            }
        }
    });

    // Flatpickr initialization
    flatpickr("#datePicker", {
        dateFormat: "d.m.Y",
        defaultDate: "<?php echo date('d.m.Y', strtotime($selected_date)); ?>",
        maxDate: "today",
        locale: "tr",
        disableMobile: "true",
        onChange: function(selectedDates, dateStr, instance) {
            const dateParts = dateStr.split(".");
            const ymdDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
            const url = new URL(window.location.href);
            url.searchParams.set('date', ymdDate);
            location.href = url.toString();
        }
    });

    // Swipe logic (Defer CSS/style changes until threshold is exceeded)
    let touchStartX = 0;
    let touchMoveX = 0;
    let touchStartY = 0;
    let touchMoveY = 0;
    let currentSwipeItem = null;
    let isSwipeMoving = false;
    const swipeThreshold = 60;

    $(document).on('touchstart', '.person-item-content', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
        touchStartY = e.originalEvent.touches[0].clientY;
        touchMoveX = touchStartX;
        touchMoveY = touchStartY;
        currentSwipeItem = $(this);
        isSwipeMoving = false;
    });

    $(document).on('touchmove', '.person-item-content', function(e) {
        touchMoveX = e.originalEvent.touches[0].clientX;
        touchMoveY = e.originalEvent.touches[0].clientY;
        let diffX = touchStartX - touchMoveX;
        let diffY = Math.abs(touchStartY - touchMoveY);

        // Defer style changes until horizontal drag threshold is exceeded
        if (!isSwipeMoving && Math.abs(diffX) > 8 && diffY < 10) {
            isSwipeMoving = true;
            // Close other open swipe actions
            $('.person-item-content').not(currentSwipeItem).css('transform', 'translateX(0)');
            $('.person-item-actions').not(currentSwipeItem.siblings('.person-item-actions')).css('visibility', 'hidden');
            
            // Make current actions visible
            currentSwipeItem.siblings('.person-item-actions').css('visibility', 'visible');
        }

        if (isSwipeMoving) {
            if (diffX > 0) {
                if (diffX > swipeThreshold + 20) diffX = swipeThreshold + 20;
                $(this).css('transition', 'none');
                $(this).css('transform', 'translateX(-' + diffX + 'px)');
            } else {
                $(this).css('transform', 'translateX(0)');
            }
        }
    });

    $(document).on('touchend', '.person-item-content', function(e) {
        if (isSwipeMoving) {
            let diffX = touchStartX - touchMoveX;
            $(this).css('transition', 'transform 0.2s ease-out');
            if (diffX > swipeThreshold / 2) {
                $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
            } else {
                $(this).css('transform', 'translateX(0)');
                setTimeout(() => {
                    $(this).siblings('.person-item-actions').css('visibility', 'hidden');
                }, 200);
            }
        }
    });

    $(document).on('touchstart', function(e) {
        if (!$(e.target).closest('.person-item-wrapper').length) {
            $('.person-item-content').css('transform', 'translateX(0)');
            setTimeout(() => {
                $('.person-item-actions').css('visibility', 'hidden');
            }, 200);
        }
    });
});

function clearPuantaj(personId, personName) {
    const serverDate = '<?php echo $selected_date; ?>';
    const badge = document.getElementById(`status-badge-${personId}`);
    if (!badge) return;
    const originalContent = badge.outerHTML;
    
    // Anlık geri bildirim
    badge.innerHTML = '<div class="loading-spinner-inner"></div>';
    badge.style.backgroundColor = '#f1f5f9';
    badge.style.color = 'transparent';

    jQuery.ajax({
        url: 'modules/puantaj/api/puantaj-delete.php',
        method: 'POST',
        data: {
            person_id: personId,
            date: serverDate,
            project_id: <?php echo (int)($selected_project_id ?: -1); ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' || response.status === 'info') {
                // UI Güncelleme
                badge.style.backgroundColor = '#f8fafc';
                badge.style.color = '#94a3b8';
                badge.className = "avatar avatar-sm rounded-circle";
                badge.innerText = "-";
                badge.style.border = "1px dashed #e2e8f0";
                
                const row = document.querySelector(`.person-row[data-person-id="${personId}"]`);
                if (row) row.setAttribute('data-current-type-id', '');
                
                // Kaydırmayı kapat
                $('.person-item-content').css('transform', 'translateX(0)');
                setTimeout(() => {
                    $('.person-item-actions').css('visibility', 'hidden');
                }, 200);
            } else {
                badge.outerHTML = originalContent;
                Swal.fire('Hata', response.message, 'error');
                $('.person-item-content').css('transform', 'translateX(0)');
            }
        },
        error: function() {
            badge.outerHTML = originalContent;
            Swal.fire('Hata', 'Bağlantı hatası oluştu.', 'error');
            $('.person-item-content').css('transform', 'translateX(0)');
        }
    });
}

function applyFilters() {
    const form = document.getElementById('filterForm');
    const formData = new FormData(form);
    const url = new URL(window.location.href);
    
    url.searchParams.set('project_id', formData.get('project_id'));
    url.searchParams.set('job_group', formData.get('job_group'));
    url.searchParams.set('team_id', formData.get('team_id'));
    url.searchParams.set('collar_type', formData.get('collar_type'));
    url.searchParams.set('person_status', formData.get('person_status'));
    
    location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location.href);
    const date = url.searchParams.get('date') || '<?php echo date('Y-m-d'); ?>';
    location.href = `puantaj?date=${date}`;
}

let isSelectionMode = false;
let selectedPersons = [];

function handleRowClick(element) {
    if (isSelectionMode) {
        togglePersonSelection($(element));
    } else {
        const viewMode = '<?php echo $view_mode; ?>';
        if (viewMode === 'monthly') {
            openMonthlyEditModal(element);
        } else {
            openPuantajModal(element);
        }
    }
}

function startSelectionMode($row) {
    isSelectionMode = true;
    document.getElementById('bulkActionBar').classList.remove('d-none');
    document.body.classList.add('selection-active');
    togglePersonSelection($row);
    
    if (window.navigator && window.navigator.vibrate) {
        window.navigator.vibrate(50);
    }
}

function togglePersonSelection($row) {
    const personId = $row.attr('data-person-id');
    const personKey = $row.attr('data-person-key');
    const personName = $row.attr('data-person-name');
    const checkbox = $row.find('.form-check-input')[0];
    
    const index = selectedPersons.findIndex(p => p.id === personId);
    
    if (index > -1) {
        selectedPersons.splice(index, 1);
        $row.removeClass('selected');
        if (checkbox) checkbox.checked = false;
    } else {
        selectedPersons.push({ id: personId, key: personKey, name: personName });
        $row.addClass('selected');
        if (checkbox) checkbox.checked = true;
    }
    
    document.getElementById('selectedCountText').innerText = `${selectedPersons.length} kişi seçildi`;
    
    if (selectedPersons.length === 0) {
        cancelSelection();
    }
}

function cancelSelection() {
    isSelectionMode = false;
    selectedPersons = [];
    $('.person-row').removeClass('selected');
    $('.form-check-input').prop('checked', false);
    document.getElementById('bulkActionBar').classList.add('d-none');
    document.body.classList.remove('selection-active');
}

function openBulkPuantajModal(fromSelection = false) {
    isBulkMode = true;
    currentSelectedPersonId = null;
    currentSelectedPersonKey = null;
    currentSelectedTypeId = null;
    
    if (fromSelection && selectedPersons.length > 0) {
        document.getElementById('modalPersonName').innerText = "Seçili Personeller (" + selectedPersons.length + ")";
    } else {
        document.getElementById('modalPersonName').innerText = "Tüm Personeller";
        // If not from selection, we might want to clear selectedPersons to avoid confusion
        selectedPersons = []; 
    }
    
    // Seçimleri temizle
    document.querySelectorAll('.type-option-row').forEach(row => {
        row.classList.remove('selected');
    });
    
    // Varsayılan olarak Normal Çalışma sekmesini aç
    const tabButtons = Array.from(document.querySelectorAll('#v-pills-tab button'));
    const normalTabButton = tabButtons.find(btn => btn.innerText.trim() === 'Normal Çalışma');
    if (normalTabButton) {
        bootstrap.Tab.getOrCreateInstance(normalTabButton).show();
    } else if (tabButtons.length > 0) {
        bootstrap.Tab.getOrCreateInstance(tabButtons[0]).show();
    }
    
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('puantajModal'));
    modal.show();
}

function openPuantajModal(element) {
    isBulkMode = false;
    currentSelectedPersonId = element.getAttribute('data-person-id');
    currentSelectedPersonKey = element.getAttribute('data-person-key');
    const personName = element.getAttribute('data-person-name');
    const currentTypeId = element.getAttribute('data-current-type-id');
    
    document.getElementById('modalPersonName').innerText = personName;
    currentSelectedTypeId = currentTypeId;
    
    // Clear previous selection
    document.querySelectorAll('.type-option-row').forEach(row => {
        row.classList.remove('selected');
    });
    
    // Select current type if it exists
    if (currentTypeId) {
        const activeOption = document.querySelector(`.type-option-row[data-type-id="${currentTypeId}"]`);
        if (activeOption) {
            activeOption.classList.add('selected');
            // Switch to the correct category tab for this option
            const tabPane = activeOption.closest('.tab-pane');
            if (tabPane) {
                const tabButtonId = tabPane.getAttribute('aria-labelledby');
                if (tabButtonId) {
                    const tabButton = document.getElementById(tabButtonId);
                    if (tabButton) {
                        bootstrap.Tab.getOrCreateInstance(tabButton).show();
                    }
                }
            }
        }
    } else {
        // If no selection exists, default to 'Normal Çalışma' tab
        const tabButtons = Array.from(document.querySelectorAll('#v-pills-tab button'));
        const normalTabButton = tabButtons.find(btn => btn.innerText.trim() === 'Normal Çalışma');
        if (normalTabButton) {
            bootstrap.Tab.getOrCreateInstance(normalTabButton).show();
        } else if (tabButtons.length > 0) {
            bootstrap.Tab.getOrCreateInstance(tabButtons[0]).show();
        }
    }
    
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('puantajModal'));
    modal.show();
}

function selectTypeOption(element) {
    document.querySelectorAll('.type-option-row').forEach(row => {
        row.classList.remove('selected');
    });
    element.classList.add('selected');
    currentSelectedTypeId = element.getAttribute('data-type-id');
    
    // Seçim yapınca direkt atama yapsın!
    if (isMonthlyCalendarMode) {
        if (isMonthlyBulkMode) {
            saveMonthlyBulkPuantaj(element);
        } else {
            saveMonthlySingleDayPuantaj(element);
        }
    } else {
        if (isBulkMode) {
            saveBulkPuantaj(element);
        } else {
            saveSelectedPuantaj(element);
        }
    }
}

function saveBulkPuantaj(selectedOption) {
    const typeCode = selectedOption.getAttribute('data-type-code');
    const typeId = selectedOption.getAttribute('data-type-id');
    const serverDate = '<?php echo $selected_date; ?>';
    
    Swal.fire({
        title: 'Emin misiniz?',
        text: `Tüm personelleri "${typeCode}" olarak işaretlemek istediğinize emin misiniz?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#206bc4',
        cancelButtonColor: '#9299a6',
        confirmButtonText: 'Evet, Uygula!',
        cancelButtonText: 'Vazgeç',
        reverseButtons: true,
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-link link-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            const modalEl = document.getElementById('puantajModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            const rows = document.querySelectorAll('.person-row');
            const payload = {};
            const targets = [];

            if (selectedPersons.length > 0) {
                // Sadece seçili olanlar
                selectedPersons.forEach(person => {
                    payload[person.key] = {};
                    payload[person.key][serverDate] = {
                        puantajId: typeId,
                        project_id: <?php echo (int)$selected_project_id; ?>
                    };
                    targets.push(person.id);
                });
            } else {
                // Görünür olan tüm personeller
                rows.forEach(row => {
                    if (row.getAttribute('data-is-disabled') === 'true') return;
                    if (row.parentElement.style.display === 'none') return; // person-item-wrapper display'ine bak
                    
                    const personKey = row.getAttribute('data-person-key');
                    payload[personKey] = {};
                    payload[personKey][serverDate] = {
                        puantajId: typeId,
                        project_id: <?php echo (int)$selected_project_id; ?>
                    };
                    targets.push(row.getAttribute('data-person-id'));
                });
            }

            targets.forEach(personId => {
                const badge = document.getElementById(`status-badge-${personId}`);
                if(badge) {
                    badge.innerHTML = '<div class="loading-spinner-inner"></div>';
                    badge.style.backgroundColor = '#f1f5f9';
                    badge.style.color = 'transparent';
                }
            });

            jQuery.ajax({
                url: 'modules/puantaj/api/puantaj-bulk-save.php',
                method: 'POST',
                data: {
                    action: 'savePuantaj',
                    data: JSON.stringify(payload)
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' || response.status === 'info') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı',
                            text: 'Puantajlar başarıyla güncellendi.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            if (selectedPersons.length > 0) {
                                cancelSelection();
                            }
                            location.reload();
                        });
                    } else {
                        Swal.fire('Hata', response.message, 'error').then(() => location.reload());
                    }
                },
                error: function(xhr) {
                    Swal.fire('Bağlantı Hatası', 'İşlem sırasında bir hata oluştu.', 'error').then(() => location.reload());
                }
            });
        }
    });
}

function saveSelectedPuantaj(selectedOption) {
    if (!currentSelectedPersonId || !currentSelectedTypeId) {
        var modalEl = document.getElementById('puantajModal');
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
        return;
    }
    
    const typeCode = selectedOption.getAttribute('data-type-code');
    const typeLabel = selectedOption.getAttribute('data-type-label');
    const typeColor = selectedOption.getAttribute('data-type-color');
    const typeTextColor = selectedOption.getAttribute('data-type-text-color');
    
    // Merkezi API ile uyumlu tireli tarih formatı
    const serverDate = '<?php echo $selected_date; ?>'; 
    
    const payload = {};
    payload[currentSelectedPersonKey] = {};
    payload[currentSelectedPersonKey][serverDate] = {
        puantajId: currentSelectedTypeId,
        project_id: <?php echo (int)$selected_project_id; ?>
    };
    
    const badge = document.getElementById(`status-badge-${currentSelectedPersonId}`);
    const originalContent = badge.outerHTML;
    
    badge.innerHTML = '<div class="loading-spinner-inner"></div>';
    badge.style.backgroundColor = '#f1f5f9';
    badge.style.color = 'transparent';
    
    var modalEl = document.getElementById('puantajModal');
    var modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
    
    // Mobil subdomain kısıtlaması nedeniyle yerel API'yi kullanıyoruz
    jQuery.ajax({
        url: 'modules/puantaj/api/puantaj-save.php',
        method: 'POST',
        data: {
            person_id: currentSelectedPersonId,
            date: serverDate,
            type_id: currentSelectedTypeId,
            project_id: <?php echo (int)$selected_project_id; ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                badge.style.backgroundColor = typeColor;
                badge.style.color = typeTextColor;
                badge.className = "avatar avatar-md rounded-circle font-weight-bold";
                badge.innerText = typeCode;
                
                const row = document.querySelector(`.person-row[data-person-id="${currentSelectedPersonId}"]`);
                row.setAttribute('data-current-type-id', currentSelectedTypeId);
                row.classList.add('saved');
                setTimeout(() => row.classList.remove('saved'), 1000);
            } else {
                badge.outerHTML = originalContent;
                alert('Hata: ' + response.message);
            }
        },
        error: function(xhr) {
            badge.outerHTML = originalContent;
            alert('Bağlantı hatası: ' + xhr.status + "\nYanıt: " + xhr.responseText);
        }
    });
}

// Initialize filter modal selects when modal is about to be shown
document.getElementById('filterModal').addEventListener('show.bs.modal', function () {
    const $form = $(this);
    
    // Initial check for values before select2 replaces them
    $form.find('select').each(function() {
        if ($(this).val() && $(this).val() != "0") {
            $(this).closest('.form-floating-select2').addClass('has-value');
        } else {
            $(this).closest('.form-floating-select2').removeClass('has-value');
        }
    });

    if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {

        $('#job_group, #team_id, #project_id').select2({
            dropdownParent: $('#filterModal'),
            width: '100%'
        }).on('select2:open', function() {
            $(this).closest('.form-floating-select2').addClass('is-focused');
        }).on('select2:close', function() {
            $(this).closest('.form-floating-select2').removeClass('is-focused');
            if ($(this).val() && $(this).val() != "0") {
                $(this).closest('.form-floating-select2').addClass('has-value');
            } else {
                $(this).closest('.form-floating-select2').removeClass('has-value');
            }
        }).on('change', function() {
            if ($(this).val() && $(this).val() != "0") {
                $(this).closest('.form-floating-select2').addClass('has-value');
            } else {
                $(this).closest('.form-floating-select2').removeClass('has-value');
            }
        });

        // Initial check for values
        $('#job_group, #team_id, #project_id').each(function() {
            if ($(this).val() && $(this).val() != "0") {
                $(this).closest('.form-floating-select2').addClass('has-value');
            }
        });
    }
});

let activeMonthlyPersonId = null;
let activeMonthlyPersonKey = null;
let activeMonthlyPersonName = null;
let activeMonthlyDay = null;
let monthlyDaysInMonth = 0;
let monthlyYear = '<?php echo $year; ?>';
let monthlyMonth = '<?php echo $month; ?>';
let monthlyAttendanceData = {};
let dayLongPressTimer = null;
let isMonthlyMultiSelectMode = false;
let selectedMonthlyDays = [];
let isMonthlyCalendarMode = false;
let isMonthlyBulkMode = false;

function openMonthlyEditModal(element) {
    activeMonthlyPersonId = element.getAttribute('data-person-id');
    activeMonthlyPersonKey = element.getAttribute('data-person-key');
    activeMonthlyPersonName = element.getAttribute('data-person-name');
    
    document.getElementById('monthlyModalTitle').innerText = activeMonthlyPersonName;
    const monthNames = <?php echo json_encode($months); ?>;
    const monthName = monthNames[monthlyMonth] || '';
    document.getElementById('monthlyModalSubtitle').innerText = `${monthName} ${monthlyYear} Dönemi Puantaj Girişi`;
    
    const grid = document.getElementById('monthlyCalendarGrid');
    grid.innerHTML = '<div class="text-center py-4 w-100" style="grid-column: span 7;"><div class="spinner-border text-primary" role="status"></div></div>';
    
    cancelMonthlyDaySelection();
    
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('monthlyPuantajModal'));
    modal.show();

    // Fetch monthly data
    fetch(`modules/puantaj/api/get-person-monthly-puantaj.php?person_id=${activeMonthlyPersonId}&month=${monthlyMonth}&year=${monthlyYear}`)
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success') {
                monthlyAttendanceData = res.data;
                monthlyDaysInMonth = res.days_in_month;
                renderMonthlyEditCalendar(res.data, res.days_in_month, monthlyYear, monthlyMonth);
            } else {
                grid.innerHTML = `<div class="alert alert-danger" style="grid-column: span 7;">${res.message}</div>`;
            }
        })
        .catch(err => {
            grid.innerHTML = '<div class="alert alert-danger" style="grid-column: span 7;">Veriler alınırken bağlantı hatası oluştu.</div>';
        });
}

function renderMonthlyEditCalendar(data, daysInMonth, year, month) {
    const grid = document.getElementById('monthlyCalendarGrid');
    grid.innerHTML = '';
    
    // Day headers
    const dayNames = ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'];
    dayNames.forEach(name => {
        const h = document.createElement('div');
        h.className = 'text-center text-xs font-weight-bold text-muted pb-1';
        h.innerText = name;
        grid.appendChild(h);
    });

    // Offset spaces
    const firstDay = new Date(year, parseInt(month) - 1, 1).getDay();
    const startOffset = (firstDay === 0 ? 6 : firstDay - 1);

    for (let i = 0; i < startOffset; i++) {
        const empty = document.createElement('div');
        empty.className = 'calendar-day-edit empty';
        grid.appendChild(empty);
    }

    // Days
    for (let day = 1; day <= daysInMonth; day++) {
        const dayBox = document.createElement('div');
        dayBox.className = 'calendar-day-edit';
        dayBox.setAttribute('data-day', day);
        
        // Touch events for long press on mobile
        dayBox.addEventListener('touchstart', function(e) {
            if (dayBox.getAttribute('data-is-locked') === 'true') return;
            dayLongPressTimer = setTimeout(() => {
                if (!isMonthlyMultiSelectMode) {
                    startMonthlyMultiSelectMode(day);
                }
            }, 600);
        }, { passive: true });
        
        dayBox.addEventListener('touchend', function() {
            clearTimeout(dayLongPressTimer);
        }, { passive: true });
        
        dayBox.addEventListener('touchmove', function() {
            clearTimeout(dayLongPressTimer);
        }, { passive: true });
        
        // Mouse events for long press on desktop
        dayBox.addEventListener('mousedown', function() {
            if (dayBox.getAttribute('data-is-locked') === 'true') return;
            dayLongPressTimer = setTimeout(() => {
                if (!isMonthlyMultiSelectMode) {
                    startMonthlyMultiSelectMode(day);
                }
            }, 600);
        });
        
        dayBox.addEventListener('mouseup', function() {
            clearTimeout(dayLongPressTimer);
        });
        
        dayBox.addEventListener('mouseleave', function() {
            clearTimeout(dayLongPressTimer);
        });

        // Click event handler
        dayBox.addEventListener('click', function() {
            if (dayBox.getAttribute('data-is-locked') === 'true') {
                Swal.fire({
                    icon: 'info',
                    title: 'Puantaj Kilitli',
                    text: 'Bu tarihte onaylı bir izin talebi bulunmaktadır. Değiştirilemez.',
                    confirmButtonText: 'Tamam'
                });
                return;
            }
            if (isMonthlyMultiSelectMode) {
                toggleMonthlyDaySelection(day);
            } else {
                handleMonthlyDayClick(day);
            }
        });
        
        const dayNum = document.createElement('span');
        dayNum.className = 'day-num';
        dayNum.innerText = day;
        dayBox.appendChild(dayNum);
        
        const dayCode = document.createElement('span');
        dayCode.className = 'day-code';
        
        if (data[day]) {
            dayCode.innerText = data[day].code;
            dayBox.style.backgroundColor = data[day].bg;
            dayCode.style.color = data[day].color;
            if (data[day].bg !== '#f8fafc' && data[day].bg !== 'transparent') {
                dayNum.style.color = data[day].color;
                dayNum.style.opacity = '0.7';
            }
            if (data[day].is_locked) {
                dayBox.classList.add('izin-kilitli');
                dayBox.setAttribute('data-is-locked', 'true');
                dayBox.style.cursor = 'not-allowed';
            }
        } else {
            // Weekend check
            const dateObj = new Date(year, parseInt(month) - 1, day);
            const dNum = dateObj.getDay();
            const isWeekend = (dNum === 6 || dNum === 0);
            
            dayCode.innerText = isWeekend ? 'HT' : '-';
            dayCode.style.color = isWeekend ? '#d97706' : '#94a3b8';
            if (isWeekend) {
                dayBox.style.backgroundColor = 'rgba(245, 158, 11, 0.1)';
            }
        }
        
        dayBox.appendChild(dayCode);
        grid.appendChild(dayBox);
    }
}

function startMonthlyMultiSelectMode(day) {
    const cell = document.querySelector(`.calendar-day-edit[data-day="${day}"]`);
    if (cell && cell.getAttribute('data-is-locked') === 'true') return;
    
    isMonthlyMultiSelectMode = true;
    selectedMonthlyDays = [day];
    
    // Update footer UI
    document.getElementById('monthlyModalNormalFooter').classList.add('d-none');
    document.getElementById('monthlyModalBulkFooter').classList.remove('d-none');
    document.getElementById('monthlyModalBulkFooter').classList.add('d-flex');
    
    // Highlight selected cell
    if (cell) {
        cell.classList.add('selected-for-bulk');
    }
    
    updateMonthlySelectedDaysCount();
    
    if (window.navigator && window.navigator.vibrate) {
        window.navigator.vibrate(50);
    }
}

function toggleMonthlyDaySelection(day) {
    const cell = document.querySelector(`.calendar-day-edit[data-day="${day}"]`);
    if (cell && cell.getAttribute('data-is-locked') === 'true') return;
    
    const index = selectedMonthlyDays.indexOf(day);
    
    if (index > -1) {
        selectedMonthlyDays.splice(index, 1);
        if (cell) cell.classList.remove('selected-for-bulk');
    } else {
        selectedMonthlyDays.push(day);
        if (cell) cell.classList.add('selected-for-bulk');
    }
    
    updateMonthlySelectedDaysCount();
    
    if (selectedMonthlyDays.length === 0) {
        cancelMonthlyDaySelection();
    }
}

function updateMonthlySelectedDaysCount() {
    document.getElementById('monthlySelectedDaysCount').innerText = `${selectedMonthlyDays.length} gün seçildi`;
}

function cancelMonthlyDaySelection() {
    isMonthlyMultiSelectMode = false;
    selectedMonthlyDays = [];
    
    document.querySelectorAll('.calendar-day-edit').forEach(el => {
        el.classList.remove('selected-for-bulk');
    });
    
    const normalFooter = document.getElementById('monthlyModalNormalFooter');
    const bulkFooter = document.getElementById('monthlyModalBulkFooter');
    if (normalFooter && bulkFooter) {
        bulkFooter.classList.add('d-none');
        bulkFooter.classList.remove('d-flex');
        normalFooter.classList.remove('d-none');
    }
}

function handleMonthlyDayClick(day) {
    activeMonthlyDay = day;
    isMonthlyCalendarMode = true;
    isMonthlyBulkMode = false;
    
    // Format date string for Turkish locale display
    const dateObj = new Date(monthlyYear, parseInt(monthlyMonth) - 1, day);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const dateStrTR = dateObj.toLocaleDateString('tr-TR', options);
    
    // Set daily modal header details
    document.getElementById('modalPersonName').innerText = activeMonthlyPersonName;
    document.getElementById('puantajModalDateSubtitle').innerHTML = `<i class="ti ti-calendar me-1"></i>${dateStrTR} Tarihli Puantaj Girişi`;
    
    // Show 'Temizle' button in daily modal footer
    $('#btnMonthlyModalClearDay').removeClass('d-none');
    
    // Pre-select current day's type in the modal if it exists
    const dayData = monthlyAttendanceData[day];
    const currentTypeId = (dayData && dayData.id) ? dayData.id : null;
    currentSelectedTypeId = currentTypeId;
    currentSelectedPersonId = activeMonthlyPersonId;
    currentSelectedPersonKey = activeMonthlyPersonKey;
    isBulkMode = false;
    
    document.querySelectorAll('.type-option-row').forEach(row => {
        row.classList.remove('selected');
    });
    
    if (currentTypeId) {
        const activeOption = document.querySelector(`.type-option-row[data-type-id="${currentTypeId}"]`);
        if (activeOption) {
            activeOption.classList.add('selected');
            const tabPane = activeOption.closest('.tab-pane');
            if (tabPane) {
                const tabButtonId = tabPane.getAttribute('aria-labelledby');
                if (tabButtonId) {
                    const tabButton = document.getElementById(tabButtonId);
                    if (tabButton) {
                        bootstrap.Tab.getOrCreateInstance(tabButton).show();
                    }
                }
            }
        }
    } else {
        // Default to Normal Çalışma tab
        const tabButtons = Array.from(document.querySelectorAll('#v-pills-tab button'));
        const normalTabButton = tabButtons.find(btn => btn.innerText.trim() === 'Normal Çalışma');
        if (normalTabButton) {
            bootstrap.Tab.getOrCreateInstance(normalTabButton).show();
        } else if (tabButtons.length > 0) {
            bootstrap.Tab.getOrCreateInstance(tabButtons[0]).show();
        }
    }
    
    // Hide monthly modal first to prevent backdrop z-index issues
    const monthlyModal = bootstrap.Modal.getInstance(document.getElementById('monthlyPuantajModal'));
    if (monthlyModal) monthlyModal.hide();
    
    // Open the daily modal puantajModal
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('puantajModal'));
    modal.show();
}

function openMonthlyBulkTypeSelector() {
    isMonthlyCalendarMode = true;
    isMonthlyBulkMode = true;
    isBulkMode = true;
    
    currentSelectedPersonId = activeMonthlyPersonId;
    currentSelectedPersonKey = activeMonthlyPersonKey;
    currentSelectedTypeId = null;
    
    document.getElementById('modalPersonName').innerText = `${activeMonthlyPersonName} - Toplu İşlem`;
    document.getElementById('puantajModalDateSubtitle').innerHTML = `<i class="ti ti-calendar me-1"></i>Seçili ${selectedMonthlyDays.length} Gün İçin Puantaj Girişi`;
    
    // Hide 'Temizle' button since we are bulk assigning
    $('#btnMonthlyModalClearDay').addClass('d-none');
    
    document.querySelectorAll('.type-option-row').forEach(row => {
        row.classList.remove('selected');
    });
    
    const tabButtons = Array.from(document.querySelectorAll('#v-pills-tab button'));
    const normalTabButton = tabButtons.find(btn => btn.innerText.trim() === 'Normal Çalışma');
    if (normalTabButton) {
        bootstrap.Tab.getOrCreateInstance(normalTabButton).show();
    } else if (tabButtons.length > 0) {
        bootstrap.Tab.getOrCreateInstance(tabButtons[0]).show();
    }
    
    // Hide monthly modal first
    const monthlyModal2 = bootstrap.Modal.getInstance(document.getElementById('monthlyPuantajModal'));
    if (monthlyModal2) monthlyModal2.hide();
    
    const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('puantajModal'));
    modal.show();
}

function saveMonthlySingleDayPuantaj(selectedOption) {
    const typeId = selectedOption.getAttribute('data-type-id');
    const typeCode = selectedOption.getAttribute('data-type-code');
    const typeLabel = selectedOption.getAttribute('data-type-label');
    const typeColor = selectedOption.getAttribute('data-type-color');
    const typeTextColor = selectedOption.getAttribute('data-type-text-color');
    
    const dateStr = `${monthlyYear}-${monthlyMonth}-${String(activeMonthlyDay).padStart(2, '0')}`;
    
    const activeBox = document.querySelector(`.calendar-day-edit[data-day="${activeMonthlyDay}"]`);
    const originalContent = activeBox ? activeBox.innerHTML : '';
    if (activeBox) {
        activeBox.innerHTML = '<div class="loading-spinner-inner" style="width: 14px; height: 14px;"></div>';
    }
    
    const modalEl = document.getElementById('puantajModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
    
    jQuery.ajax({
        url: 'modules/puantaj/api/puantaj-save.php',
        method: 'POST',
        data: {
            person_id: activeMonthlyPersonId,
            date: dateStr,
            type_id: typeId,
            project_id: <?php echo (int)$selected_project_id; ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                // Update local monthly data
                monthlyAttendanceData[activeMonthlyDay] = {
                    id: typeId,
                    code: typeCode,
                    bg: typeColor,
                    color: typeTextColor
                };
                
                // Update dayBox UI
                if (activeBox) {
                    activeBox.innerHTML = `<span class="day-num" style="color: ${typeTextColor}; opacity: 0.7;">${activeMonthlyDay}</span><span class="day-code" style="color: ${typeTextColor};">${typeCode}</span>`;
                    activeBox.style.backgroundColor = typeColor;
                }
                
                // Update background personnel statistics summary immediately
                updateBackgroundPersonStats(activeMonthlyPersonId);
            } else {
                if (activeBox) activeBox.innerHTML = originalContent;
                Swal.fire('Hata', response.message, 'error');
            }
        },
        error: function(xhr) {
            if (activeBox) activeBox.innerHTML = originalContent;
            Swal.fire('Hata', 'Kayıt sırasında bağlantı hatası oluştu.', 'error');
        }
    });
}

function saveMonthlyBulkPuantaj(selectedOption) {
    const typeId = selectedOption.getAttribute('data-type-id');
    const typeCode = selectedOption.getAttribute('data-type-code');
    const typeLabel = selectedOption.getAttribute('data-type-label');
    const typeColor = selectedOption.getAttribute('data-type-color');
    const typeTextColor = selectedOption.getAttribute('data-type-text-color');
    
    const modalEl = document.getElementById('puantajModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();

    // Prepare payload
    const payload = {};
    payload[activeMonthlyPersonKey] = {};
    
    selectedMonthlyDays.forEach(day => {
        const dateStr = `${monthlyYear}-${monthlyMonth}-${String(day).padStart(2, '0')}`;
        payload[activeMonthlyPersonKey][dateStr] = {
            puantajId: typeId,
            project_id: <?php echo (int)$selected_project_id; ?>
        };
        
        // Show spinner on dayBoxes
        const activeBox = document.querySelector(`.calendar-day-edit[data-day="${day}"]`);
        if (activeBox) {
            activeBox.innerHTML = '<div class="loading-spinner-inner" style="width: 14px; height: 14px;"></div>';
        }
    });
    
    jQuery.ajax({
        url: 'modules/puantaj/api/puantaj-bulk-save.php',
        method: 'POST',
        data: {
            action: 'savePuantaj',
            data: JSON.stringify(payload)
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' || response.status === 'info') {
                selectedMonthlyDays.forEach(day => {
                    // Update local monthly data
                    monthlyAttendanceData[day] = {
                        id: typeId,
                        code: typeCode,
                        bg: typeColor,
                        color: typeTextColor
                    };
                    
                    // Update dayBox UI
                    const activeBox = document.querySelector(`.calendar-day-edit[data-day="${day}"]`);
                    if (activeBox) {
                        activeBox.innerHTML = `<span class="day-num" style="color: ${typeTextColor}; opacity: 0.7;">${day}</span><span class="day-code" style="color: ${typeTextColor};">${typeCode}</span>`;
                        activeBox.style.backgroundColor = typeColor;
                    }
                });
                
                // Clear selection
                cancelMonthlyDaySelection();
                
                // Update background personnel statistics summary immediately
                updateBackgroundPersonStats(activeMonthlyPersonId);
            } else {
                Swal.fire('Hata', response.message, 'error');
                // Re-fetch monthly data to restore calendar
                openMonthlyEditModal({
                    getAttribute: (attr) => {
                        if (attr === 'data-person-id') return activeMonthlyPersonId;
                        if (attr === 'data-person-key') return activeMonthlyPersonKey;
                        if (attr === 'data-person-name') return activeMonthlyPersonName;
                    }
                });
            }
        },
        error: function(xhr) {
            Swal.fire('Hata', 'Toplu kayıt sırasında bağlantı hatası oluştu.', 'error');
        }
    });
}

function clearActiveCalendarDay() {
    if (!activeMonthlyPersonId || !activeMonthlyDay) return;
    
    const dateStr = `${monthlyYear}-${monthlyMonth}-${String(activeMonthlyDay).padStart(2, '0')}`;
    const activeBox = document.querySelector(`.calendar-day-edit[data-day="${activeMonthlyDay}"]`);
    const originalContent = activeBox ? activeBox.innerHTML : '';
    if (activeBox) {
        activeBox.innerHTML = '<div class="loading-spinner-inner" style="width: 14px; height: 14px;"></div>';
    }
    
    const modalEl = document.getElementById('puantajModal');
    const modal = bootstrap.Modal.getInstance(modalEl);
    if (modal) modal.hide();
    
    jQuery.ajax({
        url: 'modules/puantaj/api/puantaj-delete.php',
        method: 'POST',
        data: {
            person_id: activeMonthlyPersonId,
            date: dateStr,
            project_id: <?php echo (int)($selected_project_id ?: -1); ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success' || response.status === 'info') {
                // Clear local monthly data
                delete monthlyAttendanceData[activeMonthlyDay];
                
                // Reset dayBox UI (checking weekend for default HT)
                const dateObj = new Date(monthlyYear, parseInt(monthlyMonth) - 1, activeMonthlyDay);
                const dNum = dateObj.getDay();
                const isWeekend = (dNum === 6 || dNum === 0);
                
                const codeText = isWeekend ? 'HT' : '-';
                const codeColor = isWeekend ? '#d97706' : '#94a3b8';
                
                if (activeBox) {
                    activeBox.innerHTML = `<span class="day-num">${activeMonthlyDay}</span><span class="day-code" style="color: ${codeColor};">${codeText}</span>`;
                    activeBox.style.backgroundColor = isWeekend ? 'rgba(245, 158, 11, 0.1)' : '';
                }
                
                // Update background personnel statistics summary immediately
                updateBackgroundPersonStats(activeMonthlyPersonId);
            } else {
                if (activeBox) activeBox.innerHTML = originalContent;
                Swal.fire('Hata', response.message, 'error');
            }
        },
        error: function() {
            if (activeBox) activeBox.innerHTML = originalContent;
            Swal.fire('Hata', 'Silme sırasında bağlantı hatası oluştu.', 'error');
        }
    });
}

function updateBackgroundPersonStats(personId) {
    const statsContainer = document.getElementById(`monthly-stats-${personId}`);
    if (!statsContainer) return;
    
    // We can count types directly from our monthlyAttendanceData
    const counts = {};
    const allTypes = <?php echo json_encode($all_puantaj_types); ?>;
    
    for (let day in monthlyAttendanceData) {
        const item = monthlyAttendanceData[day];
        if (item && item.id) {
            const type = allTypes[item.id];
            if (type) {
                const cat = type.Turu;
                const color = type.ArkaPlanRengi;
                const textColor = type.FontRengi;
                
                // Abbreviation (Normal Çalışma -> NÇ)
                const words = cat.split(' ');
                let short = '';
                words.forEach(w => { if(w) short += w.substring(0, 1); });
                
                if (!counts[cat]) {
                    counts[cat] = {
                        count: 0,
                        short: short,
                        color: color
                    };
                }
                counts[cat].count++;
            }
        }
    }
    
    // Render back HTML
    let html = '';
    const sortedCats = Object.keys(counts).sort((a, b) => {
        if (a === 'Normal Çalışma') return -1;
        if (b === 'Normal Çalışma') return 1;
        return a.localeCompare(b);
    });
    
    let limit = 3;
    let i = 0;
    sortedCats.forEach(catName => {
        const stat = counts[catName];
        if (i < limit && stat.count > 0) {
            html += `<div class="text-center px-1.5 py-1" style="min-width: 33px; background-color: ${stat.color}18; border: 1px solid ${stat.color}38; border-radius: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.03);">
              <div class="fw-bold mb-0" style="font-size: 0.78rem; color: ${stat.color}; line-height: 1.1;">${stat.count}</div>
              <div class="fw-bold" style="font-size: 0.62rem; color: ${stat.color}; opacity: 0.9; line-height: 1; margin-top: 1px; letter-spacing: 0.2px; text-transform: uppercase;">${stat.short}</div>
            </div>`;
            i++;
        }
    });
    
    if (html === '') {
        html = '<span class="text-muted text-xs" style="font-size: 0.75rem;">Giriş yok</span>';
    }
    
    statsContainer.innerHTML = html;
}
</script>
