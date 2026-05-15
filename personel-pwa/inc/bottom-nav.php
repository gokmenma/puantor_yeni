<nav class="app-nav">
    <a href="?route=dashboard" class="nav-item <?php echo $route == 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard-tab">
        <i class="ti ti-smart-home"></i>
        <span>Anasayfa</span>
    </a>
    <a href="?route=attendance" class="nav-item <?php echo $route == 'attendance' ? 'active' : ''; ?>" data-tab="attendance-tab">
        <i class="ti ti-calendar-event"></i>
        <span>Takvim</span>
    </a>
    <?php if ($personnel_advance_request_visible == 1): ?>
    <a href="?route=advance" class="nav-item <?php echo $route == 'advance' ? 'active' : ''; ?>" data-tab="advance-tab">
        <i class="ti ti-wallet"></i>
        <span>Avans</span>
    </a>
    <?php endif; ?>
    <a href="?route=profile" class="nav-item <?php echo $route == 'profile' ? 'active' : ''; ?>" data-tab="profile-tab">
        <i class="ti ti-user"></i>
        <span>Profil</span>
    </a>
</nav>
