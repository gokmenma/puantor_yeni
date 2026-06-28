<?php
require_once "App/Helper/helper.php";
require_once "Model/NationalHolidaysModel.php";

use App\Helper\Helper;
use App\Helper\Security;

// Yetki kontrolü
$perm->checkAuthorize("national_holidays_list");

$NationalHolidays = new NationalHolidaysModel();
$holidays = $NationalHolidays->all();
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
                <h4 class="alert-title">Resmi Tatil Tanımlama!</h4>
                <div class="text-secondary">Firmanız için resmi tatil günleri tanımlayabilir ve puantaj hesaplamalarında bu günleri otomatik olarak tatil olarak dikkate alabilirsiniz!</div>
            </div>
        </div>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
    </div>
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Resmi Tatil Listesi</h3>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nationalHolidayModal" id="btnNewHoliday">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table text-nowrap" id="national-holidays-table">
                        <thead>
                            <tr>
                                <th style="width:7%">Sıra</th>
                                <th>Tatil Adı</th>
                                <th>Tatil Tarihi</th>
                                <th>Açıklama</th>
                                <th style="width:7%">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($holidays as $holiday):
                                $id = Security::encrypt($holiday->id);
                                $formatted_date = !empty($holiday->holiday_date) ? date('d.m.Y', strtotime($holiday->holiday_date)) : '';
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i++; ?></td>
                                    <td>
                                        <a class="btn btn-ghost-info btn-sm btn-edit-holiday" href="#"
                                            data-id="<?php echo $id ?>"
                                            data-name="<?php echo htmlspecialchars($holiday->holiday_name) ?>"
                                            data-date="<?php echo $formatted_date ?>"
                                            data-desc="<?php echo htmlspecialchars($holiday->description) ?>">
                                            <?php echo htmlspecialchars($holiday->holiday_name); ?>
                                        </a>
                                    </td>
                                    <td><?php echo $formatted_date; ?></td>
                                    <td><?php echo htmlspecialchars($holiday->description); ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top"
                                                data-bs-toggle="dropdown">İşlem</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a class="dropdown-item btn-edit-holiday" href="#"
                                                    data-id="<?php echo $id ?>"
                                                    data-name="<?php echo htmlspecialchars($holiday->holiday_name) ?>"
                                                    data-date="<?php echo $formatted_date ?>"
                                                    data-desc="<?php echo htmlspecialchars($holiday->description) ?>">
                                                    <i class="ti ti-edit icon me-3"></i> Güncelle
                                                </a>
                                                <a class="dropdown-item delete-national-holiday" href="#"
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
<div class="modal modal-blur fade" id="nationalHolidayModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="holidayModalTitle"><i class="ti ti-calendar-event text-primary me-2"></i>Yeni Resmi Tatil</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <form id="nationalHolidayForm">
                    <input type="hidden" name="id" id="id">
                    <input type="hidden" name="action" value="saveNationalHoliday">
                    
                    <div class="mb-3">
                        <label class="form-label required">Tatil Adı</label>
                        <input type="text" name="holiday_name" id="holiday_name" class="form-control" placeholder="Örn: 29 Ekim Cumhuriyet Bayramı">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Tatil Tarihi</label>
                        <input type="text" name="holiday_date" id="holiday_date" class="form-control flatpickr" placeholder="Tarih seçin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <input type="text" name="description" id="description" class="form-control" placeholder="Açıklama giriniz...">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary ms-auto" id="saveNationalHoliday">
                    <i class="ti ti-device-floppy icon me-2"></i>Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.createDataTable) {
        window.createDataTable('#national-holidays-table', {
            pageLength: 25
        });
    }
});
</script>
