<?php
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once ROOT . '/Model/IzinTalep.php';
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

if ( isset( $Auths ) ) {
    $Auths->checkFirmReturn();
}

$firm_id = ( int ) ( $_SESSION[ 'firm_id' ] ?? 0 );

$year = ( int ) ( isset( $_REQUEST[ 'year' ] ) ? $_REQUEST[ 'year' ] : ( $_COOKIE[ 'p_year' ] ?? date( 'Y' ) ) );
$month = ( int ) ( isset( $_REQUEST[ 'months' ] ) ? $_REQUEST[ 'months' ] : ( $_COOKIE[ 'p_months' ] ?? date( 'm' ) ) );
$last_day = Date::Ymd( Date::lastDay( $month, $year ) );
$project_ids = [];
if ( isset( $_REQUEST[ 'projects' ] ) ) {
    if ( is_array( $_REQUEST[ 'projects' ] ) ) {
        $project_ids = array_map( 'intval', $_REQUEST[ 'projects' ] );
    } else {
        $project_ids = array_filter( array_map( 'intval', explode( ',', $_REQUEST[ 'projects' ] ) ) );
    }
} elseif ( isset( $_COOKIE[ 'p_projects' ] ) && $_COOKIE[ 'p_projects' ] !== '' ) {
    $project_ids = array_filter( array_map( 'intval', explode( ',', $_COOKIE[ 'p_projects' ] ) ) );
}

$valid_project_ids = [];
foreach ( $project_ids as $p_id ) {
    if ( $p_id > 0 ) {
        if ( $projects->belongsToFirm( $p_id, $firm_id ) ) {
            $valid_project_ids[] = $p_id;
        }
    }
}

$job_group = ( int ) ( isset( $_REQUEST[ 'job_groups' ] ) ? $_REQUEST[ 'job_groups' ] : ( $_COOKIE[ 'p_job_groups' ] ?? 0 ) );
$team_id = isset( $_REQUEST[ 'team_id' ] ) ? $_REQUEST[ 'team_id' ] : ( $_COOKIE[ 'p_team_id' ] ?? '' );
$person_status = isset( $_REQUEST[ 'person_status' ] ) ? $_REQUEST[ 'person_status' ] : ( $_COOKIE[ 'p_person_status' ] ?? 'active' );
$only_active_project = ( int ) ( $_COOKIE[ 'p_only_active_project' ] ?? 0 );

require_once 'Model/SettingsModel.php';

$Settings = new SettingsModel();
$showWhiteCollar = $Settings->getSettings( 'show_white_collar_in_puantaj' )->set_value ?? 0;
$first_day = Date::firstDay( $month, $year );

if ( empty( $valid_project_ids ) ) {
    // Proje id boş ise Firma id'sine göre tüm mavi yakalı, işe başlama tarihi o ayın son gününden önce olan personelleri getirir
    // Akıllı görünürlük: Yeni başlayanlar veya bu ay puantajı olanlar her zaman görünür
    $persons = $personObj->getPersonIdByFirmBlueCollarCurrentMonth($firm_id, $first_day, $last_day, $job_group, $team_id, $showWhiteCollar, $person_status);
} else {
    // Proje id dolu ise projeye ait, işe başlama tarihi o ayın son gününden önce olan mavi yakalı personelleri getirir
    // Akıllı görünürlük: Projeye atanmış olanlar veya bu ay bu projede puantajı olanlar
    $persons = $projects->getPersonIdByFromProjectCurrentMonth($valid_project_ids, $first_day, $last_day, $job_group, $team_id, $showWhiteCollar, $person_status);
}
// Ayın son gününü bulma
$days = Date::daysInMonth($month, $year);
// Tarihleri oluşturma
$dates = Date::generateDates($year, $month, $days);

// ===== PERFORMANS OPTİMİZASYONU: Toplu veri çekme =====
// Tüm person ID'lerini topla
    $person_ids = array_map( function( $p ) {
        return $p->id;
    }
    , $persons );

    // 1 ) Tüm personellerin aylık puantaj verilerini TEK sorguda çek
    $first_day_ymd = Date::Ymd( Date::firstDay( $month, $year ) );
    $last_day_ymd = $last_day;
    // Hem tireli hem tiresiz formatta arama yapabilmek için geniş aralık
    $allPuantajData = $puantajObj->getAllPuantajForPersons( $person_ids, $first_day_ymd, str_replace( '-', '', $last_day_ymd ) );

    // 2 ) Tüm puantaj türlerini TEK sorguda çek ve cache'le
$allPuantajTurleri = $puantajObj->getAllPuantajTurleri();

// Onaylı izin günlerini yükle: [person_id][YYYY-MM-DD] = {kod, arkaplan, font, turu, puantaj_turu_id}
$izinModel = new IzinTalep();
$onayliIzinGunleri = $izinModel->getOnayliIzinGunleriToplu($person_ids, $first_day_ymd, $last_day_ymd);

// 3) Tüm proje isimlerini TEK sorguda çek ve cache'le
    $allProjects = $projects->getProjectsByFirm( $firm_id );
    $projectNamesCache = [];
    foreach ( $allProjects as $proj ) {
        $projectNamesCache[ $proj->id ] = $proj->project_name;
    }
    $projectNamesCache[ 0 ] = 'Proje Yok';

    // 4 ) Tüm personellerin proje üyeliklerini tek sorguda çek
    $personProjectsMap = [];
    if ( !empty( $person_ids ) ) {
        $placeholders = implode( ',', array_fill( 0, count( $person_ids ), '?' ) );
        $query = $projects->getDb()->prepare( "SELECT person_id, project_id FROM project_person WHERE person_id IN ($placeholders)" );
        $query->execute( $person_ids );
        $pp_results = $query->fetchAll( PDO::FETCH_OBJ );
        foreach ( $pp_results as $row ) {
            $personProjectsMap[ $row->person_id ][] = ( int )$row->project_id;
        }
    }
    // ===  == OPTİMİZASYON SONU ===  == ?>
<style>
/* Dynamic AJAX Top Progress Bar */
#puantaj-top-loading-bar {
    position: fixed;
    top: 0;
    left: 0;
    width: 0%;
    height: 3px;
    background: linear-gradient(90deg, #206bc4 0%, #4f96eb 50%, #206bc4 100%);
    z-index: 99999;
    transition: width 0.3s ease, opacity 0.3s ease;
    box-shadow: 0 0 10px rgba(32, 107, 196, 0.6);
    opacity: 0;
    pointer-events: none;
}

.gun {
    width: 35px;
    min-width: 35px;
    background-color: var(--tblr-bg-surface);
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
#puantajTable .extra-grup,
#puantajTable .extra-ekip,
#puantajTable .extra-unvan {
    width: 140px !important;
    min-width: 140px !important;
}

table#puantajTable.table {
    width: 100% !important;
    table-layout: auto !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    margin: 0 !important;
    border: 1px solid var(--tblr-border-color) !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

#puantajTable th,
#puantajTable td {
    border: 1px solid var(--tblr-border-color) !important;
    padding: 8px 6px !important;
    vertical-align: middle !important;
    white-space: nowrap !important;
}

/* First column greedy behavior */
#puantajTable th:nth-child(1),
#puantajTable td:nth-child(1) {
    width: auto !important;
    min-width: 220px !important;
}

#puantajTable th:nth-child(2),
#puantajTable td:nth-child(2) {
    width: auto !important;
    min-width: 160px !important;
}

#puantajTable th,
#puantajTable td {
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
.gunadi,
.head-date {
    width: 40px !important;
    min-width: 40px !important;
    max-width: 40px !important;
    padding: 4px 0 !important;
    text-align: center !important;
    overflow: hidden !important;
    white-space: nowrap !important;
    font-size: 13px !important;
}

.gun {
    width: 40px !important;
    min-width: 40px !important;
    max-width: 40px !important;
    padding: 4px 0 !important;
    text-align: center !important;
    white-space: nowrap !important;
    font-size: 13px !important;
}

.table tr td,
.table th {
    border: 1px solid var(--tblr-border-color) !important;
}

.gun.clicked {
    background-color: #FFED00 !important;
    cursor: pointer;
}

.gun.izin-kilitli {
    cursor: not-allowed !important;
    opacity: 0.85;
    background: #d4edda !important;
    color: #155724 !important;
}

.unclicked {
    background-color: var(--tblr-bg-surface);
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

    /* width: 100%;
        */
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

[ data-tooltip]:before {
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
    border-collapse: separate !important;
}

#puantajTable thead th {
    position: -webkit-sticky !important;
    position: sticky !important;
    background: var(--tblr-bg-surface) !important;
    z-index: 1000 !important;
    border-bottom: 1px solid var(--tblr-border-color) !important;
    border-right: 1px solid var(--tblr-border-color) !important;
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
    border: 1px solid var(--tblr-border-color);
}

/* Ayarlar dropdown stili */
.dropdown-menu-settings {
    min-width: 250px;
    padding: 0.5rem 0;
    z-index: 1060;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

/* Dropdown okunu ( caret ) icon-only butonlar için gizle */
.btn-icon.dropdown-toggle::after,
.btn-icon::after {
    display: none !important;
}

.cursor-pointer {
    cursor: pointer !important;
}

#puantajTable thead th.cursor-pointer:hover {
    background-color: var(--tblr-bg-surface-secondary) !important;
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
    z-index: 1050;
    /* Dropdown'ın her şeyin üstünde olması için */
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

    0%,
    100% {
        opacity: 1;
        transform: scale(1);
    }

    50% {
        opacity: .8;
        transform: scale(1.08);
    }
}

/* Select2 Multi-Select Custom Styling to match Tabler's .form-select */
.select2-container--default .select2-selection--multiple {
    border: 1px solid var(--tblr-border-color, #dadce0) !important;
    border-radius: 4px !important;
    /* Matches standard Tabler border-radius */
    background-color: var(--tblr-bg-surface, #fff) !important;
    min-height: 40px !important;
    /* Matches standard Tabler select2-selection--single height of 40px */
    height: 40px !important;
    /* Matches standard Tabler select2-selection--single height of 40px */
    padding: 0 2rem 0 0.75rem !important;
    /* Left-right padding, right padding room for arrow */
    display: flex !important;
    align-items: center !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%239ca3af' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    background-size: 16px 12px !important;
}

.select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #90bbf9 !important;
    outline: 0 !important;
    box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, 0.25) !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__rendered {
    display: flex !important;
    flex-wrap: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    white-space: nowrap !important;
    align-items: center !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
    list-style: none !important;
    gap: 0px !important;
    height: 100% !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline {
    display: flex !important;
    align-items: center !important;
    height: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field {
    margin: 0 !important;
    padding: 0 !important;
    background: transparent !important;
    border: none !important;
    outline: none !important;
    box-shadow: none !important;
    font-size: 0.875rem !important;
    font-family: inherit !important;
    color: #495057 !important;
    height: 100% !important;
    line-height: 40px !important;
    text-align: left !important;
}

.select2-container--default .select2-selection--multiple .select2-search--inline .select2-search__field::placeholder {
    color: #9ca3af !important;
    opacity: 1 !important;
    text-align: left !important;
}

/* Force Select2 search text and placeholder alignment to left */
.select2-container .select2-search__field,
.select2-container .select2-search__field::placeholder,
.select2-container .select2-selection__placeholder,
.select2-container .select2-selection__rendered,
.select2-search__field,
.select2-search__field::placeholder {
    text-align: left !important;
    direction: ltr !important;
}

/* Hide search input when there is at least one selection */
.select2-container--default .select2-selection--multiple:has(.select2-selection__choice) .select2-search--inline {
    display: none !important;
}

/* Ensure search input spans full width when empty to fully display placeholder */
.select2-container--default .select2-selection--multiple:not( :has(.select2-selection__choice)) .select2-search--inline,
.select2-container--default .select2-selection--multiple:not( :has(.select2-selection__choice)) .select2-search__field {
    width: 100% !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background: transparent !important;
    color: var(--tblr-body-color, #2d3748) !important;
    border: none !important;
    padding: 0 !important;
    font-size: 0.875rem !important;
    font-weight: 400 !important;
    display: inline-flex !important;
    align-items: center !important;
    margin: 0 !important;
    line-height: 1.4 !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice:not( :last-child)::after {
    content: ', ' !important;
    margin-right: 4px !important;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    display: none !important;
}

@media print {
    @page {
        size: landscape;
        margin: 8mm 8mm;
    }

    body {
        background-color: #fff !important;
        color: #000 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    /* Hide navbar, sidebar, header, filters, alerts, accordions, and action buttons */
    .navbar,
    aside,
    #navbar,
    .footer,
    .preloader,
    .fab-menu,
    .alert,
    .accordion-item,
    .card-header,
    .d-print-none,
    .page-header,
    .dt-length,
    .dt-search,
    .dt-info,
    .dt-paging,
    .dataTables_wrapper .row:first-child,
    .dataTables_wrapper .row:last-child,
    .dt-layout-row {
        display: none !important;
    }

    /* Expand full width and remove scrollbars */
    .page-wrapper,
    .container-xl,
    .container-fluid,
    .card,
    .card-body {
        padding: 0 !important;
        margin: 0 !important;
        border: none !important;
        background: transparent !important;
        box-shadow: none !important;
    }

    .table-responsive {
        max-height: none !important;
        overflow: visible !important;
        height: auto !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }

    #puantajTable {
        width: 100% !important;
        border-collapse: collapse !important;
        table-layout: auto !important;
    }

    #puantajTable thead th {
        position: static !important;
        background-color: #f1f5f9 !important;
        color: #1e293b !important;
    }

    #puantajTable thead input {
        display: none !important;
    }

    #puantajTable th,
    #puantajTable td {
        font-size: 10px !important;
        padding: 4px 2px !important;
    }

    #puantajTable th.gunadi,
    #puantajTable th.head-date,
    #puantajTable td.gun {
        width: 25px !important;
        min-width: 25px !important;
        max-width: 25px !important;
        font-size: 9px !important;
    }

    /* Make sure default first column is not too wide */
    #puantajTable th:nth-child(1),
    #puantajTable td:nth-child(1) {
        position: static !important;
        min-width: 120px !important;
        width: 120px !important;
        white-space: nowrap !important;
    }

    /* Keep color styles for cells */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

/* Seçili Tür Kısayolu ve Hover Efekti */
.selected-type-badge-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    border: 1px solid var(--tblr-border-color);
    border-radius: 4px;
    background: var(--tblr-bg-surface);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
    padding-right: 2px;
    transition: opacity 0.2s, border-style 0.2s, background-color 0.2s, border-color 0.2s;
}

.selected-type-badge-wrapper:hover {
    background-color: var(--tblr-bg-surface-secondary);
    border-color: var(--tblr-border-color-darker, #b0b0b0);
}

.selected-type-badge-wrapper #clear-selected-type {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 16px;
    height: 16px;
    background-color: #000 !important;
    color: #fff !important;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    font-weight: bold;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transition: opacity 0.15s ease, visibility 0.15s ease;
    border: 1px solid #fff;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
    z-index: 10;
    padding: 0;
}

.selected-type-badge-wrapper:hover #clear-selected-type {
    opacity: 1;
    visibility: visible;
}

#selected-type-code {
    padding: 7px 14px 7px 8px !important;
    font-size: 13px !important;
    border-radius: 4px !important;
    line-height: 1.2 !important;
    display: inline-block;
    min-width: 32px;
    text-align: center;
    border: none !important;
    box-shadow: none !important;
    transition: opacity 0.2s;
}

/* Adı Soyadı sütununu yatay kaydırmada sabit tut */
#puantajTable th:nth-child(1),
#puantajTable td:nth-child(1) {
    position: sticky !important;
    left: 0 !important;
    background-color: var(--tblr-bg-surface) !important;
    z-index: 3 !important;
}

#puantajTable thead tr:nth-child(1) th:nth-child(1) {
    z-index: 1020 !important;
}
#puantajTable thead tr:nth-child(2) th:nth-child(1) {
    z-index: 1019 !important;
}

/* Inactive State styling */
.selected-type-badge-wrapper.inactive {
    border-style: dashed !important;
    background-color: var(--tblr-bg-surface-secondary) !important;
    opacity: 0.6;
}

.selected-type-badge-wrapper.inactive #selected-type-code {
    background-color: transparent !important;
    color: var(--tblr-text-secondary) !important;
}

/* Select2 multi select tags custom styling to ensure clear buttons are always visible and beautiful */
.select2-container .select2-selection--multiple .select2-selection__choice {
    background-color: var(--bs-primary, #1b84ff) !important;
    border: 1px solid var(--bs-primary, #1b84ff) !important;
    color: #fff !important;
    padding: 4px 8px 4px 24px !important; /* Extra left padding for clear button */
    position: relative !important;
    display: inline-flex !important;
    align-items: center !important;
    border-radius: 4px !important;
    margin-top: 4px !important;
    margin-bottom: 4px !important;
    height: auto !important;
    line-height: 1.2 !important;
}
.select2-container .select2-selection--multiple .select2-selection__choice__remove {
    position: absolute !important;
    left: 4px !important;
    top: 50% !important;
    transform: translateY(-50%) !important;
    color: rgba(255, 255, 255, 0.9) !important;
    font-size: 14px !important;
    font-weight: bold !important;
    border: none !important;
    background: transparent !important;
    padding: 0 !important;
    margin: 0 !important;
    line-height: 1 !important;
    height: auto !important;
    width: auto !important;
    cursor: pointer !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
}
.select2-container .select2-selection--multiple .select2-selection__choice__remove span {
    display: inline !important;
    line-height: 1 !important;
}
.select2-container .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #ff4d4d !important;
    background-color: transparent !important;
}
</style>

<?php include_once 'content/puantaj-turleri-modal.php' ?>
<?php include_once 'content/puantaj-istatistik-modal.php' ?>
<?php include_once 'content/puantaj-excel-modal.php' ?>

<div class='container-fluid mt-3'>
    <form action='' method='post' id='puantajInfoForm'>
        <div class='row g-2 align-items-center d-print-none'>
            <div class='col-md-3'>
                <label for='projects' class='form-label'>Proje:</label>
                <?php echo $projectHelper->getProjectSelectMultiple( 'projects', $valid_project_ids ); ?>
            </div>
            <div class='col-md-1'>
                <label for='months' class='form-label'>Ay:</label>
                <?php echo Date::getMonthsSelect( 'months', $month );
        ?>
            </div>
            <div class='col-md-1'>
                <label for='year' class='form-label'>Yıl:</label>
                <?php echo Date::getYearsSelect( 'year', $year );
        ?>
            </div>
            <div class='col-md-2'>
                <label for='job_groups' class='form-label'>Grup:</label>
                <?php echo $jobsHelper->jobGroupsSelect( 'job_groups', $job_group );
        ?>
            </div>
            <div class='col-md-2'>
                <label for='team_id' class='form-label'>Ekip:</label>
                <?php echo $teamsHelper->teamsSelect( 'team_id', $team_id );
        ?>
            </div>
            <div class='col-md-3'>
                <label class='form-label'>Personel Durumu:</label>
                <div class='form-selectgroup'>
                    <label class='form-selectgroup-item'>
                        <input type='radio' name='person_status' value='all' class='form-selectgroup-input' <?php echo $person_status == 'all' ? 'checked' : '';
        ?>>
                        <span class='form-selectgroup-label'>
                            <i class='ti ti-users icon me-1'></i> Tümü
                        </span>
                    </label>
                    <label class='form-selectgroup-item'>
                        <input type='radio' name='person_status' value='active' class='form-selectgroup-input' <?php echo $person_status == 'active' ? 'checked' : '';
        ?>>
                        <span class='form-selectgroup-label'>
                            <i class='ti ti-user-check icon me-1 text-success'></i> Aktif
                        </span>
                    </label>
                    <label class='form-selectgroup-item'>
                        <input type='radio' name='person_status' value='passive' class='form-selectgroup-input' <?php echo $person_status == 'passive' ? 'checked' : '';
        ?>>
                        <span class='form-selectgroup-label'>
                            <i class='ti ti-user-x icon me-1 text-danger'></i> Pasif
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <div id='project-warning-bar' class='row d-none mt-2'>
            <div class='col-12'>
                <div class='alert alert-warning py-2 px-3 mb-0 d-flex align-items-center justify-content-between'
                    style='font-size: 12px; line-height: 1.4; border-radius: 4px;'>
                    <div class='d-flex align-items-center'>
                        <i class='ti ti-alert-triangle me-2'></i>
                        <span>Birden fazla proje seçildiğinde, yeni atamalar her personelin varsayılan projesine göre yapılacaktır.</span>
                    </div>
                    <button type='button' id='btn-clear-projects' class='btn btn-warning btn-sm py-1 px-2 border-0' style='font-size: 11px; font-weight: 600;'>
                        <i class='ti ti-x me-1'></i> Projeleri Temizle
                    </button>
                </div>
            </div>
        </div>
        <div id='project-empty-warning-bar' class='row d-none mt-2'>
            <div class='col-12'>
                <div class='alert alert-warning py-2 px-3 mb-0 d-flex align-items-center'
                    style='font-size: 12px; line-height: 1.4; border-radius: 4px; background-color: #fff3cd; border-color: #ffecb5; color: #664d03;'>
                    <i class='ti ti-alert-triangle me-2 fs-2' style='color: #664d03;'></i>
                    <span><strong>Proje Seçilmedi!</strong> Proje seçmeden de puantaj girişi yapabilirsiniz. Verileri projeye göre filtrelemek için en az bir proje seçiniz.</span>
                </div>
            </div>
        </div>
    </form>
</div>

<div class='container-fluid mt-3'>
    <div class='row row-deck row-cards'>
        <div class='col-12'>
            <div class='card'>
                <div class='card-header'>
                    <div class='accordion-item border-0' style='background: transparent;'>
                        <h2 class='accordion-header' id='heading-1'>
                            <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse'
                                data-bs-target='#collapse-1' aria-expanded='false'
                                style='background: transparent; box-shadow: none;'>
                                <div class='d-flex align-items-center'>
                                    <div class='bg-blue-lt text-blue d-flex align-items-center justify-content-center me-2 animate-pulse'
                                        style='width: 32px; height: 32px; border-radius: 8px;'>
                                        <i class='ti ti-info-circle fs-2'></i>
                                    </div>
                                    <div>
                                        <h3 class='card-title mb-0 '
                                            style='font-size: 14px; font-weight: 700; color: #1e293b;'>Puantaj İpuçları
                                            & Kısayollar</h3>
                                        <small class='text-muted font-weight-semibold m-0'
                                            style='font-size: 11px; cursor: pointer;'>
                                            Kullanım ipuçlarını ve kısayolları görmek
                                            için tıklayın!
                                        </small>
                                    </div>
                                </div>
                            </button>
                        </h2>
                        <div id='collapse-1' class='accordion-collapse collapse' data-bs-parent='#heading-1'>
                            <div class='accordion-body pt-3 pb-2 ps-2'
                                style='font-size: 13px; line-height: 1.6; color: #475569;'>
                                <div class='row g-3'>
                                    <div class='col-md-6'>
                                        <div class='mb-2 d-flex align-items-start'>
                                            <i class='ti ti-pointer text-blue me-2 mt-1 fs-3'></i>
                                            <span><strong>Tek Tek Seçim:</strong> İstediğiniz hücrenin üzerine tek
                                                tıklayarak seçebilirsiniz.</span>
                                        </div>
                                        <div class='mb-2 d-flex align-items-start'>
                                            <i class='ti ti-mouse text-blue me-2 mt-1 fs-3'></i>
                                            <span><strong>Çoklu Seçim:</strong> Sol tıklayıp basılı tutarak fareyi
                                                hücreler üzerinde sürükleyebilirsiniz.</span>
                                        </div>
                                        <div class='mb-2 d-flex align-items-start'>
                                            <i class='ti ti-arrows-down text-blue me-2 mt-1 fs-3'></i>
                                            <span><strong>Tüm Sütunu Seçme:</strong> En üstteki <strong>tarih
                                                    sayısına</strong> veya <strong>gün adına</strong> tıklayarak tüm
                                                personellerin o günkü hücresini seçebilirsiniz.</span>
                                        </div>
                                    </div>
                                    <div class='col-md-6'>
                                        <div class='mb-2 d-flex align-items-start'>
                                            <i class='ti ti-keyboard text-purple me-2 mt-1 fs-3'></i>
                                            <span><strong>Hızlı Menü ( Ctrl ):</strong> Seçim yaparken
                                                <strong>Ctrl</strong> tuşunu basılı tutarsanız, seçimi bıraktığınızda
                                                tür menüsü otomatik açılır.</span>
                                        </div>
                                        <div class='mb-2 d-flex align-items-start'>
                                            <i class='ti ti-trash text-danger me-2 mt-1 fs-3'></i>
                                            <span><strong>Puantaj Silme ( Delete ):</strong> İlgili hücreleri seçip
                                                klavyeden <strong>Delete</strong> tuşuna basın ve ardından
                                                <strong>Kaydet</strong> butonuna tıklayın.</span>
                                        </div>
                                        <div class='mb-2 d-flex align-items-start'>
                                            <i class='ti ti-x text-warning me-2 mt-1 fs-3'></i>
                                            <span><strong>Seçimleri Temizleme ( Esc ):</strong> Sarı renkli seçili
                                                hücreleri iptal etmek için klavyeden <strong>ESC</strong> tuşuna
                                                basabilirsiniz.</span>
                                        </div>
                                        <div class='mb-0 d-flex align-items-start'>
                                            <i class='ti ti-device-floppy text-success me-2 mt-1 fs-3'></i>
                                            <span><strong>Hızlı Kaydet ( Ctrl+S ):</strong> Çalışmalarınızı anında
                                                kaydetmek için klavyeden <strong>Ctrl + S</strong> tuş kombinasyonunu
                                                kullanabilirsiniz.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class='col-auto ms-auto d-flex gap-2 align-items-center'>
                        <div id='selected-type-container' class='d-none align-items-center gap-2'>
                            <span class='text-secondary' style='font-size: 13px; font-weight: 500;'>Seçili Tür:</span>
                            <div class='selected-type-badge-wrapper cursor-pointer' id='selected-type-toggle'>
                                <span id='selected-type-status-dot'
                                    style='width: 8px; height: 8px; border-radius: 50%; display: inline-block; background-color: #2fb344; box-shadow: 0 0 0 2px rgba(47, 179, 68, 0.2); transition: background-color 0.2s, box-shadow 0.2s; margin-left: 6px;'></span>
                                <span id='selected-type-code' class='fw-bold' title='Aktif/Pasif yapmak için tıklayın'
                                    style='margin-left: 2px;'></span>
                                <span id='clear-selected-type' title='Temizle'>
                                    <i class='ti ti-x'></i>
                                </span>
                            </div>
                        </div>

                        <a href='#' class='btn' data-bs-toggle='modal' data-bs-target='#modal-default'>
                            <i class='ti ti-plus icon me-2'></i> Puantaj Türleri
                        </a>

                        <?php if ( $Auths->hasPermission( 'puantaj_data_entry' ) ) {
            ?>
                        <button type='button' class='btn btn-primary float-end' onclick='puantaj_olustur()'>
                            <i class='ti ti-device-floppy icon me-2'></i> Kaydet
                        </button>
                        <?php }
            ?>

                        <div class='dropdown'>
                            <button type='button' class='btn btn-outline-secondary dropdown-toggle'
                                data-bs-toggle='dropdown' aria-haspopup='true' aria-expanded='false' title='İşlemler'>
                                <i class='ti ti-settings icon me-1'></i> İşlemler
                            </button>
                            <div class='dropdown-menu dropdown-menu-end dropdown-menu-settings dropdown-menu-column-selector'
                                style='min-width: 260px;'>
                                <h6 class='dropdown-header'>Tablo İşlemleri</h6>
                                <a class='dropdown-item cursor-pointer' id='export_excel_puantaj' href='#'>
                                    <i class='ti ti-file-type-xls text-success me-2 fs-3'></i> Excel Olarak İndir
                                </a>
                                <a class='dropdown-item cursor-pointer' id='print_puantaj' onclick='window.print()'
                                    href='#'>
                                    <i class='ti ti-printer text-primary me-2 fs-3'></i> Puantajı Yazdır
                                </a>

                                <?php if ( $Auths->hasPermission( 'puantaj_data_entry' ) ) {
                ?>
                                <a class='dropdown-item cursor-pointer' data-bs-toggle='modal'
                                    data-bs-target='#modal-statistics'>
                                    <svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 24 24'
                                        fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round'
                                        stroke-linejoin='round'
                                        class='icon icon-tabler icons-tabler-outline icon-tabler-chart-dots-2 text-warning me-2'
                                        style='margin: 0; width: 18px; height: 18px; vertical-align: middle;'>
                                        <path stroke='none' d='M0 0h24v24H0z' fill='none' />
                                        <path d='M3 3v18h18' />
                                        <path d='M7 15a2 2 0 1 0 4 0a2 2 0 1 0 -4 0' />
                                        <path d='M11 5a2 2 0 1 0 4 0a2 2 0 1 0 -4 0' />
                                        <path d='M16 12a2 2 0 1 0 4 0a2 2 0 1 0 -4 0' />
                                        <path d='M21 3l-6 1.5' />
                                        <path d='M14.113 6.65l2.771 3.695' />
                                        <path d='M16 12.5l-5 2' />
                                    </svg> İstatistikler
                                </a>

                                <div class='dropdown-divider'></div>
                                <h6 class='dropdown-header'>Sütun Görünümü</h6>
                                <label class='dropdown-item cursor-pointer'>
                                    <input type='checkbox' class='form-check-input me-2 column-toggle-check'
                                        data-column='extra-unvan'>
                                    Unvan
                                </label>
                                <label class='dropdown-item cursor-pointer'>
                                    <input type='checkbox' class='form-check-input me-2 column-toggle-check'
                                        data-column='extra-grup'>
                                    İş Grubu
                                </label>
                                <label class='dropdown-item cursor-pointer'>
                                    <input type='checkbox' class='form-check-input me-2 column-toggle-check'
                                        data-column='extra-ekip'>
                                    Ekip
                                </label>
                                <label class='dropdown-item cursor-pointer'>
                                    <input type='checkbox' class='form-check-input me-2 column-toggle-check'
                                        data-column='extra-toplam-gun' checked>
                                    Toplam Gün
                                </label>
                                <label class='dropdown-item cursor-pointer'>
                                    <input type='checkbox' class='form-check-input me-2 column-toggle-check'
                                        data-column='extra-toplam-fazla-mesai' checked>
                                    Toplam Fazla Mesai
                                </label>
                                <label class='dropdown-item cursor-pointer'>
                                    <input type='checkbox' class='form-check-input me-2 column-toggle-check'
                                        data-column='extra-project-totals' checked>
                                    Proje Toplam Günleri
                                </label>

                                <div class='dropdown-divider'></div>
                                <h6 class='dropdown-header'>Puantaj Ayarları</h6>
                                <div class='dropdown-item py-1'>
                                    <label class='form-check form-switch mb-0'>
                                        <input class='form-check-input' type='checkbox' id='setting-auto-open-modal'>
                                        <span class='form-check-label' style='font-size: 13px;'>Seçim Yapınca Türler
                                            Açılsın</span>
                                    </label>
                                </div>
                                <div class='dropdown-item py-1 mt-1'>
                                    <label class='form-check form-switch mb-0'>
                                        <input class='form-check-input' type='checkbox'
                                            id='setting-only-active-project'>
                                        <span class='form-check-label' style='font-size: 13px;'>Sadece Aktif Proje
                                            Günleri Topla</span>
                                    </label>
                                </div>
                                <?php }
                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class='d-none d-print-block mb-3'>
                    <h2 class='text-center'>PUANTAJ CETVELİ</h2>
                    <div class='text-center text-muted'>
                        <strong>Dönem:</strong> <?php echo Date::monthName( $month ) . ' ' . $year;
                ?>
                        | <strong>Projeler:</strong>
                        <?php
                if ( !empty( $valid_project_ids ) ) {
                    $selected_names = [];
                    foreach ( $valid_project_ids as $p_id ) {
                        if ( isset( $projectNamesCache[ $p_id ] ) ) {
                            $selected_names[] = $projectNamesCache[ $p_id ];
                        }
                    }
                    echo htmlspecialchars( implode( ', ', $selected_names ) );
                } else {
                    echo 'Tüm Projeler';
                }
                ?>
                    </div>
                </div>

                <div class='table-responsive'>
                    <table id='puantajTable' class='table card-table text-nowrap datatable'>
                        <thead class='sticky'>
                            <tr>
                                <th class='ld cursor-pointer' onclick='sortPuantaj(0)'>Adı Soyadı</th>
                                <th class='ld extra-column extra-unvan cursor-pointer'
                                    style='display:none; width: 150px !important;' onclick='sortPuantaj(1)'>Unvanı</th>
                                <th class='ld extra-column extra-grup cursor-pointer'
                                    style='display:none; width: 120px !important;' onclick='sortPuantaj(2)'>İş Grubu
                                </th>
                                <th class='ld extra-column extra-ekip cursor-pointer'
                                    style='display:none; width: 120px !important;' onclick='sortPuantaj(3)'>Ekip</th>

                                <?php foreach ( $dates as $date ): ?>
                                <?php
                $style = 'width: 40px !important; min-width: 40px !important;';
                $isSunday = ( date( 'N', strtotime( $date ) ) == 7 );
                if ( $isSunday ) {
                    $style .= 'background-color:#fee2e2 !important;color:#b91c1c !important;';
                } else if ( Date::isWeekend( $date ) ) {
                    $style .= 'background-color:#99A98F;color:white;';
                }
                echo ' <th class="gunadi" style="' . $style . '">' . Date::gunadi( $date ) . '</th>';
                ?>
                                <?php endforeach;
                ?>
                                <th class='ld extra-column extra-toplam-gun text-center'
                                    style='width: 80px !important;'>Toplam Gün</th>
                                <th class='ld extra-column extra-toplam-fazla-mesai text-center'
                                    style='width: 80px !important;'>Toplam FM</th>
                                <?php foreach ( $allProjects as $proj ): ?>
                                <th class='ld extra-column extra-project-totals text-center'
                                    style='width: 90px !important;'><?php echo htmlspecialchars($proj->project_name); ?></th>
                                <?php endforeach; ?>
                                <th class='ld extra-column extra-project-totals text-center'
                                    style='width: 90px !important;'>Proje Yok</th>
                            </tr>

                            <tr>
                                <th class='ld'></th>
                                <th class='ld extra-column extra-unvan' style='display:none; width: 150px !important;'>
                                </th>
                                <th class='ld extra-column extra-grup' style='display:none; width: 120px !important;'>
                                </th>
                                <th class='ld extra-column extra-ekip' style='display:none; width: 120px !important;'>
                                </th>

                                <?php foreach ( $dates as $date ): ?>
                                <?php
                $style = 'width: 40px !important; min-width: 40px !important;';
                $isSunday = ( date( 'N', strtotime( $date ) ) == 7 );
                if ( $isSunday ) {
                    $style .= 'background-color:#fee2e2 !important;color:#b91c1c !important;';
                }
                echo '<th class="head-date" style="' . $style . '"><span>' . date( 'd', strtotime( $date ) ) . '</span></th>';
                ?>
                                <?php endforeach;
                ?>
                                <th class='ld extra-column extra-toplam-gun' style='width: 80px !important;'></th>
                                <th class='ld extra-column extra-toplam-fazla-mesai' style='width: 80px !important;'>
                                </th>
                                <?php foreach ( $allProjects as $proj ): ?>
                                <th class='ld extra-column extra-project-totals' style='width: 90px !important;'></th>
                                <?php endforeach; ?>
                                <th class='ld extra-column extra-project-totals' style='width: 90px !important;'></th>
                            </tr>

                        </thead>
                        <tbody>
                            <?php
                foreach ( $persons as $person ):

                $id = Security::encrypt( $person->id );

                // Personelin işten ayrılma tarihi bu ayın başından önceyse personeli getirme
                if ( $person->job_end_date != null && $person->job_end_date != '' ) {
                    $job_end_date_ymd = Date::Ymd( $person->job_end_date );
                    if ( $job_end_date_ymd < Date::firstDay( $month, $year ) ) {
                        // continue;
                        // Artık model seviyesinde yapıyoruz ama güvenlik için kalabilir
                    }
                }

                // İş başlama/bitiş tarihlerini döngü dışında bir kez hesapla
                $jobStartDate = str_replace( '-', '', Date::Ymd( $person->job_start_date ) );
                $jobEndDate = str_replace( '-', '', Date::Ymd( $person->job_end_date ) );
                if ( $jobEndDate == '' ) {
                    $jobEndDate = 99999999;
                }

                // Bu personelin aylık puantaj verisini cache'den al
                                $personPuantaj = $allPuantajData[$person->id] ?? [];

                                // Calculate totals for this person
                                $totalDays = 0;
                                $totalOvertime = 0;
                                $personProjDays = [];
                                $countedTalepIds = [];
                                $deductableCount = 0;
                                foreach ($dates as $date) {
                                    if ($jobStartDate <= $date && $jobEndDate >= $date) {
                                        $dateYmdCalc = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
                                        $izinBilgiCalc = $onayliIzinGunleri[$person->id][$dateYmdCalc] ?? null;
                                        if ($izinBilgiCalc) {
                                            if (!isset($countedTalepIds[$izinBilgiCalc->talep_id])) {
                                                $countedTalepIds[$izinBilgiCalc->talep_id] = true;
                                                if (stripos($izinBilgiCalc->turu, 'ücretsiz') === false) {
                                                    $totalDays += $izinBilgiCalc->gun_sayisi;
                                                    $personProjDays[0] = ($personProjDays[0] ?? 0) + $izinBilgiCalc->gun_sayisi;
                                                } else {
                                                    $deductableCount += $izinBilgiCalc->gun_sayisi;
                                                }
                                            }
                                            continue;
                                        }

                                        $dateKey = str_replace('-', '', $date);
                                        $puantajRecord = $personPuantaj[$dateKey] ?? $personPuantaj[$date] ?? null;
                                        $puantaj_id = $puantajRecord->puantaj_id ?? '';

                                        if ($puantaj_id >= 0 && $puantaj_id !== '') {
                                            $puantaj_project = (int) ($puantajRecord->project_id ?? 0);
                                            $should_count = true;
                                            if ($only_active_project && !empty($valid_project_ids)) {
                                                if (!in_array($puantaj_project, $valid_project_ids)) {
                                                    $should_count = false;
                                                }
                                            }

                                            if ($should_count) {
                                                $puantajTuru = $allPuantajTurleri[$puantaj_id] ?? null;
                                                if ($puantajTuru) {
                                                    if ($person->wage_type == 1) {
                                                        if (!empty($puantajTuru->is_deductable)) {
                                                            $deductableCount++;
                                                        } else {
                                                            $totalDays++;
                                                            $personProjDays[$puantaj_project] = ($personProjDays[$puantaj_project] ?? 0) + 1;
                                                        }
                                                    } else {
                                                        if ($puantajTuru->Turu != 'Ücretsiz') {
                                                            $totalDays++;
                                                            $personProjDays[$puantaj_project] = ($personProjDays[$puantaj_project] ?? 0) + 1;
                                                        }
                                                    }
                                                    if ($puantajTuru->Turu == 'Fazla Çalışma') {
                                                        $totalOvertime += floatval($puantajTuru->EklenecekSaat);
                                                    }
                                                }
                                            }
                                        } else {
                                            if ($person->wage_type == 1) {
                                                if (Date::isWeekend($date)) {
                                                    $totalDays++;
                                                    $personProjDays[0] = ($personProjDays[0] ?? 0) + 1;
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($person->wage_type == 1) {
                                    if ($deductableCount == 0) {
                                        $totalDays = 30;
                                    } else {
                                        $totalDays = max(0, 30 - $deductableCount);
                                    }
                                }

                                // Calculate default project ID for this person
                                $default_project_id = 0;
                                $person_projects = $personProjectsMap[$person->id] ?? [];
                                if (!empty($valid_project_ids)) {
                                    $intersect = array_intersect($person_projects, $valid_project_ids);
                                    if (!empty($intersect)) {
                                        $default_project_id = reset($intersect);
                                    } else {
                                        // Check existing puantaj this month
                                        foreach ($personPuantaj as $dateKey => $puantajRecord) {
                                            if (isset($puantajRecord->project_id) && $puantajRecord->project_id > 0 && in_array($puantajRecord->project_id, $valid_project_ids)) {
                                                $default_project_id = (int)$puantajRecord->project_id;
                                                break;
                                            }
                                        }
                                        if ($default_project_id == 0) {
                                            $default_project_id = $valid_project_ids[0];
                                        }
                                    }
                                } else {
                                    if (!empty($person_projects)) {
                                        $default_project_id = $person_projects[0];
                                    } else {
                                        foreach ($personPuantaj as $dateKey => $puantajRecord) {
                                            if (isset($puantajRecord->project_id) && $puantajRecord->project_id > 0) {
                                                $default_project_id = (int)$puantajRecord->project_id;
                                                break;
                                            }
                                        }
                                    }
                                }
                                ?>
                            <tr data-default-project="<?php echo $default_project_id; ?>">
                                <td class="text-nowrap" data-id="<?php echo $id ?>">
                                    <a href="index.php?p=persons/manage&id=<?php echo $id ?>"
                                        target="_blank"><?php echo $person->full_name ?></a>
                                </td>

                                <td class="text-nowrap extra-column extra-unvan"
                                    style="display:none; width: 150px !important;">
                                    <?php echo $person->job ?>
                                </td>

                                <td class="text-nowrap extra-column extra-grup"
                                    style="display:none; width: 120px !important;">
                                    <?php echo $person->job_group ?>
                                </td>

                                <td class="text-nowrap extra-column extra-ekip"
                                    style="display:none; width: 120px !important;">
                                    <?php echo $person->ekip ?>
                                </td>
                                <?php
                                    foreach ($dates as $date):
                                        $month_date = $date;

                                        if ($jobStartDate <= $month_date && $jobEndDate >= $month_date) {
                                            // Cache'den puantaj verisini al ( tiresiz formatta )
                $dateKey = str_replace( '-', '', $date );
                $dateYmd  = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
                $puantajRecord = $personPuantaj[ $dateKey ] ?? null;
                $puantaj_id = $puantajRecord->puantaj_id ?? '';

                $izinBilgi = $onayliIzinGunleri[$person->id][$dateYmd] ?? null;

                if ($izinBilgi) {
                    $izKod = htmlspecialchars($izinBilgi->kod);
                    echo "<td class='gun noselect izin-kilitli' data-izin-kilitli='1' data-change='false' data-project='0' data-id='{$izinBilgi->puantaj_turu_id}' title='Onaylı izin — düzenlenemez' style='background:{$izinBilgi->arkaplan};color:{$izinBilgi->font};min-width:40px !important;'>{$izKod}</td>";
                } elseif ( $puantaj_id >= 0 && $puantaj_id !== '' ) {
                    $puantaj_project = $puantajRecord->project_id ?? 0;
                    $puantajTuru = $allPuantajTurleri[$puantaj_id] ?? null;
                    $tooltip = $projectNamesCache[ $puantaj_project ] ?? 'Proje Yok';

                    if ( $puantajTuru ) {
                        if ( $puantajTuru->PuantajKod == 'HT' ) {
                            $backcolor = $puantajTuru->ArkaPlanRengi;
                            $color = $puantajTuru->FontRengi;
                            $selected = '';
                        } else {
                            if ( count( $valid_project_ids ) === 1 && !in_array( $puantaj_project, $valid_project_ids ) ) {
                                $backcolor = '#bbb';
                                $color = '#666';
                                $selected = 'selected';
                            } else {
                                $backcolor = $puantajTuru->ArkaPlanRengi;
                                $color = $puantajTuru->FontRengi;
                                $selected = '';
                            }
                        }
                        echo "<td class='gun noselect $selected' data-change='false' data-project='" . $puantaj_project . "' data-id=" . $puantajTuru->id . " title='$tooltip' data-tooltip='$tooltip' style='background:" . $backcolor . ';color:' . $color . "; min-width: 40px !important;'>" . $puantajTuru->PuantajKod . '</td>';
                    } else {
                        echo "<td class='gun noselect' data-change='false' data-project='0' style='min-width: 40px !important;'></td>";
                    }
                } else {
                    if ( Date::isWeekend( $date ) ) {
                        $weekendTuru = $allPuantajTurleri[ 53 ] ?? null;
                        if ( $weekendTuru ) {
                            echo "<td class='gun noselect' data-tooltip='' data-change='false' data-project='' data-id='53' style='background:" . $weekendTuru->ArkaPlanRengi . ';color:' . $weekendTuru->FontRengi . "; min-width: 40px !important;'>" . $weekendTuru->PuantajKod . '</td>';
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

                                <?php endforeach;
            ?>
                                <td class='text-center extra-column extra-toplam-gun td-toplam-gun fw-semibold'
                                    style='width: 80px !important;'><?php echo $totalDays;
            ?></td>
                                <td class='text-center extra-column extra-toplam-fazla-mesai td-toplam-fazla-mesai text-danger fw-bold'
                                    style='width: 80px !important;'>
                                    <?php echo $totalOvertime > 0 ? str_replace( '.0', '', ( string )$totalOvertime ) : '0';
            ?>
                                </td>
                                <?php foreach ( $allProjects as $proj ): ?>
                                <td class='text-center extra-column extra-project-totals fw-semibold'
                                    style='width: 90px !important;'>
                                    <?php echo $personProjDays[$proj->id] ?? 0; ?>
                                </td>
                                <?php endforeach; ?>
                                <td class='text-center extra-column extra-project-totals fw-semibold text-warning'
                                    style='width: 90px !important;'>
                                    <?php echo ($personProjDays[0] ?? 0) + ($personProjDays[''] ?? 0); ?>
                                </td>
                            </tr>
                            <?php endforeach;
            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>
<script>
var allPuantajTurleri = <?php echo json_encode( $allPuantajTurleri );
            ?>;
var workHour = <?php echo json_encode( $Settings->getSettings( 'work_hour' )->set_value ?? 8 );
            ?>;

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