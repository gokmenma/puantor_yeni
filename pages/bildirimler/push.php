<?php
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/UserModel.php';

$Auths->checkFirmReturn();

$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$firma_id     = (int) ($_SESSION['firm_id'] ?? 0);
$Person       = new Persons();
$User         = new UserModel();
$firm_persons = $Person->getPersonsByFirm($firma_id);
$firm_users   = $User->allByFirms($firma_id);
$csrf_token   = (string) ($_SESSION['csrf_token'] ?? '');
?>

<div class="page-header d-print-none mb-0">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Bildirimler</div>
                <h2 class="page-title">Push Bildirim Gönder</h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalPushGonder">
                    <i class="ti ti-plus me-2"></i> Yeni Bildirim Gönder
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="row g-3 mb-3" id="statsRow">
            <div class="col-md-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-primary text-white avatar">
                                    <i class="ti ti-users"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="h2 mb-0 fw-bold" id="statToplam">—</div>
                                <div class="text-secondary small">Toplam Personel</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-success text-white avatar">
                                    <i class="ti ti-bell-ringing"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="h2 mb-0 fw-bold" id="statAbone">—</div>
                                <div class="text-secondary small">Abone Personel</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="bg-danger text-white avatar">
                                    <i class="ti ti-bell-off"></i>
                                </span>
                            </div>
                            <div class="col">
                                <div class="h2 mb-0 fw-bold" id="statAboneDegil">—</div>
                                <div class="text-secondary small">Abone Olmayan</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" data-bs-toggle="tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <a href="#tab-sistem-bildirimleri" class="nav-link active" data-bs-toggle="tab" role="tab">
                                    <i class="ti ti-settings-automation me-2"></i>Sistem Bildirimleri
                                </a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a href="#tab-kullanici-bildirimleri" class="nav-link" data-bs-toggle="tab" role="tab">
                                    <i class="ti ti-user-share me-2"></i>Kullanıcı Bildirimleri
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body p-0">
                        <div class="tab-content">
                            <div class="tab-pane active show" id="tab-sistem-bildirimleri" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table card-table table-vcenter text-nowrap w-100" id="sistemBildirimTable">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <?php if ($is_superadmin): ?><th>Firma</th><?php endif; ?>
                                                <th>Gönderen</th>
                                                <th>Bildirim Hedefi</th>
                                                <th>Başlık</th>
                                                <th>İçerik</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="tab-kullanici-bildirimleri" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table card-table table-vcenter text-nowrap w-100" id="gonderilenTable">
                                        <thead>
                                            <tr>
                                                <th>Tarih</th>
                                                <?php if ($is_superadmin): ?><th>Firma</th><?php endif; ?>
                                                <th>Gönderen</th>
                                                <th>Gönderim Hedefi</th>
                                                <th>Başlık</th>
                                                <th>İçerik</th>
                                                <th>Açılacak Sayfa</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal modal-blur fade" id="modalPushGonder" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-send text-primary me-2"></i>Yeni Bildirim Gönder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="pushForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-body">
                    <!-- Bilgi Alert Box -->
                    <div class="alert alert-info d-flex align-items-start mb-3" role="alert">
                        <i class="ti ti-info-circle fs-2 me-2 mt-1 text-info"></i>
                        <div>
                            <h4 class="alert-title fw-bold mb-1">Bilgilendirme</h4>
                            <ul class="text-secondary small mb-0 ps-3">
                                <li class="mb-1">PWA'da bildirim izni veren personeller abone olur.</li>
                                <li class="mb-1">Personel birden fazla cihazda abone olabilir.</li>
                                <li class="mb-0">Bildirimler tüm abone cihazlarına iletilir.</li>
                            </ul>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Alıcı Türü</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="hedef_turu" value="personel" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-id-badge-2 me-1"></i> Personeller
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="hedef_turu" value="kullanici" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-user-shield me-1"></i> Sistem Kullanıcıları
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gönderim Hedefi</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="hedef" id="hedefBelirli" value="belirli" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-user me-1"></i> <span id="belirliHedefEtiketi">Belirli Personel(ler)</span>
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="hedef" id="hedefHepsi" value="hepsi" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-users me-1"></i> <span id="tumHedefEtiketi">Tüm Personellere (…)</span>
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="mb-3" id="personelSecWrapper">
                        <label class="form-label">Personel Seçin</label>
                        <select name="personel_ids[]" id="personelSec" class="form-select select2" multiple style="width:100%;">
                            <?php foreach ($firm_persons as $p): ?>
                                <option value="<?= $p->id ?>">
                                    <?= htmlspecialchars($p->full_name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 d-none" id="kullaniciSecWrapper">
                        <label class="form-label">Sistem Kullanıcısı Seçin</label>
                        <select name="kullanici_ids[]" id="kullaniciSec" class="form-select select2" multiple style="width:100%;">
                            <?php foreach ($firm_users as $firm_user): ?>
                                <option value="<?= (int) $firm_user->id ?>">
                                    <?= htmlspecialchars($firm_user->full_name . ' — ' . $firm_user->email, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label required">Bildirim Başlığı</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-heading"></i>
                                </span>
                                <input type="text" name="baslik" id="pushBaslik" class="form-control" placeholder="Bildirim başlığı..." required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tıklandığında Açılacak Sayfa</label>
                            <div class="input-icon">
                                <span class="input-icon-addon">
                                    <i class="ti ti-link"></i>
                                </span>
                                <select name="url" id="pushUrl" class="form-select" style="width:100%;">
                                    <option value="">Tıklandığında Açılacak Sayfa</option>
                                    <option value="./modules/dashboard/">Ana Sayfa</option>
                                    <option value="./modules/leave/">İzin Talepleri</option>
                                    <option value="./modules/advance/">Avans Talepleri</option>
                                    <option value="./modules/attendance/">Puantaj</option>
                                    <option value="./modules/profile/">Profil</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label required">Bildirim İçeriği</label>
                        <div class="input-icon">
                            <span class="input-icon-addon align-self-start pt-2">
                                <i class="ti ti-message"></i>
                            </span>
                            <textarea name="icerik" id="pushIcerik" class="form-control" rows="4" placeholder="Bildirim içeriğini yazın..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" class="btn btn-primary ms-auto" id="btnGonder">
                        <i class="ti ti-send me-1"></i> Gönder
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modalBildirimDetay" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <div class="text-secondary small mb-1">Bildirim Detayı</div>
                    <h5 class="modal-title" id="detayBaslik">—</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-secondary small">Tarih</div>
                        <div class="fw-medium" id="detayTarih">—</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-secondary small">Firma</div>
                        <div class="fw-medium" id="detayFirma">—</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-secondary small">Gönderen</div>
                        <div class="fw-medium" id="detayGonderen">—</div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="text-secondary small">Hedef</div>
                        <div class="fw-medium" id="detayHedef">—</div>
                    </div>
                </div>
                <div class="form-label">Bildirim İçeriği</div>
                <div class="bg-light rounded border p-3 text-body"
                     id="detayIcerik"
                     style="white-space:pre-wrap;overflow-wrap:anywhere;min-height:90px;"></div>
                <div class="d-flex align-items-center gap-2 mt-4 mb-2">
                    <div class="form-label mb-0">İletilen Alıcılar</div>
                    <span class="badge bg-primary-lt" id="detayAliciSayisi">0 alıcı</span>
                </div>
                <div class="bg-light rounded border p-3 d-flex flex-wrap gap-2"
                     id="detayAlicilar"
                     style="max-height:220px;overflow-y:auto;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const notificationRows = new Map();

    // Select2 elements inside modal
    $('#personelSec').select2({
        dropdownParent: $('#modalPushGonder'),
        placeholder: 'Personel Seç',
        allowClear: true
    });
    $('#kullaniciSec').select2({
        dropdownParent: $('#modalPushGonder'),
        placeholder: 'Sistem Kullanıcısı Seç',
        allowClear: true
    });
    $('#pushUrl').select2({
        dropdownParent: $('#modalPushGonder'),
        placeholder: 'Tıklandığında Açılacak Sayfa',
        allowClear: true
    });

    // DataTable initialization using factory with customized layout for clean pagination
    const dt = window.createDataTable('#gonderilenTable', {
        data: [],
        columns: [
            { data: 'created_at', render: fmtDateTime },
            <?php if ($is_superadmin): ?>
            { data: 'firma_adi', render: (d) => escapeHtml(d || '—') },
            <?php endif; ?>
            { data: 'gonderen_adi', render: (d) => escapeHtml(d || '—') },
            { 
                data: 'hedef', 
                render: (d, t, row) => {
                    if (d === 'hepsi') {
                        const label = row.hedef_turu === 'kullanici' ? 'Tüm Sistem Kullanıcıları' : 'Tüm Personeller';
                        return `<span class="badge bg-success-lt"><i class="ti ti-users me-1"></i>${label}</span>`;
                    }
                    return `<span class="badge bg-primary-lt" title="${escapeHtml(row.hedef_aciklama)}"><i class="ti ti-user me-1"></i>${escapeHtml(row.hedef_aciklama)}</span>`;
                }
            },
            { data: 'baslik', render: $.fn.dataTable.render.text() },
            {
                data: 'icerik',
                render: (d, type, row) => renderNotificationContent(d, type, row, 'kullanici')
            },
            { 
                data: 'url',
                render: (d) => {
                    if (!d) return '—';
                    let pageName = d;
                    if (d === './modules/dashboard/') pageName = 'Ana Sayfa';
                    else if (d === './modules/leave/') pageName = 'İzin Talepleri';
                    else if (d === './modules/advance/') pageName = 'Avans Talepleri';
                    else if (d === './modules/attendance/') pageName = 'Puantaj';
                    else if (d === './modules/profile/') pageName = 'Profil';
                    return `<span class="badge bg-secondary-lt">${pageName}</span>`;
                }
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        skipSearch: ['İşlem']
    });

    const systemDt = window.createDataTable('#sistemBildirimTable', {
        data: [],
        columns: [
            { data: 'created_at', render: fmtDateTime },
            <?php if ($is_superadmin): ?>
            { data: 'firma_adi', render: (d) => escapeHtml(d || 'Birden fazla firma') },
            <?php endif; ?>
            {
                data: 'gonderen_adi',
                render: () => '<span class="badge bg-azure-lt"><i class="ti ti-settings-automation me-1"></i>Sistem</span>'
            },
            {
                data: 'hedef_aciklama',
                render: (d) => `<span class="badge bg-secondary-lt">${escapeHtml(d)}</span>`
            },
            { data: 'baslik', render: $.fn.dataTable.render.text() },
            {
                data: 'icerik',
                render: (d, type, row) => renderNotificationContent(d, type, row, 'sistem')
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        skipSearch: ['İşlem']
    });

    dt.on('draw', initializeTooltips);
    systemDt.on('draw', initializeTooltips);

    function escapeHtml(value) {
        return $('<div>').text(value || '').html();
    }

    function plainText(value) {
        return $('<div>').html(value || '').text();
    }

    function renderNotificationContent(value, type, row, source) {
        const plain = plainText(value);
        if (type !== 'display') return plain;

        const preview = plain.length > 70 ? `${plain.substring(0, 70)}...` : plain;
        const key = `${source}:${row.id}`;
        return `<button type="button"
                        class="btn btn-link link-body p-0 text-start text-decoration-none js-bildirim-detay"
                        data-notification-key="${escapeHtml(key)}"
                        data-bs-toggle="tooltip"
                        data-bs-placement="top"
                        data-bs-title="${escapeHtml(plain)}"
                        title="${escapeHtml(plain)}">${escapeHtml(preview)}</button>`;
    }

    function initializeTooltips() {
        document.querySelectorAll('.js-bildirim-detay[data-bs-toggle="tooltip"]').forEach(element => {
            bootstrap.Tooltip.getOrCreateInstance(element);
        });
    }

    function fmtDateTime(d) {
        if (!d) return '—';
        const p = (d + '').split(/[-T ]/);
        if (p.length >= 3) {
            const time = p[3] ? p[3].substring(0, 5) : '';
            return `${p[2]}.${p[1]}.${p[0]} ${time}`;
        }
        return d;
    }

    function loadStats() {
        fetch('/api/bildirimler/push.php?action=stats')
            .then(r => r.json())
            .then(d => {
                if (d.status !== 'success') return;
                document.getElementById('statToplam').textContent      = d.toplam;
                document.getElementById('statAbone').textContent       = d.abone;
                document.getElementById('statAboneDegil').textContent  = d.abone_degil;
                document.getElementById('tumHedefEtiketi').dataset.personelCount = d.hedef_toplam_personel;
                document.getElementById('tumHedefEtiketi').dataset.kullaniciCount = d.toplam_kullanici;
                updateRecipientUi();
            });
    }

    function loadSentList() {
        fetch('/api/bildirimler/push.php?action=list')
            .then(r => r.json())
            .then(d => {
                if (d.status !== 'success') return;
                d.list.forEach(row => notificationRows.set(`kullanici:${row.id}`, row));
                dt.clear().rows.add(d.list).draw();
                initializeTooltips();
            });
    }

    function loadSystemList() {
        fetch('/api/bildirimler/push.php?action=system-list')
            .then(r => r.json())
            .then(d => {
                if (d.status !== 'success') return;
                d.list.forEach(row => notificationRows.set(`sistem:${row.id}`, row));
                systemDt.clear().rows.add(d.list).draw();
                initializeTooltips();
            });
    }

    // Initial loads
    loadStats();
    loadSentList();
    loadSystemList();

    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', () => {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
            initializeTooltips();
        });
    });

    document.addEventListener('click', event => {
        const trigger = event.target.closest('.js-bildirim-detay');
        if (!trigger) return;

        bootstrap.Tooltip.getInstance(trigger)?.hide();
        const row = notificationRows.get(trigger.dataset.notificationKey);
        if (!row) return;

        document.getElementById('detayBaslik').textContent = plainText(row.baslik) || '—';
        document.getElementById('detayTarih').textContent = fmtDateTime(row.created_at);
        document.getElementById('detayFirma').textContent = row.firma_adi || '—';
        document.getElementById('detayGonderen').textContent = row.gonderen_adi || 'Sistem';
        document.getElementById('detayHedef').textContent = row.hedef_aciklama || '—';
        document.getElementById('detayIcerik').textContent = plainText(row.icerik) || '—';
        const recipients = Array.isArray(row.alici_listesi) ? row.alici_listesi : [];
        const recipientContainer = document.getElementById('detayAlicilar');
        recipientContainer.replaceChildren();
        document.getElementById('detayAliciSayisi').textContent = `${row.alici_sayisi ?? recipients.length} alıcı`;
        if (recipients.length === 0) {
            const empty = document.createElement('span');
            empty.className = 'text-secondary';
            empty.textContent = 'Alıcı bilgisi bulunamadı.';
            recipientContainer.appendChild(empty);
        } else {
            recipients.forEach(name => {
                const badge = document.createElement('span');
                badge.className = 'badge bg-white text-body border fw-normal';
                badge.textContent = name;
                recipientContainer.appendChild(badge);
            });
        }
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalBildirimDetay')).show();
    });

    document.querySelectorAll('input[name="hedef"]').forEach(r => {
        r.addEventListener('change', () => {
            updateRecipientUi();
        });
    });

    document.querySelectorAll('input[name="hedef_turu"]').forEach(r => {
        r.addEventListener('change', updateRecipientUi);
    });

    function updateRecipientUi() {
        const recipientType = document.querySelector('input[name="hedef_turu"]:checked').value;
        const target = document.querySelector('input[name="hedef"]:checked').value;
        const specific = target === 'belirli';
        const allLabel = document.getElementById('tumHedefEtiketi');
        const count = recipientType === 'kullanici'
            ? (allLabel.dataset.kullaniciCount || '…')
            : (allLabel.dataset.personelCount || '…');

        document.getElementById('belirliHedefEtiketi').textContent =
            recipientType === 'kullanici' ? 'Belirli Sistem Kullanıcıları' : 'Belirli Personel(ler)';
        allLabel.textContent =
            recipientType === 'kullanici' ? `Tüm Sistem Kullanıcılarına (${count})` : `Tüm Personellere (${count})`;
        document.getElementById('personelSecWrapper').classList.toggle('d-none', !specific || recipientType !== 'personel');
        document.getElementById('kullaniciSecWrapper').classList.toggle('d-none', !specific || recipientType !== 'kullanici');
    }

    document.getElementById('pushForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const recipientType = document.querySelector('input[name="hedef_turu"]:checked').value;
        const target = document.querySelector('input[name="hedef"]:checked').value;
        if (target === 'belirli') {
            const selected = recipientType === 'kullanici' ? $('#kullaniciSec').val() : $('#personelSec').val();
            if (!selected || selected.length === 0) {
                Swal.fire({ icon: 'warning', title: 'Alıcı seçin', text: 'En az bir alıcı seçmelisiniz.' });
                return;
            }
        }
        const btn = document.getElementById('btnGonder');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Gönderiliyor…';

        const fd = new FormData(e.target);
        fd.append('action', 'gonder');

        try {
            const res = await fetch('/api/bildirimler/push.php', { method: 'POST', body: fd });
            const d = await res.json();
            if (d.status === 'success') {
                Swal.fire({ icon: 'success', title: 'Gönderildi', text: d.message, timer: 3000, showConfirmButton: false });
                
                // Reset form and select2
                e.target.reset();
                $('#personelSec').val(null).trigger('change');
                $('#kullaniciSec').val(null).trigger('change');
                $('#pushUrl').val(null).trigger('change');
                updateRecipientUi();

                // Close Modal
                const modalEl = document.getElementById('modalPushGonder');
                const modal = bootstrap.Modal.getInstance(modalEl);
                if (modal) {
                    modal.hide();
                } else {
                    $(modalEl).modal('hide');
                }

                // Reload data
                loadStats();
                loadSentList();
            } else {
                Swal.fire({ icon: 'error', title: 'Hata', text: d.message });
            }
        } catch (err) {
            Swal.fire({ icon: 'error', title: 'Hata', text: 'Bağlantı hatası.' });
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="ti ti-send me-1"></i> Gönder';
    });
});
</script>
