<?php
// Puantor Mobil - Personel Avans Talepleri
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/date.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Date;
use App\Helper\Security;

$firm_id = $_SESSION['firm_id'] ?? 0;
?>

<style>
    .advance-card {
        border-radius: 16px;
        border: 1px solid rgba(0,0,0,0.05);
        background: #fff;
        margin-bottom: 12px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    body[data-bs-theme="dark"] .advance-card {
        background: #1e293b;
        border-color: rgba(255,255,255,0.05);
    }
    .advance-card:active {
        transform: scale(0.98);
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
    
    .btn-action {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: none;
        transition: all 0.2s;
    }
    .btn-approve { background: #10b981; color: white; }
    .btn-reject { background: #ef4444; color: white; }
    .btn-approve:active { background: #059669; transform: scale(0.9); }
    .btn-reject:active { background: #dc2626; transform: scale(0.9); }

    .empty-state {
        padding: 40px 20px;
        text-align: center;
    }
    .empty-state i {
        font-size: 3rem;
        color: #cbd5e1;
        margin-bottom: 16px;
    }
</style>

<div class="container px-0">
    <div class="mb-4 d-flex align-items-center justify-content-between">
        <div>
            <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Avans Talepleri</h2>
            <p class="text-muted text-xs mb-0">Personelden gelen avans taleplerini yönetin.</p>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="d-flex gap-2 mb-3 overflow-auto pb-2 no-scrollbar">
        <button class="btn btn-sm btn-light rounded-pill px-3 active filter-btn" data-status="all">Tümü</button>
        <button class="btn btn-sm btn-light rounded-pill px-3 filter-btn" data-status="0">Bekleyenler</button>
        <button class="btn btn-sm btn-light rounded-pill px-3 filter-btn" data-status="1">Onaylananlar</button>
        <button class="btn btn-sm btn-light rounded-pill px-3 filter-btn" data-status="2">Reddedilenler</button>
    </div>

    <div id="advance-list">
        <!-- Talepler AJAX ile buraya dolacak -->
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
        </div>
    </div>
</div>

<!-- Onay/Red Onay Modalı veya Toast -->
<script>
$(document).ready(function() {
    loadAdvances();

    let currentAdvancesList = [];

    function loadAdvances() {
        $.get('api/advances.php?action=list')
        .done(function(response) {
            if (typeof response === 'string') {
                try {
                    response = JSON.parse(response);
                } catch (e) {
                    console.error("JSON parse error:", e);
                }
            }
            
            if (response && response.status === 'success') {
                currentAdvancesList = response.list || [];
                renderAdvances(response.list);
            } else {
                const msg = response && response.message ? response.message : 'Veriler yüklenemedi.';
                $('#advance-list').html(`<div class="alert alert-danger">${msg}</div>`);
            }
        })
        .fail(function(xhr, status, error) {
            console.error("AJAX Error:", status, error, xhr.responseText);
            $('#advance-list').html('<div class="alert alert-danger">Bağlantı hatası: Sunucudan cevap alınamadı.</div>');
        });
    }

    function renderAdvances(list) {
        if (!list || list.length === 0) {
            $('#advance-list').html(`
                <div class="empty-state">
                    <i class="ti ti-receipt-off"></i>
                    <p class="text-muted">Henüz bir avans talebi bulunmuyor.</p>
                </div>
            `);
            return;
        }

        let html = '';
        list.forEach(item => {
            let statusClass = '';
            let statusText = '';
            if (item.durum == 0) { statusClass = 'status-pending'; statusText = 'Bekliyor'; }
            else if (item.durum == 1) { statusClass = 'status-approved'; statusText = 'Onaylandı'; }
            else if (item.durum == 2) { statusClass = 'status-rejected'; statusText = 'Reddedildi'; }

            html += `
                <div class="advance-card p-3 shadow-sm" data-status="${item.durum}">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div class="avatar avatar-sm rounded-circle bg-blue-lt">
                                ${item.full_name.substring(0, 2).toUpperCase()}
                            </div>
                            <div>
                                <h4 class="mb-0 text-bold" style="font-size: 0.95rem;">${item.full_name}</h4>
                                <span class="text-muted" style="font-size: 0.75rem;">${item.created_at}</span>
                            </div>
                        </div>
                        <span class="status-badge ${statusClass}">${statusText}</span>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="text-muted text-xs">Tutar:</span>
                            <span class="text-bold text-primary" style="font-size: 1.1rem;">₺${parseFloat(item.tutar).toLocaleString('tr-TR', {minimumFractionDigits: 2})}</span>
                        </div>
                        <div class="d-flex align-items-baseline gap-1">
                            <span class="text-muted text-xs">Dönem:</span>
                            <span class="text-semibold text-sm">${item.hedef_ay}/${item.hedef_yil}</span>
                        </div>
                    </div>

                    ${item.aciklama ? `
                    <div class="bg-light p-2 rounded-2 mb-2 text-secondary" style="font-size: 0.8rem; border-left: 3px solid #cbd5e1;">
                        <i class="ti ti-quote text-muted me-1"></i><strong>Talep Açıklaması:</strong> ${item.aciklama}
                    </div>
                    ` : ''}

                    ${item.admin_note ? `
                    <div class="p-2 rounded-2 mb-3 ${item.durum == 2 ? 'bg-danger-lt text-danger' : 'bg-light text-secondary'}" style="font-size: 0.8rem; border-left: 3px solid ${item.durum == 2 ? '#ef4444' : '#64748b'};">
                        <i class="ti ti-info-circle me-1"></i><strong>Yönetici Notu:</strong> ${item.admin_note}
                    </div>
                    ` : ''}

                    ${item.durum == 0 ? `
                    <div class="d-flex gap-2 justify-content-end border-top pt-3 mt-1">
                        <button class="btn btn-light btn-sm px-3 rounded-pill text-danger btn-reject-action" data-id="${item.id}">
                            <i class="ti ti-x me-1"></i> Reddet
                        </button>
                        <button class="btn btn-primary btn-sm px-3 rounded-pill btn-approve-action" data-id="${item.id}">
                            <i class="ti ti-check me-1"></i> Onayla
                        </button>
                    </div>
                    ` : ''}
                </div>
            `;
        });
        $('#advance-list').html(html);
    }

    // Filtreleme
    $(document).on('click', '.filter-btn', function() {
        $('.filter-btn').removeClass('active btn-primary').addClass('btn-light');
        $(this).addClass('active btn-primary').removeClass('btn-light');
        
        const status = $(this).data('status');
        if (status === 'all') {
            $('.advance-card').show();
        } else {
            $('.advance-card').hide();
            $(`.advance-card[data-status="${status}"]`).show();
        }
    });

    // Onaylama İşlemi
    $(document).on('click', '.btn-approve-action', function(e) {
        e.stopPropagation();
        const id = $(this).data('id');
        updateStatus(id, 1);
    });

    // Reddetme İşlemi
    $(document).on('click', '.btn-reject-action', function(e) {
        e.stopPropagation();
        const id = $(this).data('id');
        rejectAdvance(id);
    });

    function rejectAdvance(id) {
        const item = currentAdvancesList.find(i => i.id == id);

        $('#reject_advance_id').val(id);
        $('#reject_admin_note').val('Uygun Değildir.').removeClass('is-invalid');
        $('#reject_note_error').hide();

        if (item) {
            $('#modal_reject_person_name').text(item.full_name || 'Personel');
            $('#modal_reject_amount').text('₺ ' + parseFloat(item.tutar).toLocaleString('tr-TR', {minimumFractionDigits: 2}));
            $('#modal_reject_period').text(`${item.hedef_ay}/${item.hedef_yil}`);
            if (item.aciklama && item.aciklama.trim()) {
                $('#modal_reject_desc').text(item.aciklama);
                $('#modal_reject_desc_wrapper').show();
            } else {
                $('#modal_reject_desc_wrapper').hide();
            }
        }

        const modalEl = document.getElementById('rejectAdvanceModal');
        if (modalEl.parentNode !== document.body) {
            document.body.appendChild(modalEl);
        }
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    $(document).on('submit', '#rejectAdvanceForm', function(e) {
        e.preventDefault();
        const id = $('#reject_advance_id').val();
        const note = $('#reject_admin_note').val().trim();
        
        if (!note) {
            $('#reject_admin_note').addClass('is-invalid');
            $('#reject_note_error').show();
            return;
        }

        const modalEl = document.getElementById('rejectAdvanceModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();

        $.post('api/advances.php', {
            action: 'update_status',
            id: id,
            status: 2,
            admin_note: note
        }, function(response) {
            if (response.status === 'success') {
                Swal.fire({
                    title: 'Reddedildi',
                    text: response.message,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false,
                    background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                    color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
                });
                loadAdvances();
            } else {
                Swal.fire('Hata!', response.message, 'error');
            }
        }, 'json');
    });

    function updateStatus(id, status) {
        const actionText = 'onaylamak';
        const confirmButtonText = 'Evet, Onayla';
        const cancelButtonText = 'Vazgeç';
        const confirmButtonColor = '#10b981';

        Swal.fire({
            title: 'Emin misiniz?',
            text: `Bu avans talebini ${actionText} istediğinize emin misiniz?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: confirmButtonText,
            cancelButtonText: cancelButtonText,
            confirmButtonColor: confirmButtonColor,
            reverseButtons: true,
            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b',
            borderRadius: '16px'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('api/advances.php', {
                    action: 'update_status',
                    id: id,
                    status: status
                }, function(response) {
                    if (response.status === 'success') {
                        Swal.fire({
                            title: 'Başarılı!',
                            text: response.message,
                            icon: 'success',
                            timer: 1500,
                            showConfirmButton: false,
                            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
                            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
                        });
                        loadAdvances();
                    } else {
                        Swal.fire('Hata!', response.message, 'error');
                    }
                }, 'json');
            }
        });
    }
});
</script>

<!-- Avans Reddetme Modalı (Dialog) -->
<div class="modal modal-blur fade" id="rejectAdvanceModal" tabindex="-1" role="dialog" aria-hidden="true" style="z-index: 1060 !important;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
            <div class="modal-header border-0 pb-0 pt-4 px-4 text-center d-block position-relative">
                <button type="button" class="btn-close position-absolute" data-bs-dismiss="modal" aria-label="Kapat" style="top: 16px; right: 16px;"></button>
                <h4 class="modal-title text-bold text-dark mb-1" style="font-size: 1.25rem;">Avans Talebini Reddet</h4>
                <p class="text-muted text-xs mb-0">Lütfen red gerekçesini giriniz:</p>
            </div>
            <form id="rejectAdvanceForm">
                <input type="hidden" id="reject_advance_id" name="id" value="">
                <div class="modal-body py-3 px-4">
                    <!-- Avans Talebi Özet Kartı -->
                    <div class="card border-0 bg-light rounded-3 p-3 mb-3 text-start" style="background: rgba(241, 245, 249, 0.7) !important; border-radius: 14px !important;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-bold text-dark" style="font-size: 0.95rem;" id="modal_reject_person_name">Personel Adı</span>
                            <span class="badge bg-danger-lt text-danger fw-bold" style="font-size: 0.65rem;">REDDEDİLECEK</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline mb-1">
                            <span class="text-muted text-xs">Talep Edilen Tutar:</span>
                            <span class="text-bold text-primary" style="font-size: 1.1rem;" id="modal_reject_amount">₺ 0,00</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <span class="text-muted text-xs">Hedef Dönem:</span>
                            <span class="text-semibold text-sm text-dark" id="modal_reject_period">1/2026</span>
                        </div>
                        <div id="modal_reject_desc_wrapper" class="bg-white p-2 rounded-2 border text-secondary text-xs" style="display: none;">
                            <i class="ti ti-quote text-muted me-1"></i><strong class="text-muted">Açıklama:</strong> <span id="modal_reject_desc"></span>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-xs text-muted text-uppercase tracking-wider font-weight-bold">Red Gerekçesi <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_admin_note" name="admin_note" rows="3" required placeholder="Red gerekçesi..." style="border-radius: 12px; font-size: 0.95rem; resize: none; border: 1.5px solid #cbd5e1; padding: 12px;">Uygun Değildir.</textarea>
                        <div class="text-danger text-xs mt-1" id="reject_note_error" style="display: none;">Açıklama yazılması zorunludur!</div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <div class="d-flex align-items-center gap-2 w-100">
                        <button type="button" class="btn btn-light text-secondary w-50 py-2.5 rounded-3 fw-semibold" data-bs-dismiss="modal" style="border-radius: 12px !important; margin: 0; font-size: 0.9rem;">Vazgeç</button>
                        <button type="submit" class="btn btn-danger w-50 py-2.5 rounded-3 fw-semibold" style="border-radius: 12px !important; background: #ef4444; border: none; margin: 0; font-size: 0.9rem;">Reddet</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
