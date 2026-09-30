<!-- İcra Kesintileri Geçmişi Modalı (Bordro Listesi için) -->
<div class="modal modal-blur fade" id="deductionsHistoryModal" tabindex="-1" role="dialog" aria-labelledby="modal-deductions-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 680px;" role="document">
        <div class="modal-content shadow-lg border-0" style="border-radius: 14px; overflow: hidden;">
            <!-- Modal Header -->
            <div class="modal-header py-3 px-3.5 bg-white border-bottom d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-teal-lt text-teal shadow-xs" style="width: 42px; height: 42px;">
                        <i class="ti ti-receipt-tax" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h4 class="modal-title fw-bold text-dark mb-0" id="modal-deductions-title" style="font-size: 1.15rem; letter-spacing: -0.3px;">İcra Kesintileri Geçmişi</h4>
                        <div class="text-secondary small mt-0.5" style="font-size: 12.5px;">Bordro dönemlerinde yapılan maaş haczi kesintileri</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-0 bg-white">
                <!-- Personel ve Toplam Kesinti Özet Kutusu -->
                <div class="m-3.5 p-3 rounded-3 bg-light-subtle border d-flex justify-content-between align-items-center" style="border-color: #e2e8f0 !important;">
                    <div class="pe-3">
                        <div class="small text-muted text-uppercase tracking-wider fw-semibold mb-1" style="font-size: 10.5px; letter-spacing: 0.5px;">Personel / Dosya</div>
                        <div class="d-flex align-items-center flex-wrap gap-2" id="modal-deductions-person-name">
                            <span class="fs-4 text-dark fw-bold">-</span>
                        </div>
                        <div class="small text-muted mt-1 d-flex align-items-center gap-1" id="modal-deductions-count-wrapper" style="font-size: 11.5px;">
                            <i class="ti ti-layers-subtract text-secondary"></i>
                            <span id="modal-deductions-count">0 kesinti</span>
                        </div>
                    </div>
                    <div class="text-end ps-3">
                        <div class="bg-white px-3.5 py-2 rounded-2 border shadow-xs text-end" style="border-color: #d1fae5 !important; background-color: #f0fdf4 !important;">
                            <div class="text-success text-uppercase tracking-wider fw-bold" style="font-size: 10.5px; letter-spacing: 0.5px;">Toplam Kesilen</div>
                            <div class="h3 mb-0 text-success fw-bold" id="modal-deductions-total" style="font-size: 1.35rem; letter-spacing: -0.4px;">0,00 ₺</div>
                        </div>
                    </div>
                </div>

                <!-- Kesintiler Tablosu -->
                <div class="px-3.5 pb-3.5">
                    <div class="table-responsive border rounded-3" style="max-height: 380px; border-color: #e2e8f0 !important;">
                        <table class="table table-vcenter table-hover table-sm mb-0">
                            <thead class="bg-light sticky-top" style="border-bottom: 1px solid #e2e8f0;">
                                <tr>
                                    <th class="ps-3 py-2.5 text-secondary fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; width: 125px;">Dönem</th>
                                    <th class="py-2.5 text-secondary fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">Kesinti Açıklaması</th>
                                    <th class="text-end pe-3 py-2.5 text-secondary fw-bold text-uppercase" style="font-size: 11px; letter-spacing: 0.5px; width: 150px;">Tutar</th>
                                </tr>
                            </thead>
                            <tbody id="modal-deductions-table-body">
                                <!-- AJAX ile doldurulacak -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer py-2.5 px-3.5 bg-light-subtle border-top d-flex justify-content-between align-items-center" style="border-color: #e2e8f0 !important;">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1.5" id="btn-modal-print-deductions" style="height: 34px; font-size: 12.5px;">
                        <i class="ti ti-printer" style="font-size: 16px;"></i> Yazdır
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1.5" id="btn-modal-excel-deductions" style="height: 34px; font-size: 12.5px;">
                        <i class="ti ti-file-spreadsheet" style="font-size: 16px;"></i> Excel
                    </button>
                </div>
                <button type="button" class="btn btn-dark btn-sm px-4" data-bs-dismiss="modal" style="height: 34px; font-size: 13px; font-weight: 500;">Kapat</button>
            </div>
        </div>
    </div>
</div>
