<?php
$firm_id = isset($_SESSION['firm_id']) ? $_SESSION['firm_id'] : 0;

require_once "App/Helper/company.php";
require_once ROOT . '/Model/DuyuruModel.php';
require_once ROOT . '/App/Helper/security.php';

$_topbar_kullanici_id  = $_SESSION['user']->id ?? 0;
$_topbar_is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$_topbar_is_main_user  = !$_topbar_is_superadmin && (($_SESSION['user']->parent_id ?? 1) == 0);
$_topbar_okunmamis     = 0;
$_topbar_son_duyurular = [];
try {
    $_topbar_duyuru        = new DuyuruModel();
    $_topbar_okunmamis     = $_topbar_is_superadmin ? 0 : $_topbar_duyuru->getOkunmamisSayisi($_topbar_kullanici_id, $firm_id, $_topbar_is_main_user);
    $_topbar_son_duyurular = $_topbar_duyuru->getDuyurular($_topbar_kullanici_id, $firm_id, $_topbar_is_superadmin, $_topbar_is_main_user);
} catch (Exception $e) {
    // duyurular tablosu henüz oluşturulmamış
}

$company = new CompanyHelper();

// Mevcut URL'yi al
$current_url = $_SERVER['REQUEST_URI'];

// URL'yi parse et
$url_parts = parse_url($current_url);

// Query string'i al ve parse et
parse_str($url_parts['query'] ?? '', $query_params);

// theme parametresini oturumdan kontrol et ve sonraki durum için değiştir
$current_theme = $_SESSION['theme'] ?? 'light';
if ($current_theme === 'dark') {
    $query_params['theme'] = 'light';
} else {
    $query_params['theme'] = 'dark';
}


// Yeni query string oluştur
$new_query_string = http_build_query($query_params);

// Yeni URL oluştur
$new_url = $url_parts['path'] . '?' . $new_query_string;

?>


<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">

        <div class="navbar">
        <div class="collapse-button text-muted" onclick="toggleNavbar()">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

            <!-- <div class="col-auto align-bottom" style="margin-left:250px;">

                 <button class="btn" type="button">
                    <i class="ti ti-menu-2"></i>
                </button> 

            </div> -->



            <div class="navbar-nav flex-row order-md-last ms-auto me-3">

                <div class="nav-item ms-auto">
                    <?php


                    // Sayfa adını kontrol et
                    if (basename($_SERVER['PHP_SELF']) != 'company-list.php') {
                        echo $company->myCompanySelect("myFirm", $firm_id);
                    }

                    ?>
                    
                </div>
                <div class="d-none d-md-flex">

                    <a href="<?php echo htmlspecialchars($new_url); ?>" class="nav-link px-0 hide-theme-dark js-theme-toggle"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" aria-label="Enable dark mode"
                        data-bs-original-title="Enable dark mode">
                        <!-- Download SVG icon from http://tabler-icons.io/i/moon -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z">
                            </path>
                        </svg>
                    </a>
                    <a href="<?php echo htmlspecialchars($new_url); ?>" class="nav-link px-0 hide-theme-light js-theme-toggle"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" aria-label="Enable light mode"
                        data-bs-original-title="Enable light mode">
                        <!-- Download SVG icon from http://tabler-icons.io/i/sun -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            class="icon">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M12 12m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"></path>
                            <path
                                d="M3 12h1m8 -9v1m8 8h1m-9 8v1m-6.4 -15.4l.7 .7m12.1 -.7l-.7 .7m0 11.4l.7 .7m-12.1 -.7l-.7 .7">
                            </path>
                        </svg>
                    </a>
                    
                    <?php
                    $_topbar_has_support_permission = false;
                    if (isset($_SESSION['user'])) {
                        if (isset($Auths)) {
                            $_topbar_has_support_permission = $Auths->Authorize('supports_tickets_view');
                        } else {
                            require_once ROOT . '/Model/Auths.php';
                            $_topbar_auth = new Auths();
                            $_topbar_has_support_permission = $_topbar_auth->Authorize('supports_tickets_view');
                        }
                    }

                    if ($_topbar_has_support_permission) {
                        require_once ROOT . '/Model/SupportsModel.php';
                        try {
                            $_topbar_supports_model = new SupportsModel();
                            $_topbar_open_supports_count = $_topbar_supports_model->getOpenSupportsCount();
                        } catch (Exception $e) {
                            $_topbar_open_supports_count = 0;
                        }
                    ?>
                    <a href="index.php?p=<?php echo $_topbar_is_superadmin ? 'supports/admin-tickets' : 'supports/tickets'; ?>" class="nav-link px-0 me-3"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Destek Talepleri" aria-label="Destek Talepleri">
                        <span class="position-relative d-inline-flex">
                            <i class="ti ti-headset" style="font-size:1.25rem;"></i>
                            <?php if ($_topbar_open_supports_count > 0): ?>
                            <span style="position:absolute;top:-5px;right:-7px;min-width:15px;height:15px;padding:0 3px;font-size:9px;line-height:15px;border-radius:8px;background:#2fb344;color:#fff;text-align:center;pointer-events:none;font-weight:600;"><?= $_topbar_open_supports_count ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                    <?php } ?>

                    <div class="nav-item dropdown d-none d-md-flex me-3">
                        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1"
                            aria-label="Duyurular">
                            <span class="position-relative d-inline-flex">
                                <i class="ti ti-bell" style="font-size:1.25rem;"></i>
                                <?php if ($_topbar_okunmamis > 0): ?>
                                <span style="position:absolute;top:-5px;right:-7px;min-width:15px;height:15px;padding:0 3px;font-size:9px;line-height:15px;border-radius:8px;background:#d63939;color:#fff;text-align:center;pointer-events:none;font-weight:600;"><?= $_topbar_okunmamis ?></span>
                                <?php endif; ?>
                            </span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card" style="min-width:320px;">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h3 class="card-title">Duyurular</h3>
                                    <a href="index.php?p=duyurular/list" class="btn btn-sm btn-ghost-primary">Tümü</a>
                                </div>
                                <div class="list-group list-group-flush list-group-hoverable" style="max-height:320px;overflow-y:auto;">
                                    <?php
                                    $topbarList = array_slice($_topbar_son_duyurular, 0, 6);
                                    if (empty($topbarList)):
                                    ?>
                                    <div class="list-group-item text-secondary text-center py-3">
                                        Henüz duyuru yok
                                    </div>
                                    <?php else: foreach ($topbarList as $_td):
                                        $_td_okundu  = !$_topbar_is_superadmin && !empty($_td->okundu_at);
                                        $_td_oncelik_map   = ['acil' => 'bg-red', 'onemli' => 'bg-orange'];
                                        $_td_oncelik_class = $_td_oncelik_map[$_td->oncelik] ?? 'bg-blue';
                                    ?>
                                    <div class="list-group-item topbar-duyuru-item"
                                         data-id="<?= \App\Helper\Security::encrypt($_td->id) ?>"
                                         data-okundu="<?= $_td_okundu ? '1' : '0' ?>"
                                         style="cursor:pointer;">
                                        <div class="row align-items-center">
                                            <div class="col-auto">
                                                <span class="status-dot <?= $_td_okundu ? '' : 'status-dot-animated' ?> <?= $_td_oncelik_class ?> d-block"></span>
                                            </div>
                                            <div class="col text-truncate">
                                                <span class="text-body d-block <?= $_td_okundu ? 'text-secondary' : 'fw-bold' ?>">
                                                    <?= htmlspecialchars($_td->baslik) ?>
                                                </span>
                                                <div class="d-block text-secondary text-truncate mt-n1 small">
                                                    <?= date('d.m.Y', strtotime($_td->created_at)) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nav-item dropdown">
                    <?php
                    $_topbar_avatar = $_SESSION["user"]->avatar ?? null;
                    $_topbar_avatar_exists = !empty($_topbar_avatar) && file_exists(ROOT . '/uploads/avatars/' . $_topbar_avatar);
                    $_topbar_avatar_url = $_topbar_avatar_exists ? 'uploads/avatars/' . htmlspecialchars($_topbar_avatar) : '';

                    $_topbar_user_name = $_SESSION["user"]->full_name ?? '';
                    $topbar_words = explode(" ", trim($_topbar_user_name));
                    $topbar_initials = "";
                    foreach ($topbar_words as $w) {
                        $topbar_initials .= mb_substr($w, 0, 1, 'UTF-8');
                    }
                    $topbar_initials = mb_strtoupper(mb_substr($topbar_initials, 0, 2, 'UTF-8'));
                    if (empty($topbar_initials)) { $topbar_initials = "U"; }
                    ?>
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown"
                        aria-label="Open user menu">
                        <?php if ($_topbar_avatar_exists): ?>
                            <span class="avatar avatar-sm rounded-circle" style="background-image: url('<?php echo $_topbar_avatar_url; ?>'); background-size: cover; background-position: center;"></span>
                        <?php else: ?>
                            <span class="avatar avatar-sm rounded-circle bg-primary text-white fw-bold d-inline-flex align-items-center justify-content-center" style="font-size: 11px;"><?php echo htmlspecialchars($topbar_initials); ?></span>
                        <?php endif; ?>
                        <div class="d-none d-xl-block ps-2">
                            <div><?php echo htmlspecialchars($_SESSION["user"]->full_name ?? ''); ?></div>
                            <div class="mt-1 small text-secondary"><?php echo htmlspecialchars($_SESSION["user"]->job ?? ''); ?></div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow" data-bs-theme="light">
                        <?php if ($_topbar_is_superadmin): ?>
                        <a href="index.php?p=supports/admin-tickets" class="dropdown-item">
                            <i class="ti ti-headset me-2"></i> Destek Yönetimi
                        </a>
                        <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a href="index.php?p=settings/manage&view=profile" class="dropdown-item">
                            <i class="ti ti-user me-2"></i> Profil Bilgileri
                        </a>
                        <a href="index.php?p=settings/manage&view=profile#tabs-notifications-7" class="dropdown-item">
                            <i class="ti ti-bell me-2"></i> Bildirim Tercihleri
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="./logout.php" class="dropdown-item text-danger">
                            <i class="ti ti-logout me-2"></i> Çıkış Yap
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<script>
$(document).on('click', '.topbar-duyuru-item', function () {
    var encId = $(this).data('id');
    var okundu = $(this).data('okundu');
    var go = function () { window.location.href = 'index.php?p=duyurular/list'; };

    if (okundu == '1') { go(); return; }

    $.post('pages/duyurular/api.php', { action: 'okundu', id: encId }).always(go);
});
</script>