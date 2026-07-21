<?php
require_once "App/Helper/jobs.php";
require_once "App/Helper/teams.php";
require_once "Model/Wages.php";

$jobGroups = new Jobs();
$teamsHelper = new Teams();
$WagesModel = new Wages();

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

if (isset($person->wage_type) && $person->wage_type == 1) {
    $wage_type_label = 'Aylık Maaş';
    $white_checked = 'checked';
} else {
    $wage_type_label = 'Günlük Ücret';
    $blue_checked = 'checked';
}

$active_custom_wage = null;
if (!empty($person->id)) {
    $active_custom_wage = $WagesModel->getCurrentWage($person->id);
}
?>
<div class="row mb-3">

    <div class="col-auto d-flex ms-auto">
        <!-- Page title actions -->
        <div class="col-auto d-print-none me-2">
            <a href="#" class="btn btn-teal route-link" data-page="persons/manage">
                <i class="ti ti-plus icon me-2"></i> Yeni
            </a>
        </div>
        <div class="col-auto d-print-none">
            <button type="button" class="btn btn-primary" id="savePerson">
                <i class="ti ti-device-floppy icon me-2"></i>
                Kaydet
            </button>
        </div>
    </div>
</div>



<form action="" id="personForm">

    <div class="row d-none">
        <div class="col-4">
            <input type="text" class="form-control" name="id" id="person_id" value="<?php echo $new_id; ?>"
                required>
        </div>
        <div class="col-4">
            <input type="text" class="form-control" name="action" value="savePerson" required>
        </div>
    </div>

        <div class="col-md-12">


            <div class="row mt-2">
                <div class="col-md-2">
                    <label for="">Adı Soyadı (*)</label>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="full_name"
                        value="<?php echo $person->full_name ?? ''; ?>" required>
                </div>
                <div class="col-md-2 mt-2">
                    <label for="">Tc Kimlik No (*) </label>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="kimlik_no"
                        value="<?php echo Security::safeDecrypt($person->kimlik_no ?? ''); ?>" required>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-2">
                    <label for="">İşe Başlama/Ayrılma Tarihi (*)</label>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control flatpickr" name="job_start_date"
                        value="<?php echo $person->job_start_date ?? date('d.m.Y'); ?>" required>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control flatpickr" name="job_end_date"
                        placeholder="İşten Ayrılma Tarihi" value="<?php echo $person->job_end_date ?? ''; ?>">
                </div>
                <div class="col-md-2 mt-2">
                    <label id="wage_type_label" for=""><?php echo $wage_type_label; ?></label>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control fw-bold money <?php echo $active_custom_wage ? 'border-warning bg-warning-lt' : ''; ?>" name="daily_wages"
                        value="<?php echo Helper::moneyToNumber($person->daily_wages ?? 0) ?? ''; ?>"
                        <?php echo $active_custom_wage ? 'title="Ücret Tanımları sekmesinde aktif özel ücret mevcuttur."' : ''; ?>>
                    <?php if ($active_custom_wage): ?>
                        <div class="form-text text-warning fw-semibold mt-1" style="font-size: 10.5px; line-height: 1.2;">
                            <i class="ti ti-alert-triangle me-1"></i> Aktif Özel Ücret: <strong>₺<?php echo Helper::formattedMoneyWithoutCurrency($active_custom_wage->amount); ?></strong>
                            <div class="text-muted fw-normal" style="font-size: 9.5px;">(Ücret Tanımları geçerlidir)</div>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-2 mt-2">

                    <div class="d-flex">

                        <label class="form-check form-check-inline">
                            <input class="form-check-input wage_type" type="radio" value="2" name="wage_type"
                                id="blue_collar" <?php echo $blue_checked ?? ''; ?>>
                            <span class="form-check-label">Mavi Yaka</span>
                        </label>
                        <label class="form-check form-check-inline">
                            <input class="form-check-input wage_type" type="radio" value="1" id="white_collar"
                                name="wage_type" <?php echo $white_checked ?? ''; ?>>
                            <span class="form-check-label">Beyaz Yaka</span>
                        </label>
                    </div>
                </div>

            </div>
            <div class="row mt-2">
                <div class="col-md-2">
                    <label for="">Telefon/Email Adresi</label>
                </div>
                <div class="col-md-2">
                    <input type="text" class="form-control" placeholder="505 555 55 55" maxlength="15" name="phone"
                        value="<?php echo Security::safeDecrypt($person->phone ?? ''); ?>">
                </div>

                <div class="col-md-2">
                    <input type="text" class="form-control" name="email" value="<?php echo Security::safeDecrypt($person->email ?? ''); ?>">
                </div>

                <div class="col-md-2 mt-2">
                    <label for="">Doğum Tarihi</label>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control flatpickr" name="birth_date"
                        placeholder="Doğum Tarihi" value="<?php echo !empty($person->birth_date) ? Date::dmY($person->birth_date) : ''; ?>">
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-md-2">
                    <label for="">PWA Giriş Şifresi</label>
                </div>
                <div class="col-md-4">
                    <input type="password" class="form-control" name="password" placeholder="Yeni şifre girin (Boş bırakılırsa değişmez)">
                </div>
                <div class="col-md-2 mt-2">
                    <label for="">İban Numarası</label>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="iban_number" maxlength="32"
                        value="<?php echo Security::safeDecrypt($person->iban_number ?? ''); ?>">
                </div>
            </div>
            <div class="row mt-2">

            <div class="col-md-2">
                    <label for="">Çalıştığı Proje</label>
                </div>
                <div class="col-md-4">
                    <?php echo $projectHelper->getProjectSelectMultiple("person_project",$personProjectsIds); ?>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-2">
                    <label for="">Grubu</label>
                </div>
                <div class="col-md-4">
                    <?php echo $jobGroups->jobGroupsSelect("job_groups", $person->job_group ?? ''); ?>
                </div>
                <div class="col-md-2 mt-2">
                    <label for="">Görevi</label>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control" name="job" value="<?php echo $person->job ?? ''; ?>">
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-2">
                    <label for="">Ekibi</label>
                </div>
                <div class="col-md-4">
                    <?php echo $teamsHelper->teamsSelect("team_id", $person->ekip ?? ($person->team_id ?? '')); ?>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-2">
                    <label for="">Adresi</label>
                </div>
                <div class="col-md-4">
                    <textarea class="form-control" style="min-height:100px" name="address"
                        id="address"><?php echo $person->address ?? ''; ?></textarea>
                </div>
                <div class="col-md-2 mt-2">
                    <label for="">Açıklama</label>
                </div>
                <div class="col-md-4">
                    <textarea class="form-control" style="min-height:100px"
                        placeholder="Personel hakkında not ekleyebilirsiniz" name="aciklama"
                        id="aciklama"><?php echo $person->aciklama ?? ''; ?></textarea>
                </div>

            </div>

            <?php if (!isset($person->id) || !$person->id): ?>
            <div class="row mt-3">
                <div class="col-md-12">
                    <div class="alert alert-info d-flex align-items-center gap-2 py-2">
                        <label class="form-check mb-0">
                            <input type="checkbox" class="form-check-input" name="kvkk_aydinlatma_onay" value="1" id="kvkkOnayCheck">
                            <span class="form-check-label">
                                KVKK kapsamında personele <strong>aydınlatma metni</strong> okundu/bilgi verildi ve onay alındı.
                            </span>
                        </label>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <?php
                require_once ROOT . '/Model/KvkkAydinlatmaModel.php';
                $aydinlatmaModel = new KvkkAydinlatmaModel();
                $onayVar = $aydinlatmaModel->onayVarMi((int)$person->id);
            ?>
            <div class="row mt-3">
                <div class="col-md-12">
                    <?php if ($onayVar): ?>
                    <span class="badge bg-success-lt"><i class="ti ti-shield-check me-1"></i> KVKK Aydınlatma Onayı Mevcut</span>
                    <?php else: ?>
                    <span class="badge bg-warning-lt"><i class="ti ti-shield-x me-1"></i> KVKK Aydınlatma Onayı Yok</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
</form>

<script>
    $(document).ready(function () {
        console.log("Job group and team initialization");

        // Enable tags for job_groups select2
        $("#job_groups").select2({
            tags: true,
            placeholder: "İş Grubu Seçiniz veya Yazınız",
            allowClear: true,
            width: '100%',
            dropdownParent: $("#job_groups").parent()
        });

        // Enable tags for team_id select2
        $("#team_id").select2({
            tags: true,
            placeholder: "Ekip Seçiniz veya Yazınız",
            allowClear: true,
            width: '100%',
            dropdownParent: $("#team_id").parent()
        });

    });
</script>