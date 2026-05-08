<?php
// Puantor Mobil - Hızlı Puantaj Girişi (Masaüstü Pratikliğinde)
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Puantaj.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Date;
use App\Helper\Security;

$personsModel = new Persons();
$puantajModel = new Puantaj();

$firm_id = $_SESSION['firm_id'] ?? 0;
$selected_date = $_GET['date'] ?? date('Y-m-d');
$persons = $personsModel->getPersonsByFirm($firm_id);

// Puantaj Türlerini dinamik olarak veritabanından çekelim!
$db = new Database\Db();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT * FROM puantajturu ORDER BY Turu, PuantajSaati ASC");
$stmt->execute();
$puantaj_types = $stmt->fetchAll(PDO::FETCH_OBJ);

$grouped_types = [];
foreach ($puantaj_types as $type) {
    $grouped_types[$type->Turu][] = $type;
}
?>

<div class="container px-0">
    <div class="mb-4">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Hızlı Puantaj</h2>
            <div class="d-flex gap-2">
                <a href="?p=puantaj_detail" class="btn btn-icon btn-sm btn-outline-secondary border-0" title="Aylık Özet">
                    <i class="ti ti-list-details"></i>
                </a>
                <div class="position-relative d-inline-block">
                    <input type="text" id="datePicker" class="form-control form-control-sm border-0 bg-secondary-lt text-bold text-center" 
                           value="<?php echo date('d.m.Y', strtotime($selected_date)); ?>" 
                           style="width: 130px; border-radius: 10px; cursor: pointer; padding-right: 2rem;">
                    <i class="ti ti-calendar position-absolute text-muted" style="right: 8px; top: 50%; transform: translateY(-50%); pointer-events: none; font-size: 1rem;"></i>
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 overflow-auto pb-2 no-scrollbar">
            <button class="btn btn-sm btn-pill <?php echo $selected_date == date('Y-m-d') ? 'btn-primary' : 'btn-outline-primary'; ?>" 
                    onclick="location.href='?p=puantaj&date=<?php echo date('Y-m-d'); ?>'">Bugün</button>
            <button class="btn btn-sm btn-pill <?php echo $selected_date == date('Y-m-d', strtotime('-1 day')) ? 'btn-primary' : 'btn-outline-primary'; ?>"
                    onclick="location.href='?p=puantaj&date=<?php echo date('Y-m-d', strtotime('-1 day')); ?>'">Dün</button>
            <button class="btn btn-sm btn-pill btn-outline-secondary" onclick="setAll('G')">Tümünü Geldi Yap</button>
        </div>
    </div>

    <!-- Arama Çubuğu -->
    <div class="search-container mb-3">
        <i class="ti ti-search search-icon"></i>
        <input type="text" id="puantajSearchInput" class="search-input" placeholder="Personel ara...">
    </div>

    <div class="list-group list-group-mobile mb-5" id="puantajListContainer">
        <?php foreach ($persons as $person): 
            $current_status_id = $puantajModel->getPuantajTuruId($person->id, $selected_date);
            $current_type = null;
            if (!empty($current_status_id)) {
                $current_type = $puantajModel->getPuantajTuruById($current_status_id);
            }
        ?>
            <div class="list-group-item list-group-item-action py-3 person-row cursor-pointer" 
                 data-person-id="<?php echo $person->id; ?>" 
                 data-person-key="<?php echo Security::encrypt($person->id); ?>"
                 data-person-name="<?php echo htmlspecialchars($person->full_name); ?>"
                 data-current-type-id="<?php echo $current_status_id; ?>"
                 data-name="<?php echo strtolower($person->full_name); ?>"
                 onclick="openPuantajModal(this)">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md rounded-circle text-uppercase font-weight-bold" 
                             style="background-color: #f1f5f9; color: #475569; width: 44px; height: 44px; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.02);">
                            <?php echo mb_substr($person->full_name, 0, 1, 'UTF-8'); ?>
                        </div>
                        <div>
                            <div class="text-bold text-dark mb-0.5" style="font-size: 0.95rem;"><?php echo htmlspecialchars($person->full_name); ?></div>
                            <div class="text-muted text-xs"><?php echo htmlspecialchars($person->job ?? 'Görevi Yok'); ?></div>
                        </div>
                    </div>
                    <div>
                        <?php if ($current_type): ?>
                            <span id="status-badge-<?php echo $person->id; ?>" class="badge px-2.5 py-1.5 text-xs font-weight-bold" 
                                  style="background-color: <?php echo htmlspecialchars($current_type->ArkaPlanRengi); ?>; color: <?php echo htmlspecialchars($current_type->FontRengi); ?>; border-radius: 8px;">
                                <?php echo htmlspecialchars($current_type->PuantajAdi); ?> (<?php echo htmlspecialchars($current_type->PuantajKod); ?>)
                            </span>
                        <?php else: ?>
                            <span id="status-badge-<?php echo $person->id; ?>" class="badge bg-secondary-lt text-secondary px-2.5 py-1.5 text-xs font-weight-bold" style="border-radius: 8px;">
                                Seçilmedi
                            </span>
                        <?php endif; ?>
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
                    <p class="text-muted text-xs mb-0">Puantaj türü ve çalışma saatini seçin</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body py-4">
                <div class="row h-100 g-0">
                    <!-- Sol Liste: Kategoriler -->
                    <div class="col-4 border-end pe-2" style="max-height: 380px; overflow-y: auto;">
                        <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
                            <?php 
                            $is_first = true;
                            foreach ($grouped_types as $category => $items): 
                                $cat_id = md5($category);
                            ?>
                                <button class="nav-link text-start text-xs font-weight-bold py-2 px-3 mb-1 text-truncate <?php echo $is_first ? 'active' : ''; ?>" 
                                        id="v-pills-<?php echo $cat_id; ?>-tab" 
                                        data-bs-toggle="pill" 
                                        data-bs-target="#v-pills-<?php echo $cat_id; ?>" 
                                        type="button" 
                                        role="tab" 
                                        aria-controls="v-pills-<?php echo $cat_id; ?>" 
                                        aria-selected="<?php echo $is_first ? 'true' : 'false'; ?>"
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
                            ?>
                                <div class="tab-pane fade <?php echo $is_first ? 'show active' : ''; ?>" 
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
            <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                <button type="button" class="btn btn-link text-muted px-0 text-decoration-none text-xs font-weight-bold" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary px-4 py-2" id="btnConfirmPuantaj" style="border-radius: 12px; font-size: 0.85rem;">
                    <i class="ti ti-check me-1"></i> Seç
                </button>
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
</style>

<script>
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
                    row.style.setProperty('display', 'block', 'important');
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
        onChange: function(selectedDates, dateStr, instance) {
            const dateParts = dateStr.split(".");
            const ymdDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
            location.href = '?p=puantaj&date=' + ymdDate;
        }
    });
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
}

document.getElementById('btnConfirmPuantaj').addEventListener('click', function() {
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
    const payload = {};
    payload[currentSelectedPersonKey] = {};
    payload[currentSelectedPersonKey][date] = {
        puantajId: currentSelectedTypeId,
        project_id: 0
    };
    
    const badge = document.getElementById(`status-badge-${currentSelectedPersonId}`);
    const originalContent = badge.outerHTML;
    
    badge.innerText = "Kaydediliyor...";
    badge.className = "badge bg-secondary-lt text-secondary px-2.5 py-1.5 text-xs font-weight-bold";
    badge.style.backgroundColor = '';
    badge.style.color = '';
    
    bootstrap.Modal.getInstance(document.getElementById('puantajModal')).hide();
    
    $.ajax({
        url: '../api/puantaj.php',
        method: 'POST',
        data: {
            action: 'savePuantaj',
            data: JSON.stringify(payload)
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                badge.style.backgroundColor = typeColor;
                badge.style.color = typeTextColor;
                badge.className = "badge px-2.5 py-1.5 text-xs font-weight-bold";
                badge.innerText = `${typeLabel} (${typeCode})`;
                
                const row = document.querySelector(`.person-row[data-person-id="${currentSelectedPersonId}"]`);
                row.setAttribute('data-current-type-id', currentSelectedTypeId);
                row.classList.add('saved');
                setTimeout(() => row.classList.remove('saved'), 1000);
            } else {
                badge.outerHTML = originalContent;
                alert('Hata: ' + response.message);
            }
        },
        error: function() {
            badge.outerHTML = originalContent;
            alert('Sunucuyla iletişim kurulurken bir hata oluştu.');
        }
    });
});

function setAll(typeCode) {
    if (!confirm('Tüm personelleri "Geldi" yapmak istediğinize emin misiniz?')) return;
    
    const typeOption = document.querySelector(`.type-option-row[data-type-code="${typeCode}"]`);
    if (!typeOption) {
        alert('Geldi puantaj türü bulunamadı.');
        return;
    }
    
    const typeId = typeOption.getAttribute('data-type-id');
    const typeLabel = typeOption.getAttribute('data-type-label');
    const typeColor = typeOption.getAttribute('data-type-color');
    const typeTextColor = typeOption.getAttribute('data-type-text-color');
    const date = '<?php echo $selected_date; ?>';
    
    const payload = {};
    const rows = document.querySelectorAll('.person-row');
    
    rows.forEach(row => {
        const personKey = row.getAttribute('data-person-key');
        payload[personKey] = {};
        payload[personKey][date] = {
            puantajId: typeId,
            project_id: 0
        };
    });
    
    rows.forEach(row => {
        const personId = row.getAttribute('data-person-id');
        const badge = document.getElementById(`status-badge-${personId}`);
        badge.innerText = "Kaydediliyor...";
        badge.className = "badge bg-secondary-lt text-secondary px-2.5 py-1.5 text-xs font-weight-bold";
        badge.style.backgroundColor = '';
        badge.style.color = '';
    });
    
    $.ajax({
        url: '../api/puantaj.php',
        method: 'POST',
        data: {
            action: 'savePuantaj',
            data: JSON.stringify(payload)
        },
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                rows.forEach(row => {
                    const personId = row.getAttribute('data-person-id');
                    const badge = document.getElementById(`status-badge-${personId}`);
                    badge.style.backgroundColor = typeColor;
                    badge.style.color = typeTextColor;
                    badge.className = "badge px-2.5 py-1.5 text-xs font-weight-bold";
                    badge.innerText = `${typeLabel} (${typeCode})`;
                    
                    row.setAttribute('data-current-type-id', typeId);
                    row.classList.add('saved');
                    setTimeout(() => row.classList.remove('saved'), 1000);
                });
            } else {
                alert('Hata: ' + response.message);
            }
        },
        error: function() {
            alert('Sunucuyla iletişim kurulurken bir hata oluştu.');
        }
    });
}
</script>
