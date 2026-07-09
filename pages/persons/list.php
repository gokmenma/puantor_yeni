<?php
require_once 'Model/Persons.php';
require_once 'Model/Bordro.php';
require_once 'App/Helper/company.php';
require_once 'App/Helper/helper.php';
require_once 'App/Helper/date.php';
require_once 'Model/Projects.php';
require_once 'App/Helper/projects.php';


//Yetki Kontrolü yapılır
$perm->checkAuthorize("personnel_page");

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$person = new Persons();
$bordro = new Bordro();

$persons = $person->getPersonsByFirm($firm_id);
$company = new CompanyHelper();

// Firma projeleri ve personel-proje atama tablosunu çekerek eşleştirme dizisi oluşturma
$projectMap = [];
$personProjectsMap = [];
try {
    $projectsObj = new Projects();
    $allProjects = $projectsObj->getProjectsByFirm($firm_id);
    foreach ($allProjects as $proj) {
        $projectMap[$proj->id] = $proj->project_name;
    }
    
    $allAssignments = $projectsObj->getDb()->query("
        SELECT pp.person_id, pp.project_id 
        FROM project_person pp 
        JOIN projects p ON pp.project_id = p.id 
        WHERE p.firm_id = " . intval($firm_id)
    )->fetchAll(PDO::FETCH_OBJ);
    
    foreach ($allAssignments as $assign) {
        $personProjectsMap[$assign->person_id][] = $assign->project_id;
    }
} catch (Exception $e) {
    // Hata durumunda boş kalması sağlanır
}
?>
<div class="container-xl mt-3">
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Personel Listesi</h3>
                    <div class="ms-3">
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="person_status" value=""
                                    class="form-selectgroup-input status-filter">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-users icon me-1"></i> Tümü
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="person_status" value="Aktif"
                                    class="form-selectgroup-input status-filter" checked>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-user-check icon me-1 text-success"></i> Aktif
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="person_status" value="Pasif"
                                    class="form-selectgroup-input status-filter">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-user-x icon me-1 text-danger"></i> Pasif
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="d-flex col-auto ms-auto">
                        <button type="button" id="btnDeleteSelected" class="btn btn-danger me-2 d-none">
                            <i class="ti ti-trash icon me-2"></i> Seçilenleri Sil
                        </button>
                        <a href="/pages/persons/to-pdf.php" target="_blank" class="btn btn-icon me-2" data-page=""
                            data-tooltip="Pdf'e Aktar">
                            <i class="ti ti-file-type-pdf icon"></i>
                        </a>
                        <a href="/pages/persons/to-xls.php" class="btn btn-icon me-2" data-page=""
                            data-tooltip="Excele Aktar">
                            <i class="ti ti-file-excel icon"></i>
                        </a>
                        <div class="dropdown me-2">
                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                <i class="ti ti-columns icon me-2"></i>

                            </button>
                            <div class="dropdown-menu dropdown-menu-end p-2" id="personsColvisMenu"
                                style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                                <!-- Checkboxes will be rendered dynamically by JS -->
                            </div>
                        </div>
                        <div class="dropdown me-2">
                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">
                                <i class="ti ti-list-details icon me-2"></i>
                                İşlemler</button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item route-link"
                                    data-tooltip="Personelleri Excel dosyasından yükleyin" data-tooltip-location="left"
                                    href="#" data-page="persons/xls/person-load">
                                    <i class="ti ti-upload icon me-3"></i> Excelden Yükle
                                </a>
                                <a class="dropdown-item" data-tooltip="Günlük Ücretleri toplu olarak güncelleyin"
                                    data-tooltip-location="left" href="#" data-bs-toggle="modal"
                                    data-bs-target="#update_wages_modal">
                                    <i class="ti ti-user-dollar icon me-3"></i> Ücretleri Güncelle
                                </a>


                            </div>
                        </div>
                        <a href="#" class="btn btn-primary route-link" data-page="persons/manage">
                            <i class="ti ti-plus icon me-2"></i> Yeni
                        </a>

                    </div>

                </div>


                <div class="table-responsive">
                    <table class="table card-table table-hover text-nowrap datatable" id="persons">
                        <thead>
                            <tr>
                                <th style="width: 2%" class="no-export" data-orderable="false"><input type="checkbox"
                                        class="form-check-input select-all-persons"></th>
                                <th style="width:5%">Sıra</th>
                                <th>Adı Soyadı</th>
                                <th>TC Kimlik No</th>
                                <th>Firma Adı</th>
                                <th>Ücret Türü</th>
                                <th>İşe Giriş Tarihi</th>
                                <th>İşten Çıkış Tarihi</th>
                                <th>Telefon</th>
                                <th>E-posta</th>
                                <th>IBAN Numarası</th>
                                <th>Grubu</th>
                                <th>Görevi</th>
                                <th>Ekip</th>
                                <th>Proje</th>
                                <th>Günlük/Aylık Ücretİ</th>
                                <th>Durumu</th>
                                <th>Güncel Bakiyesi</th>
                                <th>Adres</th>
                                <th>Açıklama</th>
                                <th style="width:7%" class="no-export">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>


                            <?php
                            $i = 1;
                            foreach ($persons as $person):
                                $wage_type = $person->wage_type == 1 ? 'Beyaz Yaka' : 'Mavi Yaka';
                                $wage_type_color = $person->wage_type == 2 ? "style='color:blue'" : '';
                                $balance = $bordro->getBalance($person->id);
                                $color = Helper::balanceColor($balance);
                                $id = Security::encrypt($person->id);

                                ?>
                            <?php if ($person->firm_id == $_SESSION["firm_id"]) { ?>
                            <tr>
                                <td><input type="checkbox" class="form-check-input person-checkbox"
                                        value="<?php echo $id; ?>"></td>
                                <td class="text-center"><?php echo $i; ?></td>
                                <td> <a href="#" data-tooltip="Detay/Güncelle"
                                        data-page="persons/manage&id=<?php echo $id ?>"
                                        class="nav-item route-link"><?php echo $person->full_name; ?></a></td>
                                <td><?php echo Security::safeDecrypt($person->kimlik_no ?? '') ?: '-'; ?></td>
                                <td><?php 
                                             $compName = $company->getCompanyName($person->company_id);
                                             echo ($compName !== 'bilinmiyor' && $compName !== '') ? $compName : $company->getFirmName($person->firm_id); 
                                         ?></td>
                                <td <?php echo $wage_type_color; ?>><?php echo $wage_type; ?></td>
                                <td><?php echo $person->job_start_date ?? '-'; ?></td>
                                <td><?php echo $person->job_end_date ?? '-'; ?></td>
                                <td><?php echo Security::safeDecrypt($person->phone ?? '') ?: '-'; ?></td>
                                <td><?php echo Security::safeDecrypt($person->email ?? '') ?: '-'; ?>
                                </td>
                                <td><?php echo Security::safeDecrypt($person->iban_number ?? '') ?: '-'; ?></td>
                                <td><?php echo $person->job_group ?? '-'; ?></td>
                                <td><?php echo $person->job ?? '-'; ?></td>
                                <td><?php echo $person->ekip ?: '-'; ?></td>
                                <td><?php 
                                            $assignedProjs = [];
                                            if (isset($personProjectsMap[$person->id])) {
                                                foreach ($personProjectsMap[$person->id] as $projId) {
                                                    if (isset($projectMap[$projId])) {
                                                        $assignedProjs[] = $projectMap[$projId];
                                                    }
                                                }
                                            }
                                            echo !empty($assignedProjs) ? implode(', ', $assignedProjs) : '-';
                                        ?></td>
                                <td><?php echo Helper::formattedMoney($person->daily_wages ?? 0); ?></td>
                                <td>
                                    <?php if (empty($person->job_end_date)): ?>
                                    <span class="badge bg-success-lt">Aktif</span>
                                    <?php else: ?>
                                    <span class="badge bg-danger-lt">Pasif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="<?php echo $color ?>"><?php echo Helper::formattedMoney($balance) ?></td>
                                <td><?php echo $person->address ?: '-'; ?></td>
                                <td><?php echo $person->description ?: ($person->aciklama ?: '-'); ?></td>
                                <td class="text-end">
                                    <div class="dropdown">
                                        <button class="btn dropdown-toggle align-text-top"
                                            data-bs-toggle="dropdown">İşlem</button>
                                        <div class="dropdown-menu dropdown-menu-end">


                                            <a class="dropdown-item route-link"
                                                data-page="persons/manage&id=<?php echo $id ?>" href="#">
                                                <i class="ti ti-edit icon me-3"></i> Detay/Güncelle
                                            </a>

                                            <a class="dropdown-item delete-person" data-id="<?php echo $id ?>" href="#">
                                                <i class="ti ti-trash icon me-3"></i> Sil
                                            </a>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <?php } ?>
                            <?php
                                $i++;
                            endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var table = $('#persons').DataTable();

    // Sütunların varsayılan durumları ve etiketleri
    var columnConfig = {
        3: {
            label: 'TC Kimlik No',
            default: false
        },
        4: {
            label: 'Firma Adı',
            default: true
        },
        5: {
            label: 'Ücret Türü',
            default: true
        },
        6: {
            label: 'İşe Giriş Tarihi',
            default: true
        },
        7: {
            label: 'İşten Çıkış Tarihi',
            default: true
        },
        8: {
            label: 'Telefon',
            default: true
        },
        9: {
            label: 'E-posta',
            default: false
        },
        10: {
            label: 'IBAN Numarası',
            default: false
        },
        11: {
            label: 'Grubu',
            default: false
        },
        12: {
            label: 'Görevi',
            default: false
        },
        13: {
            label: 'Ekip',
            default: true
        },
        14: {
            label: 'Proje',
            default: true
        },
        15: {
            label: 'Günlük/Aylık Ücreti',
            default: true
        },
        16: {
            label: 'Durumu',
            default: true
        },
        17: {
            label: 'Güncel Bakiyesi',
            default: true
        },
        18: {
            label: 'Adres',
            default: false
        },
        19: {
            label: 'Açıklama',
            default: false
        }
    };

    // Kayıtlı sütun durumlarını yükle veya varsayılanları kullan
    var savedVisibility = localStorage.getItem('persons_column_visibility');
    var visibilityState = savedVisibility ? JSON.parse(savedVisibility) : {};

    // Menüyü oluştur ve sütun görünürlüklerini ayarla
    var menuHtml = '';
    $.each(columnConfig, function(idx, conf) {
        var isVisible = visibilityState.hasOwnProperty(idx) ? visibilityState[idx] : conf.default;

        // DataTable üzerinde görünürlüğü ayarla (ikinci argüman false: redraw yapmaz, daha performanslı)
        table.column(idx).visible(isVisible, false);

        // Menü elemanını ekle
        menuHtml += `
                <label class="dropdown-item d-flex align-items-center cursor-pointer py-1.5 px-3 rounded-2" style="font-size: 0.85rem;">
                    <div class="form-check mb-0 w-100">
                        <input class="form-check-input persons-col-trigger" type="checkbox" id="colCheck_${idx}" data-column="${idx}" ${isVisible ? "checked" : ""}>
                        <span class="form-check-label fw-medium ms-2 text-secondary" style="user-select:none;">
                            ${conf.label}
                        </span>
                    </div>
                </label>`;
    });

    $('#personsColvisMenu').html(menuHtml);

    // Tabloyu çiz ve kolon boyutlarını otomatik ayarla
    table.columns.adjust().draw(false);

    // Sütun tetikleyicilerine tıklama olayı
    $(document).on('change', '.persons-col-trigger', function() {
        var colIdx = parseInt($(this).data('column'));
        var isChecked = this.checked;

        // DataTable sütununu gizle/göster
        table.column(colIdx).visible(isChecked);

        // Durumu kaydet
        visibilityState[colIdx] = isChecked;
        localStorage.setItem('persons_column_visibility', JSON.stringify(visibilityState));
    });

    // Dropdown tıklamasında kapanmasını önleme
    $(document).on('click', '#personsColvisMenu', function(e) {
        e.stopPropagation();
    });

    // Varsayılan olarak Aktif olanları filtrele (Durumu kolonu indeksi 16 oldu)
    table.column(16).search('^Aktif$', true, false).draw();

    // Filtre butonlarına tıklandığında
    $('.status-filter').on('change', function() {
        var val = $(this).val();
        if (val === '') {
            table.column(16).search('').draw();
        } else {
            // Tam eşleşme için regex kullanıyoruz: ^Aktif$ veya ^Pasif$
            table.column(16).search('^' + val + '$', true, false).draw();
        }
    });
});
</script>

<!-- Ücret Güncelleme Modalı -->
<div class="modal modal-blur fade" id="update_wages_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Günlük Ücretleri Güncelle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkWageForm">
                    <div class="table-responsive">
                        <table class="table table-vcenter">
                            <thead>
                                <tr>
                                    <th>Adı Soyadı</th>
                                    <th>Görevi</th>
                                    <th>Mevcut Ücret</th>
                                    <th style="width: 150px;">Yeni Ücret</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($persons as $p): ?>
                                <tr>
                                    <td><?php echo $p->full_name; ?></td>
                                    <td class="text-secondary"><?php echo $p->job ?? '-'; ?></td>
                                    <td><?php echo Helper::formattedMoney($p->daily_wages); ?></td>
                                    <td>
                                        <input type="text" name="wages[<?php echo $p->id; ?>]"
                                            class="form-control money" placeholder="Yeni Ücret"
                                            value="<?php echo Helper::moneyToNumber($p->daily_wages); ?>">
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">İptal</button>
                <button type="button" id="btnSaveBulkWages" class="btn btn-primary">
                    <i class="ti ti-device-floppy icon me-2"></i> Kaydet
                </button>
            </div>
        </div>
    </div>
</div>