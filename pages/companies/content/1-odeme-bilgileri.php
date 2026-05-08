<div class="card">
    <div class="card-header">
        <h3 class="card-title">Gelir Gider Listesi </h3>
        <div class="d-flex col-auto ms-auto">
            <a href="#" class="btn btn-success" id="firmaOdemesi" data-bs-toggle="modal" data-bs-target="#firmaOdemeModal">
                <i class="ti ti-plus icon me-2"></i>
                Yeni
            </a>
        </div>
    </div>
</div>

<div class="modal modal-blur fade"
    id="firmaOdemeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-3 modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Firma Ödeme Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="" id="firmaOdemeForm">
                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label for="firma_adi" class="form-label">Firma Adı</label>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="firma_adi" class="form-control"
                                value="<?php echo $company->company_name ?? "" ?>">
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn me-auto" data-bs-dismiss="modal">Kapat</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Kaydet</button>
            </div>
        </div>
    </div>
</div>