<div class="modal modal-blur fade" id="modal-excel-preview" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom py-3">
                <div class="d-flex align-items-center">
                    <div class="bg-success-lt text-success d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; border-radius: 10px;">
                        <i class="ti ti-file-spreadsheet fs-1"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark" style="font-size: 1.1rem;">Excel İndirme Önizleme</h5>
                        <p class="text-muted mb-0 small">İndirmeden önce verilerinizi inceleyin ve biçimlendirmeyi seçin.</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- Seçenekler ve İstatistikler -->
                <div class="row g-3 mb-4 align-items-center bg-light p-3 rounded-3 border">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold text-secondary mb-2" style="font-size: 0.85rem;">Gösterim Şekli:</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="excel_view_format" value="code" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label py-2 px-3">
                                    <i class="ti ti-code icon me-1 text-primary"></i> Kısa Kod Olarak Göster (NÇ, HT, FM)
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="excel_view_format" value="hour" class="form-selectgroup-input">
                                <span class="form-selectgroup-label py-2 px-3">
                                    <i class="ti ti-clock icon me-1 text-success"></i> Saat Olarak Göster (8, 0, 10)
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-5 text-md-end border-start-md border-top-sm pt-md-0 pt-3">
                        <div class="d-inline-block text-start">
                            <span class="d-block text-secondary small">Önizlenen Satır Sayısı</span>
                            <span class="h3 mb-0 fw-bold text-dark" id="excel-preview-row-count">0 Satır</span>
                        </div>
                    </div>
                </div>

                <!-- Tablo Önizleme Alanı -->
                <div class="card border shadow-sm">
                    <div class="card-header bg-light py-2 px-3">
                        <h4 class="card-title text-secondary mb-0" style="font-size: 0.85rem; font-weight: 600;">
                            <i class="ti ti-eye text-success me-2"></i> Veri Önizleme
                        </h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 450px; overflow: auto;" id="excel-preview-scroll-container">
                            <div id="excel-preview-container">
                                <!-- JS ile doldurulacak -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer bg-light border-top py-3">
                <button type="button" class="btn btn-link link-secondary fw-semibold" data-bs-dismiss="modal">
                    Vazgeç
                </button>
                <button type="button" class="btn btn-success ms-auto px-4" id="btn-confirm-excel-export">
                    <i class="ti ti-download icon me-2"></i>
                    Excel Olarak İndir
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    #modal-excel-preview .form-selectgroup-label {
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    #modal-excel-preview .form-selectgroup-input:checked + .form-selectgroup-label {
        background-color: var(--tblr-bg-surface);
        border-color: var(--tblr-primary);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    }
    #excel-preview-container table {
        font-size: 11px;
    }
    #excel-preview-container th, 
    #excel-preview-container td {
        padding: 6px 8px !important;
        text-align: center;
        vertical-align: middle;
        border: 1px solid var(--tblr-border-color) !important;
        box-shadow: none !important;
    }
    #excel-preview-container td.text-start {
        text-align: left !important;
    }
    @media (min-width: 1200px) {
        #modal-excel-preview .modal-dialog {
            max-width: 96% !important;
            width: 96% !important;
        }
    }
</style>
