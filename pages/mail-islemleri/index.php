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
$smtpHost = (string) ($settingsModel->getSystemSetting('smtp_host') ?? 'mail.puantor.com.tr');
$smtpPort = (int) ($settingsModel->getSystemSetting('smtp_port') ?? 465);
$infoEmail = (string) ($settingsModel->getSystemSetting('smtp_info_username') ?? 'bilgi@puantor.com.tr');
$supportEmail = (string) ($settingsModel->getSystemSetting('smtp_support_username') ?? 'destek@puantor.com.tr');
$serverReady = $smtpHost !== '' && $smtpPort > 0;
$infoReady = $serverReady && filter_var($infoEmail, FILTER_VALIDATE_EMAIL) !== false;
$supportReady = $serverReady && filter_var($supportEmail, FILTER_VALIDATE_EMAIL) !== false;
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
                <div class="flex-fill">Mail sunucusu veya gönderen hesapları henüz kaydedilmemiş. SMTP ekranında Değişiklikleri Kaydet butonuna basın.</div>
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

        <div class="card mb-3">
            <div class="card-body py-2">
                <ul class="nav nav-pills" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="sent-mails-tab" data-bs-toggle="tab" data-bs-target="#sent-mails-pane" type="button" role="tab">
                            <i class="ti ti-send me-2"></i>Gönderilenler
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="inbox-mails-tab" data-bs-toggle="tab" data-bs-target="#inbox-mails-pane" type="button" role="tab">
                            <i class="ti ti-inbox me-2"></i>Gelen Kutusu
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="sent-mails-pane" role="tabpanel" aria-labelledby="sent-mails-tab">
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

            <div class="tab-pane fade" id="inbox-mails-pane" role="tabpanel" aria-labelledby="inbox-mails-tab">
                <div class="card">
                    <div class="card-header d-flex flex-wrap gap-3 align-items-center">
                        <div class="me-auto">
                            <h3 class="card-title">Gelen Kutusu</h3>
                            <p class="card-subtitle" id="inboxAccountLabel">IMAP üzerinden gelen mailler</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <select class="form-select" id="inboxAccount" aria-label="Gelen kutusu hesabı">
                                <option value="info">Bilgilendirme — <?php echo htmlspecialchars($infoEmail, ENT_QUOTES, 'UTF-8'); ?></option>
                                <option value="support">Destek — <?php echo htmlspecialchars($supportEmail, ENT_QUOTES, 'UTF-8'); ?></option>
                            </select>
                            <div class="input-icon">
                                <span class="input-icon-addon"><i class="ti ti-search"></i></span>
                                <input type="search" class="form-control" id="inboxSearch" autocomplete="off" data-lpignore="true" placeholder="Gelen kutusunda ara">
                            </div>
                            <button type="button" class="btn btn-outline-primary" id="inboxRefresh"><i class="ti ti-refresh"></i></button>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter table-hover mb-0" id="inboxTable">
                            <thead>
                                <tr>
                                    <th style="width:42px"></th>
                                    <th>Gönderen</th>
                                    <th>Konu</th>
                                    <th>Tarih</th>
                                    <th class="text-end">Boyut</th>
                                </tr>
                            </thead>
                            <tbody id="inboxRows">
                                <tr><td colspan="5" class="text-center text-secondary py-5">Gelen Kutusu sekmesini açtığınızda mailler yüklenecek.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer d-flex flex-wrap align-items-center gap-2">
                        <div class="text-secondary" id="inboxPaginationInfo">—</div>
                        <div class="ms-auto btn-list">
                            <button type="button" class="btn btn-sm" id="inboxPrevious" disabled><i class="ti ti-chevron-left me-1"></i>Önceki</button>
                            <button type="button" class="btn btn-sm" id="inboxNext" disabled>Sonraki<i class="ti ti-chevron-right ms-1"></i></button>
                        </div>
                    </div>
                </div>
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

#inboxTable .inbox-message-row {
    cursor: pointer;
}

#inboxTable .inbox-message-row.is-unread td {
    background: rgba(32, 107, 196, .06);
    font-weight: 600;
}

#inboxMessageFrame {
    width: 100%;
    min-height: 46vh;
    border: 0;
    background: #fff;
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
                        <textarea class="form-control" name="harici_emailler" rows="3" autocomplete="off" data-lpignore="true" data-1p-ignore="true" placeholder="ornek@firma.com, ikinci@firma.com"></textarea>
                        <div class="form-hint">Adresleri virgül, noktalı virgül, boşluk veya yeni satırla ayırabilirsiniz.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Konu</label>
                        <input type="text" class="form-control" name="konu" maxlength="255" autocomplete="off" data-lpignore="true" data-1p-ignore="true" required placeholder="Mail konusu">
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

<div class="modal modal-blur fade" id="inboxMessageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title text-truncate" id="inboxMessageSubject">Mail detayı</h5>
                    <div class="text-secondary small text-truncate" id="inboxMessageMeta"></div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body p-0">
                <div id="inboxMessageLoading" class="text-center py-5">
                    <span class="spinner-border spinner-border-sm me-2"></span>Mail yükleniyor...
                </div>
                <div id="inboxMessageContent" class="d-none">
                    <div class="border-bottom px-4 py-3">
                        <div><span class="text-secondary">Kimden:</span> <span id="inboxMessageFrom"></span></div>
                        <div><span class="text-secondary">Kime:</span> <span id="inboxMessageTo"></span></div>
                        <div><span class="text-secondary">Tarih:</span> <span id="inboxMessageDate"></span></div>
                    </div>
                    <iframe id="inboxMessageFrame" sandbox="allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" title="Mail içeriği"></iframe>
                    <div class="border-top px-4 py-3 d-none" id="inboxAttachments"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary me-auto" id="inboxToggleSeen"><i class="ti ti-mail me-1"></i>Okunmadı Yap</button>
                <button type="button" class="btn btn-outline-primary" id="inboxReply"><i class="ti ti-arrow-back-up me-1"></i>Yanıtla</button>
                <button type="button" class="btn" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const apiUrl = '/api/mail-islemleri/index.php';
    const composeForm = document.getElementById('mailComposeForm');
    const sendButton = document.getElementById('mailSendButton');
    const csrfToken = composeForm.querySelector('input[name="csrf_token"]').value;
    let inboxPage = 1;
    let inboxPageCount = 1;
    let inboxLoaded = false;
    let inboxSearchTimer = null;
    let currentInboxMessage = null;

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

    function formatBytes(bytes) {
        const value = Number(bytes) || 0;
        if (value < 1024) return `${value} B`;
        if (value < 1048576) return `${(value / 1024).toFixed(1)} KB`;
        return `${(value / 1048576).toFixed(1)} MB`;
    }

    async function loadInbox(resetPage) {
        if (resetPage) inboxPage = 1;
        const account = document.getElementById('inboxAccount').value;
        const search = document.getElementById('inboxSearch').value.trim();
        const rows = document.getElementById('inboxRows');
        rows.innerHTML = '<tr><td colspan="5" class="text-center py-5"><span class="spinner-border spinner-border-sm me-2"></span>Gelen kutusu yükleniyor...</td></tr>';
        document.getElementById('inboxPrevious').disabled = true;
        document.getElementById('inboxNext').disabled = true;

        try {
            const query = new URLSearchParams({ action: 'inbox', account: account, page: inboxPage, per_page: 25, search: search });
            const response = await fetch(`${apiUrl}?${query.toString()}`);
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message || 'Gelen kutusu yüklenemedi.');

            inboxPage = Number(data.page) || 1;
            inboxPageCount = Number(data.page_count) || 1;
            document.getElementById('inboxAccountLabel').textContent = `${data.account_email} · ${Number(data.total)} mail`;
            document.getElementById('inboxPaginationInfo').textContent = `${Number(data.total)} mail · Sayfa ${inboxPage} / ${inboxPageCount}`;
            document.getElementById('inboxPrevious').disabled = inboxPage <= 1;
            document.getElementById('inboxNext').disabled = inboxPage >= inboxPageCount;

            rows.innerHTML = data.rows.map(function (message) {
                const sender = message.from_name || message.from_email || 'Bilinmeyen gönderen';
                const answered = message.answered ? '<i class="ti ti-arrow-back-up text-primary ms-1" title="Yanıtlandı"></i>' : '';
                return `<tr class="inbox-message-row ${message.seen ? '' : 'is-unread'}" data-uid="${Number(message.uid)}" data-seen="${message.seen ? '1' : '0'}">
                    <td>${message.seen ? '<i class="ti ti-mail-opened text-secondary"></i>' : '<i class="ti ti-mail text-primary"></i>'}</td>
                    <td><div>${escapeHtml(sender)}${answered}</div><div class="text-secondary small">${escapeHtml(message.from_email || '')}</div></td>
                    <td>${escapeHtml(message.subject || '(Konu yok)')}</td>
                    <td class="text-nowrap">${formatDate(message.date)}</td>
                    <td class="text-end text-nowrap">${formatBytes(message.size)}</td>
                </tr>`;
            }).join('') || '<tr><td colspan="5" class="text-center text-secondary py-5">Bu gelen kutusunda mail bulunamadı.</td></tr>';
            inboxLoaded = true;
        } catch (error) {
            rows.innerHTML = `<tr><td colspan="5" class="text-center py-5"><div class="text-danger mb-2"><i class="ti ti-alert-circle me-1"></i>${escapeHtml(error.message || 'Gelen kutusu yüklenemedi.')}</div><a class="btn btn-sm btn-outline-primary" href="index.php?p=settings/manage&view=system&tab=smtp">IMAP Ayarları</a></td></tr>`;
            document.getElementById('inboxPaginationInfo').textContent = 'Bağlantı kurulamadı';
        }
    }

    async function openInboxMessage(uid, wasSeen) {
        const account = document.getElementById('inboxAccount').value;
        const modalElement = document.getElementById('inboxMessageModal');
        document.getElementById('inboxMessageLoading').innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mail yükleniyor...';
        document.getElementById('inboxMessageLoading').classList.remove('d-none');
        document.getElementById('inboxMessageContent').classList.add('d-none');
        document.getElementById('inboxMessageSubject').textContent = 'Mail yükleniyor...';
        bootstrap.Modal.getOrCreateInstance(modalElement).show();

        try {
            const query = new URLSearchParams({ action: 'message', account: account, uid: uid });
            const response = await fetch(`${apiUrl}?${query.toString()}`);
            const data = await response.json();
            if (data.status !== 'success') throw new Error(data.message || 'Mail yüklenemedi.');

            const message = data.message_data;
            currentInboxMessage = { account: account, uid: Number(message.uid), from_email: message.from_email, subject: message.subject, seen: wasSeen };
            document.getElementById('inboxMessageSubject').textContent = message.subject || '(Konu yok)';
            document.getElementById('inboxMessageMeta').textContent = `${message.from_name || message.from_email} · ${formatDate(message.date)}`;
            document.getElementById('inboxMessageFrom').textContent = message.from_name && message.from_name !== message.from_email ? `${message.from_name} <${message.from_email}>` : message.from_email;
            document.getElementById('inboxMessageTo').textContent = (message.to || []).map(function (item) { return item.name && item.name !== item.email ? `${item.name} <${item.email}>` : item.email; }).join(', ') || '—';
            document.getElementById('inboxMessageDate').textContent = formatDate(message.date);
            document.getElementById('inboxMessageFrame').srcdoc = message.body || '<p>İçerik yok.</p>';

            const attachments = document.getElementById('inboxAttachments');
            if (message.attachments && message.attachments.length) {
                attachments.classList.remove('d-none');
                attachments.innerHTML = `<div class="fw-semibold mb-2"><i class="ti ti-paperclip me-1"></i>Ekler</div><div class="btn-list">${message.attachments.map(function (attachment) {
                    const query = new URLSearchParams({ account: account, uid: message.uid, part: attachment.part });
                    return `<a class="btn btn-sm btn-outline-secondary" href="/api/mail-islemleri/attachment.php?${query.toString()}"><i class="ti ti-download me-1"></i>${escapeHtml(attachment.filename)} <span class="text-secondary ms-1">${formatBytes(attachment.size)}</span></a>`;
                }).join('')}</div>`;
            } else {
                attachments.classList.add('d-none');
                attachments.innerHTML = '';
            }

            document.getElementById('inboxMessageLoading').classList.add('d-none');
            document.getElementById('inboxMessageContent').classList.remove('d-none');
            if (wasSeen) {
                document.getElementById('inboxToggleSeen').innerHTML = '<i class="ti ti-mail me-1"></i>Okunmadı Yap';
            } else {
                try {
                    await updateInboxSeen(true);
                } catch (error) {
                    document.getElementById('inboxToggleSeen').innerHTML = '<i class="ti ti-mail-opened me-1"></i>Okundu Yap';
                }
            }
        } catch (error) {
            document.getElementById('inboxMessageSubject').textContent = 'Mail yüklenemedi';
            document.getElementById('inboxMessageLoading').innerHTML = `<div class="text-danger"><i class="ti ti-alert-circle me-1"></i>${escapeHtml(error.message || 'Mail yüklenemedi.')}</div>`;
        }
    }

    async function updateInboxSeen(seen) {
        if (!currentInboxMessage) return;
        const formData = new FormData();
        formData.append('action', 'seen');
        formData.append('csrf_token', csrfToken);
        formData.append('account', currentInboxMessage.account);
        formData.append('uid', currentInboxMessage.uid);
        formData.append('seen', seen ? '1' : '0');
        const response = await fetch(apiUrl, { method: 'POST', body: formData });
        const data = await response.json();
        if (data.status !== 'success') throw new Error(data.message || 'Mail durumu güncellenemedi.');
        currentInboxMessage.seen = seen;
        document.getElementById('inboxToggleSeen').innerHTML = seen
            ? '<i class="ti ti-mail me-1"></i>Okunmadı Yap'
            : '<i class="ti ti-mail-opened me-1"></i>Okundu Yap';
        const row = document.querySelector(`#inboxRows tr[data-uid="${currentInboxMessage.uid}"]`);
        row?.classList.toggle('is-unread', !seen);
        if (row) {
            row.dataset.seen = seen ? '1' : '0';
            row.querySelector('td:first-child').innerHTML = seen
                ? '<i class="ti ti-mail-opened text-secondary"></i>'
                : '<i class="ti ti-mail text-primary"></i>';
        }
    }

    document.getElementById('inbox-mails-tab').addEventListener('shown.bs.tab', function () {
        if (!inboxLoaded) loadInbox(true);
    });

    document.getElementById('inboxAccount').addEventListener('change', function () {
        inboxLoaded = false;
        loadInbox(true);
    });

    document.getElementById('inboxRefresh').addEventListener('click', function () {
        loadInbox(false);
    });

    document.getElementById('inboxSearch').addEventListener('input', function () {
        clearTimeout(inboxSearchTimer);
        inboxSearchTimer = setTimeout(function () { loadInbox(true); }, 450);
    });

    document.getElementById('inboxPrevious').addEventListener('click', function () {
        if (inboxPage > 1) {
            inboxPage--;
            loadInbox(false);
        }
    });

    document.getElementById('inboxNext').addEventListener('click', function () {
        if (inboxPage < inboxPageCount) {
            inboxPage++;
            loadInbox(false);
        }
    });

    document.getElementById('inboxRows').addEventListener('click', function (event) {
        const row = event.target.closest('.inbox-message-row');
        if (row) openInboxMessage(Number(row.dataset.uid), row.dataset.seen === '1');
    });

    document.getElementById('inboxToggleSeen').addEventListener('click', async function () {
        if (!currentInboxMessage) return;
        try {
            await updateInboxSeen(!currentInboxMessage.seen);
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'İşlem başarısız', text: error.message });
        }
    });

    document.getElementById('inboxReply').addEventListener('click', function () {
        if (!currentInboxMessage || !currentInboxMessage.from_email) return;
        composeForm.reset();
        $('#mailSystemUsers').val(null).trigger('change');
        document.getElementById('mailSenderAccount').value = currentInboxMessage.account;
        const externalRadio = composeForm.querySelector('input[name="alici_turu"][value="harici"]');
        externalRadio.checked = true;
        externalRadio.dispatchEvent(new Event('change'));
        composeForm.querySelector('[name="harici_emailler"]').value = currentInboxMessage.from_email;
        const subject = /^(re|ynt):/i.test(currentInboxMessage.subject) ? currentInboxMessage.subject : `Ynt: ${currentInboxMessage.subject}`;
        composeForm.querySelector('[name="konu"]').value = subject;
        $('#mailBody').summernote('code', '<p><br></p>');
        const inboxModalElement = document.getElementById('inboxMessageModal');
        inboxModalElement.addEventListener('hidden.bs.modal', function () {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('mailComposeModal')).show();
        }, { once: true });
        bootstrap.Modal.getOrCreateInstance(inboxModalElement).hide();
    });

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
