<?php
$sub_limit = $Settings->getSettings("cases_sub_limit")->set_value ?? 0;
$personnel_advance_request_visible = $Settings->getSettings("personnel_advance_request_visible")->set_value ?? 1;
?>
<div class="card-body">

    <div>
        <div class="row">
            <!-- Kaydet butonu -->
            <div class="col-auto ms-auto">
                <a class="btn btn-primary" id="financial_save">
                    <i class="ti ti-device-floppy icon"></i>
                    Kaydet
                </a>
            </div>
        </div>
        <?php if ($Auths->hasPermission("cases_sub_limit")) {
            ; ?>
            <h3 class="card-title mt-1 mb-0">Kasa Alt Limiti</h3>
            <p class="mb-2 p-0">

                Firmanızdaki kasaların belirleyeceğiniz tutarın altına düşmesini engelleyebiliriniz
            </p>
            

            <form action="" id="settingsFinancialForm">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Kasa Alt Limiti (₺)</label>
                        <input type="text" class="form-control money" name="sub_limit" placeholder="Örn:0"
                            value="<?php echo $sub_limit ; ?>">
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-label">Uygulama Görünürlük Ayarları</div>
                        <div>
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="personnel_advance_request_visible" <?php echo $personnel_advance_request_visible == 1 ? 'checked' : ''; ?>>
                                <span class="form-check-label">Personel Avans Talepleri Sayfası</span>
                                <small class="form-hint text-muted mt-1">Açık olduğunda personel uygulamasında avans talepleri sayfası görünür.</small>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        <?php } ?>
    </div>
</div>