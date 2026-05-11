<?php
// Puantor Mobil - Dashboard Ana Sayfası
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/Projects.php";
require_once ROOT . "/Model/GorevModel.php";
require_once ROOT . "/App/Helper/security.php";
use App\Helper\Security;

$personsModel = new Persons();
$projectsModel = new Projects();
$gorevModel = new GorevModel();

$firm_id = $_SESSION['firm_id'] ?? 0;
$user = $_SESSION['user'] ?? null;

// Canlı Veri Sayımları
$active_persons = count($personsModel->getPersonsByFirm($firm_id));
$active_projects = count($projectsModel->getProjectsByFirm($firm_id));
$todos = $gorevModel->getTumGorevler($firm_id);
$pending_todos_count = 0;
foreach ($todos as $t) {
    if (($t->tamamlandi ?? 0) == 0) {
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
    <div class="mobile-card p-0 overflow-hidden mb-4" style="border-radius: 18px; border: 1px solid rgba(0,0,0,0.05);">
      <div class="list-group list-group-flush" id="dashboard-todos">
        <?php 
        $count = 0;
        $total_todos = 0;
        // First count how many we will show to handle borders correctly
        foreach($todos as $t) if(($t->tamamlandi ?? 0) == 0) $total_todos++;
        
        foreach ($todos as $todo): 
          if ($count >= 3) break;
          if (($todo->tamamlandi ?? 0) == 1) continue;
          $count++;
          $todo_id_enc = Security::encrypt($todo->id);
          $is_last = ($count == min(3, $total_todos));
        ?>
          <div class="swipe-item-wrapper <?php echo !$is_last ? 'border-bottom' : ''; ?>" style="border-bottom-color: rgba(0,0,0,0.03) !important;">
            <div class="swipe-actions-left">
              <button class="btn-swipe-action bg-primary" onclick="showDashboardTodoDetail('<?php echo $todo_id_enc; ?>')">
                <i class="ti ti-info-circle"></i>
                <span>Detay</span>
              </button>
            </div>
            <a href="todos" class="swipe-item-content d-flex align-items-center justify-content-between py-3 text-decoration-none"
                 style="padding-left: 1rem; padding-right: 1rem; color: inherit;"
                 data-id="<?php echo $todo_id_enc; ?>"
                 data-title="<?php echo htmlspecialchars($todo->baslik ?? ''); ?>"
                 data-description="<?php echo htmlspecialchars($todo->aciklama ?? 'Açıklama yok.'); ?>"
                 data-date="<?php echo !empty($todo->tarih) && $todo->tarih != '0000-00-00' ? date('d.m.Y', strtotime($todo->tarih)) : 'Süresiz'; ?>"
                 data-time="<?php echo $todo->saat ? substr($todo->saat, 0, 5) : ''; ?>"
                 data-list="<?php echo htmlspecialchars($todo->liste_adi ?? 'Genel'); ?>">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(32, 107, 196, 0.1); color: var(--mobile-primary); border: 1.5px solid rgba(32, 107, 196, 0.1); flex-shrink: 0;">
                  <i class="ti ti-square" style="font-size: 1.25rem;"></i>
                </div>
                <div>
                  <div class="text-bold text-sm" style="color: #1d273b; line-height: 1.2;"><?php echo htmlspecialchars($todo->baslik ?? 'Görev'); ?></div>
                  <div class="text-muted text-xs d-flex align-items-center gap-1 mt-0.5">
                    <?php if (!empty($todo->liste_adi)): ?>
                      <span><?php echo htmlspecialchars($todo->liste_adi); ?></span>
                      <span class="text-muted-50">•</span>
                    <?php endif; ?>
                    <span class="<?php echo !empty($todo->tarih) && strtotime($todo->tarih) < time() ? 'text-danger text-bold' : ''; ?>">
                      <?php echo !empty($todo->tarih) && $todo->tarih !== '0000-00-00' ? date('d.m.Y', strtotime($todo->tarih)) : 'Süresiz'; ?>
                    </span>
                  </div>
                </div>
              </div>
              <div class="text-muted">
                <i class="ti ti-chevron-right opacity-30"></i>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
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
    .swipe-item-wrapper {
        position: relative;
        overflow: hidden;
        background: transparent;
    }
    body[data-bs-theme="dark"] .swipe-item-wrapper {
        background: transparent;
    }
    .swipe-actions-left {
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        display: flex;
        z-index: 1;
    }
    .btn-swipe-action {
        border: none;
        color: white;
        width: 65px;
        height: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 0.65rem;
        font-weight: 600;
        text-decoration: none;
        transition: opacity 0.2s;
    }
    .btn-swipe-action:active {
        opacity: 0.8;
    }
    .btn-swipe-action i {
        font-size: 1.2rem;
        margin-bottom: 2px;
    }
    .swipe-item-content {
        position: relative;
        z-index: 2;
        background: #fff;
        transition: transform 0.2s ease-out;
    }
    body[data-bs-theme="dark"] .swipe-item-content {
        background: #1d273b;
    }

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

<!-- Görev Detay Modali -->
<div class="modal fade" id="dashboardTodoDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="border-radius: 24px; border: none; overflow: hidden;">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title text-semibold">Görev Detayı</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body pt-3">
        <div class="mb-3">
          <div class="badge bg-primary-lt mb-2" id="detail-list-badge">Liste Adı</div>
          <h3 class="mb-1 text-bold" id="detail-title">Görev Başlığı</h3>
          <div class="d-flex align-items-center gap-2 text-muted text-xs">
            <i class="ti ti-calendar"></i>
            <span id="detail-date">Tarih</span>
            <span id="detail-time-wrapper"><i class="ti ti-clock ms-2"></i> <span id="detail-time">Saat</span></span>
          </div>
        </div>
        <div class="p-3 bg-light rounded-3 mb-3">
          <label class="text-xs text-muted text-uppercase tracking-wider font-weight-bold mb-1 d-block">Açıklama</label>
          <p class="mb-0 text-sm" id="detail-description" style="white-space: pre-wrap;">Görev açıklaması buraya gelecek.</p>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <a href="todos" class="btn btn-primary w-100 py-2.5 text-semibold" style="border-radius: 12px;">Tüm Yapılacaklara Git</a>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
    let touchStartX = 0;
    let touchMoveX = 0;
    const swipeThreshold = 65; // Only 1 button now (65px)

    $(document).on('touchstart', '.swipe-item-content', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
        touchMoveX = touchStartX;
        $('.swipe-item-content').not(this).css('transform', 'translateX(0)');
    });

    $(document).on('touchmove', '.swipe-item-content', function(e) {
        touchMoveX = e.originalEvent.touches[0].clientX;
        let diff = touchMoveX - touchStartX;
        
        // Swiping right reveals left actions
        if (diff > 0) {
            if (diff > swipeThreshold + 20) diff = swipeThreshold + 20;
            $(this).css('transition', 'none');
            $(this).css('transform', 'translateX(' + diff + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    $(document).on('touchend', '.swipe-item-content', function(e) {
        let diff = touchMoveX - touchStartX;
        $(this).css('transition', 'transform 0.2s ease-out');
        
        if (diff > swipeThreshold / 2) {
            $(this).css('transform', 'translateX(' + swipeThreshold + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    $(document).on('click', function(e) {
        if (!$(e.target).closest('.swipe-item-wrapper').length) {
            $('.swipe-item-content').css('transform', 'translateX(0)');
        }
    });
});

function showDashboardTodoDetail(id) {
    const content = $(`.swipe-item-content[data-id="${id}"]`);
    if (!content.length) return;

    $('#detail-title').text(content.attr('data-title'));
    $('#detail-description').text(content.attr('data-description'));
    $('#detail-date').text(content.attr('data-date'));
    $('#detail-list-badge').text(content.attr('data-list'));
    
    const time = content.attr('data-time');
    if (time) {
        $('#detail-time').text(time);
        $('#detail-time-wrapper').show();
    } else {
        $('#detail-time-wrapper').hide();
    }

    // Reset swipe
    content.css('transform', 'translateX(0)');
    
    const modal = new bootstrap.Modal($('#dashboardTodoDetailModal'));
    modal.show();
}
</script>

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

