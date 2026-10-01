<?php
require_once "App/Helper/helper.php";
require_once "Model/Auths.php";
require_once "Model/AbonelikPaketleriModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;

$perm->checkAuthorize("aboneler_paketleri");

$authObj = new Auths();
$paketModel = new AbonelikPaketleriModel();

$id = Security::decrypt($_GET['id'] ?? '') ?? 0;
if (!isset($_GET['id']) || $id == 0) {
    header('Location: /abonelik-paketleri');
    exit();
}

$pkg = $paketModel->find($id);
if (!$pkg) {
    header('Location: /abonelik-paketleri');
    exit();
}

$pkg_id_encrypted = Security::encrypt($pkg->id);
$modules = $authObj->auths();
$selected_ids = array_filter(explode(',', $pkg->modul_auth_ids ?? ''));
$is_unlimited = empty($selected_ids);
?>

<div class="container-xl mt-1" id="paketModullerPage">

    <!-- Page Header (Standart Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-apps" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Paket Modülleri: <?php echo htmlspecialchars($pkg->ad); ?>
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Bu pakete dahil edilecek ve hariç tutulacak yetki modüllerini yapılandırın
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="#" class="btn btn-sm btn-outline-secondary route-link" data-page="abonelik-islemleri/paketler" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                        <i class="ti ti-arrow-left icon me-1"></i> Paket Listesine Dön
                    </a>
                    <button type="button" class="btn btn-sm btn-primary shadow-sm" id="modulesSave" style="height: 32px; padding: 4px 12px; font-size: 12.5px;">
                        <i class="ti ti-device-floppy icon me-1"></i> Yetkileri Kaydet
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
    html:not([data-bs-theme="dark"]) #paketModullerPage .module-main-card {
        background: #ffffff !important;
        border: 1px solid #dbe3ec !important;
        box-shadow: 0 4px 14px rgba(15, 23, 42, 0.07) !important;
        border-radius: 12px !important;
        overflow: hidden;
    }

    .accordion-button:not(.collapsed) {
        background-color: transparent !important;
        color: inherit !important;
        box-shadow: none !important;
    }

    .accordion-button::after {
        background-size: 1rem;
        transition: transform 0.25s ease;
    }

    .accordion-item {
        transition: all 0.2s ease;
        border: 1px solid #e2e8f0 !important;
        border-radius: 10px !important;
    }

    .accordion-item:hover {
        border-color: #cbd5e1 !important;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04) !important;
    }

    .form-selectgroup-label {
        transition: all 0.15s ease;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
    }

    .form-selectgroup-input:checked + .form-selectgroup-label {
        border-color: #0284c7 !important;
        background-color: #f0f9ff !important;
    }

    .selection-counter {
        min-width: 55px;
        text-align: center;
        transition: all 0.2s ease;
    }

    .accordion-body {
        max-height: 600px;
        overflow-y: auto;
        scrollbar-width: thin;
    }

    [data-bs-theme="dark"] #paketModullerPage .module-main-card {
        background: #182433 !important;
        border-color: rgba(255, 255, 255, 0.08) !important;
        box-shadow: 0 3px 12px rgba(0, 0, 0, .22) !important;
    }

    [data-bs-theme="dark"] .accordion-item {
        background-color: #1e293b !important;
        border-color: #334155 !important;
    }

    [data-bs-theme="dark"] .form-selectgroup-label {
        background-color: #182433 !important;
        border-color: #334155 !important;
    }

    [data-bs-theme="dark"] .form-selectgroup-input:checked + .form-selectgroup-label {
        background-color: rgba(59, 130, 246, 0.12) !important;
        border-color: #3b82f6 !important;
    }
    </style>

    <div class="row row-cards">
        <div class="col-12">
            <div class="card module-main-card">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3 border-0">
                    <div class="d-flex align-items-center gap-2">
                        <div class="avatar avatar-sm rounded-2 bg-primary-lt text-primary" style="width: 32px; height: 32px;">
                            <i class="ti ti-apps" style="font-size: 18px;"></i>
                        </div>
                        <div>
                            <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Pakete Dahil Modüller</h4>
                            <p class="text-muted mb-0" style="font-size: 11.5px; line-height: 1.2;">Müşterinin bu pakette erişebileceği modül ve alt yetkileri seçiniz</p>
                        </div>
                    </div>

                    <!-- Toolbar & Arama -->
                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <div class="input-icon" id="moduleTreeToolbarLeft" style="min-width: 200px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="moduleSearch" class="form-control form-control-sm" placeholder="Modül veya yetki ara..." style="height: 32px; border-radius: 6px;">
                        </div>

                        <div class="d-flex align-items-center gap-2" id="moduleTreeToolbarRight">
                            <div class="btn-group">
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="expandAll" style="height: 32px; padding: 4px 8px; font-size: 11.5px;">
                                    <i class="ti ti-arrows-maximize me-1"></i> Tümünü Aç
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="collapseAll" style="height: 32px; padding: 4px 8px; font-size: 11.5px;">
                                    <i class="ti ti-arrows-minimize me-1"></i> Tümünü Kapat
                                </button>
                            </div>
                            <div class="form-check mb-0 ms-2">
                                <input class="form-check-input" type="checkbox" id="checkAll" style="width: 16px; height: 16px; cursor: pointer;">
                                <label class="form-check-label fw-bold small ms-1" for="checkAll" style="cursor: pointer;">Tümünü Seç</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3 pt-0">
                    <form action="" id="modulesForm">
                        <input type="hidden" name="action" value="saveModules">
                        <input type="hidden" name="paket_id" value="<?php echo $pkg_id_encrypted; ?>">

                        <!-- Sınırsız Modül Switch Kartı -->
                        <div class="card border rounded-3 p-3 mb-3 bg-light-subtle">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ti ti-infinity text-primary" style="font-size: 22px;"></i>
                                    <div>
                                        <div class="fw-bold text-dark">Tüm Modüllere İzin Ver (Sınırsız / Kısıtlama Yok)</div>
                                        <div class="text-secondary small">Bu seçenek aktif olduğunda paket, sistemdeki mevcut ve gelecekte eklenecek tüm modüllere tam erişim sağlar.</div>
                                    </div>
                                </div>
                                <label class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="unlimited_modules" name="unlimited_modules" value="1" <?php echo $is_unlimited ? 'checked' : ''; ?> style="cursor: pointer; transform: scale(1.15);">
                                </label>
                            </div>
                        </div>

                        <!-- Modül Ağacı -->
                        <div id="moduleTreeWrapper" style="<?php echo $is_unlimited ? 'display:none;' : ''; ?>">
                            <div class="accordion" id="accordion-modules">
                                <?php
                                foreach ($modules as $module) {
                                    $sub_modules = $authObj->subAuths($module->id);
                                    $parent_selected = in_array($module->id, $selected_ids);
                                    $checked_count = 0;
                                    foreach ($sub_modules as $sub) {
                                        if ($parent_selected || in_array($sub->id, $selected_ids)) {
                                            $checked_count++;
                                        }
                                    }

                                    $main_checked = $parent_selected ? 'checked' : '';
                                    ?>
                                    <div class="accordion-item mb-2 overflow-hidden">
                                        <div class="accordion-header d-flex align-items-center bg-white" id="heading-<?php echo $module->id; ?>">
                                            <div class="form-check mb-0 me-2 ms-3">
                                                <input class="form-check-input main-category-check" type="checkbox"
                                                    name="modules[]" value="<?php echo $module->id; ?>"
                                                    id="main_module_<?php echo $module->id; ?>" <?php echo $main_checked; ?>
                                                    data-group="group-<?php echo $module->id; ?>"
                                                    style="width: 18px; height: 18px; cursor: pointer;">
                                            </div>
                                            <button
                                                class="accordion-button collapsed flex-fill py-2.5 px-2 bg-transparent text-dark shadow-none"
                                                type="button" data-bs-toggle="collapse"
                                                data-bs-target="#collapse-<?php echo $module->id; ?>" aria-expanded="false"
                                                aria-controls="collapse-<?php echo $module->id; ?>">
                                                <div class="d-flex flex-column text-start">
                                                    <div class="fw-bold text-dark" style="font-size: 14px;"><?php echo htmlspecialchars($module->title); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($module->description ?? ''); ?></div>
                                                </div>
                                                <div class="ms-auto me-3 d-flex align-items-center">
                                                    <span class="badge bg-primary-lt px-2 py-1 rounded-pill fw-semibold selection-counter"
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
                                            <div class="accordion-body bg-light-subtle pt-2 pb-3 border-top">
                                                <div class="row g-2 group-<?php echo $module->id; ?>">
                                                    <?php foreach ($sub_modules as $sub_module):
                                                        $sub_checked = ($parent_selected || in_array($sub_module->id, $selected_ids)) ? 'checked' : '';
                                                        ?>
                                                        <div class="col-12 col-md-6 col-lg-4">
                                                            <label class="form-selectgroup-item w-100 mb-0">
                                                                <input type="checkbox" name="modules[]" <?php echo $sub_checked; ?>
                                                                    value="<?php echo $sub_module->id; ?>"
                                                                    class="form-selectgroup-input sub-module-check"
                                                                    data-parent-counter="counter-<?php echo $module->id; ?>"
                                                                    data-parent-main="main_module_<?php echo $module->id; ?>">
                                                                <div class="form-selectgroup-label d-flex align-items-center p-2.5 bg-white">
                                                                    <div class="me-2.5">
                                                                        <span class="form-selectgroup-check"></span>
                                                                    </div>
                                                                    <div class="form-selectgroup-label-content d-flex text-start">
                                                                        <div>
                                                                            <div class="fw-bold text-dark mb-0.5" style="font-size: 13px;">
                                                                                <?php echo htmlspecialchars($sub_module->title); ?>
                                                                            </div>
                                                                            <div class="text-muted small lh-sm" style="font-size: 11px;">
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
        var unlimited = $('#unlimited_modules').is(':checked');
        $('#moduleTreeWrapper').toggle(!unlimited);
        $('#moduleTreeToolbarLeft, #moduleTreeToolbarRight').toggle(!unlimited);
    }
    toggleModuleTree();
    $('#unlimited_modules').on('change', toggleModuleTree);

    function updateParentState(mainId, group) {
        var mainCheckbox = document.getElementById(mainId);
        if (!mainCheckbox) return;
        var subChecks = $('.' + group).find('.sub-module-check');
        var total = subChecks.length;
        if (total === 0) return;
        
        var checked = subChecks.filter(':checked').length;
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

    $(document).on('change', '.sub-module-check', function() {
        var mainId = $(this).data('parent-main');
        var group = $(this).closest('.row').attr('class').split(' ').find(function(c) { return c.startsWith('group-'); });
        updateParentState(mainId, group);
    });

    $(document).on('change', '.main-category-check', function() {
        var group = $(this).data('group');
        var checked = $(this).is(':checked');
        this.indeterminate = false;
        $('.' + group).find('.sub-module-check').prop('checked', checked);
        var counterId = $('#counter-' + $(this).val());
        var total = $('.' + group).find('.sub-module-check').length;
        var checkedCount = $('.' + group).find('.sub-module-check:checked').length;
        counterId.text(checkedCount + ' / ' + total);
    });

    $('.main-category-check').each(function() {
        var group = $(this).data('group');
        updateParentState(this.id, group);
    });

    $('#expandAll').on('click', function() {
        $('#accordion-modules .accordion-collapse').addClass('show');
    });
    $('#collapseAll').on('click', function() {
        $('#accordion-modules .accordion-collapse').removeClass('show');
    });

    $('#checkAll').on('change', function() {
        var checked = $(this).is(':checked');
        $('#moduleTreeWrapper input[type=checkbox]').prop('checked', checked).prop('indeterminate', false);
        $('.main-category-check').each(function() {
            var counterId = $('#counter-' + $(this).val());
            var group = $(this).data('group');
            var total = $('.' + group).find('.sub-module-check').length;
            var checkedCount = $('.' + group).find('.sub-module-check:checked').length;
            counterId.text(checkedCount + ' / ' + total);
        });
    });

    $('#moduleSearch').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#accordion-modules .accordion-item').each(function() {
            var match = $(this).text().toLowerCase().indexOf(value) > -1;
            $(this).toggle(match);
        });
    });

    $('#modulesSave').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Kaydediliyor...');

        var formData = new FormData($('#modulesForm')[0]);

        fetch('/api/abonelik-islemleri/paketler.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            var title = data.status === "success" ? "Başarılı" : "Hata";
            Swal.fire({
                title: title,
                text: data.message,
                icon: data.status
            });
        })
        .catch(function(error) {
            Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
        })
        .finally(function() {
            btn.prop('disabled', false).html('<i class="ti ti-device-floppy icon me-1"></i> Yetkileri Kaydet');
        });
    });
});
</script>
