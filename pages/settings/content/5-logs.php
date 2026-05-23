<?php
$loginLogs = $User->getLoginLogs($user->id);
?>
<div class="card" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-4">
            <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle" style="width: 45px; height: 45px;">
                <i class="ti ti-history" style="font-size: 1.5rem;"></i>
            </span>
            <div>
                <h3 class="card-title mb-1 fw-bold text-dark" style="font-size: 1.15rem;">Giriş Kayıtları</h3>
                <p class="text-secondary small mb-0">Hesabınıza ait son oturum açma hareketleri.</p>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-vcenter card-table text-nowrap">
                <thead>
                    <tr>
                        <th>Tarih</th>
                        <th>IP Adresi</th>
                        <th>Tarayıcı</th>
                        <th>Durum</th>
                        <th>Açıklama</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($loginLogs)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">Kayıt bulunamadı.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach (array_slice($loginLogs, 0, 10) as $log): 
                            // Parse simple browser from user agent
                            $ua = $log->user_agent;
                            $browser = "Bilinmeyen";
                            if (preg_match('/chrome/i', $ua)) {
                                $browser = 'Chrome';
                                // Extract version if possible
                                if (preg_match('/Chrome\/([0-9\.]+)/i', $ua, $matches)) {
                                    $browser .= ' ' . explode('.', $matches[1])[0] . '.0.0.0';
                                }
                            } elseif (preg_match('/firefox/i', $ua)) {
                                $browser = 'Firefox';
                            } elseif (preg_match('/safari/i', $ua)) {
                                $browser = 'Safari';
                            } elseif (preg_match('/edge/i', $ua)) {
                                $browser = 'Edge';
                            }
                            
                            $roleName = $User->roleName($user->user_roles);
                            $description = "Giriş yapıldı: " . htmlspecialchars($user->full_name) . " (" . htmlspecialchars($roleName) . ")";
                            ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i', strtotime($log->login_time)); ?></td>
                                <td><?php echo htmlspecialchars($log->ip_address); ?></td>
                                <td class="text-secondary"><?php echo htmlspecialchars($browser); ?></td>
                                <td>
                                    <span class="badge bg-success-lt text-success px-2 py-0.5 rounded-pill" style="font-size: 0.75rem;">
                                        ● INFO
                                    </span>
                                </td>
                                <td class="text-secondary"><?php echo $description; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
