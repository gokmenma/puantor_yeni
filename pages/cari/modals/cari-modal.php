<div class="modal modal-blur fade" id="cari-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Cari Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="cariForm">
                    <input type="hidden" name="id" id="cari_id" value="0">
                    <div class="mb-3">
                        <label class="form-label">Cari Adı <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="CariAdi" id="CariAdi" placeholder="Cari Adı" required>
                    </div>
                    <div class="row">
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Telefon</label>
                                <input type="text" class="form-control" name="Telefon" id="Telefon" placeholder="Telefon">
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="Email" id="Email" placeholder="Email">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Adres</label>
                        <textarea class="form-control" name="Adres" id="Adres" rows="2" placeholder="Adres"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notlar</label>
                        <textarea class="form-control" name="notlar" id="notlar" rows="2" placeholder="Özel notlar"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" class="btn btn-primary" id="saveCari">Kaydet</button>
            </div>
        </div>
    </div>
</div>
