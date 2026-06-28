<?php
require_once ROOT . "/Model/GorevModel.php";
require_once ROOT . "/App/Helper/helper.php";

use App\Helper\Helper;
use App\Helper\Security;

$gorevObj = new GorevModel();
$yaklasanGorevler = $gorevObj->getYaklasanGorevler($firm_id, 10);

if (!$Auths->Authorize("home_page_mission_view")) {
    return;
}

$bugun = date('Y-m-d');
?>

<div class="col-md-6" data-id="widget-gorevler">
    <div class="card" style="max-height: 450px; display: flex; flex-direction: column;">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">YAKLAŞAN GÖREVLER</span>
            <div class="ms-auto d-flex align-items-center">
                <a href="index.php?p=gorevler/list" class="btn btn-sm btn-link me-2" style="font-size:10px; padding:0;">Tümünü Gör</a>
                <i class="ti ti-grid-dots drag-handle text-muted"></i>
            </div>
        </div>
        <div class="card-body p-0" style="overflow-y: auto;">
            <div class="list-group list-group-flush">
                <?php if (count($yaklasanGorevler) > 0): ?>
                    <?php foreach ($yaklasanGorevler as $gorev):
                        $gecmis = $gorev->tarih && $gorev->tarih < $bugun;
                        $bugunMu = $gorev->tarih && $gorev->tarih === $bugun;
                        $avatarClass = $gecmis ? 'bg-red-lt' : ($bugunMu ? 'bg-orange-lt' : 'bg-blue-lt');
                        $listeRenk = $gorev->liste_renk ?? '#6c757d';
                    ?>
                        <a href="javascript:void(0)" class="list-group-item list-group-item-action text-decoration-none view-home-task-detail" data-id="<?php echo Security::encrypt($gorev->id); ?>">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar avatar-sm <?php echo $avatarClass; ?> rounded-circle">
                                        <?php if ($gorev->yildizli): ?>
                                            <i class="ti ti-star-filled"></i>
                                        <?php else: ?>
                                            <i class="ti ti-checkbox"></i>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="col text-truncate">
                                    <div class="text-reset d-block text-truncate" style="font-size: 13px; font-weight: 500;" title="<?php echo htmlspecialchars($gorev->baslik); ?>">
                                        <?php echo htmlspecialchars($gorev->baslik); ?>
                                    </div>
                                    <div class="d-flex align-items-center mt-1" style="gap: 8px;">
                                        <span class="badge" style="background-color: <?php echo htmlspecialchars($listeRenk); ?>; color: #fff; font-size: 10px; padding: 2px 6px;">
                                            <?php echo htmlspecialchars($gorev->liste_adi); ?>
                                        </span>
                                        <?php if ($gorev->tarih): ?>
                                            <small class="<?php echo $gecmis ? 'text-danger' : 'text-secondary'; ?>" style="font-size: 11px;">
                                                <i class="ti ti-calendar-event me-1"></i>
                                                <?php echo date('d.m.Y', strtotime($gorev->tarih)); ?>
                                                <?php if ($gorev->saat): ?>
                                                    <i class="ti ti-clock ms-1 me-1"></i>
                                                    <?php echo substr($gorev->saat, 0, 5); ?>
                                                <?php endif; ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="ti ti-chevron-right text-muted"></i>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-checkbox mb-2" style="font-size: 32px; opacity: 0.5;"></i>
                        <div style="font-size: 13px;">Henüz bekleyen görev yok.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══ DETAY MODAL ═══ -->
<div class="modal modal-blur fade" id="modal-home-task-detail" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-muted" style="font-size: 11px; letter-spacing: 1px; text-transform: uppercase;">
                    <i class="ti ti-checkbox me-1"></i> Görev Detayı
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body pt-2">
                <!-- Task Header (Title & Star) -->
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <h3 class="modal-title fw-bold text-dark mb-0" id="task-detail-title" style="font-size: 18px; line-height: 1.4;">
                        <!-- Title here -->
                    </h3>
                    <span id="task-detail-star" class="ms-2">
                        <!-- Star icon if starred -->
                    </span>
                </div>

                <!-- List/Category and Status -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge py-2 px-3 fw-semibold text-white" id="task-detail-list-badge" style="font-size: 11px; border-radius: 4px;">
                        <!-- List Name -->
                    </span>
                    <span class="badge py-2 px-3 fw-semibold bg-blue-lt" id="task-detail-status-badge" style="font-size: 11px; border-radius: 4px;">
                        Yapılacak
                    </span>
                </div>

                <!-- Description -->
                <div class="mb-4 bg-light p-3 rounded-3" style="border: 1px solid #f1f3f5;" id="task-detail-description-container">
                    <label class="form-label text-muted fw-semibold mb-2" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px;">Açıklama</label>
                    <div class="text-secondary" id="task-detail-description" style="font-size: 13px; white-space: pre-wrap; line-height: 1.6;">
                        <!-- Description -->
                    </div>
                </div>

                <!-- Date, Time and Assigned Users info grid -->
                <div class="row g-3">
                    <div class="col-6" id="task-detail-date-container">
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center" style="border: 1px solid #f1f3f5; min-height: 70px;">
                            <span class="avatar avatar-sm bg-orange-lt rounded-circle me-3">
                                <i class="ti ti-calendar-event" style="font-size: 18px;"></i>
                            </span>
                            <div>
                                <div class="text-muted fw-semibold mb-0" style="font-size: 10px; text-transform: uppercase;">Tarih & Saat</div>
                                <div class="text-dark fw-bold" id="task-detail-date-time" style="font-size: 13px;">
                                    <!-- Date and Time -->
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6" id="task-detail-recurrence-container">
                        <div class="p-3 bg-light rounded-3 d-flex align-items-center" style="border: 1px solid #f1f3f5; min-height: 70px;">
                            <span class="avatar avatar-sm bg-purple-lt rounded-circle me-3">
                                <i class="ti ti-refresh" style="font-size: 18px;"></i>
                            </span>
                            <div>
                                <div class="text-muted fw-semibold mb-0" style="font-size: 10px; text-transform: uppercase;">Yineleme</div>
                                <div class="text-dark fw-bold" id="task-detail-recurrence" style="font-size: 13px;">
                                    <!-- Recurrence Info -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12" id="task-detail-users-container">
                        <div class="p-3 bg-light rounded-3" style="border: 1px solid #f1f3f5;">
                            <div class="text-muted fw-semibold mb-2" style="font-size: 10px; text-transform: uppercase;">Sorumlu Personeller</div>
                            <div class="d-flex flex-wrap gap-2" id="task-detail-users">
                                <!-- User badges -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <input type="hidden" id="task-detail-id">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal" style="font-size: 13px;">Kapat</button>
                <button type="button" class="btn btn-outline-primary me-2" id="btn-home-task-go-to" style="font-size: 13px;">
                    <i class="ti ti-external-link me-1"></i> Görevlere Git
                </button>
                <button type="button" class="btn btn-success fw-bold px-4" id="btn-home-task-complete" style="font-size: 13px;">
                    <i class="ti ti-check me-1"></i> Tamamlandı
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // view-home-task-detail click handler
    $(document).on('click', '.view-home-task-detail', function(e) {
        e.preventDefault();
        const gorevId = $(this).data('id');
        
        // Fetch task details via AJAX
        $.ajax({
            url: 'pages/gorevler/api.php',
            type: 'POST',
            data: {
                action: 'get-gorev-detail',
                gorev_id: gorevId
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const task = response.data;
                    
                    // Populate modal fields
                    $('#task-detail-id').val(task.id);
                    $('#task-detail-title').text(task.baslik);
                    
                    // Star status
                    if (parseInt(task.yildizli) === 1) {
                        $('#task-detail-star').html('<i class="ti ti-star-filled text-warning" style="font-size: 20px;"></i>');
                    } else {
                        $('#task-detail-star').empty();
                    }
                    
                    // List badge
                    $('#task-detail-list-badge')
                        .text(task.liste_adi)
                        .css('background-color', task.liste_renk || '#6c757d');
                        
                    // Status badge
                    if (parseInt(task.tamamlandi) === 1) {
                        $('#task-detail-status-badge')
                            .text('Tamamlandı')
                            .removeClass('bg-blue-lt')
                            .addClass('bg-success-lt');
                        $('#btn-home-task-complete').hide();
                    } else {
                        $('#task-detail-status-badge')
                            .text('Bekliyor')
                            .removeClass('bg-success-lt')
                            .addClass('bg-blue-lt');
                        $('#btn-home-task-complete').show();
                    }
                    
                    // Description
                    if (task.aciklama && task.aciklama.trim() !== '') {
                        $('#task-detail-description').text(task.aciklama);
                        $('#task-detail-description-container').show();
                    } else {
                        $('#task-detail-description-container').hide();
                    }
                    
                    // Date & Time
                    if (task.tarih_formatli) {
                        let dateTimeStr = task.tarih_formatli;
                        if (task.saat_formatli) {
                            dateTimeStr += ' ' + task.saat_formatli;
                        }
                        $('#task-detail-date-time').text(dateTimeStr);
                        $('#task-detail-date-container').show();
                    } else {
                        $('#task-detail-date-container').hide();
                    }
                    
                    // Recurrence
                    if (task.yineleme_sikligi) {
                        let birimMap = {
                            'gun': 'Gün',
                            'hafta': 'Hafta',
                            'ay': 'Ay',
                            'yil': 'Yıl'
                        };
                        let birim = birimMap[task.yineleme_birimi] || task.yineleme_birimi;
                        let recStr = `Her ${task.yineleme_sikligi} ${birim}`;
                        $('#task-detail-recurrence').text(recStr);
                        $('#task-detail-recurrence-container').show();
                    } else {
                        $('#task-detail-recurrence-container').hide();
                    }
                    
                    // Assigned users
                    const usersDiv = $('#task-detail-users');
                    usersDiv.empty();
                    if (task.kullanicilar && task.kullanicilar.length > 0) {
                        task.kullanicilar.forEach(function(userName) {
                            usersDiv.append(`<span class="badge bg-secondary-lt py-1 px-2 fw-medium text-dark" style="font-size: 11px; border-radius: 4px;">${userName}</span>`);
                        });
                        $('#task-detail-users-container').show();
                    } else {
                        // If no assigned users, show generic badge
                        usersDiv.append(`<span class="badge bg-secondary-lt py-1 px-2 fw-medium text-muted" style="font-size: 11px; border-radius: 4px; font-style: italic;">Genel (Herkes)</span>`);
                        $('#task-detail-users-container').show();
                    }
                    
                    // Show modal
                    $('#modal-home-task-detail').modal('show');
                } else {
                    Swal.fire({
                        title: 'Hata',
                        text: response.message || 'Görev detayları alınamadı.',
                        icon: 'error',
                        confirmButtonText: 'Tamam'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Hata',
                    text: 'İletişim sırasında bir hata oluştu.',
                    icon: 'error',
                    confirmButtonText: 'Tamam'
                });
            }
        });
    });
    
    // Complete task click handler
    $('#btn-home-task-complete').click(function() {
        const gorevId = $('#task-detail-id').val();
        if (!gorevId) return;
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu görevi tamamlandı olarak işaretlemek istiyor musunuz?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2fb344',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Tamamla',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'pages/gorevler/api.php',
                    type: 'POST',
                    data: {
                        action: 'tamamla',
                        gorev_id: gorevId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Başarılı',
                                text: response.message || 'Görev tamamlandı.',
                                icon: 'success',
                                confirmButtonText: 'Tamam'
                            }).then(() => {
                                $('#modal-home-task-detail').modal('hide');
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Hata',
                                text: response.message || 'İşlem gerçekleştirilemedi.',
                                icon: 'error',
                                confirmButtonText: 'Tamam'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Hata',
                            text: 'Sistem hatası oluştu.',
                            icon: 'error',
                            confirmButtonText: 'Tamam'
                        });
                    }
                });
            }
        });
    });

    // Go to tasks page click handler
    $('#btn-home-task-go-to').click(function() {
        window.location.href = 'index.php?p=gorevler/list';
    });
});
</script>
