<?php
require_once ROOT . "/Model/SupportsModel.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Security;
use App\Helper\Date;

// Yetki kontrolü
if (($_SESSION['user']->superadmin ?? 0) != 1) {
    header("Location: dashboard");
    exit();
}

$supportsModel = new SupportsModel();
$supports = $supportsModel->getAllSupportsForAdmin();

// İstatistikler
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
</style>

<div class="container px-0">
    <div class="mb-4 d-flex align-items-center gap-2">
        <a href="more" class="btn btn-icon btn-sm btn-outline-secondary border-0 text-muted">
            <i class="ti ti-chevron-left" style="font-size: 1.5rem;"></i>
        </a>
        <div>
            <h2 class="mb-0 text-semibold" style="letter-spacing: -0.5px;">Destek Yönetimi</h2>
            <p class="text-muted text-xs mb-0">Müşterilerden gelen tüm teknik destek taleplerini yanıtlayın.</p>
        </div>
    </div>

    <!-- Özet Kartı -->
    <div class="mobile-card text-white p-4 mb-4 position-relative overflow-hidden" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%) !important;">
        <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
            <i class="ti ti-headset"></i>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;">SİSTEM GENELİ</span>
            <i class="ti ti-chart-bar" style="font-size: 1.5rem; opacity: 0.8;"></i>
        </div>
        <h3 class="mb-0 text-bold" style="font-size: 2.2rem; letter-spacing: -1px;"><?php echo $total_tickets; ?> Toplam Talep</h3>
        <div class="mt-3 d-flex gap-2">
            <span class="badge bg-white-10 text-white text-xs d-flex align-items-center gap-1" style="background: rgba(255,255,255,0.15); border-radius: 8px; padding: 4px 10px;">
                <i class="ti ti-activity"></i>
                <?php echo $open_tickets; ?> Açık Bekleyen
            </span>
        </div>
    </div>

    <!-- İki Kolon İstatistikler -->
    <div class="row g-2 mb-4">
        <div class="col-6">
            <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border-radius: 16px;">
                <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Cevap Bekleyen</div>
                <div class="text-bold h3 mb-0"><?php echo $open_tickets; ?> Talep</div>
            </div>
        </div>
        <div class="col-6">
            <div class="mobile-card p-3 mb-0 border-0" style="background: rgba(34, 197, 94, 0.1); color: #22c55e; border-radius: 16px;">
                <div class="text-xs text-uppercase font-weight-bold mb-1" style="font-size: 0.65rem; opacity: 0.8;">Kapatılmış</div>
                <div class="text-bold h3 mb-0"><?php echo $closed_tickets; ?> Talep</div>
            </div>
        </div>
    </div>

    <!-- Destek Talepleri Listesi -->
    <h4 class="mb-3 text-semibold" style="font-size: 0.95rem; margin-left: 4px;">Talep Listesi</h4>
    <div class="mb-5" id="tickets-list">
        <?php if (empty($supports)): ?>
            <div class="text-center py-5 bg-white rounded-3 border" style="border-radius: 16px !important; border: 1px solid var(--support-card-border) !important;">
                <i class="ti ti-message-chatbot text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                <p class="text-muted text-sm mb-0">Henüz destek talebi bulunmuyor.</p>
            </div>
        <?php else: ?>
            <?php foreach ($supports as $support): 
                $is_closed = ($support->status ?? 0) == 1;
                $encrypted_id = Security::encrypt($support->id);
            ?>
                <div class="ticket-card d-flex align-items-center justify-content-between" onclick="location.href='ticket-view?id=<?php echo $encrypted_id; ?>'">
                    <div class="d-flex align-items-center gap-3" style="min-width: 0; flex: 1;">
                        <div class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: <?php echo $is_closed ? 'rgba(100, 116, 139, 0.12)' : 'rgba(239, 68, 68, 0.12)'; ?>; color: <?php echo $is_closed ? '#64748b' : '#ef4444'; ?>; flex-shrink: 0;">
                            <i class="ti <?php echo $is_closed ? 'ti-lock' : 'ti-clock'; ?>" style="font-size: 1.2rem;"></i>
                        </div>
                        <div style="min-width: 0; flex: 1;">
                            <div class="text-bold text-sm text-truncate" style="color: var(--support-text-main);"><?php echo htmlspecialchars($support->user_name ?? 'Bilinmeyen Kullanıcı'); ?></div>
                            <div class="text-semibold text-xs text-muted text-truncate mt-0.5"><?php echo htmlspecialchars($support->subject); ?></div>
                            <div class="text-muted text-xs d-flex align-items-center gap-2 mt-1">
                                <span><?php echo Date::dmY($support->created_at); ?></span>
                                <span>•</span>
                                <span class="text-truncate" style="max-width: 140px;"><?php echo preg_replace('/\?/', '', strip_tags($support->message)); ?></span>
                            </div>
                        </div>
                    </div>
                    <div style="flex-shrink: 0; margin-left: 8px;">
                        <?php if ($is_closed): ?>
                            <span class="ticket-status-badge text-secondary bg-secondary-lt" style="background: rgba(100, 116, 139, 0.1) !important;">Kapalı</span>
                        <?php else: ?>
                            <span class="ticket-status-badge text-danger bg-danger-lt" style="background: rgba(239, 68, 68, 0.1) !important; color: #ef4444 !important;">Açık</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
