<?php
require_once "App/Helper/users.php";
require_once "Model/UserModel.php";

$Users = new UserModel();
$userHelper = new UserHelper();


; ?>
<form action="" id="userForm">
    <div class="row mb-3">

        <div class="col-auto ms-auto d-print-none">
            <button type="button" class="btn btn-primary" id="userSave">
                <i class="ti ti-device-floppy icon me-2"></i>
                Kaydet
            </button>
        </div>

    </div>

    <div class="row mb-3">

        <div class="col-md-2">
            <label for="" class="align-middle">Adı Soyadı</label>
        </div>
        <div class="col-md-4 mb-3">
            <input type="text" class="form-control" name="full_name" value="<?php echo $user->full_name ?? '' ?>"
                id="full_name" required>
        </div>

        <div class="col-md-2">
            <label for="" class="align-middle">Parola</label>
        </div>
        <div class="col-md-4">
            <input type="password" autocomplete="off" class="form-control" name="password" value="" id="password"
                required>
        </div>

    </div>

    <div class="row mb-3">

        <div class="col-md-2">
            <label for="" class="align-middle">Eposta Adresi</label>
        </div>
        <div class="col-md-4 mb-3">
            <input type="text" class="form-control" disabled readonly name="email"
                value="<?php echo $user->email ?? '' ?>" id="email" required>
        </div>

        <div class="col-md-2">
            <label for="" class="align-middle">Telefon</label>
        </div>
        <div class="col-md-4">
            <input type="text" class="form-control" name="phone" value="<?php echo $user->phone ?? '' ?>" id="phone">
        </div>

    </div>

    <div class="row mb-3 d-flex">

        <div class="col-md-2">
            <label for="" class="align-middle">Kullanıcı Rolü</label>
        </div>
        <div class="col-md-4 mb-3">
            <?php echo $userHelper->userRoles("user_roles", $user->user_roles ?? '') ?>
        </div>

        <div class="col-md-2">
            <label for="" class="align-middle">Mesleği</label>
        </div>

        <div class="col-md-4 col-sm-6">
            <input type="text" class="form-control" name="job" value="<?php echo $user->job ?? '' ?>" id="job">
        </div>

    </div>
</form>