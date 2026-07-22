<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../Database/require.php";
require_once __DIR__ . "/../Model/Persons.php";
require_once __DIR__ . "/../App/Helper/security.php";
use App\Helper\Security;

// Beni Hatırla kontrolü (PWA için)
if (!isset($_SESSION['personel_id']) && isset($_COOKIE['personel_remember_me'])) {
    $token = $_COOKIE['personel_remember_me'];
    $PersonsModel = new Persons();
    $cookie_person = $PersonsModel->getPersonBySessionToken($token);
    if ($cookie_person && empty($cookie_person->job_end_date)) {
        $_SESSION['personel_user'] = $cookie_person;
        $_SESSION['personel_id'] = $cookie_person->id;
        $_SESSION['firm_id'] = $cookie_person->firm_id;
        
        // Refresh cookie expiry
        setcookie('personel_remember_me', $token, time() + 30 * 24 * 3600, '/');
    }
}

// Auth check
if (!isset($_SESSION['personel_id'])) {
    header("Location: login.php");
    exit;
}

$route = $_GET['route'] ?? 'dashboard';
$user = $_SESSION['personel_user'];

// Ensure data is decrypted (fallback for legacy sessions)
if (isset($user->phone)) $user->phone = Security::safeDecrypt($user->phone);
if (isset($user->email)) $user->email = Security::safeDecrypt($user->email);
if (isset($user->iban_number)) $user->iban_number = Security::safeDecrypt($user->iban_number);

// Load settings
require_once __DIR__ . "/../Model/SettingsModel.php";
$Settings = new SettingsModel();
$personnel_advance_request_visible = $Settings->getSettings("personnel_advance_request_visible")->set_value ?? 1;

// Route to file mapping
$routes = [
    'dashboard' => [
        'title' => 'Anasayfa',
        'file' => 'modules/dashboard/index.php',
        'icon' => 'ti ti-smart-home'
    ],
    'attendance' => [
        'title' => 'Puantaj',
        'file' => 'modules/attendance/index.php',
        'icon' => 'ti ti-calendar-event'
    ],
    'advance' => [
        'title' => 'Bordro',
        'file' => 'modules/advance/index.php',
        'icon' => 'ti ti-file-invoice'
    ],
    'payroll' => [
        'title' => 'Bordro',
        'file' => 'modules/advance/index.php',
        'icon' => 'ti ti-file-invoice'
    ],
    'leave' => [
        'title' => 'Yıllık İzin',
        'file' => 'modules/leave/index.php',
        'icon' => 'ti ti-beach'
    ],
    'profile' => [
        'title' => 'Profilim',
        'file' => 'modules/profile/index.php',
        'icon' => 'ti ti-user'
    ],
    'more' => [
        'title' => 'Diğer İşlemler',
        'file' => 'modules/more/index.php',
        'icon' => 'ti ti-menu-2'
    ],
    'icra' => [
        'title' => 'İcra Kesintilerim',
        'file' => 'modules/icra/index.php',
        'icon' => 'ti ti-folder'
    ]
];

if (!isset($routes[$route])) {
    $route = 'dashboard';
}

$current_route = $routes[$route];
$title = $current_route['title'];
?>
<!DOCTYPE html>
<html lang="tr">
<?php include_once "inc/head.php"; ?>
<body>

    <div id="main-content" class="app-shell">
        <!-- Header -->
        <?php include_once "inc/header.php"; ?>

        <!-- Content -->
        <main class="app-content">
            <?php 
            if (file_exists($current_route['file'])) {
                include_once $current_route['file'];
            } else {
                echo "<div class='alert alert-danger'>Modül bulunamadı.</div>";
            }
            ?>
        </main>

        <!-- Bottom Navigation -->
        <?php include_once "inc/bottom-nav.php"; ?>
    </div>

    <!-- Bildirim Overlay & Sheet -->
    <div id="notif-overlay" class="notif-overlay" onclick="app.closeNotificationSheet()"></div>
    <div id="notif-sheet" class="notif-sheet">
        <div class="notif-sheet-header">
            <span class="fw-bold fs-4">Bildirimler</span>
            <div class="d-flex align-items-center gap-1">
                <button class="btn btn-sm btn-ghost-secondary" onclick="app.markAllNotificationsRead()">Tümünü okundu</button>
                <button class="btn btn-sm btn-icon btn-ghost-secondary" onclick="app.closeNotificationSheet()">
                    <i class="ti ti-x fs-3"></i>
                </button>
            </div>
        </div>
        <div id="notif-list" class="notif-list">
            <div class="notif-empty">Yükleniyor...</div>
        </div>
    </div>

    <!-- Global Modal Shell -->
    <div class="modal modal-blur fade modal-bottom-sheet" id="app-modal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="app-modal-title">Başlık</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="app-modal-body">
                    <!-- Dynamic -->
                </div>
            </div>
        </div>
    </div>

    <?php include_once "inc/scripts.php"; ?>
    
    <script>
        // Sync JS app state with PHP session data
        window.app.user = <?php echo json_encode($user); ?>;
        
        // Update header based on route
        document.getElementById('header-icon').className = '<?php echo $current_route['icon']; ?>';
        document.getElementById('page-title').textContent = '<?php echo $current_route['title']; ?>';
        
        // Initializations
        document.addEventListener('DOMContentLoaded', () => {
            app.updateProfileUI();
            if ('<?php echo $route; ?>' === 'dashboard') app.loadSummary();
            if (['advance', 'payroll'].includes('<?php echo $route; ?>')) app.loadFinanceHub();
            if ('<?php echo $route; ?>' === 'attendance') app.loadAttendance();
            // leave modülü kendi içinde başlatılır
        });
    </script>
</body>
</html>
