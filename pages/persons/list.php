<?php
$perm->checkAuthorize("personnel_page");
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
                                    data-bs-target="#bulk-wages-modal">
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
                    <table class="table card-table table-hover text-nowrap" id="persons">
                        <thead>
                            <tr>
                                <th style="width: 40px; min-width: 40px;" class="text-center no-export" data-orderable="false"><input type="checkbox"
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
                        <tbody></tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
#persons th:last-child,
#persons td:last-child {
    width: 110px !important;
    min-width: 110px !important;
    white-space: nowrap;
}

#persons td:last-child .dropdown,
#persons td:last-child .dropdown-toggle {
    width: 100%;
    min-width: 88px;
}

#persons_wrapper .dt-layout-table,
#persons_wrapper .table-responsive {
    overflow-x: auto;
}
</style>

<script>
$(document).ready(function() {
    var currentStatus = 'active';
    var $searchRow = $('<tr class="search-input-row"></tr>');
    var personColumnTitles = [];
    $('#persons thead tr:first th').each(function(index) {
        personColumnTitles.push($(this).text().trim());
        $searchRow.append('<th data-column-index="' + index + '"></th>');
    });
    $('#persons thead').append($searchRow);

    var table = $('#persons').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        searchDelay: 400,
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],
        order: [[1, 'asc']],
        orderCellsTop: true,
        ajax: {
            url: 'api/persons/list.php',
            type: 'POST',
            data: function(data) {
                data.status = currentStatus;
            }
        },
        columnDefs: [
            { targets: [0, 20], orderable: false, searchable: false },
            { targets: 0, className: 'no-export' },
            { targets: 1, className: 'text-center' },
            { targets: 20, width: '110px', className: 'text-end no-export actions-column' }
        ],
        language: {
            url: 'src/tr.json',
            processing: '<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...'
        },
        initComplete: function() {
            var api = this.api();

            api.columns().every(function(index) {
                var column = this;
                var title = personColumnTitles[index] || '';
                var $cell = $('#persons .search-input-row th[data-column-index="' + index + '"]');
                if (index > 1 && index < 20 && index !== 16) {
                    var $input = $('<input type="text" class="form-control form-control-sm" autocomplete="off">')
                        .attr('placeholder', title);
                    var timer = null;
                    $input.on('input', function() {
                        var value = this.value;
                        clearTimeout(timer);
                        timer = setTimeout(function() {
                            if (column.search() !== value) {
                                column.search(value).draw();
                            }
                        }, 400);
                    });
                    $cell.append($input);
                }
            });
            syncPersonSearchVisibility();
        },
        drawCallback: function() {
            $('.select-all-persons').prop('checked', false);
            if (typeof toggleBulkDeleteButton === 'function') {
                toggleBulkDeleteButton();
            }
        }
    });

    function syncPersonSearchVisibility() {
        table.columns().every(function(index) {
            $('#persons .search-input-row th[data-column-index="' + index + '"]')
                .css('display', this.visible() ? '' : 'none');
        });
    }

    table.on('column-visibility.dt draw.dt', syncPersonSearchVisibility);

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
        $('#persons .search-input-row th[data-column-index="' + idx + '"]')
            .css('display', isVisible ? '' : 'none');

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
    table.columns.adjust();

    // Sütun tetikleyicilerine tıklama olayı
    $(document).on('change', '.persons-col-trigger', function() {
        var colIdx = parseInt($(this).data('column'));
        var isChecked = this.checked;

        // DataTable sütununu gizle/göster
        table.column(colIdx).visible(isChecked);
        syncPersonSearchVisibility();

        // Durumu kaydet
        visibilityState[colIdx] = isChecked;
        localStorage.setItem('persons_column_visibility', JSON.stringify(visibilityState));
    });

    // Dropdown tıklamasında kapanmasını önleme
    $(document).on('click', '#personsColvisMenu', function(e) {
        e.stopPropagation();
    });

    $('.status-filter').on('change', function() {
        var val = $(this).val();
        currentStatus = val === 'Aktif' ? 'active' : (val === 'Pasif' ? 'passive' : '');
        table.ajax.reload(null, true);
    });
});
</script>

<?php include ROOT . '/pages/payroll/content/bulk-wages-modal.php'; ?>
