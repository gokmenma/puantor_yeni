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


<?php
$_topbar_is_bordro_or_puantaj = (
    in_array($active_page ?? '', ['payroll/list', 'puantaj/list', 'payroll/bordro', 'payroll/pay-slip', 'raporlar/list'], true) ||
    str_starts_with($active_page ?? '', 'puantaj/') ||
    str_starts_with($active_page ?? '', 'payroll/') ||
    str_starts_with($active_page ?? '', 'raporlar/')
);

$_topbar_period_year = (int) ($_SESSION['period_year'] ?? date('Y'));
$_topbar_period_month = (int) ($_SESSION['period_month'] ?? date('m'));
$_topbar_month_names = [1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];
$_topbar_days_in_month = cal_days_in_month(CAL_GREGORIAN, $_topbar_period_month, $_topbar_period_year);
$_topbar_month_name = $_topbar_month_names[$_topbar_period_month] ?? '';
$_topbar_period_formatted = sprintf('%s %04d', $_topbar_month_name, $_topbar_period_year);

require_once ROOT . '/Model/MyFirmModel.php';
$_topbar_myFirmModel = new MyFirmModel();
$_topbar_myFirms = $_topbar_myFirmModel->getMyFirmByUserId();

$_topbar_current_firm_name = 'Firma Seçiniz';
if ($firm_id > 0) {
    foreach ($_topbar_myFirms as $f) {
        if ((int)$f->id === (int)$firm_id) {
            $_topbar_current_firm_name = $f->firm_name;
            break;
        }
    }
    if ($_topbar_current_firm_name === 'Firma Seçiniz') {
        $_topbar_current_firm_name = $company->getFirmName($firm_id);
    }
} elseif (!empty($_topbar_myFirms)) {
    $_topbar_current_firm_name = $_topbar_myFirms[0]->firm_name;
}
?>


<header class="navbar-expand-md">
    <div class="collapse navbar-collapse" id="navbar-menu">

        <div class="navbar position-relative">
            <div class="topbar-left-wrapper d-flex align-items-center">
                <div class="collapse-button text-muted me-2" onclick="toggleNavbar()">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <?php if (basename($_SERVER['PHP_SELF']) != 'company-list.php'): ?>
                <div class="d-flex align-items-center flex-wrap" style="gap: 4px;">
                    <!-- Firma Seçimi Dropdown -->
                    <div class="dropdown topbar-firm-dropdown">
                        <button class="btn btn-ghost-secondary text-body fw-bold py-1 px-2 text-decoration-none dropdown-toggle d-flex align-items-center border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="box-shadow:none; font-size: 0.92rem; border-radius: 6px;">
                            <span class="text-truncate" style="max-width: 260px;"><?= htmlspecialchars($_topbar_current_firm_name, ENT_QUOTES, 'UTF-8') ?></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-arrow shadow-sm py-1" style="min-width: 240px; max-height: 320px; overflow-y: auto;">
                            <div class="dropdown-header text-uppercase text-secondary fw-bold" style="font-size: 11px;">Firma Seçimi</div>
                            <?php foreach ($_topbar_myFirms as $firmItem): ?>
                                <?php $isCurrent = ((int)$firmItem->id === (int)$firm_id); ?>
                                <a class="dropdown-item d-flex align-items-center justify-content-between py-2 <?= $isCurrent ? 'active fw-bold' : '' ?>" 
                                   href="set-session.php?p=<?= urlencode($active_page) ?>&firm_id=<?= \App\Helper\Security::encrypt($firmItem->id) ?>">
                                    <span class="text-truncate"><?= htmlspecialchars($firmItem->firm_name, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($isCurrent): ?>
                                        <i class="ti ti-check ms-2 text-success"></i>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <?php if ($_topbar_is_bordro_or_puantaj): ?>
                    <span class="text-secondary opacity-50 px-1" style="font-size: 1.1rem; font-weight: 300;">/</span>

                    <!-- Dönem Seçimi Flatpickr MonthSelect -->
                    <div class="topbar-period-wrapper position-relative d-inline-flex align-items-center cursor-pointer py-1 px-2 rounded-2" id="topbar-period-container" title="Dönem Değiştir" style="transition: background-color 0.15s ease;">
                        <span class="status-dot status-dot-animated bg-success me-2" style="width: 8px; height: 8px;"></span>
                        <span class="fw-bold text-body me-1" id="topbar-period-display" style="font-size: 0.92rem; white-space: nowrap;"><?= htmlspecialchars($_topbar_period_formatted, ENT_QUOTES, 'UTF-8') ?></span>
                        <i class="ti ti-chevron-down text-muted" style="font-size: 0.82rem;"></i>
                        <input type="text" id="topbar-period-picker" class="position-absolute opacity-0" style="inset: 0; width: 100%; height: 100%; cursor: pointer; z-index: 5;" readonly aria-label="Dönem Seçimi" />
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Orta: Global Arama (Personel, Proje, Kasa, Cari, İcra, İzin, Görev) -->
            <div class="header-search-wrap d-none d-lg-flex">
                <div class="global-search-container" id="global-search-container">
                    <div class="global-search-input-box">
                        <i class="ti ti-search global-search-icon" aria-hidden="true"></i>
                        <input type="text" 
                            id="global-search-input" 
                            class="global-search-input" 
                            placeholder="Personel, proje, kasa, cari, icra, izin veya görev ara..." 
                            autocomplete="off" 
                            spellcheck="false">
                        <button type="button" class="global-search-clear-btn" id="global-search-clear" title="Temizle" style="display: none;">
                            <i class="ti ti-x"></i>
                        </button>
                        <div class="global-search-kbd-badge" title="Kısayol: Ctrl + K">
                            <kbd>ctrl</kbd><kbd>K</kbd>
                        </div>
                        <div class="global-search-spinner" id="global-search-spinner" style="display: none;">
                            <i class="ti ti-loader-2"></i>
                        </div>
                    </div>

                    <!-- Arama Sonuç Dropdown Kartı -->
                    <div class="global-search-dropdown" id="global-search-dropdown">
                        <!-- Kategori Filtreleme Sekmeleri -->
                        <div class="global-search-categories" id="global-search-categories">
                            <button type="button" class="gs-cat-pill active" data-cat="all">
                                <i class="ti ti-layout-grid"></i> Tümü <span class="gs-count" id="count-all">0</span>
                            </button>
                            <button type="button" class="gs-cat-pill" data-cat="persons">
                                <i class="ti ti-users"></i> Personeller <span class="gs-count" id="count-persons">0</span>
                            </button>
                            <button type="button" class="gs-cat-pill" data-cat="projects">
                                <i class="ti ti-folders"></i> Projeler <span class="gs-count" id="count-projects">0</span>
                            </button>
                            <button type="button" class="gs-cat-pill" data-cat="financial">
                                <i class="ti ti-wallet"></i> Kasa & Cari <span class="gs-count" id="count-financial">0</span>
                            </button>
                            <button type="button" class="gs-cat-pill" data-cat="icra">
                                <i class="ti ti-scale"></i> İcra <span class="gs-count" id="count-icra">0</span>
                            </button>
                            <button type="button" class="gs-cat-pill" data-cat="izin">
                                <i class="ti ti-calendar-time"></i> İzin <span class="gs-count" id="count-izin">0</span>
                            </button>
                            <button type="button" class="gs-cat-pill" data-cat="tasks">
                                <i class="ti ti-checkbox"></i> Görevler <span class="gs-count" id="count-tasks">0</span>
                            </button>
                        </div>

                        <!-- Sonuç İçerik Alanı -->
                        <div class="global-search-results" id="global-search-results">
                            <!-- JS dinamik render edecek -->
                        </div>

                        <!-- Alt Bilgi / Kısayol İpuçları Çubuğu -->
                        <div class="global-search-footer">
                            <div class="gs-footer-info" id="gs-footer-info">
                                Toplam <span id="gs-total-count">0</span> sonuç bulundu
                            </div>
                            <div class="gs-footer-hints">
                                <span class="gs-hint-item"><kbd>↑</kbd><kbd>↓</kbd> Gezin</span>
                                <span class="gs-hint-item"><kbd>↵</kbd> Seç</span>
                                <span class="gs-hint-item"><kbd>Esc</kbd> Kapat</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="navbar-nav flex-row order-md-last ms-auto me-3">
                <div class="d-none d-md-flex align-items-center">

                    <!-- Tema Özelleştirici Butonu -->
                    <a href="javascript:void(0);" onclick="openThemeCustomizer();" class="nav-link px-0 me-2"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Tema Özelleştirici" aria-label="Tema Özelleştirici">
                        <i class="ti ti-palette" style="font-size: 1.25rem;"></i>
                    </a>

                    <a href="<?php echo htmlspecialchars($new_url); ?>" class="nav-link px-0 me-1 hide-theme-dark js-theme-toggle"
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
                    <a href="<?php echo htmlspecialchars($new_url); ?>" class="nav-link px-0 me-1 hide-theme-light js-theme-toggle"
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
                            if (
                                in_array($active_page ?? '', ['supports/ticket-view', 'supports/admin-ticket-view'], true)
                                && !empty($_GET['id'])
                            ) {
                                $_topbar_support_id = \App\Helper\Security::decrypt($_GET['id']);
                                $_topbar_supports_model->markAsRead($_topbar_support_id);
                            }
                            $_topbar_unread_supports_count = $_topbar_supports_model->getUnreadSupportsCount();
                        } catch (\Throwable $e) {
                            $_topbar_unread_supports_count = 0;
                        }
                    ?>
                    <a href="<?php echo $_topbar_is_superadmin ? '/destek-yonetimi' : '/destek-talepleri'; ?>" class="nav-link px-0 me-1"
                        data-bs-toggle="tooltip" data-bs-placement="bottom" title="Destek Talepleri" aria-label="Destek Talepleri">
                        <span class="position-relative d-inline-flex">
                            <i class="ti ti-headset" style="font-size:1.25rem;"></i>
                            <?php if ($_topbar_unread_supports_count > 0): ?>
                            <span style="position:absolute;top:-5px;right:-7px;min-width:15px;height:15px;padding:0 3px;font-size:9px;line-height:15px;border-radius:8px;background:#2fb344;color:#fff;text-align:center;pointer-events:none;font-weight:600;"><?= $_topbar_unread_supports_count ?></span>
                            <?php endif; ?>
                        </span>
                    </a>
                    <?php } ?>

                    <div class="nav-item dropdown d-none d-md-flex me-1">
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
                                    <a href="/duyurular" class="btn btn-sm btn-ghost-primary">Tümü</a>
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
                        <a href="/destek-yonetimi" class="dropdown-item">
                            <i class="ti ti-headset me-2"></i> Destek Yönetimi
                        </a>
                        <div class="dropdown-divider"></div>
                        <?php endif; ?>
                        <a href="/ayarlar?view=profile" class="dropdown-item">
                            <i class="ti ti-user me-2"></i> Profil Bilgileri
                        </a>
                        <a href="/ayarlar?view=profile#tabs-notifications-7" class="dropdown-item">
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
    var go = function () { window.location.href = '/duyurular'; };

    if (okundu == '1') { go(); return; }

    $.post('pages/duyurular/api.php', { action: 'okundu', id: encId }).always(go);
});

$(document).ready(function () {
    var pickerEl = document.getElementById('topbar-period-picker');
    if (pickerEl && typeof flatpickr === 'function') {
        var defaultYear = <?= (int)($_topbar_period_year ?? date('Y')) ?>;
        var defaultMonth = <?= (int)($_topbar_period_month ?? date('m')) ?>;

        flatpickr('#topbar-period-picker', {
            locale: (typeof flatpickr.l10ns !== 'undefined' && flatpickr.l10ns.tr) ? flatpickr.l10ns.tr : 'tr',
            defaultDate: new Date(defaultYear, defaultMonth - 1, 1),
            dateFormat: "Y-m",
            plugins: [
                (typeof monthSelectPlugin === 'function') ? monthSelectPlugin({
                    shorthand: false,
                    dateFormat: "Y-m",
                    altFormat: "F Y"
                }) : null
            ].filter(Boolean),
            onChange: function (selectedDates, dateStr, instance) {
                if (selectedDates && selectedDates.length > 0) {
                    var d = selectedDates[0];
                    var y = d.getFullYear();
                    var m = (d.getMonth() + 1).toString().padStart(2, '0');
                    var period = y + '-' + m;

                    document.cookie = "p_months=" + m + "; path=/; max-age=31536000";
                    document.cookie = "p_year=" + y + "; path=/; max-age=31536000";

                    var url = new URL(window.location.href);
                    url.searchParams.set('period', period);
                    url.searchParams.set('year', y);
                    url.searchParams.set('months', m);
                    window.location.href = url.toString();
                }
            }
        });
    }
});
</script>

