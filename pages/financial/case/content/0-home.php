<?php

require_once "App/Helper/helper.php";
require_once "App/Helper/users.php";


use App\Helper\Helper;
$userHelper = new UserHelper();

$selected_firm = $case->firm_id ?? $_SESSION['firm_id'];
$default_case = ($case->isDefault ?? 0) == 1 ? "checked" : "";
$is_disabled = ($case->isDefault ?? 0) == 1 ? "disabled" : "";

?>


<form action="" id="caseForm">
    <div class="row d-none">
        <div class="col-md-4">
            <input type="text" name="id" id="id" class="form-control" value="<?php echo $new_id ?>">
        </div>
        <div class="col-md-4">
            <input type="text" name="action" value="saveCase" class="form-control">
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-2">
            <label for="case_name" class="form-label">Varsayılan Kasa</label>
        </div>
        <div class="col-md-10">

            <label class="form-check form-switch form-switch-lg">
                <input class="form-check-input" name="default_case" id="default_case" type="checkbox" <?php echo $default_case . " " . $is_disabled; ?>>
                <span class="form-check-label form-check-label-on">Varsayılan</span>
                <span class="form-check-label form-check-label-off">Varsayılan Değil</span>
            </label>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-2">
            <label for="case_name" class="form-label">Firması</label>
        </div>
        <div class="col-md-4">
            <?php echo $company->myCompanySelect("firm_company", $selected_firm, "disabled"); ?>
        </div>
        <div class="col-md-2">
            <label for="case_name" class="form-label">Kasa Adı</label>
        </div>
        <div class="col-md-4">
            <input type="text" name="case_name" id="case_name" class="form-control"
                value="<?php echo $case->case_name ?? '' ?>">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-2">
            <label for="case_name" class="form-label">Bankası</label>
        </div>
        <div class="col-md-4">
            <input type="text" name="bank_name" class="form-control" value="<?php echo $case->bank_name ?? '' ?>">

        </div>
        <div class="col-md-2">
            <label for="case_name" class="form-label">Şubesi</label>
        </div>
        <div class="col-md-4">
            <input type="text" name="branch_name" class="form-control" value="<?php echo $case->branch_name ?? '' ?>">
        </div>
    </div>
    <div class="row mb-3">

        <div class="col-md-2">
            <label for="case_name" class="form-label">Açıklama</label>
        </div>
        <div class="col-md-4">
            <input type="text" name="description" class="form-control" value="<?php echo $case->description ?? '' ?>">
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-2">
            <label for="case_name" class="form-label">Kasa Para Birimi</label>
        </div>
        <div class="col-md-4">
            <?php echo Helper::moneySelect('case_money_unit', $case->start_budget_money ?? ''); ?>
        </div>

        <div class="col-md-2">
            <label for="case_name" class="form-label">Yetkili Kullanıcılar</label>
        </div>
        <div class="col-md-4">

            <?php
            // Veritabanından gelen user_ids değerini diziye dönüştür
            $user_ids = isset($case->user_ids) ? explode(',', $case->user_ids) : [];
            echo $userHelper->userSelectMultiple("user_ids[]", $user_ids);
            ?>
            <span class="form-text">Firma Sahibi; Kasayı Görmesini İstediği Kullanıcıları Ekleyebilir...</span>
        </div>

    </div>
</form>