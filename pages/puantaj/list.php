<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/projects.php';
require_once 'App/Helper/puantaj.php';
require_once 'Model/Persons.php';
require_once 'Model/Puantaj.php';
require_once 'App/Helper/security.php';
require_once 'App/Helper/jobs.php';
require_once 'App/Helper/teams.php';

use App\Helper\Date;
use App\Helper\Helper;
use App\Helper\Security;

$puantajHelper = new puantajHelper();
$projectHelper = new ProjectHelper();
$personObj = new Persons();
$projects = new Projects();

$puantajObj = new Puantaj();
$jobsHelper = new Jobs();
$teamsHelper = new Teams();

if (isset($Auths)) {
    $Auths->checkFirmReturn();
}

$firm_id = (int) ($_SESSION['firm_id'] ?? 0);

$year = (int) (isset($_REQUEST['year']) ? $_REQUEST['year'] : ($_COOKIE['p_year'] ?? date('Y')));
$month = (int) (isset($_REQUEST['months']) ? $_REQUEST['months'] : ($_COOKIE['p_months'] ?? date('m')));
$last_day = Date::Ymd(Date::lastDay($month, $year));
$project_id = (int) (isset($_REQUEST['projects']) ? $_REQUEST['projects'] : ($_COOKIE['p_projects'] ?? 0));
$job_group = (int) (isset($_REQUEST['job_groups']) ? $_REQUEST['job_groups'] : ($_COOKIE['p_job_groups'] ?? 0));
$team_id = (int) (isset($_REQUEST['team_id']) ? $_REQUEST['team_id'] : ($_COOKIE['p_team_id'] ?? 0));
$person_status = isset($_REQUEST['person_status']) ? $_REQUEST['person_status'] : ($_COOKIE['p_person_status'] ?? 'active');


require_once 'Model/SettingsModel.php';

$Settings = new SettingsModel();
$showWhiteCollar = $Settings->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;
$first_day = Date::firstDay($month, $year);

if ($project_id > 0 && !$projects->belongsToFirm($project_id, $firm_id)) {
    setcookie('p_projects', '', time() - 3600, '/');
    $project_id = 0;
    $persons = [];
} elseif ($project_id == 0 || $project_id == '') {
    // Proje id boş ise Firma id'sine göre tüm mavi yakalı, işe başlama tarihi o ayın son gününden önce olan personelleri getirir
    // Akıllı görünürlük: Yeni başlayanlar veya bu ay puantajı olanlar her zaman görünür
    $persons = $personObj->getPersonIdByFirmBlueCollarCurrentMonth($firm_id, $first_day, $last_day, $job_group, $team_id, $showWhiteCollar, $person_status);
} else {
    // Proje id dolu ise projeye ait, işe başlama tarihi o ayın son gününden önce olan mavi yakalı personelleri getirir
    // Akıllı görünürlük: Projeye atanmış olanlar veya bu ay bu projede puantajı olanlar
    $persons = $projects->getPersonIdByFromProjectCurrentMonth($project_id, $first_day, $last_day, $job_group, $team_id, $showWhiteCollar, $person_status);
}
// Ayın son gününü bulma
$days = Date::daysInMonth($month, $year);
// Tarihleri oluşturma
$dates = Date::generateDates($year, $month, $days);

// ===== PERFORMANS OPTİMİZASYONU: Toplu veri çekme =====
// Tüm person ID'lerini topla
$person_ids = array_map(function($p) { return $p->id; }, $persons);

// 1) Tüm personellerin aylık puantaj verilerini TEK sorguda çek
$first_day_ymd = Date::Ymd(Date::firstDay($month, $year));
$last_day_ymd = $last_day;
// Hem tireli hem tiresiz formatta arama yapabilmek için geniş aralık
$allPuantajData = $puantajObj->getAllPuantajForPersons($person_ids, $first_day_ymd, str_replace('-', '', $last_day_ymd));

// 2) Tüm puantaj türlerini TEK sorguda çek ve cache'le
$allPuantajTurleri = $puantajObj->getAllPuantajTurleri();

// 3) Tüm proje isimlerini TEK sorguda çek ve cache'le
$allProjects = $projects->getProjectsByFirm($firm_id);
$projectNamesCache = [];
foreach ($allProjects as $proj) {
    $projectNamesCache[$proj->id] = $proj->project_name;
}
$projectNamesCache[0] = "Proje Yok";
// ===== OPTİMİZASYON SONU =====?>
<style>
    .gun {
        width: 35px;
        min-width: 35px;
        background-color: white;
        text-align: center;
        cursor: pointer;
        font-weight: 600;
    }

    .gunadi {
        width: 35px !important;
        min-width: 35px !important;
        max-width: 35px !important;
        text-align: center;
    }

    .head-date {
        width: 35px !important;
        min-width: 35px !important;
        max-width: 35px !important;
        text-align: center;
    }

    table.dataTable.table-sm>thead>tr>th:not(.sorting_disabled) {
        padding: 7px !important;
    }

    .dataTables_wrapper .dataTables_filter {
        display: none;
    }

    .table {
        padding-bottom: 15px !important;
        width: auto !important;
        min-width: 100% !important;
        border-collapse: collapse !important;
    }

    /* DataTables sıralama ikonlarını gün sütunlarında gizle */
    #puantajTable thead th.gunadi::before,
    #puantajTable thead th.gunadi::after,
    #puantajTable thead th.head-date::before,
    #puantajTable thead th.head-date::after {
        display: none !important;
    }

    #puantajTable thead th.gunadi,
    #puantajTable thead th.head-date {
        padding-right: 8px !important;
        padding-left: 8px !important;
        text-align: center !important;
    }
    
    .table thead th input {
        width: 100% !important;
    }

    /* İsim ve Unvan sütunları için genişlik */
    #puantajTable .extra-grup, #puantajTable .extra-ekip { 
        width: 140px !important;
        min-width: 140px !important;
    }
    
    table#puantajTable.table {
        width: 100% !important;
        table-layout: auto !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        margin: 0 !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    #puantajTable th, #puantajTable td {
        border: 1px solid #e2e8f0 !important;
        padding: 8px 6px !important;
        vertical-align: middle !important;
        white-space: nowrap !important;
    }

    /* First column greedy behavior */
    #puantajTable th:nth-child(1), #puantajTable td:nth-child(1) { 
        width: auto !important; 
        min-width: 220px !important; 
    }
    #puantajTable th:nth-child(2), #puantajTable td:nth-child(2) { 
        width: auto !important; 
        min-width: 160px !important; 
    }
    
    #puantajTable th, #puantajTable td {
        box-sizing: border-box;
    }

    .table tbody tr td {
        max-height: 45px !important;
        height: 45px !important;
        padding: 4px !important;
        vertical-align: middle !important;
        text-align: center;
    }

    /* Gün hücrelerini her koşulda sabitle */
    .gun, .gunadi, .head-date {
        width: 40px !important;
        min-width: 40px !important;
        max-width: 40px !important;
        padding: 4px 0 !important;
        text-align: center !important;
        overflow: hidden !important;
        white-space: nowrap !important;
        font-size: 13px !important;
    }

    .table tr td,
    .table th {
        border: 1px solid #ddd !important;
    }

    .gun.clicked {
        background-color: #FFED00 !important;
        cursor: pointer;
    }

    .unclicked {
        background-color: white;
    }

    th:hover {
        cursor: pointer;
    }

    th.ld {
        min-width: 100px;
    }

    th.vertical {
        writing-mode: vertical-rl;
        transform: rotate(180deg);
        white-space: nowrap;
        height: 100px;
        width: 40px;
        min-width: 40px;
        max-width: 40px;
        vertical-align: bottom;
        padding: 0;
        line-height: 1.5;
    }



    th.vertical span {
        writing-mode: vertical-lr;
        font-size: 12px;
        transform: rotate(180deg);
        font-weight: 800;

    }

    .hover-menu {

        /* width: 100%; */
        overflow-y: auto;
        height: 400px;
    }

    .hover-menu ul li {
        padding: 10px;
        margin: 5px;
        border: none !important;

    }

    .hover-menu ul li:hover {
        background-color: #eee;
        border-radius: 6px;
        cursor: pointer;


    }

    .hover-menu .nav-item {
        padding: 7px !important;
        margin: 0px !important;

    }

    .card-body .hover-menu {
        padding: 5px !important;

    }

    .hover-menu ul li:active {
        background-color: #ccc;
        transition: background-color 0.4s ease;
        /* Geçiş efekti */
    }

    .grid {
        gap: 2px;
    }

    .noselect {
        user-select: none;
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
    }

    .nav-pills .nav-link {
        white-space: nowrap;
    }

    .no-wrap {
        white-space: nowrap;
    }

    .avatar {

        color: white;
        opacity: 1;
        content: attr(data-initials);
        font-weight: bold;
        border-radius: 50%;
        vertical-align: middle;
        margin-right: 0.4em;
        width: 35px;
        height: 35px;
        line-height: 35px;
        text-align: center;
        float: left;
    }

    .avatar-big {
        width: 70px;
        height: 70px;
        line-height: 70px;
        color: #222;
        position: fixed;
        z-index: 100;
    }

    table {
        width: 100% !important;
    }

    [data-tooltip]:before {
        text-align: left;
    }

    .head-date {
        font-size: 12px !important;
        font-weight: 700 !important;
        text-align: center !important;
    }

    .dt-column-order {

        display: none;
    }

    .dt-search {
        display: none;
    }

    .description {
        font-size: 12px;
        color: #777;
    }

    .head-title {
        font-size: 15px;
        font-weight: 600;
    }

    #puantajTable {
        border-collapse: collapse !important;
    }

    #puantajTable thead th {
        position: -webkit-sticky !important;
        position: sticky !important;
        background: #f8fafc !important;
        z-index: 1000 !important;
        border-bottom: 1px solid #ddd !important;
        border-right: 1px solid #ddd !important;
    }

    #puantajTable thead tr:nth-child(1) th {
        top: 0 !important;
        z-index: 1001 !important;
    }

    #puantajTable thead tr:nth-child(2) th {
        top: 38px !important; 
        z-index: 1000 !important;
    }

    /* Tablo içeriğinin kaydırılabilir olması ve başlıkların sabit kalması için */
    .table-responsive {
        max-height: 70vh;
        overflow: auto !important;
        border: 1px solid #e2e8f0;
    }

    /* Ayarlar dropdown stili */
    .dropdown-menu-settings {
        min-width: 250px;
        padding: 0.5rem 0;
        z-index: 1060;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .cursor-pointer {
        cursor: pointer !important;
    }

    #puantajTable thead th.cursor-pointer:hover {
        background-color: #f1f5f9 !important;
    }

    #puantajTable thead th.cursor-pointer {
        position: relative;
        padding-right: 20px !important;
    }

    #puantajTable thead th.cursor-pointer::after {
        content: '↕';
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.3;
        font-size: 0.8em;
    }

    #puantajTable thead th.sorting_asc::after {
        content: '↑' !important;
        opacity: 1 !important;
        color: #206bc4;
    }

    #puantajTable thead th.sorting_desc::after {
        content: '↓' !important;
        opacity: 1 !important;
        color: #206bc4;
    }


    /* Pazar günleri için soft kırmızı */
    .bg-danger-lt {
        background-color: #fee2e2 !important;
        color: #b91c1c !important;
    }


    .dropdown-menu-column-selector {
        min-width: 180px;
        padding: 0.5rem 0;
        z-index: 1050; /* Dropdown'ın her şeyin üstünde olması için */
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }

    .dropdown-item.cursor-pointer {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 0.5rem 1rem;
    }

    .animate-pulse {
        animation: pulse 2.5s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: .8; transform: scale(1.08); }
    }
 
</style>





<?php include_once 'content/puantaj-turleri-modal.php' ?>
<?php include_once 'content/puantaj-istatistik-modal.php' ?>


<div class="container-fluid mt-3">
    <form action="" method="post" id="puantajInfoForm">
        <div class="row g-2 align-items-center">
            <div class="col-md-2">
                <label for="projects" class="form-label">Proje:</label>
                <?php echo $projectHelper->getProjectSelect('projects', $project_id); ?>
            </div>
            <div class="col-md-1">
                <label for="months" class="form-label">Ay:</label>
                <?php echo Date::getMonthsSelect('months', $month); ?>
            </div>
            <div class="col-md-1">
                <label for="year" class="form-label">Yıl:</label>
                <?php echo Date::getYearsSelect('year', $year); ?>
            </div>
            <div class="col-md-2">
                <label for="job_groups" class="form-label">Grup:</label>
                <?php echo $jobsHelper->jobGroupsSelect('job_groups', $job_group); ?>
            </div>
            <div class="col-md-2">
                <label for="team_id" class="form-label">Ekip:</label>
                <?php echo $teamsHelper->teamsSelect('team_id', $team_id); ?>
            </div>
            <div class="col-md-2">
                <label class="form-label">Personel Durumu:</label>
                <div class="form-selectgroup">
                    <label class="form-selectgroup-item">
                        <input type="radio" name="person_status" value="active" class="form-selectgroup-input" <?php echo $person_status == 'active' ? 'checked' : ''; ?>>
                        <span class="form-selectgroup-label" title="Aktif Personeller">Aktif</span>
                    </label>
                    <label class="form-selectgroup-item">
                        <input type="radio" name="person_status" value="passive" class="form-selectgroup-input" <?php echo $person_status == 'passive' ? 'checked' : ''; ?>>
                        <span class="form-selectgroup-label" title="Pasif Personeller">Pasif</span>
                    </label>
                    <label class="form-selectgroup-item">
                        <input type="radio" name="person_status" value="all" class="form-selectgroup-input" <?php echo $person_status == 'all' ? 'checked' : ''; ?>>
                        <span class="form-selectgroup-label" title="Tüm Personeller">Tümü</span>
                    </label>
                </div>
            </div>
            <div class="col-md-2">
                <label for="actions" class="form-label">İşlem</label>
                <div class="d-flex gap-1">
                    <input type="radio" class="btn-check" name="btn-radio-toolbar" id="btn-radio-toolbar-1"
                        autocomplete="off">
                    <label for="btn-radio-toolbar-1" data-tooltip="İndir" class="btn btn-icon" id="export_excel_puantaj">
                        <i class="ti ti-file-type-xls icon"></i>
                    </label>

                    <input type="radio" class="btn-check" name="btn-radio-toolbar" id="btn-radio-toolbar-1"
                        autocomplete="off">
                    <label for="btn-radio-toolbar-1" data-tooltip="Yazdır" class="btn btn-icon">
                        <i class="ti ti-printer icon"></i>
                    </label>

                    <input type="radio" class="btn-check" name="btn-radio-toolbar" id="btn-radio-toolbar-1"
                        autocomplete="off">
                    <label for="btn-radio-toolbar-1" data-tooltip="Personele Gönder" class="btn btn-icon">
                        <i class="ti ti-send icon"></i>
                    </label>
                </div>
            </div>
        </div>
    </form>
</div>


<div class="container-fluid mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="accordion-item border-0" style="background: transparent;">
                        <h2 class="accordion-header" id="heading-1">
                            <button class="accordion-button collapsed p-2" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapse-1" aria-expanded="false" style="background: transparent; box-shadow: none;">
                                <div class="d-flex align-items-center">
                                    <div class="bg-blue-lt text-blue d-flex align-items-center justify-content-center me-2 animate-pulse" style="width: 32px; height: 32px; border-radius: 8px;">
                                        <i class="ti ti-info-circle fs-2"></i>
                                    </div>
                                    <div>
                                        <h3 class="card-title mb-0" style="font-size: 14px; font-weight: 700; color: #1e293b;">Puantaj İpuçları & Kısayollar</h3>
                                        <small class="text-blue d-block font-weight-semibold" style="font-size: 11px; cursor: pointer;">
                                            <i class="ti ti-help me-1"></i>Kullanım ipuçlarını ve kısayolları görmek için tıklayın!
                                        </small>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id="collapse-1" class="accordion-collapse collapse" data-bs-parent="#heading-1">
                            <div class="accordion-body pt-3 pb-2 ps-2" style="font-size: 13px; line-height: 1.6; color: #475569;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="mb-2 d-flex align-items-start">
                                            <i class="ti ti-pointer text-blue me-2 mt-1 fs-3"></i>
                                            <span><strong>Tek Tek Seçim:</strong> İstediğiniz hücrenin üzerine tek tıklayarak seçebilirsiniz.</span>
                                        </div>
                                        <div class="mb-2 d-flex align-items-start">
                                            <i class="ti ti-mouse text-blue me-2 mt-1 fs-3"></i>
                                            <span><strong>Çoklu Seçim:</strong> Sol tıklayıp basılı tutarak fareyi hücreler üzerinde sürükleyebilirsiniz.</span>
                                        </div>
                                        <div class="mb-2 d-flex align-items-start">
                                            <i class="ti ti-arrows-down text-blue me-2 mt-1 fs-3"></i>
                                            <span><strong>Tüm Sütunu Seçme:</strong> En üstteki <strong>tarih sayısına</strong> veya <strong>gün adına</strong> tıklayarak tüm personellerin o günkü hücresini seçebilirsiniz.</span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-2 d-flex align-items-start">
                                            <i class="ti ti-keyboard text-purple me-2 mt-1 fs-3"></i>
                                            <span><strong>Hızlı Menü (Ctrl):</strong> Seçim yaparken <strong>Ctrl</strong> tuşunu basılı tutarsanız, seçimi bıraktığınızda tür menüsü otomatik açılır.</span>
                                        </div>
                                        <div class="mb-2 d-flex align-items-start">
                                            <i class="ti ti-trash text-danger me-2 mt-1 fs-3"></i>
                                            <span><strong>Puantaj Silme (Delete):</strong> İlgili hücreleri seçip klavyeden <strong>Delete</strong> tuşuna basın ve ardından <strong>Kaydet</strong> butonuna tıklayın.</span>
                                        </div>
                                        <div class="mb-2 d-flex align-items-start">
                                            <i class="ti ti-x text-warning me-2 mt-1 fs-3"></i>
                                            <span><strong>Seçimleri Temizleme (Esc):</strong> Sarı renkli seçili hücreleri iptal etmek için klavyeden <strong>ESC</strong> tuşuna basabilirsiniz.</span>
                                        </div>
                                        <div class="mb-0 d-flex align-items-start">
                                            <i class="ti ti-device-floppy text-success me-2 mt-1 fs-3"></i>
                                            <span><strong>Hızlı Kaydet (Ctrl+S):</strong> Çalışmalarınızı anında kaydetmek için klavyeden <strong>Ctrl + S</strong> tuş kombinasyonunu kullanabilirsiniz.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-auto ms-auto d-flex gap-2">
                      
                       
                        <a href="#" class="btn" data-bs-toggle="modal" data-bs-target="#modal-default">
                            <i class="ti ti-plus icon me-2"></i> Puantaj Türleri
                        </a>
                           <?php if ($Auths->hasPermission('puantaj_data_entry')) { ?>
                    <button href="" type="button" class="btn btn-primary float-end" onclick="puantaj_olustur()">
                        <i class="ti ti-device-floppy icon me-2"></i> Kaydet
                    </button>
                        <a class="btn btn-animate-icon btn-animate-icon-rotate" data-bs-toggle="modal" data-bs-target="#modal-statistics" title="İstatistikler">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-chart-dots-2"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M3 3v18h18" /><path d="M7 15a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M11 5a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M16 12a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M21 3l-6 1.5" /><path d="M14.113 6.65l2.771 3.695" /><path d="M16 12.5l-5 2" /></svg>
                        </a>
                        
                        <div class="dropdown">
                            <button type="button" class="btn btn-icon dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Sütunları Göster/Gizle">
                                <i class="ti ti-layout-columns icon"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-column-selector">
                                <h6 class="dropdown-header">Sütun Görünümü</h6>
                                <label class="dropdown-item cursor-pointer">
                                    <input type="checkbox" class="form-check-input me-2 column-toggle-check" data-column="extra-grup">
                                    İş Grubu
                                </label>
                                <label class="dropdown-item cursor-pointer">
                                    <input type="checkbox" class="form-check-input me-2 column-toggle-check" data-column="extra-ekip">
                                    Ekip
                                </label>
                            </div>
                        </div>

                        <div class="dropdown">
                            <button type="button" class="btn btn-icon dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="Ayarlar">
                                <i class="ti ti-settings icon"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-settings">
                                <h6 class="dropdown-header">Puantaj Ayarları</h6>
                                <div class="dropdown-item">
                                    <label class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="setting-auto-open-modal">
                                        <span class="form-check-label">Seçim Yapınca Puantaj Türleri Açılsın</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                <?php } ?>

                    </div>
                </div>



                <div class="table-responsive">
                    <table id="puantajTable" class="table card-table text-nowrap datatable">
                        <thead class="sticky">
                            <tr>
                                <th class="ld cursor-pointer" onclick="sortPuantaj(0)">Adı Soyadı</th>
                                <th class="ld cursor-pointer" style="width: 150px !important;" onclick="sortPuantaj(1)">Unvanı</th>
                                <th class="ld extra-column extra-grup cursor-pointer" style="display:none; width: 120px !important;" onclick="sortPuantaj(2)">İş Grubu</th>
                                <th class="ld extra-column extra-ekip cursor-pointer" style="display:none; width: 120px !important;" onclick="sortPuantaj(3)">Ekip</th>

                                <?php foreach ($dates as $date): ?>
                                    <?php
                                    $style = 'width: 40px !important; min-width: 40px !important;';
                                    $isSunday = (date('N', strtotime($date)) == 7);
                                    if ($isSunday) {
                                        $style .= 'background-color:#fee2e2 !important;color:#b91c1c !important;';
                                    } else if (Date::isWeekend($date)) {
                                        $style .= 'background-color:#99A98F;color:white;';
                                    }
                                    echo ' <th class="gunadi" style="' . $style . '">' . Date::gunadi($date) . '</th>';
                                    ?>
                                <?php endforeach; ?>
                            </tr>

                            <tr>
                                <th class="ld"></th>
                                <th class="ld" style="width: 150px !important;"></th>
                                <th class="ld extra-column extra-grup" style="display:none; width: 120px !important;"></th>
                                <th class="ld extra-column extra-ekip" style="display:none; width: 120px !important;"></th>

                                <?php foreach ($dates as $date): ?>
                                    <?php
                                    $style = 'width: 40px !important; min-width: 40px !important;';
                                    $isSunday = (date('N', strtotime($date)) == 7);
                                    if ($isSunday) {
                                        $style .= 'background-color:#fee2e2 !important;color:#b91c1c !important;';
                                    }
                                    echo '<th class="head-date" style="' . $style . '"><span>' . date('d', strtotime($date)) . '</span></th>';
                                    ?>
                                <?php endforeach; ?>
                            </tr>

                        </thead>
                        <tbody>
                            <?php
                            foreach ($persons as $person):

                                $id = Security::encrypt($person->id);

                                // Personelin işten ayrılma tarihi bu ayın başından önceyse personeli getirme
                                if ($person->job_end_date != null && $person->job_end_date != '') {
                                    $job_end_date_ymd = Date::Ymd($person->job_end_date);
                                    if ($job_end_date_ymd < Date::firstDay($month, $year)) {
                                        // continue; // Artık model seviyesinde yapıyoruz ama güvenlik için kalabilir
                                    }
                                }

                                // İş başlama/bitiş tarihlerini döngü dışında bir kez hesapla
                                $jobStartDate = str_replace('-', '', Date::Ymd($person->job_start_date));
                                $jobEndDate = str_replace('-', '', Date::Ymd($person->job_end_date));
                                if ($jobEndDate == '') {
                                    $jobEndDate = 99999999;
                                }

                                // Bu personelin aylık puantaj verisini cache'den al
                                $personPuantaj = $allPuantajData[$person->id] ?? [];

                                ?>
                                <tr>
                                    <td class="text-nowrap" data-id="<?php echo $id ?>"><a class="btn-user-modal"
                                             type="button">
                                            <a href="index.php?p=persons/manage&id=<?php echo $id ?>"
                                                target="_blank"><?php echo $person->full_name ?></a></td>

                                    <td class="text-nowrap" style="width: 150px !important;">
                                        <?php echo $person->job ?>
                                    </td>

                                    <td class="text-nowrap extra-column extra-grup" style="display:none; width: 120px !important;">
                                        <?php echo $person->job_group ?>
                                    </td>

                                    <td class="text-nowrap extra-column extra-ekip" style="display:none; width: 120px !important;">
                                        <?php echo $person->ekip ?>
                                    </td>
                                    <?php
                                    foreach ($dates as $date):
                                        $month_date = $date;

                                        if ($jobStartDate <= $month_date && $jobEndDate >= $month_date) {
                                            // Cache'den puantaj verisini al (tiresiz formatta)
                                            $dateKey = str_replace('-', '', $date);
                                            $puantajRecord = $personPuantaj[$dateKey] ?? null;
                                            $puantaj_id = $puantajRecord->puantaj_id ?? '';

                                            if ($puantaj_id >= 0 && $puantaj_id !== '') {
                                                $puantaj_project = $puantajRecord->project_id ?? 0;
                                                
                                                // Cache'den puantaj türü bilgisini al
                                                $puantajTuru = $allPuantajTurleri[$puantaj_id] ?? null;
                                                // Cache'den proje adını al
                                                $tooltip = $projectNamesCache[$puantaj_project] ?? "Proje Yok";

                                                if ($puantajTuru) {
                                                    if ($puantajTuru->PuantajKod == "HT") {
                                                        $backcolor = $puantajTuru->ArkaPlanRengi;
                                                        $color = $puantajTuru->FontRengi;
                                                        $selected = "";
                                                    } else {
                                                        if ($puantaj_project != $project_id) {
                                                            $backcolor = "#bbb";
                                                            $color = "#666";
                                                            $selected = "selected";
                                                        } else {
                                                            $backcolor = $puantajTuru->ArkaPlanRengi;
                                                            $color = $puantajTuru->FontRengi;
                                                            $selected = "";
                                                        }
                                                    }
                                                    echo "<td class='gun noselect $selected' data-tooltip ='$tooltip' data-change='false' data-project='" . $puantaj_project . "' data-id=" . $puantajTuru->id . " style='background:" . $backcolor . ";color:" . $color . "; min-width: 40px !important;'>" . $puantajTuru->PuantajKod . "</td>";
                                                } else {
                                                    echo "<td class='gun noselect' data-change='false' data-project='0' style='min-width: 40px !important;'></td>";
                                                }
                                            } else {
                                                if (Date::isWeekend($date)) {
                                                    // Hafta sonu varsayılan puantaj türü (53)
                                                    $weekendTuru = $allPuantajTurleri[53] ?? null;
                                                    if ($weekendTuru) {
                                                        echo "<td class='gun noselect' data-tooltip='' data-change='false' data-project='' data-id='53' style='background:" . $weekendTuru->ArkaPlanRengi . ";color:" . $weekendTuru->FontRengi . "; min-width: 40px !important;'>" . $weekendTuru->PuantajKod . "</td>";
                                                    } else {
                                                        echo "<td class='gun noselect' data-project='' style='min-width: 40px !important;'></td>";
                                                    }
                                                } else {
                                                    echo "<td class='gun noselect' data-project='' style='min-width: 40px !important;'></td>";
                                                }
                                            }
                                        } else {
                                            echo "<td class='noselect text-center' style='background:#ddd; min-width: 40px !important;'>---</td>";
                                        }
                                        ?>

                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        // DataTable yüklendikten sonra sütun genişliklerini ayarla
        setTimeout(function() {
            if ($.fn.DataTable.isDataTable('#puantajTable')) {
                $('#puantajTable').DataTable().columns.adjust().draw();
            }
        }, 500);

        // Pencere boyutu değiştiğinde de ayarla
        $(window).on('resize', function() {
            if ($.fn.DataTable.isDataTable('#puantajTable')) {
                $('#puantajTable').DataTable().columns.adjust();
            }
        });

        // Ayarların yüklenmesi
        const autoOpenSetting = localStorage.getItem('autoOpenPuantajTypes') === 'true';
        $('#setting-auto-open-modal').prop('checked', autoOpenSetting);

        $('#setting-auto-open-modal').on('change', function() {
            localStorage.setItem('autoOpenPuantajTypes', $(this).is(':checked'));
        });
    });
</script>
