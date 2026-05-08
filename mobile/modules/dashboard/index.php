<?php
// Puantor Mobil - Dashboard Ana Sayfası
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/Model/TodoModel.php"; // loads Todo class

$personsModel = new Persons();
$projectsModel = new Projects();
$todoModel = new Todo();

$firm_id = $_SESSION['firm_id'] ?? 0;
$user = $_SESSION['user'] ?? null;

// Canlı Veri Sayımları
$active_persons = count($personsModel->getPersonsByFirm($firm_id));
$active_projects = count($projectsModel->getProjectsByFirm($firm_id));
$todos = $todoModel->getTodosByFirm();
$pending_todos_count = 0;
foreach ($todos as $t) {
    if (($t->state ?? 0) == 0) {
        $pending_todos_count++;
    }
}
$active_firm = null;
foreach ($myFirms as $firm) {
    if ($firm->id == $firm_id) {
        $active_firm = $firm;
        break;
    }
}
$active_firm_name = $active_firm ? $active_firm->firm_name : 'Firma Seçilmedi';
?>

<div class="container px-0">
  <!-- Karşılama Kartı -->
  <div class="mb-3 d-flex align-items-center justify-content-between">
    <div>
      <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Merhaba, <?php echo htmlspecialchars($user->name ?? 'Kullanıcı'); ?>! 👋</h2>
      <p class="text-muted text-xs mb-0">Bugün işler yolunda görünüyor.</p>
    </div>
  </div>

  <!-- Aktif Firma / Çalışma Alanı Kartı -->
  <div class="mobile-card mb-4 bg-primary-lt border-primary-subtle p-3 btn-active-scale" data-bs-toggle="modal" data-bs-target="#firmSelectionModal" style="cursor: pointer;">
    <div class="d-flex align-items-center justify-content-between">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-md rounded-circle bg-primary text-white">
          <i class="ti ti-building" style="font-size: 1.35rem;"></i>
        </div>
        <div>
          <span class="text-muted text-xs d-block text-semibold">Aktif Çalışma Alanı</span>
          <h4 class="mb-0 text-bold text-primary" style="font-size: 0.95rem; line-height: 1.2;"><?php echo htmlspecialchars($active_firm_name); ?></h4>
        </div>
      </div>
      <i class="ti ti-chevron-right text-primary" style="font-size: 1.25rem;"></i>
    </div>
  </div>


  <!-- KPI İstatistik Kartları (Grid) -->
  <div class="row g-1 mb-2">
    <!-- Personel Kartı -->
    <div class="col-6">
      <div class="mobile-card h-100 d-flex flex-column justify-content-between mb-0">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted text-xs text-semibold">Personel</span>
          <span class="badge bg-primary-lt badge-pill">
            <i class="ti ti-users" style="font-size: 1rem;"></i>
          </span>
        </div>
        <div>
          <h3 class="mb-0 text-semibold" style="font-size: 1.5rem;"><?php echo $active_persons; ?></h3>
          <span class="text-muted text-xs">Aktif Çalışan</span>
        </div>
      </div>
    </div>

    <!-- Proje Kartı -->
    <div class="col-6">
      <div class="mobile-card h-100 d-flex flex-column justify-content-between mb-0">
        <div class="d-flex align-items-center justify-content-between mb-2">
          <span class="text-muted text-xs text-semibold">Projeler</span>
          <span class="badge bg-green-lt badge-pill">
            <i class="ti ti-folders" style="font-size: 1rem;"></i>
          </span>
        </div>
        <div>
          <h3 class="mb-0 text-semibold" style="font-size: 1.5rem;"><?php echo $active_projects; ?></h3>
          <span class="text-muted text-xs">Toplam Proje</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Hızlı İşlemler Gridi (Quick Actions) -->
  <h4 class="mb-3 text-semibold" style="font-size: 0.9rem; letter-spacing: -0.2px; opacity: 0.9;">Hızlı İşlemler</h4>
  <div class="quick-actions-grid">
    <a href="persons" class="quick-action-btn">
      <i class="ti ti-user-plus" style="color: #206bc4; font-size: 1.35rem;"></i>
      <span>Personel<br>Listesi</span>
    </a>
    <a href="puantaj-detail" class="quick-action-btn">
      <i class="ti ti-calendar-event" style="color: #2fb344; font-size: 1.35rem;"></i>
      <span>Puantaj<br>Listesi</span>
    </a>
    <a href="todos" class="quick-action-btn">
      <i class="ti ti-checklist" style="color: #f59e0b; font-size: 1.35rem;"></i>
      <span>Yapılacaklar</span>
    </a>
    <a href="https://wa.me/905000000000" target="_blank" class="quick-action-btn">
      <i class="ti ti-brand-whatsapp" style="color: #07d341; font-size: 1.35rem;"></i>
      <span>WhatsApp<br>Destek</span>
    </a>
  </div>

  <!-- Yapılacaklar Listesi (Recent Todos) -->
  <div class="d-flex align-items-center justify-content-between mb-3 mt-2">
    <h4 class="mb-0 text-semibold" style="font-size: 0.9rem; letter-spacing: -0.2px; opacity: 0.9;">Yapılacaklar (<?php echo $pending_todos_count; ?>)</h4>
    <a href="todos" class="text-primary text-xs text-semibold text-decoration-none">Tümünü Gör</a>
  </div>

  <?php if (empty($todos)): ?>
    <div class="mobile-card text-center py-4">
      <i class="ti ti-circle-check text-muted mb-2" style="font-size: 2rem;"></i>
      <p class="text-muted text-xs mb-0">Hiç yapılacak işiniz yok!</p>
    </div>
  <?php else: ?>
    <div class="list-group list-group-mobile mb-4">
      <?php 
      $count = 0;
      foreach ($todos as $todo): 
        if ($count >= 3) break;
        $count++;
        $is_done = ($todo->status ?? 0) == 1;
      ?>
        <a href="todos" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between py-2.5">
          <div class="d-flex align-items-center gap-3">
            <input class="form-check-input m-0" type="checkbox" <?php echo $is_done ? 'checked' : ''; ?> disabled style="width: 18px; height: 18px; border-radius: 6px;">
            <span class="text-sm <?php echo $is_done ? 'text-decoration-line-through text-muted' : 'text-semibold'; ?>">
              <?php echo htmlspecialchars($todo->title ?? 'Görev'); ?>
            </span>
          </div>
          <span class="text-xs text-muted">
            <?php echo isset($todo->created_at) ? date('d.m', strtotime($todo->created_at)) : ''; ?>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Son Aktiviteler (Activity Feed) -->
  <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
    <h4 class="mb-0 text-semibold" style="font-size: 0.95rem; letter-spacing: -0.3px;">Son Aktiviteler</h4>
  </div>

  <?php 
  require_once ROOT . "/Model/ActivityLogModel.php";
  $activityModel = new ActivityLogModel();
  $activities = $activityModel->getRecentActivities(10);
  
  if (empty($activities)): 
  ?>
    <div class="mobile-card text-center py-4">
      <i class="ti ti-history text-muted mb-2" style="font-size: 2rem;"></i>
      <p class="text-muted text-xs mb-0">Henüz bir aktivite bulunmuyor.</p>
    </div>
  <?php else: ?>
    <div class="activity-feed mb-5">
      <?php foreach ($activities as $activity): 
        $icon = 'ti-activity';
        $color = 'primary';
        switch($activity->activity_type) {
            case 'puantaj': $icon = 'ti-calendar-event'; $color = 'green'; break;
            case 'finance': $icon = 'ti-receipt-2'; $color = 'blue'; break;
            case 'personnel': $icon = 'ti-user-plus'; $color = 'orange'; break;
        }
      ?>
        <div class="activity-item d-flex gap-3 mb-3">
          <div class="activity-icon-wrapper">
            <div class="activity-icon bg-<?php echo $color; ?>-lt">
              <i class="ti <?php echo $icon; ?>"></i>
            </div>
            <div class="activity-line"></div>
          </div>
          <div class="activity-content pb-2">
            <div class="d-flex justify-content-between align-items-start mb-0.5">
              <span class="text-xs text-bold text-dark"><?php echo htmlspecialchars($activity->user_name ?? 'Bilinmeyen Kullanıcı'); ?></span>
              <span class="text-xs text-muted" style="font-size: 10px;"><?php echo date('H:i', strtotime($activity->created_at)); ?></span>
            </div>
            <p class="text-xs text-muted mb-1" style="line-height: 1.4;">
              <?php echo htmlspecialchars($activity->description); ?>
            </p>
            <span class="text-xs text-muted" style="font-size: 10px; opacity: 0.8;">
              <?php echo date('d.m.Y', strtotime($activity->created_at)); ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <style>
    .activity-feed {
        position: relative;
        padding-left: 5px;
    }
    .activity-item {
        position: relative;
    }
    .activity-icon-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        flex-shrink: 0;
    }
    .activity-icon {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        z-index: 2;
    }
    .activity-line {
        width: 2px;
        flex-grow: 1;
        background-color: #f1f5f9;
        margin-top: 4px;
        margin-bottom: -15px;
    }
    .activity-item:last-child .activity-line {
        display: none;
    }
    .activity-content {
        flex-grow: 1;
        border-bottom: 1px solid #f8fafc;
    }
    .activity-item:last-child .activity-content {
        border-bottom: none;
    }
  </style>

</div>

<!-- Firma Seçim Modali (Bottom Sheet tarzı) -->
<div class="modal fade" id="firmSelectionModal" tabindex="-1" aria-labelledby="firmSelectionModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
    <div class="modal-content border-0" style="border-radius: 24px; box-shadow: 0 20px 40px rgba(0,0,0,0.15);">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title text-semibold" id="firmSelectionModalLabel" style="font-size: 1.1rem; letter-spacing: -0.3px;">Çalışma Alanı Seçin</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body pt-3">
        <p class="text-muted text-xs mb-3">İşlemlerini yapmak istediğiniz firmayı aşağıdaki listeden seçebilirsiniz.</p>
        
        <div class="list-group list-group-mobile m-0">
          <?php foreach ($myFirms as $firm): ?>
            <?php $is_active = ($firm->id == $firm_id); ?>
            <form action="" method="POST" class="m-0 select-firm-form">
              <input type="hidden" name="action" value="select_firm">
              <input type="hidden" name="firm_id" value="<?php echo $firm->id; ?>">
              <button type="submit" class="list-group-item list-group-item-action d-flex align-items-center justify-content-between text-start border-0 py-3 <?php echo $is_active ? 'bg-primary-lt' : ''; ?>" style="background: none; width: 100%;">
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar avatar-md rounded-circle <?php echo $is_active ? 'bg-primary text-white' : 'bg-secondary-lt text-secondary'; ?>">
                    <i class="ti ti-building" style="font-size: 1.2rem;"></i>
                  </div>
                  <div>
                    <span class="text-sm font-weight-medium <?php echo $is_active ? 'text-primary text-bold' : ''; ?>">
                      <?php echo htmlspecialchars($firm->firm_name); ?>
                    </span>
                    <?php if ($is_active): ?>
                      <span class="badge bg-primary text-white text-xs ms-2 rounded-pill" style="font-size: 0.6rem;">Aktif</span>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if ($is_active): ?>
                  <i class="ti ti-circle-check-filled text-primary" style="font-size: 1.35rem;"></i>
                <?php else: ?>
                  <i class="ti ti-chevron-right text-muted" style="font-size: 1.1rem;"></i>
                <?php endif; ?>
              </button>
            </form>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

