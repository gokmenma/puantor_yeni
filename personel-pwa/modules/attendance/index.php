<div id="attendance-tab" class="tab-content active">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h2 mb-0">Puantaj Takvimi</h2>
            <p id="current-month-label" class="text-muted small">Yükleniyor...</p>
        </div>
        <div class="d-flex gap-2">
            <button onclick="app.changeMonth(-1)" class="btn btn-icon btn-light rounded-circle">
                <i class="ti ti-chevron-left"></i>
            </button>
            <button onclick="app.changeMonth(1)" class="btn btn-icon btn-light rounded-circle">
                <i class="ti ti-chevron-right"></i>
            </button>
        </div>
    </div>

    <div class="mobile-card p-4">
        <div class="calendar-grid mb-4" id="calendar-grid">
            <!-- Dynamic -->
        </div>
        <div class="d-flex justify-content-center gap-4 border-top pt-3">
            <div class="d-flex align-items-center gap-2">
                <span class="status-dot status-primary"></span>
                <span class="small text-muted">Çalışma</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="status-dot status-danger"></span>
                <span class="small text-muted">Tatil</span>
            </div>
        </div>
    </div>

    <div id="day-details" class="mb-4">
        <h3 id="selected-day-label" class="h4 mb-3">Seçili Gün Detayı</h3>
        <div class="mobile-card d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div id="day-icon-bg" class="avatar avatar-md rounded bg-primary-lt text-primary">
                    <i id="day-icon" class="ti ti-briefcase"></i>
                </div>
                <div>
                    <h4 id="day-status" class="mb-0">Normal Çalışma</h4>
                    <p class="text-muted small mb-0">Günlük Durum</p>
                </div>
            </div>
            <div class="text-end">
                <h3 id="day-duration-new" class="mb-0 text-primary">8s</h3>
                <p class="text-muted small mb-0">Süre</p>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-4">
            <div class="mobile-card text-center p-2">
                <span class="text-muted small d-block mb-1">Çalışma</span>
                <h4 id="summary-work-days" class="mb-0">0 Gün</h4>
            </div>
        </div>
        <div class="col-4">
            <div class="mobile-card text-center p-2">
                <span class="text-muted small d-block mb-1">Tatil</span>
                <h4 id="summary-holidays" class="mb-0">0 Gün</h4>
            </div>
        </div>
        <div class="col-4">
            <div class="mobile-card text-center p-2">
                <span class="text-muted small d-block mb-1">Toplam</span>
                <h4 id="summary-total-hours" class="mb-0">0 s</h4>
            </div>
        </div>
    </div>
</div>
