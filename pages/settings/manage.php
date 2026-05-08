<?php
require_once 'Model/Cases.php';
require_once 'Model/SettingsModel.php';

$Settings = new SettingsModel();

$caseObj = new Cases();
$id = $_GET['id'] ?? 0;

$pageTitle = 'Ayarlar';

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
                                <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-home-7" id="tabs-home" class="nav-link active"
                                            data-bs-toggle="tab" aria-selected="true"
                                            role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                                            <i class="ti ti-home icon me-2"></i>
                                            Genel Bilgiler</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-financial-7" id="tabs-home" class="nav-link"
                                            data-bs-toggle="tab" aria-selected="true"
                                            role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/home -->
                                            <i class="ti ti-home icon me-2"></i>
                                            Finansal İşlemler</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-profile-7" id="tabs-profile" class="nav-link"
                                            data-bs-toggle="tab" aria-selected="false" tabindex="-1"
                                            role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/user -->
                                            <i class="ti ti-user icon me-2"></i>
                                            Profil Bilgileri</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-account-7" id="tabs-account" class="nav-link" data-bs-toggle="tab"
                                            aria-selected="false" tabindex="-1"
                                            role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/activity -->
                                            <i class="ti ti-settings-spark icon me-2"></i>
                                            Hesabım</a>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <a href="#tabs-notifications-7" class="nav-link" data-bs-toggle="tab"
                                            aria-selected="false" tabindex="-1"
                                            role="tab"><!-- Download SVG icon from http://tabler-icons.io/i/activity -->
                                            <i class="ti ti-bell-ringing icon me-2"></i>
                                            Bildirimler</a>
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
                                    <div class="tab-pane" id="tabs-profile-7" role="tabpanel">
                                        <?php include_once "content/2-profile.php" ?>
                                    </div>
                                    <div class="tab-pane" id="tabs-account-7" role="tabpanel">
                                        <?php include_once "content/3-account.php" ?>
                                    </div>
                                    <div class="tab-pane" id="tabs-notifications-7" role="tabpanel">
                                        <?php include_once "content/4-notifications.php" ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                   
            </div>

        </div>
    </div>
</div>

