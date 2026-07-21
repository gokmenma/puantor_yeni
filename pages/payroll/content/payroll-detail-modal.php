<div class="modal modal-blur fade" id="payroll-detail-modal" tabindex="-1" role="dialog" aria-labelledby="payroll-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down" role="document">
        <div class="modal-content payroll-detail-modal-content">
            <div class="modal-header px-3 px-md-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="avatar bg-primary-lt text-primary">
                        <i class="ti ti-file-invoice fs-2"></i>
                    </span>
                    <div>
                        <h5 class="modal-title" id="payroll-detail-title">Bordro Detayı</h5>
                        <div class="text-muted small" id="payroll-detail-period">Gelir, kesinti ve puantaj dökümü</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="payroll-detail-content" aria-live="polite">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted small mt-3">Bordro detayları hazırlanıyor...</div>
                </div>
            </div>
            <div class="modal-footer px-3 px-md-4">
                <button type="button" class="btn btn-ghost-secondary me-auto" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" id="print-detailed-payroll">
                    <i class="ti ti-printer icon me-2"></i> Detayı Yazdır
                </button>
            </div>
        </div>
    </div>
</div>
