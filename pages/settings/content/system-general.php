<?php
$system_title = $Settings->getSystemSetting("system_title") ?? 'İşçi Maaş';
$system_email = $Settings->getSystemSetting("system_email") ?? 'bilgi@iscimaas.com.tr';
$system_language = $Settings->getSystemSetting("system_language") ?? 'tr';
$maintenance_mode = $Settings->getSystemSetting("maintenance_mode") ?? '0';
$kvkk_consent = $Settings->getSystemSetting("kvkk_consent") ?? '0';
?>
<div class="card mb-3" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-4">
            <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle" style="width: 45px; height: 45px;">
                <i class="ti ti-globe" style="font-size: 1.5rem;"></i>
            </span>
            <div>
                <h3 class="card-title mb-1 fw-bold text-dark" style="font-size: 1.15rem;">Genel Platform Ayarları</h3>
                <p class="text-secondary small mb-0">Platformun genel tanımlarını ve yerelleştirme dil tercihlerini yapılandırın.</p>
            </div>
        </div>
        
        <form id="systemGeneralForm">
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Sistem Başlığı (Site Title)</label>
                    <input type="text" class="form-control py-2" name="system_title" value="<?php echo htmlspecialchars($system_title); ?>" required style="border-radius: 8px;">
                    <div class="form-text mt-1 text-secondary small" style="font-size: 0.8rem;">Platformun tarayıcı sekmesinde ve genel arayüz başlıklarında kullanılacak isim.</div>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-12">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Yönetici Bildirim E-Postası</label>
                    <input type="email" class="form-control py-2" name="system_email" value="<?php echo htmlspecialchars($system_email); ?>" required style="border-radius: 8px;">
                    <div class="form-text mt-1 text-secondary small" style="font-size: 0.8rem;">Sistem kritik hataları ve rapor bildirim uyarılarının gönderileceği adres.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Varsayılan Sistem Dili</label>
                    <div class="input-icon">
                        <select class="form-select" name="system_language" style="border-radius: 8px;">
                            <option value="tr" <?php echo $system_language === 'tr' ? 'selected' : ''; ?>>Türkçe (TR)</option>
                            <option value="en" <?php echo $system_language === 'en' ? 'selected' : ''; ?>>English (EN)</option>
                        </select>
                    </div>
                    <div class="form-text mt-1 text-secondary small" style="font-size: 0.8rem;">Yeni oluşturulan kullanıcılar ve oturum dışı alanlar için varsayılan dil.</div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-4">
            <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle" style="width: 45px; height: 45px;">
                <i class="ti ti-adjustments" style="font-size: 1.5rem;"></i>
            </span>
            <div>
                <h3 class="card-title mb-1 fw-bold text-dark" style="font-size: 1.15rem;">Sistem Anahtarları (Mod Toggles)</h3>
                <p class="text-secondary small mb-0">Platformun operasyonel modlarını ve yasal uyumluluk pencerelerini anlık yönetin.</p>
            </div>
        </div>
        
        <div class="row mb-3">
            <div class="col-12">
                <label class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" name="maintenance_mode" form="systemGeneralForm" <?php echo $maintenance_mode == 1 ? 'checked' : ''; ?>>
                    <span class="form-check-label fw-bold text-dark small">Bakım Modu (Maintenance Mode)</span>
                    <small class="form-hint text-secondary mt-1" style="font-size: 0.8rem;">Aktif edildiğinde, süper adminler haricindeki tüm kullanıcılar "Bakım Yapılıyor" ekranı ile karşılaşır.</small>
                </label>
            </div>
        </div>
        
        <div class="row">
            <div class="col-12">
                <label class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" name="kvkk_consent" form="systemGeneralForm" <?php echo $kvkk_consent == 1 ? 'checked' : ''; ?>>
                    <span class="form-check-label fw-bold text-dark small">KVKK ve Çerez Rıza Bildirimi</span>
                    <small class="form-hint text-secondary mt-1" style="font-size: 0.8rem;">Kullanıcı girişi ve kayıt sayfalarında KVKK aydınlatma metnini ve çerez izin barını gösterir.</small>
                </label>
            </div>
        </div>
    </div>
</div>
