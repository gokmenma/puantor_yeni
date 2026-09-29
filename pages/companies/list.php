<?php
require_once "Model/Company.php";
require_once "App/Helper/helper.php";
require_once "App/Helper/cities.php";
require_once "Model/Auths.php";

use App\Helper\Helper;
use App\Helper\Security;

$perm = new Auths();
$Auths = $perm;
$perm->checkAuthorize("company_page");

$helper = new Helper();
$cities = new Cities();
$companyObj = new Company();
$companies = $companyObj->allWithUserId();

// Proje sayılarını hesaplamak için sorgu
$project_counts = [];
try {
    $pStmt = $companyObj->getDb()->prepare("SELECT company_id, COUNT(*) as cnt FROM projects WHERE (deleted_at IS NULL OR deleted_at = '0' OR deleted_at = '') AND company_id IS NOT NULL AND company_id > 0 GROUP BY company_id");
    $pStmt->execute();
    foreach ($pStmt->fetchAll(PDO::FETCH_OBJ) as $pRow) {
        $project_counts[(int)$pRow->company_id] = (int)$pRow->cnt;
    }
} catch (Exception $e) {
    error_log("Firma proje sayıları alınamadı: " . $e->getMessage());
}

// Özet İstatistikleri Hesaplama
$total_companies = count($companies);
$companies_with_projects = 0;
$companies_with_contact = 0;
$distinct_cities = [];

foreach ($companies as $c) {
    if (($project_counts[$c->id] ?? 0) > 0) {
        $companies_with_projects++;
    }
    if (!empty($c->phone) || !empty($c->email)) {
        $companies_with_contact++;
    }
    if (!empty($c->city)) {
        $distinct_cities[$c->city] = true;
    }
}
$distinct_cities_count = count($distinct_cities);
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'companies-summary-collapsed',
            localStorage.getItem('companies_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.companies-summary-collapsed #companySummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="companiesPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-building" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Firma Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Müşteri, tedarikçi ve taşeron firmalar, iletişim ve proje ilişkileri
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon companies-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="companiesColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar JS ile dinamik yüklenecek -->
                        </div>
                    </div>
                    <?php if ($Auths->hasPermission('company_add_update')) { ?>
                        <button type="button" class="btn btn-sm btn-dark shadow-sm companies-header-action" id="btn-new-company" style="background-color: #1e293b; border-color: #1e293b;">
                            <i class="ti ti-plus me-1"></i> Yeni Firma Ekle
                        </button>
                    <?php } ?>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle companies-header-action" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a href="#" class="dropdown-item" id="export_excel">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dörtlü KPI / İstatistik Özet Kartları -->
    <div class="row row-cards g-3 mb-3" id="companySummaryCards">
        <!-- Kart 1: Toplam Firma -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border company-summary-card" style="border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM FİRMA</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-building" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= number_format($total_companies, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Müşteri & Tedarikçi
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm firmaları göster">
                            <input type="radio" name="company_type_filter" value="" class="company-type-filter" checked>
                            <span><i class="ti ti-building"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Projeli Firmalar -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border company-summary-card" style="border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">PROJELİ FİRMALAR</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-building-community" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= number_format($companies_with_projects, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Bağlı Projesi Olan
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece projesi olan firmaları göster">
                            <input type="radio" name="company_type_filter" value="Projeli" class="company-type-filter">
                            <span><i class="ti ti-building-community"></i> Projeli</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: İletişim Kayıtlı -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border company-summary-card" style="border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">İLETİŞİM KAYITLI</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-phone-call" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= number_format($companies_with_contact, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Telefon / E-posta
                        </span>
                        <label class="status-summary-filter mb-0" title="İletişim bilgisi olan firmaları göster">
                            <input type="radio" name="company_type_filter" value="İletişim" class="company-type-filter">
                            <span><i class="ti ti-phone-call"></i> İletişim</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Şehir Çeşitliliği -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border company-summary-card" style="border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ŞEHİR ÇEŞİTLİLİĞİ</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-map-pin" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= number_format($distinct_cities_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Lokasyon Dağılımı
                        </span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;"><?= $distinct_cities_count ?> Farklı İl</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card companies-table-card" style="border-radius: 12px; border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-building text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Firma Listesi</h4>
                                <?php if ($Auths->hasPermission('company_add_update')) { ?>
                                    <a href="#" class="btn-card-header-add" id="btn-new-company-header" data-tooltip="Yeni Firma Ekle">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                <?php } ?>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Müşteri, tedarikçi ve taşeron firma yönetimi, iletişim ve proje takibi</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon companies-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="companies-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="companies-search-clear" class="companies-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleCompaniesSummary" class="btn btn-sm btn-outline-secondary btn-icon companies-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container (Seamless inside card) -->
                <div class="table-responsive companies-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="companiesTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 5%; min-width: 45px;" class="text-center no-export" data-orderable="false">Sıra</th>
                                <th>Firma Adı</th>
                                <th>Yetkili</th>
                                <th>Şehir</th>
                                <th>İlçe</th>
                                <th>Telefon</th>
                                <th>E-posta</th>
                                <th style="width: 6%; min-width: 65px;" class="text-center">Proje</th>
                                <th>Vergi Bilgisi</th>
                                <th>Adres</th>
                                <th class="no-export text-end actions-column" style="width: 90px; min-width: 90px;" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 0;
                            foreach ($companies as $company) { 
                                $i++;
                                $id = Security::encrypt($company->id);
                                $projCount = $project_counts[$company->id] ?? 0;
                                $cityName = !empty($company->city) ? $cities->getCityName($company->city) : '';
                                $townName = !empty($company->town) ? $cities->getTownName($company->town) : '';
                                $hasContact = (!empty($company->phone) || !empty($company->email)) ? 'İletişim' : 'Yok';
                                $taxInfo = trim(($company->tax_office ?? '') . ' ' . ($company->tax_number ?? ''));
                            ?>
                                <tr data-company-id="<?php echo $id; ?>" 
                                    data-company-name="<?php echo htmlspecialchars($company->company_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-has-projects="<?php echo $projCount > 0 ? 'Projeli' : 'Projesiz'; ?>"
                                    data-has-contact="<?php echo $hasContact; ?>">
                                    
                                    <td class="text-center fw-medium text-muted"><?php echo $i; ?></td>
                                    
                                    <td>
                                        <a href="#" class="route-link fw-bold text-primary" data-page="companies/manage&id=<?php echo $id; ?>" data-tooltip="Firma Detayları">
                                            <?php echo htmlspecialchars($company->company_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>

                                    <td>
                                        <?php if (!empty($company->yetkili)): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="ti ti-user text-muted me-1 small"></i>
                                                <span><?php echo htmlspecialchars($company->yetkili, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td><?php echo !empty($cityName) ? htmlspecialchars($cityName, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">-</span>'; ?></td>
                                    <td><?php echo !empty($townName) ? htmlspecialchars($townName, ENT_QUOTES, 'UTF-8') : '<span class="text-muted">-</span>'; ?></td>

                                    <td>
                                        <?php if (!empty($company->phone)): ?>
                                            <a href="tel:<?php echo htmlspecialchars($company->phone, ENT_QUOTES, 'UTF-8'); ?>" class="text-body text-decoration-none d-inline-flex align-items-center">
                                                <i class="ti ti-phone text-muted me-1 small"></i>
                                                <span><?php echo htmlspecialchars($company->phone, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($company->email)): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($company->email, ENT_QUOTES, 'UTF-8'); ?>" class="text-body text-decoration-none d-inline-flex align-items-center">
                                                <i class="ti ti-mail text-muted me-1 small"></i>
                                                <span><?php echo htmlspecialchars($company->email, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge <?php echo $projCount > 0 ? 'bg-primary-lt text-primary' : 'bg-secondary-lt text-muted'; ?> py-1 px-2" style="font-size: 11.5px;">
                                            <i class="ti ti-building-community me-1"></i> <?php echo $projCount; ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?php if (!empty($taxInfo)): ?>
                                            <span class="small text-muted font-monospace"><?php echo htmlspecialchars($taxInfo, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if (!empty($company->address)): ?>
                                            <span class="text-muted small text-truncate d-inline-block" style="max-width: 180px;" title="<?php echo htmlspecialchars($company->address, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars(Helper::short($company->address, 30), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>

                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle align-text-top py-1 px-2" data-bs-toggle="dropdown" data-bs-boundary="viewport" style="font-size: 12px;">
                                                İşlem
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link" data-page="companies/manage&id=<?php echo $id; ?>" href="#">
                                                    <i class="ti ti-eye icon me-2 text-primary"></i> Firma Detayları
                                                </a>
                                                <a class="dropdown-item company-edit-btn" data-id="<?php echo $id; ?>" href="#">
                                                    <i class="ti ti-edit icon me-2 text-warning"></i> Bilgileri Güncelle
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item delete-company text-danger" data-id="<?php echo $id; ?>" href="#">
                                                    <i class="ti ti-trash icon me-2"></i> Firmayı Sil
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Yeni & Düzenleme Firma Modalı -->
<div class="modal modal-blur fade" id="company-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fs-3 fw-bold text-primary" id="company-modal-title">Yeni Firma Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="companyForm">
                <input type="hidden" name="id" id="company_id" value="0">
                <input type="hidden" name="action" value="saveCompany">
                
                <div class="modal-body pt-2">
                    <!-- Bölüm 1: Temel Firma Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-primary-lt p-2 rounded-2 me-2">
                                <i class="ti ti-info-circle text-primary fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Temel Firma Bilgileri</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">Firma Adı</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-building"></i>
                                    </span>
                                    <input type="text" class="form-control" name="company_name" id="company_name" placeholder="Firma adını giriniz" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Yetkilisi</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-user"></i>
                                    </span>
                                    <input type="text" class="form-control" name="yetkili" id="yetkili" placeholder="Yetkili ad soyad">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 2: İletişim Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-success-lt p-2 rounded-2 me-2">
                                <i class="ti ti-phone text-success fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">İletişim Bilgileri</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Telefon</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-phone"></i>
                                    </span>
                                    <input type="text" class="form-control" name="phone" id="phone" placeholder="Telefon numarası">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-posta</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-mail"></i>
                                    </span>
                                    <input type="email" class="form-control" name="email" id="email" placeholder="E-posta adresi">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 3: Vergi ve Hesap Bilgileri -->
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-warning-lt p-2 rounded-2 me-2">
                                <i class="ti ti-wallet text-warning fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Vergi ve Hesap Bilgileri</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Vergi Dairesi</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-building-bank"></i>
                                    </span>
                                    <input type="text" class="form-control" name="tax_office" id="tax_office" placeholder="Vergi dairesi">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Vergi Numarası</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-file-text"></i>
                                    </span>
                                    <input type="text" class="form-control" name="tax_number" id="tax_number" placeholder="Vergi no">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Hesap Numarası</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-credit-card"></i>
                                    </span>
                                    <input type="text" class="form-control" name="account_number" id="account_number" placeholder="Hesap no">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 4: Konum ve Diğer Bilgiler -->
                    <div>
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-info-lt p-2 rounded-2 me-2">
                                <i class="ti ti-map-2 text-info fs-2"></i>
                            </div>
                            <h6 class="mb-0 fw-bold text-uppercase tracking-wider text-muted small">Konum ve Diğer Bilgiler</h6>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Şehir</label>
                                <?php echo $cities->citySelect("firm_cities", null); ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">İlçe</label>
                                <select name="firm_towns" id="firm_towns" class="form-control select2" style="width:100%">
                                    <option value="">İlçe Seçiniz</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Açıklama</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-notes"></i>
                                    </span>
                                    <input type="text" class="form-control" name="description" id="description" placeholder="Açıklama giriniz">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Adres</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-map-pin"></i>
                                    </span>
                                    <input type="text" class="form-control" name="address" id="address" placeholder="Firma adresi">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-lt border-0 rounded-bottom-4">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">İptal</button>
                    <button type="button" class="btn btn-primary px-4 shadow-sm" id="saveCompany">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.companies-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.companies-header-icon-action,
.companies-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.companies-header-icon-action i,
.companies-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

.page-wrapper:has(#companiesPage) {
    background: #eef3f8 !important;
    min-height: calc(100vh - 56px);
}

.company-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
}

#companySummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

.companies-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
}

.companies-table-card > .companies-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.companies-table-card > .card-header {
    border-bottom: 0 !important;
}

.status-summary-filter { cursor: pointer; }
.status-summary-filter input {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}
.status-summary-filter span {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 24px;
    padding: 2px 8px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    color: #64748b;
    background: #fff;
    font-size: 10.5px;
    font-weight: 600;
    transition: all .15s ease;
}
.status-summary-filter:hover span,
.status-summary-filter input:focus-visible + span {
    border-color: #94a3b8;
    color: #334155;
}
.status-summary-filter input:checked + span {
    border-color: #206bc4;
    color: #206bc4;
    background: rgba(32, 107, 196, .08);
    box-shadow: 0 0 0 1px rgba(32, 107, 196, .08);
}

.companies-search-wrap { position: relative; }
#companies-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.companies-search-wrap,
.companies-search-wrap.input-icon {
    height: 32px !important;
}
.companies-search-clear {
    position: absolute;
    top: 50%;
    right: 6px;
    z-index: 3;
    display: inline-flex;
    width: 22px;
    height: 22px;
    padding: 0;
    align-items: center;
    justify-content: center;
    transform: translateY(-50%);
    border: 0;
    border-radius: 50%;
    color: #64748b;
    background: #f1f5f9;
    cursor: pointer;
}
.companies-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#companiesTable_wrapper,
div.dt-container,
div.dt-container .dt-layout-row.dt-layout-table,
div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell {
    height: auto !important;
    min-height: 0 !important;
    min-height: unset !important;
    max-height: none !important;
    flex-grow: 0 !important;
    border: none !important;
    box-shadow: none !important;
}

div.dt-container .dt-layout-row.dt-layout-table {
    padding: 0 !important;
    margin: 0 !important;
}

div.dt-container .dt-layout-row.dt-layout-table > div.dt-layout-cell {
    padding: 0 !important;
    margin: 0 !important;
}

/* Tek Çerçeve (Kart ile Bütünleşik Tablo) */
table#companiesTable.data-table,
table#companiesTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#companiesTable.data-table tbody,
table#companiesTable.dataTable tbody,
table#companiesTable.data-table tbody tr:last-child,
table#companiesTable.dataTable tbody tr:last-child,
#companiesTable_wrapper .dt-layout-table,
#companiesTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#companiesTable.data-table thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 11.5px !important;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    padding: 9px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}
table#companiesTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#companiesTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#companiesTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#companiesTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#companiesTable.dataTable > tbody > tr:last-child > *,
table#companiesTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#companiesTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#companiesTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child {
    margin: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
}

#companiesTable th:last-child,
#companiesTable td:last-child {
    width: 90px !important;
    min-width: 90px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 12px !important;
}

/* Dark Mode */
[data-bs-theme="dark"] table#companiesTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#companiesTable.data-table,
[data-bs-theme="dark"] table#companiesTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#companiesTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#companiesTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#companiesTable.data-table tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] .status-summary-filter span {
    color: #94a3b8;
    background: #1e293b;
    border-color: #334155;
}
[data-bs-theme="dark"] .status-summary-filter input:checked + span {
    color: #60a5fa;
    background: rgba(59, 130, 246, .14);
    border-color: #3b82f6;
}
[data-bs-theme="dark"] .companies-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .page-wrapper:has(#companiesPage) {
    background: #0f172a !important;
}
[data-bs-theme="dark"] .company-summary-card,
[data-bs-theme="dark"] .companies-table-card {
    background: #182433 !important;
    border-color: #334155 !important;
    box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
}

/* Custom Context Menu */
.custom-context-menu {
    position: fixed;
    z-index: 1050;
    display: none;
    min-width: 180px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
    padding: 6px;
    font-size: 13px;
    user-select: none;
}
[data-bs-theme="dark"] .custom-context-menu {
    background: #1e293b;
    border-color: #334155;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
}
.custom-context-menu .cm-header {
    padding: 6px 10px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    color: #64748b;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
[data-bs-theme="dark"] .custom-context-menu .cm-header {
    border-bottom-color: #334155;
    color: #94a3b8;
}
.custom-context-menu a {
    display: flex;
    align-items: center;
    padding: 6px 10px;
    color: #334155;
    text-decoration: none;
    border-radius: 6px;
    transition: background-color 0.15s;
    font-weight: 500;
}
[data-bs-theme="dark"] .custom-context-menu a {
    color: #e2e8f0;
}
.custom-context-menu a i {
    font-size: 15px;
    margin-right: 8px;
    width: 16px;
    text-align: center;
}
.custom-context-menu a:hover {
    background-color: #f1f5f9;
    color: #206bc4;
}
[data-bs-theme="dark"] .custom-context-menu a:hover {
    background-color: #334155;
    color: #38bdf8;
}
.custom-context-menu .cm-divider {
    height: 1px;
    background-color: #e2e8f0;
    margin: 4px 0;
}
[data-bs-theme="dark"] .custom-context-menu .cm-divider {
    background-color: #334155;
}
.custom-context-menu a.cm-danger {
    color: #ef4444;
}
.custom-context-menu a.cm-danger:hover {
    background-color: #fef2f2;
    color: #dc2626;
}
[data-bs-theme="dark"] .custom-context-menu a.cm-danger:hover {
    background-color: rgba(239, 68, 68, 0.15);
    color: #f87171;
}
tbody tr.context-menu-active td {
    background-color: rgba(32, 107, 196, 0.08) !important;
}

/* Modal tasarım iyileştirmeleri */
#company-modal .modal-content {
    border-radius: 1.25rem;
    overflow: hidden;
}
#company-modal .form-label.required:after {
    content: " *";
    color: #d63f3f;
}
#company-modal .bg-primary-lt, #company-modal .bg-success-lt, #company-modal .bg-warning-lt, #company-modal .bg-info-lt {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}
#company-modal .input-icon-addon {
    color: #94a3b8;
}
#company-modal .form-control:focus {
    border-color: #206bc4;
    box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, 0.15);
}
#company-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}
#company-modal .modal-body::-webkit-scrollbar {
    width: 6px;
}
#company-modal .modal-body::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
#company-modal .modal-body::-webkit-scrollbar-track {
    background: transparent;
}
</style>

<script>
$(document).ready(function() {
    var $summaryToggle = $('#toggleCompaniesSummary');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('companies-summary-collapsed');
        $summaryToggle
            .attr('aria-expanded', String(!isCollapsed))
            .attr('aria-label', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle')
            .attr('title', isCollapsed ? 'Özet kartlarını göster' : 'Özet kartlarını gizle');
        $summaryToggle.find('i')
            .toggleClass('ti-chevron-up', !isCollapsed)
            .toggleClass('ti-chevron-down', isCollapsed);
    }

    syncSummaryToggle();

    $summaryToggle.on('click', function() {
        var isCollapsed = document.documentElement.classList.toggle('companies-summary-collapsed');
        try {
            localStorage.setItem('companies_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    var $companiesTable = $('#companiesTable');

    var columnConfig = {
        1: { label: 'Firma Adı', default: true },
        2: { label: 'Yetkili', default: true },
        3: { label: 'Şehir', default: true },
        4: { label: 'İlçe', default: true },
        5: { label: 'Telefon', default: true },
        6: { label: 'E-posta', default: true },
        7: { label: 'Proje Sayısı', default: true },
        8: { label: 'Vergi Bilgisi', default: true },
        9: { label: 'Adres', default: true }
    };

    var savedVisibility = {};
    try {
        var rawStored = localStorage.getItem('companies_column_visibility');
        if (rawStored) {
            var parsed = JSON.parse(rawStored);
            if (typeof parsed === 'object' && parsed !== null && !Array.isArray(parsed)) {
                savedVisibility = parsed;
            }
        }
    } catch(e) {
        savedVisibility = {};
    }

    var tableOptions = {
        autoWidth: false,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [],
        stateSave: false,
        columnDefs: [
            { targets: '_all', defaultContent: '' },
            { targets: [0, 10], orderable: false, searchable: false },
            { targets: [0, 7], className: 'text-center' },
            { targets: 10, width: '90px', className: 'text-end no-export actions-column' }
        ],
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'd-none',
                title: 'Firma Listesi',
                filename: 'firmalar_' + new Date().toLocaleDateString('tr-TR').replace(/\./g, '-'),
                exportOptions: {
                    columns: ':not(.no-export)'
                }
            }
        ],
        layout: {
            topStart: null,
            topEnd: null,
            bottomStart: ['info', 'pageLength'],
            bottomEnd: 'paging'
        },
        language: { url: 'src/tr.json' },
        initComplete: function() {
            var api = this.api();

            $.each(columnConfig, function(colIdx, conf) {
                colIdx = parseInt(colIdx, 10);
                var isVisible = (typeof savedVisibility[colIdx] === 'boolean')
                    ? savedVisibility[colIdx]
                    : conf.default;
                api.column(colIdx).visible(isVisible, false);
            });

            if (typeof window.initDataTableColumnFilters === 'function') {
                window.initDataTableColumnFilters($('#companiesTable'), api);
            }

            // Dil dosyasi ve DOM verileri tamamen yuklendikten sonra ciz.
            api.columns.adjust().draw(false);
        }
    };

    var table = $.fn.DataTable.isDataTable($companiesTable[0])
        ? $companiesTable.DataTable()
        : $companiesTable.DataTable(tableOptions);

    var $colvisMenu = $('#companiesColvisMenu');
    $colvisMenu.empty();

    if (table) {
        $.each(columnConfig, function(colIdx, conf) {
            colIdx = parseInt(colIdx, 10);
            var isVisible = (savedVisibility && typeof savedVisibility[colIdx] === 'boolean') ? savedVisibility[colIdx] : conf.default;

            var $item = $(
                '<label class="dropdown-item d-flex align-items-center py-1.5 px-3 rounded-2 cursor-pointer" style="font-size: 0.85rem;">' +
                '<div class="form-check mb-0 w-100">' +
                '<input class="form-check-input me-2 mt-0 col-toggle-cb" type="checkbox" data-column="' + colIdx + '"' + (isVisible ? ' checked' : '') + '>' +
                '<span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">' + conf.label + '</span>' +
                '</div>' +
                '</label>'
            );
            $colvisMenu.append($item);
        });

    }

    // Checkbox değiştiğinde sütunu göster/gizle ve kaydet
    $colvisMenu.on('change', '.col-toggle-cb', function(e) {
        e.stopPropagation();
        var colIdx = parseInt($(this).data('column'), 10);
        var isChecked = $(this).is(':checked');

        table.column(colIdx).visible(isChecked, true);

        savedVisibility[colIdx] = isChecked;
        try {
            localStorage.setItem('companies_column_visibility', JSON.stringify(savedVisibility));
        } catch(err) {}
    });

    $colvisMenu.on('click', function(e) {
        e.stopPropagation();
    });

    // Özet Kartı Radyo Filtreleri
    $('.company-type-filter').on('change', function() {
        var filterType = $(this).val();
        if (!table) return;

        if (filterType === 'Projeli') {
            table.search('').columns().search('');
            table.column(7).search('Proje').draw();
        } else if (filterType === 'İletişim') {
            table.search('').columns().search('');
            table.column(5).search('\\d|@', true, false).draw();
        } else {
            table.search('').columns().search('').draw();
        }
    });

    // Hızlı Genel Arama Inputu
    var searchTimer = null;
    $('#companies-fast-search').on('input', function() {
        var val = this.value;
        $('#companies-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (table) table.search(val).draw();
        }, 300);
    });

    $('#companies-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#companies-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        if (table) table.search('').draw();
    });

    // Excel Export Butonu
    $('#export_excel').off('click').on('click', function(e) {
        e.preventDefault();
        if (table && table.button) {
            table.button('.buttons-excel').trigger();
        }
    });

    // Header Yeni Firma Butonu
    $('#btn-new-company-header').on('click', function(e) {
        e.preventDefault();
        $('#btn-new-company').trigger('click');
    });

    // Tabloda Sağ Tık (Custom Context Menu)
    $(document).on('contextmenu', '#companiesTable tbody tr', function(e) {
        var $tr = $(this);
        var companyId = $tr.attr('data-company-id');
        var companyName = $tr.attr('data-company-name') || 'Firma İşlemleri';

        if (!companyId) return;

        e.preventDefault();

        $('#companiesTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-building me-1"></i> ${$('<div>').text(companyName).html()}</div>
            <a href="#" class="route-link" data-page="companies/manage&id=${companyId}"><i class="ti ti-eye"></i> Firma Detayları</a>
            <a href="#" class="company-edit-btn" data-id="${companyId}"><i class="ti ti-edit text-warning"></i> Bilgileri Güncelle</a>
            <div class="cm-divider"></div>
            <a href="#" class="cm-danger delete-company" data-id="${companyId}"><i class="ti ti-trash"></i> Firmayı Sil</a>
        `;

        $contextMenu.html(menuHtml);
        $contextMenu.css({ display: 'block', opacity: 0 });

        var menuWidth = $contextMenu.outerWidth();
        var menuHeight = $contextMenu.outerHeight();
        var clickX = e.clientX;
        var clickY = e.clientY;
        var windowWidth = $(window).width();
        var windowHeight = $(window).height();

        var posX = (clickX + menuWidth > windowWidth) ? windowWidth - menuWidth - 10 : clickX;
        var posY = (clickY + menuHeight > windowHeight) ? windowHeight - menuHeight - 10 : clickY;

        $contextMenu.css({
            top: posY + 'px',
            left: posX + 'px',
            opacity: 1
        });
    });

    // Modal Select2 Entegrasyonu
    $('#company-modal').on('shown.bs.modal', function() {
        if (typeof $.fn.select2 !== 'undefined') {
            $('#firm_cities, #firm_towns').select2({
                dropdownParent: $('#company-modal'),
                width: '100%'
            });
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#companiesTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#companiesTable tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#companiesTable tbody tr').removeClass('context-menu-active');
    });
});
</script>
