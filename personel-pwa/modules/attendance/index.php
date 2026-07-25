<div id="attendance-tab" class="tab-content active">
    <section class="attendance-calendar-card" aria-labelledby="attendance-person-name">
        <div class="attendance-calendar-header">
            <div class="min-w-0">
                <h2 id="attendance-person-name" class="attendance-person-name">
                    <?php echo htmlspecialchars($user->full_name ?? 'Personel', ENT_QUOTES, 'UTF-8'); ?>
                </h2>
                <p id="current-month-label" class="attendance-period-label">Yükleniyor...</p>
            </div>
            <div class="attendance-month-actions" aria-label="Ay seçimi">
                <button type="button" onclick="app.changeMonth(-1)" class="attendance-month-button" aria-label="Önceki ay">
                    <i class="ti ti-chevron-left"></i>
                </button>
                <button type="button" onclick="app.changeMonth(1)" class="attendance-month-button" aria-label="Sonraki ay">
                    <i class="ti ti-chevron-right"></i>
                </button>
            </div>
        </div>

        <div class="attendance-calendar-body">
            <div class="calendar-grid" id="calendar-grid" aria-label="Aylık puantaj takvimi">
            <!-- Dynamic -->
            </div>
        </div>

        <div class="attendance-calendar-footer">
            <span id="attendance-calendar-hint">Gün detayını görmek için güne dokunun.</span>
        </div>
    </section>

    <div id="day-details" class="mb-4 mt-4">
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
                <span class="text-muted small d-block mb-1">Fazla Mesai</span>
                <h4 id="summary-overtime" class="mb-0">0 s</h4>
            </div>
        </div>
    </div>
</div>
