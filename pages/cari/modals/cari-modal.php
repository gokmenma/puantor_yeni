<div class="modal modal-blur fade" id="cari-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header py-2 px-3">
                <h5 class="modal-title font-weight-700">
                    <i class="ti ti-address-book me-2 text-primary"></i>Yeni Cari Ekle
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <form id="cariForm">
                    <input type="hidden" name="id" id="cari_id" value="0">
                    <div class="row g-2">
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <label class="form-label required font-weight-600">Firma Adı</label>
                                <input type="text" class="form-control" name="FirmaAdi" id="FirmaAdi" placeholder="Örn: ABC İnşaat Ltd. Şti." required>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <label class="form-label font-weight-600">Yetkili Adı</label>
                                <input type="text" class="form-control" name="YetkiliAdi" id="YetkiliAdi" placeholder="Örn: Ahmet Yılmaz">
                            </div>
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <label class="form-label font-weight-600">Telefon</label>
                                <input type="text" class="form-control" name="Telefon" id="Telefon" placeholder="05xx xxx xx xx">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-2">
                                <label class="form-label font-weight-600">Email</label>
                                <input type="email" class="form-control" name="Email" id="Email" placeholder="info@firma.com">
                            </div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label font-weight-600">Adres</label>
                        <textarea class="form-control" name="Adres" id="Adres" rows="2" placeholder="Firma açık adresi..."></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label font-weight-600">Notlar</label>
                        <textarea class="form-control" name="notlar" id="notlar" rows="2" placeholder="Özel açıklama ve notlar..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer py-2 px-3 bg-light-subtle">
                <button type="button" class="btn btn-sm btn-link link-secondary me-auto" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-sm btn-primary" id="saveCari">
                    <i class="ti ti-device-floppy me-1"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>
