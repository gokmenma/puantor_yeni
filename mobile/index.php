<?php
// Puantor Premium Mobil Giriş ve Kabuk (App Shell) Dosyası
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

define("ROOT", dirname(__DIR__));
date_default_timezone_set('Europe/Istanbul');

require_once ROOT . "/App/Helper/session_security.php";
puantorStartSecureSession();
puantorEnforceSessionTimeout();
require_once __DIR__ . "/../Database/db.php";
require_once __DIR__ . "/../Model/UserModel.php";
require_once __DIR__ . "/../Model/MyFirmModel.php";

require_once __DIR__ . "/../Model/Auths.php";

$User = new UserModel();
$Auths = new Auths();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}


// Oturum kontrolü
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    if (isset($_COOKIE['remember_me_mobile'])) {
        $token = $_COOKIE['remember_me_mobile'];
        $cookie_user = $User->getUserByMobileSessionToken($token);
        if ($cookie_user && (int) ($cookie_user->superadmin ?? 0) === 1) {
            puantorExpireCookie('remember_me_mobile');
            $User->setMobileToken($cookie_user->id, bin2hex(random_bytes(32)));
            header("Location: sign-in.php");
            exit();
        } elseif ($cookie_user && $cookie_user->status == 1) {
            session_regenerate_id(true);
            $_SESSION['user'] = $cookie_user;
            $_SESSION['firm_id'] = $cookie_user->firm_id;
            $_SESSION['full_name'] = $cookie_user->full_name;
            $_SESSION['user_role'] = $cookie_user->user_roles;
            // Refresh cookie expiry
            $_SESSION['last_activity_at'] = time();
            setcookie('remember_me_mobile', $token, [
                'expires' => time() + 30 * 24 * 3600, 'path' => '/',
                'secure' => puantorIsHttps(), 'httponly' => true, 'samesite' => 'Lax',
            ]);
        } else {
            puantorExpireCookie('remember_me_mobile');
            header("Location: sign-in.php");
            exit();
        }
    } else {
        header("Location: sign-in.php");
        exit();
    }
}

$user = $User->find($_SESSION['user']->id) ?? null;

if (!$user) {
    header("Location: sign-in.php");
    exit();
}

$_SESSION["user"] = $user;
$is_superadmin = (int) ($user->superadmin ?? 0) === 1;

// Kullanıcının yetkili firmalarını çek
$myFirmObj = new MyFirmModel();
$myFirms = $is_superadmin ? [] : $myFirmObj->getMyFirmByUserId();

// Post ile firma seçildiyse ve kullanıcının bu firmaya yetkisi varsa session'a yaz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'select_firm') {
    if ($is_superadmin || !hash_equals((string) $_SESSION['csrf_token'], (string) ($_POST['csrf_token'] ?? ''))) {
        http_response_code(403);
        exit('Bu işlem için yetkiniz yok.');
    }
    $selected_firm_id = intval($_POST['firm_id'] ?? 0);
    if ($selected_firm_id > 0) {
        $has_access = false;
        foreach ($myFirms as $firm) {
            if ($firm->id == $selected_firm_id) {
                $has_access = true;
                break;
            }
        }
        
        if ($has_access) {
            $_SESSION['firm_id'] = $selected_firm_id;
            
            // Eğer alt kullanıcı ise seçili firmadaki verileri ile güncelle
            if ($_SESSION["user"]->parent_id != 0) {
                $email = $_SESSION['user']->email ?? null;
                $db_user = $User->getUserByEmailAndFirm($email, $selected_firm_id);
                if ($db_user) {
                    $_SESSION['user'] = $db_user;
                }
            }
        }
        
        header("Location: " . $_SERVER['REQUEST_URI']);
        exit();
    }
}

// Aktif firmanın yetki kontrolünü yap ve varsayılan firmayı güvenli bir şekilde belirle
$has_active_access = $is_superadmin;
if (isset($_SESSION['firm_id']) && !empty($_SESSION['firm_id'])) {
    foreach ($myFirms as $firm) {
        if ($firm->id == $_SESSION['firm_id']) {
            $has_active_access = true;
            break;
        }
    }
}

if (!$has_active_access) {
    $default_firm_id = (int) ($_SESSION['user']->default_firm_id ?? 0);
    $default_found = false;
    if ($default_firm_id > 0 && !empty($myFirms)) {
        foreach ($myFirms as $firm) {
            if ((int)$firm->id === $default_firm_id) {
                $_SESSION['firm_id'] = $default_firm_id;
                $default_found = true;
                break;
            }
        }
    }
    if (!$default_found) {
        if (!empty($myFirms)) {
            $_SESSION['firm_id'] = $myFirms[0]->id;
        } else {
            $_SESSION['firm_id'] = $_SESSION['user']->firm_id ?? 0;
        }
    }
}




// Tema ayarları
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'] == 'dark' ? 'dark' : 'light';
}
$theme = $_SESSION['theme'] ?? 'light';

// Aktif rota/sayfa tayini
$route = isset($_GET["route"]) ? trim($_GET["route"], "/") : "";

/*
 * SUPERADMIN MOBİL MENÜ / SAYFA İZİNLERİ
 * Bir sayfayı superadmin mobil alanına eklemek veya buradan çıkarmak için yalnızca
 * aşağıdaki listeyi güncelleyin. Listede olmayan rotalar menüde gösterilmez ve
 * adres çubuğundan doğrudan çağrılsa bile sunucu tarafından engellenir.
 */
$superadmin_mobile_routes = [
    '', 'home', 'dashboard',
    'tickets', 'ticket-view',
    'abonelik-islemleri',
    'notifications', 'announcements', 'duyurular',
    'settings', 'profile', 'more',
];

if ($is_superadmin && !in_array($route, $superadmin_mobile_routes, true)) {
    header('Location: dashboard');
    exit();
}

// Temiz rotaları modüler yapıya eşleştir
switch ($route) {
    case '':
    case 'home':
    case 'dashboard':
        $title = $is_superadmin ? "Sistem Yönetimi" : "Puantaj Takip";
        $page_file = $is_superadmin ? "modules/superadmin/dashboard.php" : "modules/dashboard/index.php";
        $active_page = "home";
        break;
    case 'persons':
        $persons_auth = $Auths->getAuthIdByTitle("Personeller");
        if ($persons_auth && !$Auths->AuthorizeByAuthId($persons_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Personeller";
        $page_file = "modules/persons/index.php";
        $active_page = "persons";
        break;
    case 'person-add':
    case 'person-edit':
        $persons_auth = $Auths->getAuthIdByTitle("Personeller");
        if ($persons_auth && !$Auths->AuthorizeByAuthId($persons_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = ($route == 'person-add') ? "Yeni Personel Ekle" : "Personel Düzenle";
        $page_file = ($route == 'person-add') ? "modules/persons/add.php" : "modules/persons/edit.php";
        $active_page = "persons";
        break;
    case 'puantaj':
        $puantaj_auth = $Auths->getAuthIdByTitle("Puantaj");
        if ($puantaj_auth && !$Auths->AuthorizeByAuthId($puantaj_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Hızlı Puantaj";
        $page_file = "modules/puantaj/index.php";
        $active_page = "puantaj";
        break;
    case 'puantaj-detail':
        $puantaj_auth = $Auths->getAuthIdByTitle("Puantaj");
        if ($puantaj_auth && !$Auths->AuthorizeByAuthId($puantaj_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Aylık Puantaj";
        $page_file = "modules/puantaj/detail.php";
        $active_page = "puantaj";
        break;
    case 'projects':
        $projects_auth = $Auths->getAuthIdByTitle("Projeler");
        if ($projects_auth && !$Auths->AuthorizeByAuthId($projects_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Projeler";
        $page_file = "modules/projects/index.php";
        $active_page = "more";
        break;
    case 'project-manage':
        $projects_auth = $Auths->getAuthIdByTitle("Projeler");
        if ($projects_auth && !$Auths->AuthorizeByAuthId($projects_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Proje Detay / Güncelle";
        $page_file = "modules/projects/manage.php";
        $active_page = "more";
        break;
    case 'finance':
        $finance_auth = $Auths->getAuthIdByTitle("Finans");
        if ($finance_auth && !$Auths->AuthorizeByAuthId($finance_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Kasa & Finans";
        $page_file = "modules/finance/index.php";
        $active_page = "more";
        break;
    case 'payroll':
        $payroll_auth = $Auths->getAuthIdByTitle("Bordro");
        if ($payroll_auth && !$Auths->AuthorizeByAuthId($payroll_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Bordrolar";
        $page_file = "modules/payroll/index.php";
        $active_page = "more";
        break;
    case 'todos':
        $todos_auth = $Auths->getAuthIdByTitle("Yapılacaklar");
        if ($todos_auth && !$Auths->AuthorizeByAuthId($todos_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Yapılacaklar";
        $page_file = "modules/todos/index.php";
        $active_page = "more";
        break;
    case 'person-puantaj':
        $puantaj_auth = $Auths->getAuthIdByTitle("Puantaj");
        if ($puantaj_auth && !$Auths->AuthorizeByAuthId($puantaj_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Personel Puantajı";
        $page_file = "modules/puantaj/detail.php";
        $active_page = "persons";
        break;
    case 'person-finance':
        $finance_auth = $Auths->getAuthIdByTitle("Finans");
        if ($finance_auth && !$Auths->AuthorizeByAuthId($finance_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Personel Ödemeleri";
        $page_file = "modules/finance/index.php";
        $active_page = "persons";
        break;
    case 'person-documents':
        $title = "Personel Evrakları";
        $page_file = "modules/persons/documents.php";
        $active_page = "persons";
        break;
    case 'tickets':
        $is_superadmin = ($user->superadmin ?? 0) == 1;
        $title = $is_superadmin ? "Destek Yönetimi" : "Teknik Destek";
        $page_file = $is_superadmin ? "modules/supports/admin-tickets.php" : "modules/supports/tickets.php";
        $active_page = "more";
        break;
    case 'ticket-view':
        $is_superadmin = ($user->superadmin ?? 0) == 1;
        $title = "Destek Talebi";
        $page_file = $is_superadmin ? "modules/supports/admin-ticket-view.php" : "modules/supports/ticket-view.php";
        $active_page = "more";
        break;
    case 'settings':
        $settings_auth = $Auths->getAuthIdByTitle("Ayarlar");
        if ($settings_auth && !$Auths->AuthorizeByAuthId($settings_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Ayarlar";
        $page_file = "modules/settings/index.php";
        $active_page = "more";
        break;
    case 'profile':
        $title = "Profil Ayarları";
        $page_file = "modules/settings/index.php";
        $active_page = "more";
        break;
    case 'more':
        $title = "Daha Fazla";
        $page_file = "modules/more/index.php";
        $active_page = "more";
        break;
    case 'notifications':
        $title = "Bildirimler";
        $page_file = "modules/more/notifications.php";
        $active_page = "more";
        break;
    case 'announcements':
    case 'duyurular':
        $title = "Duyurular";
        $page_file = "modules/more/announcements.php";
        $active_page = "more";
        break;
    case 'advance-requests':
        $advance_auth = $Auths->getAuthIdByTitle("Avans Talepleri");
        if ($advance_auth && !$Auths->AuthorizeByAuthId($advance_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Avans Talepleri";
        $page_file = "modules/more/advance_requests.php";
        $active_page = "more";
        break;
    case 'leave-requests':
    case 'izin-talepleri':
        $izin_auth = $Auths->getAuthIdByTitle("İzin Talepleri");
        if ($izin_auth && !$Auths->AuthorizeByAuthId($izin_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "İzin Talepleri";
        $page_file = "modules/more/leave_requests.php";
        $active_page = "more";
        break;
    case 'cari':
        if (!$Auths->hasPermission("cari_takip")) {
            header("Location: dashboard");
            exit();
        }
        $title = "Cari Takip";
        $page_file = "modules/cari/index.php";
        $active_page = "more";
        break;
    case 'cari-movements':
        if (!$Auths->hasPermission("cari_hareketleri")) {
            header("Location: dashboard");
            exit();
        }
        $title = "Cari Hareketleri";
        $page_file = "modules/cari/movements.php";
        $active_page = "more";
        break;
    case 'abonelik-islemleri':
        if (!$Auths->hasPermission("aboneler_sayfasi") && !$Auths->hasPermission("aboneler_paketleri") && !$Auths->hasPermission("abonelik_satin_alimlari")) {
            header("Location: dashboard");
            exit();
        }
        $title = "Abonelik İşlemleri";
        $page_file = "modules/abonelik-islemleri/index.php";
        $active_page = "more";
        break;
    case 'reports':
    case 'raporlar':
        $reports_auth = $Auths->getAuthIdByTitle("Raporlar");
        if ($reports_auth && !$Auths->AuthorizeByAuthId($reports_auth->id)) {
            header("Location: dashboard");
            exit();
        }
        $title = "Raporlar";
        $page_file = "modules/reports/index.php";
        $active_page = "more";
        break;
    default:
        $title = $is_superadmin ? "Sistem Yönetimi" : "Puantaj Takip";
        $page_file = $is_superadmin ? "modules/superadmin/dashboard.php" : "modules/dashboard/index.php";
        $active_page = "home";
        break;
}

// Başlık şablonunu yükleme
include_once __DIR__ . "/inc/head.php";
?>

<body class="layout-fluid" data-bs-theme="<?php echo $theme; ?>">
    <div class="app-shell">
        
        <!-- Üst Başlık (Header) -->
        <?php include_once __DIR__ . "/inc/header.php"; ?>

        <!-- Dinamik Alt Sayfa İçeriği -->
        <main class="app-content">
            <?php 
            $page_file_path = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $page_file);
            if (file_exists($page_file_path)) {
                include_once $page_file_path;
            } else {
                echo "<div class='alert alert-warning'>Modül sayfası bulunamadı: " . htmlspecialchars($page_file_path) . "</div>";
            }
            ?>
        </main>

        <!-- Alt Sabit Menü (Bottom Navigation) -->
        <?php include_once __DIR__ . "/inc/bottom-nav.php"; ?>

    </div>

    <!-- Bootstrap 5 / Tabler JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.4.0/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/tr.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="../dist/js/pull-to-refresh.js?v=<?php echo filemtime(ROOT . '/dist/js/pull-to-refresh.js'); ?>"></script>
    <script>
        jQuery(document).ready(function($) {
            // Global fix for Bootstrap modals in PWA app-shell: append modal to body when showing to prevent backdrop z-index overlay
            $(document).on('show.bs.modal', '.modal', function() {
                if (!$(this).parent().is('body')) {
                    $(this).appendTo('body');
                }
            });

            // Global Flatpickr initialization for mobile
            const initFlatpickr = () => {
                if (typeof flatpickr !== 'undefined') {
                    flatpickr('input[type="date"], .flatpickr', {
                        dateFormat: "d.m.Y",
                        locale: "tr",
                        disableMobile: "true",
                        animate: true,
                        static: false,
                        onOpen: function(selectedDates, dateStr, instance) {
                            // Ensure floating label doesn't overlap on open
                            $(instance.element).closest('.form-floating').addClass('has-value');
                        }
                    });
                }
            };

            // Global Select2 initialization for mobile
            if ($.fn && $.fn.select2) {
                $('.select2-init').select2();
            }

            // Global Swipe Handler
            let touchStartX = 0;
            let touchEndX = 0;

            const initSwipe = () => {
                document.querySelectorAll('.swipe-container').forEach(container => {
                    container.removeEventListener('touchstart', handleStart);
                    container.removeEventListener('touchend', handleEnd);
                    container.addEventListener('touchstart', handleStart, { passive: true });
                    container.addEventListener('touchend', handleEnd, { passive: true });
                });
            };

            function handleStart(e) {
                touchStartX = e.touches[0].clientX;
                document.querySelectorAll('.swipe-container.swiped').forEach(el => {
                    if (el !== this) el.classList.remove('swiped');
                });
            }

            function handleEnd(e) {
                touchEndX = e.changedTouches[0].clientX;
                const distance = touchStartX - touchEndX;
                if (distance > 60) this.classList.add('swiped'); // Swipe Left
                else if (distance < -60) this.classList.remove('swiped'); // Swipe Right
            }

            initFlatpickr();
            initSwipe();
            
            // Re-init on dynamic content changes
            window.reInitMobileUI = () => {
                initFlatpickr();
                initSwipe();
            };
        });
    </script>
</body>
</html>
