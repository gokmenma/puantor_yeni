<?php
$sub_limit = $Settings->getSettings("cases_sub_limit")->set_value ?? 0;



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
                <div class="col-md-2">
                    <input type="text" class="form-control money" name="sub_limit" placeholder="Örn:0"
                        value="<?php echo $sub_limit ; ?>">
                </div>
            </form>
        <?php } ?>
    </div>
</div>