<?php
// Puantor Mobil - Proje Listesi (Kasa Tasarımı Uyumlu)
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Security;

$projectsModel = new Projects();
$firm_id = $_SESSION['firm_id'] ?? 0;
$projects = $projectsModel->getProjectsByFirm($firm_id);

$active_projects = 0;
$passive_projects = 0;
foreach($projects as $p) {
    if(($p->status ?? 1) == 1) $active_projects++;
    else $passive_projects++;
}
?>

<div class="container px-2">
  
  <!-- Üst Başlık Alanı -->
  <div class="mb-4 d-flex align-items-center justify-content-between pt-2 px-1">
    <div>
      <h2 class="mb-0 text-bold" style="letter-spacing: -0.8px; font-size: 1.5rem;">Projeler</h2>
      <p class="text-muted text-xs mb-0">Toplam <?php echo count($projects); ?> proje tanımlı.</p>
    </div>
    <a href="project-manage" class="btn btn-icon btn-primary rounded-circle shadow-sm btn-active-scale" id="btn-add-project">
      <i class="ti ti-plus fs-2"></i>
    </a>
  </div>

  <!-- Kasa Tasarımı Özet Kartları -->
  <div class="row g-1 mb-2">
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0 shadow-sm" style="background: rgba(47, 179, 68, 0.1); color: #2fb344; border-radius: 16px;">
        <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">AKTİF PROJELER</div>
        <div class="text-bold h3 mb-0"><?php echo $active_projects; ?> Adet</div>
      </div>
    </div>
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0 shadow-sm" style="background: rgba(214, 63, 63, 0.1); color: #d63f3f; border-radius: 16px;">
        <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">PASİF PROJELER</div>
        <div class="text-bold h3 mb-0"><?php echo $passive_projects; ?> Adet</div>
      </div>
    </div>
  </div>

  <!-- Arama Çubuğu -->
  <div class="search-container mb-3 px-1">
    <i class="ti ti-search search-icon"></i>
    <input type="text" id="project-search" class="search-input shadow-sm" placeholder="Proje adı veya konum ara...">
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
        $person_count = count($projectsModel->getPersonFromProject($project->id));
        $is_active = $status == 1;
        $id_encrypted = Security::encrypt($project->id);
      ?>
        <a href="project-manage?id=<?php echo $id_encrypted; ?>" 
           class="list-group-item project-item border-0 border-bottom py-3 px-3" data-name="<?php echo strtolower($project->project_name . ' ' . ($project->address ?? '')); ?>">
          <div class="d-flex align-items-center justify-content-between w-100">
            <div class="d-flex align-items-center gap-3">
              <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center border border-white shadow-sm" style="background: <?php echo $is_active ? 'rgba(47, 179, 68, 0.15)' : 'rgba(214, 63, 63, 0.15)'; ?>; color: <?php echo $is_active ? '#2fb344' : '#d63f3f'; ?>; width: 42px; height: 42px;">
                <i class="ti <?php echo $is_active ? 'ti-building' : 'ti-building-off'; ?>" style="font-size: 1.25rem;"></i>
              </div>
              <div>
                <div class="text-bold text-sm text-dark"><?php echo htmlspecialchars($project->project_name); ?></div>
                <div class="text-muted text-xs d-flex align-items-center gap-1 mt-0.5">
                  <i class="ti ti-map-pin" style="font-size: 0.75rem;"></i>
                  <span><?php echo htmlspecialchars(($project->address ?? '') ?: 'Konum Yok'); ?></span>
                </div>
              </div>
            </div>
            <div class="text-end">
              <div class="text-bold text-sm text-primary">
                <?php echo $person_count; ?>
              </div>
              <div class="text-muted text-xs font-weight-bold opacity-75" style="font-size: 0.65rem;">PERSONEL</div>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
$(document).ready(function() {
  $('#project-search').on('keyup', function() {
    var value = $(this).val().toLowerCase();
    $('#project-list .project-item').filter(function() {
      $(this).toggle($(this).data('name').indexOf(value) > -1)
    });
  });
});
</script>

<style>
.text-bold { font-weight: 700 !important; }
.text-semibold { font-weight: 600 !important; }
.bg-primary-lt { background-color: rgba(32, 107, 196, 0.08) !important; }
body[data-bs-theme="dark"] .text-dark { color: #f4f6fa !important; }
</style>
