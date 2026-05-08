<div class="card">
    <div class="card-header">

        <h3 class="card-title">Firma Bilgileri</h3>
        <div class="col-auto ms-auto d-print-none">
            <a href="index?p=companies/manage" class="btn btn-success" id="newCompany">
                <i class="ti ti-plus icon me-2"></i>
                Yeni
            </a>
            <button type="button" class="btn btn-primary" id="saveCompany">
                <i class="ti ti-device-floppy icon me-2"></i>
                Kaydet
            </button>
        </div>
    </div>
    <div class="card-body">

        <form action="" id="companyForm">
            <div class="row d-flex d-none">
                <div class="col-md-4">
                    <input type="text" name="id" class="form-control" value="<?php echo $new_id; ?>">
                </div>
                <div class="col-md-4">
                    <input type="text" name="action" class="form-control" value="saveCompany">
                </div>
            </div>

            <div class="row mb-3 mt-3">
                <div class="col-md-2">
                    <label for="company_name" class="form-label">Firma Adı</label>
                </div>
                <div class="col-md-4">
                    <input type="text" name="company_name" class="form-control"
                        value="<?php echo $company->company_name ?? "" ?>">
                </div>
                <div class="col-md-2">
                    <label for="" class="form-label">Yetkilisi</label>
                </div>
                <div class="col-md-4">
                    <input type="text" name="yetkili" class="form-control"
                        value="<?php echo $company->yetkili ?? "" ?>">
                </div>

            </div>

            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="" class="form-label">Telefon</label>
                </div>
                <div class="col-md-4">
                    <input type="text" name="phone" class="form-control" value="<?php echo $company->phone ?? "" ?>">
                </div>
                <div class="col-md-2">
                    <label for="" class="form-label">Email</label>
                </div>
                <div class="col-md-4">
                    <input type="text" name="email" class="form-control" value="<?php echo $company->email ?? "" ?>">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="" class="form-label">Vergi Dairesi</label>
                </div>
                <div class="col-md-4">
                    <input type="text" name="tax_office" class="form-control"
                        value="<?php echo $company->tax_office ?? "" ?>">
                </div>
                <div class="col-md-2">
                    <label for="" class="form-label">Vergi No/Hesap No</label>
                </div>
                <div class="col-md-2">
                    <input type="text" name="tax_number" class="form-control"
                        value="<?php echo $company->tax_number ?? "" ?>">
                </div>

                <div class="col-md-2">
                    <input type="text" name="account_number" class="form-control"
                        value="<?php echo $company->account_number ?? "" ?>">
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="" class="form-label">Şehir</label>
                </div>
                <div class="col-md-4">
                    <?php echo $cities->citySelect("firm_cities", $company->city ?? ''); ?>
                </div>
                <div class="col-md-2">
                    <label for="" class="form-label">İlçe</label>
                </div>
                <div class="col-md-4">
                    <select name="firm_towns" id="firm_towns" class="form-control select2" style="width:100%">
                        <option value="">İlçe Seçiniz</option>
                        <option selected value="<?php echo $company->town ?? ''; ?>">
                            <?php echo $cities->getTownName($company->town ?? ''); ?>
                        </option>
                    </select>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-2">
                    <label for="" class="form-label">Açıklama</label>
                </div>
                <div class="col-md-4">
                    <input type="text" name="description" class="form-control"
                        value="<?php echo $company->description ?? "" ?>">

                </div>
                <div class="col-md-2">
                    <label for="" class="form-label">Adres</label>
                </div>
                <div class="col-md-4">
                    <input type="text" name="address" class="form-control"
                        value="<?php echo $company->address ?? "" ?>">

                </div>

            </div>
        </form>
    </div>
</div>