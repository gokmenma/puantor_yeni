<?php
require_once "App/Helper/date.php";
require_once "App/Helper/projects.php";
require_once "App/Helper/bordroHelper.php";

use App\Helper\Date;
$projectHelper = new ProjectHelper();
$bordroHelper = new BordroHelper();


$year = isset($_POST['year']) ? $_POST['year'] : date('Y');
$month = isset($_POST['months']) ? $_POST['months'] : date('m');
$project_id = isset($_POST['projects']) ? $_POST['projects'] : 0;

//Sayfaya erişim yetkisi kontrolü
$Auths->checkAuthorize('upload_payment_permission');

?>


<div class="container-xl mt-3">

    <form action="" method="post" id="paymentLoadForm">


        <div class="row">
            <div class="col-md-3">
                <label for="projects" class="form-label">Proje:</label>
                <?php echo $projectHelper->getProjectSelect('projects', $project_id); ?>
            </div>
            <div class="col-3">
                <label for="months" class="form-label">Ay:</label>
                <?php echo Date::getMonthsSelect('months', $month); ?>
            </div>
            <div class="col-3">
                <label for="year" class="form-label">Yıl:</label>
                <?php echo Date::getYearsSelect('year', $year); ?>
            </div>
            <div class="col-3">
                <label for="year" class="form-label">Kategori:</label>
                <?php echo $bordroHelper->getIncExpSelectByFirmAndType() ?>
            </div>
            <div class="col-auto me-auto mt-auto d-flex">


            </div>


        </div>
        <div class="row mt-3">
            <div class="col-md-9">
                <label for="file" class="form-label">Dosya:</label>
                <input type="file" name="payment-load-file" id="payment-load-file" class="form-control">
            </div>

            <div class="col-md-3 me-auto mt-auto d-flex">


                <label for="" class="form-label"></label>
                <a href="#" class="btn btn-primary me-2" id="paymentLoadButton" data-tooltip="Excele Aktar">
                    <i class="ti ti-file-excel icon"></i> Yükle
                </a>
                <label for="İndir"></label>
                <a href="pages/payroll/xls/payment-load.php" class="btn me-2" data-tooltip="Yüklenecek Şablonu indirin">
                    <i class="ti ti-file-excel icon"></i> Örnek Dosya İndir
                </a>
                <a href="#" class="btn btn-ghost-danger me-2 clear" data-tooltip="Formu Temizleyin">
                    <i class="ti ti-trash icon"></i> Temizle
                </a>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header">
                <h5>Ödeme Bilgileri</h5>
            </div>
            <div class="card-body">

                <div class="row">
                    <div id="result">
                        <table class="table" id="payment-load-table">
                            <thead>
                                <tr>
                                    <th>id</th>
                                    <th>Ad Soyad</th>
                                    <th>Ödeme Günü</th>
                                    <th>Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr></tr>
                            </tbody>
                        </table>

                    </div>
                </div>
            </div>
        </div>
    </form>
</div>