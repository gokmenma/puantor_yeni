<?php
require_once "App/Helper/helper.php";
require_once "Model/AbonelikPaketleriModel.php";
require_once "Model/Auths.php";
require_once "App/Helper/security.php";

use App\Helper\Security;

// Yetki Kontrolü
$perm->checkAuthorize("aboneler_paketleri");

$paketModel = new AbonelikPaketleriModel();
$packages = $paketModel->getPackages();

$authsModel = new Auths();
$topLevelModules = $authsModel->auths();
$moduleTitlesById = [];
foreach ($topLevelModules as $module) {
    $moduleTitlesById[$module->id] = $module->title;
    foreach ($authsModel->subAuths($module->id) as $sub) {
        $moduleTitlesById[$sub->id] = $sub->title;
    }
}
?>
<div class="container-xl">
    <!-- Alert component'i dahil et -->
    <?php
    $title = "Abonelik Paketleri!";
    $text = "Sistemdeki tüm abonelik paketlerini, fiyatlarını, limitlerini ve özelliklerini buradan yönetebilirsiniz.";
    require_once 'pages/components/alert.php';
    ?>
    
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Abonelik Paketleri</h3>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary btn-add-paket">
                            <i class="ti ti-plus icon me-2"></i> Yeni Paket Ekle
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable" id="paketTable">
                        <thead>
                            <tr>
                                <th style="width:7%" class="text-center">Sıra</th>
                                <th>Paket Adı</th>
                                <th>Fiyat</th>
                                <th>Süre (Gün)</th>
                                <th>Firma Limiti</th>
                                <th>Alt Kullanıcı Limiti</th>
                                <th>Modüller</th>
                                <th>Özellikler</th>
                                <th>Görünürlük</th>
                                <th>Durum</th>
                                <th style="width:10%" class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($packages as $pkg):
                                $id = Security::encrypt($pkg->id);
                                $fiyat_format = number_format($pkg->fiyat, 2, ',', '.') . ' ₺';
                                $status_badge = $pkg->aktif_mi == 1 
                                    ? '<span class="badge bg-success text-success-fg">Aktif</span>' 
                                    : '<span class="badge bg-secondary text-secondary-fg">Pasif</span>';
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td>
                                        <div class="font-weight-medium text-primary"><?php echo htmlspecialchars($pkg->ad); ?></div>
                                    </td>
                                    <td><strong><?php echo $fiyat_format; ?></strong></td>
                                    <td><?php echo (int)$pkg->sure; ?> Gün</td>
                                    <td><?php echo (int)$pkg->firma_hakki; ?> Firma</td>
                                    <td><?php echo (int)$pkg->alt_kullanici_hakki; ?> Kullanıcı</td>
                                    <td>
                                        <?php if (empty($pkg->modul_auth_ids)): ?>
                                            <span class="badge bg-blue-lt">Tüm Modüller</span>
                                        <?php else:
                                            $pkgModuleIds = array_filter(array_map('intval', explode(',', $pkg->modul_auth_ids)));
                                            $pkgModuleTitles = array_map(function ($mid) use ($moduleTitlesById) {
                                                return $moduleTitlesById[$mid] ?? null;
                                            }, $pkgModuleIds);
                                            $pkgModuleTitles = array_filter($pkgModuleTitles);
                                            ?>
                                            <span class="text-muted text-truncate d-inline-block" style="max-width: 200px;" title="<?php echo htmlspecialchars(implode(', ', $pkgModuleTitles)); ?>">
                                                <?php echo htmlspecialchars(implode(', ', $pkgModuleTitles)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="text-muted text-truncate d-inline-block" style="max-width: 250px;" title="<?php echo htmlspecialchars($pkg->ozellikler ?? ''); ?>">
                                            <?php echo htmlspecialchars($pkg->ozellikler ?? '-'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo ($pkg->kullaniciya_goster_mi ?? 1) == 1
                                            ? '<span class="badge bg-blue-lt">Görünür</span>'
                                            : '<span class="badge bg-secondary text-secondary-fg">Gizli (Sadece Yönetici)</span>'; ?>
                                    </td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item btn-edit-paket" href="#"
                                                   data-id="<?php echo $id; ?>"
                                                   data-ad="<?php echo htmlspecialchars($pkg->ad); ?>"
                                                   data-fiyat="<?php echo htmlspecialchars($pkg->fiyat); ?>"
                                                   data-sure="<?php echo (int)$pkg->sure; ?>"
                                                   data-firma_hakki="<?php echo (int)$pkg->firma_hakki; ?>"
                                                   data-alt_kullanici_hakki="<?php echo (int)$pkg->alt_kullanici_hakki; ?>"
                                                   data-ozellikler="<?php echo htmlspecialchars($pkg->ozellikler ?? ''); ?>"
                                                   data-aktif_mi="<?php echo (int)$pkg->aktif_mi; ?>"
                                                   data-kullaniciya_goster_mi="<?php echo (int)($pkg->kullaniciya_goster_mi ?? 1); ?>">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item route-link" href="#"
                                                   data-page="abonelik-islemleri/paket-moduller&id=<?php echo $id; ?>">
                                                    <i class="ti ti-apps icon me-3"></i> Modülleri Düzenle
                                                </a>
                                                <a class="dropdown-item delete-paket" href="#" data-id="<?php echo $id; ?>">
                                                    <i class="ti ti-trash icon me-3"></i> Sil
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                                $i++;
                            endforeach;
                            ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Paket Ekle / Güncelle Modalı -->
<div class="modal modal-blur fade" id="paketModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(0,0,0,0.06); padding: 1.5rem 1.5rem 1rem;">
                <h5 class="modal-title font-weight-bold" id="paketModalTitle" style="font-size: 1.25rem;">
                    <i class="ti ti-package icon me-2 text-primary" style="font-size: 1.4rem;"></i>
                    <span>Yeni Paket Ekle</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" id="paketForm">
                <div class="modal-body" style="padding: 1.5rem;">
                    <!-- Gizli alanlar -->
                    <input type="hidden" name="id" id="paket_id" value="0">
                    <input type="hidden" name="action" value="savePackage">

                    <div class="mb-3">
                        <label class="form-label required font-weight-semibold">Paket Adı</label>
                        <input type="text" name="ad" id="paket_ad" class="form-control" style="border-radius: 8px; padding: 0.6rem 0.8rem;" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required font-weight-semibold">Fiyat (₺)</label>
                            <input type="number" step="0.01" min="0" name="fiyat" id="paket_fiyat" class="form-control" style="border-radius: 8px; padding: 0.6rem 0.8rem;" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required font-weight-semibold">Süre (Gün)</label>
                            <input type="number" min="1" name="sure" id="paket_sure" class="form-control" style="border-radius: 8px; padding: 0.6rem 0.8rem;" required value="30">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required font-weight-semibold">Firma Limiti</label>
                            <input type="number" min="1" name="firma_hakki" id="paket_firma_hakki" class="form-control" style="border-radius: 8px; padding: 0.6rem 0.8rem;" required value="30">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required font-weight-semibold">Alt Kullanıcı Limiti</label>
                            <input type="number" min="0" name="alt_kullanici_hakki" id="paket_alt_kullanici_hakki" class="form-control" style="border-radius: 8px; padding: 0.6rem 0.8rem;" required value="3">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="paket_kullaniciya_goster_mi" name="kullaniciya_goster_mi" value="1" checked>
                            <span class="form-check-label font-weight-semibold">Kullanıcılara Göster</span>
                        </label>
                        <div class="form-text">Kapatılırsa bu paket, müşterinin kendi satın alma ekranında listelenmez; sadece yönetici tarafından manuel atanabilir.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label font-weight-semibold">Özellikler (Açıklama)</label>
                        <textarea name="ozellikler" id="paket_ozellikler" rows="3" class="form-control" style="border-radius: 8px; padding: 0.6rem 0.8rem;" placeholder="Paket özelliklerini buraya yazabilirsiniz."></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required font-weight-semibold">Durum</label>
                        <select name="aktif_mi" id="paket_aktif_mi" class="form-select" style="border-radius: 8px; padding: 0.6rem 0.8rem;">
                            <option value="1">Aktif</option>
                            <option value="0">Pasif</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: none; padding: 0 1.5rem 1.5rem;">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button type="button" class="btn w-100" data-bs-dismiss="modal" style="border-radius: 8px; padding: 0.6rem; font-weight: 600; border: 1px solid #e6e6e6; background-color: #fff; color: #4e4e4e;">
                                    Vazgeç
                                </button>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-dark w-100" id="savePackageBtn" style="border-radius: 8px; padding: 0.6rem; font-weight: 600; background-color: #1d1d20; border-color: #1d1d20; color: #fff;">
                                    Paketi Kaydet
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add button handler
    $(document).on('click', '.btn-add-paket', function(e) {
        e.preventDefault();
        $('#paketForm')[0].reset();
        $('#paket_id').val('0');
        $('#paketModalTitle span').text('Yeni Paket Ekle');
        $('#paketModal').modal('show');
    });

    // Edit button handler
    $(document).on('click', '.btn-edit-paket', function(e) {
        e.preventDefault();
        $('#paketForm')[0].reset();

        let id = $(this).data('id');
        let ad = $(this).data('ad');
        let fiyat = $(this).data('fiyat');
        let sure = $(this).data('sure');
        let firma_hakki = $(this).data('firma_hakki');
        let alt_kullanici_hakki = $(this).data('alt_kullanici_hakki');
        let ozellikler = $(this).data('ozellikler');
        let aktif_mi = $(this).data('aktif_mi');
        let kullaniciya_goster_mi = $(this).data('kullaniciya_goster_mi');

        $('#paket_id').val(id);
        $('#paket_ad').val(ad);
        $('#paket_fiyat').val(fiyat);
        $('#paket_sure').val(sure);
        $('#paket_firma_hakki').val(firma_hakki);
        $('#paket_alt_kullanici_hakki').val(alt_kullanici_hakki);
        $('#paket_ozellikler').val(ozellikler);
        $('#paket_aktif_mi').val(aktif_mi);
        $('#paket_kullaniciya_goster_mi').prop('checked', String(kullaniciya_goster_mi) === '1');

        $('#paketModalTitle span').text('Abonelik Paketi Güncelle');
        $('#paketModal').modal('show');
    });

    // Submit handler
    $(document).on('submit', '#paketForm', function(e) {
        e.preventDefault();
        
        var form = $(this);
        form.validate({
            rules: {
                ad: { required: true },
                fiyat: { required: true, number: true },
                sure: { required: true, digits: true },
                firma_hakki: { required: true, digits: true },
                alt_kullanici_hakki: { required: true, digits: true }
            },
            messages: {
                ad: { required: "Paket adı boş bırakılamaz." },
                fiyat: { required: "Fiyat boş bırakılamaz.", number: "Geçerli bir sayı giriniz." },
                sure: { required: "Süre boş bırakılamaz.", digits: "Süre tam sayı olmalıdır." },
                firma_hakki: { required: "Firma limiti boş bırakılamaz.", digits: "Tam sayı olmalıdır." },
                alt_kullanici_hakki: { required: "Alt kullanıcı limiti boş bırakılamaz.", digits: "Tam sayı olmalıdır." }
            }
        });

        if (!form.valid()) {
            return;
        }

        let formData = new FormData(form[0]);

        fetch('/api/abonelik-islemleri/paketler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            let title = data.status === "success" ? "Başarılı" : "Hata";
            Swal.fire({
                title: title,
                text: data.message,
                icon: data.status
            }).then(() => {
                if (data.status === "success") {
                    $('#paketModal').modal('hide');
                    // Reload to update list
                    window.location.reload();
                }
            });
        })
        .catch(error => {
            Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
        });
    });

    // Delete handler
    $(document).on("click", ".delete-paket", function (e) {
        e.preventDefault();
        let action = "deletePackage";
        let confirmMessage = "Seçilen abonelik paketi silinecektir! Bu paketi kullanan abonelikler etkilenebilir.";
        let url = "/api/abonelik-islemleri/paketler.php";
        
        deleteRecord(this, action, confirmMessage, url);
    });
});
</script>
