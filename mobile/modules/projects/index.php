<?php
// Puantor Mobil - Proje Listesi
require_once ROOT . "/Model/ProjectIncomeExpense.php";

use App\Helper\Helper;
use App\Helper\Security;

$projectsModel = new Projects();
$incexpModel = new ProjectIncomeExpense();
$firm_id = $_SESSION['firm_id'] ?? 0;
$projects = $projectsModel->getProjectsByFirm($firm_id);

$active_projects = 0;
$passive_projects = 0;
foreach($projects as $p) {
    if(($p->status ?? 1) == 1) $active_projects++;
    else $passive_projects++;
}
?>
<style>
:root {
    --project-card-bg: #ffffff;
    --project-card-border: rgba(0, 0, 0, 0.08);
    --project-text-main: #1d273b;
    --project-text-muted: #64748b;
}

body[data-bs-theme="dark"] {
    --project-card-bg: #1e293b;
    --project-card-border: rgba(255, 255, 255, 0.1);
    --project-text-main: #f4f6fa;
    --project-text-muted: #94a3b8;
}

.project-item-wrapper {
    position: relative;
    overflow: hidden;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    user-select: none;
}
body[data-bs-theme="dark"] .project-item-wrapper,
body[data-bs-theme="dark"] .project-item-content {
    background: #1e293b !important;
}
.project-item-actions {
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    display: flex;
    align-items: center;
    background: var(--mobile-primary);
    z-index: 1;
}
.project-item-content {
    position: relative;
    background: #fff;
    z-index: 2;
    transition: transform 0.2s ease-out;
    width: 100%;
}
.btn-swipe-action {
    color: white;
    width: 70px;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: none;
    background: transparent;
    font-size: 0.7rem;
    font-weight: 600;
    text-decoration: none;
}
.btn-swipe-action i {
    font-size: 1.2rem;
    margin-bottom: 2px;
}
</style>

<div class="container px-0">
  
  <!-- Üst Başlık Alanı -->
  <div class="mb-4 d-flex align-items-center justify-content-between pt-2 px-3">
    <div>
      <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Projeler</h2>
      <p class="text-muted text-xs mb-0">Toplam <?php echo count($projects); ?> proje kayıtlı.</p>
    </div>
  </div>

  <!-- Kasa Tasarımı Özet Kartları -->
  <div class="row g-2 mb-3 px-3">
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0 shadow-sm d-flex flex-column h-100" style="background: rgba(47, 179, 68, 0.1); color: #2fb344; border-radius: 16px;">
        <span class="text-bold h3 mb-0"><?php echo $active_projects; ?> <span class="text-xs text-uppercase opacity-75" style="font-size: 0.7rem;">Adet</span></span>
        <div class="text-xs text-uppercase font-weight-bold mt-1" style="font-size: 0.6rem; opacity: 0.8; letter-spacing: 0.5px;">AKTİF PROJELER</div>
      </div>
    </div>
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0 shadow-sm d-flex flex-column h-100" style="background: rgba(214, 63, 63, 0.1); color: #d63f3f; border-radius: 16px;">
        <span class="text-bold h3 mb-0"><?php echo $passive_projects; ?> <span class="text-xs text-uppercase opacity-75" style="font-size: 0.7rem;">Adet</span></span>
        <div class="text-xs text-uppercase font-weight-bold mt-1" style="font-size: 0.6rem; opacity: 0.8; letter-spacing: 0.5px;">PASİF PROJELER</div>
      </div>
    </div>
  </div>

  <!-- Filtre Butonları -->
  <div class="mb-3 px-3">
    <div class="d-flex gap-1 bg-secondary-lt p-1" style="border-radius: 12px; width: 100%;">
      <button type="button" class="btn btn-sm border-0 shadow-none filter-btn active bg-white shadow-sm" data-type="Tümü" style="border-radius: 10px; font-weight: 600; flex: 1 1 0;">Tümü</button>
      <button type="button" class="btn btn-sm border-0 shadow-none filter-btn text-muted" data-type="Alınan" style="border-radius: 10px; font-weight: 600; flex: 1 1 0;">Alınan</button>
      <button type="button" class="btn btn-sm border-0 shadow-none filter-btn text-muted" data-type="Verilen" style="border-radius: 10px; font-weight: 600; flex: 1 1 0;">Verilen</button>
    </div>
  </div>

  <!-- Arama Çubuğu -->
  <div class="search-container mb-4 px-3">
    <i class="ti ti-search search-icon" style="left: 28px;"></i>
    <input type="text" id="project-search" class="search-input shadow-sm" placeholder="Proje ara..." style="padding-left: 45px; border-radius: 16px;">
  </div>

  <!-- Proje Listesi -->
  <div class="list-group list-group-mobile shadow-sm" id="project-list">
    <?php if (empty($projects)): ?>
      <div class="text-center py-5 bg-white rounded-3 border">
        <i class="ti ti-building-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
        <p class="text-muted text-sm mb-0">Henüz kayıtlı proje bulunamadı.</p>
      </div>
    <?php else: ?>
      <?php foreach ($projects as $project): 
        $status = $project->status ?? 1;
        $type = ($project->type ?? 1) == 1 ? 'Alınan' : 'Verilen';
        $person_count = count($projectsModel->getPersonFromProject($project->id));
        $is_active = $status == 1;
        $id_encrypted = Security::encrypt($project->id);
        $initials = mb_substr($project->project_name, 0, 2, 'UTF-8');

        // Calculate progress percentage
        $summary = $incexpModel->sumAllIncomeExpense($project->id);
        $hakedis = $summary->gelir ?? 0; // In manage.php total_income was summed from turu 14 and 1. sumAllIncomeExpense returns it as 'gelir'.
        $contract_amount = $project->contract_amount ?? 0;
        $range = $contract_amount > 0 ? round(($hakedis / $contract_amount) * 100) : 0;
        if($range > 100) $range = 100;
      ?>
        <div class="project-item-wrapper" data-name="<?php echo strtolower($project->project_name . ' ' . ($project->address ?? '')); ?>" data-type="<?php echo $type; ?>">
          <div class="project-item-actions">
            <a href="project-manage?id=<?php echo $id_encrypted; ?>" class="btn-swipe-action">
              <i class="ti ti-arrow-right"></i>
              <span>Detay</span>
            </a>
          </div>
          <div class="project-item-content">
            <div class="list-group-item border-0 py-3 px-3 w-100 bg-transparent d-flex align-items-center justify-content-between update-project" 
                 data-id="<?php echo $id_encrypted; ?>" 
                 style="cursor: pointer;">
              <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                  <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center" style="background: rgba(32, 107, 196, 0.12); color: var(--mobile-primary); width: 42px; height: 42px;">
                    <span class="text-bold" style="font-size: 0.85rem;"><?php echo mb_strtoupper($initials); ?></span>
                  </div>
                  <span class="position-absolute bottom-0 end-0 badge rounded-pill <?php echo $is_active ? 'bg-success' : 'bg-danger'; ?> border-2 border-white" style="width: 12px; height: 12px; padding: 0; transform: translate(15%, 15%); box-shadow: 0 2px 4px rgba(0,0,0,0.1);"></span>
                </div>
                <div>
                  <div class="text-bold text-sm text-dark"><?php echo htmlspecialchars($project->project_name); ?></div>
                  <div class="text-muted text-xs mt-0.5"><?php echo $type; ?> • <?php echo htmlspecialchars(($project->address ?? '') ?: 'Konum Yok'); ?></div>
                </div>
              </div>
              <div class="text-end">
                <div class="text-bold text-sm text-green" style="font-size: 1rem;">
                  <?php echo $person_count; ?>
                </div>
                <div class="text-muted text-xs font-weight-bold opacity-75" style="font-size: 0.65rem;">PERSONEL</div>
              </div>
            </div>
            <!-- Alt Kısım: Tamamlanma Yüzdesi Progress -->
            <div class="px-3 pb-2" style="margin-top: -8px;">
              <div class="progress" style="height: 4px; border-radius: 10px; background: rgba(0,0,0,0.05);">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $range; ?>%;" aria-valuenow="<?php echo $range; ?>" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
              <div class="d-flex justify-content-end mt-1">
                <span class="text-muted" style="font-size: 0.62rem; font-weight: 600; opacity: 0.8;">%<?php echo $range; ?> Tamamlandı</span>
              </div>
            </div>
          </div>
        </div>
<?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Floating Action Button (FAB) -->
<a href="#" class="mobile-fab" id="addNewProject">
  <i class="ti ti-plus"></i>
</a>

<script src="js/projects.js?v=<?php echo time(); ?>"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<script src="js/jquery.inputmask.js"></script>

<script>
$(document).ready(function() {
  // Money mask initialization for mobile modal
  if ($.fn.inputmask) {
    $(".money").inputmask("decimal", {
      radixPoint: ",",
      groupSeparator: ".",
      digits: 2,
      autoGroup: true,
      rightAlign: false,
      removeMaskOnSubmit: true
    });
  }
});

// Mobile helper for towns since app.js is not loaded
function getTowns(cityId, targetElement) {
  var formData = new FormData();
  formData.append("city_id", cityId);
  formData.append("action", "getTowns");

  fetch("../api/il-ilce.php", {
    method: "POST",
    body: formData
  })
    .then((response) => response.json())
    .then((data) => {
      let towns = data.towns;
      $(targetElement).html(towns);
    })
    .catch((error) => {
      console.error("Error:", error);
    });
}
</script>

<?php include_once ROOT . "/pages/projects/modals/project-modal.php"; ?>

<style>
.text-bold { font-weight: 700 !important; }
.text-semibold { font-weight: 600 !important; }
.bg-primary-lt { background-color: rgba(32, 107, 196, 0.08) !important; }
body[data-bs-theme="dark"] .text-dark { color: #f4f6fa !important; }

/* Modal adjustment for mobile */
.modal-dialog {
    margin: 0.5rem;
}
.modal-content {
    border-radius: 20px;
}
</style>
