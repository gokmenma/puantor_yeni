<style>
.leave-item-pending:active {
    background-color: rgba(0, 0, 0, 0.05) !important;
}
.leave-item-pending:hover {
    background-color: rgba(0, 0, 0, 0.02);
}
.cursor-pointer {
    cursor: pointer;
}
</style>
<style>
.transaction-item-wrapper {
    position: relative;
    overflow: hidden;
    background: #fff;
    border-bottom: 1px solid rgba(0,0,0,0.05);
    user-select: none;
}
.transaction-item-content {
    position: relative;
    background: #fff;
    z-index: 2;
    transition: background-color 0.15s ease;
}
body[data-bs-theme="dark"] .transaction-item-wrapper,
body[data-bs-theme="dark"] .transaction-item-content {
    background: #1e293b !important;
}
.leave-item-pending {
    transition: transform 0.2s, background-color 0.2s, box-shadow 0.2s !important;
}
.leave-item-pending:active .transaction-item-content {
    background-color: rgba(0, 0, 0, 0.03) !important;
}
body[data-bs-theme="dark"] .leave-item-pending:active .transaction-item-content {
    background-color: rgba(255, 255, 255, 0.03) !important;
}
.cursor-pointer {
    cursor: pointer;
}
</style>
<div id="leave-tab" class="tab-content active">
    <div class="page-header mb-4">
        <h2 class="h1 mb-0 fw-bold text-dark" style="letter-spacing: -1px;">Yıllık İzin</h2>
        <p class="text-muted small mb-0">İzin talepleriniz ve bakiyeniz.</p>
    </div>

    <!-- Özet kart -->
    <div id="btn-show-hakedis" class="mobile-card text-white p-4 mb-4 position-relative overflow-hidden" style="border: none; border-radius: 20px; background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%) !important; cursor: pointer; transition: transform 0.15s ease;" onclick="this.style.transform='scale(0.97)';" ontouchend="this.style.transform='none';">
        <div class="position-absolute" style="right: -10px; bottom: -20px; font-size: 8rem; opacity: 0.12; pointer-events: none;">
            <i class="ti ti-beach"></i>
        </div>
        <div class="d-flex align-items-center justify-content-between mb-2">
            <span class="text-white-50 text-xs text-uppercase tracking-wider font-weight-bold" style="font-size: 0.7rem;">KALAN YILLIK İZİN</span>
            <i class="ti ti-beach" style="font-size: 1.5rem; opacity: 0.8;"></i>
        </div>
        <div class="d-flex align-items-baseline gap-1 text-white">
            <h2 id="leave-kalan-gun" class="mb-0 text-bold" style="font-size: 2.2rem; letter-spacing: -1px;">—</h2>
            <span class="fs-4 opacity-75 font-weight-bold">gün</span>
        </div>
    </div>

    <!-- Liste başlığı -->
    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h3 class="h3 mb-0 fw-bold text-dark">Taleplerim</h3>
        <div class="btn-group btn-group-sm p-1 bg-light rounded-pill" id="leave-filter-group">
            <button class="btn btn-white border-0 rounded-pill px-3 py-1 shadow-none active extra-small fw-bold" data-filter="">Tümü</button>
            <button class="btn btn-light border-0 rounded-pill px-3 py-1 shadow-none extra-small fw-bold opacity-50" data-filter="beklemede">Beklemede</button>
            <button class="btn btn-light border-0 rounded-pill px-3 py-1 shadow-none extra-small fw-bold opacity-50" data-filter="onaylandi">Onaylı</button>
        </div>
    </div>

    <div class="list-group list-group-mobile mb-5" id="leave-list">
        <div class="p-4 text-center text-muted small">Yükleniyor...</div>
    </div>
</div>

<!-- FAB -->
<button id="btn-new-leave" class="mobile-fab" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
    <i class="ti ti-plus fs-1"></i>
</button>

<!-- Modal: Yeni İzin Talebi -->
<div class="modal modal-blur fade modal-bottom-sheet" id="modalYeniIzin" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 24px 24px 0 0;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Yeni İzin Talebi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2">
                <div class="mb-3">
                    <label class="form-label fw-semibold">İzin Türü</label>
                    <select id="leave-tur" class="form-select">
                        <option value="">Yükleniyor...</option>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label fw-semibold">Başlangıç</label>
                        <input type="date" id="leave-baslangic" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label fw-semibold">Bitiş</label>
                        <input type="date" id="leave-bitis" class="form-control">
                    </div>
                </div>
                <div class="mb-3 p-3 bg-light rounded-3 d-flex justify-content-between align-items-center">
                    <span class="text-muted small">İş günü sayısı:</span>
                    <strong id="leave-gun-preview" class="text-info">—</strong>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Açıklama <span class="text-muted fw-normal">(opsiyonel)</span></label>
                    <textarea id="leave-aciklama" class="form-control" rows="2" style="resize:none;"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light w-100 mb-2" data-bs-dismiss="modal">İptal</button>
                <button type="button" class="btn btn-info w-100 fw-bold" id="btn-leave-gonder">Talep Gönder</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: İzin Hakedişleri -->
<div class="modal modal-blur fade modal-bottom-sheet" id="modalHakedisler" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="border-radius: 24px 24px 0 0; max-height: 80vh; display: flex; flex-direction: column;">
            <div class="modal-header border-0 pb-0 flex-shrink-0">
                <h5 class="modal-title fw-bold">İzin Hakediş Detayları</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-2 overflow-y-auto flex-grow-1" style="max-height: calc(80vh - 120px);">
                <div class="list-group list-group-mobile" id="hakedis-list-container">
                    <div class="p-4 text-center text-muted small">Yükleniyor...</div>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 flex-shrink-0">
                <button type="button" class="btn btn-light w-100 mb-2" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    const LEAVE_API = 'api/leave.php';
    const personId  = <?= (int) ($_SESSION['personel_id'] ?? 0) ?>;
    const firmId    = <?= (int) ($_SESSION['firm_id'] ?? 0) ?>;

    let activeFilter = '';
    let allTalepler  = [];

    const durumCfg = {
        beklemede:  { badge: 'bg-warning text-white', label: 'Beklemede', icon: 'ti-clock' },
        onaylandi:  { badge: 'bg-info text-white',  label: 'Onaylı',    icon: 'ti-check' },
        reddedildi: { badge: 'bg-danger text-white',   label: 'Reddedildi',icon: 'ti-x' },
        iptal:      { badge: 'bg-secondary text-white',label: 'İptal',     icon: 'ti-ban' },
    };

    function fmtDate(d) {
        if (!d) return '—';
        const p = d.split(/[-T ]/);
        return p.length >= 3 ? `${p[2]}.${p[1]}.${p[0]}` : d;
    }

    let editingTalepId = null;

    function renderList() {
        const filtered = activeFilter ? allTalepler.filter(t => t.durum === activeFilter) : allTalepler;
        const el = document.getElementById('leave-list');
        if (!filtered.length) {
            el.innerHTML = '<div class="p-4 text-center text-muted small">Kayıt bulunamadı.</div>';
            return;
        }
        el.innerHTML = filtered.map(t => {
            const cfg = durumCfg[t.durum] || { badge: 'bg-secondary', label: t.durum, icon: 'ti-help' };
            
            let avatarBg = 'rgba(108, 117, 125, 0.15)';
            let avatarColor = '#6c757d';
            let avatarIcon = 'ti-help';
            
            if (t.durum === 'beklemede') {
                avatarBg = 'rgba(247, 185, 36, 0.15)';
                avatarColor = '#f7b924';
                avatarIcon = 'ti-clock';
            } else if (t.durum === 'onaylandi') {
                avatarBg = 'rgba(23, 162, 184, 0.15)';
                avatarColor = '#17a2b8';
                avatarIcon = 'ti-check';
            } else if (t.durum === 'reddedildi') {
                avatarBg = 'rgba(214, 63, 63, 0.15)';
                avatarColor = '#d63f3f';
                avatarIcon = 'ti-x';
            } else if (t.durum === 'iptal') {
                avatarBg = 'rgba(108, 117, 125, 0.15)';
                avatarColor = '#6c757d';
                avatarIcon = 'ti-ban';
            }

            const iptalBtn = t.durum === 'beklemede'
                ? `<button class="btn btn-icon btn-sm btn-outline-danger rounded-circle border-0" onclick="event.stopPropagation(); leaveIptal(${t.id})" title="İptal Et" style="width: 28px; height: 28px; padding:0; display: inline-flex; align-items: center; justify-content: center; background: rgba(220, 53, 69, 0.1);">
                    <i class="ti ti-trash text-danger" style="font-size: 16px;"></i>
                   </button>`
                : '';
            return `
                <div class="transaction-item-wrapper ${t.durum === 'beklemede' ? 'cursor-pointer leave-item-pending' : ''}" onclick="${t.durum === 'beklemede' ? `editLeave(${JSON.stringify(t).replace(/"/g, '&quot;')})` : ''}">
                    <div class="transaction-item-content d-flex align-items-center justify-content-between p-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="avatar avatar-sm rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: ${avatarBg}; color: ${avatarColor}; flex-shrink: 0;">
                                <i class="ti ${avatarIcon}" style="font-size: 1.25rem;"></i>
                            </div>
                            <div>
                                <div class="text-bold text-sm" style="color: var(--tblr-body-color, #1d273b);">${t.tur_adi}</div>
                                <div class="text-muted text-xs d-flex align-items-center gap-1 mt-0.5">
                                    <span>${fmtDate(t.baslangic_tarihi)} – ${fmtDate(t.bitis_tarihi)}</span>
                                    <span class="text-muted-50">•</span>
                                    <span class="text-bold" style="color: var(--tblr-body-color, #1d273b);">${t.gun_sayisi} gün</span>
                                </div>
                                ${t.aciklama ? `<div class="text-muted text-xs font-italic mt-1" style="font-size: 0.7rem; opacity: 0.85;">"${t.aciklama}"</div>` : ''}
                                ${t.yonetici_notu ? `<div class="text-danger text-xs font-italic mt-1" style="font-size: 0.7rem; opacity: 0.95;"><i class="ti ti-message-2 me-0.5"></i>Yönetici: "${t.yonetici_notu}"</div>` : ''}
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge ${cfg.badge} rounded-pill px-2.5 py-1 text-xs font-medium">${cfg.label}</span>
                            ${iptalBtn}
                        </div>
                    </div>
                </div>`;
        }).join('');
    }

    function loadLeave() {
        fetch(`${LEAVE_API}?action=list&person_id=${personId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') return;
                allTalepler = res.list;
                document.getElementById('leave-kalan-gun').textContent = res.kalan_gun ?? '—';
                renderList();
            });
    }

    function loadTurler(callback) {
        fetch(`${LEAVE_API}?action=turler`)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success') return;
                const sel = document.getElementById('leave-tur');
                sel.innerHTML = '<option value="">Seçiniz</option>' +
                    res.list.map(t => `<option value="${t.id}">${t.ad}</option>`).join('');
                if (callback) callback();
            });
    }

    window.editLeave = function(t) {
        editingTalepId = t.id;
        document.querySelector('#modalYeniIzin .modal-title').textContent = 'İzin Talebini Düzenle';
        document.getElementById('btn-leave-gonder').textContent = 'Güncelle';
        document.getElementById('leave-baslangic').value = t.baslangic_tarihi;
        document.getElementById('leave-bitis').value = t.bitis_tarihi;
        document.getElementById('leave-aciklama').value = t.aciklama || '';
        calcGun();
        loadTurler(function() {
            document.getElementById('leave-tur').value = t.tur_id;
        });
        new bootstrap.Modal('#modalYeniIzin').show();
    };

    let calcTimer = null;
    function calcGun() {
        const b = document.getElementById('leave-baslangic').value;
        const s = document.getElementById('leave-bitis').value;
        const el = document.getElementById('leave-gun-preview');
        if (!b || !s || s < b) { el.textContent = '—'; return; }
        clearTimeout(calcTimer);
        calcTimer = setTimeout(() => {
            fetch(`${LEAVE_API}?action=calc_gun&baslangic=${b}&bitis=${s}`)
                .then(r => r.json())
                .then(res => { el.textContent = res.status === 'success' ? res.gun_sayisi + ' gün' : '—'; });
        }, 400);
    }

    function showHakedisModal() {
        const modal = new bootstrap.Modal('#modalHakedisler');
        modal.show();
        
        const container = document.getElementById('hakedis-list-container');
        container.innerHTML = '<div class="p-4 text-center text-muted small"><div class="spinner-border spinner-border-sm text-primary me-2"></div>Yükleniyor...</div>';
        
        fetch(`${LEAVE_API}?action=hakedisler&person_id=${personId}`)
            .then(r => r.json())
            .then(res => {
                if (res.status !== 'success' || !res.list.length) {
                    container.innerHTML = '<div class="p-4 text-center text-muted small">Hakediş bulunamadı.</div>';
                    return;
                }
                
                container.innerHTML = res.list.map(h => {
                    const kalan = (parseInt(h.gun_sayisi) || 0) - (parseInt(h.kullanilan_gun) || 0);
                    const yildiz = h.yil < 100 ? `${h.yil}. Yıl` : h.yil;
                    return `
                        <div class="list-group-item px-3 py-2.5 border-bottom border-light">
                            <div class="d-flex justify-content-between align-items-start w-100">
                                <div>
                                    <div class="text-bold text-sm" style="color: var(--tblr-body-color, #1d273b);">${yildiz} Hakedişi</div>
                                    <div class="text-muted text-xs mt-0.5">
                                        <i class="ti ti-calendar me-0.5"></i> ${fmtDate(h.hakedis_tarihi)}
                                    </div>
                                    ${h.aciklama ? `<div class="text-muted extra-small mt-1 font-italic">"${h.aciklama}"</div>` : ''}
                                </div>
                                <div class="text-end" style="flex-shrink:0;">
                                    <div class="text-xs text-muted mb-0.5">Hak: <strong class="text-dark">${h.gun_sayisi}</strong> | Kul: <strong class="text-dark">${h.kullanilan_gun}</strong></div>
                                    <span class="badge ${kalan > 0 ? 'bg-info text-white' : 'bg-secondary text-white'} rounded-pill text-xs font-semibold px-2 py-0.5">Kalan: ${kalan} gün</span>
                                </div>
                            </div>
                        </div>`;
                }).join('');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        loadLeave();

        document.getElementById('btn-show-hakedis').addEventListener('click', showHakedisModal);

        document.getElementById('leave-baslangic').addEventListener('change', calcGun);
        document.getElementById('leave-bitis').addEventListener('change', calcGun);

        document.getElementById('btn-new-leave').addEventListener('click', function() {
            editingTalepId = null;
            document.querySelector('#modalYeniIzin .modal-title').textContent = 'Yeni İzin Talebi';
            document.getElementById('btn-leave-gonder').textContent = 'Talep Gönder';
            
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const yyyy = tomorrow.getFullYear();
            const mm = String(tomorrow.getMonth() + 1).padStart(2, '0');
            const dd = String(tomorrow.getDate()).padStart(2, '0');
            const tomorrowStr = `${yyyy}-${mm}-${dd}`;
            
            document.getElementById('leave-baslangic').value = tomorrowStr;
            document.getElementById('leave-bitis').value = tomorrowStr;
            document.getElementById('leave-aciklama').value = '';
            document.getElementById('leave-gun-preview').textContent = '—';
            
            loadTurler(function() {
                const select = document.getElementById('leave-tur');
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].text.toLowerCase().includes('yıllık izin')) {
                        select.selectedIndex = i;
                        break;
                    }
                }
            });
            
            calcGun();
            new bootstrap.Modal('#modalYeniIzin').show();
        });

        document.getElementById('btn-leave-gonder').addEventListener('click', function() {
            const body = new FormData();
            if (editingTalepId) {
                body.append('action', 'update');
                body.append('talep_id', editingTalepId);
            } else {
                body.append('action', 'create');
            }
            body.append('person_id', personId);
            body.append('firm_id', firmId);
            body.append('tur_id', document.getElementById('leave-tur').value);
            body.append('baslangic_tarihi', document.getElementById('leave-baslangic').value);
            body.append('bitis_tarihi', document.getElementById('leave-bitis').value);
            body.append('aciklama', document.getElementById('leave-aciklama').value);

            fetch(LEAVE_API, { method: 'POST', body })
                .then(r => r.json())
                .then(res => {
                    const isSuccess = res.success || res.status === 'success';
                    if (isSuccess) {
                        bootstrap.Modal.getInstance('#modalYeniIzin').hide();
                        loadLeave();
                        if (typeof toastr !== 'undefined') toastr.success(res.message);
                    } else {
                        if (typeof toastr !== 'undefined') toastr.error(res.message);
                        else alert(res.message);
                    }
                });
        });

        document.getElementById('leave-filter-group').addEventListener('click', function(e) {
            const btn = e.target.closest('[data-filter]');
            if (!btn) return;
            this.querySelectorAll('button').forEach(b => b.classList.replace('btn-white', 'btn-light') || b.classList.add('opacity-50'));
            btn.classList.replace('btn-light', 'btn-white');
            btn.classList.remove('opacity-50');
            activeFilter = btn.dataset.filter;
            renderList();
        });
    });

    window.leaveIptal = function(id) {
        if (!confirm('Bu talebi iptal etmek istiyor musunuz?')) return;
        const body = new FormData();
        body.append('action', 'cancel');
        body.append('talep_id', id);
        body.append('person_id', personId);
        fetch(LEAVE_API, { method: 'POST', body })
            .then(r => r.json())
            .then(res => {
                if (res.success) { loadLeave(); }
                if (typeof toastr !== 'undefined') toastr[res.success ? 'success' : 'error'](res.message);
            });
    };
})();
</script>
