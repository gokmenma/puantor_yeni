<?php
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/Model/PackageModel.php";
require_once ROOT . "/Model/UsersPackagesModel.php";
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Date;
use App\Helper\Security;

$Settings = new SettingsModel();
$User = new UserModel();
$Packages = new PackageModel();
$UsersPackages = new UsersPackageModel();
$Auths = new Auths();

$user = $_SESSION['user'];
$userId = $user->id;
$firmId = $_SESSION['firm_id'];

// Get settings
$work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
$show_white_collar = $Settings->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;
$personnel_advance_request_visible = $Settings->getSettings("personnel_advance_request_visible")->set_value ?? 1;

// Get package info
$user_package = $UsersPackages->getSelectUserPackage($userId);
$current_package = $Packages->getPackage($user_package->package_id ?? 0) ?? null;

// Yetki kontrolü (Sistem ayarları için)
$settings_auth = $Auths->getAuthIdByTitle("Ayarlar");
$can_view_settings = ($settings_auth && $Auths->AuthorizeByAuthId($settings_auth->id));

// Rota bazlı görünüm belirle
$view_mode = ($route == 'profile') ? 'personal' : 'system';

// Eğer sistem görünümündeyse ve yetki yoksa, zorla profile yönlendir
if ($view_mode == 'system' && !$can_view_settings) {
    $view_mode = 'personal';
}
?>

<div class="container px-0">
    <div class="mb-4 px-2">
        <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;"><?php echo ($view_mode == 'personal') ? 'Profil ve Hesap' : 'Sistem Ayarları'; ?></h2>
        <p class="text-muted text-xs"><?php echo ($view_mode == 'personal') ? 'Kişisel bilgilerinizi ve hesap detaylarını yönetin' : 'Sistem ve finansal tercihlerinizi yapılandırın'; ?></p>
    </div>

    <div class="mobile-tabs-container mb-4 mx-2 overflow-auto">
        <ul class="nav nav-pills mobile-nav-pills flex-nowrap" id="settingsTabs" role="tablist" style="min-width: max-content;">
            <?php if ($view_mode == 'system' && $can_view_settings): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab">
                        <i class="ti ti-home me-1"></i> Genel
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="financial-tab" data-bs-toggle="pill" data-bs-target="#tab-financial" type="button" role="tab">
                        <i class="ti ti-receipt me-1"></i> Finansal
                    </button>
                </li>
            <?php else: ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab">
                        <i class="ti ti-user me-1"></i> Profil
                    </button>
                </li>
                <?php if ($user->parent_id == 0): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="account-tab" data-bs-toggle="pill" data-bs-target="#tab-account" type="button" role="tab">
                        <i class="ti ti-settings me-1"></i> Hesap
                    </button>
                </li>
                <?php endif; ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#tab-notifications" type="button" role="tab">
                        <i class="ti ti-bell me-1"></i> Bildirimler
                    </button>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="tab-content" id="settingsTabContent">
        <?php if ($view_mode == 'system' && $can_view_settings): ?>
            <!-- Genel Ayarlar -->
            <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                <div class="mobile-card p-3 mb-3 mx-2">
                    <form id="settingsHomeForm">
                        <div class="mb-3">
                            <label class="form-label text-semibold text-xs text-uppercase tracking-wider">Sistem Tercihleri</label>
                            <p class="text-muted text-xxs mb-3">Çalışma saatleri ve görünürlük tercihleri</p>
                            
                            <div class="form-floating mb-3">
                                <input type="number" class="form-control" name="work_hour" id="work_hour" placeholder="8" value="<?php echo htmlspecialchars($work_hour); ?>">
                                <label for="work_hour">Günlük Çalışma Saati</label>
                            </div>

                            <div class="form-check form-switch mobile-switch p-0">
                                <div class="d-flex align-items-center justify-content-between">
                                    <label class="form-check-label" for="show_white_collar">Beyaz Yaka Personellerini Puantajda Göster</label>
                                    <input class="form-check-input" type="checkbox" name="show_white_collar_in_puantaj" id="show_white_collar" <?php echo $show_white_collar == 1 ? 'checked' : ''; ?>>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-primary w-100 py-2 mt-2" id="home_save">
                            <i class="ti ti-device-floppy me-2"></i> Ayarları Kaydet
                        </button>
                    </form>
                </div>
            </div>

            <!-- Finansal Ayarlar -->
            <div class="tab-pane fade" id="tab-financial" role="tabpanel">
                <?php $sub_limit = $Settings->getSettings("cases_sub_limit")->set_value ?? 0; ?>
                <div class="mobile-card p-3 mb-3 mx-2">
                    <form id="settingsFinancialForm">
                        <label class="form-label text-semibold text-xs text-uppercase tracking-wider">Kasa & Uygulama Ayarları</label>
                        <p class="text-muted text-xxs mb-3">Finansal limitleri ve uygulama görünürlüğünü yönetin</p>

                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" name="sub_limit" id="sub_limit" placeholder="0" value="<?php echo htmlspecialchars($sub_limit); ?>">
                            <label for="sub_limit">Kasa Alt Limiti (₺)</label>
                        </div>

                        <div class="form-check form-switch mobile-switch p-0 mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-check-label" for="personnel_advance_request_visible">
                                    Personel Avans Talepleri Sayfası
                                    <small class="d-block text-muted text-xxs mt-1">Personel uygulamasında avans talebi görünürlüğünü kontrol eder.</small>
                                </label>
                                <input class="form-check-input" type="checkbox" name="personnel_advance_request_visible" id="personnel_advance_request_visible" <?php echo $personnel_advance_request_visible == 1 ? 'checked' : ''; ?>>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary w-100 py-2 mt-2" id="financial_save">
                            <i class="ti ti-device-floppy me-2"></i> Finansal Ayarları Kaydet
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Profil Ayarları -->
            <div class="tab-pane fade show active" id="tab-profile" role="tabpanel">
                <div class="mobile-card p-3 mb-3 mx-2">
                    <form id="userForm">
                        <label class="form-label text-semibold text-xs text-uppercase tracking-wider">Kişisel Bilgiler</label>
                        <p class="text-muted text-xxs mb-3">Profil bilgilerinizi ve şifrenizi güncelleyin</p>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" name="name" id="profile_name" placeholder="Ad Soyad" value="<?php echo htmlspecialchars($user->full_name ?? $user->name ?? ''); ?>">
                            <label for="profile_name">Ad Soyad</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" name="email" id="profile_email" placeholder="E-posta" value="<?php echo htmlspecialchars($user->email ?? ''); ?>" readonly>
                            <label for="profile_email">E-posta (Değiştirilemez)</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" name="password" id="profile_password" placeholder="Yeni Şifre">
                            <label for="profile_password">Yeni Şifre (Boş bırakılırsa değişmez)</label>
                        </div>

                        <button type="button" class="btn btn-primary w-100 py-2 mt-2" id="userSave">
                            <i class="ti ti-user-check me-2"></i> Profili Güncelle
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($user->parent_id == 0): ?>
            <!-- Hesap Ayarları -->
            <div class="tab-pane fade" id="tab-account" role="tabpanel">
                <div class="mobile-card p-3 mb-3 mx-2">
                    <label class="form-label text-semibold text-xs text-uppercase tracking-wider">Paket Bilgileri</label>
                    
                    <div class="bg-primary-lt p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h4 class="mb-0 text-primary fw-bold"><?php echo $current_package->name ?? 'Paket Yok'; ?></h4>
                            <span class="badge bg-primary-lt border border-primary text-primary">Aktif</span>
                        </div>
                        <div class="text-xs text-muted">
                            Bitiş: <?php echo Date::dmY($user_package->end_date ?? ''); ?>
                        </div>
                    </div>

                    <div class="list-group list-group-flush mb-3">
                        <div class="list-group-item px-0 bg-transparent border-0 d-flex justify-content-between text-sm">
                            <span class="text-muted">Personel Limiti:</span>
                            <span class="fw-bold"><?php echo ($current_package->person ?? 0) > 100 ? 'Sınırsız' : ($current_package->person ?? 0); ?></span>
                        </div>
                        <div class="list-group-item px-0 bg-transparent border-0 d-flex justify-content-between text-sm">
                            <span class="text-muted">Proje Limiti:</span>
                            <span class="fw-bold"><?php echo ($current_package->project ?? 0) > 100 ? 'Sınırsız' : ($current_package->project ?? 0); ?></span>
                        </div>
                    </div>

                    <a href="https://puantor.com" target="_blank" class="btn btn-outline-primary w-100 py-2">
                        <i class="ti ti-arrow-up-circle me-2"></i> Paketi Yükselt
                    </a>
                </div>

                <div class="mobile-card p-3 border-danger-lt mx-2" style="border: 1px solid rgba(214, 51, 108, 0.2);">
                    <label class="form-label text-danger text-semibold text-xs text-uppercase tracking-wider">Tehlikeli Bölge</label>
                    <p class="text-muted text-xxs mb-3">Bu işlemler geri alınamaz</p>
                    
                    <button class="btn btn-ghost-danger w-100 text-start d-flex align-items-center justify-content-between px-2">
                        <span class="text-sm">Hesabımı Dondur</span>
                        <i class="ti ti-chevron-right"></i>
                    </button>
                    <hr class="my-2 opacity-50">
                    <button class="btn btn-ghost-danger w-100 text-start d-flex align-items-center justify-content-between px-2">
                        <span class="text-sm">Hesabımı Sil</span>
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Bildirim Ayarları -->
            <div class="tab-pane fade" id="tab-notifications" role="tabpanel">
                <?php 
                $get_value = $Settings->getSettingIdByUserAndAction($userId, "loginde_mail_gonder")->set_value ?? null;
                $send_email_on_login = $get_value ? "checked" : "";
                ?>
                <div class="mobile-card p-3 mb-3 mx-2">
                    <form id="notificationsForm">
                        <label class="form-label text-semibold text-xs text-uppercase tracking-wider">E-posta Bildirimleri</label>
                        <p class="text-muted text-xxs mb-3">Hesap aktiviteleri hakkında bilgilendirme</p>

                        <div class="form-check form-switch mobile-switch p-0">
                            <div class="d-flex align-items-center justify-content-between">
                                <label class="form-check-label" for="send_email_on_login">Giriş Yapıldığında Bildir</label>
                                <input class="form-check-input" type="checkbox" name="send_email_on_login" id="send_email_on_login" <?php echo $send_email_on_login; ?>>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.mobile-tabs-container {
    background: #ebedf2 !important; /* Slightly darker to be more visible */
    padding: 3px !important;
    border-radius: 14px !important;
    margin: 0 12px 24px 12px !important;
    display: flex !important;
    align-items: center !important;
}
.mobile-nav-pills {
    display: flex !important;
    width: 100% !important;
    border: none !important;
}
.mobile-nav-pills .nav-item {
    flex: 1 !important;
}
.mobile-nav-pills .nav-link {
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    padding: 10px 4px !important;
    border-radius: 11px !important;
    color: #6e7687 !important;
    border: none !important;
    background: transparent !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    white-space: nowrap !important;
}
.mobile-nav-pills .nav-link.active {
    background: #ffffff !important;
    color: var(--tblr-primary) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12) !important;
}
.mobile-card {
    background: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #eef2f7 !important;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
    margin-bottom: 20px !important;
}
.form-floating > .form-control {
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
}
</style>

<!-- Using existing settings.js logic by adapting the IDs -->
<script>
$(document).ready(function() {
    // Profil Kaydet
    $(document).on("click", "#userSave", function () {
        var form = $("#userForm");
        var formData = new FormData(form[0]);
        formData.append("action", "userSave");

        fetch("../api/settings/settings.php", {
            method: "POST",
            body: formData
        })
        .then((response) => response.json())
        .then((data) => {
            const title = data.status == "success" ? "Başarılı!" : "Hata!";
            Swal.fire({
                title: title,
                text: data.message,
                icon: data.status,
                confirmButtonText: "Tamam"
            });
        })
        .catch((error) => {
            console.error("Error:", error);
        });
    });

    // Genel Ayarları Kaydet
    $(document).on("click", "#home_save", function () {
        var form = $("#settingsHomeForm");
        let formData = new FormData(form[0]);
        formData.append("action", "homeSettings");

        fetch("../api/settings/settings.php", {
            method: "POST",
            body: formData
        })
        .then((response) => response.json())
        .then((data) => {
            const title = data.status == "success" ? "Başarılı!" : "Hata!";
            Swal.fire({
                title: title,
                text: data.message,
                icon: data.status,
                confirmButtonText: "Tamam"
            });
        });
    });

    // Finansal Ayarları Kaydet
    $(document).on("click", "#financial_save", function () {
        var form = $("#settingsFinancialForm");
        let formData = new FormData(form[0]);
        formData.append("action", "financialSettings");

        fetch("../api/settings/settings.php", {
            method: "POST",
            body: formData
        })
        .then((response) => response.json())
        .then((data) => {
            const title = data.status == "success" ? "Başarılı!" : "Hata!";
            Swal.fire({
                title: title,
                text: data.message,
                icon: data.status,
                confirmButtonText: "Tamam"
            });
        });
    });

    // Bildirim Ayarları (Otomatik Kaydet)
    $(document).on("change", "#send_email_on_login", function () {
        var form = $("#notificationsForm");
        var formData = new FormData(form[0]);
        formData.append("action", "send_email_on_login");

        fetch("../api/settings/settings.php", {
            method: "POST",
            body: formData
        })
        .then((response) => response.json())
        .then((data) => {
            if (data.status !== "success") {
                Swal.fire({
                    title: "Hata!",
                    text: data.message,
                    icon: "error",
                    confirmButtonText: "Tamam"
                });
            }
        });
    });
});
</script>
