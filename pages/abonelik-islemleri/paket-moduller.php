<?php

require_once "Model/Auths.php";
require_once "Model/AbonelikPaketleriModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;

$perm->checkAuthorize("aboneler_paketleri");

$authObj = new Auths();
$paketModel = new AbonelikPaketleriModel();

$id = Security::decrypt($_GET['id']) ?? 0;
if (!isset($_GET['id']) || $id == 0) {
    header('Location: index.php?p=abonelik-islemleri/paketler');
    exit();
}

$pkg = $paketModel->find($id);
if (!$pkg) {
    header('Location: index.php?p=abonelik-islemleri/paketler');
    exit();
}

$pkg_id_encrypted = Security::encrypt($pkg->id);
$modules = $authObj->auths();
$selected_ids = array_filter(explode(',', $pkg->modul_auth_ids ?? ''));
$is_unlimited = empty($selected_ids);
?>

<div class="page-wrapper">
    <!-- Page header -->
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row g-2 align-items-center">
                <div class="col">
                    <h2 class="page-title">
                        Paket Modülleri: <?php echo htmlspecialchars($pkg->ad); ?>
                    </h2>
                </div>

                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-outline-secondary route-link" data-page="abonelik-islemleri/paketler">
                        <i class="ti ti-list icon me-2"></i>
                        Listeye Dön
                    </button>
                </div>
                <div class="col-auto ms-auto d-print-none">
                    <button type="button" class="btn btn-primary" id="modulesSave">
                        <i class="ti ti-device-floppy icon me-2"></i>
                        Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .accordion-button:not(.collapsed) {
        background-color: transparent !important;
        color: inherit !important;
        box-shadow: none !important;
    }

    .accordion-button::after {
        background-size: 1rem;
        transition: transform 0.3s ease;
    }

    .accordion-item {
        transition: all 0.3s ease;
        border: 1px solid rgba(0, 0, 0, .05) !important;
    }

    .accordion-item:hover {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .08) !important;
    }

    .form-selectgroup-label {
        transition: all 0.2s ease;
        border-radius: 8px;
    }

    .form-selectgroup-input:checked+.form-selectgroup-label {
        border-color: var(--tblr-primary) !important;
        background-color: rgba(var(--tblr-primary-rgb), 0.03);
    }

    .selection-counter {
        min-width: 60px;
        text-align: center;
        transition: all 0.3s ease;
    }

    .bg-light-lt {
        background-color: #f8fafc !important;
    }

    .accordion-body {
        max-height: 600px;
        overflow-y: auto;
        scrollbar-width: thin;
    }
    </style>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Pakete Dahil Modüller</h3>
                    <div class="col ms-5 me-5" id="moduleTreeToolbarLeft">
                        <div class="input-icon">
                            <span class="input-icon-addon">
                                <i class="ti ti-search icon text-primary"></i>
                            </span>
                            <input type="text" id="moduleSearch" class="form-control form-control-rounded"
                                placeholder="Modül veya alt yetki ara...">
                        </div>
                    </div>
                    <div class="col-auto ms-auto d-flex align-items-center" id="moduleTreeToolbarRight">
                        <div class="btn-group me-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="expandAll">
                                <i class="ti ti-arrows-maximize icon me-1"></i> Tümünü Aç
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="collapseAll">
                                <i class="ti ti-arrows-minimize icon me-1"></i> Tümünü Kapat
                            </button>
                        </div>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                            <label class="form-check-label fw-bold" for="checkAll">Tümünü Seç</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form action="" id="modulesForm">
                        <input type="hidden" name="action" value="saveModules">
                        <input type="hidden" name="paket_id" value="<?php echo $pkg_id_encrypted; ?>">

                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="unlimited_modules"
                                    name="unlimited_modules" value="1" <?php echo $is_unlimited ? 'checked' : ''; ?>>
                                <span class="form-check-label font-weight-semibold">Tüm modüllere izin ver (kısıtlama yok)</span>
                            </label>
                        </div>

                        <div id="moduleTreeWrapper" style="<?php echo $is_unlimited ? 'display:none;' : ''; ?>">
                            <div class="accordion" id="accordion-modules">
                                <?php
                                foreach ($modules as $module) {
                                    $sub_modules = $authObj->subAuths($module->id);

                                    // Üst modülün kendi id'si seçiliyse (tam modül erişimi), Model/Auths.php
                                    // BFS ile tüm alt yetkileri zaten otomatik kapsıyor; arayüzde de tutarlı
                                    // görünmesi için tüm alt yetkiler işaretli gösterilir.
                                    $parent_selected = in_array($module->id, $selected_ids);
                                    $checked_count = 0;
                                    foreach ($sub_modules as $sub) {
                                        if ($parent_selected || in_array($sub->id, $selected_ids)) {
                                            $checked_count++;
                                        }
                                    }

                                    $main_checked = $parent_selected ? 'checked' : '';
                                    ?>
                                    <div class="accordion-item mb-3 border-0 shadow-sm rounded-3 overflow-hidden">
                                        <div class="accordion-header d-flex align-items-center bg-white"
                                            id="heading-<?php echo $module->id; ?>">
                                            <div class="form-check mb-0 me-2 ms-3">
                                                <input class="form-check-input main-category-check" type="checkbox"
                                                    name="modules[]" value="<?php echo $module->id; ?>"
                                                    id="main_module_<?php echo $module->id; ?>" <?php echo $main_checked; ?>
                                                    data-group="group-<?php echo $module->id; ?>">
                                            </div>
                                            <button
                                                class="accordion-button collapsed flex-fill py-3 px-2 bg-transparent text-dark shadow-none"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapse-<?php echo $module->id; ?>" aria-expanded="false"
                                                aria-controls="collapse-<?php echo $module->id; ?>">
                                                <div class="d-flex flex-column text-start">
                                                    <div class="h4 mb-0 fw-bold"><?php echo htmlspecialchars($module->title); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($module->description ?? ''); ?></div>
                                                </div>
                                                <div class="ms-auto me-3 d-flex align-items-center">
                                                    <span
                                                        class="badge bg-primary-lt px-2 py-1 rounded-pill fw-medium selection-counter"
                                                        id="counter-<?php echo $module->id; ?>"
                                                        data-total="<?php echo count($sub_modules); ?>">
                                                        <?php echo $checked_count; ?> / <?php echo count($sub_modules); ?>
                                                    </span>
                                                </div>
                                            </button>
                                        </div>
                                        <?php if (count($sub_modules) > 0): ?>
                                        <div id="collapse-<?php echo $module->id; ?>" class="accordion-collapse collapse"
                                            aria-labelledby="heading-<?php echo $module->id; ?>">
                                            <div class="accordion-body bg-light-lt pt-0 border-top">
                                                <div class="row g-3 mt-1 group-<?php echo $module->id; ?>">
                                                    <?php foreach ($sub_modules as $sub_module):
                                                        $sub_checked = ($parent_selected || in_array($sub_module->id, $selected_ids)) ? 'checked' : '';
                                                        ?>
                                                        <div class="col-12 col-md-6 col-lg-4">
                                                            <label class="form-selectgroup-item w-100">
                                                                <input type="checkbox" name="modules[]" <?php echo $sub_checked; ?>
                                                                    value="<?php echo $sub_module->id; ?>"
                                                                    class="form-selectgroup-input sub-module-check"
                                                                    data-parent-counter="counter-<?php echo $module->id; ?>"
                                                                    data-parent-main="main_module_<?php echo $module->id; ?>">
                                                                <div class="form-selectgroup-label d-flex align-items-center p-3 bg-white border-2">
                                                                    <div class="me-3">
                                                                        <span class="form-selectgroup-check"></span>
                                                                    </div>
                                                                    <div class="form-selectgroup-label-content d-flex text-start">
                                                                        <div>
                                                                            <div class="font-weight-bold text-dark mb-1">
                                                                                <?php echo htmlspecialchars($sub_module->title); ?>
                                                                            </div>
                                                                            <div class="text-muted small lh-sm">
                                                                                <?php echo htmlspecialchars($sub_module->description ?? ''); ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function toggleModuleTree() {
        let unlimited = $('#unlimited_modules').is(':checked');
        $('#moduleTreeWrapper').toggle(!unlimited);
        $('#moduleTreeToolbarLeft, #moduleTreeToolbarRight').toggle(!unlimited);
    }
    toggleModuleTree();
    $('#unlimited_modules').on('change', toggleModuleTree);

    // Üst modül checkbox'ı: sadece "modülün tamamına izin ver" anlamına gelir (paket ∩ rol
    // kesişiminde üst modül seçilirse tüm alt yetkiler otomatik dahil edilir, bkz. Model/Auths.php).
    // Bu yüzden bazı alt yetkiler işaretliyken üst kutu asla otomatik "checked" yapılmaz;
    // sadece kısmi seçimi göstermek için "indeterminate" (tire) durumuna alınır.
    function updateParentState(mainId, group) {
        let mainCheckbox = document.getElementById(mainId);
        if (!mainCheckbox) return;
        let subChecks = $('.' + group).find('.sub-module-check');
        let total = subChecks.length;
        if (total === 0) {
            // Alt yetkisi olmayan modüllerde dokunmadan bırak, sunucudan gelen durum geçerli
            return;
        }
        let checked = subChecks.filter(':checked').length;

        $('#counter-' + mainCheckbox.value).text(checked + ' / ' + total);

        if (checked === 0) {
            mainCheckbox.checked = false;
            mainCheckbox.indeterminate = false;
        } else if (checked === total) {
            mainCheckbox.checked = true;
            mainCheckbox.indeterminate = false;
        } else {
            mainCheckbox.checked = false;
            mainCheckbox.indeterminate = true;
        }
    }

    // Alt yetki değişince üst kategori sayacını ve tire/checkbox durumunu güncelle
    $(document).on('change', '.sub-module-check', function() {
        let mainId = $(this).data('parent-main');
        let group = $(this).closest('.row').attr('class').split(' ').find(c => c.startsWith('group-'));
        updateParentState(mainId, group);
    });

    // Üst kategori değişince tüm alt yetkileri işaretle/kaldır (tam modül seçimi)
    $(document).on('change', '.main-category-check', function() {
        let group = $(this).data('group');
        let checked = $(this).is(':checked');
        this.indeterminate = false;
        $('.' + group).find('.sub-module-check').prop('checked', checked);
        let counterId = $('#counter-' + $(this).val());
        let total = $('.' + group).find('.sub-module-check').length;
        let checkedCount = $('.' + group).find('.sub-module-check:checked').length;
        counterId.text(checkedCount + ' / ' + total);
    });

    // Sayfa yüklenirken kısmi seçili modüllerde üst kutuyu tire (indeterminate) durumuna al
    $('.main-category-check').each(function() {
        let group = $(this).data('group');
        updateParentState(this.id, group);
    });

    $('#expandAll').on('click', function() {
        $('#accordion-modules .accordion-collapse').addClass('show');
    });
    $('#collapseAll').on('click', function() {
        $('#accordion-modules .accordion-collapse').removeClass('show');
    });

    $('#checkAll').on('change', function() {
        let checked = $(this).is(':checked');
        $('#moduleTreeWrapper input[type=checkbox]').prop('checked', checked).prop('indeterminate', false);
        $('.main-category-check').each(function() {
            let counterId = $('#counter-' + $(this).val());
            let group = $(this).data('group');
            let total = $('.' + group).find('.sub-module-check').length;
            let checkedCount = $('.' + group).find('.sub-module-check:checked').length;
            counterId.text(checkedCount + ' / ' + total);
        });
    });

    $('#moduleSearch').on('keyup', function() {
        let value = $(this).val().toLowerCase();
        $('#accordion-modules .accordion-item').each(function() {
            let match = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(match);
        });
    });

    $('#modulesSave').on('click', function() {
        let formData = new FormData($('#modulesForm')[0]);

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
            });
        })
        .catch(error => {
            Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
        });
    });
});
</script>
