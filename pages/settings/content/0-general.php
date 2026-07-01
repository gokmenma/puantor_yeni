<?php
$work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
$show_white_collar = $Settings->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;
$yillik_izin_dusmeyecek_gunler = $Settings->getSettings("yillik_izin_dusmeyecek_gunler")->set_value ?? "6,7";
?>
<div class="card">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-4">
            <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle">
                <i class="ti ti-settings" style="font-size: 1.5rem;"></i>
            </span>
            <div>
                <h3 class="card-title mb-1 fw-bold text-dark">Program Ayarları</h3>
                <p class="text-secondary small mb-0">Firmanızın genel çalışma ve görünürlük ayarlarını buradan yönetebilirsiniz.</p>
                <small class="text-danger fw-bold" style="font-size: 0.8rem;">Bu ayarlarda yapılacak değişiklikler tüm hesaplamaları etkileyebilir.</small>
            </div>
        </div>

        <?php if ($is_superadmin || $Auths->hasPermission("daily_working_hours_edit")): ?>
        <form id="settingsHomeForm">
            <!-- Programmatic trigger button for backwards compatibility with settings.js -->
            <button type="button" id="home_save" style="display: none;"></button>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Günlük Çalışma Saati</label>
                    <input type="number" class="form-control" name="work_hour" placeholder="Örn: 8" value="<?php echo htmlspecialchars($work_hour); ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Yıllık İzinden Düşmeyecek Günler</label>
                    <select class="form-select select2-setting" name="yillik_izin_dusmeyecek_gunler" id="yillik_izin_dusmeyecek_gunler">
                        <option value="6,7" <?php echo $yillik_izin_dusmeyecek_gunler === '6,7' ? 'selected' : ''; ?>>Cumartesi ve Pazar</option>
                        <option value="7" <?php echo $yillik_izin_dusmeyecek_gunler === '7' ? 'selected' : ''; ?>>Sadece Pazar</option>
                    </select>
                    <small class="text-secondary d-block mt-1" style="font-size: 0.8rem;">Seçilen günler yıllık izin hakedişlerinden düşmeyecektir.</small>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <label class="form-label fw-bold text-dark small mb-2">Puantaj Görünürlük Ayarları</label>
                    <div class="form-check form-switch p-0">
                        <label class="d-flex align-items-center" style="cursor: pointer;">
                            <input class="form-check-input me-3" type="checkbox" name="show_white_collar_in_puantaj" id="show_white_collar_in_puantaj" value="1" <?php echo $show_white_collar == 1 ? 'checked' : ''; ?> style="width: 2.5rem; height: 1.25rem; margin-left: 0;">
                            <div>
                                <span class="fw-bold text-dark d-block">Beyaz Yaka Personellerini Puantajda Göster</span>
                                <small class="text-secondary d-block mt-0.5" style="font-size: 0.8rem; line-height: 1.3;">Seçili olduğu takdirde aylık ücret yerine ay/gün olarak işlem yapılacaktır.</small>
                            </div>
                        </label>
                    </div>
                </div>
            </div>
        </form>
        <?php else: ?>
        <div class="alert alert-warning mb-0">
            Bu ayarları düzenlemek için gerekli yetkiniz bulunmamaktadır.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
$(document).ready(function() {
    if ($.fn.select2) {
        $('.select2-setting').select2({
            width: '100%',
            minimumResultsForSearch: -1
        });
    }
});
</script>