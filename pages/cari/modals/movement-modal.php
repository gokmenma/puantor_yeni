<div class="modal modal-blur fade" id="movement-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-receipt-2 me-2 text-primary"></i>Yeni Hareket Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="movementForm" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" name="id" id="movement_id" value="0">
                    <input type="hidden" name="cari_id" value="<?php echo htmlspecialchars($_GET['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">İşlem Tipi <span class="text-danger">*</span></label>
                        <div class="form-selectgroup w-100">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="mType" value="alacak" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2">
                                    <i class="ti ti-arrow-down-left text-success me-2"></i> Tahsilat (Alacak)
                                </span>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="mType" value="borc" class="form-selectgroup-input">
                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2">
                                    <i class="ti ti-arrow-up-right text-danger me-2"></i> Ödeme / Satış (Borç)
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Tutar (₺) <span class="text-danger">*</span></label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-currency-lira"></i></span>
                            <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="mAmount" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">İşlem Tarihi <span class="text-danger">*</span></label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                                    <input type="text" class="form-control flatpickr" name="islem_tarihi" id="islem_tarihi" value="<?php echo date('d.m.Y'); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Belge No</label>
                                <div class="input-icon">
                                    <span class="input-icon-addon"><i class="ti ti-file-text"></i></span>
                                    <input type="text" class="form-control" name="belge_no" id="belge_no" placeholder="Örn: FTR-001">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-semibold">Açıklama</label>
                        <textarea class="form-control" name="aciklama" id="aciklama" rows="2" placeholder="Hareket açıklaması giriniz..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="button" class="btn btn-primary" id="saveMovement">
                        <i class="ti ti-device-floppy me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
