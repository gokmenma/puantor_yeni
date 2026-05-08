<?php 
require_once $_SERVER['DOCUMENT_ROOT'] . '/App/Helper/cities.php';
$cities = new Cities();

?>
<div class="row mt-3">
    <div class="col-md-2">
        <label for="">Şehir</label>
    </div>
    <div class="col-md-4">
        <?php echo $cityHelper->citySelect("project_city", $project->city ?? '') ?>
    </div>
    <div class="col-md-2">
        <label for="">İlçe</label>
    </div>
    <div class="col-md-4">
        <select type="text" class="form-control select2" name="project_town" id="project_town" style="width:100%">
            <option value="">İlçe seçiniz</option>
            <option selected value="<?php echo $project->town ?? '';?>"><?php echo $cities->getTownName($project->town ?? ''); ?></option>

        </select>
    </div>
</div>
<div class="row mt-3">
    <div class="col-md-2">
        <label for="">Email</label>
    </div>
    <div class="col-md-4">
        <input type="text" class="form-control" name="email" value="<?php echo $project->email ?? '' ?>">
    </div>
    <div class="col-md-2">
        <label for="">Telefon</label>
    </div>
    <div class="col-md-4">
        <input type="text" class="form-control" name="phone" value="<?php echo $project->phone ?? '' ?>">
    </div>
</div>
<div class="row mt-3">
    <div class="col-md-2">
        <label for="">Hesap Numarası</label>
    </div>
    <div class="col-md-4">
        <input type="text" class="form-control" name="account_number" value="<?php echo $project->account_number ?? '' ?>">
    </div>
    <div class="col-md-2">
        <label for="">Adres</label>
    </div>
    <div class="col-md-4">
        <textarea class="form-control" name="address" placeholder="Proje adresi"><?php echo $project->address ?? '' ?></textarea>
    </div>
</div>