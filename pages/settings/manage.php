<?php
require_once 'Model/Cases.php';
require_once 'Model/SettingsModel.php';
require_once 'Model/Auths.php';

$Settings = new SettingsModel();
$Auths = new Auths();

$caseObj = new Cases();
$id = $_GET['id'] ?? 0;
$view = $_GET['view'] ?? 'system';

// Yetki kontrolü (Sistem ayarları için)
$has_settings_auth = false;
$settings_auth = $Auths->getAuthIdByTitle("Ayarlar");
if ($settings_auth && $Auths->AuthorizeByAuthId($settings_auth->id)) {
    $has_settings_auth = true;
}

// Eğer sistem görünümündeyse ve yetki yoksa, profile yönlendir veya hata ver
if ($view == 'system' && !$has_settings_auth) {
    $view = 'profile';
}

$pageTitle = ($view == 'profile') ? 'Profil ve Hesap Ayarları' : 'Sistem Ayarları';
?>
<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        <?php echo $pageTitle; ?>
                    </h2>
                </div>
               

            </div>
        </div>
    </div>
    <!-- Page body -->
    <div class="page-body">
        <div class="container-xl">
            <div class="col-md-12">

                
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs card-header-tabs nav-fill" data-bs-toggle="tabs" role="tablist">
                                    <?php if ($view == 'system'): ?>
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
                                    <?php else: ?>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-profile-7" id="tabs-profile" class="nav-link active"
                                            data-bs-toggle="tab" aria-selected="true"
                                            role="tab">
                                            <i class="ti ti-user icon me-2"></i>
                                            Profil Bilgileri</a>
                                    </li>
                                    <?php if ($_SESSION["user"]->parent_id == 0): ?>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-account-7" id="tabs-account" class="nav-link" data-bs-toggle="tab"
                                            aria-selected="false" tabindex="-1"
                                            role="tab">
                                            <i class="ti ti-settings-spark icon me-2"></i>
                                            Hesabım</a>
                                    </li>
                                    <?php endif; ?>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-notifications-7" id="tabs-notifications" class="nav-link" data-bs-toggle="tab"
                                            aria-selected="false" tabindex="-1"
                                            role="tab">
                                            <i class="ti ti-bell-ringing icon me-2"></i>
                                            Bildirimler</a>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                            <div class="card-body">
                                <div class="tab-content">
                                    <?php if ($view == 'system'): ?>
                                    <div class="tab-pane active show" id="tabs-home-7" role="tabpanel">
                                        <?php include_once "content/0-general.php" ?>
                                    </div>
                                    <div class="tab-pane" id="tabs-financial-7" role="tabpanel">
                                        <?php include_once "content/1-financial.php" ?>
                                    </div>
                                    <?php else: ?>
                                    <div class="tab-pane active show" id="tabs-profile-7" role="tabpanel">
                                        <?php include_once "content/2-profile.php" ?>
                                    </div>
                                    <?php if ($_SESSION["user"]->parent_id == 0): ?>
                                    <div class="tab-pane" id="tabs-account-7" role="tabpanel">
                                        <?php include_once "content/3-account.php" ?>
                                    </div>
                                    <?php endif; ?>
                                    <div class="tab-pane" id="tabs-notifications-7" role="tabpanel">
                                        <?php include_once "content/4-notifications.php" ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                   
            </div>

        </div>
    </div>
</div>

