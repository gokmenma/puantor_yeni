<?php
require_once 'Model/Projects.php';
require_once 'Model/ProjectIncomeExpense.php';
require_once 'App/Helper/helper.php';
require_once 'App/Helper/cities.php';
require_once 'App/Helper/company.php';
require_once 'App/Helper/date.php';
require_once 'App/Helper/financial.php';
require_once "Model/Cases.php";
require_once "Model/Auths.php";

use App\Helper\Helper;
use App\Helper\Security;
use App\Helper\Date;

$perm = new Auths();
$Auths = $perm;
$perm->checkAuthorize("projects_page");

$projectObj = new Projects();
$incexpObj = new ProjectIncomeExpense();
$cities = new Cities();
$firm_id = (int)($_SESSION['firm_id'] ?? 0);
$projects = $projectObj->getProjectsByFirm($firm_id);
$companyHelper = new CompanyHelper();
$Cases = new Cases();
$financialHelper = new Financial();

$case_id = $Cases->getDefaultCaseIdByFirm();

// Özet İstatistikleri Hesaplama
$total_count = count($projects);
$total_budget = 0;
$alinan_count = 0;
$alinan_budget = 0;
$verilen_count = 0;
$verilen_budget = 0;
$active_count = 0;
$completed_count = 0;

foreach ($projects as $p) {
    $b = (float)($p->budget ?? 0);
    $total_budget += $b;
    if ($p->type == 1) {
        $alinan_count++;
        $alinan_budget += $b;
    } else {
        $verilen_count++;
        $verilen_budget += $b;
    }
    
    $has_end = !empty($p->end_date);
    $p_gun = Date::getDateDiff($p->start_date, $p->end_date ?? $p->start_date) ?? 0;
    $k_gun = $has_end ? Date::getRemainingDays($p->end_date) : '';
    if ($has_end && $p_gun > 0 && is_numeric($k_gun) && $k_gun <= 0) {
        $completed_count++;
    } else {
        $active_count++;
    }
}
?>

<script>
(function() {
    try {
        document.documentElement.classList.toggle(
            'projects-summary-collapsed',
            localStorage.getItem('projects_summary_collapsed') === '1'
        );
    } catch (e) {}
})();
</script>
<style>
html.projects-summary-collapsed #projectsSummaryCards {
    max-height: 0 !important;
    margin-bottom: 12px !important;
    opacity: 0;
    transform: translateY(-8px);
    pointer-events: none;
}
</style>

<div class="container-xl mt-1" id="projectsPage">

    <!-- Page Header (Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-building-community" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Proje Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Tüm projelerin takibi, hakedişler, gelir-gider süreçleri ve maliyet yönetimi
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon projects-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle">
                            <i class="ti ti-columns"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="projectsColvisMenu"
                            style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar dinamik yüklenecek -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark shadow-sm projects-header-action" id="addNewProject" style="background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-plus me-1"></i> Yeni Proje Ekle
                    </button>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle projects-header-action" data-bs-toggle="dropdown">
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
    <div class="row row-cards g-3 mb-3" id="projectsSummaryCards">
        <!-- Kart 1: Toplam Proje -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border project-summary-card" style="border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">TOPLAM PROJE</span>
                        <div class="avatar avatar-sm rounded-2 bg-secondary-lt text-secondary" style="width: 32px; height: 32px;">
                            <i class="ti ti-building-community" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= number_format($total_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Tutar: <strong class="text-dark"><?= Helper::formattedMoney($total_budget); ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Tüm projeleri göster">
                            <input type="radio" name="project_type_filter" value="" class="project-type-filter" checked>
                            <span><i class="ti ti-building-community"></i> Tümü</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 2: Alınan Projeler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border project-summary-card" style="border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">ALINAN PROJELER</span>
                        <div class="avatar avatar-sm rounded-2 bg-warning-lt text-warning" style="width: 32px; height: 32px;">
                            <i class="ti ti-download" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= number_format($alinan_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Tutar: <strong class="text-warning"><?= Helper::formattedMoney($alinan_budget); ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece alınan projeleri göster">
                            <input type="radio" name="project_type_filter" value="Alınan" class="project-type-filter">
                            <span><i class="ti ti-download"></i> Alınan</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 3: Verilen Projeler -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border project-summary-card" style="border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">VERİLEN PROJELER</span>
                        <div class="avatar avatar-sm rounded-2 bg-success-lt text-success" style="width: 32px; height: 32px;">
                            <i class="ti ti-upload" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= number_format($verilen_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Tutar: <strong class="text-success"><?= Helper::formattedMoney($verilen_budget); ?></strong>
                        </span>
                        <label class="status-summary-filter mb-0" title="Sadece verilen projeleri göster">
                            <input type="radio" name="project_type_filter" value="Verilen" class="project-type-filter">
                            <span><i class="ti ti-upload"></i> Verilen</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kart 4: Devam Eden / Tamamlanan -->
        <div class="col-sm-6 col-xl-3">
            <div class="card card-sm border project-summary-card" style="border-radius: 12px; border-color: #e2e8f0 !important;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-uppercase fw-bold text-muted" style="font-size: 11px; letter-spacing: 0.5px;">DEVAM EDEN SÜREÇ</span>
                        <div class="avatar avatar-sm rounded-2 bg-info-lt text-info" style="width: 32px; height: 32px;">
                            <i class="ti ti-clock-play" style="font-size: 18px;"></i>
                        </div>
                    </div>
                    <div class="h1 mb-2 fw-bold text-dark" style="font-size: 1.85rem; letter-spacing: -0.5px;">
                        <?= number_format($active_count, 0, ',', '.') ?>
                    </div>
                    <div class="d-flex align-items-center justify-content-between pt-1 border-top" style="border-color: #f1f5f9 !important;">
                        <span class="text-muted" style="font-size: 11.5px;">
                            Aktif Süreçte
                        </span>
                        <span class="badge bg-info-lt fw-semibold" style="font-size: 10px;">Tamamlanan: <?= $completed_count ?> Proje</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Table Card -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card projects-table-card" style="border-radius: 12px; border: 1px solid #dbe3ec !important; overflow: hidden; background: #ffffff;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-2">
                        <div class="card-header-icon" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; background: #f1f5f9; border-radius: 8px;">
                            <i class="ti ti-building-community text-secondary" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Proje Listesi</h4>
                                <a href="#" class="btn-card-header-add" id="addNewProject" data-tooltip="Yeni Proje Ekle">
                                    <i class="ti ti-plus"></i>
                                </a>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık arama, sütun filtreleme ve proje maliyet yönetimi</p>
                        </div>
                    </div>

                    <!-- Actions & Search -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Fast Instant Search -->
                        <div class="input-icon projects-search-wrap" style="min-width: 170px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="projects-fast-search" class="form-control form-control-sm" placeholder="Arayın..." autocomplete="off">
                            <button type="button" id="projects-search-clear" class="projects-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                        <button type="button" id="toggleProjectsSummary" class="btn btn-sm btn-outline-secondary btn-icon projects-summary-toggle" title="Özet kartlarını gizle" aria-label="Özet kartlarını gizle" aria-expanded="true">
                            <i class="ti ti-chevron-up"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Responsive Container (Seamless inside card) -->
                <div class="table-responsive projects-table-area" style="overflow-x: auto !important;">
                    <table class="table data-table table-hover text-nowrap w-100 mb-0" id="projectTable" style="width: 100% !important; margin: 0 !important;">
                        <thead>
                            <tr>
                                <th style="width: 5%; min-width: 45px;" class="text-center no-export" data-orderable="false">Sıra</th>
                                <th>Türü</th>
                                <th>Firma Adı</th>
                                <th>Proje Adı</th>
                                <th class="text-end">Proje Bedeli</th>
                                <th>Şehir</th>
                                <th>İlçe</th>
                                <th style="width: 10%; min-width: 95px;" class="text-center">Başlama Tarihi</th>
                                <th style="width: 10%; min-width: 95px;" class="text-center">Tahmini Bitiş Tarihi</th>
                                <th style="width: 13%; min-width: 130px;">Kalan Gün</th>
                                <th style="width: 6%; min-width: 65px;" class="text-center">Personel</th>
                                <th class="no-export text-end actions-column" style="width: 90px; min-width: 90px;" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($projects as $project):
                                $id = Security::encrypt($project->id);
                                $balance = $incexpObj->getBalance($project->id);

                                $has_end_date = !empty($project->end_date);
                                $proje_gunu = Date::getDateDiff($project->start_date, $project->end_date ?? $project->start_date) ?? 0;
                                $kalan_gun = $has_end_date ? Date::getRemainingDays($project->end_date) : '';
                                 
                                if ($has_end_date) {
                                    $elapsed = $proje_gunu - $kalan_gun;
                                    if ($elapsed < 0) $elapsed = 0;
                                    $date_range = ($proje_gunu > 0) ? round(($elapsed / $proje_gunu) * 100) : 0;
                                    if ($date_range > 100) $date_range = 100;
                                } else {
                                    $date_range = 100;
                                }

                                if ($has_end_date && $proje_gunu > 0 && is_numeric($kalan_gun) && $kalan_gun <= 0) {
                                    $date_range = 100;
                                    $progress_color = "bg-success";
                                    $sub_text = "Proje Tamamlandı";
                                } else {
                                    if (is_numeric($kalan_gun) && $kalan_gun < 10) {
                                        $progress_color = "bg-danger";
                                    } else if (is_numeric($kalan_gun) && $kalan_gun < 30) {
                                        $progress_color = "bg-warning";
                                    } else {
                                        $progress_color = "bg-primary";
                                    }
                                    $sub_text = "Proje Devam Ediyor";
                                }
                                ?>
                                <tr data-project-id="<?php echo $id ?>" data-project-type="<?php echo $project->type ?>" data-project-name="<?php echo htmlspecialchars($project->project_name ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="text-center fw-medium text-muted"><?php echo $i ?></td>
                                    <td>
                                        <span class="badge <?php echo $project->type == 1 ? 'bg-primary-lt text-primary' : 'bg-warning-lt text-warning' ?>">
                                            <i class="ti <?php echo $project->type == 1 ? 'ti-download' : 'ti-upload' ?> me-1"></i>
                                            <?php echo $project->type == 1 ? 'Alınan' : 'Verilen' ?>
                                        </span>
                                    </td>
                                    <td class="fw-semibold text-dark"><?php echo htmlspecialchars($companyHelper->getCompanyName($project->company_id) ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <a href="#" class="link update-project fw-bold text-primary" data-id="<?php echo $id ?>" data-tooltip="Düzenle">
                                            <?php echo htmlspecialchars($project->project_name ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td class="text-end fw-semibold"><?php echo Helper::formattedMoney($project->budget ?? 0) ?></td>
                                    <td><?php echo htmlspecialchars($cities->getCityName($project->city) ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars($cities->getTownName($project->town) ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center font-monospace small"><?php echo htmlspecialchars($project->start_date ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td class="text-center font-monospace small"><?php echo htmlspecialchars($project->end_date ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="progress progress-sm mb-1" style="height: 6px; border-radius: 4px; background: rgba(0,0,0,0.06);">
                                            <div class="progress-bar <?php echo $progress_color ?>"
                                                style="width: <?php echo $date_range ?>%; border-radius: 4px;" 
                                                role="progressbar" 
                                                aria-valuenow="<?php echo $date_range ?>" 
                                                aria-valuemin="0" 
                                                aria-valuemax="100"
                                                data-bs-toggle="tooltip" 
                                                title="%<?php echo $date_range ?> tamamlandı"></div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center" style="font-size: 11px; line-height: 1.2;">
                                            <span class="text-muted">
                                                <?php 
                                                if ($has_end_date && is_numeric($kalan_gun)) {
                                                    if ($kalan_gun > 0) {
                                                        echo '<strong class="text-dark">' . $kalan_gun . '</strong> Gün';
                                                    } else {
                                                        echo '<span class="text-success fw-bold">' . $sub_text . '</span>';
                                                    }
                                                } else {
                                                    echo $sub_text;
                                                }
                                                ?>
                                            </span>
                                            <?php if ($has_end_date): ?>
                                                <span class="fw-bold text-dark">%<?php echo $date_range ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <a href="#" class="btn btn-ghost-primary btn-sm route-link py-1 px-2" data-page="projects/manage&id=<?php echo $id ?>#tabs-personnel-3" style="font-size: 12px;">
                                            <i class="ti ti-users icon me-1"></i>
                                            <span><?php echo $projectObj->getProjectPersonnelCount($project->id) ?></span>
                                        </a>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle align-text-top py-1 px-2"
                                                data-bs-toggle="dropdown" data-bs-boundary="viewport" style="font-size: 12px;">
                                                İşlem
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item route-link"
                                                    data-page="projects/manage&id=<?php echo $id ?>" href="#">
                                                    <i class="ti ti-eye icon me-2 text-primary"></i> Proje Detayları
                                                </a>
                                                <?php if ($perm->hasPermission("project_add_update")) { ?>
                                                    <a class="dropdown-item update-project"
                                                        data-id="<?php echo $id ?>" href="#">
                                                        <i class="ti ti-edit icon me-2 text-info"></i> Güncelle
                                                    </a>
                                                <?php } ?>

                                                <?php if ($project->type == 1) { ?>
                                                    <a class="dropdown-item add-progress-payment" href="#"
                                                         data-id="<?php echo $id; ?>">
                                                        <i class="ti ti-upload icon me-2 text-success"></i> Hakediş Ekle
                                                    </a>
                                                <?php } ?>
                                                <?php if ($project->type == 2) { ?>
                                                    <a class="dropdown-item add-payment" href="#"
                                                        data-id="<?php echo $id; ?>">
                                                        <i class="ti ti-download icon me-2 text-success"></i> Ödeme Ekle
                                                    </a>
                                                <?php } ?>
                                                <a class="dropdown-item add-expense" href="#"
                                                 data-id="<?php echo $id; ?>">
                                                    <i class="ti ti-receipt icon me-2 text-warning"></i> Masraf Ekle
                                                </a>
                                                <a class="dropdown-item route-link"
                                                    data-page="projects/manage&id=<?php echo $id ?>#tabs-personnel-3" href="#">
                                                    <i class="ti ti-users icon me-2 text-muted"></i> Personelleri Gör
                                                </a>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item delete-project text-danger" href="#"
                                                    data-id="<?php echo $id; ?>">
                                                    <i class="ti ti-trash icon me-2"></i> Projeyi Sil
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                $i++;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.projects-header-action {
    height: 32px;
    padding: 4px 10px;
    font-size: 12.5px;
    font-weight: 500;
    border-radius: 6px;
}

.projects-header-icon-action,
.projects-summary-toggle {
    width: 32px !important;
    min-width: 32px !important;
    height: 32px !important;
    padding: 0 !important;
    border-radius: 6px !important;
}
.projects-header-icon-action i,
.projects-summary-toggle i {
    margin: 0 !important;
    font-size: 18px !important;
}

.page-wrapper:has(#projectsPage) {
    background: #eef3f8 !important;
    min-height: calc(100vh - 56px);
}

.project-summary-card {
    background: #ffffff !important;
    border: 1px solid #dbe3ec !important;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.06) !important;
}

#projectsSummaryCards {
    max-height: 1000px;
    opacity: 1;
    transform: translateY(0);
    overflow: hidden;
    transition: max-height .3s ease, opacity .2s ease, transform .3s ease, margin-bottom .3s ease;
}

.projects-table-card {
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
}

.projects-table-card > .projects-table-area {
    width: calc(100% - 16px) !important;
    margin: 0 8px 8px !important;
    padding: 0 !important;
}

.projects-table-card > .card-header {
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

.projects-search-wrap { position: relative; }
#projects-fast-search {
    height: 32px !important;
    min-height: 32px !important;
    padding: 4px 32px 4px 34px !important;
    line-height: 1.25 !important;
    font-size: 12.5px;
    border-radius: 6px;
}
.projects-search-wrap,
.projects-search-wrap.input-icon {
    height: 32px !important;
}
.projects-search-clear {
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
.projects-search-clear:hover {
    color: #1e293b;
    background: #e2e8f0;
}

.table-responsive,
#projectTable_wrapper,
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
table#projectTable.data-table,
table#projectTable.dataTable {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 8px !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
    overflow: hidden !important;
}
table#projectTable.data-table tbody,
table#projectTable.dataTable tbody,
table#projectTable.data-table tbody tr:last-child,
table#projectTable.dataTable tbody tr:last-child,
#projectTable_wrapper .dt-layout-table,
#projectTable_wrapper .dt-layout-cell {
    border-bottom: 0 !important;
    box-shadow: none !important;
}

/* Tablo Başlık Hücreleri */
table#projectTable.data-table thead th {
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
table#projectTable.data-table thead th:last-child {
    border-right: none !important;
}

/* Gövde Satır ve Sütun Kenarlıkları */
table#projectTable.data-table tbody td {
    padding: 6px 10px !important;
    font-size: 13px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#projectTable.data-table tbody td:last-child {
    border-right: none !important;
}
table#projectTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
table#projectTable.dataTable > tbody > tr:last-child > *,
table#projectTable.data-table > tbody > tr:last-child > * {
    border-bottom: 0 !important;
    box-shadow: none !important;
}
table#projectTable.data-table tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* Tablo Altı Sayfalama ve Bilgi Alanı */
#projectTable_wrapper .dt-layout-row:last-child,
div.dt-container .dt-layout-row:last-child {
    margin: 0 !important;
    padding: 10px 16px !important;
    background: transparent !important;
    border-top: none !important;
    box-shadow: none !important;
}

#projectTable th:last-child,
#projectTable td:last-child {
    width: 90px !important;
    min-width: 90px !important;
    text-align: right !important;
    white-space: nowrap;
    padding-right: 12px !important;
}

/* Dark Mode */
[data-bs-theme="dark"] table#projectTable.data-table thead th {
    background: #0f172a !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#projectTable.data-table,
[data-bs-theme="dark"] table#projectTable.dataTable {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#projectTable.data-table tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#projectTable.data-table tbody tr:last-child td {
    border-bottom: none !important;
}
[data-bs-theme="dark"] table#projectTable.data-table tbody tr:hover td {
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
[data-bs-theme="dark"] .projects-search-clear {
    color: #94a3b8;
    background: #334155;
}
[data-bs-theme="dark"] .page-wrapper:has(#projectsPage) {
    background: #0f172a !important;
}
[data-bs-theme="dark"] .project-summary-card,
[data-bs-theme="dark"] .projects-table-card {
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
</style>

<script>
$(document).ready(function() {
    var $summaryToggle = $('#toggleProjectsSummary');
    var $projectTable = $('#projectTable');

    function syncSummaryToggle() {
        var isCollapsed = document.documentElement.classList.contains('projects-summary-collapsed');
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
        var isCollapsed = document.documentElement.classList.toggle('projects-summary-collapsed');
        try {
            localStorage.setItem('projects_summary_collapsed', isCollapsed ? '1' : '0');
        } catch (e) {}
        syncSummaryToggle();
    });

    // Sütunların yapılandırması
    var columnConfig = {
        1: { label: 'Türü', default: true },
        2: { label: 'Firma Adı', default: true },
        3: { label: 'Proje Adı', default: true },
        4: { label: 'Proje Bedeli', default: true },
        5: { label: 'Şehir', default: true },
        6: { label: 'İlçe', default: true },
        7: { label: 'Başlama Tarihi', default: true },
        8: { label: 'Tahmini Bitiş Tarihi', default: true },
        9: { label: 'Kalan Gün / İlerleme', default: true },
        10: { label: 'Personel', default: true }
    };

    var savedVisibility = {};
    try {
        var rawStored = localStorage.getItem('projects_column_visibility');
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
            { targets: [0, 11], orderable: false, searchable: false },
            { targets: [0, 7, 8, 10], className: 'text-center' },
            { targets: 4, className: 'text-end' },
            { targets: 11, width: '90px', className: 'text-end no-export actions-column' }
        ],
        buttons: [
            {
                extend: 'excelHtml5',
                className: 'd-none',
                title: 'Proje Listesi',
                filename: 'projeler_' + new Date().toLocaleDateString('tr-TR').replace(/\./g, '-'),
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
                window.initDataTableColumnFilters($('#projectTable'), api);
            }

            // Dil dosyasi yuklenip DOM satirlari DataTables'a aktarildiktan
            // sonra olculendir ve ciz. Bundan once draw() cagirmak tbody'yi
            // bos durum satiriyla degistiriyordu.
            api.columns.adjust().draw(false);
        }
    };

    var table = $.fn.DataTable.isDataTable($projectTable[0])
        ? $projectTable.DataTable()
        : $projectTable.DataTable(tableOptions);

    var $colvisMenu = $('#projectsColvisMenu');
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
            localStorage.setItem('projects_column_visibility', JSON.stringify(savedVisibility));
        } catch(err) {}
    });

    $colvisMenu.on('click', function(e) {
        e.stopPropagation();
    });

    // Özet Kartı Radyo Filtreleri
    $('.project-type-filter').on('change', function() {
        var filterType = $(this).val();
        if (!table) return;

        if (!filterType) {
            table.column(1).search('').draw();
        } else {
            table.column(1).search(filterType).draw();
        }
    });

    // Hızlı Genel Arama Inputu
    var searchTimer = null;
    $('#projects-fast-search').on('input', function() {
        var val = this.value;
        $('#projects-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (table) table.search(val).draw();
        }, 300);
    });

    $('#projects-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#projects-fast-search').val('').trigger('focus');
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

    // Tabloda Sağ Tık (Custom Context Menu)
    $(document).on('contextmenu', '#projectTable tbody tr', function(e) {
        var $tr = $(this);
        var projectId = $tr.attr('data-project-id');
        var projectName = $tr.attr('data-project-name') || 'Proje İşlemleri';
        var projectType = $tr.attr('data-project-type');

        if (!projectId) return;

        e.preventDefault();

        $('#projectTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $contextMenu = $('#customContextMenu');
        if (!$contextMenu.length) {
            $contextMenu = $('<div id="customContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var menuHtml = `
            <div class="cm-header"><i class="ti ti-building-community me-1"></i> ${$('<div>').text(projectName).html()}</div>
            <a href="#" class="route-link" data-page="projects/manage&id=${projectId}"><i class="ti ti-eye"></i> Proje Detayları</a>
            <a href="#" class="update-project" data-id="${projectId}"><i class="ti ti-edit"></i> Bilgileri Güncelle</a>
            ${projectType == '1' ? `<a href="#" class="add-progress-payment" data-id="${projectId}"><i class="ti ti-upload text-success"></i> Hakediş Ekle</a>` : ''}
            ${projectType == '2' ? `<a href="#" class="add-payment" data-id="${projectId}"><i class="ti ti-download text-success"></i> Ödeme Ekle</a>` : ''}
            <a href="#" class="add-expense" data-id="${projectId}"><i class="ti ti-receipt text-warning"></i> Masraf Ekle</a>
            <a href="#" class="route-link" data-page="projects/manage&id=${projectId}#tabs-personnel-3"><i class="ti ti-users"></i> Personelleri Gör</a>
            <div class="cm-divider"></div>
            <a href="#" class="cm-danger delete-project" data-id="${projectId}"><i class="ti ti-trash"></i> Projeyi Sil</a>
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

    $(document).on('click', function(e) {
        if (!$(e.target).closest('#customContextMenu').length) {
            $('#customContextMenu').hide();
            $('#projectTable tbody tr').removeClass('context-menu-active');
        }
    });

    $(document).on('click', '#customContextMenu a', function() {
        $('#customContextMenu').hide();
        $('#projectTable tbody tr').removeClass('context-menu-active');
    });

    $(window).on('scroll resize blur', function() {
        $('#customContextMenu').hide();
        $('#projectTable tbody tr').removeClass('context-menu-active');
    });
});
</script>

<?php include_once ROOT . '/pages/projects/modals/progress-payment-modal.php' ?>
<?php include_once ROOT . '/pages/projects/modals/payment-modal.php' ?>
<?php include_once ROOT . '/pages/projects/modals/expense-modal.php' ?>
<?php include_once ROOT . '/pages/projects/modals/project-modal.php' ?>
