<?php
// Puantor Mobil - Diğer Menüler ve Ayarlar
$user = $_SESSION['user'] ?? null;
$userId = $user->id ?? 0;
?>

<style>
/* Sortable Premium Styles */
.sortable-ghost {
    opacity: 0.4;
    background-color: rgba(32, 107, 196, 0.05) !important;
    border: 1px dashed var(--tblr-primary) !important;
}
.sortable-chosen {
    background-color: var(--tblr-bg-surface-secondary) !important;
    box-shadow: 0 10px 20px rgba(0,0,0,0.08) !important;
    transform: scale(1.02);
    z-index: 10;
}
.sortable-drag {
    opacity: 0;
}
.drag-handle {
    cursor: grab;
    padding: 6px;
    margin-right: 4px;
    opacity: 0.4;
    transition: opacity 0.2s ease, color 0.2s ease;
    touch-action: none;
}
.drag-handle:hover, .drag-handle:active {
    opacity: 0.9;
    color: var(--tblr-primary);
    cursor: grabbing;
}
.list-group-mobile .list-group-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
</style>

<div class="container px-0">
  <div class="mb-4">
    <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Daha Fazla</h2>
  </div>

  <!-- Kullanıcı Profili Özeti -->
  <div class="mobile-card mb-4 p-3 d-flex align-items-center gap-3">
    <div class="avatar avatar-lg rounded-circle text-uppercase" style="background-color: rgba(32, 107, 196, 0.1); color: var(--tblr-primary); font-size: 1.2rem; font-weight: 700;">
      <?php 
        if ($user) {
          echo mb_substr($user->full_name ?? $user->name ?? $user->email ?? 'U', 0, 2, 'UTF-8');
        } else {
          echo 'U';
        }
      ?>
    </div>
    <div class="flex-1">
      <h3 class="mb-0 text-bold" style="font-size: 1rem;"><?php echo htmlspecialchars($user->full_name ?? $user->name ?? 'İsimsiz Kullanıcı'); ?></h3>
      <p class="text-muted text-xs mb-0 text-truncate"><?php echo htmlspecialchars($user->email ?? ''); ?></p>
    </div>
    <a href="/index.php?p=settings/manage&tab=edit-account" class="btn btn-icon btn-sm btn-outline-secondary border-0">
      <i class="ti ti-pencil"></i>
    </a>
  </div>

  <!-- Ana Modüller -->
  <div class="d-flex align-items-center justify-content-between mb-2 px-2">
    <h4 class="m-0 text-muted text-xs text-uppercase tracking-wide text-semibold">Uygulama Modülleri</h4>
    <span class="text-muted text-xxs d-flex align-items-center gap-1"><i class="ti ti-hand-finger text-primary"></i> Sıralamak için sürükleyin</span>
  </div>
  <div id="module-list" class="list-group list-group-mobile mb-4">
    <a href="?p=payroll" class="list-group-item" data-id="payroll">
      <div class="d-flex align-items-center gap-1">
        <i class="ti ti-grip-vertical text-muted drag-handle"></i>
        <div class="avatar avatar-sm rounded bg-cyan-lt me-2">
          <i class="ti ti-report-money text-cyan"></i>
        </div>
        <span class="text-semibold text-sm">Bordrolar</span>
      </div>
      <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
    </a>

    <a href="?p=projects" class="list-group-item" data-id="projects">
      <div class="d-flex align-items-center gap-1">
        <i class="ti ti-grip-vertical text-muted drag-handle"></i>
        <div class="avatar avatar-sm rounded bg-blue-lt me-2">
          <i class="ti ti-folders text-blue"></i>
        </div>
        <span class="text-semibold text-sm">Projeler</span>
      </div>
      <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
    </a>
    
    <a href="?p=todos" class="list-group-item" data-id="todos">
      <div class="d-flex align-items-center gap-1">
        <i class="ti ti-grip-vertical text-muted drag-handle"></i>
        <div class="avatar avatar-sm rounded bg-orange-lt me-2">
          <i class="ti ti-checklist text-orange"></i>
        </div>
        <span class="text-semibold text-sm">Yapılacaklar</span>
      </div>
      <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
    </a>
  </div>

  <!-- Destek ve Ayarlar -->
  <h4 class="mb-2 ms-2 text-muted text-xs text-uppercase tracking-wide text-semibold">Destek & Sistem</h4>
  <div class="list-group list-group-mobile mb-4">
    <a href="/index.php?p=settings/manage" class="list-group-item">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-sm rounded bg-secondary-lt">
          <i class="ti ti-settings text-secondary"></i>
        </div>
        <span class="text-semibold text-sm">Firma Ayarları</span>
      </div>
      <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
    </a>

    <a href="tickets" class="list-group-item">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-sm rounded bg-indigo-lt">
          <i class="ti ti-headset text-indigo"></i>
        </div>
        <span class="text-semibold text-sm">Teknik Destek</span>
      </div>
      <i class="ti ti-chevron-right text-muted" style="opacity: 0.5;"></i>
    </a>
    
    <a href="logout.php" class="list-group-item">
      <div class="d-flex align-items-center gap-3">
        <div class="avatar avatar-sm rounded bg-red-lt">
          <i class="ti ti-logout text-red"></i>
        </div>
        <span class="text-semibold text-sm text-red">Çıkış Yap</span>
      </div>
    </a>
  </div>

  <div class="text-center pb-4">
    <p class="text-muted text-xs mb-1">Puantor v2.0</p>
    <a href="/index.php?p=home&view=desktop" class="text-primary text-xs text-decoration-none">Masaüstü Görünüme Geç</a>
  </div>
</div>

<!-- SortableJS library and initialization -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
$(document).ready(function() {
    const userId = <?php echo json_encode($userId); ?>;
    const storageKey = 'puantor_module_order_' + userId;
    const list = document.getElementById('module-list');
    
    function applyOrder() {
        if (!list) return;
        const storedOrder = localStorage.getItem(storageKey);
        if (storedOrder) {
            try {
                const orderArray = JSON.parse(storedOrder);
                const items = Array.from(list.children);
                orderArray.forEach(id => {
                    const item = items.find(el => el.getAttribute('data-id') === id);
                    if (item) {
                        list.appendChild(item);
                    }
                });
            } catch (e) {
                console.error("Error parsing stored module order:", e);
            }
        }
    }
    
    if (list) {
        applyOrder();
        
        new Sortable(list, {
            animation: 150,
            handle: '.drag-handle',
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function () {
                const currentOrder = Array.from(list.children).map(el => el.getAttribute('data-id'));
                localStorage.setItem(storageKey, JSON.stringify(currentOrder));
                
                // Show standard premium micro-toast
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-5 start-50 translate-middle-x bg-dark text-white px-3 py-2 rounded shadow-lg text-sm border border-secondary d-flex align-items-center gap-2';
                toast.style.zIndex = '9999';
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                toast.style.transform = 'translate(-50%, 10px)';
                toast.style.fontSize = '0.85rem';
                toast.style.borderRadius = '12px';
                toast.style.backdropFilter = 'blur(8px)';
                toast.innerHTML = '<i class="ti ti-check text-success" style="font-size: 1.1rem;"></i><span>Modül sıralaması kaydedildi</span>';
                
                document.body.appendChild(toast);
                
                // Trigger animation
                setTimeout(() => {
                    toast.style.opacity = '0.95';
                    toast.style.transform = 'translate(-50%, 0)';
                }, 50);
                
                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translate(-50%, 10px)';
                    setTimeout(() => toast.remove(), 250);
                }, 1800);
            }
        });
    }
});
</script>

