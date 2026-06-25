<?php
$smtp_host = $Settings->getSystemSetting("smtp_host") ?? '';
$smtp_port = $Settings->getSystemSetting("smtp_port") ?? '';
$smtp_encryption = $Settings->getSystemSetting("smtp_encryption") ?? 'ssl';
$smtp_from_name = $Settings->getSystemSetting("smtp_from_name") ?? '';

// Account 1: Şifre Sıfırlama (sifre@...)
$smtp_username = $Settings->getSystemSetting("smtp_username") ?? '';
$smtp_password = $Settings->getSystemSetting("smtp_password") ?? '';
$masked_password = !empty($smtp_password) ? '********' : '';

// Account 2: Bilgilendirme (bilgi@...)
$smtp_info_username = $Settings->getSystemSetting("smtp_info_username") ?? '';
$smtp_info_password = $Settings->getSystemSetting("smtp_info_password") ?? '';
$masked_info_password = !empty($smtp_info_password) ? '********' : '';

// Account 3: Destek (destek@...)
$smtp_support_username = $Settings->getSystemSetting("smtp_support_username") ?? '';
$smtp_support_password = $Settings->getSystemSetting("smtp_support_password") ?? '';
$masked_support_password = !empty($smtp_support_password) ? '********' : '';
?>
<div class="card mb-3" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-4">
            <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle" style="width: 45px; height: 45px;">
                <i class="ti ti-mail" style="font-size: 1.5rem;"></i>
            </span>
            <div>
                <h3 class="card-title mb-1 fw-bold text-dark" style="font-size: 1.15rem;">SMTP Sunucu Bağlantısı</h3>
                <p class="text-secondary small mb-0">E-posta bildirimlerinin gönderileceği posta sunucusu parametrelerini yapılandırın.</p>
            </div>
        </div>
        
        <form id="systemSmtpForm">
            <!-- Server Configs -->
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">SMTP Sunucu Adresi</label>
                    <input type="text" class="form-control py-2" name="smtp_host" value="<?php echo htmlspecialchars($smtp_host); ?>" placeholder="mail.site.com" required style="border-radius: 8px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">SMTP Port Numarası</label>
                    <input type="text" class="form-control py-2" name="smtp_port" value="<?php echo htmlspecialchars($smtp_port); ?>" placeholder="465 veya 587" required style="border-radius: 8px;">
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Şifreleme Türü</label>
                    <select class="form-select" name="smtp_encryption" style="border-radius: 8px;">
                        <option value="ssl" <?php echo $smtp_encryption === 'ssl' ? 'selected' : ''; ?>>SSL/TLS (Port 465)</option>
                        <option value="tls" <?php echo $smtp_encryption === 'tls' ? 'selected' : ''; ?>>STARTTLS (Port 587)</option>
                        <option value="none" <?php echo $smtp_encryption === 'none' ? 'selected' : ''; ?>>Yok</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Gönderen Adı (From Name)</label>
                    <input type="text" class="form-control py-2" name="smtp_from_name" value="<?php echo htmlspecialchars($smtp_from_name); ?>" placeholder="İşçi Maaş Programı" required style="border-radius: 8px;">
                </div>
            </div>

            <!-- Header for Accounts -->
            <div class="hr-text text-start text-dark fw-bold mb-4" style="font-size: 0.95rem;">E-Posta Hesapları (3 Adet)</div>

            <!-- Account 1: Şifre Sıfırlama -->
            <div class="row mb-3 bg-light p-3 border" style="border-radius: 8px; margin-left: 0; margin-right: 0;">
                <div class="col-12 mb-2"><strong class="text-dark small">1. Şifre Sıfırlama E-Postası (sifre@...)</strong></div>
                <div class="col-md-6 mb-2 mb-md-0">
                    <label class="form-label text-dark small">Kullanıcı Adı (E-Posta)</label>
                    <input type="text" class="form-control py-2 bg-white" name="smtp_username" value="<?php echo htmlspecialchars($smtp_username); ?>" required style="border-radius: 8px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-dark small">SMTP Şifresi</label>
                    <input type="password" class="form-control py-2 bg-white" name="smtp_password" value="<?php echo htmlspecialchars($masked_password); ?>" placeholder="••••••••" style="border-radius: 8px;">
                </div>
            </div>

            <!-- Account 2: Bilgilendirme -->
            <div class="row mb-3 bg-light p-3 border" style="border-radius: 8px; margin-left: 0; margin-right: 0;">
                <div class="col-12 mb-2"><strong class="text-dark small">2. Kayıt & Bilgilendirme E-Postası (bilgi@...)</strong></div>
                <div class="col-md-6 mb-2 mb-md-0">
                    <label class="form-label text-dark small">Kullanıcı Adı (E-Posta)</label>
                    <input type="text" class="form-control py-2 bg-white" name="smtp_info_username" value="<?php echo htmlspecialchars($smtp_info_username); ?>" required style="border-radius: 8px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-dark small">SMTP Şifresi</label>
                    <input type="password" class="form-control py-2 bg-white" name="smtp_info_password" value="<?php echo htmlspecialchars($masked_info_password); ?>" placeholder="••••••••" style="border-radius: 8px;">
                </div>
            </div>

            <!-- Account 3: Destek -->
            <div class="row bg-light p-3 border" style="border-radius: 8px; margin-left: 0; margin-right: 0;">
                <div class="col-12 mb-2"><strong class="text-dark small">3. Destek E-Postası (destek@...)</strong></div>
                <div class="col-md-6 mb-2 mb-md-0">
                    <label class="form-label text-dark small">Kullanıcı Adı (E-Posta)</label>
                    <input type="text" class="form-control py-2 bg-white" name="smtp_support_username" value="<?php echo htmlspecialchars($smtp_support_username); ?>" required style="border-radius: 8px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-dark small">SMTP Şifresi</label>
                    <input type="password" class="form-control py-2 bg-white" name="smtp_support_password" value="<?php echo htmlspecialchars($masked_support_password); ?>" placeholder="••••••••" style="border-radius: 8px;">
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-4">
            <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle" style="width: 45px; height: 45px;">
                <i class="ti ti-mail-forward" style="font-size: 1.5rem;"></i>
            </span>
            <div>
                <h3 class="card-title mb-1 fw-bold text-dark" style="font-size: 1.15rem;">E-Posta Gönderim Testi (Real-time Test)</h3>
                <p class="text-secondary small mb-0">Girdiğiniz SMTP ayarlarını kaydetmeden önce e-posta gönderim yeteneğini test edebilirsiniz.</p>
            </div>
        </div>
        
        <form id="smtpTestForm">
            <div class="row align-items-end">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Test Edilecek Hesap</label>
                    <select class="form-select" id="smtp_test_account" name="test_account" style="border-radius: 8px;">
                        <option value="default">1. Şifre Sıfırlama (<?php echo htmlspecialchars($smtp_username); ?>)</option>
                        <option value="info">2. Bilgilendirme (<?php echo htmlspecialchars($smtp_info_username); ?>)</option>
                        <option value="support">3. Destek (<?php echo htmlspecialchars($smtp_support_username); ?>)</option>
                    </select>
                </div>
                <div class="col-md-5 mb-3 mb-md-0">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Test E-Posta Adresi</label>
                    <input type="email" class="form-control py-2" id="smtp_test_email" name="test_email" placeholder="test@gmail.com" required style="border-radius: 8px;">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-dark w-100 py-2" id="btn-test-smtp" style="border-radius: 8px; font-weight: 600; background-color: #1d1d20; color: #fff;">
                        <i class="ti ti-plug icon me-2" style="font-size: 1.1rem;"></i>
                        Test Et
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
