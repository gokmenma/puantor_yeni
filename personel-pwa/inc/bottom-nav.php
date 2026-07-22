<nav class="app-nav">
    <a href="?route=dashboard" class="nav-item <?php echo $route == 'dashboard' ? 'active' : ''; ?>" data-tab="dashboard-tab">
        <i class="ti ti-smart-home"></i>
        <span>Anasayfa</span>
    </a>
    <a href="?route=attendance" class="nav-item <?php echo $route == 'attendance' ? 'active' : ''; ?>" data-tab="attendance-tab">
        <i class="ti ti-calendar-event"></i>
        <span>Puantaj</span>
    </a>
    <a href="?route=payroll" class="nav-item <?php echo in_array($route, ['advance', 'payroll']) ? 'active' : ''; ?>" data-tab="payroll-tab">
        <i class="ti ti-file-invoice"></i>
        <span>Bordro</span>
    </a>
    <a href="?route=leave" class="nav-item <?php echo $route == 'leave' ? 'active' : ''; ?>" data-tab="leave-tab">
        <i class="ti ti-beach"></i>
        <span>İzin</span>
    </a>
    <a href="?route=more" class="nav-item <?php echo in_array($route, ['more', 'profile', 'icra']) ? 'active' : ''; ?>" data-tab="more-tab">
        <i class="ti ti-menu-2"></i>
        <span>Diğer</span>
    </a>
</nav>
