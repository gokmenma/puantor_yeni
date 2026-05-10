<div class="modal modal-blur fade" id="movement-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Hareket Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="movementForm">
                    <input type="hidden" name="id" id="movement_id" value="0">
                    <input type="hidden" name="cari_id" value="<?php echo $_GET['id']; ?>">
                    <div class="mb-3">
                        <label class="form-label">İşlem Tipi</label>
                        <div class="form-selectgroup w-100">
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="mType" value="alacak" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2">
                                    <i class="ti ti-plus text-success me-2"></i> Tahsilat (Alacak)
                                </span>
                            </label>
                            <label class="form-selectgroup-item flex-fill">
                                <input type="radio" name="mType" value="borc" class="form-selectgroup-input">
                                <span class="form-selectgroup-label d-flex align-items-center justify-content-center py-2">
                                    <i class="ti ti-minus text-danger me-2"></i> Ödeme (Borç)
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tutar (₺) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="amount" id="mAmount" placeholder="0.00" required>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">İşlem Tarihi <span class="text-danger">*</span></label>
                                <input type="text" class="form-control flatpickr" name="islem_tarihi" id="islem_tarihi" value="<?php echo date('d.m.Y'); ?>" required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Belge No</label>
                                <input type="text" class="form-control" name="belge_no" id="belge_no" placeholder="Belge No">
                            </div>
                        </div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="aciklama" id="aciklama" rows="2" placeholder="Açıklama"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="saveMovement">Kaydet</button>
            </div>
        </div>
    </div>
</div>
