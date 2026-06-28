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
            <div class="col-md-4">
                <div class="card">
                    <div class="card-status-start bg-info"></div>
                    <div class="card-body">
                        <h3 class="card-title">
                            <i class="ti ti-info-circle text-info me-2"></i> Bilgi
                        </h3>
                        <ul class="text-secondary small mb-0 ps-3">
                            <li class="mb-2">PWA'da bildirim izni veren personeller abone olur.</li>
                            <li class="mb-2">Personel birden fazla cihazda abone olabilir.</li>
                            <li class="mb-0">Bildirimler tüm abone cihazlarına iletilir.</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="ti ti-send text-primary me-2"></i> Bildirim Gönder
                        </h3>
                    </div>
                    <div class="card-body">
                        <form id="pushForm">
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
                                        <select name="url" id="pushUrl" class="form-select">
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

                            <div class="text-end">
                                <button type="submit" class="btn btn-primary px-4" id="btnGonder">
                                    <i class="ti ti-send me-1"></i> Gönder
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    $('#personelSec').select2({ placeholder: 'Personel Seç', allowClear: true });

    fetch('/api/bildirimler/push.php?action=stats')
        .then(r => r.json())
        .then(d => {
            if (d.status !== 'success') return;
            document.getElementById('statToplam').textContent      = d.toplam;
            document.getElementById('statAbone').textContent       = d.abone;
            document.getElementById('statAboneDegil').textContent  = d.abone_degil;
            document.getElementById('aboneSayisi').textContent     = d.abone;
        });

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
                e.target.reset();
                $('#personelSec').val(null).trigger('change');
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
