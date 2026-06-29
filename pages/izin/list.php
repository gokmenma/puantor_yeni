<?php
require_once ROOT . '/Model/IzinTalep.php';
require_once ROOT . '/Model/IzinTur.php';
require_once ROOT . '/Model/Persons.php';
require_once ROOT . '/App/Helper/security.php';

use App\Helper\Security;

$Auths->checkFirmReturn();

$firma_id    = (int) ($_SESSION['firm_id'] ?? 0);
$turler      = (new IzinTur())->getAktifTurler();
$personeller = (new Persons())->getPersonsByFirm($firma_id);
?>

<div class="page-header d-print-none mb-0">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <h2 class="page-title">Yıllık İzin Talepleri</h2>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" id="btn-yeni-talep">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M12 5l0 14"></path>
                        <path d="M5 12l14 0"></path>
                    </svg>
                    Yeni Talep
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <!-- Filtreler -->
        <div class="card mb-3">
            <div class="card-body">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label mb-1 small">Personel</label>
                        <select id="filter-personel" class="form-select select2-filter">
                            <option value="">Tüm Personel</option>
                            <?php foreach ($personeller as $p): ?>
                                <option value="<?= Security::encrypt($p->id) ?>"><?= htmlspecialchars($p->full_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 small">Durum</label>
                        <select id="filter-durum" class="form-select select2-filter">
                            <option value="">Tüm Durumlar</option>
                            <option value="beklemede">Beklemede</option>
                            <option value="onaylandi">Onaylı</option>
                            <option value="reddedildi">Reddedildi</option>
                            <option value="iptal">İptal</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 small">Başlangıç</label>
                        <input type="text" id="filter-baslangic" class="form-control flatpickr-filter" placeholder="gg.aa.yyyy" autocomplete="off">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label mb-1 small">Bitiş</label>
                        <input type="text" id="filter-bitis" class="form-control flatpickr-filter" placeholder="gg.aa.yyyy" autocomplete="off">
                    </div>
                    <div class="col-md-auto">
                        <button class="btn btn-primary" id="btn-filtrele">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                                <path d="M21 21l-6 -6" />
                            </svg>
                            Filtrele
                        </button>
                        <button class="btn btn-outline-secondary ms-1" id="btn-temizle">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M20 11a8.1 8.1 0 0 0 -15.5 -2m-.5 -4v4h4" />
                                <path d="M4 13a8.1 8.1 0 0 0 15.5 2m.5 4v-4h-4" />
                            </svg>
                            Temizle
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tablo -->
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-vcenter table-hover card-table" id="izin-table">
                        <thead>
                            <tr>
                                <th>Personel</th>
                                <th>İzin Türü</th>
                                <th>Başlangıç</th>
                                <th>Bitiş</th>
                                <th class="text-center">Gün</th>
                                <th class="text-center">Durum</th>
                                <th>Talep Tarihi</th>
                                <th class="text-center">İşlem</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal: Yeni Talep -->
<div class="modal modal-blur fade" id="modalYeniTalep" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni İzin Talebi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Personel</label>
                    <select id="yeni-personel" class="form-select select2-modal">
                        <option value="">Seçiniz</option>
                        <?php foreach ($personeller as $p): ?>
                            <option value="<?= Security::encrypt($p->id) ?>"><?= htmlspecialchars($p->full_name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">İzin Türü</label>
                    <select id="yeni-tur" class="form-select select2-modal">
                        <option value="">Seçiniz</option>
                        <?php foreach ($turler as $t): ?>
                            <option value="<?= $t->id ?>"><?= htmlspecialchars($t->ad) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label">Başlangıç Tarihi</label>
                        <input type="text" id="yeni-baslangic" class="form-control flatpickr-modal" placeholder="gg.aa.yyyy" autocomplete="off">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Bitiş Tarihi</label>
                        <input type="text" id="yeni-bitis" class="form-control flatpickr-modal" placeholder="gg.aa.yyyy" autocomplete="off">
                    </div>
                </div>
                <div class="mt-2 mb-3 p-2 bg-light rounded">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="text-muted small">Kullanılacak İzin Günü:</span>
                        <strong id="takvim-gun-sayisi-preview" class="text-secondary">—</strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Düşülecek İzin Günü (İş Günü):</span>
                        <strong id="gun-sayisi-preview" class="text-success">—</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Açıklama <span class="text-muted small">(opsiyonel)</span></label>
                    <textarea id="yeni-aciklama" class="form-control" rows="6"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">İznin Geçirileceği Adres</label>
                    <textarea id="yeni-adres" class="form-control" rows="3" placeholder="İzninizi geçireceğiniz adres..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M18 6l-12 12"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>
                    İptal
                </button>
                <button type="button" class="btn btn-primary" id="btn-talep-kaydet">
                    Kaydet
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-end icon-2">
                        <path d="M5 12l14 0"></path>
                        <path d="M13 18l6 -6"></path>
                        <path d="M13 6l6 6"></path>
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Red Notu -->
<div class="modal modal-blur fade" id="modalRed" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Red Notu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <textarea id="red-not" class="form-control" rows="6" placeholder="Neden reddediliyor?"></textarea>
                <input type="hidden" id="red-talep-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M18 6l-12 12"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>
                    İptal
                </button>
                <button type="button" class="btn btn-danger" id="btn-red-onayla">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M18 6l-12 12"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>
                    Reddet
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Kısmi Onayla -->
<div class="modal modal-blur fade" id="modalKismiOnay" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Kısmi Onay</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted small">Personel</span>
                    <strong id="kismi-personel-adi" class="small text-end"></strong>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted small">Talep Tarihleri</span>
                    <span id="kismi-talep-tarihleri" class="small text-end"></span>
                </div>
                <div class="mb-3 d-flex justify-content-between">
                    <span class="text-muted small">Talep Edilen</span>
                    <span id="kismi-orijinal-gun" class="small fw-bold text-end"></span>
                </div>
                <hr class="my-2">
                <div class="mb-3">
                    <label class="form-label">Onaylanacak Bitiş Tarihi</label>
                    <input type="text" id="kismi-bitis" class="form-control" placeholder="gg.aa.yyyy" autocomplete="off">
                    <div class="mt-2 p-2 bg-light rounded">
                        <span class="text-muted small">Onaylanacak gün: </span>
                        <strong id="kismi-gun-preview" class="text-warning">—</strong>
                    </div>
                </div>
                <input type="hidden" id="kismi-talep-id">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path d="M18 6l-12 12"></path>
                        <path d="M6 6l12 12"></path>
                    </svg>
                    İptal
                </button>
                <button type="button" class="btn btn-warning" id="btn-kismi-onayla">
                    Kısmi Onayla
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-end icon-2">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                        <path d="M16 3v4" />
                        <path d="M8 3v4" />
                        <path d="M4 11h16" />
                        <path d="M9 15l2 2l4 -4" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Formu Yazdır / İmza Modali -->
<div class="modal modal-blur fade" id="modalYazdir" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">İzin Formu İmza Bilgileri</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="print-talep-id">
                <p class="text-muted small mb-3">İzin talep formunun altında yer alacak onaylayan kişileri ve ünvanlarını seçiniz. Yeni bir ünvan veya isim eklemek için yazıp <strong>Enter</strong> tuşuna basınız.</p>
                
                <div class="card mb-2">
                    <div class="card-header py-2 bg-light">
                        <h3 class="card-title text-muted fw-bold small mb-0">1. ONAYLAYAN (Örn: İnsan Kaynakları)</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ünvan/Görev</label>
                                <select id="print-unvan-1" class="form-select select2-modal-signature" data-type="unvan"></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ad Soyad</label>
                                <select id="print-isim-1" class="form-select select2-modal-signature" data-type="isim"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-2">
                    <div class="card-header py-2 bg-light">
                        <h3 class="card-title text-muted fw-bold small mb-0">2. ONAYLAYAN (Örn: Bölüm Yöneticisi)</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ünvan/Görev</label>
                                <select id="print-unvan-2" class="form-select select2-modal-signature" data-type="unvan"></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ad Soyad</label>
                                <select id="print-isim-2" class="form-select select2-modal-signature" data-type="isim"></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-2">
                    <div class="card-header py-2 bg-light">
                        <h3 class="card-title text-muted fw-bold small mb-0">3. ONAYLAYAN (Örn: Genel Müdür)</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-2">
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ünvan/Görev</label>
                                <select id="print-unvan-3" class="form-select select2-modal-signature" data-type="unvan"></select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted small mb-1">Ad Soyad</label>
                                <select id="print-isim-3" class="form-select select2-modal-signature" data-type="isim"></select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-primary" id="btn-form-yazdir">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                        <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                        <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
                    </svg>
                    Yazdır
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CAN_DELETE_APPROVED = <?= $Auths->Authorize('onayli_izinleri_sil') ? 'true' : 'false' ?>;

$(document).ready(function() {

    const IZIN_API = 'api/izin/talep.php';

    // ---------- Select2 ----------
    $('.select2-filter').select2({ width: '100%', allowClear: true, placeholder: 'Seçiniz' });
    $('.select2-modal').select2({ width: '100%', allowClear: true, placeholder: 'Seçiniz', dropdownParent: $('#modalYeniTalep') });

    // ---------- Flatpickr ----------
    const fpOpts = { dateFormat: 'd.m.Y', locale: 'tr', allowInput: true };
    flatpickr('.flatpickr-filter', fpOpts);

    let fpBaslangic, fpBitis;
    fpBaslangic = flatpickr('#yeni-baslangic', {
        ...fpOpts,
        onChange: function(dates) {
            if (fpBitis) fpBitis.set('minDate', dates[0] || null);
            calcGun();
        }
    });
    fpBitis = flatpickr('#yeni-bitis', {
        ...fpOpts,
        onChange: calcGun
    });

    // ---------- DataTable ----------
    const dt = window.createDataTable('#izin-table', {
        data: [],
        columns: [
            { data: 'personel_adi' },
            { data: 'tur_adi' },
            { data: 'baslangic_tarihi', render: fmtDate },
            { data: 'bitis_tarihi',     render: fmtDate },
            { data: 'gun_sayisi', className: 'text-center', render: d => `<strong>${d}</strong>` },
            { data: 'durum', className: 'text-center', render: durumBadge },
            { data: 'olusturma_tarihi', render: fmtDate },
            { data: null, orderable: false, className: 'text-center', render: (d, t, row) => buildActions(row) }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        skipSearch: ['İşlem'],
    });

    function loadList() {
        const baslangicRaw = $('#filter-baslangic').val();
        const bitisRaw     = $('#filter-bitis').val();

        const params = new URLSearchParams({
            action:      'list',
            personel_id: $('#filter-personel').val(),
            durum:       $('#filter-durum').val(),
            baslangic:   baslangicRaw ? parseTrDate(baslangicRaw) : '',
            bitis:       bitisRaw     ? parseTrDate(bitisRaw)     : '',
        });

        $.get(IZIN_API + '?' + params.toString(), function(res) {
            if (res.status !== 'success') return;
            dt.clear().rows.add(res.list).draw();
        });
    }

    function parseTrDate(str) {
        const p = str.split('.');
        return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : str;
    }

    function fmtDate(d) {
        if (!d) return '—';
        const p = (d + '').split(/[-T ]/);
        return p.length >= 3 ? `${p[2]}.${p[1]}.${p[0]}` : d;
    }

    function durumBadge(d) {
        const map = {
            beklemede:  ['bg-warning text-white', 'Beklemede'],
            onaylandi:  ['bg-success text-white',  'Onaylı'],
            reddedildi: ['bg-danger text-white',   'Reddedildi'],
            iptal:      ['bg-secondary text-white','İptal'],
        };
        const [cls, lbl] = map[d] || ['bg-secondary text-white', d];
        return `<span class="badge ${cls}">${lbl}</span>`;
    }

    function buildActions(row) {
        let html = '';
        const printBtn = `<button class="btn btn-icon btn-sm rounded-circle btn-outline-primary ms-1" onclick="openPrintModal('${row.id}')" title="Formu Yazdır" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0; pointer-events: none;">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M17 17h2a2 2 0 0 0 2 -2v-4a2 2 0 0 0 -2 -2h-14a2 2 0 0 0 -2 2v4a2 2 0 0 0 2 2h2" />
                <path d="M17 9v-4a2 2 0 0 0 -2 -2h-6a2 2 0 0 0 -2 2v4" />
                <path d="M7 13m0 2a2 2 0 0 1 2 -2h6a2 2 0 0 1 2 2v4a2 2 0 0 1 -2 2h-6a2 2 0 0 1 -2 -2z" />
            </svg>
        </button>`;

        if (row.durum === 'beklemede') {
            html += `<button class="btn btn-icon btn-sm rounded-circle btn-outline-success me-1" onclick="onayla('${row.id}')" title="Onayla" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0; pointer-events: none;">
                    <path d="M5 12l5 5l10 -10"></path>
                </svg>
            </button>`;
            html += `<button class="btn btn-icon btn-sm rounded-circle btn-outline-warning me-1" onclick="kismiOnaylaAc('${row.id}', '${row.baslangic_tarihi}', '${row.bitis_tarihi}', '${row.personel_adi}', ${row.gun_sayisi})" title="Kısmi Onayla" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0; pointer-events: none;">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M4 7a2 2 0 0 1 2 -2h12a2 2 0 0 1 2 2v12a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-12z" />
                    <path d="M16 3v4" />
                    <path d="M8 3v4" />
                    <path d="M4 11h16" />
                    <path d="M9 15l2 2l4 -4" />
                </svg>
            </button>`;
            html += `<button class="btn btn-icon btn-sm rounded-circle btn-outline-danger me-1" onclick="openRedModal('${row.id}')" title="Reddet" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; padding: 0;">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0; pointer-events: none;">
                    <path d="M18 6l-12 12"></path>
                    <path d="M6 6l12 12"></path>
                </svg>
            </button>`;
            html += printBtn;
        } else if (row.durum === 'onaylandi') {
            html += `<button class="btn btn-xs btn-outline-danger" onclick="openRedModal('${row.id}')" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 0.15rem 0.4rem; font-weight: 500; height: 20px; font-size: 11px;">
                <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0; pointer-events: none;">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M9 14l-4 -4l4 -4" />
                    <path d="M5 10h11a4 4 0 1 1 0 8h-1" />
                </svg>
                Geri Al
            </button>`;
            if (CAN_DELETE_APPROVED) {
                html += ` <button class="btn btn-xs btn-outline-danger me-1" onclick="talepSil('${row.id}')" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 0.15rem 0.4rem; font-weight: 500; height: 20px; font-size: 11px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0; pointer-events: none;">
                        <path d="M4 7h16"></path>
                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                    </svg>
                    Sil
                </button>`;
            }
            html += printBtn;
        } else if (row.durum === 'reddedildi') {
            if (CAN_DELETE_APPROVED) {
                html += `<button class="btn btn-xs btn-outline-danger" onclick="talepSil('${row.id}')" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px; padding: 0.15rem 0.4rem; font-weight: 500; height: 20px; font-size: 11px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-1" style="margin: 0; pointer-events: none;">
                        <path d="M4 7h16"></path>
                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"></path>
                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"></path>
                    </svg>
                    Sil
                </button>`;
            } else {
                html = '<span class="text-muted">—</span>';
            }
        } else {
            html = '<span class="text-muted">—</span>';
        }
        return `<div class="d-flex align-items-center justify-content-center gap-1 text-nowrap">${html}</div>`;
    }

    // ---------- Gün hesaplama ----------
    let calcTimer = null;
    function calcGun() {
        const b = $('#yeni-baslangic').val();
        const s = $('#yeni-bitis').val();
        if (!b || !s) { 
            $('#gun-sayisi-preview').text('—'); 
            $('#takvim-gun-sayisi-preview').text('—'); 
            return; 
        }

        const parseTr = (str) => {
            const parts = str.split('.');
            if (parts.length === 3) {
                return new Date(parts[2], parts[1] - 1, parts[0]);
            }
            return null;
        };

        const d1 = parseTr(b);
        const d2 = parseTr(s);
        if (d1 && d2 && d2 >= d1) {
            const diffTime = Math.abs(d2 - d1);
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;
            $('#takvim-gun-sayisi-preview').text(diffDays + ' gün');
        } else {
            $('#takvim-gun-sayisi-preview').text('—');
        }

        clearTimeout(calcTimer);
        calcTimer = setTimeout(() => {
            $.get(IZIN_API, { action: 'calc_gun', baslangic: parseTrDate(b), bitis: parseTrDate(s) }, function(res) {
                $('#gun-sayisi-preview').text(res.status === 'success' ? res.gun_sayisi + ' gün' : '—');
            });
        }, 400);
    }

    // ---------- Yeni talep ----------
    $('#btn-yeni-talep').on('click', function() {
        $('#yeni-personel, #yeni-tur').val(null).trigger('change');
        $('#yeni-aciklama').val('');
        $('#yeni-adres').val('');
        $('#gun-sayisi-preview').text('—');
        $('#takvim-gun-sayisi-preview').text('—');
        if (fpBaslangic) fpBaslangic.clear();
        if (fpBitis) fpBitis.clear();
        new bootstrap.Modal('#modalYeniTalep').show();
    });

    $('#btn-talep-kaydet').on('click', function() {
        const data = {
            action:           'add',
            personel_id:      $('#yeni-personel').val(),
            tur_id:           $('#yeni-tur').val(),
            baslangic_tarihi: parseTrDate($('#yeni-baslangic').val()),
            bitis_tarihi:     parseTrDate($('#yeni-bitis').val()),
            aciklama:         $('#yeni-aciklama').val(),
            adres:            $('#yeni-adres').val(),
        };
        $.post(IZIN_API, data, function(res) {
            Swal.fire({
                title: res.status === 'success' ? 'Başarılı' : 'Hata',
                text: res.message,
                icon: res.status === 'success' ? 'success' : 'error',
                confirmButtonText: 'Tamam'
            });
            if (res.status === 'success') {
                bootstrap.Modal.getInstance('#modalYeniTalep').hide();
                loadList();
            }
        });
    });

    // ---------- Onay / Red ----------
    window.onayla = function(encId) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu izin talebini onaylamak istediğinize emin misiniz?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#2fb344',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Onayla',
            cancelButtonText: 'İptal Et'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(IZIN_API, { action: 'approve', id: encId }, function(res) {
                    Swal.fire({
                        title: res.status === 'success' ? 'Başarılı' : 'Hata',
                        text: res.message,
                        icon: res.status === 'success' ? 'success' : 'error',
                        confirmButtonText: 'Tamam'
                    });
                    if (res.status === 'success') loadList();
                });
            }
        });
    };

    window.openRedModal = function(encId) {
        $('#red-talep-id').val(encId);
        $('#red-not').val('');
        new bootstrap.Modal('#modalRed').show();
    };

    $('#btn-red-onayla').on('click', function() {
        $.post(IZIN_API, { action: 'reject', id: $('#red-talep-id').val(), not: $('#red-not').val() }, function(res) {
            Swal.fire({
                title: res.status === 'success' ? 'Başarılı' : 'Hata',
                text: res.message,
                icon: res.status === 'success' ? 'success' : 'error',
                confirmButtonText: 'Tamam'
            });
            if (res.status === 'success') {
                bootstrap.Modal.getInstance('#modalRed').hide();
                loadList();
            }
        });
    });

    // ---------- Kısmi Onayla ----------
    let fpKismi = null;
    let kismiBaslangic = null;

    window.kismiOnaylaAc = function(encId, baslangic, bitis, personelAdi, orijinalGun) {
        kismiBaslangic = baslangic;
        $('#kismi-talep-id').val(encId);
        $('#kismi-personel-adi').text(personelAdi);
        $('#kismi-talep-tarihleri').text(fmtDate(baslangic) + ' – ' + fmtDate(bitis));
        $('#kismi-orijinal-gun').text(orijinalGun + ' iş günü');
        $('#kismi-gun-preview').text('—');

        if (fpKismi) fpKismi.destroy();
        fpKismi = flatpickr('#kismi-bitis', {
            ...fpOpts,
            minDate: fmtDate(baslangic),
            maxDate: fmtDate(bitis),
            defaultDate: null,
            onChange: calcKismiGun
        });
        fpKismi.clear();

        new bootstrap.Modal('#modalKismiOnay').show();
    };

    let kismiTimer = null;
    function calcKismiGun() {
        const val = $('#kismi-bitis').val();
        if (!val) { $('#kismi-gun-preview').text('—'); return; }
        clearTimeout(kismiTimer);
        kismiTimer = setTimeout(() => {
            $.get(IZIN_API, {
                action: 'calc_gun',
                baslangic: kismiBaslangic,
                bitis: parseTrDate(val)
            }, function(res) {
                $('#kismi-gun-preview').text(res.status === 'success' ? res.gun_sayisi + ' gün' : '—');
            });
        }, 400);
    }

    $('#btn-kismi-onayla').on('click', function() {
        const id    = $('#kismi-talep-id').val();
        const bitis = parseTrDate($('#kismi-bitis').val());
        if (!bitis) {
            Swal.fire({ title: 'Hata', text: 'Lütfen bitiş tarihi giriniz.', icon: 'error', confirmButtonText: 'Tamam' });
            return;
        }
        $.post(IZIN_API, { action: 'partial_approve', id: id, yeni_bitis: bitis }, function(res) {
            Swal.fire({
                title: res.status === 'success' ? 'Başarılı' : 'Hata',
                text: res.message,
                icon: res.status === 'success' ? 'success' : 'error',
                confirmButtonText: 'Tamam'
            });
            if (res.status === 'success') {
                bootstrap.Modal.getInstance('#modalKismiOnay').hide();
                loadList();
            }
        });
    });

    window.talepSil = function(encId) {
        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu izin talebi kalıcı olarak silinecektir. Bu işlem geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal Et'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(IZIN_API, { action: 'delete', id: encId }, function(res) {
                    Swal.fire({
                        title: res.status === 'success' ? 'Başarılı' : 'Hata',
                        text: res.message,
                        icon: res.status === 'success' ? 'success' : 'error',
                        confirmButtonText: 'Tamam'
                    });
                    if (res.status === 'success') loadList();
                });
            }
        });
    };

    // ---------- Filtre ----------
    $('#btn-filtrele').on('click', loadList);
    $('#btn-temizle').on('click', function() {
        $('#filter-personel, #filter-durum').val(null).trigger('change');
        flatpickr('#filter-baslangic').clear();
        flatpickr('#filter-bitis').clear();
        loadList();
    });

    // ---------- Signature Printing Modal Logic ----------
    let loadedOptions = { unvan: [], isim: [] };

    function templateResultFormat(state) {
        if (!state.id || state.id === "") {
            return state.text;
        }
        if (!state.element) {
            return state.text;
        }
        const selectEl = $(state.element).closest('select');
        const type = selectEl.data('type');
        if (!type) return state.text;

        const opt = loadedOptions[type].find(o => o.deger === state.text);
        if (!opt) {
            return state.text;
        }

        return $(
            '<div class="d-flex justify-content-between align-items-center w-100">' +
                '<span>' + state.text + '</span>' +
                '<button type="button" class="btn btn-sm btn-ghost-danger p-0 delete-sig-opt" data-id="' + opt.id + '" style="line-height: 1; height: 18px; width: 18px; display: inline-flex; align-items: center; justify-content: center; border:none; background:transparent;">' +
                    '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon text-danger p-0 m-0"><path d="M18 6l-12 12"></path><path d="M6 6l12 12"></path></svg>' +
                '</button>' +
            '</div>'
        );
    }

    $('.select2-modal-signature').select2({
        width: '100%',
        allowClear: true,
        placeholder: 'Seçiniz veya yazınız...',
        dropdownParent: $('#modalYazdir'),
        tags: true,
        templateResult: templateResultFormat
    });

    function loadSignatureOptions(callback) {
        $.get('api/izin/form_onay_options.php', { action: 'list' }, function(res) {
            if (res.status === 'success') {
                loadedOptions.unvan = res.unvanlar;
                loadedOptions.isim = res.isimler;

                populateSelect2Signature('#print-unvan-1', res.unvanlar);
                populateSelect2Signature('#print-unvan-2', res.unvanlar);
                populateSelect2Signature('#print-unvan-3', res.unvanlar);

                populateSelect2Signature('#print-isim-1', res.isimler);
                populateSelect2Signature('#print-isim-2', res.isimler);
                populateSelect2Signature('#print-isim-3', res.isimler);

                if (res.last_selections) {
                    $('#print-unvan-1').val(res.last_selections.izin_form_onaylayan_unvan_1 || '').trigger('change');
                    $('#print-isim-1').val(res.last_selections.izin_form_onaylayan_isim_1 || '').trigger('change');
                    $('#print-unvan-2').val(res.last_selections.izin_form_onaylayan_unvan_2 || '').trigger('change');
                    $('#print-isim-2').val(res.last_selections.izin_form_onaylayan_isim_2 || '').trigger('change');
                    $('#print-unvan-3').val(res.last_selections.izin_form_onaylayan_unvan_3 || '').trigger('change');
                    $('#print-isim-3').val(res.last_selections.izin_form_onaylayan_isim_3 || '').trigger('change');
                }

                if (callback) callback();
            }
        });
    }

    function populateSelect2Signature(selector, data) {
        const select = $(selector);
        const currentVal = select.val();
        let html = '<option value="">Seçiniz</option>';
        data.forEach(item => {
            html += `<option value="${item.deger}">${item.deger}</option>`;
        });
        select.html(html);
        select.val(currentVal);
    }

    $('.select2-modal-signature').on('select2:select', function(e) {
        const data = e.params.data;
        const selectEl = $(this);
        const type = selectEl.data('type');
        const text = data.text.trim();

        const exists = loadedOptions[type].some(opt => opt.deger.toLowerCase() === text.toLowerCase());
        if (!exists && text !== '') {
            $.post('api/izin/form_onay_options.php', { action: 'add', type: type, val: text }, function(res) {
                if (res.status === 'success') {
                    loadedOptions[type].push({ id: res.id, deger: text });
                    $(`.select2-modal-signature[data-type="${type}"]`).each(function() {
                        const s = $(this);
                        const val = s.val();
                        let html = '<option value="">Seçiniz</option>';
                        loadedOptions[type].forEach(item => {
                            html += `<option value="${item.deger}">${item.deger}</option>`;
                        });
                        s.html(html).val(val).trigger('change');
                    });
                    // Restore the newly added value on the current select
                    selectEl.val(text).trigger('change');
                }
            });
        }
    });

    $(document).on('click', '.delete-sig-opt', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const btn = $(this);
        const id = btn.data('id');

        Swal.fire({
            title: 'Emin misiniz?',
            text: "Bu seçenek silinecektir. Sildiğinizde listede tekrar görünmeyecektir.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'İptal'
        }).then((result) => {
            if (result.isConfirmed) {
                $('.select2-modal-signature').select2('close');
                $.post('api/izin/form_onay_options.php', { action: 'delete', id: id }, function(res) {
                    if (res.status === 'success') {
                        // Clear selected values that match the deleted option
                        $('.select2-modal-signature').each(function() {
                            if ($(this).val() === btn.parent().find('span').text()) {
                                $(this).val('').trigger('change');
                            }
                        });
                        loadSignatureOptions();
                    } else {
                        Swal.fire('Hata!', res.message, 'error');
                    }
                });
            }
        });
    });

    window.openPrintModal = function(encId) {
        $('#print-talep-id').val(encId);
        loadSignatureOptions(function() {
            new bootstrap.Modal('#modalYazdir').show();
        });
    };

    $('#btn-form-yazdir').on('click', function() {
        const id = $('#print-talep-id').val();
        const selections = {
            izin_form_onaylayan_unvan_1: $('#print-unvan-1').val() || '',
            izin_form_onaylayan_isim_1: $('#print-isim-1').val() || '',
            izin_form_onaylayan_unvan_2: $('#print-unvan-2').val() || '',
            izin_form_onaylayan_isim_2: $('#print-isim-2').val() || '',
            izin_form_onaylayan_unvan_3: $('#print-unvan-3').val() || '',
            izin_form_onaylayan_isim_3: $('#print-isim-3').val() || ''
        };

        $.post('api/izin/form_onay_options.php', { action: 'save_last', selections: selections }, function(res) {
            if (res.status === 'success') {
                bootstrap.Modal.getInstance('#modalYazdir').hide();
                const params = new URLSearchParams({
                    id: id,
                    u1: selections.izin_form_onaylayan_unvan_1,
                    i1: selections.izin_form_onaylayan_isim_1,
                    u2: selections.izin_form_onaylayan_unvan_2,
                    i2: selections.izin_form_onaylayan_isim_2,
                    u3: selections.izin_form_onaylayan_unvan_3,
                    i3: selections.izin_form_onaylayan_isim_3
                });
                window.open('print_izin.php?' + params.toString(), '_blank');
            } else {
                Swal.fire('Hata!', 'Tercihler kaydedilemedi: ' + res.message, 'error');
            }
        });
    });

    loadList();
});
</script>
