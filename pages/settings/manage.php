<?php
require_once 'Model/Cases.php';
require_once 'Model/SettingsModel.php';
require_once 'Model/Auths.php';
require_once 'Model/UserModel.php';

$Settings = new SettingsModel();
$Auths = new Auths();
$UserModel = new UserModel();

$caseObj = new Cases();
$id = $_GET['id'] ?? 0;
$view = $_GET['view'] ?? 'system';

// Yetki kontrolü (Sistem ayarları için)
$has_settings_auth = false;
$settings_auth = $Auths->getAuthIdByTitle("Ayarlar");
if ($settings_auth && $Auths->AuthorizeByAuthId($settings_auth->id)) {
    $has_settings_auth = true;
}

// Eğer sistem görünümündeyse ve yetki yoksa, profile yönlendir
if ($view == 'system' && !$has_settings_auth) {
    $view = 'profile';
}

$pageTitle = ($view == 'profile') ? 'Profil Ayarları' : 'Sistem Ayarları';
?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title fw-bold text-dark" style="font-size: 1.75rem;">
                        <?php echo $pageTitle; ?>
                    </h2>
                    <?php if ($view == 'profile'): ?>
                        <p class="text-secondary mb-0 mt-1" style="font-size: 0.95rem;">Kişisel bilgilerinizi, güvenlik tercihlerinizi ve hesap detaylarınızı buradan yönetin.</p>
                    <?php endif; ?>
                </div>
                
                <?php if ($view == 'profile'): ?>
                <div class="col-auto ms-auto" id="btn-save-changes-container" style="display: none;">
                    <button type="button" class="btn btn-dark px-4 py-2" id="btn-save-changes" style="border-radius: 8px; background-color: #1d1d20; border-color: #1d1d20; font-weight: 600;">
                        <i class="ti ti-device-floppy icon me-2" style="font-size: 1.1rem;"></i>
                        Değişiklikleri Kaydet
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            
            <?php if ($view == 'system'): ?>
            <!-- System Settings (Original Layout) -->
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs nav-fill" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#tabs-home-7" id="tabs-home" class="nav-link active"
                                    data-bs-toggle="tab" aria-selected="true"
                                    role="tab">
                                    <i class="ti ti-home icon me-2"></i>
                                    Genel Bilgiler</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tabs-financial-7" id="tabs-financial" class="nav-link"
                                    data-bs-toggle="tab" aria-selected="false"
                                    role="tab">
                                    <i class="ti ti-receipt icon me-2"></i>
                                    Finansal İşlemler</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active show" id="tabs-home-7" role="tabpanel">
                                <?php include_once "content/0-general.php" ?>
                            </div>
                            <div class="tab-pane" id="tabs-financial-7" role="tabpanel">
                                <?php include_once "content/1-financial.php" ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php else: ?>
            <!-- Profile Settings (New Sidebar Split Layout) -->
            <style>
                .settings-sidebar-link.active {
                    background-color: #1d1d20 !important;
                    color: #fff !important;
                    border-color: #1d1d20 !important;
                }
                .settings-sidebar-link.active i {
                    color: #fff !important;
                }
                .settings-sidebar-link {
                    color: #495057 !important;
                    border-left: 3px solid transparent;
                    font-weight: 500;
                    transition: all 0.2s ease;
                }
                .settings-sidebar-link:hover:not(.active) {
                    background-color: #f8f9fa !important;
                }
                @media (min-width: 768px) {
                    .border-end-md {
                        border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
                    }
                }
            </style>
            
            <div class="row g-4">
                <!-- Sol Kenar Çubuğu (Sidebar) -->
                <div class="col-md-3">
                    <!-- Kullanıcı Bilgi Özeti -->
                    <?php
                    $words = explode(" ", trim($user->full_name ?? ''));
                    $initials = "";
                    foreach ($words as $w) {
                        $initials .= mb_substr($w, 0, 1, 'UTF-8');
                    }
                    $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'));
                    if (empty($initials)) { $initials = "U"; }
                    ?>
                    <div class="card mb-3" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
                        <div class="card-body text-center p-4">
                            <span class="avatar avatar-xl mb-3 rounded-circle bg-dark text-white fw-bold" style="font-size: 1.5rem; width: 80px; height: 80px; line-height: 80px; display: inline-flex; align-items: center; justify-content: center; background-color: #1d1d20 !important;"><?php echo htmlspecialchars($initials); ?></span>
                            <h3 class="mb-1 fw-bold text-dark" style="font-size: 1.15rem;"><?php echo htmlspecialchars($user->full_name ?? ''); ?></h3>
                            <div class="text-secondary small" style="font-size: 0.85rem;"><?php echo htmlspecialchars($user->email ?? ''); ?></div>
                        </div>
                    </div>
                    
                    <!-- Dikey Sekme Listesi -->
                    <div class="card mb-3" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01); overflow: hidden;">
                        <div class="list-group list-group-flush" id="settings-tabs" role="tablist">
                            <a href="#tabs-profile" class="list-group-item list-group-item-action settings-sidebar-link active py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-user icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Kişisel Bilgiler</span>
                            </a>
                            <a href="#tabs-password" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-lock icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Şifre Değiştir</span>
                            </a>
                            <?php if ($_SESSION["user"]->parent_id == 0 || $_SESSION["user"]->parent_id == $_SESSION["user"]->id || ($_SESSION["user"]->is_main_user ?? 0) == 1): ?>
                            <a href="#tabs-account" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-info-circle icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Hesap Detayları</span>
                            </a>
                            <?php endif; ?>
                            <a href="#tabs-logs" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-history icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Giriş Kayıtları</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Hatırlatma Kutusu -->
                    <div class="card bg-white" id="settings-reminder" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); display: none;">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="text-blue me-2 mt-0.5">
                                    <i class="ti ti-info-circle" style="font-size: 1.35rem;"></i>
                                </div>
                                <div>
                                    <h4 class="card-title mb-1 fw-bold text-dark" style="font-size: 0.9rem;">Hatırlatma</h4>
                                    <p class="text-secondary small mb-0" style="font-size: 0.8rem; line-height: 1.4;">Profil değişikliklerinizin kaydedilmesi için sağ üst köşedeki "Değişiklikleri Kaydet" butonuna tıklayınız.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sağ İçerik Paneli (Tab Content) -->
                <div class="col-md-9">
                    <div class="tab-content">
                        <!-- Kişisel Bilgiler Sekmesi -->
                        <div class="tab-pane active show" id="tabs-profile" role="tabpanel">
                            <?php include_once "content/2-profile.php" ?>
                        </div>
                        
                        <!-- Şifre Değiştirme Sekmesi -->
                        <div class="tab-pane" id="tabs-password" role="tabpanel">
                            <?php include_once "content/2-password.php" ?>
                        </div>
                        
                        <!-- Hesap Detayları Sekmesi -->
                        <?php if ($_SESSION["user"]->parent_id == 0 || $_SESSION["user"]->parent_id == $_SESSION["user"]->id || ($_SESSION["user"]->is_main_user ?? 0) == 1): ?>
                        <div class="tab-pane" id="tabs-account" role="tabpanel">
                            <?php include_once "content/3-account.php" ?>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Giriş Kayıtları Sekmesi -->
                        <div class="tab-pane" id="tabs-logs" role="tabpanel">
                            <?php include_once "content/5-logs.php" ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            document.addEventListener("DOMContentLoaded", function () {
                // Tab switch event listeners to manage header save button & reminder box
                function toggleSaveElements() {
                    var activeTab = document.querySelector("#settings-tabs a.active");
                    if (!activeTab) return;
                    
                    var targetId = activeTab.getAttribute("href");
                    var saveBtnContainer = document.getElementById("btn-save-changes-container");
                    var reminderCard = document.getElementById("settings-reminder");
                    
                    if (targetId === "#tabs-profile" || targetId === "#tabs-password") {
                        if (saveBtnContainer) saveBtnContainer.style.display = "block";
                        if (reminderCard) reminderCard.style.display = "block";
                    } else {
                        if (saveBtnContainer) saveBtnContainer.style.display = "none";
                        if (reminderCard) reminderCard.style.display = "none";
                    }
                }
                
                // Initialize elements state
                toggleSaveElements();
                
                // Trigger state changes on tab change
                var tabLinks = document.querySelectorAll("#settings-tabs a[data-bs-toggle='tab']");
                tabLinks.forEach(function(tab) {
                    tab.addEventListener("shown.bs.tab", function() {
                        toggleSaveElements();
                    });
                });
                
                // Global "Değişiklikleri Kaydet" header button trigger
                var headerSaveBtn = document.getElementById("btn-save-changes");
                if (headerSaveBtn) {
                    headerSaveBtn.addEventListener("click", function() {
                        var activeTab = document.querySelector("#settings-tabs a.active");
                        if (!activeTab) return;
                        
                        var targetId = activeTab.getAttribute("href");
                        if (targetId === "#tabs-profile") {
                            // Trigger profile form submit
                            var profileFormSubmitBtn = document.getElementById("profileFormSubmit");
                            if (profileFormSubmitBtn) {
                                profileFormSubmitBtn.click();
                            } else {
                                // Fallback: trigger custom save event/form submit
                                var profileForm = document.getElementById("profileForm");
                                if (profileForm) {
                                    var event = new Event("submit", { cancelable: true });
                                    profileForm.dispatchEvent(event);
                                }
                            }
                        } else if (targetId === "#tabs-password") {
                            // Trigger password form submit
                            var passwordForm = document.getElementById("passwordForm");
                            if (passwordForm) {
                                var event = new Event("submit", { cancelable: true });
                                passwordForm.dispatchEvent(event);
                            }
                        }
                    });
                }
            });
            </script>
            <?php endif; ?>
            
        </div>
    </div>
</div>
