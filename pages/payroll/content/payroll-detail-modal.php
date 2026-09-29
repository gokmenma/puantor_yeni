<div class="modal modal-blur fade" id="payroll-detail-modal" tabindex="-1" role="dialog" aria-labelledby="payroll-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 16px;">
            <div class="modal-header bg-light py-3 px-3 px-md-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 42px; height: 42px;">
                        <i class="ti ti-file-invoice" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="payroll-detail-title" style="font-size: 1.1rem; letter-spacing: -0.2px;">Bordro Detayı</h5>
                        <div class="text-secondary small mt-0.5" id="payroll-detail-period" style="font-size: 12px;">Gelir, kesinti ve puantaj dökümü</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" id="payroll-detail-content" aria-live="polite" style="background-color: #f8fafc;">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted small mt-3">Bordro detayları hazırlanıyor...</div>
                </div>
            </div>
            <div class="modal-footer bg-light py-2.5 px-3 px-md-4 d-flex justify-content-between align-items-center">
                <button type="button" class="btn btn-link link-secondary text-decoration-none" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-dark shadow-sm px-3" id="print-detailed-payroll" style="background-color: #1e293b; border-color: #1e293b;">
                    <i class="ti ti-printer icon me-1.5"></i> Detayı Yazdır
                </button>
            </div>
        </div>
    </div>
</div>
