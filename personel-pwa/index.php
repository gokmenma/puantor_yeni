<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Auth check
if (!isset($_SESSION['personel_id'])) {
    header("Location: login.php");
    exit;
}

$route = $_GET['route'] ?? 'dashboard';
$user = $_SESSION['personel_user'];

// Route to file mapping
$routes = [
    'dashboard' => [
        'title' => 'Anasayfa',
        'file' => 'modules/dashboard/index.php',
        'icon' => 'ti ti-smart-home'
    ],
    'attendance' => [
        'title' => 'Takvim',
        'file' => 'modules/attendance/index.php',
        'icon' => 'ti ti-calendar-event'
    ],
    'advance' => [
        'title' => 'Avans Talepleri',
        'file' => 'modules/advance/index.php',
        'icon' => 'ti ti-wallet'
    ],
    'profile' => [
        'title' => 'Profil',
        'file' => 'modules/profile/index.php',
        'icon' => 'ti ti-user'
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
            if ('<?php echo $route; ?>' === 'advance') app.loadAdvances();
            if ('<?php echo $route; ?>' === 'attendance') app.loadAttendance();
        });
    </script>
</body>
</html>
