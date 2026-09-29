<?php
require_once "App/Helper/date.php";
require_once "App/Helper/projects.php";
require_once "App/Helper/bordroHelper.php";

if (!isset($Auths)) {
    require_once ROOT . '/Model/Auths.php';
    $Auths = new Auths();
}
// Sayfaya erişim yetkisi kontrolü
$Auths->checkAuthorize('upload_payment_permission');

use App\Helper\Date;
$projectHelper = new ProjectHelper();
$bordroHelper = new BordroHelper();

$year = isset($_POST['year']) ? (int)$_POST['year'] : (int)($_SESSION['period_year'] ?? date('Y'));
$month = isset($_POST['months']) ? (int)$_POST['months'] : (int)($_SESSION['period_month'] ?? date('m'));
$project_id = isset($_POST['projects']) ? (int)$_POST['projects'] : 0;
?>

<div class="container-xl mt-1" id="paymentLoadPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-info-lt text-info shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-table-import" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Ödeme Yükleme (Excel)
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Excel şablonu ile toplu ödeme girişi, veri doğrulama ve bordroya aktarım
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="pages/payroll/xls/payment-load.php" class="btn btn-sm btn-outline-info shadow-sm" style="height: 32px; padding: 4px 10px; font-size: 12.5px; border-radius: 6px;" data-tooltip="Yüklenecek boş şablonu indirin">
                        <i class="ti ti-file-download me-1"></i> Şablon İndir (.xls)
                    </a>
                    <a href="#" class="btn btn-sm btn-dark route-link shadow-sm" data-page="payroll/list" style="background-color: #1e293b; border-color: #1e293b; height: 32px; padding: 4px 12px; font-size: 12.5px; border-radius: 6px;">
                        <i class="ti ti-arrow-left me-1"></i> Bordro Listesi
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Alert -->
    <div class="alert alert-info border-0 shadow-sm rounded-3 mb-3 p-3" style="background-color: #f0f9ff; border-left: 4px solid #0ea5e9 !important;">
        <div class="d-flex align-items-start gap-3">
            <div class="avatar avatar-sm rounded-2 bg-info-lt text-info flex-shrink-0" style="width: 32px; height: 32px;">
                <i class="ti ti-info-circle" style="font-size: 18px;"></i>
            </div>
            <div>
                <h4 class="alert-title mb-1 text-dark fw-bold" style="font-size: 13.5px;">Ödeme Yükleme Rehberi</h4>
                <div class="text-secondary small" style="line-height: 1.5; font-size: 12px;">
                    <ul class="mb-0 ps-3">
                        <li><strong>1. Şablon İndir:</strong> Sağ üstteki <strong>"Şablon İndir"</strong> butonuna basarak güncel personel listesini içeren Excel dosyasını indirin.</li>
                        <li><strong>2. Tutarları Girin:</strong> İndirilen şablonda personellerin ödeme tutarlarını ve varsa ödeme günlerini doldurun. Sütun başlıklarını değiştirmeyin.</li>
                        <li><strong>3. Yükleyin:</strong> Aşağıdaki formdan ilgili Proje, Dönem ve Ödeme Kategorisini seçip dosyayı yükleyin.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <form action="" method="post" id="paymentLoadForm">
        <!-- Form Kartı -->
        <div class="card mb-3" style="border: 1px solid #dbe3ec !important; box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important; border-radius: 12px; overflow: hidden; background: #fff;">
            <div class="card-header py-2 px-3 bg-light-subtle d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #f1f5f9;">
                <div class="d-flex align-items-center gap-2">
                    <div class="card-header-icon" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; background: #e0f2fe; color: #0284c7; border-radius: 6px;">
                        <i class="ti ti-adjustments-horizontal" style="font-size: 16px;"></i>
                    </div>
                    <h5 class="card-title mb-0 fw-bold" style="font-size: 13.5px;">Yükleme Kriterleri ve Dosya Seçimi</h5>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="projects" class="form-label fw-semibold small text-secondary mb-1">Proje Seçimi:</label>
                        <?= $projectHelper->getProjectSelect('projects', $project_id, 'Tüm Projeler') ?>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="months" class="form-label fw-semibold small text-secondary mb-1">Dönem Ayı:</label>
                        <?= Date::getMonthsSelect('months', $month) ?>
                    </div>
                    <div class="col-6 col-md-2">
                        <label for="year" class="form-label fw-semibold small text-secondary mb-1">Dönem Yılı:</label>
                        <?= Date::getYearsSelect('year', $year) ?>
                    </div>
                    <div class="col-md-5">
                        <label for="payment_inc_exp_type" class="form-label fw-semibold small text-secondary mb-1">Ödeme Kategorisi / Türü:</label>
                        <?= $bordroHelper->getIncExpSelectByFirmAndType() ?>
                    </div>
                </div>

                <div class="row g-3 align-items-end mt-1">
                    <div class="col-md-8">
                        <label for="payment-load-file" class="form-label fw-semibold small text-secondary mb-1">Excel Dosyası Seçin (.xls, .xlsx):</label>
                        <input type="file" name="payment-load-file" id="payment-load-file" class="form-control" accept=".xls,.xlsx">
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <a href="#" class="btn btn-primary d-inline-flex align-items-center shadow-sm" id="paymentLoadButton" style="height: 36px; padding: 4px 14px; font-size: 12.5px; border-radius: 6px;">
                                <i class="ti ti-upload icon me-1"></i> Dosyayı Yükle
                            </a>
                            <a href="#" class="btn btn-outline-danger d-inline-flex align-items-center clear" style="height: 36px; padding: 4px 12px; font-size: 12.5px; border-radius: 6px;" title="Formu Temizle">
                                <i class="ti ti-trash icon me-1"></i> Temizle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Önizleme Kartı -->
        <div class="card" style="border: 1px solid #dbe3ec !important; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important; border-radius: 12px; overflow: hidden; background: #fff;">
            <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid #f1f5f9;">
                <div class="d-flex align-items-center gap-2">
                    <div class="card-header-icon" style="width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 6px;">
                        <i class="ti ti-eye text-secondary" style="font-size: 16px;"></i>
                    </div>
                    <div>
                        <h4 class="card-title mb-0 fw-bold" style="font-size: 14px;">Dosya Önizleme</h4>
                        <span class="text-muted small" style="font-size: 11px;">Seçilen dosyadaki satırlar yüklendikten sonra aşağıda listelenir</span>
                    </div>
                </div>
            </div>

            <div class="table-responsive" style="width: calc(100% - 16px) !important; margin: 0 8px 8px !important; padding: 0 !important; overflow-x: auto;">
                <div id="result">
                    <table class="table table-hover text-nowrap w-100 mb-0" id="payment-load-table" style="border: 1px solid #dbe3ec !important; border-radius: 8px !important; border-collapse: separate !important; border-spacing: 0 !important;">
                        <thead>
                            <tr style="background-color: #f8fafc;">
                                <th style="font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 8px 10px; border-bottom: 1px solid #dbe3ec;">ID</th>
                                <th style="font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 8px 10px; border-bottom: 1px solid #dbe3ec;">Adı Soyadı</th>
                                <th style="font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 8px 10px; border-bottom: 1px solid #dbe3ec;">Ödeme Günü</th>
                                <th style="font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 8px 10px; border-bottom: 1px solid #dbe3ec;" class="text-end">Tutar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4" style="font-size: 12.5px;">
                                    <i class="ti ti-file-upload fs-1 d-block mb-1 text-secondary"></i>
                                    Henüz bir Excel dosyası seçilmedi. Lütfen yukarıdan bir dosya seçin.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>
</div>