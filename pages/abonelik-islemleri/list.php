<?php
require_once "App/Helper/helper.php";
require_once "Model/AbonelerModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;

// Yetki Kontrolü
$perm->checkAuthorize("aboneler_sayfasi");

$abonelerModel = new AbonelerModel();
$subscribers = $abonelerModel->getSubscribers();
?>
<div class="container-xl">
    <!-- Alert component'i dahil et -->
    <?php
    $title = "Aboneler Listesi!";
    $text = "Sistemdeki tüm ana aboneleri (parent_id = 0), aktif paketlerini, başlangıç/bitiş tarihlerini ve kalan gün sürelerini buradan takip edebilirsiniz.";
    require_once 'pages/components/alert.php';
    ?>
    
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Aboneler Listesi</h3>
                    <div class="col-auto ms-auto d-flex align-items-center gap-2">
                        <span id="selected-count" class="text-muted d-none fs-5"></span>
                        <button type="button" id="btn-send-mail" class="btn btn-primary d-none">
                            <i class="ti ti-mail icon me-2"></i> Mail Gönder
                        </button>
                        <button type="button" id="btn-clear-data" class="btn btn-danger d-none">
                            <i class="ti ti-trash icon me-2"></i> Verileri Temizle
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table card-table table-vcenter text-nowrap datatable" id="aboneTable">
                        <thead>
                            <tr>
                                <th style="width:40px" class="text-center">
                                    <input type="checkbox" id="select-all-abones" class="form-check-input m-0">
                                </th>
                                <th style="width:7%" class="text-center">Sıra</th>
                                <th>Adı Soyadı</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Aktif Paket</th>
                                <th>Başlangıç Tarihi</th>
                                <th>Bitiş Tarihi</th>
                                <th>Kalan Gün</th>
                                <th style="width:10%">Durum</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $i = 1;
                            foreach ($subscribers as $sub):
                                // Initials for Avatar
                                $words = explode(" ", trim($sub->full_name));
                                $initials = "";
                                foreach ($words as $w) {
                                    $initials .= mb_substr($w, 0, 1, 'UTF-8');
                                }
                                $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'));
                                if (empty($initials)) {
                                    $initials = "AB";
                                }

                                // Status and package formatting
                                $status = $sub->abonelik_durumu;
                                $paket_adi = $sub->paket_adi;
                                
                                // Format dates
                                $baslangic = $sub->baslangic_tarihi ? date('d.m.Y', strtotime($sub->baslangic_tarihi)) : '-';
                                $bitis = $sub->bitis_tarihi ? date('d.m.Y', strtotime($sub->bitis_tarihi)) : '-';

                                // Calculate remaining days if active and bitis_tarihi exists
                                $kalan_gun_str = '-';
                                $kalan_gun_class = 'text-secondary';
                                if ($status == 'aktif' && $sub->bitis_tarihi) {
                                    $today = new DateTime(date('Y-m-d'));
                                    $end_date = new DateTime($sub->bitis_tarihi);
                                    if ($today <= $end_date) {
                                        $interval = $today->diff($end_date);
                                        $days = (int)$interval->format('%r%a');
                                        if ($days == 0) {
                                            $kalan_gun_str = 'Bugün son gün';
                                            $kalan_gun_class = 'badge bg-warning text-warning-fg';
                                        } else {
                                            $kalan_gun_str = $days . ' gün kaldı';
                                            $kalan_gun_class = $days <= 5 ? 'badge bg-warning text-warning-fg' : 'badge bg-success text-success-fg';
                                        }
                                    } else {
                                        $kalan_gun_str = 'Süresi doldu';
                                        $kalan_gun_class = 'badge bg-danger text-danger-fg';
                                    }
                                }

                                // Status Badge
                                $status_badge = '';
                                if ($status == 'aktif') {
                                    $status_badge = '<span class="badge bg-success text-success-fg">Aktif</span>';
                                } elseif ($status == 'sona_erdi') {
                                    $status_badge = '<span class="badge bg-secondary text-secondary-fg">Sona Erdi</span>';
                                } elseif ($status == 'iptal') {
                                    $status_badge = '<span class="badge bg-danger text-danger-fg">İptal Edildi</span>';
                                } elseif ($status == 'onay_bekliyor') {
                                    $status_badge = '<span class="badge bg-warning text-warning-fg">Onay Bekliyor</span>';
                                } elseif ($status == 'beklemede') {
                                    $status_badge = '<span class="badge bg-info text-info-fg">Beklemede</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-light text-secondary">Abonelik Yok</span>';
                                }
                                ?>
                                <tr>
                                    <td class="text-center">
                                        <input type="checkbox" class="form-check-input m-0 abone-checkbox"
                                               value="<?php echo (int)$sub->id; ?>"
                                               data-name="<?php echo htmlspecialchars($sub->full_name, ENT_QUOTES); ?>"
                                               data-email="<?php echo htmlspecialchars($sub->email, ENT_QUOTES); ?>">
                                    </td>
                                    <td class="text-center"><?php echo $i; ?></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-sm me-2 bg-blue-lt text-blue font-weight-bold"><?php echo htmlspecialchars($initials); ?></span>
                                            <div class="font-weight-medium"><?php echo htmlspecialchars($sub->full_name); ?></div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($sub->email); ?></td>
                                    <td><?php echo htmlspecialchars($sub->phone ?? '-'); ?></td>
                                    <td>
                                        <?php if ($paket_adi): ?>
                                            <span class="font-weight-medium text-primary"><?php echo htmlspecialchars($paket_adi); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Aktif Paket Yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $baslangic; ?></td>
                                    <td><?php echo $bitis; ?></td>
                                    <td>
                                        <span class="<?php echo $kalan_gun_class; ?>">
                                            <?php echo $kalan_gun_str; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $status_badge; ?></td>
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

<style>
/* Summernote toolbar — Tabler Icons uyumu */
.note-toolbar .note-btn i.ti,
.note-popover .note-btn i.ti {
    font-size: 1rem;
    line-height: 1;
    vertical-align: middle;
}
.note-toolbar .note-btn i.ti-chevron-down {
    font-size: 0.65rem;
}
.note-color-all .note-btn i.ti {
    font-size: 0.9rem;
}
</style>

<!-- Mail Gönder Modal -->
<div class="modal modal-blur fade" id="sendMailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ti ti-mail icon me-2 text-primary"></i>
                    Abonelere Mail Gönder
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-muted">Alıcılar</label>
                    <div id="recipients-display" class="p-2 border rounded bg-light" style="min-height:48px;"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label required">Konu</label>
                    <input type="text" id="mail-subject" class="form-control" placeholder="Mail konusunu giriniz...">
                </div>
                <div class="mb-3">
                    <label class="form-label required">İçerik</label>
                    <textarea id="mail-body"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" id="btn-confirm-send" class="btn btn-primary">
                    <i class="ti ti-send icon me-2"></i> Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Veri Temizleme Modal -->
<div class="modal modal-blur fade" id="clearDataModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form autocomplete="off" onsubmit="return false;">
                <!-- Tarayıcı autofill yakalayıcı gizli alan -->
                <input type="text" name="fake_username_autofill" style="display:none;" tabindex="-1" autocomplete="username">
                <div class="modal-header bg-danger-lt">
                    <h5 class="modal-title text-danger">
                        <i class="ti ti-trash icon me-2 text-danger"></i>
                        Abone Verilerini Temizle
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label text-muted">Seçili Aboneler</label>
                        <div id="clear-recipients-display" class="p-2 border rounded bg-light" style="min-height:48px;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label required">Temizlenecek Modüller</label>
                        <div class="card p-3 bg-light-lt">
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="puantaj" checked>
                                    <span class="form-check-label">Puantaj Verileri <small class="text-muted">(Puantaj çalışma saatleri ve tutarları)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="personnel" checked>
                                    <span class="form-check-label">Personel Kayıtları <small class="text-muted">(Personel, izinler, avanslar, ücretler)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="finance" checked>
                                    <span class="form-check-label">Kasa Hareketleri <small class="text-muted">(Gelir ve gider işlemleri)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="companies" checked>
                                    <span class="form-check-label">Cari Firmalar <small class="text-muted">(Müşteri ve tedarikçi cari kartları)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="projects" checked>
                                    <span class="form-check-label">Projeler ve Görevler <small class="text-muted">(Projeler, proje görevleri ve gelir/gider)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="offers" checked>
                                    <span class="form-check-label">Teklifler <small class="text-muted">(Teklifler ve teklif kalemleri)</small></span>
                                </label>
                            </div>
                            <div class="mb-2">
                                <label class="form-check m-0">
                                    <input class="form-check-input clear-module-checkbox" type="checkbox" value="roles" checked>
                                    <span class="form-check-label">Yetki Grupları <small class="text-muted">(Tanımlanan kullanıcı rolleri ve yetkileri)</small></span>
                                </label>
                            </div>
                            <div class="mb-0">
                                <label class="form-check m-0 text-danger">
                                    <input class="form-check-input clear-module-checkbox border-danger" type="checkbox" value="myfirms">
                                    <span class="form-check-label font-weight-bold">Firmalarım <small class="text-danger">(Kendi tanımladığınız firmalar. UYARI: Sıfırlanır!)</small></span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Yönetici Şifreniz</label>
                        <input type="password" id="admin-password" name="admin_password" class="form-control" placeholder="İşlemi onaylamak için şifrenizi giriniz..." autocomplete="current-password" data-lpignore="true" data-1p-ignore="true" data-bwignore="true">
                    </div>

                    <div class="alert alert-warning mb-0">
                        <h4 class="alert-title"><i class="ti ti-alert-triangle me-1"></i>Dikkat!</h4>
                        <div class="text-muted">Seçtiğiniz veriler kalıcı olarak silinecektir ve bu işlem geri alınamaz.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="button" id="btn-confirm-clear" class="btn btn-danger">
                        <i class="ti ti-trash icon me-2"></i> Verileri Kalıcı Olarak Sil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    // Summernote başlat
    $('#mail-body').summernote({
        height: 280,
        lang: 'tr-TR',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['font', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link', 'hr']],
            ['view', ['undo', 'redo', 'fullscreen', 'codeview']]
        ],
        icons: {
            'align':          'ti ti-align-left',
            'alignCenter':    'ti ti-align-center',
            'alignJustify':   'ti ti-align-justified',
            'alignLeft':      'ti ti-align-left',
            'alignRight':     'ti ti-align-right',
            'rowBelow':       'ti ti-row-insert-bottom',
            'colBefore':      'ti ti-column-insert-left',
            'colAfter':       'ti ti-column-insert-right',
            'rowAbove':       'ti ti-row-insert-top',
            'rowRemove':      'ti ti-table-row',
            'colRemove':      'ti ti-table-column',
            'indent':         'ti ti-indent-increase',
            'outdent':        'ti ti-indent-decrease',
            'arrowsAlt':      'ti ti-arrows-maximize',
            'bold':           'ti ti-bold',
            'caret':          'ti ti-chevron-down',
            'circle':         'ti ti-circle',
            'close':          'ti ti-x',
            'code':           'ti ti-code',
            'eraser':         'ti ti-eraser',
            'floatLeft':      'ti ti-layout-align-left',
            'floatRight':     'ti ti-layout-align-right',
            'font':           'ti ti-typography',
            'frame':          'ti ti-border-outer',
            'italic':         'ti ti-italic',
            'link':           'ti ti-link',
            'unlink':         'ti ti-link-off',
            'magic':          'ti ti-wand',
            'menuCheck':      'ti ti-check',
            'minus':          'ti ti-minus',
            'orderedlist':    'ti ti-list-numbers',
            'pencil':         'ti ti-pencil',
            'picture':        'ti ti-photo',
            'question':       'ti ti-help',
            'redo':           'ti ti-arrow-forward-up',
            'rollback':       'ti ti-rotate',
            'square':         'ti ti-square',
            'strikethrough':  'ti ti-strikethrough',
            'subscript':      'ti ti-subscript',
            'superscript':    'ti ti-superscript',
            'table':          'ti ti-table',
            'textHeight':     'ti ti-text-size',
            'trash':          'ti ti-trash',
            'underline':      'ti ti-underline',
            'undo':           'ti ti-arrow-back-up',
            'unorderedlist':  'ti ti-list',
            'video':          'ti ti-video'
        },
        fontNames: ['inter', 'Arial', 'Arial Black', 'Courier New'],
        addDefaultFonts: 'inter',
        callbacks: {
            onInit: function () {
                $('#mail-body').summernote('fontName', 'inter');
            }
        }
    });

    const selectAll = document.getElementById('select-all-abones');
    const btnSendMail = document.getElementById('btn-send-mail');
    const btnClearData = document.getElementById('btn-clear-data');
    const selectedCountEl = document.getElementById('selected-count');

    function getChecked() {
        return document.querySelectorAll('.abone-checkbox:checked');
    }

    function updateUI() {
        const count = getChecked().length;
        if (count > 0) {
            btnSendMail.classList.remove('d-none');
            btnClearData.classList.remove('d-none');
            selectedCountEl.classList.remove('d-none');
            selectedCountEl.textContent = count + ' kişi seçildi';
        } else {
            btnSendMail.classList.add('d-none');
            btnClearData.classList.add('d-none');
            selectedCountEl.classList.add('d-none');
        }
        const allBoxes = document.querySelectorAll('.abone-checkbox');
        selectAll.checked = allBoxes.length > 0 && count === allBoxes.length;
        selectAll.indeterminate = count > 0 && count < allBoxes.length;
    }

    selectAll.addEventListener('change', function () {
        document.querySelectorAll('.abone-checkbox').forEach(cb => cb.checked = this.checked);
        updateUI();
    });

    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('abone-checkbox')) {
            updateUI();
        }
    });

    btnSendMail.addEventListener('click', function () {
        const checked = getChecked();
        const display = document.getElementById('recipients-display');
        display.innerHTML = [...checked].map(cb =>
            `<span class="badge bg-blue-lt text-blue me-1 mb-1">${cb.dataset.name} &lt;${cb.dataset.email}&gt;</span>`
        ).join('');
        document.getElementById('mail-subject').value = '';
        $('#mail-body').summernote('reset');
        new bootstrap.Modal(document.getElementById('sendMailModal')).show();
    });

    document.getElementById('btn-confirm-send').addEventListener('click', function () {
        const checked = getChecked();
        const userIds = [...checked].map(cb => cb.value);
        const subject = document.getElementById('mail-subject').value.trim();
        const body = $('#mail-body').summernote('code').trim();
        const bodyText = $('#mail-body').summernote('isEmpty');

        if (!subject) {
            Swal.fire('Uyarı', 'Lütfen mail konusunu giriniz.', 'warning');
            return;
        }
        if (bodyText) {
            Swal.fire('Uyarı', 'Lütfen mail içeriğini giriniz.', 'warning');
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gönderiliyor...';

        fetch('/api/abonelik-islemleri/send-mail.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_ids: userIds, subject: subject, body: body})
        })
        .then(res => res.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('sendMailModal')).hide();
            Swal.fire({
                title: data.success ? 'Başarılı' : 'Hata',
                text: data.message,
                icon: data.success ? 'success' : 'error'
            }).then(() => {
                if (data.success) {
                    document.querySelectorAll('.abone-checkbox').forEach(cb => cb.checked = false);
                    selectAll.checked = false;
                    updateUI();
                }
            });
        })
        .catch(err => {
            Swal.fire('Hata', 'Bir hata oluştu: ' + err.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-send icon me-2"></i> Gönder';
        });
    });

    btnClearData.addEventListener('click', function () {
        const checked = getChecked();
        const display = document.getElementById('clear-recipients-display');
        display.innerHTML = [...checked].map(cb =>
            `<span class="badge bg-blue-lt text-blue me-1 mb-1">${cb.dataset.name}</span>`
        ).join('');
        document.getElementById('admin-password').value = '';
        document.querySelectorAll('.clear-module-checkbox').forEach(cb => {
            if (cb.value === 'myfirms') {
                cb.checked = false;
            } else {
                cb.checked = true;
            }
        });
        new bootstrap.Modal(document.getElementById('clearDataModal')).show();
    });

    document.getElementById('btn-confirm-clear').addEventListener('click', function () {
        const checked = getChecked();
        const userIds = [...checked].map(cb => cb.value);
        
        const selectedModules = [];
        document.querySelectorAll('.clear-module-checkbox:checked').forEach(cb => {
            selectedModules.push(cb.value);
        });

        const password = document.getElementById('admin-password').value.trim();

        if (selectedModules.length === 0) {
            Swal.fire('Uyarı', 'Lütfen temizlemek istediğiniz en az bir modül seçiniz.', 'warning');
            return;
        }

        if (!password) {
            Swal.fire('Uyarı', 'Lütfen işlemi onaylamak için şifrenizi giriniz.', 'warning');
            return;
        }

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Temizleniyor...';

        fetch('/api/abonelik-islemleri/clear-data.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_ids: userIds, modules: selectedModules, password: password})
        })
        .then(res => res.json())
        .then(data => {
            bootstrap.Modal.getInstance(document.getElementById('clearDataModal')).hide();
            Swal.fire({
                title: data.success ? 'Başarılı' : 'Hata',
                text: data.message,
                icon: data.success ? 'success' : 'error'
            }).then(() => {
                if (data.success) {
                    document.querySelectorAll('.abone-checkbox').forEach(cb => cb.checked = false);
                    selectAll.checked = false;
                    updateUI();
                }
            });
        })
        .catch(err => {
            Swal.fire('Hata', 'Bir hata oluştu: ' + err.message, 'error');
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="ti ti-trash icon me-2"></i> Verileri Kalıcı Olarak Sil';
        });
    });
});
</script>
