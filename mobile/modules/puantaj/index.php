<?php
// Puantor Mobil - Hızlı Puantaj Girişi (Masaüstü Pratikliğinde)
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Puantaj.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/projects.php";

use App\Helper\Date;
use App\Helper\Security;

$personsModel = new Persons();
$puantajModel = new Puantaj();
$projectHelper = new ProjectHelper();
$projectsModel = new Projects();

$firm_id = $_SESSION['firm_id'] ?? 0;
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_project_id = intval($_GET['project_id'] ?? 0);

$persons = $personsModel->getPersonsByFirm($firm_id);

if ($selected_project_id > 0) {
    $filtered_persons = [];
    foreach ($persons as $person) {
        $is_member = ($projectsModel->isExistPersonInProject($selected_project_id, $person->id) > 0);
        $is_default_project = (isset($person->project_id) && intval($person->project_id) == $selected_project_id);
        if ($is_member || $is_default_project) {
            $filtered_persons[] = $person;
        }
    }
    $persons = $filtered_persons;
}

// Puantaj Türlerini dinamik olarak veritabanından çekelim!
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

<div class="container px-0">
    <div class="mb-2">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Hızlı Puantaj</h2>
                <div class="d-flex align-items-center gap-2">
                    <a href="puantaj?date=<?php echo $prev_date; ?>&project_id=<?php echo $selected_project_id; ?>" class="btn btn-icon bg-secondary-lt border-0 text-secondary rounded-3 p-0" style="width: 34px; height: 34px; min-height: auto !important; display: flex; align-items: center; justify-content: center;" title="Önceki Gün">
                        <i class="ti ti-chevron-left fs-3"></i>
                    </a>
                    <div class="position-relative d-inline-block">
                        <input type="text" id="datePicker" class="form-control form-control-sm border-0 bg-secondary-lt text-bold text-center" 
                                value="<?php echo date('d.m.Y', strtotime($selected_date)); ?>" 
                                style="width: 100px; height: 34px; border-radius: 10px; cursor: pointer; padding-right: 1.6rem; font-size: 0.82rem; color: #1d273b !important; min-height: auto !important;">
                        <i class="ti ti-calendar position-absolute text-muted" style="right: 6px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 0.85rem;"></i>
                    </div>
                    <?php if (!$is_today_or_future): ?>
                        <a href="puantaj?date=<?php echo $next_date; ?>&project_id=<?php echo $selected_project_id; ?>" class="btn btn-icon bg-secondary-lt border-0 text-secondary rounded-3 p-0" style="width: 34px; height: 34px; min-height: auto !important; display: flex; align-items: center; justify-content: center;" title="Sonraki Gün">
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
                    onclick="location.href='puantaj?date=<?php echo date('Y-m-d'); ?>&project_id=<?php echo $selected_project_id; ?>'">Bugün</button>
            <button class="btn btn-sm btn-pill <?php echo $selected_date == date('Y-m-d', strtotime('-1 day')) ? 'btn-primary' : 'btn-outline-primary'; ?>"
                    onclick="location.href='puantaj?date=<?php echo date('Y-m-d', strtotime('-1 day')); ?>&project_id=<?php echo $selected_project_id; ?>'">Dün</button>
            <button class="btn btn-sm btn-pill btn-outline-secondary" onclick="setAll('X')">Tümünü Geldi Yap</button>
        </div>
        
        <!-- Proje Seçimi -->
        <?php
        $all_projects = $projectsModel->getProjectsByFirm($firm_id);
        ?>
        <div class="mt-2 position-relative">
            <select id="projectSelect" class="form-select border-0 bg-secondary-lt text-semibold py-2" style="border-radius: 10px; font-size: 0.82rem; color: #1d273b; cursor: pointer; padding-left: 1rem; padding-right: 2rem; appearance: none; -webkit-appearance: none; height: 34px;">
                <option value="0">Tüm Projeler (Filtresiz)</option>
                <?php foreach ($all_projects as $proj): ?>
                    <option value="<?php echo $proj->id; ?>" <?php echo ($selected_project_id == $proj->id) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($proj->project_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <i class="ti ti-chevron-down position-absolute text-muted" style="right: 12px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 0.9rem;"></i>
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

            $current_status_id = $puantajModel->getPuantajTuruId($person->id, str_replace('-', '', $selected_date));
            $puantaj_project_id = $puantajModel->getPuantajProjectId($person->id, str_replace('-', '', $selected_date));
            
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
            <div class="list-group-item list-group-item-action py-2.5 person-row cursor-pointer d-flex align-items-center justify-content-between" 
                 data-person-id="<?php echo $person->id; ?>" 
                 data-person-key="<?php echo Security::encrypt($person->id); ?>"
                 data-person-name="<?php echo htmlspecialchars($person->full_name); ?>"
                 data-current-type-id="<?php echo $current_status_id; ?>"
                 data-name="<?php echo mb_strtolower($person->full_name, 'UTF-8'); ?>"
                 data-is-disabled="<?php echo $is_disabled ? 'true' : 'false'; ?>"
                 onclick="<?php echo $is_disabled ? "Swal.fire({icon: 'info', title: 'Puantaj Kilitli', text: 'Bu personelin bu tarihteki puantajı başka bir projede (" . htmlspecialchars($disabled_project_name) . ") girilmiştir. Değiştirilemez.', confirmButtonText: 'Tamam'})" : "openPuantajModal(this)"; ?>"
                 style="gap: 12px; border-radius: 0; <?php echo $is_disabled ? 'opacity: 0.7; background-color: rgba(241, 245, 249, 0.4); pointer-events: auto;' : ''; ?>">
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

<style>
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

<script>
// jQuery'nin $ olarak tanımlandığından emin olalım
if (typeof $ === 'undefined' && typeof jQuery !== 'undefined') {
    var $ = jQuery;
}

document.addEventListener('DOMContentLoaded', function() {
    // Search Filtering
    const searchInput = document.getElementById('puantajSearchInput');
    const rows = document.querySelectorAll('.person-row');

    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            rows.forEach(row => {
                const name = row.getAttribute('data-name');
                if (name.includes(term)) {
                    row.style.setProperty('display', 'flex', 'important');
                } else {
                    row.style.setProperty('display', 'none', 'important');
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
            const projId = document.getElementById('projectSelect').value || 0;
            location.href = `puantaj?date=${ymdDate}&project_id=${projId}`;
        }
    });

    // Project Select Change Handler
    const projectSelect = document.getElementById('projectSelect');
    if (projectSelect) {
        projectSelect.addEventListener('change', function() {
            const projId = this.value;
            const date = '<?php echo $selected_date; ?>';
            location.href = `puantaj?date=${date}&project_id=${projId}`;
        });
    }
});

let currentSelectedPersonId = null;
let currentSelectedPersonKey = null;
let currentSelectedTypeId = null;

function openPuantajModal(element) {
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
    saveSelectedPuantaj();
}

function saveSelectedPuantaj() {
    if (!currentSelectedPersonId || !currentSelectedTypeId) {
        bootstrap.Modal.getInstance(document.getElementById('puantajModal')).hide();
        return;
    }
    
    const selectedOption = document.querySelector(`.type-option-row[data-type-id="${currentSelectedTypeId}"]`);
    const typeCode = selectedOption.getAttribute('data-type-code');
    const typeLabel = selectedOption.getAttribute('data-type-label');
    const typeColor = selectedOption.getAttribute('data-type-color');
    const typeTextColor = selectedOption.getAttribute('data-type-text-color');
    
    const date = '<?php echo $selected_date; ?>';
    
    const badge = document.getElementById(`status-badge-${currentSelectedPersonId}`);
    const originalContent = badge.outerHTML;
    
    badge.innerText = "...";
    badge.className = "avatar avatar-md rounded-circle font-weight-bold";
    badge.style.backgroundColor = '#f1f5f9';
    badge.style.color = '#94a3b8';
    
    bootstrap.Modal.getInstance(document.getElementById('puantajModal')).hide();
    
    jQuery.ajax({
        url: '/modules/puantaj/api/puantaj-save.php',
        method: 'POST',
        data: {
            person_id: currentSelectedPersonId,
            date: date,
            type_id: currentSelectedTypeId,
            project_id: '<?php echo $selected_project_id; ?>'
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
        error: function(xhr, status, error) {
            badge.outerHTML = originalContent;
            alert('Sunucu hatası (' + xhr.status + '): ' + xhr.responseText);
        }
    });
}

function setAll(typeCode) {
    const typeOption = document.querySelector(`.type-option-row[data-type-code="${typeCode}"]`);
    if (!typeOption) {
        Swal.fire({
            icon: 'error',
            title: 'Hata',
            text: `"${typeCode}" puantaj türü sistemde bulunamadı.`
        });
        return;
    }
    
    Swal.fire({
        title: 'Emin misiniz?',
        text: `Tüm personelleri "${typeCode}" olarak işaretlemek üzeresiniz.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Evet, İşaretle',
        cancelButtonText: 'Vazgeç'
    }).then((result) => {
        if (result.isConfirmed) {
            const typeId = typeOption.getAttribute('data-type-id');
            const typeColor = typeOption.getAttribute('data-type-color');
            const typeTextColor = typeOption.getAttribute('data-type-text-color');
            const date = '<?php echo $selected_date; ?>';
            const rows = document.querySelectorAll('.person-row');
            
            Swal.fire({
                title: 'İşleniyor...',
                html: 'Puantajlar kaydediliyor, lütfen bekleyin. <br><b>0</b> / ' + rows.length,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            let completed = 0;
            const total = rows.length;
            
            if (total === 0) {
                Swal.fire('Uyarı', 'Listede personel bulunamadı.', 'warning');
                return;
            }

            rows.forEach(row => {
                if (row.getAttribute('data-is-disabled') === 'true') {
                    completed++;
                    Swal.getHtmlContainer().querySelector('b').innerText = completed;
                    if (completed === total) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Başarılı',
                            text: 'Puantaj başarıyla güncellendi',
                            confirmButtonText: 'OK'
                        });
                        setTimeout(() => {
                            document.querySelectorAll('.person-row.saved').forEach(r => r.classList.remove('saved'));
                        }, 2000);
                    }
                    return;
                }
                const personId = row.getAttribute('data-person-id');
                const badge = document.getElementById(`status-badge-${personId}`);
                
                jQuery.ajax({
                    url: '/modules/puantaj/api/puantaj-save.php',
                    method: 'POST',
                    data: {
                        person_id: personId,
                        date: date,
                        type_id: typeId,
                        project_id: '<?php echo $selected_project_id; ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            badge.style.backgroundColor = typeColor;
                            badge.style.color = typeTextColor;
                            badge.className = "avatar avatar-md rounded-circle font-weight-bold";
                            badge.innerText = typeCode;
                            
                            row.setAttribute('data-current-type-id', typeId);
                            row.classList.add('saved');
                        }
                    },
                    complete: function() {
                        completed++;
                        Swal.getHtmlContainer().querySelector('b').innerText = completed;
                        
                        if (completed === total) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Başarılı',
                                text: 'Puantaj başarıyla güncellendi',
                                confirmButtonText: 'OK'
                            });
                            setTimeout(() => {
                                document.querySelectorAll('.person-row.saved').forEach(r => r.classList.remove('saved'));
                            }, 2000);
                        }
                    }
                });
            });
        }
    });
}
</script>
