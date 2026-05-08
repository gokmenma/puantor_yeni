<?php
require_once ROOT . "/Model/SupportsModel.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Security;
use App\Helper\Date;

$supportsModel = new SupportsModel();
$supports = $supportsModel->getSupportsByUser();

// Dynamic Absolute AJAX URL Calculation
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'];
$script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$ajaxUrl = $protocol . $domainName . ($script_dir == '/' ? '' : $script_dir) . '/api/supports/tickets.php';


// Group stats
$total_tickets = count($supports);
$open_tickets = 0;
$closed_tickets = 0;

foreach ($supports as $support) {
    if (($support->status ?? 0) == 0) {
        $open_tickets++;
    } else {
        $closed_tickets++;
    }
}
?>

<style>
:root {
    --support-card-bg: #ffffff;
    --support-card-border: rgba(0, 0, 0, 0.08);
    --support-text-main: #1d273b;
    --support-text-muted: #64748b;
}

body[data-bs-theme="dark"] {
    --support-card-bg: #1e293b;
    --support-card-border: rgba(255, 255, 255, 0.1);
    --support-text-main: #f4f6fa;
    --support-text-muted: #94a3b8;
}

.ticket-card {
    background: var(--support-card-bg);
    border: 1px solid var(--support-card-border);
    border-radius: 16px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    transition: transform 0.2s, box-shadow 0.2s;
    cursor: pointer;
}

.ticket-card:active {
    transform: scale(0.98);
    background-color: rgba(0, 0, 0, 0.01);
}

body[data-bs-theme="dark"] .ticket-card:active {
    background-color: rgba(255, 255, 255, 0.01);
}

.ticket-status-badge {
    padding: 4px 10px;
    border-radius: 30px;
    font-size: 0.7rem;
    font-weight: 600;
}

.form-floating > .form-control,
.form-floating > textarea {
    color: var(--support-text-main) !important;
    font-weight: 500;
}

body[data-bs-theme="dark"] .form-floating > .form-control,
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

<div class="container px-0">
    <div class="mb-4 d-flex align-items-center gap-2">
        <a href="more" class="btn btn-icon btn-sm btn-outline-secondary border-0 text-muted">
            <i class="ti ti-chevron-left" style="font-size: 1.5rem;"></i>
        </a>
        <div>
            <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Teknik Destek</h2>
            <p class="text-muted text-xs mb-0">Bizimle iletişime geçin ve destek taleplerinizi takip edin.</p>
        </div>
    </div>

    <!-- Özet Kartı (Gradients and Icons matching premium UI-UX) -->
    <div class="mobile-card text-white p-4 mb-4 position-relative overflow-hidden" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%) !important;">
        <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
            <i class="ti ti-headset"></i>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;">DESTEK ÖZETİ</span>
            <i class="ti ti-help-circle" style="font-size: 1.5rem; opacity: 0.8;"></i>
        </div>
        <h3 class="mb-0 text-bold" style="font-size: 2.2rem; letter-spacing: -1px;"><?php echo $total_tickets; ?> Toplam Bildirim</h3>
        <div class="mt-3 d-flex gap-2">
            <span class="badge bg-white-10 text-white text-xs d-flex align-items-center gap-1" style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 4px 10px;">
                <i class="ti ti-activity"></i>
                <?php echo $open_tickets; ?> Aktif Talep
            </span>
        </div>
    </div>

    <!-- Two-Column Stats Row -->
    <div class="row g-2 mb-4">
        <div class="col-6">
            <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5; border-radius: 16px;">
                <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Açık Talepler</div>
                <div class="text-bold h3 mb-0"><?php echo $open_tickets; ?> Talep</div>
            </div>
        </div>
        <div class="col-6">
            <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(34, 197, 94, 0.1); color: #22c55e; border-radius: 16px;">
                <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Kapatılanlar</div>
                <div class="text-bold h3 mb-0"><?php echo $closed_tickets; ?> Talep</div>
            </div>
        </div>
    </div>

    <!-- Destek Talepleri Listesi -->
    <h4 class="mb-3 text-semibold" style="font-size: 0.95rem; margin-left: 4px;">Talep Geçmişi</h4>
    <div class="mb-5" id="tickets-list">
        <?php if (empty($supports)): ?>
            <div class="text-center py-5 bg-white rounded-3 border" style="border-radius: 16px !important; border: 1px solid var(--support-card-border) !important;">
                <i class="ti ti-message-chatbot text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                <p class="text-muted text-sm mb-0">Kayıtlı destek talebiniz bulunmuyor.</p>
                <button class="btn btn-primary btn-sm mt-3 px-4" onclick="openTicketModal()" style="border-radius: 8px; background: #4f46e5; border: none;">Yeni Talep Oluştur</button>
            </div>
        <?php else: ?>
            <?php foreach ($supports as $support): 
                $is_closed = ($support->status ?? 0) == 1;
                $encrypted_id = Security::encrypt($support->id);
            ?>
                <div class="ticket-card d-flex align-items-center justify-content-between" onclick="location.href='ticket-view?id=<?php echo $encrypted_id; ?>'">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: <?php echo $is_closed ? 'rgba(100, 116, 139, 0.12)' : 'rgba(79, 70, 229, 0.12)'; ?>; color: <?php echo $is_closed ? '#64748b' : '#4f46e5'; ?>;">
                            <i class="ti <?php echo $is_closed ? 'ti-lock' : 'ti-message-dots'; ?>" style="font-size: 1.2rem;"></i>
                        </div>
                        <div>
                            <div class="text-bold text-sm" style="color: var(--support-text-main);"><?php echo htmlspecialchars($support->subject); ?></div>
                            <div class="text-muted text-xs d-flex align-items-center gap-2 mt-1">
                                <span><?php echo Date::dmY($support->created_at); ?></span>
                                <span>•</span>
                                <span class="text-truncate" style="max-width: 150px;"><?php echo preg_replace('/\?/', '', strip_tags($support->message)); ?></span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <?php if ($is_closed): ?>
                            <span class="ticket-status-badge text-secondary bg-secondary-lt" style="background: rgba(100, 116, 139, 0.1) !important;">Kapatıldı</span>
                        <?php else: ?>
                            <span class="ticket-status-badge text-primary bg-primary-lt" style="background: rgba(79, 70, 229, 0.1) !important; color: #4f46e5 !important;">Açık</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Floating Action Button (FAB) -->
<a href="#" class="mobile-fab" onclick="openTicketModal()" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%) !important; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4) !important;">
    <i class="ti ti-plus"></i>
</a>

<!-- Modal Structure -->
<div class="modal modal-blur fade" id="ticketModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header py-3" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                <h5 class="modal-title text-semibold" style="font-size: 1.05rem;">Yeni Destek Talebi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="ticketForm" onsubmit="saveTicket(event)">
                <input type="hidden" name="action" value="saveSupportTicket">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">Talep Bilgileri</label>
                        <div class="form-floating">
                            <input type="text" class="form-control text-bold" id="ticketSubject" name="subject" required placeholder="Destek konusu nedir?">
                            <label for="ticketSubject">Konu Başlığı <span class="text-danger">*</span></label>
                        </div>
                    </div>

                    <div class="mb-0">
                        <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">Açıklama</label>
                        <div class="form-floating">
                            <textarea class="form-control" id="ticketMessage" name="message" required placeholder="Problemi detaylıca açıklayınız..." style="height: 150px; resize: none;"></textarea>
                            <label for="ticketMessage">Mesajınız <span class="text-danger">*</span></label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2.5 bg-light d-flex justify-content-between" style="border-top: 1px solid rgba(0,0,0,0.06); border-bottom-left-radius: 20px; border-bottom-right-radius: 20px;">
                    <button type="button" class="btn btn-link text-muted text-xs text-semibold text-decoration-none" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 py-2 text-xs text-semibold" id="submitTicketBtn" style="border-radius: 10px; background: #4f46e5; border: none;">
                        <i class="ti ti-send me-1"></i> Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openTicketModal() {
    $('#ticketForm')[0].reset();
    new bootstrap.Modal($('#ticketModal')).show();
}

function saveTicket(e) {
    e.preventDefault();
    const btn = $('#submitTicketBtn');
    const originalText = btn.html();
    
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status"></span> Gönderiliyor...');
    
    const formData = new FormData(e.target);
    
    $.ajax({
        url: '<?php echo $ajaxUrl; ?>',
        method: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            btn.prop('disabled', false).html(originalText);
            if (response.status === 'success') {
                bootstrap.Modal.getInstance($('#ticketModal')[0]).hide();
                
                // Show standard premium micro-toast
                const toast = document.createElement('div');
                toast.className = 'position-fixed bottom-5 start-50 translate-middle-x bg-dark text-white px-3 py-2 rounded shadow-lg text-sm border border-secondary d-flex align-items-center gap-2';
                toast.style.zIndex = '9999';
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.25s ease, transform 0.25s ease';
                toast.style.transform = 'translate(-50%, 10px)';
                toast.style.borderRadius = '12px';
                toast.style.backdropFilter = 'blur(8px)';
                toast.innerHTML = '<i class="ti ti-check text-success" style="font-size: 1.1rem;"></i><span>Destek talebi oluşturuldu</span>';
                
                document.body.appendChild(toast);
                setTimeout(() => {
                    toast.style.opacity = '0.95';
                    toast.style.transform = 'translate(-50%, 0)';
                }, 50);
                
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                Swal.fire('Hata', response.message || 'Destek talebi oluşturulamadı.', 'error');
            }
        },
        error: function() {
            btn.prop('disabled', false).html(originalText);
            Swal.fire('Hata', 'Bir ağ hatası oluştu.', 'error');
        }
    });
}
</script>
