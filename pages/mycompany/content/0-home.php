<div class="row">

    <div class="col-md-2 text-center vertical-center">
        <div class="brand-img">
            <img src="../../uploads/<?php echo $myfirm->brand_logo ?? '' ?>" alt="">
        </div>
    </div>
    <div class="col-md-10">
        <div class="row mt-3 mb-3">
            <div class="col-md-2">
                <label for="">Firma Adı (*)</label>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="firm_name" value="<?php echo $myfirm->firm_name ?? '' ?>"
                    required>
            </div>
            <div class="col-md-2">
                <label for="">Telefon</label>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="phone" value="<?php echo $myfirm->phone ?? '' ?>">
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-2">
                <label for="">Mail Adresi</label>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="email" value="<?php echo $myfirm->email ?? '' ?>">
            </div>
            <div class="col-md-2">
                <label for="">Başlangıç Bütçesi</label>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control money" name="start_budget"
                    value="<?php echo $myfirm->start_budget ?? '' ?>">
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-2">
                <label for="">Firma Logosu</label>
            </div>
            <div class="col-md-4">
                <input type="file" class="form-control" name="brand_logo" onchange="previewImage(event)">

            </div>
            <div class="col-md-2">
                <label for="">Açıklama</label>
            </div>
            <div class="col-md-4">
                <input type="text" class="form-control" name="description"
                    value="<?php echo $myfirm->description ?? '' ?>">
            </div>

        </div>

    </div>
</div>
