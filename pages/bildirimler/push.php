<?php
require_once ROOT . '/Model/Persons.php';

$Auths->checkFirmReturn();

$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
if (!$is_superadmin) {
    echo '<div class="container-xl py-5"><div class="alert alert-danger"><i class="ti ti-lock me-2"></i>Bu sayfaya erişim yetkiniz yok.</div></div>';
    return;
}

$firma_id     = (int) ($_SESSION['firm_id'] ?? 0);
$Person       = new Persons();
$firm_persons = $Person->getPersonsByFirm($firma_id);
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
                        <h3 class="card-title">Gönderilen Bildirimler</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap w-100" id="gonderilenTable">
                            <thead>
                                <tr>
                                    <th>Tarih</th>
                                    <th>Gönderen</th>
                                    <th>Gönderim Hedefi</th>
                                    <th>Başlık</th>
                                    <th>İçerik</th>
                                    <th>Açılacak Sayfa</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
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
                        <label class="form-label">Gönderim Hedefi</label>
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="hedef" id="hedefBelirli" value="belirli" class="form-selectgroup-input" checked>
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-user me-1"></i> Belirli Personel(ler)
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="hedef" id="hedefHepsi" value="hepsi" class="form-selectgroup-input">
                                <span class="form-selectgroup-label">
                                    <i class="ti ti-users me-1"></i> Tüm Personellere (<span id="aboneSayisi">…</span>)
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

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Select2 elements inside modal
    $('#personelSec').select2({
        dropdownParent: $('#modalPushGonder'),
        placeholder: 'Personel Seç',
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
            { data: 'gonderen_adi' },
            { 
                data: 'hedef', 
                render: (d, t, row) => {
                    if (d === 'hepsi') {
                        return '<span class="badge bg-success-lt"><i class="ti ti-users me-1"></i> Tüm Personeller</span>';
                    }
                    return `<span class="badge bg-primary-lt" title="${row.hedef_aciklama}"><i class="ti ti-user me-1"></i> ${row.hedef_aciklama}</span>`;
                }
            },
            { data: 'baslik', render: $.fn.dataTable.render.text() },
            { 
                data: 'icerik', 
                render: (d) => {
                    const text = $.fn.dataTable.render.text().display(d);
                    if (text.length > 50) {
                        return `<span title="${text}">${text.substr(0, 50)}...</span>`;
                    }
                    return text;
                }
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
                document.getElementById('aboneSayisi').textContent     = d.abone;
            });
    }

    function loadSentList() {
        fetch('/api/bildirimler/push.php?action=list')
            .then(r => r.json())
            .then(d => {
                if (d.status !== 'success') return;
                dt.clear().rows.add(d.list).draw();
            });
    }

    // Initial loads
    loadStats();
    loadSentList();

    document.querySelectorAll('input[name="hedef"]').forEach(r => {
        r.addEventListener('change', () => {
            document.getElementById('personelSecWrapper').style.display =
                r.value === 'belirli' ? '' : 'none';
        });
    });

    document.getElementById('pushForm').addEventListener('submit', async (e) => {
        e.preventDefault();
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
                $('#pushUrl').val(null).trigger('change');
                document.getElementById('personelSecWrapper').style.display = '';

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
