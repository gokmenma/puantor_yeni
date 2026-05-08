<style>
:root {
    --todo-card-bg: #ffffff;
    --todo-card-border: rgba(0, 0, 0, 0.08);
    --todo-text-main: #1d273b;
    --todo-text-muted: #64748b;
}

body[data-bs-theme="dark"] {
    --todo-card-bg: #1e293b;
    --todo-card-border: rgba(255, 255, 255, 0.1);
    --todo-text-main: #f4f6fa;
    --todo-text-muted: #94a3b8;
}

/* Custom styled tabs like premium mobile feel in finance */
.btn-filter.active {
    color: var(--mobile-primary) !important;
    background: #ffffff !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}
body[data-bs-theme="dark"] .btn-filter.active {
    color: #fff !important;
    background: #1e293b !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.form-selectgroup-input:checked + .form-selectgroup-label {
    border-color: var(--mobile-primary) !important;
    background: rgba(32, 107, 196, 0.04) !important;
}
body[data-bs-theme="dark"] .form-selectgroup-input:checked + .form-selectgroup-label {
    background: rgba(32, 107, 196, 0.15) !important;
}

.todo-item {
    transition: background-color 0.2s;
}
.todo-item:hover {
    background-color: rgba(0,0,0,0.01);
}
body[data-bs-theme="dark"] .todo-item:hover {
    background-color: rgba(255,255,255,0.01);
}

select.form-select {
    border-radius: 10px !important;
    border-color: rgba(0, 0, 0, 0.1) !important;
    padding: 0.5rem 0.75rem !important;
    height: auto !important;
    font-size: 0.85rem !important;
}
body[data-bs-theme="dark"] select.form-select {
    border-color: var(--mobile-card-border-dark) !important;
    background-color: #1e293b !important;
    color: #f4f6fa !important;
}

/* Custom Select2 Styling for Mobile */
.select2-container--default .select2-selection--single {
    border-radius: 10px !important;
    border: 1px solid rgba(0, 0, 0, 0.1) !important;
    height: 44px !important;
    padding: 0.5rem 0.75rem !important;
    background-color: #fff !important;
    display: flex !important;
    align-items: center !important;
}
body[data-bs-theme="dark"] .select2-container--default .select2-selection--single {
    border-color: var(--mobile-card-border-dark) !important;
    background-color: #1e293b !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    color: inherit !important;
    padding-left: 0 !important;
    line-height: normal !important;
    font-size: 0.85rem !important;
}
body[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #f4f6fa !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 44px !important;
    right: 12px !important;
    display: flex !important;
    align-items: center !important;
}
.select2-dropdown {
    border-radius: 12px !important;
    border-color: rgba(0, 0, 0, 0.08) !important;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important;
    background-color: #fff !important;
    overflow: hidden !important;
    z-index: 1060 !important;
}
body[data-bs-theme="dark"] .select2-dropdown {
    background-color: #1e293b !important;
    border-color: var(--mobile-card-border-dark) !important;
}
.select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: var(--mobile-primary) !important;
}
.select2-container {
    width: 100% !important;
}

/* Swipe to Delete Styles matching finance perfectly */
.todo-item-wrapper {
    position: relative;
    overflow: hidden;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    user-select: none;
}
body[data-bs-theme="dark"] .todo-item-wrapper,
body[data-bs-theme="dark"] .todo-item-content {
    background: #1e293b !important;
}
.todo-item-actions {
    position: absolute;
    right: 0;
    top: 0;
    height: 100%;
    display: flex;
    align-items: center;
    background: #d63f3f;
    z-index: 1;
}
.todo-item-content {
    position: relative;
    background: #fff;
    z-index: 2;
    transition: transform 0.2s ease-out;
    width: 100%;
    padding: 1rem;
}
.btn-swipe-delete {
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
}
.btn-swipe-delete i {
    font-size: 1.2rem;
    margin-bottom: 2px;
}
</style>

<?php
require_once ROOT . "/Model/GorevModel.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Security;
use App\Helper\Date;

$gorevModel = new GorevModel();
$firm_id = $_SESSION['firm_id'] ?? 0;

// Get lists of tasks
$listeler = $gorevModel->getListeler($firm_id);

// Automatically initialize a default list folder if none exists
if (empty($listeler)) {
    $gorevModel->addListe([
        'firma_id' => $firm_id,
        'baslik' => 'Genel Görevler',
        'renk' => '#4285f4',
        'olusturan_id' => $_SESSION['user']->id ?? 0
    ]);
    $listeler = $gorevModel->getListeler($firm_id);
}

// Get all tasks across all list folders
$todos = $gorevModel->getTumGorevler($firm_id);

// Group todos
$pending_todos = [];
$completed_todos = [];
foreach ($todos as $todo) {
    if (($todo->tamamlandi ?? 0) == 1) {
        $completed_todos[] = $todo;
    } else {
        $pending_todos[] = $todo;
    }
}
?>

<div class="container px-0">
  <div class="mb-4 d-flex align-items-center justify-content-between">
    <div>
      <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Yapılacaklar</h2>
      <p class="text-muted text-xs mb-0">Görevlerinizi buradan takip edebilirsiniz.</p>
    </div>
  </div>

  <!-- Özet Kartı (Gradient like Kasa Bakiyesi Kartı) -->
  <div class="mobile-card bg-primary text-white p-4 mb-4 position-relative overflow-hidden" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #206bc4 0%, #104b8c 100%) !important;">
    <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
      <i class="ti ti-checkbox"></i>
    </div>
    <div class="d-flex align-items-center justify-content-between mb-2">
      <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;">GÖREVLER DURUM ÖZETİ</span>
      <i class="ti ti-list-check" style="font-size: 1.5rem; opacity: 0.8;"></i>
    </div>
    <h3 class="mb-0 text-bold" style="font-size: 2.2rem; letter-spacing: -1px;"><?php echo count($todos); ?> Toplam Görev</h3>
    <div class="mt-3 d-flex gap-2">
      <span class="badge bg-white-10 text-white text-xs d-flex align-items-center gap-1" style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 4px 10px;">
        <i class="ti ti-confetti"></i>
        <?php echo count($completed_todos); ?> Tamamlanan
      </span>
    </div>
  </div>

  <!-- Two-Column Stats Row (exactly like Income/Expenses style) -->
  <div class="row g-1 mb-2">
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(32, 107, 196, 0.1); color: var(--mobile-primary); border-radius: 16px;">
        <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Bekleyenler</div>
        <div class="text-bold h3 mb-0"><?php echo count($pending_todos); ?> Görev</div>
      </div>
    </div>
    <div class="col-6">
      <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(47, 179, 68, 0.1); color: #2fb344; border-radius: 16px;">
        <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Tamamlananlar</div>
        <div class="text-bold h3 mb-0"><?php echo count($completed_todos); ?> Görev</div>
      </div>
    </div>
  </div>

  <!-- İşlem Filtreleri (like Son İşlemler & Filtreler) -->
  <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
    <h4 class="mb-0 text-semibold" style="font-size: 0.95rem;">Son Görevler</h4>
    <div class="btn-group btn-group-sm" role="group" style="border-radius: 8px; overflow: hidden; background: rgba(0,0,0,0.03);">
      <button type="button" class="btn btn-light btn-filter active" data-filter="all" style="font-size: 0.7rem; padding: 4px 8px;">Tümü</button>
      <button type="button" class="btn btn-light btn-filter" data-filter="pending" style="font-size: 0.7rem; padding: 4px 8px;">Bekleyen</button>
      <button type="button" class="btn btn-light btn-filter" data-filter="completed" style="font-size: 0.7rem; padding: 4px 8px;">Tamamlanan</button>
    </div>
  </div>

  <div class="list-group list-group-mobile mb-4" id="todos-list">
    <?php if (empty($todos)): ?>
      <div class="text-center py-5 bg-white rounded-3 border">
        <i class="ti ti-confetti text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
        <p class="text-muted text-sm mb-0">Görev bulunamadı.</p>
      </div>
    <?php else: ?>
      <?php foreach ($todos as $todo): 
        $is_done = ($todo->tamamlandi ?? 0) == 1;
        $todo_id_encrypted = Security::encrypt($todo->id);
      ?>
        <div class="todo-item-wrapper todo-item todo-row" 
             data-type="<?php echo $is_done ? 'completed' : 'pending'; ?>"
             data-id="<?php echo $todo_id_encrypted; ?>"
             data-title="<?php echo htmlspecialchars($todo->baslik); ?>"
             data-description="<?php echo htmlspecialchars($todo->aciklama ?? ''); ?>"
             data-liste-id="<?php echo Security::encrypt($todo->liste_id); ?>"
             data-tarih="<?php echo $todo->tarih; ?>"
             data-saat="<?php echo substr($todo->saat ?? '', 0, 5); ?>"
             data-status="<?php echo $todo->tamamlandi; ?>">
          <div class="todo-item-actions">
            <button class="btn-swipe-delete" onclick="deleteTodo('<?php echo $todo_id_encrypted; ?>')">
              <i class="ti ti-trash"></i>
              <span>Sil</span>
            </button>
          </div>
          <div class="todo-item-content d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
              <!-- Custom status toggle avatar matches the finance circle style perfectly -->
              <div onclick="event.stopPropagation(); toggleTodoStatusDirect('<?php echo $todo_id_encrypted; ?>', '<?php echo $todo->tamamlandi; ?>')" class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: <?php echo $is_done ? 'rgba(47, 179, 68, 0.15)' : 'rgba(32, 107, 196, 0.15)'; ?>; color: <?php echo $is_done ? '#2fb344' : 'var(--mobile-primary)'; ?>; cursor: pointer;">
                <i class="ti <?php echo $is_done ? 'ti-square-check' : 'ti-square'; ?>" style="font-size: 1.25rem;"></i>
              </div>
              <div onclick="editTodo('<?php echo $todo_id_encrypted; ?>')" style="cursor: pointer;">
                <div class="text-bold text-sm <?php echo $is_done ? 'text-decoration-line-through text-muted' : ''; ?>" style="color: var(--tblr-body-color, #1d273b);"><?php echo htmlspecialchars($todo->baslik); ?></div>
                <div class="text-muted text-xs d-flex align-items-center gap-1 mt-0.5">
                  <?php if (!empty($todo->liste_adi)): ?>
                    <span><?php echo htmlspecialchars($todo->liste_adi); ?></span>
                    <span class="text-muted-50">•</span>
                  <?php endif; ?>
                  <span class="<?php echo !$is_done && !empty($todo->tarih) && strtotime($todo->tarih) < time() ? 'text-danger text-bold' : ''; ?>">
                    <?php echo !empty($todo->tarih) && $todo->tarih !== '0000-00-00' ? date('d.m.Y', strtotime($todo->tarih)) . ($todo->saat ? ' ' . substr($todo->saat, 0, 5) : '') : 'Süresiz'; ?>
                  </span>
                </div>
              </div>
            </div>
            <div onclick="editTodo('<?php echo $todo_id_encrypted; ?>')" class="text-muted" style="cursor: pointer;">
              <i class="ti ti-chevron-right opacity-30"></i>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Floating Action Button (FAB) matches finance page FAB perfectly -->
<a href="#" class="mobile-fab" onclick="openTodoModal()">
  <i class="ti ti-plus"></i>
</a>

<!-- Modal Structure matches finance perfectly -->
<div class="modal modal-blur fade" id="todoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header py-3" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                <h5 class="modal-title text-semibold" id="todoModalTitle" style="font-size: 1.05rem;">Yeni Görev Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="todoForm" onsubmit="saveTodo(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="todoId" name="id">
                    
                    <div class="mb-3">
                        <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">Görev Bilgileri</label>
                        <div class="form-floating">
                            <input type="text" class="form-control text-bold" id="todoTitle" name="baslik" required placeholder="Neler yapılacak?">
                            <label for="todoTitle">Görev Başlığı <span class="text-danger">*</span></label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">Görev Listesi <span class="text-danger">*</span></label>
                        <div class="form-floating">
                            <select class="form-select select2-init" id="todoListeId" name="liste_id" required>
                                <?php foreach ($listeler as $liste): ?>
                                    <option value="<?php echo Security::encrypt($liste->id); ?>"><?php echo htmlspecialchars($liste->baslik); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="todoListeId">İlgili Liste Klasörü</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">Zamanlama</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="text" class="form-control" id="todoTarih" name="tarih" placeholder="Tarih Seçin">
                                    <label for="todoTarih">Bitiş Tarihi</label>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-floating">
                                    <input type="time" class="form-control" id="todoSaat" name="saat" placeholder="Saat Seçin">
                                    <label for="todoSaat">Saat</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">Detaylar</label>
                        <div class="form-floating">
                            <textarea class="form-control" id="todoDescription" name="aciklama" placeholder="Detay ekleyebilirsiniz..." style="height: 100px; resize: none;"></textarea>
                            <label for="todoDescription">Açıklama (Opsiyonel)</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2.5 bg-light d-flex justify-content-between" style="border-top: 1px solid rgba(0,0,0,0.06); border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                    <button type="button" class="btn btn-link text-muted text-xs text-semibold text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 text-xs text-semibold" style="border-radius: 10px; background: var(--mobile-primary); border: none;">
                        <i class="ti ti-plus me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-floating > .form-control,
    .form-floating > .form-select,
    .form-floating > textarea {
        color: var(--todo-text-main) !important;
        font-family: 'DM Sans', sans-serif !important;
    }
    
    .form-floating > label {
        color: var(--todo-text-muted) !important;
        font-size: 0.85rem;
    }
    .form-floating > .form-control,
    .form-floating > .form-select,
    .form-floating > textarea {
        color: #1d273b !important; /* Explicit dark color for visibility */
        font-weight: 500;
    }
    
    body[data-bs-theme="dark"] .form-floating > .form-control,
    body[data-bs-theme="dark"] .form-floating > .form-select,
    body[data-bs-theme="dark"] .form-floating > textarea {
        color: #f4f6fa !important;
    }
    
    .form-floating > label {
        color: #64748b !important;
    }

    .form-floating > .form-control:focus {
        background-color: #fff !important;
        color: #000 !important;
    }
    
    body[data-bs-theme="dark"] .form-floating > .form-control:focus {
        background-color: #1e293b !important;
        color: #fff !important;
    }
</style>

<script>
$(document).ready(function() {
    // Initialize Select2 with dropdownParent to fix modal focus issues
    if (jQuery.fn && jQuery.fn.select2) {
        jQuery('.select2-init, select.select2').select2({
            dropdownParent: jQuery('#todoModal')
        });
    }

    if (typeof flatpickr !== 'undefined') {
        flatpickr("#todoTarih", {
            dateFormat: "Y-m-d",
            locale: "tr",
            disableMobile: "true"
        });
    }

    // Filter Tasks instantly matches finance perfectly
    $('.btn-filter').click(function() {
        $('.btn-filter').removeClass('active');
        $(this).addClass('active');
        var filter = $(this).data('filter');
        
        if (filter === 'all') {
            $('.todo-item').fadeIn(200);
        } else {
            $('.todo-item').hide();
            $('.todo-item[data-type="' + filter + '"]').fadeIn(200);
        }
    });

    // Swipe to delete functionality matches finance perfectly
    let touchStartX = 0;
    let touchMoveX = 0;
    let currentSwipeItem = null;
    const swipeThreshold = 70;

    $(document).on('touchstart', '.todo-item-content', function(e) {
        touchStartX = e.originalEvent.touches[0].clientX;
        touchMoveX = touchStartX;
        currentSwipeItem = $(this);
        
        // Reset other open items
        $('.todo-item-content').not(currentSwipeItem).css('transform', 'translateX(0)');
    });

    $(document).on('touchmove', '.todo-item-content', function(e) {
        touchMoveX = e.originalEvent.touches[0].clientX;
        let diff = touchStartX - touchMoveX;
        
        // Only swipe left
        if (diff > 0) {
            if (diff > swipeThreshold + 20) diff = swipeThreshold + 20; // Limit over-swipe
            $(this).css('transition', 'none');
            $(this).css('transform', 'translateX(-' + diff + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    $(document).on('touchend', '.todo-item-content', function(e) {
        let diff = touchStartX - touchMoveX;
        $(this).css('transition', 'transform 0.2s ease-out');
        
        if (diff > swipeThreshold / 2) {
            $(this).css('transform', 'translateX(-' + swipeThreshold + 'px)');
        } else {
            $(this).css('transform', 'translateX(0)');
        }
    });

    // Close swipe on click elsewhere
    $(document).on('touchstart', function(e) {
        if (!$(e.target).closest('.todo-item-wrapper').length) {
            $('.todo-item-content').css('transform', 'translateX(0)');
        }
    });
});

function openTodoModal() {
    $('#todoForm')[0].reset();
    $('#todoId').val('');
    $('#todoModalTitle').text('Yeni Görev Ekle');
    
    // Reset Select2
    if (jQuery.fn.select2) {
        $('#todoListeId').val($('#todoListeId option:first').val()).trigger('change');
    }
    
    new bootstrap.Modal($('#todoModal')).show();
}

function getGorevApiUrl() {
    const pathname = window.location.pathname;
    const mobileIndex = pathname.indexOf('/mobile');
    const basePath = mobileIndex !== -1 ? pathname.substring(0, mobileIndex) : '';
    return basePath + '/pages/gorevler/api.php';
}

function saveTodo(e) {
    e.preventDefault();
    const todoId = $('#todoId').val();
    const action = todoId ? 'update-gorev' : 'add-gorev';
    
    const formData = new FormData(e.target);
    formData.append('action', action);
    if (todoId) {
        formData.append('gorev_id', todoId);
    }
    
    $.ajax({
        url: getGorevApiUrl(),
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                bootstrap.Modal.getInstance($('#todoModal')[0]).hide();
                location.reload();
            } else {
                Swal.fire('Hata', response.message || 'Bir hata oluştu.', 'error');
            }
        }
    });
}

function editTodo(id) {
    const row = $(`.todo-row[data-id="${id}"]`);
    if (!row.length) return;

    $('#todoId').val(id);
    $('#todoTitle').val(row.attr('data-title'));
    $('#todoDescription').val(row.attr('data-description'));
    
    const listeId = row.attr('data-liste-id');
    $('#todoListeId').val(listeId);
    if (jQuery.fn.select2) {
        $('#todoListeId').trigger('change');
    }
    
    const tarih = row.attr('data-tarih');
    const saat = row.attr('data-saat');
    if (tarih && tarih !== '0000-00-00') {
        $('#todoTarih').val(tarih);
        $('#todoSaat').val(saat ? saat.substring(0, 5) : '');
    } else {
        $('#todoTarih').val('');
        $('#todoSaat').val('');
    }

    $('#todoModalTitle').text('Görevi Düzenle');
    new bootstrap.Modal($('#todoModal')).show();
}

function toggleTodoStatusDirect(id, currentStatus) {
    const action = (currentStatus == '1' || currentStatus == 1) ? 'geri-al' : 'tamamla';
    $.ajax({
        url: getGorevApiUrl(),
        method: 'POST',
        data: { action: action, gorev_id: id },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                location.reload();
            } else {
                Swal.fire('Hata', response.message || 'İşlem gerçekleştirilemedi.', 'error');
            }
        }
    });
}

function deleteTodo(id) {
    Swal.fire({
        title: 'Silmek istediğinize emin misiniz?',
        text: "Bu işlem geri alınamaz!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d63f3f',
        confirmButtonText: 'Evet, Sil',
        cancelButtonText: 'İptal'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: getGorevApiUrl(),
                method: 'POST',
                data: { action: 'delete-gorev', gorev_id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        Swal.fire('Hata', response.message || 'İşlem gerçekleştirilemedi.', 'error');
                    }
                }
            });
        }
    });
}
</script>
