<?php
// Puantor Mobil - Proje Listesi
require_once ROOT . "/Model/Projects.php";
$projectsModel = new Projects();
$firm_id = $_SESSION['firm_id'] ?? 0;
$projects = $projectsModel->getProjectsByFirm($firm_id);
?>

<div class="container px-0">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Projeler</h2>
    <button class="btn btn-icon btn-sm btn-outline-primary border-0">
      <i class="ti ti-plus" style="font-size: 1.5rem;"></i>
    </button>
  </div>

  <div class="row g-3">
    <?php foreach ($projects as $project): ?>
      <div class="col-12">
        <div class="mobile-card p-3">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h3 class="mb-0 text-bold" style="font-size: 1.1rem;"><?php echo htmlspecialchars($project->project_name); ?></h3>
            <span class="badge <?php echo ($project->status ?? 1) == 1 ? 'bg-green-lt' : 'bg-red-lt'; ?>">
              <?php echo ($project->status ?? 1) == 1 ? 'Aktif' : 'Pasif'; ?>
            </span>
          </div>
          <p class="text-muted text-sm mb-3">
            <i class="ti ti-map-pin me-1"></i> <?php echo htmlspecialchars($project->location ?? 'Konum Belirtilmemiş'); ?>
          </p>
          <div class="d-flex align-items-center justify-content-between pt-2 border-top">
            <div class="d-flex -space-x-2">
              <span class="avatar avatar-xs rounded-circle bg-blue-lt">A</span>
              <span class="avatar avatar-xs rounded-circle bg-green-lt">B</span>
              <span class="avatar avatar-xs rounded-circle bg-orange-lt">+5</span>
            </div>
            <a href="#" class="text-primary text-xs text-semibold text-decoration-none">Detaylar <i class="ti ti-chevron-right ms-1"></i></a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
