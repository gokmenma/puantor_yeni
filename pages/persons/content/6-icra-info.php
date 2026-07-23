<?php
use App\Helper\Security;

// Yetki Kontrolü
if (!$Auths->Authorize("person_page_icra_info")) {
    require_once "App/Helper/helper.php";
    App\Helper\Helper::authorizePage();
    return;
}

require_once "Model/PersonIcra.php";

$person_id_encrypted = Security::encrypt($person->id);
?>

<div class="container-xl mt-3">
    <!-- İcra İstatistik Kartları -->
    <div class="row row-cards mb-4 align-items-stretch">
        <div class="col-md-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <span class="bg-blue-lt text-blue avatar avatar-md border-0 shadow-sm me-3">
                        <i class="ti ti-folder fs-2"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium text-secondary text-uppercase fs-6 tracking-wide">Toplam Dosya</div>
                        <div class="h2 mb-0" id="stats-total-files">0</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <span class="bg-success-lt text-success avatar avatar-md border-0 shadow-sm me-3">
                        <i class="ti ti-circle-check fs-2"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium text-secondary text-uppercase fs-6 tracking-wide">Aktif Dosyalar</div>
                        <div class="h2 mb-0" id="stats-active-files">0</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <span class="bg-warning-lt text-warning avatar avatar-md border-0 shadow-sm me-3">
                        <i class="ti ti-coin fs-2"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium text-secondary text-uppercase fs-6 tracking-wide">Toplam Borç</div>
                        <div class="h2 mb-0 text-warning" id="stats-total-debt">0,00 ₺</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="card card-sm shadow-sm border-0">
                <div class="card-body d-flex align-items-center">
                    <span class="bg-danger-lt text-danger avatar avatar-md border-0 shadow-sm me-3">
                        <i class="ti ti-wallet fs-2"></i>
                    </span>
                    <div>
                        <div class="font-weight-medium text-secondary text-uppercase fs-6 tracking-wide">Kalan Toplam Borç</div>
                        <div class="h2 mb-0 text-danger" id="stats-remaining-debt">0,00 ₺</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler ve Tablo -->
    <div class="card shadow-sm border-0">
        <div class="card-header border-bottom py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h3 class="card-title font-weight-700 mb-0">İcra Dosyaları</h3>
            
            <div class="d-flex align-items-center gap-2 ms-auto flex-wrap">
                <!-- Bordro kesintisi yapılsın tetiği -->
                <div class="form-check form-switch mb-0 me-3 pe-3 border-end border-light">
                    <input class="form-check-input" type="checkbox" id="icra-kesintisi-toggle" data-person-id="<?= $person_id_encrypted; ?>">
                    <label class="form-check-label fw-bold fs-5 mb-0" for="icra-kesintisi-toggle">Bordro kesintisi yapılsın</label>
                </div>

                <!-- Durum Filtresi (Select2) -->
                <div style="width: 200px;">
                    <select class="form-select" id="icra-status-filter">
                        <option value="">Tüm Durumlar</option>
                        <?php foreach (PersonIcra::getStatuses() as $key => $stInfo): ?>
                            <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($stInfo['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Arama Kutusu -->
                <div class="input-icon" style="width: 160px;">
                    <span class="input-icon-addon">
                        <i class="ti ti-search"></i>
                    </span>
                    <input type="text" class="form-control" placeholder="Dosya ara..." id="icra-search" style="height: 40px;">
                </div>

                <!-- Ekleme Butonu -->
                <button type="button" class="btn btn-primary px-3" id="btn-add-icra" style="height: 40px;">
                    <i class="ti ti-plus icon me-1"></i> Yeni
                </button>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-vcenter card-table table-hover" id="icra-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 50px;">Sıra</th>
                        <th>İcra Dairesi</th>
                        <th>Dosya No</th>
                        <th>Gelen Evrak</th>
                        <th>Giden Evrak</th>
                        <th class="text-center" style="width: 70px;">Belge</th>
                        <th>Kesinti Tarihleri</th>
                        <th class="text-end">Borç Tutarı</th>
                        <th class="text-end">Yapılan Kesinti</th>
                        <th class="text-end">Kalan Borç</th>
                        <th class="text-center" style="width: 100px;">Durum</th>
                        <th class="text-end" style="width: 120px;">İşlemler</th>
                    </tr>
                </thead>
                <tbody id="icra-table-body">
                    <!-- Dinamik Yüklenir -->
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Yeni/Güncelle İcra Dosyası Modalı -->
<div class="modal modal-blur fade" id="icraModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <form id="icraForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="person_id" value="<?= $person_id_encrypted; ?>">
                <input type="hidden" name="id" id="icra-edit-id" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title font-weight-bold" id="icraModalTitle">
                        <i class="ti ti-plus me-1"></i> Yeni İcra Dosyası Ekle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <!-- Üst Satır: İcra Sırası & İcra Dairesi -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required fw-bold">İcra Sırası</label>
                            <input type="number" class="form-control" name="icra_sirasi" id="icra-sirasi" min="1" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-bold">İcra Dairesi</label>
                            <select class="form-select select2-icra" name="icra_dairesi" id="icra-dairesi" required style="width: 100%;">
                                <!-- Dinamik dolar -->
                            </select>
                        </div>
                    </div>

                    <!-- İkinci Satır: Dosya No & Toplam Borç -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label required fw-bold">Dosya Numarası</label>
                            <input type="text" class="form-control" name="dosya_no" id="icra-dosya-no" placeholder="Örn: 2026/1234 Esas" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required fw-bold">Toplam Borç Tutarı</label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="text" class="form-control money fw-bold" name="toplam_borc" id="icra-toplam-borc" placeholder="0.00" required>
                            </div>
                        </div>
                    </div>

                    <!-- Üçüncü Satır: Alacaklı -->
                    <div class="mb-3">
                        <label class="form-label required fw-bold">Alacaklı</label>
                        <input type="text" class="form-control" name="alacakli" id="icra-alacakli" placeholder="Örn: Türkiye İş Bankası A.Ş." required>
                    </div>

                    <!-- Kesinti Tarihleri -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kesintiye Başlama Tarihi</label>
                            <input type="text" class="form-control flatpickr-date" name="baslama_tarihi" id="icra-baslama-tarihi" placeholder="Örn: 01.07.2026" autocomplete="off">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kesinti Bitiş Tarihi</label>
                            <input type="text" class="form-control flatpickr-date" name="bitis_tarihi" id="icra-bitis-tarihi" placeholder="Örn: 31.12.2026" autocomplete="off">
                        </div>
                    </div>

                    <!-- Dördüncü Satır: Kesinti Yöntemi & Oran/Tutar Girişi -->
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-6">
                            <label class="form-label required fw-bold mb-2">Kesinti Yöntemi</label>
                            <div class="form-selectgroup">
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="kesinti_yontemi" value="oran" class="form-selectgroup-input" checked id="yontem-oran">
                                    <span class="form-selectgroup-label">
                                        <i class="ti ti-percentage icon me-1"></i> % Oran
                                    </span>
                                </label>
                                <label class="form-selectgroup-item">
                                    <input type="radio" name="kesinti_yontemi" value="sabit" class="form-selectgroup-input" id="yontem-sabit">
                                    <span class="form-selectgroup-label">
                                        <i class="ti ti-cash icon me-1"></i> Sabit Tutar
                                    </span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="col-md-6" id="input-container-oran">
                            <label class="form-label required fw-bold">Kesinti Oranı</label>
                            <input type="text" class="form-control" name="kesinti_orani" id="icra-kesinti-orani" placeholder="Örn: 1/4 veya %25">
                        </div>
                        
                        <div class="col-md-6 d-none" id="input-container-sabit">
                            <label class="form-label required fw-bold">Kesinti Tutarı</label>
                            <div class="input-group">
                                <span class="input-group-text">₺</span>
                                <input type="text" class="form-control money" name="kesinti_tutari" id="icra-kesinti-tutari" placeholder="0.00">
                            </div>
                        </div>
                    </div>

                    <!-- Beşinci Satır: Durum & Açıklama -->
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label required fw-bold">Durum</label>
                            <select class="form-select" name="durum" id="icra-durum" required>
                                <?php foreach (PersonIcra::getStatuses() as $key => $stInfo): ?>
                                    <option value="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($stInfo['title'], ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Açıklama</label>
                        <textarea class="form-control" name="aciklama" id="icra-aciklama" rows="3" placeholder="Dosya ile ilgili açıklama veya not..."></textarea>
                    </div>

                    <!-- Belgeler & Evraklar (ISO 27001) -->
                    <div class="card bg-light border-0 shadow-none mt-4">
                        <div class="card-body">
                            <h4 class="card-title fw-bold text-dark mb-3">
                                <i class="ti ti-shield-lock me-1 text-purple"></i> Belgeler & Evraklar (ISO 27001)
                            </h4>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Gelen Evrak No / Tarih</label>
                                    <input type="text" class="form-control" name="gelen_evrak" id="icra-gelen-evrak" placeholder="Örn: 12.05.2026-712563">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Giden Evrak No / Tarih</label>
                                    <input type="text" class="form-control" name="giden_evrak" id="icra-giden-evrak" placeholder="Örn: 12.05.2026-712564">
                                </div>
                            </div>
                            
                            <div>
                                <label class="form-label fw-bold">Belge Dosyası Yükle</label>
                                <input type="file" class="form-control" name="belge_dosyasi" id="icra-belge-dosyasi">
                                <small class="text-secondary mt-1 d-block">İzin verilen formatlar: PDF, Resimler, Word, Excel. Maks 5MB. Belge güvenli olarak şifrelenip saklanır.</small>
                                <div class="mt-2 d-none" id="edit-has-file-info">
                                    <span class="badge bg-green-lt">
                                        <i class="ti ti-file-check me-1"></i> <span id="edit-file-name-label">Kayıtlı Belge Mevcut</span>
                                    </span>
                                    <small class="text-secondary d-block mt-1">Yeni dosya seçerseniz mevcut dosya silinerek yenisi ile değiştirilecektir.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary px-4" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-dark px-4" id="icraSubmitBtn">Dosya Oluştur</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let datatableObj = null;
    let cachedDefines = [];
    const personIdEncrypted = '<?= $person_id_encrypted; ?>';

    // Durum filtresini select2 olarak başlat
    $('#icra-status-filter').select2({
        minimumResultsForSearch: -1,
        width: '100%'
    });

    // Formdaki durum seçim alanını select2 olarak başlat
    $('#icra-durum').select2({
        dropdownParent: $('#icraModal'),
        minimumResultsForSearch: -1,
        width: '100%'
    });

    // Arama ve filtreleme olaylarını bir defa bağlayalım
    $('#icra-search').on('keyup', function() {
        if (datatableObj) {
            datatableObj.search($(this).val()).draw();
        }
    });

    $('#icra-status-filter').on('change', function() {
        if (datatableObj) {
            datatableObj.column(9).search($(this).val()).draw();
        }
    });

    // 1. İcra Dairesi select2'yi başlatma fonksiyonu
    function initSelect2(definesList) {
        let select2Elem = $('#icra-dairesi');
        select2Elem.empty();
        select2Elem.append('<option value="">İcra Dairesi Seçiniz</option>');
        
        definesList.forEach(name => {
            select2Elem.append(`<option value="${name}">${name}</option>`);
        });

        // select2 tags özelliğini aktifleştiriyoruz (yeni giriş yapılabilmesi için)
        select2Elem.select2({
            tags: true,
            placeholder: "İcra Dairesi Seçiniz veya Yazınız",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#icraModal')
        });
    }

    // 2. İcra verilerini sunucudan çekip dolduran ana yükleyici
    function loadIcraModuleData() {
        $.ajax({
            url: 'api/persons/icra.php',
            type: 'POST',
            data: { action: 'list', person_id: personIdEncrypted },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    // İstatistikleri güncelle
                    $('#stats-total-files').text(res.stats.total_files);
                    $('#stats-active-files').text(res.stats.active_files);
                    $('#stats-total-debt').text(res.stats.total_debt);
                    $('#stats-remaining-debt').text(res.stats.remaining_debt);

                    // Bordro kesintisi checkbox'ını güncelle
                    $('#icra-kesintisi-toggle').prop('checked', res.icra_kesintisi_aktif == 1);

                    // Defines listesini güncelle
                    cachedDefines = res.defines || [];
                    initSelect2(cachedDefines);

                    // Tabloyu güncelle
                    let rowsHtml = '';
                    if (res.files.length > 0) {
                        res.files.forEach(f => {
                            let statusBadge = '';
                            if (f.durum === 'Bekliyor') statusBadge = '<span class="badge bg-warning text-warning-fg">Bekliyor</span>';
                            else if (f.durum === 'Kesilen') statusBadge = '<span class="badge bg-blue text-blue-fg">Kesilen</span>';
                            else if (f.durum === 'Güncellendi') statusBadge = '<span class="badge bg-info text-info-fg">Güncellendi</span>';
                            else if (f.durum === 'Durduruldu') statusBadge = '<span class="badge bg-danger text-danger-fg">Durduruldu</span>';
                            else if (f.durum === 'Durduruldu(Bekleyen)') statusBadge = '<span class="badge bg-purple text-purple-fg">Durduruldu(Bekleyen)</span>';
                            else if (f.durum === 'Fekki Geldi') statusBadge = '<span class="badge bg-teal text-teal-fg">Fekki Geldi</span>';
                            else if (f.durum === 'Kesinti Bitti') statusBadge = '<span class="badge bg-success text-success-fg">Kesinti Bitti</span>';
                            else statusBadge = `<span class="badge bg-secondary text-secondary-fg">${f.durum}</span>`;

                            let belgeLink = '';
                            if (f.has_belge) {
                                belgeLink = `
                                    <a href="api/persons/icra.php?action=download&id=${f.id}" class="btn btn-icon btn-sm btn-outline-purple border-0" title="Belgeyi İndir">
                                        <i class="ti ti-file-download fs-2"></i>
                                    </a>
                                `;
                            } else {
                                belgeLink = '<span class="text-muted fs-6">-</span>';
                            }

                            let dateRange = '-';
                            if (f.baslama_tarihi_formatted || f.bitis_tarihi_formatted) {
                                dateRange = `${f.baslama_tarihi_formatted || '...'} - ${f.bitis_tarihi_formatted || '...'}`;
                            }

                            rowsHtml += `
                                <tr>
                                    <td class="fw-bold">${f.icra_sirasi}</td>
                                    <td>${f.icra_dairesi}</td>
                                    <td><span class="badge bg-purple-lt">${f.dosya_no}</span></td>
                                    <td><small class="text-secondary">${f.gelen_evrak || '-'}</small></td>
                                    <td><small class="text-secondary">${f.giden_evrak || '-'}</small></td>
                                    <td class="text-center">${belgeLink}</td>
                                    <td><small class="text-secondary fw-semibold">${dateRange}</small></td>
                                    <td class="text-end fw-bold text-dark">${f.toplam_borc}</td>
                                    <td class="text-end fw-bold">
                                        <a href="javascript:void(0)" class="text-success text-decoration-underline btn-view-deductions" data-file-id="${f.id}" data-person-id="<?= $person_id_encrypted; ?>" title="Kesinti Detaylarını Gör">
                                            ${f.yapilan_kesinti} <i class="ti ti-info-circle ms-1 small"></i>
                                        </a>
                                    </td>
                                    <td class="text-end fw-bold text-danger">${f.kalan_borc}</td>
                                    <td class="text-center">${statusBadge}</td>
                                    <td class="text-end">
                                        <button class="btn btn-icon btn-sm btn-ghost-primary btn-edit-icra me-1" data-id="${f.id}" data-file-json='${JSON.stringify(f)}' title="Düzenle">
                                            <i class="ti ti-edit fs-2"></i>
                                        </button>
                                        <button class="btn btn-icon btn-sm btn-ghost-danger btn-delete-icra" data-id="${f.id}" title="Sil">
                                            <i class="ti ti-trash fs-2"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
                        });
                    }
                    
                    // DataTable'ı kurmadan önce varsa eskisini imha ediyoruz
                    if ($.fn.DataTable && $.fn.DataTable.isDataTable('#icra-table')) {
                        $('#icra-table').DataTable().destroy();
                    }

                    $('#icra-table-body').html(rowsHtml);

                    // window.createDataTable kullanarak DataTable oluştur
                    if (window.createDataTable) {
                        datatableObj = window.createDataTable('#icra-table', {
                            searching: true,
                            pageLength: 10,
                            skipSearch: ['İşlemler', 'Sıra'],
                            ordering: false
                        });
                        
                        // Mevcut filtreleri uygula
                        let searchVal = $('#icra-search').val();
                        if (searchVal) {
                            datatableObj.search(searchVal).draw();
                        }
                        
                        let statusVal = $('#icra-status-filter').val();
                        if (statusVal) {
                            datatableObj.column(10).search(statusVal).draw();
                        }
                    }
                } else {
                    Swal.fire('Hata', res.message || 'Veriler yüklenemedi.', 'error');
                }
            },
            error: function() {
                Swal.fire('Hata', 'Sunucuyla bağlantı hatası oluştu.', 'error');
            }
        });
    }

    // Tarih seçicileri flatpickr ile başlat
    let fpBaslama = flatpickr('#icra-baslama-tarihi', { dateFormat: 'd.m.Y', locale: 'tr', allowInput: true });
    let fpBitis = flatpickr('#icra-bitis-tarihi', { dateFormat: 'd.m.Y', locale: 'tr', allowInput: true });

    // Verileri yükle
    loadIcraModuleData();

    // 3. Kesinti Yöntemi Tıklama Olayları
    $('input[name="kesinti_yontemi"]').on('change', function() {
        if ($(this).val() === 'oran') {
            $('#input-container-oran').removeClass('d-none');
            $('#input-container-sabit').addClass('d-none');
            $('#icra-kesinti-orani').attr('required', true);
            $('#icra-kesinti-tutari').removeAttr('required').val('');
        } else {
            $('#input-container-sabit').removeClass('d-none');
            $('#input-container-oran').addClass('d-none');
            $('#icra-kesinti-tutari').attr('required', true);
            $('#icra-kesinti-orani').removeAttr('required').val('');
        }
    });

    // 4. Yeni Ekle Butonu
    $('#btn-add-icra').on('click', function() {
        $('#icraForm')[0].reset();
        $('#icra-edit-id').val('');
        $('#icraModalTitle').html('<i class="ti ti-plus me-1"></i> Yeni İcra Dosyası Ekle');
        $('#icraSubmitBtn').text('Dosya Oluştur');
        
        // Select2 varsayılanı temizle
        $('#icra-dairesi').val('').trigger('change');
        
        // Oran varsayılan yap
        $('#yontem-oran').prop('checked', true).trigger('change');
        
        // Tarih seçicileri temizle
        fpBaslama.clear();
        fpBitis.clear();
        
        // Evrak bilgileri
        $('#edit-has-file-info').addClass('d-none');
        $('#icra-belge-dosyasi').removeAttr('required');

        $('#icraModal').modal('show');
    });

    // 5. Düzenle Butonu
    $(document).on('click', '.btn-edit-icra', function() {
        let f = $(this).data('file-json');
        
        $('#icra-edit-id').val(f.id);
        $('#icraModalTitle').html('<i class="ti ti-edit me-1"></i> İcra Dosyası Güncelle');
        $('#icraSubmitBtn').text('Dosya Güncelle');
        
        $('#icra-sirasi').val(f.icra_sirasi);
        $('#icra-dosya-no').val(f.dosya_no);
        $('#icra-toplam-borc').val(f.toplam_borc_raw);
        $('#icra-alacakli').val(f.alacakli);
        $('#icra-durum').val(f.durum).trigger('change');
        $('#icra-aciklama').val(f.aciklama);
        $('#icra-gelen-evrak').val(f.gelen_evrak);
        $('#icra-giden-evrak').val(f.giden_evrak);

        // Tarihleri doldur
        if (f.baslama_tarihi_formatted) {
            fpBaslama.setDate(f.baslama_tarihi_formatted);
        } else {
            fpBaslama.clear();
        }
        if (f.bitis_tarihi_formatted) {
            fpBitis.setDate(f.bitis_tarihi_formatted);
        } else {
            fpBitis.clear();
        }

        // Select2 dairesini ayarla. Listede yoksa ekle ve seç
        let checkOption = $('#icra-dairesi option[value="' + f.icra_dairesi + '"]');
        if (checkOption.length === 0) {
            $('#icra-dairesi').append(new Option(f.icra_dairesi, f.icra_dairesi, true, true)).trigger('change');
        } else {
            $('#icra-dairesi').val(f.icra_dairesi).trigger('change');
        }

        // Kesinti yöntemi ayarla
        if (f.kesinti_yontemi === 'oran') {
            $('#yontem-oran').prop('checked', true).trigger('change');
            $('#icra-kesinti-orani').val(f.kesinti_orani);
        } else {
            $('#yontem-sabit').prop('checked', true).trigger('change');
            $('#icra-kesinti-tutari').val(f.kesinti_tutari_raw);
        }

        // Belge bilgisi
        if (f.has_belge) {
            $('#edit-has-file-info').removeClass('d-none');
        } else {
            $('#edit-has-file-info').addClass('d-none');
        }
        $('#icra-belge-dosyasi').removeAttr('required');

        $('#icraModal').modal('show');
    });

    // 6. Form Kaydetme (AJAX Submit)
    $('#icraForm').on('submit', function(e) {
        e.preventDefault();

        // Para alanlarındaki noktalamaları düzelt (maskelemeyi aşmak için)
        // inputmask'ın unmaskedvalue metodu veya normal parse edilebilir form verisi
        let formData = new FormData(this);

        $('#icraSubmitBtn').attr('disabled', true).text('İşlem yapılıyor...');

        $.ajax({
            url: 'api/persons/icra.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(res) {
                $('#icraSubmitBtn').removeAttr('disabled');
                if (res.status === 'success') {
                    $('#icraModal').modal('hide');
                    Swal.fire({
                        title: 'Başarılı!',
                        text: res.message,
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadIcraModuleData();
                } else {
                    $('#icraSubmitBtn').text($('#icra-edit-id').val() ? 'Dosya Güncelle' : 'Dosya Oluştur');
                    Swal.fire('Hata', res.message || 'Kayıt sırasında hata oluştu.', 'error');
                }
            },
            error: function() {
                $('#icraSubmitBtn').removeAttr('disabled').text($('#icra-edit-id').val() ? 'Dosya Güncelle' : 'Dosya Oluştur');
                Swal.fire('Hata', 'Sunucu bağlantısında hata oluştu.', 'error');
            }
        });
    });

    // 7. Silme Butonu Tıklama
    $(document).on('click', '.btn-delete-icra', function() {
        let id = $(this).data('id');
        
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu icra dosyası kaydı silinecektir ve geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil!',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/persons/icra.php',
                    type: 'POST',
                    data: { action: 'delete', id: id },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            Swal.fire({
                                title: 'Silindi!',
                                text: res.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            loadIcraModuleData();
                        } else {
                            Swal.fire('Hata', res.message || 'Silinemedi.', 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Hata', 'Sunucu bağlantısında hata oluştu.', 'error');
                    }
                });
            }
        });
    });

    // 8. Bordro Kesintisi Switch Tıklama
    $('#icra-kesintisi-toggle').on('change', function() {
        let isChecked = $(this).is(':checked') ? 1 : 0;
        let pId = $(this).data('person-id');

        $.ajax({
            url: 'api/persons/icra.php',
            type: 'POST',
            data: { action: 'toggle_payroll_deduction', person_id: pId, active: isChecked },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    const statusText = isChecked ? 'Bordro kesintisi aktif edildi.' : 'Bordro kesintisi pasif edildi.';
                    Swal.fire({
                        title: 'Güncellendi',
                        text: statusText,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                } else {
                    $('#icra-kesintisi-toggle').prop('checked', !isChecked);
                    Swal.fire('Hata', res.message || 'Ayar güncellenemedi.', 'error');
                }
            },
            error: function() {
                $('#icra-kesintisi-toggle').prop('checked', !isChecked);
                Swal.fire('Hata', 'Sunucu hatası oluştu.', 'error');
            }
        });
    });
});
</script>
<!-- İcra Kesintileri Geçmişi Modalı -->
<div class="modal modal-blur fade" id="deductionsHistoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-700 text-white" id="modal-deductions-title">
                    <i class="ti ti-history me-2"></i>İcra Kesintileri Geçmişi
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                    <div>
                        <div class="small text-muted text-uppercase tracking-wide font-weight-600">Personel / Dosya</div>
                        <div class="font-weight-700 text-dark" id="modal-deductions-person-name">-</div>
                    </div>
                    <div class="text-end">
                        <div class="small text-muted text-uppercase tracking-wide font-weight-600">Toplam Kesilen</div>
                        <div class="h3 mb-0 text-success font-weight-700" id="modal-deductions-total">0,00 ₺</div>
                    </div>
                </div>
                <div class="table-responsive" style="max-height: 350px;">
                    <table class="table table-vcenter table-striped table-sm mb-0">
                        <thead class="bg-white sticky-top">
                            <tr>
                                <th class="ps-3">Dönem</th>
                                <th>Açıklama</th>
                                <th class="text-end pe-3">Tutar</th>
                            </tr>
                        </thead>
                        <tbody id="modal-deductions-table-body">
                            <!-- AJAX ile doldurulacak -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary px-4 ms-auto" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // 9. Kesintiler Geçmişi Detayı Tıklama
    $(document).on('click', '.btn-view-deductions', function(e) {
        e.preventDefault();
        const fileId = $(this).data('file-id') || '';
        const personId = $(this).data('person-id') || '';

        $('#modal-deductions-person-name').text('Yükleniyor...');
        $('#modal-deductions-total').text('0,00 ₺');
        $('#modal-deductions-table-body').html('<tr><td colspan="3" class="text-center py-3 text-muted"><div class="spinner-border spinner-border-sm text-primary me-2"></div> Kesintiler yükleniyor...</td></tr>');
        $('#deductionsHistoryModal').modal('show');

        $.ajax({
            url: 'api/persons/icra.php',
            type: 'POST',
            data: {
                action: 'deductions_history',
                file_id: fileId,
                person_id: personId
            },
            dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    $('#modal-deductions-person-name').html(`<strong>${res.person_name}</strong> <span class="badge bg-light text-dark ms-2">${res.dosya_no}</span>`);
                    $('#modal-deductions-total').text(res.total_amount || '0,00 ₺');

                    const tbody = $('#modal-deductions-table-body');
                    tbody.empty();

                    if (!res.history || res.history.length === 0) {
                        tbody.html('<tr><td colspan="3" class="text-center py-4 text-muted"><i class="ti ti-folder-off fs-1 d-block mb-1 text-secondary"></i>Bu icra dosyasına ait bordro kesintisi bulunamadı.</td></tr>');
                    } else {
                        res.history.forEach((h) => {
                            tbody.append(`
                                <tr>
                                    <td class="ps-3 fw-bold text-dark">${h.donem}</td>
                                    <td>
                                        <div class="font-weight-600 text-dark">${h.aciklama || h.turu}</div>
                                        <div class="small text-muted">${h.created_at || ''}</div>
                                    </td>
                                    <td class="text-end pe-3 text-success font-weight-700">${h.tutar}</td>
                                </tr>
                            `);
                        });
                    }
                } else {
                    Swal.fire('Hata!', res.message || 'Kesintiler yüklenemedi.', 'error');
                }
            },
            error: function() {
                Swal.fire('Hata!', 'Sunucu hatası oluştu.', 'error');
            }
        });
    });
});
</script>
