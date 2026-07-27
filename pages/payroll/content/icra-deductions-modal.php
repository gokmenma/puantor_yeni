<!-- İcra Kesintileri Geçmişi Modalı (Bordro Listesi için) -->
<div class="modal modal-blur fade" id="deductionsHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-700 text-white" id="modal-deductions-title">
                    <i class="ti ti-history me-2"></i>İcra Kesintileri Geçmişi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted text-uppercase tracking-wide font-weight-600">Personel / Dosya</div>
                        <div class="font-weight-700" id="modal-deductions-person-name">-</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted text-uppercase tracking-wide font-weight-600">Toplam Kesilen</div>
                        <div class="h3 mb-0 text-success font-weight-700" id="modal-deductions-total">0,00 ₺</div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-vcenter table-striped table-sm mb-0">
                        <thead class="sticky-top">
                            <tr>
                                <th class="ps-3">Dönem</th>
                                <th>Açıklama</th>
                                <th class="text-end pe-3">Tutar</th>
                            </tr>
                        </thead>
                        <tbody id="modal-deductions-table-body">
                            <!-- AJAX ile doldurulacak -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2 d-flex justify-content-between">
                <div class="btn-group">
                    <button type="button" class="btn btn-outline-primary btn-sm me-1" id="btn-modal-print-deductions">
                        <i class="ti ti-printer me-1"></i> Yazdır
                    </button>
                    <button type="button" class="btn btn-outline-success btn-sm me-1" id="btn-modal-excel-deductions">
                        <i class="ti ti-file-spreadsheet me-1"></i> Excel'e İndir
                    </button>
                </div>
                <button type="button" class="btn btn-secondary px-4 ms-auto" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>
