<?php
require_once ROOT . "/Model/SettingsModel.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/Model/PackageModel.php";
require_once ROOT . "/Model/UsersPackagesModel.php";
require_once ROOT . "/App/Helper/date.php";

use App\Helper\Date;
use App\Helper\Security;

$Settings = new SettingsModel();
$User = new UserModel();
$Packages = new PackageModel();
$UsersPackages = new UsersPackageModel();

$user = $_SESSION['user'];
$userId = $user->id;
$firmId = $_SESSION['firm_id'];

// Get settings
$work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
$show_white_collar = $Settings->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;

// Get package info
$user_package = $UsersPackages->getSelectUserPackage($userId);
$current_package = $Packages->getPackage($user_package->package_id ?? 0) ?? null;
?>

<div class="container px-0">
    <div class="mb-4">
        <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Ayarlar</h2>
        <p class="text-muted text-xs">Sistem ve hesap ayarlarınızı buradan yönetin</p>
    </div>

    <!-- Tabs Navigation -->
    <div class="mobile-tabs-container mb-4 overflow-auto">
        <ul class="nav nav-pills mobile-nav-pills flex-nowrap" id="settingsTabs" role="tablist" style="min-width: max-content;">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="general-tab" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab">Genel</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="financial-tab" data-bs-toggle="pill" data-bs-target="#tab-financial" type="button" role="tab">Finansal</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="profile-tab" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab">Profil</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="account-tab" data-bs-toggle="pill" data-bs-target="#tab-account" type="button" role="tab">Hesap</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="notifications-tab" data-bs-toggle="pill" data-bs-target="#tab-notifications" type="button" role="tab">Bildirimler</button>
            </li>
        </ul>
    </div>

    <div class="tab-content" id="settingsTabContent">
        <!-- Genel Ayarlar -->
        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <div class="mobile-card p-3 mb-3">
                <form id="settingsHomeForm">
                    <div class="mb-3">
                        <label class="form-label text-semibold text-xs text-uppercase tracking-wider">Sistem Ayarları</label>
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
            <div class="mobile-card p-3 mb-3">
                <form id="settingsFinancialForm">
                    <label class="form-label text-semibold text-xs text-uppercase tracking-wider">Kasa Ayarları</label>
                    <p class="text-muted text-xxs mb-3">Kasa alt limitlerini yönetin</p>

                    <div class="form-floating mb-3">
                        <input type="number" class="form-control" name="sub_limit" id="sub_limit" placeholder="0" value="<?php echo htmlspecialchars($sub_limit); ?>">
                        <label for="sub_limit">Kasa Alt Limiti (₺)</label>
                    </div>

                    <button type="button" class="btn btn-primary w-100 py-2 mt-2" id="financial_save">
                        <i class="ti ti-device-floppy me-2"></i> Finansal Ayarları Kaydet
                    </button>
                </form>
            </div>
        </div>

        <!-- Profil Ayarları -->
        <div class="tab-pane fade" id="tab-profile" role="tabpanel">
            <div class="mobile-card p-3 mb-3">
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

        <!-- Hesap Ayarları -->
        <div class="tab-pane fade" id="tab-account" role="tabpanel">
            <div class="mobile-card p-3 mb-3">
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

                <a href="/index.php?p=settings/manage&tab=account" class="btn btn-outline-primary w-100 py-2">
                    <i class="ti ti-external-link me-1"></i> Tüm Paketleri Gör
                </a>
            </div>

            <div class="mobile-card p-3 border-danger-lt" style="border: 1px solid rgba(214, 51, 108, 0.2);">
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

        <!-- Bildirim Ayarları -->
        <div class="tab-pane fade" id="tab-notifications" role="tabpanel">
            <?php 
            $get_value = $Settings->getSettingIdByUserAndAction($userId, "loginde_mail_gonder")->set_value ?? null;
            $send_email_on_login = $get_value ? "checked" : "";
            ?>
            <div class="mobile-card p-3 mb-3">
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
    </div>
</div>

<style>
.mobile-tabs-container {
    background: var(--tblr-bg-surface);
    padding: 4px;
    border-radius: 12px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.02);
}
.mobile-nav-pills {
    display: flex;
    gap: 4px;
}
.mobile-nav-pills .nav-item {
    flex: 1;
}
.mobile-nav-pills .nav-link {
    text-align: center;
    font-size: 0.85rem;
    font-weight: 600;
    padding: 8px 4px;
    border-radius: 8px;
    color: var(--tblr-muted);
}
.mobile-nav-pills .nav-link.active {
    background: var(--tblr-primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(32, 107, 196, 0.2);
}
.mobile-switch .form-check-input {
    width: 3.5rem;
    height: 1.75rem;
    cursor: pointer;
}
.mobile-switch .form-check-label {
    font-size: 0.85rem;
    font-weight: 500;
    max-width: 80%;
}
.border-danger-lt {
    background-color: rgba(214, 51, 108, 0.02);
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
