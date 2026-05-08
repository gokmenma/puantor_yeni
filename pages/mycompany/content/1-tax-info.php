<div class="col-md-12">
    <div class="row mt-3 mb-3">
        <div class="col-md-2">
            <label for="">Yetkili Adı (*)</label>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="yetkili_adi" value="<?php echo $myfirm->yetkili_adi; ?>" required>
        </div>
        <div class="col-md-2">
            <label for="">Vergi No</label>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="vergi_no" value="<?php echo $myfirm->tax_number; ?>">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-2">
            <label for="">Vergi Dairesi</label>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="vergi_dairesi" value="<?php echo $myfirm->tax_office; ?>">
        </div>
        <div class="col-md-2">
            <label for="">Başlangıç Bütçesi</label>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control money" name="start_budget" value="<?php echo $myfirm->start_budget; ?>">
        </div>
    </div>

</div>