<div id="dashboard-tab" class="tab-content active">
    <div class="mb-4 d-flex justify-content-between align-items-end">
        <div>
            <p class="text-muted small text-uppercase mb-0" style="letter-spacing: 1px;">TEKRAR HOŞ GELDİN,</p>
            <h1 id="user-display-name" class="h2 mb-0" style="font-weight: 800;">Yükleniyor...</h1>
        </div>
        <div class="avatar avatar-md rounded-circle bg-primary-lt text-primary fw-bold" id="dashboard-user-avatar">
            ??
        </div>
    </div>

    <div class="summary-card">
        
        <div class="row g-2 align-items-center mb-3">
            <div class="col-6" style="border-right: 1px solid rgba(255, 255, 255, 0.15);">
                <p class="text-white-80 small text-uppercase mb-1" style="font-weight: 600; font-size: 0.7rem; letter-spacing: 0.5px;">TOPLAM ÇALIŞILAN GÜN</p>
                <div class="d-flex align-items-baseline gap-1">
                    <h2 id="total-days" class="h1 mb-0 text-white" style="font-size: 2.2rem; font-weight: 800;">0</h2>
                    <span class="text-white-80 small">gün</span>
                </div>
            </div>
            <div class="col-6" style="padding-left: 1.25rem;">
                <p class="text-white-80 small text-uppercase mb-1" style="font-weight: 600; font-size: 0.7rem; letter-spacing: 0.5px;">TOPLAM FAZLA MESAİ</p>
                <div class="d-flex align-items-baseline gap-1">
                    <h2 id="dashboard-overtime" class="h1 mb-0 text-white" style="font-size: 2.2rem; font-weight: 800;">0</h2>
                    <span class="text-white-80 small">saat</span>
                </div>
            </div>
        </div>

        <div class="progress-premium">
            <div class="progress-premium-bar" style="width: 0%;"></div>
        </div>
        <div class="d-flex justify-content-between mt-2 small text-white-80">
            <span>Aylık Hedef: 26 Gün</span>
            <span id="dashboard-progress-percent">%0</span>
        </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="quick-actions-grid">
        <a href="?route=payroll" class="quick-action-card">
            <i class="ti ti-file-invoice"></i>
            <span>Bordrolarım</span>
        </a>
        <a href="?route=attendance" class="quick-action-card">
            <i class="ti ti-calendar-event"></i>
            <span>Puantaj</span>
        </a>
        <a href="?route=profile" class="quick-action-card">
            <i class="ti ti-file-text"></i>
            <span>Belgeler</span>
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h4 mb-0">Son Aktiviteler</h3>
        <a href="?route=attendance" class="text-primary small fw-bold">TÜMÜ</a>
    </div>
    <div id="recent-activity-list" class="space-y-2">
        <!-- Dynamic -->
    </div>
</div>
