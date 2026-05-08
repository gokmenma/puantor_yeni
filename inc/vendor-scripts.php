

<!-- <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script> -->


<?php




$page = isset($_GET['p']) ? $_GET['p'] : '';



if (
    $page == 'companies/list' ||
    $page == 'offers/list' ||
    $page == 'reports/list' ||
    $page == 'users/list' ||
    $page == 'users/roles/list' ||
    $page == 'products/list' ||
    $page == 'defines/service-head/list' ||
    $page == 'persons/list' ||
    $page == 'persons/manage' ||
    $page == 'mycompany/list' ||
    $page == 'financial/case/list' || $page == 'financial/case/manage' ||
    $page == 'financial/transactions/list' ||
    $page == 'financial/transactions/manage' ||
    $page == 'projects/list' || $page == 'projects/manage' ||
    $page == 'projects/add-person' ||
    $page == 'puantaj/list' ||
    $page == 'payroll/list' ||
    $page == 'defines/incexp/list' ||
    $page == 'missions/list' || $page == 'missions/process/list' ||
    $page == 'missions/headers/manage' || $page == 'missions/headers/list' ||
    $page == 'defines/job-groups/list' || $page == 'defines/job-groups/manage' ||
    $page == 'defines/project-status/list' ||
    $page == 'todos/list'
) {
    echo '<script src="./dist/libs/datatable/datatables.min.js"></script>';
}




//Summernote eklenecek sayfalar 
if (
    $page == "missions/manage" || $page == "feedback/list"
    || $page == "supports/tickets" || $page == "supports/ticket-view"
) {
    echo '<script src="./dist/libs/summernote/summernote-lite.min.js?1692870487"></script>';
}


// Kullanıcı ekleme ve düzenleme sayfası
if ($page == 'users/list' || $page == 'users/manage') {
    echo '<script src="./src/users/users.js"></script>';
}

// Kullanıcı rolü ekleme ve düzenleme sayfası
if ($page == 'users/roles/list' || $page == 'users/roles/manage') {
    echo '<script src="./src/users/roles.js"></script>';
}

//Role Yetkileri ekleme ve düzenleme sayfası
if ($page == 'users/auths/auths') {
    echo '<script src="./src/users/auths.js"></script>';
}

// Ürün ekleme ve düzenleme sayfası
if ($page == 'products/list' || $page == 'products/manage') {
    echo '<script src="./src/product.js"></script>';
}

// Servis Konusu ekleme ve düzenleme sayfası
if ($page == 'defines/service-head/list' || $page == 'defines/service-head/manage') {
    echo '<script src="./src/defines/service-head.js"></script>';
}
// Personel Liste, ekleme ve düzenleme sayfası
if ($page == 'persons/list' || $page == 'persons/manage') {
    echo '<script src="./src/persons/persons.js?v=' . time() . '"></script>';
}
// Personel diğer bilgileri ekleme ve düzenleme sayfası
if ($page == 'persons/manage') {
    echo '<script src="./src/persons/payment.js"></script>';
    echo '<script src="./src/persons/wages.js"></script>';
    echo '<script src="./src/persons/income.js"></script>';
    echo '<script src="./src/persons/wage-cut.js"></script>';
}

// Servis Konusu ekleme ve düzenleme sayfası
if ($page == 'mycompany/list' || $page == 'mycompany/manage') {
    echo '<script src="./src/companies/mycompanies.js"></script>';
}

if ($page == 'companies/list' || $page == 'companies/manage') {
    echo '<script src="./src/companies/companies.js"></script>';
}

// Kasa (kasa ekleme ve düzenleme sayfası)
if ($page == 'financial/case/list' || $page == 'financial/case/manage') {
    echo '<script src="./src/financial/case.js"></script>';
}
// Kasa İşlemleri(kasa ekleme ve düzenleme sayfası)
if ($page == 'financial/transactions/list') {
    echo '<script src="./src/financial/transactions.js"></script>';
}
// Proje Ekleme,güncelleme ve listeleme sayfası
if ($page == 'projects/list' || $page == 'projects/manage' || $page == 'projects/add-person') {
    echo '<script src="./src/project/projects.js"></script>';
    echo '<script src="./src/project/progress-payment.js"></script>';
    echo '<script src="./src/project/payment.js"></script>';
    echo '<script src="./src/project/expense.js"></script>';
    echo '<script src="./src/project/deduction.js"></script>';
}
// Bordro sayfası
if ($page == 'payroll/list') {
    echo '<script src="./src/bordro/bordro.js"></script>';
    echo '<script src="./src/bordro/payment.js"></script>';
    echo '<script src="./src/bordro/wage_cut.js"></script>';
    echo '<script src="./src/bordro/income.js"></script>';

}
// Gelir Gider Türü Tanımlama
if ($page == 'defines/incexp/list' || $page == 'defines/incexp/manage') {
    echo '<script src="./src/defines/incexp.js"></script>';
}


// Misyon Ekleme,güncelleme ve listeleme sayfası
if ($page == 'missions/list' || $page == 'missions/manage') {
    echo '<script src="./src/missions/missions.js"></script>';
}

// Misyon İşlem Ekleme,güncelleme ve listeleme sayfası
if ($page == 'missions/process/manage') {
    echo '<script src="./src/missions/process.js"></script>';
}

if ($page == 'missions/headers/manage' || $page == "home") {
    echo '<script src="./dist/js/jquery-ui.js"></script>';
}


if ($page == 'missions/headers/manage') {
    echo '<script src="./src/missions/headers.js"></script>';
}

if ($page == 'payroll/xls/payment-load-from-xls') {
    echo '<script src="./src/bordro/payment-load.js"></script>';
}
//personlleri excel dosyasından yükleme
if ($page == 'persons/xls/person-load') {
    echo '<script src="./src/persons/persons-load.js"></script>';
}

if ($page == 'defines/job-groups/list' || $page == 'defines/job-groups/manage') {
    echo '<script src="./src/defines/job-groups.js"></script>';
}

if ($page == 'settings/manage') {
    echo '<script src="./src/settings/settings.js"></script>';
    echo '<script src="./src/settings/packages.js"></script>';
}

if ($page == 'feedback/list') {
    echo '<script src="./src/feedback.js"></script>';
}

if ($page == 'supports/tickets' || $page == 'supports/ticket-view') {
    echo '<script src="./src/supports/tickets.js"></script>';
}

//Proje durumları
if ($page == 'defines/project-status/list' || $page == 'defines/project-status/manage') {
    echo '<script src="./src/defines/project-status.js"></script>';
}

if ($page == 'gorevler/list') {
    echo '<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>';
    echo '<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>';
    echo '<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">';
    echo '<script src="pages/gorevler/js/gorevler.js?v=' . time() . '"></script>';
}


?>







<?php


if ($page == 'home') {
    //echo '<script src="./dist/libs/apexcharts/dist/apexcharts.min.js" defer></script>';
    echo '<script src="./dist/libs/jsvectormap/dist/js/jsvectormap.min.js" defer></script>';
    echo '<script src="./dist/libs/jsvectormap/dist/maps/world.js" defer></script>';
    echo '<script src="./dist/libs/jsvectormap/dist/maps/world-merc.js" defer></script>';
    echo '<script src="./src/charts.js" defer></script>';
    echo '<script src="./src/home/missions.js"></script>';
}
?>

<script src="./dist/js/flatpickr.min.js"></script>
<script src="./dist/js/flatpickr.tr.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.16.9/xlsx.full.min.js"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script> -->
<script src="https://npmcdn.com/flatpickr/dist/l10n/tr.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery-validation@1.19.5/dist/jquery.validate.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="./dist/libs/select2/js/select2.min.js?1724846371"></script>
<!-- Tabler Core -->
<script src="./dist/js/tabler.min.js?1692870487"></script>
<!-- <script src="./dist/js/demo.min.js?1692870487"></script> -->
<script src="./src/jquery.inputmask.js"></script>



<script src="./src/app.js" defer??></script>
<?php 
if ($page == 'puantaj/list') {
    echo '<script src="./src/puantaj/puantaj.js?v=' . time() . '"></script>';
}
?>