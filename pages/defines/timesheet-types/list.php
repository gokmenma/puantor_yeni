<?php
require_once "App/Helper/helper.php";
require_once "Model/PuantajTuruModel.php";

use App\Helper\Helper;
use App\Helper\Security;

// Yetki kontrolü
$perm->checkAuthorize("timesheet_types_list");

$PuantajTuru = new PuantajTuruModel();
$types = $PuantajTuru->all();

$operants = [
    '' => 'Seçiniz',
    '+' => '+ (Ekle)',
    '-' => '- (Çıkar)',
    '*' => '* (Çarp)',
    '/' => '/ (Böl)'
];

$turOptions = [
    '' => 'Seçiniz',
    'Normal Çalışma' => 'Normal Çalışma',
    'Fazla Çalışma' => 'Fazla Çalışma',
    'Saatlik' => 'Saatlik',
    'Ücretli İzin' => 'Ücretli İzin',
    'Ücretsiz' => 'Ücretsiz'
];
?>
<div class="container-xl mt-3">
    <div class="alert alert-info bg-white alert-dismissible d-flex">
        <div class="d-flex">
            <div>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                    stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                    class="icon alert-icon text-info">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                    <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                    <path d="M12 9h.01"></path>
                    <path d="M11 12h1v4h1"></path>
                </svg>
            </div>
            <div class="ms-3">
                <h4 class="alert-title">Puantaj Türü Tanımlama!</h4>
                <div class="text-secondary">Çalışanlarınızın puantaj (devam/izin/fazla mesai) kayıtlarında kullanılacak puantaj kodlarını, çalışma saatlerini, arka plan ve font renklerini buradan yönetebilirsiniz.</div>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Puantaj Türleri</h3>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#timesheetTypeModal" id="btnNewTimesheetType">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table text-nowrap" id="timesheet-types-table">
                        <thead>
                            <tr>
                                <th style="width:7%">Sıra</th>
                                <th>Kod</th>
                                <th>Puantaj Adı</th>
                                <th>Puantaj Saati</th>
                                <th>Operant</th>
                                <th>Eklenecek Saat</th>
                                <th>Türü</th>
                                <th>İzin/Rapor</th>
                                <th>Kesinti?</th>
                                <th>BY Kesinti?</th>
                                <th>Durum</th>
                                <th style="width:7%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($types as $type):
                                $id = Security::encrypt($type->id);
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td>
                                        <span class="badge px-3 py-2 fw-bold" style="color: <?php echo htmlspecialchars($type->FontRengi); ?>; background-color: <?php echo htmlspecialchars($type->ArkaPlanRengi); ?>; border: 1px solid rgba(0,0,0,0.1);">
                                            <?php echo htmlspecialchars($type->PuantajKod); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a class="btn btn-ghost-info btn-sm btn-edit-timesheet-type" href="#"
                                            data-id="<?php echo $id ?>"
                                            data-name="<?php echo htmlspecialchars($type->PuantajAdi) ?>"
                                            data-code="<?php echo htmlspecialchars($type->PuantajKod) ?>"
                                            data-hours="<?php echo htmlspecialchars($type->PuantajSaati ?? '') ?>"
                                            data-operant="<?php echo htmlspecialchars($type->operant ?? '') ?>"
                                            data-added-hours="<?php echo htmlspecialchars($type->EklenecekSaat ?? '') ?>"
                                            data-type="<?php echo htmlspecialchars($type->Turu) ?>"
                                            data-bg="<?php echo htmlspecialchars($type->ArkaPlanRengi) ?>"
                                            data-font="<?php echo htmlspecialchars($type->FontRengi) ?>"
                                            data-active="<?php echo htmlspecialchars($type->isActive) ?>"
                                            data-izin="<?php echo htmlspecialchars($type->IzinRapor) ?>"
                                            data-deductable="<?php echo htmlspecialchars($type->is_deductable ?? 0) ?>"
                                            data-beyaz-yaka-kesinti="<?php echo htmlspecialchars($type->beyaz_yaka_kesinti ?? 0) ?>">
                                            <?php echo htmlspecialchars($type->PuantajAdi); ?>
                                        </a>
                                    </td>
                                    <td><?php echo ($type->PuantajSaati !== null) ? htmlspecialchars($type->PuantajSaati) : '-'; ?></td>
                                    <td class="text-center"><span class="badge bg-secondary-lt"><?php echo htmlspecialchars($type->operant ?? '-'); ?></span></td>
                                    <td><?php echo ($type->EklenecekSaat !== null) ? htmlspecialchars($type->EklenecekSaat) : '-'; ?></td>
                                    <td><span class="badge bg-blue-lt"><?php echo htmlspecialchars($type->Turu); ?></span></td>
                                    <td>
                                        <?php if ($type->IzinRapor == 1): ?>
                                            <span class="badge bg-success-lt">Evet</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger-lt">Hayır</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($type->is_deductable ?? 0) == 1): ?>
                                            <span class="badge bg-warning-lt">Evet</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt">Hayır</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($type->beyaz_yaka_kesinti ?? 0) == 1): ?>
                                            <span class="badge bg-danger-lt">Evet</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-lt">Hayır</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($type->isActive == 1): ?>
                                            <span class="badge bg-success">Aktif</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Pasif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item btn-edit-timesheet-type" href="#"
                                                    data-id="<?php echo $id ?>"
                                                    data-name="<?php echo htmlspecialchars($type->PuantajAdi) ?>"
                                                    data-code="<?php echo htmlspecialchars($type->PuantajKod) ?>"
                                                    data-hours="<?php echo htmlspecialchars($type->PuantajSaati ?? '') ?>"
                                                    data-operant="<?php echo htmlspecialchars($type->operant ?? '') ?>"
                                                    data-added-hours="<?php echo htmlspecialchars($type->EklenecekSaat ?? '') ?>"
                                                    data-type="<?php echo htmlspecialchars($type->Turu) ?>"
                                                    data-bg="<?php echo htmlspecialchars($type->ArkaPlanRengi) ?>"
                                                    data-font="<?php echo htmlspecialchars($type->FontRengi) ?>"
                                                    data-active="<?php echo htmlspecialchars($type->isActive) ?>"
                                                    data-izin="<?php echo htmlspecialchars($type->IzinRapor) ?>"
                                                    data-deductable="<?php echo htmlspecialchars($type->is_deductable ?? 0) ?>"
                                                    data-beyaz-yaka-kesinti="<?php echo htmlspecialchars($type->beyaz_yaka_kesinti ?? 0) ?>">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-timesheet-type" href="#"
                                                    data-id="<?php echo $id ?>">
                                                    <i class="ti ti-trash icon me-3"></i> Sil
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Ekle/Düzenle Formu -->
<div class="modal modal-blur fade" id="timesheetTypeModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="timesheetModalTitle"><i class="ti ti-calendar-time text-primary me-2"></i>Yeni Puantaj Türü</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="timesheetTypeForm">
                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="action" value="saveTimesheetType">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Puantaj Türü Adı</label>
                            <input type="text" name="puantaj_name" id="puantaj_name" class="form-control" placeholder="Örn: Normal Çalışma, Raporlu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Puantaj Kodu</label>
                            <input type="text" name="puantaj_code" id="puantaj_code" class="form-control" placeholder="Örn: X, R, FM">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Puantaj Saati</label>
                            <input type="number" step="0.01" name="puantaj_hours" id="puantaj_hours" class="form-control" placeholder="Örn: 8.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Operant</label>
                            <select name="operant" id="operant" class="form-select select2-modal">
                                <?php foreach ($operants as $val => $lbl): ?>
                                    <option value="<?php echo $val; ?>"><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Eklenecek Saat</label>
                            <input type="number" step="0.01" name="added_hours" id="added_hours" class="form-control" placeholder="Örn: 1.50">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label required">Türü</label>
                            <select name="type" id="type" class="form-select select2-modal">
                                <?php foreach ($turOptions as $val => $lbl): ?>
                                    <option value="<?php echo $val; ?>"><?php echo $lbl; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Arka Plan Rengi</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="background_color_picker" 
                                    value="#ffffff" style="max-width: 55px; padding: 2px;">
                                <input type="text" name="background_color" id="background_color" class="form-control" 
                                    value="#ffffff" placeholder="#ffffff">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Font Rengi</label>
                            <div class="input-group">
                                <input type="color" class="form-control form-control-color" id="font_color_picker" 
                                    value="#000000" style="max-width: 55px; padding: 2px;">
                                <input type="text" name="font_color" id="font_color" class="form-control" 
                                    value="#000000" placeholder="#000000">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3 mt-4">
                        <div class="col-md-4">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="isActive" id="isActive" checked>
                                <span class="form-check-label">Aktif</span>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="IzinRapor" id="IzinRapor">
                                <span class="form-check-label">İzin / Rapor Süreci</span>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_deductable" id="is_deductable">
                                <span class="form-check-label">Bordrodan Kesinti Yapılsın</span>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="beyaz_yaka_kesinti" id="beyaz_yaka_kesinti">
                                <span class="form-check-label">Beyaz Yaka Maaş Kesintisi</span>
                            </label>
                            <div class="text-muted" style="font-size:0.75rem">Aylık maaşlı personelde bu gün maaştan düşülür</div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary ms-auto" id="saveTimesheetType">
                    <i class="ti ti-device-floppy icon me-2"></i>Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.createDataTable) {
        window.createDataTable('#timesheet-types-table', {
            pageLength: 25
        });
    }
});
</script>
