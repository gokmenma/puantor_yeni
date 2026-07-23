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

$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$view = $_GET['view'] ?? ($is_superadmin ? 'system' : 'profile');

// Eğer sistem görünümündeyse ve superadmin değilse, profile yönlendir
if ($view == 'system' && !$is_superadmin) {
    $view = 'profile';
}

$pageTitle = ($view == 'profile') ? 'Profil Ayarları' : 'Sistem Ayarları';
?>
    <style>
        .settings-sidebar-link.active {
            background-color: var(--tblr-primary, #206bc4) !important;
            color: #fff !important;
            border-color: var(--tblr-primary, #206bc4) !important;
        }
        .settings-sidebar-link.active i {
            color: #fff !important;
        }
        .settings-sidebar-link {
            color: inherit !important;
            border-left: 3px solid transparent;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        .settings-sidebar-link:hover:not(.active) {
            background-color: rgba(var(--tblr-text-secondary-rgb, 108, 117, 125), 0.08) !important;
        }
        @media (min-width: 768px) {
            .border-end-md {
                border-right: 1px solid rgba(0, 0, 0, 0.08) !important;
            }
        }
    </style>

    <!-- Page header -->
    <div class="page-header d-print-none mb-3">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <div class="page-pretitle">Ayarlar</div>
                    <h2 class="page-title">
                        <?php echo $pageTitle; ?>
                    </h2>
                    <?php if ($view == 'profile'): ?>
                        <p class="text-secondary mb-0 mt-1">Kişisel bilgilerinizi, güvenlik tercihlerinizi ve hesap detaylarınızı buradan yönetin.</p>
                    <?php else: ?>
                        <p class="text-secondary mb-0 mt-1">İşçi Maaş Rapor Takip Platformunun genel, SMTP, güvenlik ve sistem yedekleme tercihlerini yönetin.</p>
                    <?php endif; ?>
                </div>
                
                <div class="col-auto ms-auto" id="btn-save-changes-container" style="display: none;">
                    <button type="button" class="btn btn-primary" id="btn-save-changes">
                        <i class="ti ti-device-floppy me-2"></i>
                        Değişiklikleri Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            
            <?php if ($view == 'system'): ?>
            <!-- System Settings (New Sidebar Split Layout) -->
            <div class="row g-4">
                <!-- Sol Kenar Çubuğu (Sidebar) -->
                <div class="col-md-3">
                    <div class="card mb-3">
                        <div class="list-group list-group-flush" id="system-settings-tabs" role="tablist">
                            <a href="#tabs-system-general" class="list-group-item list-group-item-action settings-sidebar-link active py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-world icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Genel Ayarlar</span>
                            </a>
                            <a href="#tabs-system-smtp" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-mail icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>SMTP E-Posta</span>
                            </a>
                            <a href="#tabs-program-settings" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-settings icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Program Ayarları</span>
                            </a>
                            <a href="#tabs-system-security" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-shield icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Güvenlik & API</span>
                            </a>
                            <a href="#tabs-system-backup" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-database icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Yedekleme & Bakım</span>
                            </a>
                            <a href="#tabs-system-ai" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-cpu icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Yapay Zeka</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Hatırlatma Kutusu -->
                    <div class="card mb-3" id="system-settings-reminder" style="display: none;">
                        <div class="card-status-start bg-info"></div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="text-info me-2 mt-0.5">
                                    <i class="ti ti-info-circle" style="font-size: 1.35rem;"></i>
                                </div>
                                <div>
                                    <h4 class="card-title mb-1 fw-bold" style="font-size: 0.9rem;">Hatırlatma</h4>
                                    <p class="text-secondary small mb-0" style="font-size: 0.8rem; line-height: 1.4;">Yapılan değişikliklerin yürürlüğe girmesi için sağ üst köşedeki "Değişiklikleri Kaydet" butonuna tıklamalısınız.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Sağ İçerik Paneli (Tab Content) -->
                <div class="col-md-9">
                    <div class="tab-content">
                        <!-- Genel Ayarlar Sekmesi -->
                        <div class="tab-pane active show" id="tabs-system-general" role="tabpanel">
                            <?php include_once "content/system-general.php" ?>
                        </div>
                        
                        <!-- SMTP Sekmesi -->
                        <div class="tab-pane" id="tabs-system-smtp" role="tabpanel">
                            <?php include_once "content/system-smtp.php" ?>
                        </div>

                        <!-- Program Ayarları Sekmesi -->
                        <div class="tab-pane" id="tabs-program-settings" role="tabpanel">
                            <?php include_once "content/0-general.php" ?>
                        </div>
                        
                        <!-- Güvenlik Sekmesi -->
                        <div class="tab-pane" id="tabs-system-security" role="tabpanel">
                            <?php include_once "content/system-security.php" ?>
                        </div>
                        
                        <!-- Yedekleme Sekmesi -->
                        <div class="tab-pane" id="tabs-system-backup" role="tabpanel">
                            <?php include_once "content/system-backup.php" ?>
                        </div>
                        
                        <!-- Yapay Zeka Sekmesi -->
                        <div class="tab-pane" id="tabs-system-ai" role="tabpanel">
                            <?php include_once "content/system-ai.php" ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <script>
            $(document).ready(function () {
                // Tab switch event listeners to manage header save button & reminder box for System Settings
                function toggleSystemSaveElements() {
                    var activeTab = $("#system-settings-tabs a.active");
                    if (!activeTab.length) return;
                    
                    var targetId = activeTab.attr("href");
                    var saveBtnContainer = $("#btn-save-changes-container");
                    var reminderCard = $("#system-settings-reminder");
                    
                    if (targetId === "#tabs-system-general" || targetId === "#tabs-system-smtp" || targetId === "#tabs-program-settings") {
                        saveBtnContainer.show();
                        reminderCard.show();
                    } else {
                        saveBtnContainer.hide();
                        reminderCard.hide();
                    }
                }
                
                // Initialize elements state
                toggleSystemSaveElements();
                
                // Trigger state changes on tab change
                $("#system-settings-tabs a[data-bs-toggle='tab']").on("shown.bs.tab", function() {
                    toggleSystemSaveElements();
                });
                
                // Global "Değişiklikleri Kaydet" header button trigger for System Settings
                $("#btn-save-changes").on("click", function() {
                    var activeTab = $("#system-settings-tabs a.active");
                    if (!activeTab.length) return;
                    
                    var targetId = activeTab.attr("href");
                    if (targetId === "#tabs-system-general") {
                        $("#systemGeneralForm").submit();
                    } else if (targetId === "#tabs-system-smtp") {
                        $("#systemSmtpForm").submit();
                    } else if (targetId === "#tabs-program-settings") {
                        $("#home_save").click();
                    }
                });
            });
            </script>
            
            <?php else: ?>
            <!-- Profile Settings (New Sidebar Split Layout) -->
            <div class="row g-4">
                <!-- Sol Kenar Çubuğu (Sidebar) -->
                <div class="col-md-3">
                    <!-- Kullanıcı Bilgi Özeti -->
                    <?php
                    $sidebarAvatar = $user->avatar ?? null;
                    $sidebarAvatarExists = !empty($sidebarAvatar) && file_exists(ROOT . '/uploads/avatars/' . $sidebarAvatar);
                    $sidebarAvatarUrl = $sidebarAvatarExists ? 'uploads/avatars/' . htmlspecialchars($sidebarAvatar) : '';

                    $words = explode(" ", trim($user->full_name ?? ''));
                    $initials = "";
                    foreach ($words as $w) {
                        $initials .= mb_substr($w, 0, 1, 'UTF-8');
                    }
                    $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'));
                    if (empty($initials)) { $initials = "U"; }
                    ?>
                    <div class="card mb-3">
                        <div class="card-body text-center p-4">
                            <?php if ($sidebarAvatarExists): ?>
                                <span class="avatar avatar-xl mb-3 rounded-circle shadow-sm" style="width: 80px; height: 80px; background-image: url('<?php echo $sidebarAvatarUrl; ?>'); background-size: cover; background-position: center;"></span>
                            <?php else: ?>
                                <span class="avatar avatar-xl mb-3 rounded-circle bg-primary text-white fw-bold shadow-sm" style="width: 80px; height: 80px; font-size: 1.6rem;"><?php echo htmlspecialchars($initials); ?></span>
                            <?php endif; ?>
                            <h3 class="mb-1 fw-bold"><?php echo htmlspecialchars($user->full_name ?? ''); ?></h3>
                            <div class="text-secondary small"><?php echo htmlspecialchars($user->email ?? ''); ?></div>
                        </div>
                    </div>
                    
                    <!-- Dikey Sekme Listesi -->
                    <div class="card mb-3">
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
                            <?php if ($is_superadmin || $Auths->hasPermission("daily_working_hours_edit")): ?>
                            <a href="#tabs-system-settings" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-settings icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Program Ayarları</span>
                            </a>
                            <?php endif; ?>
                            <a href="#tabs-logs" class="list-group-item list-group-item-action settings-sidebar-link py-3 px-4 d-flex align-items-center" data-bs-toggle="tab" role="tab">
                                <i class="ti ti-history icon me-3 text-secondary" style="font-size: 1.2rem;"></i>
                                <span>Giriş Kayıtları</span>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Hatırlatma Kutusu -->
                    <div class="card mb-3" id="settings-reminder" style="display: none;">
                        <div class="card-status-start bg-info"></div>
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start">
                                <div class="text-info me-2 mt-0.5">
                                    <i class="ti ti-info-circle" style="font-size: 1.35rem;"></i>
                                </div>
                                <div>
                                    <h4 class="card-title mb-1 fw-bold" style="font-size: 0.9rem;">Hatırlatma</h4>
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
                        
                        <!-- Program Ayarları Sekmesi -->
                        <?php if ($is_superadmin || $Auths->hasPermission("daily_working_hours_edit")): ?>
                        <div class="tab-pane" id="tabs-system-settings" role="tabpanel">
                            <?php include_once "content/0-general.php" ?>
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
            $(document).ready(function () {
                // Tab switch event listeners to manage header save button & reminder box
                function toggleSaveElements() {
                    var activeTab = $("#settings-tabs a.active");
                    if (!activeTab.length) return;
                    
                    var targetId = activeTab.attr("href");
                    var saveBtnContainer = $("#btn-save-changes-container");
                    var reminderCard = $("#settings-reminder");
                    
                    if (targetId === "#tabs-profile" || targetId === "#tabs-password" || targetId === "#tabs-system-settings") {
                        saveBtnContainer.show();
                        reminderCard.show();
                    } else {
                        saveBtnContainer.hide();
                        reminderCard.hide();
                    }
                }
                
                // Initialize elements state
                toggleSaveElements();
                
                // Trigger state changes on tab change
                $("#settings-tabs a[data-bs-toggle='tab']").on("shown.bs.tab", function() {
                    toggleSaveElements();
                });
                
                // Global "Değişiklikleri Kaydet" header button trigger
                $("#btn-save-changes").on("click", function() {
                    var activeTab = $("#settings-tabs a.active");
                    if (!activeTab.length) return;
                    
                    var targetId = activeTab.attr("href");
                    if (targetId === "#tabs-profile") {
                        $("#profileForm").submit();
                    } else if (targetId === "#tabs-password") {
                        $("#passwordForm").submit();
                    } else if (targetId === "#tabs-system-settings") {
                        $("#home_save").click();
                    }
                });
            });
            </script>
            <?php endif; ?>
            
        </div>
    </div>
</div>
