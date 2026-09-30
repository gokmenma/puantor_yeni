<div class="modal modal-blur fade" id="payroll-detail-modal" tabindex="-1" role="dialog" aria-labelledby="payroll-detail-title" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down" role="document">
        <div class="modal-content shadow-lg border" style="border-radius: 12px; border-color: #dbe3ec !important; overflow: hidden;">
            <div class="modal-header bg-white px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-file-invoice" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="payroll-detail-title" style="font-size: 1.15rem; letter-spacing: -0.3px;">Bordro Detayı</h4>
                        <div class="text-secondary small mt-0.5" id="payroll-detail-period" style="font-size: 12px;">Gelir, kesinti ve puantaj dökümü</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3.5 p-md-4" id="payroll-detail-content" aria-live="polite" style="background-color: #eef3f8; min-height: 280px;">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <div class="text-muted small mt-3">Bordro detayları hazırlanıyor...</div>
                </div>
            </div>
            <div class="modal-footer bg-white px-4 py-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid #e2e8f0;">
                <button type="button" class="btn btn-outline-secondary px-3 py-1.5" data-bs-dismiss="modal" style="height: 34px; font-size: 13px;">Kapat</button>
                <button type="button" class="btn btn-dark shadow-sm px-3.5 py-1.5 d-inline-flex align-items-center gap-1.5" id="print-detailed-payroll" style="background-color: #1e293b; border-color: #1e293b; height: 34px; font-size: 13px;">
                    <i class="ti ti-printer" style="font-size: 16px;"></i> Detayı Yazdır
                </button>
            </div>
        </div>
    </div>
</div>
