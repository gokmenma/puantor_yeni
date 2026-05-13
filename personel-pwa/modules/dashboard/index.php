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
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div class="avatar avatar-md rounded bg-white-20 text-white">
                <i class="ti ti-clock"></i>
            </div>
            <span class="badge bg-white-20 text-white" style="backdrop-filter: blur(4px);">BU AY</span>
        </div>
        <p class="text-white-80 small text-uppercase mb-1" style="font-weight: 600;">TOPLAM ÇALIŞMA SÜRESİ</p>
        <div class="d-flex align-items-baseline gap-2 mb-2">
            <h2 id="total-hours" class="h1 mb-0 text-white" style="font-size: 2.5rem; font-weight: 800;">0</h2>
            <span class="text-white-80">saat</span>
        </div>
        <div class="progress-premium">
            <div class="progress-premium-bar" style="width: 65%;"></div>
        </div>
        <div class="d-flex justify-content-between mt-2 small text-white-80">
            <span>Aylık Hedef: 180s</span>
            <span>%65</span>
        </div>
    </div>

    <!-- Quick Actions Grid -->
    <div class="quick-actions-grid">
        <a href="?route=advance" class="quick-action-card">
            <i class="ti ti-wallet"></i>
            <span>Avans İste</span>
        </a>
        <a href="?route=attendance" class="quick-action-card">
            <i class="ti ti-calendar-event"></i>
            <span>Takvim</span>
        </a>
        <a href="?route=profile" class="quick-action-card">
            <i class="ti ti-file-text"></i>
            <span>Belgeler</span>
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6">
            <div class="mobile-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-sm rounded bg-blue-lt text-blue">
                        <i class="ti ti-timer"></i>
                    </div>
                    <div>
                        <p class="text-muted small text-uppercase mb-0" style="font-size: 0.65rem;">Fazla Mesai</p>
                        <h3 id="dashboard-overtime" class="mb-0" style="font-size: 1.1rem; font-weight: 700;">0 s</h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="mobile-card">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-sm rounded bg-green-lt text-green">
                        <i class="ti ti-plane-departure"></i>
                    </div>
                    <div>
                        <p class="text-muted small text-uppercase mb-0" style="font-size: 0.65rem;">Kalan İzin</p>
                        <h3 id="dashboard-leave-days" class="mb-0" style="font-size: 1.1rem; font-weight: 700;">12 G</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="h4 mb-0">Son Aktiviteler</h3>
        <a href="?route=attendance" class="text-primary small fw-bold">TÜMÜ</a>
    </div>
    <div id="recent-activity-list" class="space-y-2">
        <!-- Dynamic -->
    </div>
</div>
