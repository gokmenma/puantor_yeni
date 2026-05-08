<?php
$work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;



?>
<div class="card-body">

    <div>
        <div class="row">
            <!-- Kaydet butonu -->
            <div class="col-auto ms-auto">
                <a class="btn btn-primary" id="home_save">
                    <i class="ti ti-device-floppy icon"></i>
                    Kaydet
                </a>
            </div>
        </div>
        <?php if ($Auths->hasPermission("daily_working_hours_edit")) {
            ; ?>
            <h3 class="card-title mt-1">Günlük Çalışma Saati</h3>
            <p class="card-subtitle">Firmanızda günlük çalışma saatini belirleyebilrsiniz <br>
                <small class="text-red">Tüm hesaplamalarda değişiklik yapacağı için alt kullanıcılara bu yetkiyi açmamanız
                    gerekir</small>
            </p>


            <form action="" id="settingsHomeForm">
                <div class="col-md-2">
                    <input type="text" class="form-control" name="work_hour" placeholder="Örn:10"
                        value="<?php echo $work_hour; ?>">
                </div>
            </form>
        <?php } ?>
    </div>
</div>