<?php
require_once ROOT . "/Model/LoginLogsModel.php";

if (!$Auths->Authorize("home_page_login_logs_view")) {
    return;
}

$loginLogsObj = new LoginLogsModel();
$logs = $loginLogsObj->getRecentByFirmId((int)$firm_id, 10);

/**
 * IP adresini maskeler: son oktet gizlenir (örn. 192.168.1.xxx)
 */
function maskIp(string $ip): string {
    $parts = explode('.', $ip);
    if (count($parts) === 4) {
        $parts[3] = 'xxx';
        return implode('.', $parts);
    }
    // IPv6 veya farklı format ise ortasını gizle
    return substr($ip, 0, 6) . '***';
}
?>

<div class="col-md-6" data-id="widget-login-logs">
    <div class="card" style="max-height: 450px; display: flex; flex-direction: column;">
        <div class="mac-titlebar">
            <div class="mac-buttons">
                <div class="mac-btn mac-close"></div>
                <div class="mac-btn mac-min"></div>
                <div class="mac-btn mac-max"></div>
            </div>
            <span class="mac-title">SON GİRİŞ KAYITLARI</span>
            <div class="ms-auto d-flex align-items-center">
                <span class="badge bg-blue-lt me-2" style="font-size: 9px; padding: 3px 6px;">Canlı</span>
                <i class="ti ti-grid-dots drag-handle text-muted"></i>
            </div>
        </div>
        <div class="card-body p-0" style="overflow-y: auto;">
            <div class="list-group list-group-flush">
                <?php if (!empty($logs)): ?>
                    <?php foreach ($logs as $log):
                        $ua = $log->user_agent ?? '';
                        if (stripos($ua, 'Mobile') !== false) {
                            $deviceLabel = 'Mobil';
                            $deviceIcon  = 'device-mobile';
                            $deviceColor = 'azure';
                        } elseif (stripos($ua, 'Windows') !== false) {
                            $deviceLabel = 'Windows';
                            $deviceIcon  = 'device-laptop';
                            $deviceColor = 'secondary';
                        } elseif (stripos($ua, 'Macintosh') !== false || stripos($ua, 'Mac OS') !== false) {
                            $deviceLabel = 'Mac';
                            $deviceIcon  = 'device-laptop';
                            $deviceColor = 'secondary';
                        } elseif (stripos($ua, 'Linux') !== false) {
                            $deviceLabel = 'Linux';
                            $deviceIcon  = 'device-desktop';
                            $deviceColor = 'secondary';
                        } else {
                            $deviceLabel = 'Bilinmiyor';
                            $deviceIcon  = 'device-desktop';
                            $deviceColor = 'secondary';
                        }
                        $initials = mb_strtoupper(mb_substr($log->full_name ?? '?', 0, 1, 'UTF-8'), 'UTF-8');
                    ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar avatar-sm bg-blue-lt text-primary rounded-circle" style="font-size: 13px; font-weight: 700;">
                                        <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </div>
                                <div class="col text-truncate">
                                    <div class="text-reset d-block fw-medium" style="font-size: 13px;">
                                        <?php echo htmlspecialchars($log->full_name ?? 'Bilinmeyen', ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                    <div class="d-flex align-items-center mt-1 flex-wrap" style="gap: 6px;">
                                        <small class="text-secondary" style="font-size: 11px;">
                                            <i class="ti ti-calendar-event me-1"></i>
                                            <?php echo !empty($log->login_time) ? date('d.m.Y H:i', strtotime($log->login_time)) : '-'; ?>
                                        </small>
                                        <span class="badge bg-<?php echo $deviceColor; ?>-lt text-<?php echo $deviceColor; ?>" style="font-size: 9px; padding: 2px 5px;">
                                            <i class="ti ti-<?php echo $deviceIcon; ?> me-1"></i><?php echo $deviceLabel; ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted" title="IP Adresi (gizlenmiş)" style="font-size: 10px; font-family: monospace;">
                                        <?php echo htmlspecialchars(maskIp($log->ip_address ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-history mb-2" style="font-size: 32px; opacity: 0.5;"></i>
                        <div style="font-size: 13px;">Giriş kaydı bulunamadı.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
