<?php

require_once ROOT . '/Model/MailIslemleriModel.php';
require_once ROOT . '/Model/SettingsModel.php';

if ((int) ($_SESSION['user']->superadmin ?? 0) !== 1) {
    echo '<div class="container-xl py-5"><div class="alert alert-danger"><i class="ti ti-lock me-2"></i>Bu sayfaya erişim yetkiniz yok.</div></div>';
    return;
}

$mailModel = new MailIslemleriModel();
$systemUsers = $mailModel->getSystemUsers();
$settingsModel = new SettingsModel();
$infoEmail = (string) $settingsModel->getSystemSetting('smtp_info_username');
$supportEmail = (string) $settingsModel->getSystemSetting('smtp_support_username');
$infoReady = filter_var($infoEmail, FILTER_VALIDATE_EMAIL) !== false;
$supportReady = filter_var($supportEmail, FILTER_VALIDATE_EMAIL) !== false;
$csrfToken = (string) ($_SESSION['csrf_token'] ?? '');
?>

<div class="page-header d-print-none mb-0">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Sistem Yönetimi</div>
                <h2 class="page-title">Mail İşlemleri</h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#mailComposeModal">
                    <i class="ti ti-mail-plus me-2"></i>Yeni Mail Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!$infoReady && !$supportReady): ?>
            <div class="alert alert-warning d-flex align-items-center" role="alert">
                <i class="ti ti-alert-triangle fs-2 me-2"></i>
                <div class="flex-fill">Mail hesapları henüz yapılandırılmamış. Gönderimden önce sistem SMTP ayarlarını kaydedin.</div>
                <a href="index.php?p=settings/manage&view=system&tab=smtp" class="btn btn-warning btn-sm">SMTP Ayarları</a>
            </div>
        <?php endif; ?>
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar bg-primary-lt me-3"><i class="ti ti-mail-forward"></i></span>
                        <div><div class="h2 mb-0" id="mailStatTotal">—</div><div class="text-secondary small">Toplam Gönderim</div></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar bg-success-lt me-3"><i class="ti ti-circle-check"></i></span>
                        <div><div class="h2 mb-0" id="mailStatSuccess">—</div><div class="text-secondary small">Başarılı Mail</div></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar bg-danger-lt me-3"><i class="ti ti-alert-circle"></i></span>
                        <div><div class="h2 mb-0" id="mailStatFailed">—</div><div class="text-secondary small">Başarısız Mail</div></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body d-flex align-items-center">
                        <span class="avatar bg-azure-lt me-3"><i class="ti ti-calendar"></i></span>
                        <div><div class="h2 mb-0" id="mailStatToday">—</div><div class="text-secondary small">Bugün Alıcı</div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title">Gönderim Geçmişi</h3>
                    <p class="card-subtitle">Gönderilen mailleri ve alıcı bazındaki sonuçları izleyin.</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter w-100" id="mailHistoryTable">
                    <thead>
                        <tr>
                            <th>Tarih</th>
                            <th>Gönderen</th>
                            <th>Alıcı Türü</th>
                            <th>Konu</th>
                            <th>Sonuç</th>
                            <th>Durum</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
#mailComposeModal .modal-dialog {
    height: calc(100vh - 1rem);
    margin-top: .5rem;
    margin-bottom: .5rem;
}

#mailComposeModal .modal-content {
    max-height: 100%;
}

#mailComposeForm {
    display: flex;
    flex: 1 1 auto;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;
}

#mailComposeForm .modal-body {
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
}

#mailComposeForm .modal-footer {
    flex: 0 0 auto;
}

@media (max-height: 700px) {
    #mailComposeForm .note-editable {
        min-height: 150px !important;
        height: 150px !important;
    }
}
</style>

<div class="modal modal-blur fade" id="mailComposeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-mail-forward text-primary me-2"></i>Yeni Mail Gönder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="mailComposeForm">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="send">
                <div class="modal-body">
                    <div class="alert alert-info">
                        <div class="d-flex">
                            <i class="ti ti-info-circle fs-2 me-2"></i>
                            <div>Her alıcıya ayrı mail gönderilir. Alıcılar birbirlerinin adreslerini göremez ve her teslim sonucu ayrı kaydedilir.</div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-lg-6">
                            <label class="form-label required">Gönderen Hesabı</label>
                            <select class="form-select" name="gonderen_hesabi" id="mailSenderAccount" required>
                                <option value="info" <?php echo $infoReady ? '' : 'disabled'; ?>>Bilgilendirme — <?php echo htmlspecialchars($infoReady ? $infoEmail : 'Yapılandırılmamış', ENT_QUOTES, 'UTF-8'); ?></option>
                                <option value="support" <?php echo $supportReady ? '' : 'disabled'; ?>>Destek — <?php echo htmlspecialchars($supportReady ? $supportEmail : 'Yapılandırılmamış', ENT_QUOTES, 'UTF-8'); ?></option>
                            </select>
                        </div>
                        <div class="col-lg-6">
                            <label class="form-label required">Alıcı Türü</label>
                            <div class="form-selectgroup w-100">
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="alici_turu" value="secili" class="form-selectgroup-input" checked>
                                    <span class="form-selectgroup-label text-center"><i class="ti ti-user-check me-1"></i>Seçili Kullanıcılar</span>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="alici_turu" value="tumu" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label text-center"><i class="ti ti-users me-1"></i>Tümü (<?php echo count($systemUsers); ?>)</span>
                                </label>
                                <label class="form-selectgroup-item flex-fill">
                                    <input type="radio" name="alici_turu" value="harici" class="form-selectgroup-input">
                                    <span class="form-selectgroup-label text-center"><i class="ti ti-world me-1"></i>Harici</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3" id="selectedUsersArea">
                        <label class="form-label required">Sistem Kullanıcıları</label>
                        <select class="form-select" name="kullanici_ids[]" id="mailSystemUsers" multiple style="width:100%">
                            <?php foreach ($systemUsers as $systemUser): ?>
                                <option value="<?php echo (int) $systemUser->id; ?>">
                                    <?php
                                    $optionText = $systemUser->full_name . ' — ' . $systemUser->email;
                                    if (!empty($systemUser->firm_name)) {
                                        $optionText .= ' (' . $systemUser->firm_name . ')';
                                    }
                                    echo htmlspecialchars($optionText, ENT_QUOTES, 'UTF-8');
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="externalEmailsArea">
                        <label class="form-label required">Harici E-posta Adresleri</label>
                        <textarea class="form-control" name="harici_emailler" rows="3" placeholder="ornek@firma.com, ikinci@firma.com"></textarea>
                        <div class="form-hint">Adresleri virgül, noktalı virgül, boşluk veya yeni satırla ayırabilirsiniz.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Konu</label>
                        <input type="text" class="form-control" name="konu" maxlength="255" required placeholder="Mail konusu">
                    </div>

                    <div>
                        <label class="form-label required">Mesaj</label>
                        <textarea class="form-control" name="icerik" id="mailBody" rows="10"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary ms-auto" id="mailSendButton">
                        <i class="ti ti-send me-2"></i>Maili Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="mailDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title">Gönderim Detayı</h5>
                    <div class="text-secondary small" id="mailDetailSummary"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter card-table mb-0">
                        <thead><tr><th>Alıcı</th><th>E-posta</th><th>Durum</th><th>Gönderilme Tarihi</th></tr></thead>
                        <tbody id="mailDetailRecipients"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn" data-bs-dismiss="modal">Kapat</button></div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const apiUrl = '/api/mail-islemleri/index.php';
    const composeForm = document.getElementById('mailComposeForm');
    const sendButton = document.getElementById('mailSendButton');

    $('#mailSystemUsers').select2({
        dropdownParent: $('#mailComposeModal'),
        placeholder: 'Kullanıcı seçin',
        closeOnSelect: false,
        width: '100%'
    });

    $('#mailBody').summernote({
        height: 260,
        lang: 'tr-TR',
        placeholder: 'Mail içeriğini yazın...',
        toolbar: [
            ['style', ['bold', 'italic', 'underline', 'clear']],
            ['font', ['strikethrough']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['insert', ['link']],
            ['view', ['codeview']]
        ]
    });

    const escapeHtml = function (value) {
        return $('<div>').text(value == null ? '' : String(value)).html();
    };

    const formatDate = function (value) {
        if (!value) return '—';
        const parts = String(value).split(/[-T :]/);
        return parts.length >= 5 ? `${parts[2]}.${parts[1]}.${parts[0]} ${parts[3]}:${parts[4]}` : escapeHtml(value);
    };

    const statusBadge = function (status) {
        const statuses = {
            tamamlandi: ['success', 'Tamamlandı'],
            kismi: ['warning', 'Kısmi'],
            basarisiz: ['danger', 'Başarısız'],
            gonderiliyor: ['azure', 'Gönderiliyor'],
            basarili: ['success', 'Başarılı'],
            bekliyor: ['secondary', 'Bekliyor']
        };
        const item = statuses[status] || ['secondary', status || '—'];
        return `<span class="badge bg-${item[0]}-lt">${escapeHtml(item[1])}</span>`;
    };

    const recipientLabels = { secili: 'Seçili Kullanıcılar', tumu: 'Tüm Sistem Kullanıcıları', harici: 'Harici Adresler' };

    const table = $('#mailHistoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: { url: apiUrl, data: { action: 'list' } },
        pageLength: 25,
        order: [],
        columns: [
            { data: 'created_at', render: formatDate },
            { data: null, render: function (data) { return `<div>${escapeHtml(data.gonderen_adi || '—')}</div><div class="text-secondary small">${escapeHtml(data.gonderen_email)}</div>`; } },
            { data: 'alici_turu', render: function (value) { return escapeHtml(recipientLabels[value] || value); } },
            { data: 'konu', render: function (value) { return `<span title="${escapeHtml(value)}">${escapeHtml(value)}</span>`; } },
            { data: null, searchable: false, render: function (data) { return `<span class="text-success"><i class="ti ti-check me-1"></i>${Number(data.basarili_sayisi)}</span><span class="text-danger ms-3"><i class="ti ti-x me-1"></i>${Number(data.basarisiz_sayisi)}</span><span class="text-secondary ms-2">/ ${Number(data.toplam_alici)}</span>`; } },
            { data: 'durum', searchable: false, render: statusBadge },
            { data: 'id', searchable: false, orderable: false, className: 'text-end', render: function (id) { return `<button type="button" class="btn btn-sm btn-outline-primary mail-detail-button" data-id="${Number(id)}"><i class="ti ti-eye me-1"></i>Detay</button>`; } }
        ],
        language: {
            processing: 'Yükleniyor...', search: 'Ara:', lengthMenu: '_MENU_ kayıt göster', info: '_TOTAL_ kayıttan _START_ - _END_',
            infoEmpty: 'Kayıt yok', zeroRecords: 'Eşleşen gönderim bulunamadı', emptyTable: 'Henüz mail gönderilmedi',
            paginate: { first: 'İlk', last: 'Son', next: 'Sonraki', previous: 'Önceki' }
        }
    });

    function loadStats() {
        fetch(`${apiUrl}?action=stats`)
            .then(response => response.json())
            .then(data => {
                if (data.status !== 'success') return;
                document.getElementById('mailStatTotal').textContent = data.stats.toplam_gonderim;
                document.getElementById('mailStatSuccess').textContent = data.stats.basarili_mail;
                document.getElementById('mailStatFailed').textContent = data.stats.basarisiz_mail;
                document.getElementById('mailStatToday').textContent = data.stats.bugun_alici;
            });
    }

    document.querySelectorAll('input[name="alici_turu"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const type = document.querySelector('input[name="alici_turu"]:checked').value;
            document.getElementById('selectedUsersArea').classList.toggle('d-none', type !== 'secili');
            document.getElementById('externalEmailsArea').classList.toggle('d-none', type !== 'harici');
        });
    });

    composeForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        const type = document.querySelector('input[name="alici_turu"]:checked').value;
        if (type === 'secili' && !$('#mailSystemUsers').val().length) {
            Swal.fire({ icon: 'warning', title: 'Alıcı seçin', text: 'En az bir sistem kullanıcısı seçmelisiniz.' });
            return;
        }
        if ($('#mailBody').summernote('isEmpty')) {
            Swal.fire({ icon: 'warning', title: 'Mesaj gerekli', text: 'Mail içeriğini yazın.' });
            return;
        }

        sendButton.disabled = true;
        sendButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Gönderiliyor...';
        try {
            const response = await fetch(apiUrl, { method: 'POST', body: new FormData(composeForm) });
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message || 'Gönderim başarısız.');
            Swal.fire({ icon: 'success', title: 'Gönderim tamamlandı', text: data.message });
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mailComposeModal')).hide();
            composeForm.reset();
            $('#mailSystemUsers').val(null).trigger('change');
            $('#mailBody').summernote('reset');
            document.getElementById('selectedUsersArea').classList.remove('d-none');
            document.getElementById('externalEmailsArea').classList.add('d-none');
            table.ajax.reload(null, false);
            loadStats();
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'Mail gönderilemedi', text: error.message || 'Bağlantı hatası oluştu.' });
        } finally {
            sendButton.disabled = false;
            sendButton.innerHTML = '<i class="ti ti-send me-2"></i>Maili Gönder';
        }
    });

    $('#mailHistoryTable').on('click', '.mail-detail-button', async function () {
        const id = Number(this.dataset.id);
        const body = document.getElementById('mailDetailRecipients');
        body.innerHTML = '<tr><td colspan="4" class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Yükleniyor...</td></tr>';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('mailDetailModal')).show();
        try {
            const response = await fetch(`${apiUrl}?action=detail&id=${id}`);
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message);
            document.getElementById('mailDetailSummary').textContent = `${data.send.konu} · ${data.send.toplam_alici} alıcı`;
            body.innerHTML = data.recipients.map(function (recipient) {
                return `<tr><td>${escapeHtml(recipient.alici_adi || '—')}</td><td>${escapeHtml(recipient.email)}</td><td>${statusBadge(recipient.durum)}</td><td>${formatDate(recipient.gonderilme_tarihi)}</td></tr>`;
            }).join('') || '<tr><td colspan="4" class="text-center text-secondary py-4">Alıcı kaydı yok.</td></tr>';
        } catch (error) {
            body.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${escapeHtml(error.message || 'Detay yüklenemedi.')}</td></tr>`;
        }
    });

    loadStats();
});
</script>
