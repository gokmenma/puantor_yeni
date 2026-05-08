<?php
require_once ROOT . "/App/Helper/company.php";
require_once ROOT . "/App/Helper/projects.php";
$companyHelper = new CompanyHelper();
$projectHelper = new ProjectHelper();

?>
<!-- Alınan mı verilen mi olarak radio button ekle-->
<div class="row mt-3">
    <div class="col-md-2">
        <label for="">Proje Türü</label>
    </div>
    <div class="col-md-4">
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="project_type" id="inlineRadio1" value="1"
                <?php echo $type == 1 ? 'checked' : '' ?>> 
            <label class="form-check-label" for="inlineRadio1">Alınan</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="project_type" id="inlineRadio2" value="2"
                <?php echo $type == 2 ? 'checked' : '' ?>>
            <label class="form-check-label" for="inlineRadio2">Verilen</label>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-2">
        <label for="">Proje Adı</label>
    </div>
    <div class="col-md-4">
        <input type="text" class="form-control" name="project_name" value="<?php echo $project->project_name ?? '' ?>">
    </div>
    <div class="col-md-2">
        <label for="">Yüklenici Firması</label>
    </div>
    <div class="col-md-4">
        <?php echo $companyHelper->getCompanySelect("project_company", $project->company_id ?? ''); ?>
    </div>


</div>

<div class="row mt-3">

    <div class="col-md-2">
        <label for="">Sözleşmesi</label>
    </div>
    <div class="col-md-4">
        <input type="file" class="form-control" name="project_file" value="<?php echo $project->project_file ?? '' ?>">
    </div>
    <div class="col-md-2">
        <label for="">Proje Durumu</label>
    </div>
    <div class="col-md-4">
        <?php echo $projectHelper->projectStatusSelect("project_status", $project->status ?? ''); ?>
    </div>


</div>
<div class="row mt-3">
    <div class="col-md-2">
        <label for="">Başlangıç Tarihi
            <p>Tahmini Bitiş Tarihi</p>
        </label>
    </div>
    <div class="col-md-2">
        <input type="text" class="form-control flatpickr" name="start_date"
            value="<?php echo $project->start_date ?? '' ?>">
    </div>
    <div class="col-md-2">
        <input type="text" class="form-control flatpickr" name="end_date"
            value="<?php echo $project->end_date ?? '' ?>">
    </div>
    <?php
    //if ($id == 0) {
    ?>
        <div class="col-md-2">
            <label for="">Proje Bedeli</label>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control money" name="budget" value="<?php echo $project->budget ?? 0 ?>">
        </div>
    <?php //} ?>


</div>
<div class="row mt-3">
    <div class="col-md-2">
        <label for="">Not</label>
    </div>
    <div class="col-md-10">
        <textarea class="form-control" name="project" style="min-height:100px"
            placeholder="Proje hakkında not ekleyebilirsiniz"><?php echo $project->notes ?? '' ?></textarea>
    </div>
</div>