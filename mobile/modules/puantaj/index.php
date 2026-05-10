<?php
// Puantor Mobil - Hızlı Puantaj Girişi (Masaüstü Pratikliğinde)
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Puantaj.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/App/Helper/jobs.php";
require_once ROOT . "/App/Helper/teams.php";

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
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_project_id = intval($_GET['project_id'] ?? 0);
$selected_job_group = intval($_GET['job_group'] ?? 0);
$selected_team_id = intval($_GET['team_id'] ?? 0);
$selected_collar_type = $_GET['collar_type'] ?? 'all'; // all, blue, white

// Ayarlar: Beyaz yakalıları göster
$showWhiteCollarSetting = $settingsModel->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;
$showWhiteCollar = ($selected_collar_type === 'white' || $selected_collar_type === 'all') ? 1 : 0;
// Eğer genel ayar kapalıysa ve filtre 'all' ise sadece mavileri göster
if ($showWhiteCollarSetting == 0 && $selected_collar_type === 'all') $showWhiteCollar = 0;

// Masaüstü ile %100 aynı personelleri getirmek için ortak fonksiyonu kullanıyoruz
$first_day_ymd = date('Ymd', strtotime($selected_date . ' -0 days'));
$last_day_ymd = date('Ymd', strtotime(date('Y-m-t', strtotime($selected_date))));

// Masaüstü listesi bu mantığı kullanır:
$all_projects = $projectsModel->getProjectsByFirm($firm_id);

if ($selected_project_id == 0) {
    $persons = $personsModel->getPersonIdByFirmBlueCollarCurrentMonth($firm_id, $first_day_ymd, $last_day_ymd, $selected_job_group, $selected_team_id, $showWhiteCollar);
} else {
    $persons = $projectsModel->getPersonIdByFromProjectCurrentMonth($selected_project_id, $first_day_ymd, $last_day_ymd, $selected_job_group, $selected_team_id, $showWhiteCollar);
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
        width: 70px;
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
        font-size: 1.2rem;
        margin-bottom: 2px;
    }

    /* Filtre Select Tweaks */
    #filterModal .form-select {
        height: 42px;
        font-size: 0.9rem;
    }
    #filterModal .btn-group .btn {
        font-size: 0.82rem;
        font-weight: 500;
        padding: 10px;
    }
    #filterModal .btn-check:checked + .btn {
        background-color: var(--mobile-primary) !important;
        color: white !important;
    }
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    .person-row.saved { background-color: rgba(47, 179, 68, 0.04) !important; transition: background 0.3s; }
    
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
    body[data-bs-theme="dark"] .modal-content {
        background-color: #1a2234 !important;
        color: #f4f6fa !important;
    }
    body[data-bs-theme="dark"] .border-end {
        border-color: var(--mobile-card-border-dark) !important;
    }
</style>

<div class="container px-0">
    <div class="mb-2">
        <?php 
        $base_params = $_GET;
        unset($base_params['date']);
        $query_str = http_build_query($base_params);
        $prev_url = "puantaj?date=$prev_date" . ($query_str ? "&$query_str" : "");
        $next_url = "puantaj?date=$next_date" . ($query_str ? "&$query_str" : "");
        $today_url = "puantaj?date=" . date('Y-m-d') . ($query_str ? "&$query_str" : "");
        $yesterday_url = "puantaj?date=" . date('Y-m-d', strtotime('-1 day')) . ($query_str ? "&$query_str" : "");
        ?>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Hızlı Puantaj</h2>
                <div class="d-flex align-items-center gap-2">
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
        </div>
        <div class="d-flex gap-2 overflow-auto pb-2 no-scrollbar">
            <button class="btn btn-sm btn-pill <?php echo $selected_date == date('Y-m-d') ? 'btn-primary' : 'btn-outline-primary'; ?>" 
                    onclick="location.href='<?php echo $today_url; ?>'">Bugün</button>
            <button class="btn btn-sm btn-pill <?php echo $selected_date == date('Y-m-d', strtotime('-1 day')) ? 'btn-primary' : 'btn-outline-primary'; ?>"
                    onclick="location.href='<?php echo $yesterday_url; ?>'">Dün</button>
            <button class="btn btn-sm btn-pill btn-outline-secondary" onclick="openBulkPuantajModal()">Tümünü işaretle</button>
            <button class="btn btn-sm btn-icon btn-outline-secondary rounded-pill" data-bs-toggle="modal" data-bs-target="#filterModal" style="width: 32px; height: 32px; min-height: auto !important;">
                <i class="ti ti-filter fs-3"></i>
            </button>
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
    <div class="search-container mb-2">
        <i class="ti ti-search search-icon"></i>
        <input type="text" id="puantajSearchInput" class="search-input" placeholder="Personel ara...">
    </div>

    <div class="list-group list-group-mobile mb-5" id="puantajListContainer">
        <?php foreach ($persons as $person): 
            // İş başlama ve ayrılış tarihlerine göre filtreleme
            $start_dt = !empty($person->job_start_date) ? date('Y-m-d', strtotime($person->job_start_date)) : null;
            $end_dt = !empty($person->job_end_date) ? date('Y-m-d', strtotime($person->job_end_date)) : null;
            
            if ($start_dt && $selected_date < $start_dt) continue;
            if ($end_dt && $selected_date > $end_dt) continue;

            // Veri çekme mantığını esnetiyoruz: Hem tireli hem tiresiz formatı kontrol et
            $date_dash = $selected_date; // 2026-05-08
            $date_nodash = str_replace('-', '', $selected_date); // 20260508
            
            // Masaüstü ile tam uyumlu olması için: Önce herhangi bir projedeki kaydı bul
            $current_status_id = $puantajModel->getPuantajTuruId($person->id, $date_dash, -1);
            if (empty($current_status_id)) {
                $current_status_id = $puantajModel->getPuantajTuruId($person->id, $date_nodash, -1);
            }

            $puantaj_project_id = $puantajModel->getPuantajProjectId($person->id, $date_dash, -1);
            if (empty($puantaj_project_id)) {
                $puantaj_project_id = $puantajModel->getPuantajProjectId($person->id, $date_nodash, -1);
            }

            // Hafta sonu (Pazar) HT otomatik gösterme (Sadece hiç kayıt yoksa)
            if (empty($current_status_id) && Date::isWeekend($selected_date)) {
                $current_status_id = 53; // HT ID
            }
            
            $is_disabled = false;
            $disabled_project_name = '';
            if ($selected_project_id > 0 && $puantaj_project_id > 0 && $puantaj_project_id != $selected_project_id) {
                $is_disabled = true;
                $disabled_project_name = $projectHelper->getProjectName($puantaj_project_id);
            }

            $current_type = null;
            if (!empty($current_status_id)) {
                $current_type = $puantajModel->getPuantajTuruById($current_status_id);
            }
        ?>
            <div class="person-item-wrapper" data-name="<?php echo mb_strtolower($person->full_name, 'UTF-8'); ?>">
                <div class="person-item-actions">
                    <button class="btn-swipe-clear" onclick="clearPuantaj('<?php echo $person->id; ?>', '<?php echo htmlspecialchars($person->full_name); ?>')" <?php echo $is_disabled ? 'disabled style="opacity: 0.5;"' : ''; ?>>
                        <i class="ti ti-rotate-clockwise-2"></i>
                        <span>Temizle</span>
                    </button>
                </div>
                <div class="person-item-content">
                    <div class="list-group-item list-group-item-action py-2.5 person-row cursor-pointer d-flex align-items-center justify-content-between" 
                         data-person-id="<?php echo $person->id; ?>" 
                         data-person-key="<?php echo Security::encrypt($person->id); ?>"
                         data-person-name="<?php echo htmlspecialchars($person->full_name); ?>"
                         data-current-type-id="<?php echo $current_status_id; ?>"
                         data-name="<?php echo mb_strtolower($person->full_name, 'UTF-8'); ?>"
                         data-is-disabled="<?php echo $is_disabled ? 'true' : 'false'; ?>"
                         onclick="<?php echo $is_disabled ? "Swal.fire({icon: 'info', title: 'Puantaj Kilitli', text: 'Bu personelin bu tarihteki puantajı başka bir projede (" . htmlspecialchars($disabled_project_name) . ") girilmiştir. Değiştirilemez.', confirmButtonText: 'Tamam'})" : "openPuantajModal(this)"; ?>"
                         style="gap: 12px; border-radius: 0; border: none; <?php echo $is_disabled ? 'opacity: 0.7; background-color: rgba(241, 245, 249, 0.4); pointer-events: auto;' : ''; ?>">
                        <div style="min-width: 0; flex: 1;">
                            <div class="text-semibold text-dark mb-0" style="font-size: 0.92rem; letter-spacing: -0.2px; line-height: 1.2;">
                                <?php echo htmlspecialchars($person->full_name); ?>
                            </div>
                            <div class="text-muted" style="font-size: 0.72rem; opacity: 0.7; font-weight: 500; margin-top: 2px;">
                                <?php if ($is_disabled): ?>
                                    <span class="text-danger" style="font-weight: 600;"><i class="ti ti-lock me-1"></i><?php echo htmlspecialchars($disabled_project_name); ?> (Kilitli)</span>
                                <?php else: ?>
                                    <?php 
                                    if ($puantaj_project_id > 0 && $selected_project_id == 0) {
                                        $proj_name = $projectHelper->getProjectName($puantaj_project_id);
                                        echo '<span class="text-primary" style="font-weight: 600;"><i class="ti ti-subtask me-1"></i>' . htmlspecialchars($proj_name) . '</span>';
                                    } else {
                                        echo !empty($person->job) ? htmlspecialchars($person->job) : 'Görev eklenmedi'; 
                                    }
                                    ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Sağ Taraf: Minimal Badge -->
                        <div style="flex-shrink: 0;">
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
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Puantaj Seçim Modalı -->
<div class="modal modal-blur fade" id="puantajModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content" style="border-radius: 24px; border: none; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
            <div class="modal-header border-0 pb-0">
                <div>
                    <h5 class="modal-title font-weight-bold text-dark mb-1" id="modalPersonName" style="font-size: 1.15rem;">Personel Adı</h5>
                    <p class="text-muted text-xs mb-0" style="font-weight: 500;">
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
                                                 data-type-id="<?php echo $type->id; ?>"
                                                 data-type-code="<?php echo htmlspecialchars($type->PuantajKod); ?>"
                                                 data-type-label="<?php echo htmlspecialchars($type->PuantajAdi); ?>"
                                                 data-type-color="<?php echo htmlspecialchars($type->ArkaPlanRengi); ?>"
                                                 data-type-text-color="<?php echo htmlspecialchars($type->FontRengi); ?>"
                                                 onclick="selectTypeOption(this)"
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
            <div class="modal-footer border-0 pt-0 d-flex justify-content-start">
                <button type="button" class="btn btn-link text-muted px-0 text-decoration-none text-xs font-weight-bold" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<!-- Filtre Modalı -->
<div class="modal modal-blur fade" id="filterModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title font-weight-bold">Filtrele</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="filterForm">
                    <div class="mb-3">
                        <label class="form-label text-xs font-weight-bold text-muted">PROJE</label>
                        <select name="project_id" class="form-select border-0 bg-secondary-lt" style="border-radius: 12px;">
                            <option value="0">Tüm Projeler</option>
                            <?php foreach ($all_projects as $proj): ?>
                                <option value="<?php echo $proj->id; ?>" <?php echo ($selected_project_id == $proj->id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($proj->project_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-xs font-weight-bold text-muted">PERSONEL TİPİ</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="collar_type" id="collar_all" value="all" <?php echo $selected_collar_type == 'all' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="collar_all" style="border-radius: 12px 0 0 12px;">Hepsi</label>

                            <input type="radio" class="btn-check" name="collar_type" id="collar_blue" value="blue" <?php echo $selected_collar_type == 'blue' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="collar_blue">Mavi Yaka</label>

                            <input type="radio" class="btn-check" name="collar_type" id="collar_white" value="white" <?php echo $selected_collar_type == 'white' ? 'checked' : ''; ?>>
                            <label class="btn btn-outline-primary border-0 bg-secondary-lt" for="collar_white" style="border-radius: 0 12px 12px 0;">Beyaz Yaka</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-xs font-weight-bold text-muted">İŞ GRUBU / GÖREV</label>
                        <?php echo $jobsHelper->jobGroupsSelect('job_group', $selected_job_group); ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-xs font-weight-bold text-muted">EKİBİ</label>
                        <?php echo $teamsHelper->teamsSelect('team_id', $selected_team_id); ?>
                    </div>

                    <div class="mt-4">
                        <button type="button" class="btn btn-primary w-100 py-2.5" onclick="applyFilters()" style="border-radius: 14px; font-weight: 600;">Filtreleri Uygula</button>
                        <button type="button" class="btn btn-link w-100 mt-2 text-muted text-xs text-decoration-none" onclick="clearFilters()">Seçimleri Temizle</button>
                    </div>
                </form>
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
    // Search Filtering
    const searchInput = document.getElementById('puantajSearchInput');
    const items = document.querySelectorAll('.person-item-wrapper');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
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
            const projId = '<?php echo $selected_project_id; ?>' || 0;
            location.href = `puantaj?date=${ymdDate}&project_id=${projId}`;
        }
    });

    // Swipe logic
    let touchStartX = 0;
    let touchMoveX = 0;
    let currentSwipeItem = null;
    const swipeThreshold = 70;

    $(document).on('touchstart', '.person-item-content', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
        touchMoveX = touchStartX;
        currentSwipeItem = $(this);
        
        // Diğer açık olanları kapat
        $('.person-item-content').not(currentSwipeItem).css('transform', 'translateX(0)');
        $('.person-item-actions').css('visibility', 'hidden');
        
        // Bu elemanın aksiyonlarını görünür yap
        currentSwipeItem.siblings('.person-item-actions').css('visibility', 'visible');
    });

    $(document).on('touchmove', '.person-item-content', function(e) {
        touchMoveX = e.originalEvent.touches[0].clientX;
        let diff = touchStartX - touchMoveX;
        if (diff > 0) {
            if (diff > swipeThreshold + 20) diff = swipeThreshold + 20;
            $(this).css('transition', 'none');
            $(this).css('transform', 'translateX(-' + diff + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    $(document).on('touchend', '.person-item-content', function(e) {
        let diff = touchStartX - touchMoveX;
        $(this).css('transition', 'transform 0.2s ease-out');
        if (diff > swipeThreshold / 2) {
            $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
            setTimeout(() => {
                $(this).siblings('.person-item-actions').css('visibility', 'hidden');
            }, 200);
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
    badge.innerText = "...";
    badge.style.backgroundColor = '#f1f5f9';
    badge.style.color = '#94a3b8';

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
    
    location.href = url.toString();
}

function clearFilters() {
    const url = new URL(window.location.href);
    const date = url.searchParams.get('date') || '<?php echo date('Y-m-d'); ?>';
    location.href = `puantaj?date=${date}`;
}

let currentSelectedPersonId = null;
let currentSelectedPersonKey = null;
let currentSelectedTypeId = null;
let isBulkMode = false;

function openBulkPuantajModal() {
    isBulkMode = true;
    currentSelectedPersonId = null;
    currentSelectedPersonKey = null;
    currentSelectedTypeId = null;
    
    document.getElementById('modalPersonName').innerText = "Tüm Personeller";
    
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
    
    const modal = new bootstrap.Modal(document.getElementById('puantajModal'));
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
    
    const modal = new bootstrap.Modal(document.getElementById('puantajModal'));
    modal.show();
}

function selectTypeOption(element) {
    document.querySelectorAll('.type-option-row').forEach(row => {
        row.classList.remove('selected');
    });
    element.classList.add('selected');
    currentSelectedTypeId = element.getAttribute('data-type-id');
    
    // Seçim yapınca direkt atama yapsın!
    if (isBulkMode) {
        saveBulkPuantaj(element);
    } else {
        saveSelectedPuantaj(element);
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
            
            rows.forEach(row => {
                if (row.getAttribute('data-is-disabled') === 'true') return;
                if (row.style.display === 'none') return;
                
                const personKey = row.getAttribute('data-person-key');
                payload[personKey] = {};
                payload[personKey][serverDate] = {
                    puantajId: typeId,
                    project_id: <?php echo (int)$selected_project_id; ?>
                };
                
                const personId = row.getAttribute('data-person-id');
                const badge = document.getElementById(`status-badge-${personId}`);
                if(badge) {
                    badge.innerText = "...";
                    badge.style.backgroundColor = '#f1f5f9';
                    badge.style.color = '#94a3b8';
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
    
    badge.innerText = "...";
    badge.style.backgroundColor = '#f1f5f9';
    badge.style.color = '#94a3b8';
    
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

</script>
