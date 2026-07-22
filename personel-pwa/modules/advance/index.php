<div id="payroll-tab" class="tab-content active">
    <div class="page-header mb-3">
        <h2 class="h1 mb-0 fw-bold text-dark payroll-page-title">Bordrolarım</h2>
        <p class="text-muted small mb-0">Kesinleşen bordrolarınız ve avans işlemleriniz.</p>
    </div>

    <div class="finance-switch mb-4" role="tablist" aria-label="Bordro ve avans bölümleri">
        <button type="button" class="finance-switch-button active" data-finance-view="payroll" role="tab" aria-selected="true">
            <i class="ti ti-file-invoice"></i>
            Bordrolarım
        </button>
        <?php if ($personnel_advance_request_visible == 1): ?>
        <button type="button" class="finance-switch-button" data-finance-view="advance" role="tab" aria-selected="false">
            <i class="ti ti-wallet"></i>
            Avans
            <span id="advance-count-badge" class="finance-count-badge">0</span>
        </button>
        <?php endif; ?>
    </div>

    <section id="payroll-view" class="finance-view active">
        <div id="payroll-highlight" class="payroll-highlight mb-4">
            <div class="d-flex justify-content-between align-items-start position-relative">
                <div>
                    <p class="payroll-highlight-label mb-1">SON KESİNLEŞEN BORDRO</p>
                    <p id="latest-payroll-period" class="mb-3 fw-semibold text-white-80">Yükleniyor...</p>
                </div>
                <span class="payroll-final-badge"><i class="ti ti-lock-check"></i> Kesinleşti</span>
            </div>
            <p class="text-white-80 small mb-1">Net ödenecek</p>
            <div class="d-flex align-items-baseline gap-1">
                <span class="fs-2 fw-bold opacity-75">₺</span>
                <span id="latest-payroll-net" class="payroll-highlight-amount">—</span>
            </div>
            <div id="latest-payroll-meta" class="payroll-highlight-meta mt-3"></div>
            <i class="ti ti-file-invoice payroll-highlight-icon"></i>
        </div>

        <?php if ($personnel_advance_request_visible == 1): ?>
        <button type="button" class="advance-shortcut mb-4" data-open-advance>
            <span class="advance-shortcut-icon"><i class="ti ti-wallet"></i></span>
            <span class="text-start flex-fill">
                <strong class="d-block text-dark">Avans işlemleri</strong>
                <small class="text-muted">Talep oluşturun veya durumunu takip edin</small>
            </span>
            <i class="ti ti-chevron-right text-primary fs-2"></i>
        </button>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h3 mb-0 fw-bold text-dark">Geçmiş Bordrolar</h3>
                <p class="text-muted extra-small mb-0">Yalnızca kesinleşen dönemler gösterilir.</p>
            </div>
            <i class="ti ti-shield-check text-success fs-1"></i>
        </div>

        <div id="payroll-list" class="payroll-list mb-5"></div>
    </section>

    <?php if ($personnel_advance_request_visible == 1): ?>
    <section id="advance-view" class="finance-view">
        <div class="advance-summary-card mb-4">
            <div class="d-flex justify-content-between align-items-start position-relative">
                <div>
                    <p class="extra-small text-uppercase mb-2 fw-bold text-primary">KULLANILABİLİR AVANS LİMİTİ</p>
                    <div class="d-flex align-items-baseline gap-1 text-dark">
                        <span class="fs-2 fw-bold text-primary">₺</span>
                        <h2 id="available-advance-limit-large" class="display-5 mb-0 fw-bold">0,00</h2>
                    </div>
                </div>
                <span class="advance-summary-icon"><i class="ti ti-wallet"></i></span>
            </div>
            <button id="btn-new-advance" type="button" class="btn btn-primary w-100 mt-4 py-3 fw-bold rounded-3">
                <i class="ti ti-plus me-2"></i> Yeni Avans Talebi
            </button>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h3 class="h3 mb-0 fw-bold text-dark">Avans Taleplerim</h3>
                <p class="text-muted extra-small mb-0">Taleplerinizi buradan takip edebilirsiniz.</p>
            </div>
        </div>

        <div class="mobile-card p-0 border-0 shadow-sm overflow-hidden mb-5 advance-list-card">
            <div id="advance-list" class="divide-y"></div>
        </div>
    </section>
    <?php endif; ?>
</div>
