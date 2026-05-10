<?php
$work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
$show_white_collar = $Settings->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;
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
            <h3 class="card-title mt-1">Sistem Ayarları</h3>
            <p class="card-subtitle">Firmanızın genel çalışma ve görünürlük ayarlarını buradan yönetebilirsiniz. <br>
                <small class="text-red">Bu ayarlarda yapılacak değişiklikler tüm hesaplamaları etkileyebilir.</small>
            </p>


            <form action="" id="settingsHomeForm">
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Günlük Çalışma Saati</label>
                        <input type="text" class="form-control" name="work_hour" placeholder="Örn:10"
                            value="<?php echo $work_hour; ?>">
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-label">Puantaj Görünürlük Ayarları</div>
                        <div>
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="show_white_collar_in_puantaj" <?php echo $show_white_collar == 1 ? 'checked' : ''; ?>>
                                <span class="form-check-label">Beyaz Yaka Personellerini Puantajda Göster</span>
                                <small class="form-hint text-muted mt-1">Seçili olduğu takdirde aylık ücret yerine ay/gün olarak işlem yapılacaktır.</small>
                            </label>
                        </div>
                    </div>
                </div>
            </form>
        <?php } ?>
    </div>
</div>