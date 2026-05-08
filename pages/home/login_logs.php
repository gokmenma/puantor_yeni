<?php
require_once ROOT . "/Model/LoginLogsModel.php";

$loginLogsObj = new LoginLogsModel();
$firm_id = $_SESSION['firm_id'];

// Firmaya ait kullanıcıların son loginlerini getir
// login_logs tablosunda firm_id yok, bu yüzden users ile join yapıyoruz
$sql = "SELECT l.*, u.full_name 
        FROM login_logs l 
        JOIN users u ON l.user_id = u.id 
        WHERE u.firm_id = :firm_id 
        ORDER BY l.login_time DESC 
        LIMIT 10";

$stmt = (new Model())->getDb()->prepare($sql);
$stmt->execute(['firm_id' => $firm_id]);
$logs = $stmt->fetchAll(PDO::FETCH_OBJ);

if (!$Auths->Authorize("home_page_login_logs_view")) {
    // Eğer yetki kısıtlaması yoksa veya adminse gösterilebilir. 
    // Proje bazlı yetki kontrolü burada yapılabilir.
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
                <?php if (count($logs) > 0): ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="list-group-item">
                            <div class="row align-items-center">
                                <div class="col-auto">
                                    <span class="avatar avatar-xs rounded"><?php echo substr($log->full_name, 0, 1); ?></span>
                                </div>
                                <div class="col text-truncate">
                                    <div class="text-reset d-block"><?php echo htmlspecialchars($log->full_name); ?></div>
                                    <div class="d-flex align-items-center mt-1" style="gap: 8px;">
                                        <small class="text-secondary" style="font-size: 11px;">
                                            <i class="ti ti-calendar-event me-1"></i>
                                            <?php echo date('d.m.Y H:i', strtotime($log->login_time)); ?>
                                        </small>
                                        <small class="text-secondary" style="font-size: 11px;" title="<?php echo htmlspecialchars($log->user_agent); ?>">
                                            <i class="ti ti-device-laptop me-1"></i>
                                            <?php 
                                                if (strpos($log->user_agent, 'Mobile') !== false) echo 'Mobil';
                                                else if (strpos($log->user_agent, 'Windows') !== false) echo 'Windows';
                                                else if (strpos($log->user_agent, 'Macintosh') !== false) echo 'Mac';
                                                else echo 'Bilinmiyor';
                                            ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <small class="text-muted" title="IP Adresi"><?php echo $log->ip_address; ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5 text-secondary">
                        <i class="ti ti-history mb-2" style="font-size: 32px; opacity: 0.5;"></i>
                        <div style="font-size: 13px;">Kayıt bulunamadı.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
