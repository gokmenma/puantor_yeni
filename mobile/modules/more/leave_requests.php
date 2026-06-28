<?php
// Puantor Mobil - Personel İzin Talepleri
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";
require_once ROOT . "/Model/Persons.php";
require_once ROOT . "/Model/IzinTur.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$firm_id = $_SESSION['firm_id'] ?? 0;
$can_delete_approved = $Auths->Authorize('onayli_izinleri_sil') ? 'true' : 'false';

$personsModel = new Persons();
$turModel = new IzinTur();

$personeller = $personsModel->getPersonsByFirm($firm_id);
$turler = $turModel->getAktifTurler();
?>

<style>
    .leave-card {
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
        background: #fff;
        margin-bottom: 12px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    body[data-bs-theme="dark"] .leave-card {
        background: #1e293b;
        border-color: rgba(255,255,255,0.05);
    }
    .leave-card:active {
        transform: scale(0.99);
    }
    .status-badge {
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    .status-pending { background: rgba(245, 158, 11, 0.1); color: #d97706; }
    .status-approved { background: rgba(16, 185, 129, 0.1); color: #059669; }
    .status-rejected { background: rgba(239, 68, 68, 0.1); color: #dc2626; }
    .status-canceled { background: rgba(108, 117, 125, 0.1); color: #6c757d; }

    .empty-state {
        padding: 40px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 16px;
    }
    
    .filter-btn.active {
        background-color: var(--tblr-primary) !important;
        color: #fff !important;
    }

    /* Premium Select2 Styling for Mobile */
    .select2-container--default .select2-selection--single {
        height: 46px !important;
        border-radius: 12px !important;
        border: 1px solid rgba(0,0,0,0.12) !important;
        background-color: #fff !important;
        display: flex !important;
        align-items: center !important;
        transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
    }
    body[data-bs-theme="dark"] .select2-container--default .select2-selection--single {
        background-color: #1e293b !important;
        border-color: rgba(255,255,255,0.12) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 46px !important;
        padding-left: 14px !important;
        font-size: 0.88rem !important;
        font-weight: 500 !important;
        color: var(--tblr-body-color) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px !important;
        right: 8px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow b {
        border-color: #94a3b8 transparent transparent transparent !important;
        border-width: 5px 4px 0 4px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: #206bc4 !important;
        box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, 0.15) !important;
    }
    /* Select2 Dropdown List Styling */
    .select2-dropdown {
        border-radius: 16px !important;
        border: 1px solid rgba(0,0,0,0.08) !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
        overflow: hidden !important;
        background-color: #fff !important;
        z-index: 999999 !important; /* Ensure dropdown is above Bootstrap Modal */
    }
    body[data-bs-theme="dark"] .select2-dropdown {
        background-color: #1e293b !important;
        border-color: rgba(255,255,255,0.08) !important;
    }
    .select2-container--default .select2-results__option {
        padding: 10px 14px !important;
        font-size: 0.88rem !important;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background-color: #206bc4 !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border-radius: 8px !important;
        border: 1px solid rgba(0,0,0,0.1) !important;
        padding: 6px 10px !important;
    }
    body[data-bs-theme="dark"] .select2-container--default .select2-search--dropdown .select2-search__field {
        background-color: #0f172a !important;
        border-color: rgba(255,255,255,0.1) !important;
        color: #fff !important;
    }
</style>

<div class="container px-0">
    <div class="mb-4 d-flex align-items-center justify-content-between">
        <div>
            <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">İzin Talepleri</h2>
            <p class="text-muted text-xs mb-0">Personelden gelen yıllık ve diğer izin taleplerini yönetin.</p>
        </div>
        <button class="btn btn-primary btn-icon rounded-circle" id="btn-yeni-talep" style="width: 42px; height: 42px; flex-shrink: 0;">
            <i class="ti ti-plus" style="font-size: 1.25rem;"></i>
        </button>
    </div>

    <!-- Filtreler -->
    <div class="d-flex gap-2 mb-3 overflow-auto pb-2 no-scrollbar">
        <button class="btn btn-sm btn-light rounded-pill px-3 active filter-btn" data-status="all">Tümü</button>
        <button class="btn btn-sm btn-light rounded-pill px-3 filter-btn" data-status="beklemede">Bekleyenler</button>
        <button class="btn btn-sm btn-light rounded-pill px-3 filter-btn" data-status="onaylandi">Onaylananlar</button>
        <button class="btn btn-sm btn-light rounded-pill px-3 filter-btn" data-status="reddedildi">Reddedilenler</button>
    </div>

    <div id="leave-list">
        <!-- Talepler AJAX ile buraya yüklenecek -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
</div>

<!-- Yeni İzin Talebi Modalı -->
<div class="modal fade modal-bottom-sheet" id="modalYeniTalep" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-semibold" style="font-size: 1.1rem;">Yeni İzin Talebi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="mb-3">
                    <label class="form-label text-xs text-muted">Personel</label>
                    <select id="yeni-personel" class="form-select select2-yeni">
                        <option value="">Seçiniz</option>
                        <?php foreach ($personeller as $p): ?>
                            <option value="<?php echo Security::encrypt($p->id); ?>"><?php echo htmlspecialchars($p->full_name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-xs text-muted">İzin Türü</label>
                    <select id="yeni-tur" class="form-select select2-yeni">
                        <option value="">Seçiniz</option>
                        <?php foreach ($turler as $t): ?>
                            <option value="<?php echo $t->id; ?>"><?php echo htmlspecialchars($t->ad); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label text-xs text-muted">Başlangıç Tarihi</label>
                        <input type="text" id="yeni-baslangic" class="form-control" placeholder="Seçiniz" readonly style="border-radius: 12px;">
                    </div>
                    <div class="col-6">
                        <label class="form-label text-xs text-muted">Bitiş Tarihi</label>
                        <input type="text" id="yeni-bitis" class="form-control" placeholder="Seçiniz" readonly style="border-radius: 12px;">
                    </div>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center bg-light p-2.5 rounded-3" style="font-size: 0.8rem;">
                    <div>
                        <span class="text-muted d-block" style="font-size: 0.72rem;">Takvim Günü:</span>
                        <strong id="takvim-gun-sayisi-preview" class="text-dark">—</strong>
                    </div>
                    <div class="text-end">
                        <span class="text-muted d-block" style="font-size: 0.72rem;">Hakedişten Düşecek Gün:</span>
                        <strong id="gun-sayisi-preview" class="text-azure">—</strong>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-xs text-muted">Açıklama</label>
                    <textarea id="yeni-aciklama" class="form-control" rows="2" placeholder="İzin gerekçesi, açıklama..." style="border-radius: 12px;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex gap-2">
                <button type="button" class="btn btn-light rounded-pill flex-fill" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" id="btn-talep-kaydet" class="btn btn-primary rounded-pill flex-fill">Talebi Oluştur</button>
            </div>
        </div>
    </div>
</div>

<!-- Kısmi Onayla Modalı -->
<div class="modal fade modal-bottom-sheet" id="modalKismiOnay" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-semibold" style="font-size: 1.1rem;">Kısmi Onayla</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body pt-3">
                <input type="hidden" id="kismi-talep-id">
                <div class="mb-3">
                    <label class="form-label text-xs text-muted">Personel</label>
                    <input type="text" id="kismi-personel-adi" class="form-control bg-light text-bold" readonly style="border-radius: 12px;">
                </div>
                <div class="mb-3">
                    <label class="form-label text-xs text-muted">Talep Edilen Başlangıç</label>
                    <input type="text" id="kismi-baslangic-tarihi" class="form-control bg-light" readonly style="border-radius: 12px;">
                </div>
                <div class="mb-3">
                    <label class="form-label text-xs text-muted">Yeni Bitiş Tarihi Seçin</label>
                    <input type="text" id="yeni-bitis-tarihi" class="form-control flatpickr-modal" placeholder="Bitiş Tarihi Seçin" style="border-radius: 12px;">
                </div>
                <p class="text-muted text-xxs mt-2">Kısmi onay işleminde başlangıç tarihi değişmez, sadece bitiş tarihini öne çekerek onaylayabilirsiniz.</p>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex gap-2">
                <button type="button" class="btn btn-light rounded-pill flex-fill" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" id="btn-kismi-onayla-submit" class="btn btn-warning rounded-pill flex-fill">Onayla</button>
            </div>
        </div>
    </div>
</div>

<!-- Reddet Modalı -->
<div class="modal fade modal-bottom-sheet" id="modalRed" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-semibold" style="font-size: 1.1rem;">Red Nedeni Girin</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body pt-3">
                <input type="hidden" id="red-talep-id">
                <div class="mb-3">
                    <label class="form-label text-xs text-muted">Red Açıklaması / Yönetici Notu</label>
                    <textarea id="red-notu" class="form-control" rows="3" placeholder="Reddetme nedeninizi buraya yazabilirsiniz (isteğe bağlı)..." style="border-radius: 12px;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 d-flex gap-2">
                <button type="button" class="btn btn-light rounded-pill flex-fill" data-bs-dismiss="modal">Vazgeç</button>
                <button type="button" id="btn-reddet-submit" class="btn btn-danger rounded-pill flex-fill">Reddet</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#modalYeniTalep').appendTo('body');
    $('#modalKismiOnay').appendTo('body');
    $('#modalRed').appendTo('body');

    const CAN_DELETE_APPROVED = <?php echo $can_delete_approved; ?>;
    const API_URL = 'api/izin.php';

    loadLeaves();

    function loadLeaves() {
        $.get(API_URL + '?action=list')
        .done(function(response) {
            if (response && response.status === 'success') {
                renderLeaves(response.list);
            } else {
                const msg = response && response.message ? response.message : 'Veriler yüklenemedi.';
                $('#leave-list').html(`<div class="alert alert-danger">${msg}</div>`);
            }
        })
        .fail(function() {
            $('#leave-list').html('<div class="alert alert-danger">Bağlantı hatası: Sunucudan cevap alınamadı.</div>');
        });
    }

    function fmtDate(d) {
        if (!d) return '—';
        const p = (d + '').split(/[-T ]/);
        return p.length >= 3 ? `${p[2]}.${p[1]}.${p[0]}` : d;
    }

    function parseTrDate(str) {
        if (!str) return '';
        const p = str.split('.');
        return p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : str;
    }

    function parseTr(str) {
        if (!str) return null;
        const parts = str.split('.');
        if (parts.length === 3) {
            return new Date(parts[2], parts[1] - 1, parts[0]);
        }
        return null;
    }

    function renderLeaves(list) {
        if (!list || list.length === 0) {
            $('#leave-list').html(`
                <div class="empty-state">
                    <i class="ti ti-calendar-off"></i>
                    <p class="text-muted">Henüz bir izin talebi bulunmuyor.</p>
                </div>
            `);
            return;
        }

        let html = '';
        list.forEach(item => {
            let statusClass = '';
            let statusText = '';
            if (item.durum === 'beklemede') { statusClass = 'status-pending'; statusText = 'Bekliyor'; }
            else if (item.durum === 'onaylandi') { statusClass = 'status-approved'; statusText = 'Onaylandı'; }
            else if (item.durum === 'reddedildi') { statusClass = 'status-rejected'; statusText = 'Reddedildi'; }
            else if (item.durum === 'iptal') { statusClass = 'status-canceled'; statusText = 'İptal Edildi'; }
            else { statusClass = 'status-canceled'; statusText = item.durum; }

            html += `
                <div class="leave-card p-3 shadow-sm" data-status="${item.durum}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-sm rounded-circle bg-azure-lt">
                                ${item.personel_adi.substring(0, 2).toUpperCase()}
                            </div>
                            <div>
                                <h4 class="mb-0 text-bold" style="font-size: 0.95rem;">${item.personel_adi}</h4>
                                <span class="text-muted" style="font-size: 0.72rem;">Oluşturma: ${fmtDate(item.olusturma_tarihi)}</span>
                            </div>
                        </div>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="text-muted text-xs">Tür:</span>
                            <span class="text-bold text-azure" style="font-size: 0.85rem;">${item.tur_adi}</span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="text-muted text-xs">Süre:</span>
                            <span class="text-bold text-dark" style="font-size: 0.9rem;">${item.gun_sayisi} Gün</span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="text-muted text-xs">Tarihler:</span>
                            <span class="text-semibold text-sm">${fmtDate(item.baslangic_tarihi)} - ${fmtDate(item.bitis_tarihi)}</span>
                        </div>
                    </div>

                    ${item.aciklama ? `
                    <div class="bg-light p-2 rounded-2 mb-3" style="font-size: 0.8rem; border-left: 3px solid #cbd5e1;">
                        <i class="ti ti-quote text-muted me-1"></i>${item.aciklama}
                    </div>
                    ` : ''}

                    ${item.yonetici_notu ? `
                    <div class="bg-light p-2 rounded-2 mb-3" style="font-size: 0.8rem; border-left: 3px solid #f59e0b;">
                        <span class="text-bold text-warning text-xs d-block">Yönetici Notu:</span>
                        ${item.yonetici_notu}
                    </div>
                    ` : ''}

                    <div class="d-flex gap-2 justify-content-end border-top pt-3 mt-1">
                        ${item.durum === 'beklemede' ? `
                            <button class="btn btn-light btn-sm px-3 rounded-pill text-danger btn-reject-action" data-id="${item.id}">
                                <i class="ti ti-x me-1"></i> Reddet
                            </button>
                            <button class="btn btn-light btn-sm px-3 rounded-pill text-warning btn-partial-action" data-id="${item.id}" data-name="${item.personel_adi}" data-start="${fmtDate(item.baslangic_tarihi)}" data-end="${fmtDate(item.bitis_tarihi)}">
                                <i class="ti ti-calendar me-1"></i> Kısmi Onay
                            </button>
                            <button class="btn btn-primary btn-sm px-3 rounded-pill btn-approve-action" data-id="${item.id}">
                                <i class="ti ti-check me-1"></i> Onayla
                            </button>
                        ` : ''}

                        ${item.durum === 'onaylandi' ? `
                            <button class="btn btn-light btn-sm px-3 rounded-pill text-danger btn-reject-action" data-id="${item.id}" title="Geri Al ve Reddet">
                                <i class="ti ti-rotate-clockwise-2 me-1"></i> Geri Al (Reddet)
                            </button>
                            ${CAN_DELETE_APPROVED ? `
                                <button class="btn btn-light btn-sm px-3 rounded-pill text-muted btn-delete-action" data-id="${item.id}">
                                    <i class="ti ti-trash me-1"></i> Sil
                                </button>
                            ` : ''}
                        ` : ''}

                        ${item.durum === 'reddedildi' && CAN_DELETE_APPROVED ? `
                            <button class="btn btn-light btn-sm px-3 rounded-pill text-muted btn-delete-action" data-id="${item.id}">
                                <i class="ti ti-trash me-1"></i> Sil
                            </button>
                        ` : ''}
                    </div>
                </div>
            `;
        });
        $('#leave-list').html(html);
        
        // Yeniden filtreleme yap ki seçili buton aktif kalsın
        const activeStatus = $('.filter-btn.active').data('status');
        applyFilter(activeStatus);
    }

    // Filtreleme fonksiyonu
    function applyFilter(status) {
        if (status === 'all') {
            $('.leave-card').show();
        } else {
            $('.leave-card').hide();
            $(`.leave-card[data-status="${status}"]`).show();
        }
    }

    // Filtre Butonları Tıklaması
    $(document).on('click', '.filter-btn', function() {
        $('.filter-btn').removeClass('active btn-primary').addClass('btn-light');
        $(this).addClass('active btn-primary').removeClass('btn-light');
        applyFilter($(this).data('status'));
    });

    // ---------- Yeni İzin Talebi Ekleme İşlemleri ----------
    let fpYeniBaslangic = null;
    let fpYeniBitis = null;
    let calcTimer = null;

    // Yeni Talep Butonuna Tıklama (Modalı Açar)
    $('#btn-yeni-talep').on('click', function() {
        $('#yeni-personel, #yeni-tur').val('').trigger('change');
        $('#yeni-aciklama').val('');
        $('#gun-sayisi-preview').text('—');
        $('#takvim-gun-sayisi-preview').text('—');
        
        if (fpYeniBaslangic) fpYeniBaslangic.clear();
        if (fpYeniBitis) fpYeniBitis.clear();

        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalYeniTalep')).show();
    });

    // Modal açıldığında Select2 ve Flatpickr'ları başlat (hidden element bug'larını önlemek için)
    $('#modalYeniTalep').on('shown.bs.modal', function () {
        $('.select2-yeni').select2({
            dropdownParent: $('#modalYeniTalep'),
            width: '100%'
        });

        if (!fpYeniBaslangic) {
            fpYeniBaslangic = flatpickr('#yeni-baslangic', {
                dateFormat: "d.m.Y",
                locale: "tr",
                disableMobile: "true",
                onChange: function(dates) {
                    if (fpYeniBitis) {
                        fpYeniBitis.set('minDate', dates[0] || null);
                    }
                    calcYeniGunler();
                }
            });
        }

        if (!fpYeniBitis) {
            fpYeniBitis = flatpickr('#yeni-bitis', {
                dateFormat: "d.m.Y",
                locale: "tr",
                disableMobile: "true",
                onChange: calcYeniGunler
            });
        }
    });

    function calcYeniGunler() {
        const b = $('#yeni-baslangic').val();
        const s = $('#yeni-bitis').val();
        if (!b || !s) {
            $('#takvim-gun-sayisi-preview').text('—');
            $('#gun-sayisi-preview').text('—');
            return;
        }

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
            $.get(API_URL, { action: 'calc_gun', baslangic: parseTrDate(b), bitis: parseTrDate(s) }, function(res) {
                $('#gun-sayisi-preview').text(res.status === 'success' ? res.gun_sayisi + ' gün' : '—');
            });
        }, 400);
    }

    // Yeni Talep Kaydet Submit
    $('#btn-talep-kaydet').on('click', function() {
        const personelId = $('#yeni-personel').val();
        const turId = $('#yeni-tur').val();
        const baslangic = $('#yeni-baslangic').val();
        const bitis = $('#yeni-bitis').val();
        const aciklama = $('#yeni-aciklama').val();

        if (!personelId || !turId || !baslangic || !bitis) {
            Swal.fire('Hata!', 'Lütfen tüm zorunlu alanları doldurun.', 'warning');
            return;
        }

        const data = {
            action: 'add',
            personel_id: personelId,
            tur_id: turId,
            baslangic_tarihi: parseTrDate(baslangic),
            bitis_tarihi: parseTrDate(bitis),
            aciklama: aciklama
        };

        $.post(API_URL, data, function(response) {
            if (response.status === 'success') {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('modalYeniTalep')).hide();
                Swal.fire({
                    title: 'Başarılı!',
                    text: response.message || 'İzin talebi başarıyla oluşturuldu.',
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
                });
                loadLeaves();
            } else {
                Swal.fire({
                    title: 'Hata!',
                    text: response.message || 'İzin talebi oluşturulurken hata oluştu.',
                    icon: 'error',
                    background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
                });
            }
        }, 'json');
    });

    // ---------- Onayla Butonu ----------
    $(document).on('click', '.btn-approve-action', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Onaylıyor musunuz?',
            text: 'Bu izin talebini onaylamak istediğinize emin misiniz?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Evet, Onayla',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#10b981',
            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b',
            borderRadius: '16px'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(API_URL, { action: 'approve', id: id }, function(response) {
                    Swal.fire({
                        title: response.status === 'success' ? 'Başarılı!' : 'Hata!',
                        text: response.message,
                        icon: response.status === 'success' ? 'success' : 'error',
                        background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                        color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
                    });
                    if (response.status === 'success') loadLeaves();
                }, 'json');
            }
        });
    });

    // Reddet Butonu (Modalı Açar)
    $(document).on('click', '.btn-reject-action', function() {
        const id = $(this).data('id');
        $('#red-talep-id').val(id);
        $('#red-notu').val('');
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRed'));
        modal.show();
    });

    // Reddet Submit
    $('#btn-reddet-submit').on('click', function() {
        const id = $('#red-talep-id').val();
        const not = $('#red-notu').val();
        
        $.post(API_URL, { action: 'reject', id: id, not: not }, function(response) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalRed')).hide();
            Swal.fire({
                title: response.status === 'success' ? 'Başarılı!' : 'Hata!',
                text: response.message,
                icon: response.status === 'success' ? 'success' : 'error',
                background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
            });
            if (response.status === 'success') loadLeaves();
        }, 'json');
    });

    // Kısmi Onay Butonu (Modalı Açar)
    let fpInstance = null;
    $(document).on('click', '.btn-partial-action', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        const start = $(this).data('start');
        const end = $(this).data('end');

        $('#kismi-talep-id').val(id);
        $('#kismi-personel-adi').val(name);
        $('#kismi-baslangic-tarihi').val(start);
        
        // flatpickr'ı yeniden oluştur
        if (fpInstance) {
            fpInstance.destroy();
        }

        const startParts = start.split('.');
        const minDateObj = new Date(startParts[2], startParts[1] - 1, startParts[0]);

        fpInstance = flatpickr('#yeni-bitis-tarihi', {
            dateFormat: "d.m.Y",
            locale: "tr",
            disableMobile: "true",
            minDate: minDateObj,
            defaultDate: end
        });

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKismiOnay'));
        modal.show();
    });

    // Kısmi Onayla Submit
    $('#btn-kismi-onayla-submit').on('click', function() {
        const id = $('#kismi-talep-id').val();
        const yeniBitisRaw = $('#yeni-bitis-tarihi').val();

        if (!yeniBitisRaw) {
            Swal.fire('Hata!', 'Lütfen yeni bitiş tarihini seçin.', 'warning');
            return;
        }

        // Tarihi Y-m-d formatına çevir
        const p = yeniBitisRaw.split('.');
        const yeniBitis = p.length === 3 ? `${p[2]}-${p[1]}-${p[0]}` : yeniBitisRaw;

        $.post(API_URL, { action: 'partial_approve', id: id, yeni_bitis: yeniBitis }, function(response) {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKismiOnay')).hide();
            Swal.fire({
                title: response.status === 'success' ? 'Başarılı!' : 'Hata!',
                text: response.message,
                icon: response.status === 'success' ? 'success' : 'error',
                background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
            });
            if (response.status === 'success') loadLeaves();
        }, 'json');
    });

    // Silme Butonu
    $(document).on('click', '.btn-delete-action', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Silmek istediğinize emin misiniz?',
            text: 'Bu işlem geri alınamaz! İzin talebi kalıcı olarak silinecektir.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Sil',
            cancelButtonText: 'Vazgeç',
            confirmButtonColor: '#ef4444',
            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b',
            borderRadius: '16px'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post(API_URL, { action: 'delete', id: id }, function(response) {
                    Swal.fire({
                        title: response.status === 'success' ? 'Başarılı!' : 'Hata!',
                        text: response.message,
                        icon: response.status === 'success' ? 'success' : 'error',
                        background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                        color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
                    });
                    if (response.status === 'success') loadLeaves();
                }, 'json');
            }
        });
    });
});
</script>
