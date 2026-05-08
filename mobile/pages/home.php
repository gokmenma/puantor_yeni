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
?>

<div class="container px-0">
  <!-- Karşılama Kartı -->
  <div class="mb-4 d-flex align-items-center justify-content-between">
    <div>
      <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Merhaba, <?php echo htmlspecialchars($user->name ?? 'Kullanıcı'); ?>! 👋</h2>
      <p class="text-muted text-xs mb-0">Bugün işler yolunda görünüyor.</p>
    </div>
  </div>

  <!-- KPI İstatistik Kartları (Grid) -->
  <div class="row g-3 mb-4">
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
          <h3 class="mb-0 text-bold" style="font-size: 1.5rem;"><?php echo $active_persons; ?></h3>
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
          <h3 class="mb-0 text-bold" style="font-size: 1.5rem;"><?php echo $active_projects; ?></h3>
          <span class="text-muted text-xs">Toplam Proje</span>
        </div>
      </div>
    </div>
  </div>

  <!-- Hızlı İşlemler Gridi (Quick Actions) -->
  <h4 class="mb-3 text-semibold" style="font-size: 0.95rem; letter-spacing: -0.3px;">Hızlı İşlemler</h4>
  <div class="quick-actions-grid">
    <a href="?p=persons" class="quick-action-btn">
      <i class="ti ti-user-plus" style="color: #206bc4;"></i>
      <span>Personel<br>Listesi</span>
    </a>
    <a href="?p=puantaj_detail" class="quick-action-btn">
      <i class="ti ti-calendar-event" style="color: #2fb344;"></i>
      <span>Puantaj<br>Listesi</span>
    </a>
    <a href="?p=todos" class="quick-action-btn">
      <i class="ti ti-checklist" style="color: #f59e0b;"></i>
      <span>Yapılacaklar</span>
    </a>
    <a href="https://wa.me/905000000000" target="_blank" class="quick-action-btn">
      <i class="ti ti-brand-whatsapp" style="color: #07d341;"></i>
      <span>WhatsApp<br>Destek</span>
    </a>
  </div>

  <!-- Yapılacaklar Listesi (Recent Todos) -->
  <div class="d-flex align-items-center justify-content-between mb-3 mt-2">
    <h4 class="mb-0 text-semibold" style="font-size: 0.95rem; letter-spacing: -0.3px;">Yapılacaklar (<?php echo $pending_todos_count; ?>)</h4>
    <a href="?p=todos" class="text-primary text-xs text-semibold text-decoration-none">Tümünü Gör</a>
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
        $is_done = ($todo->state ?? 0) == 1;
      ?>
        <div class="list-group-item d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-3">
            <input class="form-check-input m-0" type="checkbox" <?php echo $is_done ? 'checked' : ''; ?> disabled style="width: 18px; height: 18px; border-radius: 6px;">
            <span class="text-sm <?php echo $is_done ? 'text-decoration-line-through text-muted' : 'text-bold'; ?>">
              <?php echo htmlspecialchars($todo->title ?? $todo->content ?? 'Görev'); ?>
            </span>
          </div>
          <span class="text-xs text-muted">
            <?php echo isset($todo->created_at) ? date('d.m', strtotime($todo->created_at)) : ''; ?>
          </span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- Son Aktiviteler -->
  <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
    <h4 class="mb-0 text-semibold" style="font-size: 0.95rem; letter-spacing: -0.3px;">Son Aktiviteler</h4>
    <span class="badge bg-green-lt text-xs">Senkronize</span>
  </div>

  <?php
  require_once ROOT . "/Model/ActivityLogModel.php";
  $activityLogObj = new ActivityLogModel();
  $activities = $activityLogObj->getRecentActivities(5);
  ?>

  <?php if (empty($activities)): ?>
    <div class="mobile-card text-center py-4 mb-4">
      <i class="ti ti-activity text-muted mb-2" style="font-size: 2rem;"></i>
      <p class="text-muted text-xs mb-0">Henüz bir aktivite yok.</p>
    </div>
  <?php else: ?>
    <div class="list-group list-group-mobile mb-4">
      <?php foreach ($activities as $activity): 
        $icon = 'ti-activity';
        $color = '#6c7a91';
        switch($activity->activity_type) {
            case 'personnel': $icon = 'ti-users'; $color = '#206bc4'; break;
            case 'project': $icon = 'ti-buildings'; $color = '#2fb344'; break;
            case 'puantaj': $icon = 'ti-calendar'; $color = '#f59e0b'; break;
            case 'finance': $icon = 'ti-wallet'; $color = '#d63939'; break;
            case 'todo': $icon = 'ti-checklist'; $color = '#7239d6'; break;
        }
      ?>
        <div class="list-group-item d-flex align-items-center gap-3">
          <div class="avatar avatar-sm rounded-circle" style="background: <?php echo $color; ?>15; color: <?php echo $color; ?>; min-width: 32px;">
            <i class="ti <?php echo $icon; ?>" style="font-size: 1rem;"></i>
          </div>
          <div class="flex-fill">
            <div class="text-sm text-semibold mb-0" style="line-height: 1.2;">
              <?php echo htmlspecialchars($activity->description); ?>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-1">
              <small class="text-muted text-xs"><?php echo htmlspecialchars($activity->user_name); ?></small>
              <small class="text-muted text-xs"><?php echo date('H:i', strtotime($activity->created_at)); ?></small>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>
