<?php
require_once ROOT . '/Model/DuyuruModel.php';
require_once ROOT . '/Model/UserModel.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/Model/AbonelerModel.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

$Auths->checkFirmReturn();

$kullanici_id  = $_SESSION['user']->id ?? 0;
$firma_id      = $_SESSION['firm_id'] ?? 0;
$is_superadmin = ($_SESSION['user']->superadmin ?? 0) == 1;
$is_main_user  = !$is_superadmin && (($_SESSION['user']->parent_id ?? 1) == 0);

$can_add = $is_superadmin || $Auths->hasPermission('duyuru_ekle');

$Duyuru    = new DuyuruModel();
$User      = new UserModel();
$Person    = new Persons();
$Abone     = new AbonelerModel();

$duyurular = $Duyuru->getDuyurular($kullanici_id, $firma_id, $is_superadmin, $is_main_user);
$subscribers = $is_superadmin ? $Abone->getSubscribers() : [];
$firm_users   = $User->allByFirms($firma_id);
$firm_persons = $Person->getPersonsByFirm($firma_id);
?>

<div class="page-header d-print-none mb-0">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Duyurular</h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <?php if (!$is_superadmin): ?>
                <button type="button" class="btn btn-outline-secondary" id="btnTumunuOku">
                    <i class="ti ti-checks me-1"></i> Tümünü Okundu İşaretle
                </button>
                <?php endif; ?>
                <?php if ($can_add): ?>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#duyuruModal">
                    <i class="ti ti-plus me-1"></i> Yeni Duyuru
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="ti ti-bell me-2 text-primary"></i>
                    Duyuru Listesi
                </h3>
            </div>
            <div class="table-responsive">
                <table id="duyuruTable" class="table card-table table-vcenter table-hover datatable">
                    <thead>
                        <tr>
                            <th>Başlık</th>
                            <th>Öncelik</th>
                            <?php if ($is_superadmin): ?>
                            <th>Hedef</th>
                            <?php endif; ?>
                            <th>Tarih</th>
                            <th>Bitiş</th>
                            <?php if (!$is_superadmin): ?>
                            <th>Durum</th>
                            <?php else: ?>
                            <th>Okuyan</th>
                            <?php endif; ?>
                            <th>Aktif</th>
                            <th class="text-end">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($duyurular as $d):
                            $encId    = Security::encrypt($d->id);
                            $okundu   = !$is_superadmin && !empty($d->okundu_at);
                            $oncelikMap = ['acil' => ['bg-red', 'Acil'], 'onemli' => ['bg-orange', 'Önemli']];
                            $oncelik  = $oncelikMap[$d->oncelik] ?? ['bg-blue-lt', 'Normal'];
                            $hedefMap = [
                                'aboneler' => ['bg-purple-lt', 'Tüm Aboneler'],
                                'firma_kullanicilari' => ['bg-teal-lt', 'Kullanıcılar'],
                                'firma_personelleri' => ['bg-cyan-lt', 'Personeller'],
                                'bazi_firmalar' => ['bg-indigo-lt', 'Bazı Firmalar'],
                                'bazi_kullanicilar' => ['bg-orange-lt', 'Bazı Kullanıcılar'],
                                'bazi_personeller' => ['bg-pink-lt', 'Bazı Personeller']
                            ];
                            $hedef    = $hedefMap[$d->hedef_tip] ?? ['bg-secondary-lt', 'Herkese'];

                            $targetIds = [];
                            if (in_array($d->hedef_tip, ['bazi_firmalar', 'bazi_kullanicilar', 'bazi_personeller'])) {
                                $targets = $Duyuru->getHedefler($d->id);
                                foreach ($targets as $t) {
                                    $targetIds[] = $t->hedef_id;
                                }
                            }
                            $targetIdsStr = implode(',', $targetIds);
                        ?>
                        <tr class="<?= (!$is_superadmin && !$okundu) ? 'fw-bold' : '' ?>">
                            <td>
                                <a href="#" class="text-body btn-detay" data-id="<?= $encId ?>">
                                    <?= htmlspecialchars($d->baslik) ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge <?= $oncelik[0] ?>">
                                    <?= $oncelik[1] ?>
                                </span>
                            </td>
                            <?php if ($is_superadmin): ?>
                            <td>
                                <span class="badge <?= $hedef[0] ?>"><?= $hedef[1] ?></span>
                            </td>
                            <?php endif; ?>
                            <td><?= date('d.m.Y', strtotime($d->created_at)) ?></td>
                            <td><?= $d->bitis_tarihi ? date('d.m.Y', strtotime($d->bitis_tarihi)) : '<span class="text-secondary">—</span>' ?></td>
                            <?php if (!$is_superadmin): ?>
                            <td>
                                <?php if ($okundu): ?>
                                <span class="badge bg-green-lt"><i class="ti ti-check me-1"></i>Okundu</span>
                                <?php else: ?>
                                <span class="badge bg-yellow-lt"><i class="ti ti-clock me-1"></i>Okunmadı</span>
                                <?php endif; ?>
                            </td>
                            <?php else: ?>
                            <td><?= (int)$d->okunma_sayisi ?> kişi</td>
                            <?php endif; ?>
                            <td>
                                <?php if ($d->is_active): ?>
                                <span class="badge bg-success-lt">Aktif</span>
                                <?php else: ?>
                                <span class="badge bg-secondary-lt">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-list justify-content-end flex-nowrap">
                                    <?php if (!$is_superadmin && !$okundu): ?>
                                    <button class="btn btn-sm btn-success-lt btn-okundu" data-id="<?= $encId ?>" title="Okundu İşaretle">
                                        <i class="ti ti-check"></i>
                                    </button>
                                    <?php endif; ?>
                                    <?php if ($can_add && ($is_superadmin || $d->olusturan_id == $kullanici_id)): ?>
                                    <button class="btn btn-sm btn-secondary btn-duzenle"
                                            data-id="<?= $encId ?>"
                                            data-baslik="<?= htmlspecialchars($d->baslik) ?>"
                                            data-icerik="<?= htmlspecialchars($d->icerik) ?>"
                                            data-oncelik="<?= $d->oncelik ?>"
                                            data-hedef="<?= $d->hedef_tip ?>"
                                            data-hedef-ids="<?= $targetIdsStr ?>"
                                            data-baslangic="<?= $d->baslangic_tarihi ?? '' ?>"
                                            data-bitis="<?= $d->bitis_tarihi ?? '' ?>"
                                            data-active="<?= $d->is_active ?>"
                                            title="Düzenle">
                                        <i class="ti ti-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger-lt btn-sil" data-id="<?= $encId ?>" title="Sil">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ═══ EKLE / DÜZENLE MODAL ═══ -->
<div class="modal modal-blur fade" id="duyuruModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duyuruModalBaslik">
                    <i class="ti ti-bell-plus me-2"></i> Yeni Duyuru
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <form id="duyuruForm">
                <input type="hidden" name="id" id="formId" value="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label required">Başlık</label>
                        <input type="text" name="baslik" id="formBaslik" class="form-control" required maxlength="255" placeholder="Duyuru başlığı...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">İçerik</label>
                        <textarea id="duyuruIcerik" style="display:none;"></textarea>
                        <input type="hidden" name="icerik" id="formIcerik">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Öncelik</label>
                            <select name="oncelik" id="formOncelik" class="form-select">
                                <option value="normal">Normal</option>
                                <option value="onemli">Önemli</option>
                                <option value="acil">Acil</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label required">Hedef Kitle</label>
                            <select name="hedef_tip" id="formHedef" class="form-select">
                                <?php if ($is_superadmin): ?>
                                <option value="aboneler">Tüm Aboneler</option>
                                <option value="bazi_firmalar">Bazı Firmalar</option>
                                <?php endif; ?>
                                <option value="herkese">Firma Geneli (Herkese)</option>
                                <option value="firma_kullanicilari">Sadece Kullanıcılar</option>
                                <option value="firma_personelleri">Sadece Personeller</option>
                                <option value="bazi_kullanicilar">Bazı Kullanıcılar</option>
                                <option value="bazi_personeller">Bazı Personeller</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3 target-wrap" id="wrapBaziFirmalar" style="display: none;">
                        <label class="form-label required">Firmalar Seçin</label>
                        <select name="hedef_firmalar[]" id="hedefFirmalar" class="form-select select2" multiple="multiple" style="width: 100%;">
                            <?php foreach ($subscribers as $sub): ?>
                                <option value="<?= $sub->id ?>"><?= htmlspecialchars($sub->full_name) ?> (<?= htmlspecialchars($sub->email) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 target-wrap" id="wrapBaziKullanicilar" style="display: none;">
                        <label class="form-label required">Kullanıcılar Seçin</label>
                        <select name="hedef_kullanicilar[]" id="hedefKullanicilar" class="form-select select2" multiple="multiple" style="width: 100%;">
                            <?php foreach ($firm_users as $fu): ?>
                                <option value="<?= $fu->id ?>"><?= htmlspecialchars($fu->full_name) ?> (<?= htmlspecialchars($fu->email) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3 target-wrap" id="wrapBaziPersoneller" style="display: none;">
                        <label class="form-label required">Personeller Seçin</label>
                        <select name="hedef_personeller[]" id="hedefPersoneller" class="form-select select2" multiple="multiple" style="width: 100%;">
                            <?php foreach ($firm_persons as $fp): ?>
                                <option value="<?= $fp->id ?>"><?= htmlspecialchars($fp->full_name) ?> (<?= htmlspecialchars($fp->job ?? 'Personel') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Başlangıç Tarihi</label>
                            <input type="date" name="baslangic_tarihi" id="formBaslangic" class="form-control">
                            <small class="text-secondary">Boş = hemen yayınlanır</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bitiş Tarihi</label>
                            <input type="date" name="bitis_tarihi" id="formBitis" class="form-control">
                            <small class="text-secondary">Boş = süresiz</small>
                        </div>
                    </div>
                    <div id="formAktifWrap" class="mb-2" style="display:none;">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="formAktif" value="1" checked>
                            <span class="form-check-label">Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary me-auto" data-bs-dismiss="modal">
                        <i class="ti ti-x me-1"></i> İptal
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnKaydet">
                        <i class="ti ti-device-floppy me-1"></i> Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══ DETAY MODAL ═══ -->
<div class="modal modal-blur fade" id="detayModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="detayBaslik"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body">
                <div id="detayIcerik" style="min-height:60px;"></div>
                <hr>
                <div class="row text-secondary small" id="detayMeta"></div>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <div id="detayOkunduBolum"></div>
                <div id="detayOkunduListesi" style="display:none;width:100%;">
                    <h6 class="text-secondary mb-2"><i class="ti ti-users me-1"></i>Okuyanlar</h6>
                    <div id="okuyanlarIcerik" style="max-height:200px;overflow-y:auto;"></div>
                </div>
                <button type="button" class="btn btn-secondary ms-auto" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
const IS_SUPERADMIN = <?= $is_superadmin ? 'true' : 'false' ?>;

$(document).ready(function () {

    // ── Summernote başlat ──
    $('#duyuruIcerik').summernote({
        height: 240,
        lang: 'tr-TR'
    });

    // ── Select2 ──
    $('#formOncelik, #formHedef').select2({
        dropdownParent: $('#duyuruModal'),
        minimumResultsForSearch: Infinity
    });

    $('#hedefFirmalar, #hedefKullanicilar, #hedefPersoneller').select2({
        dropdownParent: $('#duyuruModal'),
        placeholder: "Seçim yapınız...",
        allowClear: true
    });

    $('#formHedef').on('change', function () {
        const val = $(this).val();
        $('.target-wrap').hide();
        if (val === 'bazi_firmalar') {
            $('#wrapBaziFirmalar').show();
        } else if (val === 'bazi_kullanicilar') {
            $('#wrapBaziKullanicilar').show();
        } else if (val === 'bazi_personeller') {
            $('#wrapBaziPersoneller').show();
        }
    });

    // ── Flatpickr ──
    var fpBaslangic = flatpickr('#formBaslangic', { locale: 'tr', dateFormat: 'Y-m-d', allowInput: true });
    var fpBitis     = flatpickr('#formBitis',     { locale: 'tr', dateFormat: 'Y-m-d', allowInput: true });

    // ── Modal: show → form sıfırla / içerik set et ──
    $('#duyuruModal').on('show.bs.modal', function (e) {
        if (!$(e.relatedTarget).hasClass('btn-duzenle')) {
            // Yeni duyuru
            $('#duyuruModalBaslik').html('<i class="ti ti-bell-plus me-2"></i> Yeni Duyuru');
            $('#formId').val('');
            $('#formBaslik').val('');
            $('#duyuruIcerik').summernote('reset');
            $('#formOncelik').val('normal').trigger('change');
            $('#formHedef').val(IS_SUPERADMIN ? 'aboneler' : 'herkese').trigger('change');
            $('#hedefFirmalar, #hedefKullanicilar, #hedefPersoneller').val(null).trigger('change');
            fpBaslangic.clear();
            fpBitis.clear();
            $('#formAktifWrap').hide();
            $('#formAktif').prop('checked', true);
        }
    });

    // ── Düzenle butonu ──
    $(document).on('click', '.btn-duzenle', function () {
        const btn = $(this);
        $('#duyuruModalBaslik').html('<i class="ti ti-pencil me-2"></i> Duyuru Düzenle');
        $('#formId').val(btn.data('id'));
        $('#formBaslik').val(btn.data('baslik'));
        $('#duyuruIcerik').summernote('code', btn.data('icerik'));
        $('#formOncelik').val(btn.data('oncelik')).trigger('change');

        // Set target values before triggering change
        const hedefIds = String(btn.data('hedef-ids') || '').split(',').filter(Boolean);
        const hedefTip = btn.data('hedef');
        $('#hedefFirmalar, #hedefKullanicilar, #hedefPersoneller').val(null).trigger('change');

        if (hedefTip === 'bazi_firmalar') {
            $('#hedefFirmalar').val(hedefIds).trigger('change');
        } else if (hedefTip === 'bazi_kullanicilar') {
            $('#hedefKullanicilar').val(hedefIds).trigger('change');
        } else if (hedefTip === 'bazi_personeller') {
            $('#hedefPersoneller').val(hedefIds).trigger('change');
        }

        $('#formHedef').val(hedefTip).trigger('change');
        fpBaslangic.setDate(btn.data('baslangic') || null);
        fpBitis.setDate(btn.data('bitis') || null);
        $('#formAktifWrap').show();
        $('#formAktif').prop('checked', btn.data('active') == 1);
        new bootstrap.Modal(document.getElementById('duyuruModal')).show();
    });

    // ── Form gönder ──
    $('#duyuruForm').on('submit', function (e) {
        e.preventDefault();
        const icerik = $('#duyuruIcerik').summernote('code').trim();
        if (!icerik || $('#duyuruIcerik').summernote('isEmpty')) {
            Swal.fire('Hata', 'İçerik boş olamaz.', 'error');
            return;
        }
        $('#formIcerik').val(icerik);

        const isEdit = $('#formId').val() !== '';
        const fd = new FormData(this);
        fd.set('action', isEdit ? 'guncelle' : 'ekle');
        if (!fd.has('is_active') || fd.get('is_active') === '') fd.set('is_active', '0');

        const btn = $('#btnKaydet').prop('disabled', true);
        fetch('pages/duyurular/api.php', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    bootstrap.Modal.getInstance(document.getElementById('duyuruModal')).hide();
                    Swal.fire({
                        title: 'Başarılı',
                        text: isEdit ? 'Duyuru güncellendi.' : 'Duyuru eklendi.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Hata', res.message || 'Bir hata oluştu.', 'error');
                    btn.prop('disabled', false);
                }
            })
            .catch(() => { 
                Swal.fire('Hata', 'Sunucu hatası.', 'error');
                btn.prop('disabled', false);
            });
    });

    // ── Detay ──
    $(document).on('click', '.btn-detay', function (e) {
        e.preventDefault();
        const encId = $(this).data('id');
        fetch('pages/duyurular/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'detay', id: encId })
        })
        .then(r => r.json())
        .then(res => {
            if (!res.success) return;
            const d = res.data;
            $('#detayBaslik').html('<i class="ti ti-bell me-2"></i>' + escHtml(d.baslik));
            $('#detayIcerik').html(d.icerik);
            const oncelikLbl = { acil: 'Acil', onemli: 'Önemli', normal: 'Normal' };
            $('#detayMeta').html(
                '<div class="col-auto"><i class="ti ti-user me-1"></i>' + escHtml(d.olusturan_adi) + '</div>' +
                '<div class="col-auto"><i class="ti ti-clock me-1"></i>' + d.created_at_fmt + '</div>' +
                '<div class="col-auto"><span class="badge bg-secondary-lt">' + (oncelikLbl[d.oncelik] || d.oncelik) + '</span></div>'
            );
            const okunduDiv = $('#detayOkunduBolum');
            if (!IS_SUPERADMIN && !d.okundu) {
                okunduDiv.html('<button class="btn btn-success btn-sm btn-okundu-modal" data-id="' + encId + '"><i class="ti ti-check me-1"></i>Okundu İşaretle</button>');
            } else {
                okunduDiv.html('');
            }
            const listesiDiv = $('#detayOkunduListesi');
            if (IS_SUPERADMIN && res.okuyanlar !== undefined) {
                listesiDiv.show();
                const html = res.okuyanlar.length === 0
                    ? '<p class="text-secondary small">Henüz kimse okumadı.</p>'
                    : res.okuyanlar.map(o =>
                        '<div class="d-flex justify-content-between small py-1 border-bottom">' +
                        '<span><i class="ti ti-user me-1"></i>' + escHtml(o.full_name) + ' <span class="text-secondary">&lt;' + escHtml(o.email) + '&gt;</span></span>' +
                        '<span class="text-secondary">' + o.okundu_at + '</span></div>'
                    ).join('');
                $('#okuyanlarIcerik').html(html);
            } else {
                listesiDiv.hide();
            }
            new bootstrap.Modal(document.getElementById('detayModal')).show();
        });
    });

    // ── Okundu (tablo) ──
    $(document).on('click', '.btn-okundu', function () {
        const encId = $(this).data('id');
        apiPost('okundu', { id: encId }, () => location.reload());
    });

    // ── Okundu (detay modal) ──
    $(document).on('click', '.btn-okundu-modal', function () {
        const encId = $(this).data('id');
        apiPost('okundu', { id: encId }, () => location.reload());
    });

    // ── Tümünü okundu ──
    $('#btnTumunuOku').on('click', function () {
        apiPost('tumunu_okundu', {}, () => location.reload());
    });

    // ── Sil ──
    $(document).on('click', '.btn-sil', function () {
        const encId = $(this).data('id');
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu duyuruyu silmek istediğinize emin misiniz?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'Vazgeç'
        }).then((result) => {
            if (result.isConfirmed) {
                apiPost('sil', { id: encId }, () => {
                    Swal.fire({
                        title: 'Silindi!',
                        text: 'Duyuru başarıyla silindi.',
                        icon: 'success',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                }, msg => {
                    Swal.fire('Hata', msg || 'Silme başarısız.', 'error');
                });
            }
        });
    });

    function apiPost(action, params, onSuccess, onError) {
        params.action = action;
        fetch('pages/duyurular/api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) { if (onSuccess) onSuccess(); }
            else if (onError) onError(res.message);
        });
    }

    function escHtml(str) {
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
});
</script>
