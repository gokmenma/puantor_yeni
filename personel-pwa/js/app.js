window.app = {
    user: null,
    currentMonth: new Date().getMonth() + 1,
    currentYear: new Date().getFullYear(),
    modal: null,
    
    toast(message, type = 'info') {
        const colors = {
            success: "linear-gradient(to right, #00b09b, #96c93d)",
            error: "linear-gradient(to right, #ff5f6d, #ffc371)",
            info: "linear-gradient(to right, #2193b0, #6dd5ed)",
            warning: "linear-gradient(to right, #f2994a, #f2c94c)"
        };
        
        Toastify({
            text: message,
            duration: 3000,
            close: true,
            gravity: "top",
            position: "center",
            stopOnFocus: true,
            style: {
                background: "#206bc4", // Tabler Primary
                borderRadius: "8px",
                boxShadow: "0 10px 15px -3px rgba(0, 0, 0, 0.1)",
                margin: "1.5rem auto",
                padding: "10px 24px",
                textAlign: "center",
                fontSize: "0.875rem",
                fontWeight: "600",
                color: "#ffffff",
                width: "max-content",
                maxWidth: "85%",
                left: "0",
                right: "0"
            }
        }).showToast();
    },

    init() {
        // Modal will be initialized on demand in showModal if not ready
        this.initModal();

        // If user is not provided by PHP, check localStorage (fallback)
        if (!this.user) {
            this.user = JSON.parse(localStorage.getItem('puantor_user'));
        } else {
            // Keep localStorage in sync for other features if any
            localStorage.setItem('puantor_user', JSON.stringify(this.user));
        }

        if (this.user) {
            // No need to call showMainApp here as PHP handles shell rendering
            // But we might need to update UI
            this.updateProfileUI();
        } else {
            // If on index.php without user, redirect to login.php
            if (window.location.pathname.endsWith('index.php') || window.location.pathname.endsWith('/')) {
                window.location.href = 'login.php';
            }
        }

        this.bindEvents();
        this.initTheme();
    },

    initModal() {
        if (this.modal) return true;
        const modalEl = document.getElementById('app-modal');
        if (modalEl) {
            try {
                // Try bootstrap global first, then fallback to tabler's internal if accessible
                const bootstrapObj = window.bootstrap || (window.tabler ? window.tabler.bootstrap : null);
                if (bootstrapObj) {
                    this.modal = new bootstrapObj.Modal(modalEl);
                    return true;
                }
            } catch (e) {
                console.error('Modal init error:', e);
            }
        }
        return false;
    },

    bindEvents() {
        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }

        // New advance button
        const btnNewAdvance = document.getElementById('btn-new-advance');
        if (btnNewAdvance) {
            btnNewAdvance.addEventListener('click', () => {
                this.showNewAdvanceModal();
            });
        }

        // Password toggle
        const toggleBtn = document.getElementById('togglePasswordBtn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', function() {
                const passwordInput = document.getElementById('password');
                const icon = document.getElementById('togglePasswordIcon');
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('ti-eye');
                    icon.classList.add('ti-eye-off');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('ti-eye-off');
                    icon.classList.add('ti-eye');
                }
            });
        }
    },

    initTheme() {
        const theme = localStorage.getItem('puantor_theme') || 'light';
        document.body.setAttribute('data-bs-theme', theme);
        this.updateThemeIcon(theme);
    },

    toggleTheme() {
        const currentTheme = document.body.getAttribute('data-bs-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        document.body.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('puantor_theme', newTheme);
        this.updateThemeIcon(newTheme);
    },

    updateThemeIcon(theme) {
        const icon = document.getElementById('theme-icon');
        if (icon) {
            icon.className = theme === 'dark' ? 'ti ti-sun fs-2 text-warning' : 'ti ti-moon fs-2';
        }
    },

    async handleLogin() {
        const kimlikNo = document.getElementById('kimlik_no').value;
        const password = document.getElementById('password').value;

        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                body: new URLSearchParams({
                    action: 'login',
                    kimlik_no: kimlikNo,
                    password: password
                })
            });
            const result = await response.json();
            console.log('Login Result:', result);

            if (result.status === 'success') {
                this.user = result.user;
                localStorage.setItem('puantor_user', JSON.stringify(this.user));
                this.toast('Giriş başarılı, yönlendiriliyorsunuz...', 'success');
                setTimeout(() => this.showMainApp(), 1000);
            } else {
                this.toast('Kullanıcı adı veya şifre hatalı.', 'error');
            }
        } catch (error) {
            console.error('Login error:', error);
            this.toast('Kullanıcı adı veya şifre hatalı.', 'error');
        }
    },

    logout() {
        Swal.fire({
            title: 'Çıkış Yap',
            text: 'Oturumu kapatmak istediğinize emin misiniz?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Evet, Çıkış Yap',
            cancelButtonText: 'İptal'
        }).then(async (result) => {
            if (result.isConfirmed) {
                try {
                    await fetch('api/auth.php', {
                        method: 'POST',
                        body: new URLSearchParams({ action: 'logout' })
                    });
                } catch (e) {}
                
                localStorage.removeItem('puantor_user');
                this.user = null;
                window.location.href = 'login.php';
            }
        });
    },

    showLoginPage() {
        const loginPage = document.getElementById('login-page');
        const mainContent = document.getElementById('main-content');
        if (loginPage) loginPage.style.display = 'flex';
        if (mainContent) mainContent.style.display = 'none';
    },

    showMainApp() {
        const loginPage = document.getElementById('login-page');
        const mainContent = document.getElementById('main-content');
        
        if (loginPage) loginPage.style.display = 'none';
        if (mainContent) mainContent.style.display = 'flex'; // Use flex to match app-shell class

        try {
            this.updateProfileUI();
            this.loadSummary();
            this.loadAdvances();
            this.switchTab('dashboard-tab');
        } catch (e) {
            console.error('Error switching to main app:', e);
        }
    },

    switchTab(tabId) {
        // This is only for SPA mode or post-login initial state.
        // In modular mode, PHP handles the 'active' classes.
        // But we keep it for FAB visibility if needed on current page.
        const btnNewAdvance = document.getElementById('btn-new-advance');
        if (btnNewAdvance) {
            btnNewAdvance.style.display = (tabId === 'advance-tab' || window.location.search.includes('route=advance')) ? 'flex' : 'none';
        }

        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });

        // Load data if on correct route
        if (tabId === 'advance-tab' || window.location.search.includes('route=advance')) this.loadAdvances();
        if (tabId === 'attendance-tab' || window.location.search.includes('route=attendance')) this.loadAttendance();
    },

    updateProfileUI() {
        if (this.user && this.user.full_name) {
            try {
                const nameParts = this.user.full_name.trim().split(' ');
                const initials = nameParts.length > 0 ? nameParts[0][0] + (nameParts[1] ? nameParts[1][0] : '') : '??';
                
                const setSafeText = (id, text) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = text || '-';
                };

                setSafeText('user-display-name', this.user.full_name);
                setSafeText('header-avatar-initials', initials);
                setSafeText('dashboard-user-avatar', initials);
                setSafeText('profile-initials-large', initials);
                setSafeText('profile-name', this.user.full_name);
                setSafeText('profile-id', `ID: EMP-${(this.user.id || 0).toString().padStart(3, '0')}`);
                setSafeText('profile-job', this.user.job || 'Personel');
                setSafeText('profile-phone', this.user.phone || '-');
                setSafeText('profile-email', this.user.email || '-');
                setSafeText('profile-iban', this.user.iban_number || '-');
            } catch (e) {
                console.error('Error updating Profile UI:', e);
            }
        }
    },

    async loadSummary() {
        try {
            const response = await fetch(`api/summary.php?person_id=${this.user.id}`);
            const result = await response.json();
            if (result.status === 'success') {
                const setSafeText = (id, text) => {
                    const el = document.getElementById(id);
                    if (el) el.textContent = text;
                };

                setSafeText('total-hours', result.summary.total_hours || 0);
                setSafeText('dashboard-overtime', `${result.summary.overtime || 0} s`);
                setSafeText('dashboard-advance', `${result.summary.advance || 0} TL`);
                setSafeText('dashboard-leave-days', `${result.summary.kalan_izin || 0} G`);
                setSafeText('available-advance-limit', result.summary.balance || 0);

                const recentContainer = document.getElementById('recent-activity-list');
                if (recentContainer) {
                    recentContainer.innerHTML = result.recent.map(item => `
                        <div class="mobile-card d-flex align-items-center justify-content-between p-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-sm rounded bg-primary-lt text-primary">
                                    <i class="ti ti-calendar"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0 fs-4 fw-bold">${item.puantaj_turu || item.turu}</h4>
                                    <p class="text-muted small mb-0">${item.gun}</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <h4 class="mb-0 text-primary fw-bold">${item.saat} s</h4>
                                <p class="text-muted small mb-0">SÜRE</p>
                            </div>
                        </div>
                    `).join('') || '<div class="text-center py-4 text-muted small">Henüz aktivite bulunmuyor.</div>';
                }
            }
        } catch (error) {
            console.error('Load summary error:', error);
        }
    },

    async loadAdvances() {
        try {
            const response = await fetch(`api/advance.php?action=list&person_id=${this.user.id}`);
            const result = await response.json();
            if (result.status === 'success') {
                const countBadge = document.getElementById('advance-count-badge');
                if (countBadge) countBadge.textContent = result.list.length;
                
                const container = document.getElementById('advance-list');
                container.innerHTML = result.list.map(item => {
                    let statusClass = 'bg-secondary-lt';
                    let statusText = 'Bekleyen';
                    let icon = 'ti-clock';

                    if (item.durum == 1) {
                        statusClass = 'bg-success-lt';
                        statusText = 'Onaylandı';
                        icon = 'ti-check';
                    } else if (item.durum == 2) {
                        statusClass = 'bg-danger-lt';
                        statusText = 'Reddedildi';
                        icon = 'ti-x';
                    }

                    const periodText = item.hedef_ay ? `${item.hedef_ay}/${item.hedef_yil} Dönemi` : '';

                    return `
                        <div class="swipe-item" data-id="${item.id}">
                            <div class="swipe-actions">
                                <button onclick="event.stopPropagation(); app.deleteAdvance('${item.id}')" class="btn-swipe-delete">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                            <div onclick="app.editAdvance('${item.id}', '${item.tutar}', '${(item.aciklama || '').replace(/'/g, "\\'")}', ${item.durum}, '${item.hedef_ay}', '${item.hedef_yil}')" 
                                 class="swipe-content mobile-card d-flex flex-column gap-2 cursor-pointer">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar avatar-sm rounded bg-primary-lt text-primary">
                                            <i class="ti ti-cash"></i>
                                        </div>
                                        <div>
                                            <h4 class="mb-0 fw-bold">${parseFloat(item.tutar).toFixed(2)} TL</h4>
                                            <p class="text-muted extra-small mb-0">${item.created_at} ${periodText ? '• ' + periodText : ''}</p>
                                        </div>
                                    </div>
                                    <span class="badge ${statusClass} rounded-pill">
                                        <i class="ti ${icon} me-1"></i> ${statusText}
                                    </span>
                                </div>
                                <p class="text-muted small mb-0 text-truncate">${item.aciklama || 'Açıklama belirtilmemiş'}</p>
                            </div>
                        </div>
                    `;
                }).join('') || '<div class="text-center py-4 text-muted small">Henüz avans talebiniz yok.</div>';

                this.initSwipeToDelete();
            }
        } catch (error) {
            console.error('Load advances error:', error);
        }
    },

    initSwipeToDelete() {
        const items = document.querySelectorAll('.swipe-item');
        items.forEach(item => {
            const content = item.querySelector('.swipe-content');
            let startX = 0;
            let currentX = 0;
            let isSwiping = false;

            content.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
                isSwiping = true;
                content.style.transition = 'none';
            });

            content.addEventListener('touchmove', (e) => {
                if (!isSwiping) return;
                currentX = e.touches[0].clientX;
                let diff = startX - currentX;
                
                // Sensitivity threshold: only start moving if swiped more than 20px
                if (Math.abs(diff) < 20) return;
                
                if (diff > 0) { // Swipe left
                    let moveX = diff - 20; // Subtract threshold for smoother start
                    if (moveX > 80) moveX = 80 + (moveX - 80) * 0.2; // Resistance
                    content.style.transform = `translateX(-${moveX}px)`;
                }
            });

            content.addEventListener('touchend', (e) => {
                isSwiping = false;
                content.style.transition = 'transform 0.2s ease-out';
                let diff = startX - currentX;
                if (diff > 50) { // Increased threshold for "open" state
                    content.style.transform = 'translateX(-80px)';
                } else {
                    content.style.transform = 'translateX(0)';
                }
            });

            // Close other items on touch
            document.addEventListener('touchstart', (e) => {
                if (!item.contains(e.target)) {
                    content.style.transform = 'translateX(0)';
                }
            });
        });
    },

    async deleteAdvance(id) {
        const result = await Swal.fire({
            title: 'Silmek istediğinize emin misiniz?',
            text: "Bu işlem geri alınamaz!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Evet, sil!',
            cancelButtonText: 'İptal'
        });

        if (result.isConfirmed) {
            try {
                const response = await fetch('api/advance.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        action: 'delete',
                        id: id,
                        person_id: this.user.id
                    })
                });
                const res = await response.json();
                if (res.status === 'success') {
                    this.toast(res.message, 'success');
                    this.loadAdvances();
                } else {
                    this.toast(res.message, 'error');
                }
            } catch (error) {
                this.toast('Bir hata oluştu.', 'error');
            }
        }
    },

    showNewAdvanceModal() {
        const now = new Date();
        let lastMonth = now.getMonth();
        let lastYear = now.getFullYear();
        if (lastMonth === 0) {
            lastMonth = 12;
            lastYear--;
        }
        
        const monthNames = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
        const monthOptions = monthNames.map((name, i) => `
            <option value="${i+1}" ${i+1 === lastMonth ? 'selected' : ''}>${name}</option>
        `).join('');

        this.showModal('Yeni Avans Talebi', `
            <form id="advance-form">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">HEDEF MAAŞ DÖNEMİ</label>
                    <div class="row g-2">
                        <div class="col-7">
                            <select name="hedef_ay" class="form-select form-select-lg" style="height: 58px;">${monthOptions}</select>
                        </div>
                        <div class="col-5">
                            <select name="hedef_yil" class="form-select form-select-lg" style="height: 58px;">
                                <option value="${lastYear}" selected>${lastYear}</option>
                                <option value="${lastYear+1}">${lastYear+1}</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-floating mb-3">
                    <input type="number" name="tutar" class="form-control" id="adv-tutar" placeholder="0.00" required>
                    <label for="adv-tutar">Talep Edilen Tutar (TL)</label>
                </div>
                <div class="form-floating mb-4">
                    <textarea name="aciklama" class="form-control" id="adv-desc" placeholder="Açıklama" style="height: 100px"></textarea>
                    <label for="adv-desc">Talebinizle ilgili kısa bilgi...</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Talebi Gönder</button>
            </form>
        `);

        document.getElementById('advance-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'create');
            formData.append('person_id', this.user.id);
            formData.append('firm_id', this.user.firm_id);

            try {
                const response = await fetch('api/advance.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });
                const res = await response.json();
                if (res.status === 'success') {
                    this.hideModal();
                    this.loadAdvances();
                    Swal.fire('Başarılı', 'Avans talebiniz başarıyla oluşturuldu.', 'success');
                } else {
                    Swal.fire('Hata', res.message || 'Bir hata oluştu.', 'error');
                }
            } catch (error) {
                Swal.fire('Hata', 'Talep gönderilirken hata oluştu.', 'error');
            }
        });
    },

    editAdvance(id, tutar, aciklama, durum, ay, yil) {
        if (durum !== 0) return;

        const monthNames = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
        const monthOptions = monthNames.map((name, i) => `
            <option value="${i+1}" ${parseInt(ay) === i+1 ? 'selected' : ''}>${name}</option>
        `).join('');

        const currentYear = new Date().getFullYear();
        const yearOptions = `
            <option value="${currentYear-1}" ${parseInt(yil) === currentYear-1 ? 'selected' : ''}>${currentYear-1}</option>
            <option value="${currentYear}" ${parseInt(yil) === currentYear ? 'selected' : ''}>${currentYear}</option>
            <option value="${currentYear+1}" ${parseInt(yil) === currentYear+1 ? 'selected' : ''}>${currentYear+1}</option>
        `;

        this.showModal('Talebi Güncelle', `
            <form id="edit-advance-form">
                <input type="hidden" name="id" value="${id}">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">HEDEF MAAŞ DÖNEMİ</label>
                    <div class="row g-2">
                        <div class="col-7">
                            <select name="hedef_ay" class="form-select form-select-lg" style="height: 58px;">${monthOptions}</select>
                        </div>
                        <div class="col-5">
                            <select name="hedef_yil" class="form-select form-select-lg" style="height: 58px;">
                                ${yearOptions}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-floating mb-3">
                    <input name="tutar" type="number" step="0.01" class="form-control" id="edit-adv-tutar" value="${tutar}" placeholder="0.00" required/>
                    <label for="edit-adv-tutar">Talep Edilen Tutar (TL)</label>
                </div>
                <div class="form-floating mb-4">
                    <textarea name="aciklama" class="form-control" id="edit-adv-desc" placeholder="Açıklama" style="height: 100px" required>${aciklama === 'Açıklama belirtilmemiş' ? '' : aciklama}</textarea>
                    <label for="edit-adv-desc">Talebinizle ilgili kısa bilgi...</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Güncelle ve Kaydet</button>
            </form>
        `);

        document.getElementById('edit-advance-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update');
            formData.append('person_id', this.user.id);

            try {
                const response = await fetch('api/advance.php', {
                    method: 'POST',
                    body: new URLSearchParams(formData)
                });
                const res = await response.json();
                if (res.status === 'success') {
                    this.hideModal();
                    this.loadAdvances();
                    Swal.fire('Başarılı', 'Talebiniz başarıyla güncellendi.', 'success');
                } else {
                    Swal.fire('Hata', res.message || 'Bir hata oluştu.', 'error');
                }
            } catch (error) {
                Swal.fire('Hata', 'Hata oluştu.', 'error');
            }
        });
    },

    async loadAttendance() {
        const m = this.currentMonth;
        const y = this.currentYear;
        const monthNames = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
        
        const label = document.getElementById('current-month-label');
        if (label) label.textContent = `${monthNames[m-1]} ${y}`;

        // Show preloader
        const grid = document.getElementById('calendar-grid');
        if (grid) {
            grid.innerHTML = '<div class="calendar-skeleton">' + Array(35).fill('<div class="calendar-skeleton-item shimmer rounded-circle"></div>').join('') + '</div>';
        }

        try {
            const response = await fetch(`api/summary.php?person_id=${this.user.id}&month=${m}&year=${y}`);
            const result = await response.json();
            if (result.status === 'success') {
                this.renderCalendar(m, y, result.monthly);
            }
        } catch (error) {
            console.error('Load attendance error:', error);
        }
    },

    changeMonth(delta) {
        this.currentMonth += delta;
        if (this.currentMonth > 12) {
            this.currentMonth = 1;
            this.currentYear++;
        } else if (this.currentMonth < 1) {
            this.currentMonth = 12;
            this.currentYear--;
        }
        this.loadAttendance();
    },

    renderCalendar(month, year, data) {
        const grid = document.getElementById('calendar-grid');
        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();
        let startingDay = firstDay === 0 ? 6 : firstDay - 1;
        
        let html = `
            <div class="small fw-bold text-muted">Pt</div><div class="small fw-bold text-muted">Sa</div><div class="small fw-bold text-muted">Ça</div><div class="small fw-bold text-muted">Pe</div><div class="small fw-bold text-muted">Cu</div><div class="small fw-bold text-muted">Ct</div><div class="small fw-bold text-muted text-danger">Pa</div>
        `;
        
        for (let i = 0; i < startingDay; i++) html += `<div></div>`;
        
        let totalWorkDays = 0, totalHolidays = 0, totalWorkHours = 0;

        for (let i = 1; i <= daysInMonth; i++) {
            const dayStr = `${year}${month.toString().padStart(2, '0')}${i.toString().padStart(2, '0')}`;
            const record = data.find(r => r.gun === dayStr || r.gun === `${year}-${month.toString().padStart(2, '0')}-${i.toString().padStart(2, '0')}`);
            const isToday = new Date().toDateString() === new Date(year, month-1, i).toDateString();
            const isSunday = new Date(year, month-1, i).getDay() === 0;
            
            // Logic: Record data takes precedence over Sunday
            let isHoliday = isSunday;
            let isWork = false;
            let customStyle = "";

            if (record) {
                // If it's a known work type, it's work
                if (record.attendance_type === 'Normal Çalışma' || record.attendance_type === 'Saatlik') {
                    isWork = true;
                    isHoliday = false;
                } else if (record.attendance_type === 'Ücretsiz' || record.attendance_type === 'Ücretli İzin') {
                    isHoliday = true;
                    isWork = false;
                }
                
                if (record.ArkaPlanRengi) {
                    customStyle = `style="background-color: ${record.ArkaPlanRengi}; color: ${record.FontRengi || '#fff'};"`;
                }
            }

            if (isHoliday) totalHolidays++;
            else if (isWork || (record && parseFloat(record.saat) > 0)) { 
                totalWorkDays++; 
                totalWorkHours += parseFloat(record.saat || 0); 
            }

            let cls = "calendar-day";
            if (isSunday && !isWork) cls += " weekend";
            if (isWork || (record && parseFloat(record.saat) > 0)) cls += " active";
            if (isToday) cls += " today";
            
            html += `<div class="${cls}" ${customStyle} onclick="app.showDayDetails('${dayStr}', ${JSON.stringify(record || {}).replace(/"/g, '&quot;')})">${i}</div>`;
        }
        grid.innerHTML = html;

        document.getElementById('summary-work-days').textContent = `${totalWorkDays} Gün`;
        document.getElementById('summary-holidays').textContent = `${totalHolidays} Gün`;
        document.getElementById('summary-total-hours').textContent = `${totalWorkHours.toFixed(1).replace('.0', '')} s`;

        const today = new Date();
        let defaultDay = (today.getMonth() + 1 === month && today.getFullYear() === year) ? today.getDate() : 1;
        const defaultDayStr = `${year}${month.toString().padStart(2, '0')}${defaultDay.toString().padStart(2, '0')}`;
        this.showDayDetails(defaultDayStr, data.find(r => r.gun === defaultDayStr));
    },

    showDayDetails(dayStr, record) {
        const date = new Date(dayStr.substring(0, 4), dayStr.substring(4, 6) - 1, dayStr.substring(6, 8));
        document.getElementById('selected-day-label').textContent = date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', weekday: 'long' });
        
        const statusEl = document.getElementById('day-status');
        const durationEl = document.getElementById('day-duration-new');
        const iconEl = document.getElementById('day-icon');
        const iconBgEl = document.getElementById('day-icon-bg');
        
        const isSunday = date.getDay() === 0;
        const isWork = record && (record.attendance_type === 'Normal Çalışma' || record.attendance_type === 'Saatlik');

        if (record && record.PuantajAdi) {
            statusEl.textContent = record.PuantajAdi;
            durationEl.textContent = `${parseFloat(record.saat || 0).toFixed(1).replace('.0', '')} s`;
            
            if (record.PuantajKod) {
                iconEl.style.display = 'none';
                let codeEl = iconBgEl.querySelector('.day-code');
                if (!codeEl) {
                    codeEl = document.createElement('span');
                    codeEl.className = 'day-code fw-bold fs-3';
                    iconBgEl.prepend(codeEl);
                }
                codeEl.textContent = record.PuantajKod;
                codeEl.style.display = 'block';
            } else {
                iconEl.style.display = 'block';
                const codeEl = iconBgEl.querySelector('.day-code');
                if (codeEl) codeEl.style.display = 'none';
            }

            if (isWork) {
                iconEl.className = 'ti ti-briefcase';
                iconBgEl.className = 'avatar avatar-md rounded bg-primary-lt text-primary';
            } else {
                iconEl.className = 'ti ti-calendar-event';
                iconBgEl.className = 'avatar avatar-md rounded bg-info-lt text-info';
            }

            // Apply custom color if available
            if (record.ArkaPlanRengi) {
                iconBgEl.style.backgroundColor = record.ArkaPlanRengi;
                iconBgEl.style.color = record.FontRengi || '#fff';
            } else {
                iconBgEl.style.backgroundColor = '';
                iconBgEl.style.color = '';
            }
        } else {
            iconEl.style.display = 'block';
            const codeEl = iconBgEl.querySelector('.day-code');
            if (codeEl) codeEl.style.display = 'none';

            if (isSunday) {
                statusEl.textContent = 'Hafta Tatili';
                durationEl.textContent = '0 s';
                iconEl.className = 'ti ti-sun';
                iconBgEl.className = 'avatar avatar-md rounded bg-danger-lt text-danger';
            } else {
                statusEl.textContent = 'Kayıt Bulunmuyor';
                durationEl.textContent = '0 s';
                iconEl.className = 'ti ti-calendar-off';
                iconBgEl.className = 'avatar avatar-md rounded bg-secondary-lt text-secondary';
            }
            iconBgEl.style.backgroundColor = '';
            iconBgEl.style.color = '';
        }
    },

    showEditProfile() {
        this.showModal('Bilgileri Güncelle', `
            <form id="profile-form">
                <div class="form-floating mb-3">
                    <input type="text" name="phone" id="prof-phone" value="${this.user.phone || ''}" class="form-control" placeholder="Telefon" required>
                    <label for="prof-phone">Telefon</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="email" name="email" id="prof-email" value="${this.user.email || ''}" class="form-control" placeholder="E-Posta">
                    <label for="prof-email">E-Posta</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="text" name="iban_number" id="prof-iban" value="${this.user.iban_number || ''}" class="form-control" placeholder="IBAN">
                    <label for="prof-iban">IBAN</label>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Bilgileri Güncelle</button>
            </form>
        `);

        document.getElementById('profile-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update');
            formData.append('person_id', this.user.id);

            try {
                const response = await fetch('api/profile.php', { method: 'POST', body: new URLSearchParams(formData) });
                const res = await response.json();
                if (res.status === 'success') {
                    this.user = { ...this.user, phone: formData.get('phone'), email: formData.get('email'), iban_number: formData.get('iban_number') };
                    localStorage.setItem('puantor_user', JSON.stringify(this.user));
                    this.updateProfileUI();
                    this.hideModal();
                    Swal.fire('Başarılı', 'Profil güncellendi.', 'success');
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            } catch (error) {
                Swal.fire('Hata', 'Hata oluştu.', 'error');
            }
        });
    },

    showChangePassword() {
        this.showModal('Şifre Değiştir', `
            <form id="password-form">
                <div class="form-floating mb-3">
                    <input type="password" name="current_password" id="pw-current" class="form-control" placeholder="Mevcut Şifre" required>
                    <label for="pw-current">Mevcut Şifre</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" name="new_password" id="pw-new" class="form-control" placeholder="Yeni Şifre" required>
                    <label for="pw-new">Yeni Şifre</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" name="confirm_password" id="pw-conf" class="form-control" placeholder="Yeni Şifre (Tekrar)" required>
                    <label for="pw-conf">Yeni Şifre (Tekrar)</label>
                </div>
                <button type="submit" class="btn btn-danger w-100 py-3 fw-bold">Şifreyi Güncelle</button>
            </form>
        `);

        document.getElementById('password-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            if (formData.get('new_password') !== formData.get('confirm_password')) {
                Swal.fire('Hata', 'Yeni şifreler uyuşmuyor.', 'error');
                return;
            }
            formData.append('action', 'change_password');
            formData.append('person_id', this.user.id);

            try {
                const response = await fetch('api/profile.php', { method: 'POST', body: new URLSearchParams(formData) });
                const res = await response.json();
                if (res.status === 'success') {
                    this.hideModal();
                    Swal.fire('Başarılı', 'Şifre değiştirildi.', 'success');
                } else {
                    Swal.fire('Hata', res.message, 'error');
                }
            } catch (error) {
                Swal.fire('Hata', 'Hata oluştu.', 'error');
            }
        });
    },

    showModal(title, bodyHtml) {
        try {
            const titleEl = document.getElementById('app-modal-title');
            const bodyEl = document.getElementById('app-modal-body');
            
            if (titleEl) titleEl.textContent = title;
            if (bodyEl) bodyEl.innerHTML = bodyHtml;
            
            if (this.initModal()) {
                this.modal.show();
            } else {
                console.error('Bootstrap Modal could not be initialized.');
                // Fallback to simple alert if bootstrap fails
                Swal.fire({
                    title: title,
                    html: bodyHtml,
                    showConfirmButton: false,
                    showCloseButton: true,
                    customClass: {
                        popup: 'swal2-popup-custom'
                    }
                });
            }
        } catch (e) {
            console.error('Error showing modal:', e);
        }
    },

    hideModal() {
        if (this.modal) this.modal.hide();
    }
};

document.addEventListener('DOMContentLoaded', () => window.app.init());
