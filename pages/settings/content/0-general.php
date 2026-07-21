<?php
require_once ROOT . '/Model/HolidayWorkPolicyModel.php';

$work_hour = $Settings->getSettings("work_hour")->set_value ?? 8;
$overtime_rate = $Settings->getSettings("overtime_rate")->set_value ?? 50;
if (floatval($overtime_rate) < 50) { $overtime_rate = 50; }
$show_white_collar = $Settings->getSettings("show_white_collar_in_puantaj")->set_value ?? 0;
$yillik_izin_dusmeyecek_gunler = $Settings->getSettings("yillik_izin_dusmeyecek_gunler")->set_value ?? "6,7";
$holidayPolicyModel = new HolidayWorkPolicyModel();
$holidayPolicies = $holidayPolicyModel->getForFirm($_SESSION['firm_id'] ?? 0);
$holidayPolicyLabels = [
    'national' => 'Resmî / Millî Bayram',
    'religious' => 'Dini Bayram',
    'other' => 'Diğer Tatiller',
];
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
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Fazla Mesai Oranı (%)</label>
                    <div class="input-group">
                        <input type="number" class="form-control" name="overtime_rate" id="overtime_rate" placeholder="Örn: 50" min="50" step="1" value="<?php echo htmlspecialchars($overtime_rate); ?>">
                        <span class="input-group-text">%</span>
                    </div>
                    <small class="text-secondary d-block mt-1" style="font-size: 0.8rem;">Kanunen varsayılan oran %50'dir (Minimum %50). Fazla mesai saat ücreti bu oran artırımı ile hesaplanır (Örn: %50 için 1.5 katı).</small>
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

            <div class="mb-4">
                <label class="form-label fw-bold text-dark small mb-2">Resmi Tatilde Çalışma Kuralları</label>
                <div class="table-responsive border rounded">
                    <table class="table table-vcenter mb-0">
                        <thead>
                            <tr>
                                <th>Tatil Türü</th>
                                <th style="width: 180px;">İlave Gün</th>
                                <th style="width: 260px;">Hesaplama Yöntemi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($holidayPolicyLabels as $policyType => $policyLabel):
                                $policy = $holidayPolicies[$policyType]; ?>
                                <tr>
                                    <td class="fw-medium"><?php echo htmlspecialchars($policyLabel); ?></td>
                                    <td>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text">+</span>
                                            <input type="number" class="form-control holiday-additional-day"
                                                name="holiday_policy[<?php echo $policyType; ?>][additional_day_rate]"
                                                min="0" max="10" step="0.5"
                                                value="<?php echo htmlspecialchars($policy['additional_day_rate']); ?>">
                                            <span class="input-group-text">gün</span>
                                        </div>
                                    </td>
                                    <td>
                                        <select class="form-select form-select-sm"
                                            name="holiday_policy[<?php echo $policyType; ?>][calculation_basis]">
                                            <option value="pro_rata" <?php echo $policy['calculation_basis'] === 'pro_rata' ? 'selected' : ''; ?>>Çalışılan saate orantılı</option>
                                            <option value="full_day" <?php echo $policy['calculation_basis'] === 'full_day' ? 'selected' : ''; ?>>Çalışma varsa tam gün</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <small class="text-secondary d-block mt-2" style="font-size: 0.8rem;">
                    İlave gün, normal çalışma ücretine eklenir. Örneğin +1 gün toplamda 2 günlük, +2 gün toplamda 3 günlük hakediş oluşturur.
                </small>
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
