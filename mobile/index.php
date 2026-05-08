<?php
// Puantor Premium Mobil Giriş ve Kabuk (App Shell) Dosyası
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

define("ROOT", dirname(__DIR__));
date_default_timezone_set('Europe/Istanbul');

require_once __DIR__ . "/../Database/db.php";
require_once __DIR__ . "/../Model/UserModel.php";
require_once __DIR__ . "/../Model/MyFirmModel.php";

$User = new UserModel();

// Oturum kontrolü
if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
    if (isset($_COOKIE['remember_me'])) {
        $token = $_COOKIE['remember_me'];
        $cookie_user = $User->getUserBySessionToken($token);
        if ($cookie_user && $cookie_user->status == 1) {
            $_SESSION['user'] = $cookie_user;
            $_SESSION['firm_id'] = $cookie_user->firm_id;
        } else {
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

// Kullanıcının yetkili firmalarını çek
$myFirmObj = new MyFirmModel();
$myFirms = $myFirmObj->getMyFirmByUserId();

// Post ile firma seçildiyse ve kullanıcının bu firmaya yetkisi varsa session'a yaz
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'select_firm') {
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
$has_active_access = false;
if (isset($_SESSION['firm_id']) && !empty($_SESSION['firm_id'])) {
    foreach ($myFirms as $firm) {
        if ($firm->id == $_SESSION['firm_id']) {
            $has_active_access = true;
            break;
        }
    }
}

if (!$has_active_access) {
    if (!empty($myFirms)) {
        $_SESSION['firm_id'] = $myFirms[0]->id;
    } else {
        $_SESSION['firm_id'] = $_SESSION['user']->firm_id ?? 0;
    }
}



// Tema ayarları
if (isset($_GET['theme'])) {
    $_SESSION['theme'] = $_GET['theme'] == 'dark' ? 'dark' : 'light';
}
$theme = $_SESSION['theme'] ?? 'light';

// Aktif rota/sayfa tayini
$route = isset($_GET["route"]) ? trim($_GET["route"], "/") : "";

// Temiz rotaları modüler yapıya eşleştir
switch ($route) {
    case '':
    case 'home':
    case 'dashboard':
        $title = "Puantaj Takip";
        $page_file = "modules/dashboard/index.php";
        $active_page = "home";
        break;
    case 'persons':
        $title = "Personeller";
        $page_file = "modules/persons/index.php";
        $active_page = "persons";
        break;
    case 'person-add':
        $title = "Yeni Personel Ekle";
        $page_file = "modules/persons/add.php";
        $active_page = "persons";
        break;
    case 'person-edit':
        $title = "Personel Düzenle";
        $page_file = "modules/persons/edit.php";
        $active_page = "persons";
        break;
    case 'puantaj':
        $title = "Hızlı Puantaj";
        $page_file = "modules/puantaj/index.php";
        $active_page = "puantaj";
        break;
    case 'puantaj-detail':
        $title = "Aylık Puantaj";
        $page_file = "modules/puantaj/detail.php";
        $active_page = "puantaj";
        break;
    case 'projects':
        $title = "Projeler";
        $page_file = "modules/projects/index.php";
        $active_page = "more";
        break;
    case 'project-manage':
        $title = "Proje Detay / Güncelle";
        $page_file = "modules/projects/manage.php";
        $active_page = "more";
        break;
    case 'finance':
        $title = "Kasa & Finans";
        $page_file = "modules/finance/index.php";
        $active_page = "more";
        break;
    case 'payroll':
        $title = "Bordrolar";
        $page_file = "modules/payroll/index.php";
        $active_page = "more";
        break;
    case 'todos':
        $title = "Yapılacaklar";
        $page_file = "modules/todos/index.php";
        $active_page = "more";
        break;
    case 'person-puantaj':
        $title = "Personel Puantajı";
        $page_file = "modules/puantaj/detail.php";
        $active_page = "persons";
        break;
    case 'person-finance':
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
        $title = "Teknik Destek";
        $page_file = "modules/supports/tickets.php";
        $active_page = "more";
        break;
    case 'ticket-view':
        $title = "Destek Talebi";
        $page_file = "modules/supports/ticket-view.php";
        $active_page = "more";
        break;
    case 'more':
        $title = "Daha Fazla";
        $page_file = "modules/more/index.php";
        $active_page = "more";
        break;
    default:
        $title = "Puantaj Takip";
        $page_file = "modules/dashboard/index.php";
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
    <script>
        jQuery(document).ready(function($) {
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
