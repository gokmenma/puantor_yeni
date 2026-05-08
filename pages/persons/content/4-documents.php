<?php
use App\Helper\Security;

if (!$Auths->Authorize("personnel_add_update")) {
    require_once "App/Helper/helper.php";
    App\Helper\Helper::authorizePage();
    return;
}
$person_id_encrypted = Security::encrypt($person->id);
?>
<div class="container-xl mt-3">
    <!-- Header with Stats and Modern Compliance Progress Bar -->
    <div class="row row-cards row-deck mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card card-sm bg-gradient-zinc shadow-sm border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-blue-lt text-blue avatar avatar-md border-0 shadow-sm" style="background-color: rgba(32, 107, 196, 0.1);">
                                <i class="ti ti-checklist fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary text-uppercase fs-6 tracking-wide">Belge Tamamlanma Oranı</div>
                            <div class="d-flex align-items-baseline mt-1">
                                <span class="h2 mb-0 me-2" id="compliance-percentage">0%</span>
                                <span class="text-secondary fs-5" id="compliance-count">(0 / 7 Belge)</span>
                            </div>
                        </div>
                    </div>
                    <div class="progress progress-sm mt-3" style="height: 6px; border-radius: 3px; background-color: rgba(0,0,0,0.05); margin-top: auto !important;">
                        <div class="progress-bar bg-blue" id="compliance-progress-bar" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4">
            <div class="card card-sm bg-gradient-zinc shadow-sm border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-teal-lt text-teal avatar avatar-md border-0 shadow-sm" style="background-color: rgba(47, 179, 171, 0.1);">
                                <i class="ti ti-files fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary text-uppercase fs-6 tracking-wide">Toplam Ek Belgeler</div>
                            <div class="d-flex align-items-baseline mt-1">
                                <span class="h2 mb-0 me-2" id="custom-files-count">0</span>
                                <span class="text-secondary fs-5">Eklenmiş Belge</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary mt-3 fs-5 d-flex align-items-center" style="margin-top: auto !important;">
                        <i class="ti ti-circle-check text-success me-1"></i>
                        <span>Sözleşme dökümanları ve ek sertifikalar</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-4 d-none d-lg-block">
            <div class="card card-sm bg-gradient-zinc shadow-sm border-0" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div class="row align-items-center">
                        <div class="col-auto">
                            <span class="bg-purple-lt text-purple avatar avatar-md border-0 shadow-sm" style="background-color: rgba(116, 57, 189, 0.1);">
                                <i class="ti ti-shield-lock fs-2"></i>
                            </span>
                        </div>
                        <div class="col">
                            <div class="font-weight-medium text-secondary text-uppercase fs-6 tracking-wide">Yüksek Güvenlik ve Gizlilik</div>
                            <div class="d-flex align-items-baseline mt-1">
                                <span class="h3 mb-0 me-2 text-dark font-weight-700">ISO 27001</span>
                            </div>
                        </div>
                    </div>
                    <div class="text-secondary mt-3 fs-5" style="margin-top: auto !important;">
                        Tüm personel belgeleri şifrelenerek saklanır, tenant izolasyonu ile korunur.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Standard Documents Grid -->
    <div class="row row-cards mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex align-items-center">
                    <div>
                        <h3 class="card-title font-weight-700 text-dark">Özlük Dosyası Belgeleri</h3>
                        <p class="text-secondary mb-0 fs-5 mt-1">Yasal olarak özlük dosyasında bulunması gereken zorunlu belgeler.</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row row-cards" id="standard-docs-container">
                        <!-- Dynamic Loading Spinner -->
                        <div class="col-12 text-center py-5" id="docs-loading-spinner">
                            <div class="spinner-border text-primary" role="status"></div>
                            <div class="text-secondary mt-2 fs-4">Belgeler yükleniyor, lütfen bekleyin...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Other Documents Table Section -->
    <div class="row row-cards">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white border-bottom-0 py-3 d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title font-weight-700 text-dark">Ek / Diğer Belgeler</h3>
                        <p class="text-secondary mb-0 fs-5 mt-1">Eğitim sertifikaları, sözleşmeler, zimmet formları vb. diğer dökümanlar.</p>
                    </div>
                    <button type="button" class="btn btn-primary shadow-sm" id="btn-add-custom-doc">
                        <i class="ti ti-plus icon me-2"></i> Yeni Belge Ekle
                    </button>
                </div>
                <div class="table-responsive">
                    <table class="table card-table table-sm text-nowrap table-hover" id="custom-docs-table">
                        <thead>
                            <tr class="bg-light text-secondary">
                                <th style="width: 5%">Sıra</th>
                                <th style="width: 40%">Belge Adı</th>
                                <th style="width: 30%">Orijinal Dosya Adı</th>
                                <th style="width: 15%">Yükleme Tarihi</th>
                                <th style="width: 10%" class="text-end">İşlemler</th>
                            </tr>
                        </thead>
                        <tbody id="custom-docs-tbody">
                            <!-- Custom files rendered here dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Unified Document Upload Modal -->
<div class="modal modal-blur fade" id="modal-document-upload" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <form id="form-document-upload" enctype="multipart/form-data">
                <input type="hidden" name="action" value="upload">
                <input type="hidden" name="person_id" value="<?php echo $person_id_encrypted; ?>">
                <input type="hidden" name="doc_type" id="upload-doc-type" value="">

                <div class="modal-header">
                    <h5 class="modal-title font-weight-700 text-dark" id="upload-modal-title">Belge Yükle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>

                <div class="modal-body">
                    <!-- Standard Type Badge -->
                    <div class="mb-3 d-none" id="standard-type-badge-container">
                        <label class="form-label text-secondary">Belge Türü</label>
                        <span class="badge bg-blue text-blue-fg fs-4 px-3 py-2" id="standard-type-badge">Standart Belge</span>
                    </div>

                    <!-- Custom Title Field -->
                    <div class="mb-3 d-none" id="custom-title-field-container">
                        <label class="form-label font-weight-600 text-dark">Belge Başlığı (*)</label>
                        <input type="text" class="form-control" name="doc_title" id="upload-doc-title" placeholder="Örn: Forklift Ehliyeti, İş Sözleşmesi 2026">
                    </div>

                    <!-- Beautiful File Drag & Drop Field -->
                    <div class="mb-3">
                        <label class="form-label font-weight-600 text-dark">Dosya Seçin (*)</label>
                        <div class="border-2 border-dashed border-primary-subtle rounded-3 p-4 text-center bg-light cursor-pointer position-relative" id="file-drop-area" style="border-style: dashed !important;">
                            <input type="file" name="document_file" id="upload-document-file" class="position-absolute top-0 start-0 w-100 h-100 opacity-0 cursor-pointer" required>
                            <span class="avatar avatar-md bg-blue-lt text-blue border-0 shadow-sm mb-3" style="background-color: rgba(32, 107, 196, 0.1);">
                                <i class="ti ti-upload fs-2"></i>
                            </span>
                            <h4 class="font-weight-600 mb-1" id="file-drag-title">Dosyayı buraya bırakın veya tıklayın</h4>
                            <p class="text-secondary mb-0 fs-5">Maksimum dosya boyutu: 10MB. İzin verilen formatlar: PDF, PNG, JPG, Word, Excel.</p>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary" id="btn-submit-upload">
                        <i class="ti ti-upload icon me-2"></i> Belgeyi Yükle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .cursor-pointer {
        cursor: pointer;
    }
    .hover-shadow-sm:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05) !important;
        transition: all 0.25s ease-in-out;
    }
    .card {
        transition: all 0.25s ease-in-out;
    }
    .border-dashed {
        border-style: dashed !important;
    }
</style>

<script>
$(document).ready(function() {
    const personIdEncrypted = '<?php echo $person_id_encrypted; ?>';

    // Belgeleri Getir ve Listele
    function loadDocuments() {
        $('#docs-loading-spinner').show();
        $.ajax({
            url: 'api/persons/documents.php',
            type: 'POST',
            data: {
                action: 'list',
                person_id: personIdEncrypted
            },
            dataType: 'json',
            success: function(response) {
                $('#docs-loading-spinner').hide();
                if (response.status === 'success') {
                    renderStandardDocs(response.standard_docs, response.standard_files);
                    renderCustomDocs(response.custom_files);
                    updateStats(response.standard_files, response.custom_files);
                } else {
                    Swal.fire('Hata!', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                $('#docs-loading-spinner').hide();
                console.error(error);
                Swal.fire('Hata!', 'Belgeler listelenirken sunucu hatası oluştu.', 'error');
            }
        });
    }

    // Standart Belgeleri Arayüze Çiz
    function renderStandardDocs(standardDocs, standardFiles) {
        const container = $('#standard-docs-container');
        container.empty();

        let uploadedCount = 0;

        // Her bir standart belge türünü çizelim
        Object.keys(standardDocs).forEach(function(key) {
            const title = standardDocs[key];
            const file = standardFiles[key];
            const isUploaded = !!file;

            let cardHtml = '';
            if (isUploaded) {
                uploadedCount++;
                const uploadDate = file.uploaded_at ? file.uploaded_at.substring(0, 10).split('-').reverse().join('.') : '';
                cardHtml = `
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card card-sm hover-shadow-sm border-start border-start-width-3 border-success h-100">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="avatar avatar-sm bg-success-lt text-success border-0 me-2" style="background-color: rgba(47, 179, 79, 0.1);">
                                            <i class="ti ti-file-check fs-2"></i>
                                        </span>
                                        <span class="badge bg-success-lt text-success fs-6">Yüklendi</span>
                                    </div>
                                    <h4 class="font-weight-700 text-dark mb-1 text-truncate" title="${title}">${title}</h4>
                                    <div class="text-secondary text-truncate fs-5" title="${file.original_name}">${file.original_name}</div>
                                    <div class="text-secondary fs-5 mt-1"><i class="ti ti-calendar me-1"></i>${uploadDate}</div>
                                </div>
                                <div class="mt-3 pt-2 border-top d-flex justify-content-end gap-2">
                                    <a href="api/persons/documents.php?action=download&person_id=${personIdEncrypted}&doc_type=${key}" target="_blank" class="btn btn-sm btn-outline-primary" title="Görüntüle / İndir">
                                        <i class="ti ti-download me-1"></i> İndir
                                    </a>
                                    <button class="btn btn-sm btn-outline-danger btn-delete-doc" data-type="${key}" title="Sil">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                cardHtml = `
                    <div class="col-md-6 col-lg-4 col-xl-3">
                        <div class="card card-sm hover-shadow-sm border-start border-start-width-3 border-warning h-100">
                            <div class="card-body p-3 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex align-items-center mb-2">
                                        <span class="avatar avatar-sm bg-warning-lt text-warning border-0 me-2" style="background-color: rgba(245, 159, 0, 0.1);">
                                            <i class="ti ti-file-alert fs-2"></i>
                                        </span>
                                        <span class="badge bg-warning-lt text-warning fs-6">Eksik</span>
                                    </div>
                                    <h4 class="font-weight-700 text-dark mb-1 text-truncate" title="${title}">${title}</h4>
                                    <div class="text-secondary fs-5">Bu belge henüz yüklenmedi.</div>
                                </div>
                                <div class="mt-3 pt-2 border-top d-flex justify-content-end">
                                    <button class="btn btn-sm btn-primary btn-upload-doc" data-type="${key}" data-title="${title}">
                                        <i class="ti ti-upload me-1"></i> Yükle
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }
            container.append(cardHtml);
        });
    }

    // Özel Belgeleri Arayüze Çiz
    function renderCustomDocs(customFiles) {
        const tbody = $('#custom-docs-tbody');
        tbody.empty();

        if (customFiles.length === 0) {
            tbody.append(`
                <tr>
                    <td colspan="5" class="text-center py-4 text-secondary">
                        <i class="ti ti-info-circle me-1 fs-3"></i> Eklenmiş herhangi bir özel belge bulunamadı.
                    </td>
                </tr>
            `);
            return;
        }

        customFiles.forEach(function(file, index) {
            const uploadDate = file.uploaded_at ? file.uploaded_at.substring(0, 10).split('-').reverse().join('.') : '';
            tbody.append(`
                <tr>
                    <td class="text-secondary">${index + 1}</td>
                    <td class="font-weight-600 text-dark">${file.title}</td>
                    <td class="text-secondary">${file.original_name}</td>
                    <td class="text-secondary">${uploadDate}</td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="api/persons/documents.php?action=download&person_id=${personIdEncrypted}&doc_type=custom&custom_id=${file.id}" target="_blank" class="btn btn-sm btn-outline-primary" title="Görüntüle / İndir">
                                <i class="ti ti-download me-1"></i> İndir
                            </a>
                            <button class="btn btn-sm btn-outline-danger btn-delete-doc" data-type="custom" data-id="${file.id}" title="Sil">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `);
        });
    }

    // İstatistik ve Progress Bar Güncellemesi
    function updateStats(standardFiles, customFiles) {
        let standardCount = 0;
        const totalStandard = 7;

        Object.keys(standardFiles).forEach(function(key) {
            if (standardFiles[key]) {
                standardCount++;
            }
        });

        const percent = Math.round((standardCount / totalStandard) * 100);

        $('#compliance-percentage').text(percent + '%');
        $('#compliance-count').text(`(${standardCount} / ${totalStandard} Belge)`);
        $('#compliance-progress-bar').css('width', percent + '%').attr('aria-valuenow', percent);

        // Progress bar rengini duruma göre ayarlama
        if (percent < 40) {
            $('#compliance-progress-bar').removeClass('bg-blue bg-success').addClass('bg-warning');
        } else if (percent < 80) {
            $('#compliance-progress-bar').removeClass('bg-warning bg-success').addClass('bg-blue');
        } else {
            $('#compliance-progress-bar').removeClass('bg-warning bg-blue').addClass('bg-success');
        }

        $('#custom-files-count').text(customFiles.length);
    }

    // Yükleme Tetikleyici Butonlar (Standart Belgeler İçin)
    $(document).on('click', '.btn-upload-doc', function() {
        const docType = $(this).data('type');
        const docTitle = $(this).data('title');

        $('#upload-doc-type').val(docType);
        $('#upload-modal-title').text('Zorunlu Belge Yükle');
        $('#standard-type-badge').text(docTitle);

        $('#standard-type-badge-container').removeClass('d-none');
        $('#custom-title-field-container').addClass('d-none');
        $('#upload-doc-title').prop('required', false);

        // Reset file field
        $('#upload-document-file').val('');
        $('#file-drag-title').text('Dosyayı buraya bırakın veya tıklayın');

        $('#modal-document-upload').modal('show');
    });

    // Yükleme Tetikleyici Butonlar (Özel Belgeler İçin)
    $('#btn-add-custom-doc').on('click', function() {
        $('#upload-doc-type').val('custom');
        $('#upload-modal-title').text('Özel Belge Ekle');

        $('#standard-type-badge-container').addClass('d-none');
        $('#custom-title-field-container').removeClass('d-none');
        $('#upload-doc-title').val('').prop('required', true);

        // Reset file field
        $('#upload-document-file').val('');
        $('#file-drag-title').text('Dosyayı buraya bırakın veya tıklayın');

        $('#modal-document-upload').modal('show');
    });

    // Dosya Değişikliğinde İsim Gösterme
    $('#upload-document-file').on('change', function() {
        const file = this.files[0];
        if (file) {
            $('#file-drag-title').text(file.name + ' (' + (file.size / 1024 / 1024).toFixed(2) + ' MB)');
        }
    });

    // Dosya Yükleme Postu
    $('#form-document-upload').on('submit', function(e) {
        e.preventDefault();

        const btn = $('#btn-submit-upload');
        const originalHtml = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...');

        const formData = new FormData(this);

        $.ajax({
            url: 'api/persons/documents.php',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                btn.prop('disabled', false).html(originalHtml);
                if (response.status === 'success') {
                    $('#modal-document-upload').modal('hide');
                    Swal.fire({
                        title: 'Başarılı!',
                        text: response.message,
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    loadDocuments();
                } else {
                    Swal.fire('Hata!', response.message, 'error');
                }
            },
            error: function(xhr, status, error) {
                btn.prop('disabled', false).html(originalHtml);
                console.error(error);
                Swal.fire('Hata!', 'Sunucuyla bağlantı kurulurken hata oluştu.', 'error');
            }
        });
    });

    // Belge Silme İşlemi
    $(document).on('click', '.btn-delete-doc', function() {
        const docType = $(this).data('type');
        const customId = $(this).data('id') || '';

        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu belgeyi kalıcı olarak silmek istediğinizden emin misiniz?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil!',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'api/persons/documents.php',
                    type: 'POST',
                    data: {
                        action: 'delete',
                        person_id: personIdEncrypted,
                        doc_type: docType,
                        custom_id: customId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            Swal.fire({
                                title: 'Silindi!',
                                text: response.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            });
                            loadDocuments();
                        } else {
                            Swal.fire('Hata!', response.message, 'error');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                        Swal.fire('Hata!', 'Silme işlemi sırasında sunucu hatası oluştu.', 'error');
                    }
                });
            }
        });
    });

    // İlk Yüklemede Belgeleri Al
    loadDocuments();
});
</script>