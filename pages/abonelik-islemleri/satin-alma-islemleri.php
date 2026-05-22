<?php
require_once "App/Helper/helper.php";
require_once "Model/OdemelerModel.php";
require_once "Model/AbonelerModel.php";
require_once "Model/AbonelikPaketleriModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;

// Yetki Kontrolü
$perm->checkAuthorize("abonelik_satin_alimlari");

$odemelerModel = new OdemelerModel();
$payments = $odemelerModel->getPayments();

$abonelerModel = new AbonelerModel();
$subscribers = $abonelerModel->getSubscribers();

$paketModel = new AbonelikPaketleriModel();
$packages = $paketModel->getPackages();
?>
<div class="container-xl">
    <!-- Alert component'i dahil et -->
    <?php
    $title = "Satın Alma İşlemleri!";
    $text = "Sistemdeki tüm üyelik ve abonelik ödeme işlemlerini, durumlarını ve ödeme yöntemlerini buradan takip edebilirsiniz.";
    require_once 'pages/components/alert.php';
    ?>
    
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Satın Alma / Ödeme Geçmişi</h3>
                    <div class="col-auto ms-auto">
                        <button type="button" class="btn btn-primary btn-add-transaction">
                            <i class="ti ti-plus icon me-2"></i> Yeni İşlem Ekle
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable" id="odemeTable">
                        <thead>
                            <tr>
                                <th style="width:7%" class="text-center">Sıra</th>
                                <th>Abone Adı Soyadı</th>
                                <th>Email</th>
                                <th>Satın Alınan Paket</th>
                                <th>Tutar</th>
                                <th>Ödeme Tarihi</th>
                                <th>Ödeme Yöntemi</th>
                                <th>Ödeme Durumu</th>
                                <th style="width:10%" class="text-end">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($payments as $pay):
                                $id = Security::encrypt($pay->id);
                                $tutar_format = number_format($pay->tutar, 2, ',', '.') . ' ₺';
                                $odeme_tarihi = $pay->odeme_tarihi ? date('d.m.Y H:i', strtotime($pay->odeme_tarihi)) : '-';
                                
                                // Status badge
                                $status_badge = '';
                                if ($pay->durum == 'basarili') {
                                    $status_badge = '<span class="badge bg-success text-success-fg">Başarılı</span>';
                                } elseif ($pay->durum == 'basarisiz') {
                                    $status_badge = '<span class="badge bg-danger text-danger-fg">Başarısız</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-warning text-warning-fg">Beklemede</span>';
                                }
                                ?>
                                <tr>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td>
                                        <div class="font-weight-medium text-dark"><?php echo htmlspecialchars($pay->subscriber_name ?? '-'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($pay->subscriber_email ?? '-'); ?></td>
                                    <td>
                                        <span class="badge bg-blue-lt"><?php echo htmlspecialchars($pay->paket_adi ?? 'Bilinmeyen Paket'); ?></span>
                                    </td>
                                    <td><strong><?php echo $tutar_format; ?></strong></td>
                                    <td><?php echo $odeme_tarihi; ?></td>
                                    <td>
                                        <span class="text-secondary"><?php echo htmlspecialchars($pay->odeme_yontemi ?? '-'); ?></span>
                                    </td>
                                    <td><?php echo $status_badge; ?></td>
                                    <td class="text-end">
                                        <div class="dropdown">
                                            <button class="btn dropdown-toggle align-text-top" data-bs-toggle="dropdown">Durum Değiştir</button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php if ($pay->durum !== 'basarili'): ?>
                                                    <a class="dropdown-item change-status-btn" href="#" data-id="<?php echo $id; ?>" data-status="basarili">
                                                        <span class="status-dot bg-success me-2"></span> Başarılı Yap
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($pay->durum !== 'beklemede'): ?>
                                                    <a class="dropdown-item change-status-btn" href="#" data-id="<?php echo $id; ?>" data-status="beklemede">
                                                        <span class="status-dot bg-warning me-2"></span> Beklemede Yap
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($pay->durum !== 'basarisiz'): ?>
                                                    <a class="dropdown-item change-status-btn" href="#" data-id="<?php echo $id; ?>" data-status="basarisiz">
                                                        <span class="status-dot bg-danger me-2"></span> Başarısız Yap
                                                    </a>
                                                <?php endif; ?>
                                                <div class="dropdown-divider"></div>
                                                <a class="dropdown-item delete-payment text-danger" href="#" data-id="<?php echo $id; ?>">
                                                    <i class="ti ti-trash icon me-2"></i> Sil
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

<!-- Yeni Satış İşlemi Ekle Modalı -->
<div class="modal modal-blur fade" id="transactionModal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.18); border: none;">
            <div class="modal-header" style="border-bottom: 1px solid rgba(0,0,0,0.06); padding: 1.5rem 1.5rem 1rem;">
                <h5 class="modal-title font-weight-bold" style="font-size: 1.25rem; display: flex; align-items: center; gap: 0.5rem; color: #1d1d20;">
                    <i class="ti ti-shopping-cart icon text-dark" style="font-size: 1.5rem;"></i>
                    <span>Yeni İşlem Ekle</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" id="transactionForm">
                <div class="modal-body" style="padding: 1.5rem;">
                    <!-- Gizli alanlar -->
                    <input type="hidden" name="action" value="addManualSale">

                    <!-- Kullanıcı Seçin -->
                    <div class="mb-3">
                        <label class="form-label required font-weight-semibold" style="color: #1d1d20; font-size: 0.95rem; margin-bottom: 0.5rem;">Kullanıcı Seçin</label>
                        <select name="kullanici_id" id="tx_kullanici_id" class="form-select select2-modal" style="border-radius: 10px;" required>
                            <option value="">Kullanıcı seçiniz...</option>
                            <?php foreach ($subscribers as $sub): ?>
                                <option value="<?php echo Security::encrypt($sub->id); ?>">
                                    <?php echo htmlspecialchars($sub->full_name . ' (' . $sub->email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Paket Seçin -->
                    <div class="mb-3">
                        <label class="form-label required font-weight-semibold" style="color: #1d1d20; font-size: 0.95rem; margin-bottom: 0.5rem;">Paket Seçin</label>
                        <select name="paket_id" id="tx_paket_id" class="form-select select2-modal" style="border-radius: 10px;" required>
                            <option value="">Paket seçiniz...</option>
                            <?php foreach ($packages as $pkg): 
                                if ($pkg->aktif_mi != 1) continue;
                                ?>
                                <option value="<?php echo Security::encrypt($pkg->id); ?>"
                                        data-sure="<?php echo (int)$pkg->sure; ?>"
                                        data-firma_hakki="<?php echo (int)$pkg->firma_hakki; ?>"
                                        data-alt_kullanici_hakki="<?php echo (int)$pkg->alt_kullanici_hakki; ?>"
                                        data-fiyat="<?php echo htmlspecialchars($pkg->fiyat); ?>">
                                    <?php echo htmlspecialchars($pkg->ad . ' - ' . number_format($pkg->fiyat, 2, ',', '.') . ' ₺ (' . $pkg->sure . ' Gün)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Firma Hakkı & Kullanıcı Hakkı -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required font-weight-semibold" style="color: #1d1d20; font-size: 0.95rem; margin-bottom: 0.5rem;">Firma Hakkı</label>
                            <input type="number" min="1" name="firma_hakki" id="tx_firma_hakki" class="form-control" style="border-radius: 10px; padding: 0.65rem 0.8rem; border: 1px solid #dcdcdc; color: #4e4e4e;" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required font-weight-semibold" style="color: #1d1d20; font-size: 0.95rem; margin-bottom: 0.5rem;">Kullanıcı Hakkı</label>
                            <input type="number" min="0" name="alt_kullanici_hakki" id="tx_alt_kullanici_hakki" class="form-control" style="border-radius: 10px; padding: 0.65rem 0.8rem; border: 1px solid #dcdcdc; color: #4e4e4e;" required>
                        </div>
                    </div>

                    <!-- Başlangıç Tarihi & Bitiş Tarihi -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required font-weight-semibold" style="color: #1d1d20; font-size: 0.95rem; margin-bottom: 0.5rem;">Başlangıç Tarihi</label>
                            <input type="text" name="baslangic_tarihi" id="tx_baslangic_tarihi" class="form-control flatpickr-modal" style="border-radius: 10px; padding: 0.65rem 0.8rem; border: 1px solid #dcdcdc; background-color: #fff; color: #4e4e4e;" required value="<?php echo date('d.m.Y'); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required font-weight-semibold" style="color: #1d1d20; font-size: 0.95rem; margin-bottom: 0.5rem;">Bitiş Tarihi</label>
                            <input type="text" name="bitis_tarihi" id="tx_bitis_tarihi" class="form-control flatpickr-modal" style="border-radius: 10px; padding: 0.65rem 0.8rem; border: 1px solid #dcdcdc; background-color: #fff; color: #4e4e4e;" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="border-top: none; padding: 0 1.5rem 1.5rem;">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <button type="button" class="btn w-100" data-bs-dismiss="modal" style="border-radius: 10px; padding: 0.75rem; font-weight: 600; border: 1px solid #e2e8f0; background-color: #fff; color: #4a5568; transition: all 0.2s;">
                                    Vazgeç
                                </button>
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-dark w-100" id="saveTransactionBtn" style="border-radius: 10px; padding: 0.75rem; font-weight: 600; background-color: #1d1d20; border-color: #1d1d20; color: #fff; transition: all 0.2s;">
                                    İşlemi Kaydet
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
    let fpStart = null;
    let fpEnd = null;

    // Initialize flatpickr on load
    if (typeof flatpickr !== 'undefined') {
        fpStart = flatpickr("#tx_baslangic_tarihi", {
            dateFormat: "d.m.Y",
            locale: "tr",
            onChange: function(selectedDates, dateStr, instance) {
                recalculateEndDate();
            }
        });

        fpEnd = flatpickr("#tx_bitis_tarihi", {
            dateFormat: "d.m.Y",
            locale: "tr"
        });
    }

    // Modal show event
    $(document).on('click', '.btn-add-transaction', function(e) {
        e.preventDefault();
        $('#transactionForm')[0].reset();
        
        // Clear select2 values
        $('#tx_kullanici_id').val('').trigger('change');
        $('#tx_paket_id').val('').trigger('change');
        
        // Reset flatpickr to default today
        let today = new Date();
        if (fpStart) fpStart.setDate(today);
        if (fpEnd) fpEnd.setDate('');

        $('#transactionModal').modal('show');
    });

    // Initialize Select2 correctly inside the Modal once it's displayed
    $('#transactionModal').on('shown.bs.modal', function () {
        if ($.fn.select2) {
            $('.select2-modal').select2({
                dropdownParent: $('#transactionModal'),
                width: '100%'
            });
        }
    });

    // Package select trigger
    $(document).on('change', '#tx_paket_id', function() {
        let selectedOpt = $('option:selected', this);
        if (selectedOpt.val()) {
            let firma_hakki = selectedOpt.data('firma_hakki');
            let alt_kullanici_hakki = selectedOpt.data('alt_kullanici_hakki');
            
            $('#tx_firma_hakki').val(firma_hakki);
            $('#tx_alt_kullanici_hakki').val(alt_kullanici_hakki);
            
            recalculateEndDate();
        } else {
            $('#tx_firma_hakki').val('');
            $('#tx_alt_kullanici_hakki').val('');
            if (fpEnd) fpEnd.setDate('');
        }
    });

    // Function to calculate and set end date based on duration
    function recalculateEndDate() {
        let selectedOpt = $('#tx_paket_id option:selected');
        if (!selectedOpt.val()) return;
        
        let duration = parseInt(selectedOpt.data('sure')) || 30;
        let startDateStr = $('#tx_baslangic_tarihi').val();
        if (!startDateStr) return;
        
        let parts = startDateStr.split('.');
        if (parts.length === 3) {
            let day = parseInt(parts[0], 10);
            let month = parseInt(parts[1], 10) - 1;
            let year = parseInt(parts[2], 10);
            
            let startDate = new Date(year, month, day);
            startDate.setDate(startDate.getDate() + duration);
            
            if (fpEnd) {
                fpEnd.setDate(startDate);
            } else {
                let endDay = String(startDate.getDate()).padStart(2, '0');
                let endMonth = String(startDate.getMonth() + 1).padStart(2, '0');
                let endYear = startDate.getFullYear();
                $('#tx_bitis_tarihi').val(`${endDay}.${endMonth}.${endYear}`);
            }
        }
    }

    // Submit handler with jQuery Validate
    $(document).on('submit', '#transactionForm', function(e) {
        e.preventDefault();
        
        var form = $(this);
        form.validate({
            rules: {
                kullanici_id: { required: true },
                paket_id: { required: true },
                firma_hakki: { required: true, digits: true },
                alt_kullanici_hakki: { required: true, digits: true },
                baslangic_tarihi: { required: true },
                bitis_tarihi: { required: true }
            },
            messages: {
                kullanici_id: { required: "Kullanıcı seçimi zorunludur." },
                paket_id: { required: "Paket seçimi zorunludur." },
                firma_hakki: { required: "Firma hakkı zorunludur.", digits: "Tam sayı olmalıdır." },
                alt_kullanici_hakki: { required: "Kullanıcı hakkı zorunludur.", digits: "Tam sayı olmalıdır." },
                baslangic_tarihi: { required: "Başlangıç tarihi zorunludur." },
                bitis_tarihi: { required: "Bitiş tarihi zorunludur." }
            }
        });

        if (!form.valid()) {
            return;
        }

        let formData = new FormData(form[0]);

        fetch('/api/abonelik-islemleri/odemeler.php', {
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
                    $('#transactionModal').modal('hide');
                    window.location.reload();
                }
            });
        })
        .catch(error => {
            Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
        });
    });
});

$(document).on("click", ".change-status-btn", function (e) {
    e.preventDefault();
    let id = $(this).data("id");
    let status = $(this).data("status");
    let statusText = '';
    
    switch (status) {
        case 'basarili': statusText = 'Başarılı'; break;
        case 'basarisiz': statusText = 'Başarısız'; break;
        case 'beklemede': statusText = 'Beklemede'; break;
    }

    Swal.fire({
        title: "Emin misiniz?",
        text: `Ödeme işleminin durumunu '${statusText}' olarak değiştirmek istiyor musunuz?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Evet, Değiştir!",
        cancelButtonText: "İptal"
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append("action", "updatePaymentStatus");
            formData.append("id", id);
            formData.append("status", status);

            fetch("/api/abonelik-islemleri/odemeler.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let iconType = data.status === "success" ? "success" : "error";
                let titleText = data.status === "success" ? "Başarılı!" : "Hata!";
                
                Swal.fire({
                    title: titleText,
                    text: data.message,
                    icon: iconType
                }).then(() => {
                    if (data.status === "success") {
                        window.location.reload();
                    }
                });
            })
            .catch(error => {
                Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
            });
        }
    });
});

// Delete payment handler
$(document).on("click", ".delete-payment", function (e) {
    e.preventDefault();
    let action = "deletePayment";
    let confirmMessage = "Seçilen satın alma işlemi ve ilişkili abonelik silinecektir! Bu işlemi geri alamazsınız.";
    let url = "/api/abonelik-islemleri/odemeler.php";
    
    deleteRecord(this, action, confirmMessage, url);
});
</script>
