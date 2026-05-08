window.app = {
    user: null,
    currentMonth: new Date().getMonth() + 1,
    currentYear: new Date().getFullYear(),

    init() {
        this.user = JSON.parse(localStorage.getItem('puantor_user'));
        if (this.user) {
            this.showMainApp();
        } else {
            this.showLoginPage();
        }

        this.bindEvents();
        lucide.createIcons();
    },
    bindEvents() {
        // Tab switching
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const tabId = item.getAttribute('data-tab');
                this.switchTab(tabId);
            });
        });

        // Login form
        const loginForm = document.getElementById('loginForm');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin();
            });
        }

        // Modal close
        const closeBtn = document.querySelector('.close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                this.hideModal();
            });
        }

        // New advance button
        document.getElementById('btn-new-advance').addEventListener('click', () => {
            const now = new Date();
            let lastMonth = now.getMonth(); // 0-indexed, so current month is now.getMonth()+1
            let lastYear = now.getFullYear();
            if (lastMonth === 0) {
                lastMonth = 12;
                lastYear--;
            }
            
            const monthOptions = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"].map((name, i) => `
                <option value="${i+1}" ${i+1 === lastMonth ? 'selected' : ''}>${name}</option>
            `).join('');

            this.showModal('Yeni Avans Talebi', `
                <form id="advance-form" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">HEDEF MAAŞ DÖNEMİ</label>
                        <div class="flex gap-2">
                            <select name="hedef_ay" class="flex-1 h-12 rounded-xl border-surface-variant bg-surface-container-low">
                                ${monthOptions}
                            </select>
                            <select name="hedef_yil" class="w-32 h-12 rounded-xl border-surface-variant bg-surface-container-low">
                                <option value="${lastYear}" selected>${lastYear}</option>
                                <option value="${lastYear+1}">${lastYear+1}</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">TALEP EDİLEN TUTAR (TL)</label>
                        <input type="number" name="tutar" class="w-full h-12 rounded-xl border-surface-variant bg-surface-container-low" placeholder="0.00" required>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">AÇIKLAMA</label>
                        <textarea name="aciklama" class="w-full rounded-xl border-surface-variant bg-surface-container-low" rows="3" placeholder="Talebinizle ilgili kısa bilgi..."></textarea>
                    </div>
                    <button type="submit" class="w-full h-14 bg-secondary text-on-secondary rounded-xl font-bold shadow-lg mt-2">Talebi Gönder</button>
                </form>
            `);
        });

        // Edit profile button
        document.getElementById('btn-edit-profile').addEventListener('click', () => {
            this.showModal('Bilgileri Güncelle', `
                <form id="profile-form" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">TELEFON</label>
                        <input type="text" name="phone" value="${this.user.phone || ''}" class="w-full h-12 rounded-xl border-surface-variant bg-surface-container-low" required>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">E-POSTA</label>
                        <input type="email" name="email" value="${this.user.email || ''}" class="w-full h-12 rounded-xl border-surface-variant bg-surface-container-low">
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">IBAN</label>
                        <input type="text" name="iban_number" value="${this.user.iban_number || ''}" class="w-full h-12 rounded-xl border-surface-variant bg-surface-container-low">
                    </div>
                    <button type="submit" class="w-full h-14 bg-primary text-on-primary rounded-xl font-bold shadow-lg mt-2">Güncelle</button>
                </form>
            `);
        });

        // Change password button
        document.getElementById('btn-change-password').addEventListener('click', () => {
            this.showModal('Şifre Değiştir', `
                <form id="password-form" class="flex flex-col gap-4">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">MEVCUT ŞİFRE</label>
                        <input type="password" name="current_password" class="w-full h-12 rounded-xl border-surface-variant bg-surface-container-low" required>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">YENİ ŞİFRE</label>
                        <input type="password" name="new_password" class="w-full h-12 rounded-xl border-surface-variant bg-surface-container-low" required>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-bold text-on-surface-variant">YENİ ŞİFRE (TEKRAR)</label>
                        <input type="password" name="confirm_password" class="w-full h-12 rounded-xl border-surface-variant bg-surface-container-low" required>
                    </div>
                    <button type="submit" class="w-full h-14 bg-error text-on-error rounded-xl font-bold shadow-lg mt-2">Şifreyi Güncelle</button>
                </form>
            `);
        });
    },

    async handleLogin() {
        const kimlikNo = document.getElementById('kimlik_no').value;
        const password = document.getElementById('password').value;

        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=login&kimlik_no=${kimlikNo}&password=${password}`
            });
            const result = await response.json();

            if (result.status === 'success') {
                this.user = result.user;
                localStorage.setItem('puantor_user', JSON.stringify(this.user));
                this.showMainApp();
            } else {
                alert(result.message || 'Hatalı giriş bilgileri.');
            }
        } catch (error) {
            console.error('Login error:', error);
            alert('Giriş yapılırken bir hata oluştu.');
        }
    },

    logout() {
        localStorage.removeItem('puantor_user');
        this.user = null;
        this.showLoginPage();
    },

    showLoginPage() {
        document.getElementById('login-page').style.display = 'flex';
        document.getElementById('main-content').style.display = 'none';
        document.getElementById('main-content').classList.remove('logged-in');
    },

    showMainApp() {
        document.getElementById('login-page').style.display = 'none';
        document.getElementById('main-content').style.display = 'block';
        document.getElementById('main-content').classList.add('logged-in');
        this.updateProfileUI();
        this.loadSummary();
        this.loadAdvances();
        this.switchTab('dashboard-tab');
    },

    switchTab(tabId) {
        const navs = document.querySelectorAll('.nav-item');
        const pageTitle = document.getElementById('page-title');

        // Toggle FAB visibility immediately
        const btnNewAdvance = document.getElementById('btn-new-advance');
        if (btnNewAdvance) {
            btnNewAdvance.style.display = tabId === 'advance-tab' ? 'flex' : 'none';
        }

        // Toggle Header Icon dynamically
        const headerIcon = document.getElementById('header-icon');
        if (headerIcon) {
            const icons = {
                'dashboard-tab': 'house',
                'attendance-tab': 'calendar',
                'advance-tab': 'credit-card',
                'profile-tab': 'user'
            };
            headerIcon.setAttribute('data-lucide', icons[tabId] || 'house');
        }

        // Active nav icon update immediately
        navs.forEach(nav => nav.classList.remove('active'));
        const activeNav = document.querySelector(`[data-tab="${tabId}"]`);
        if (activeNav) {
            activeNav.classList.add('active');
            if (pageTitle) {
                const labels = {
                    'dashboard-tab': 'Puantör',
                    'attendance-tab': 'Takvim',
                    'advance-tab': 'Avans Talepleri',
                    'profile-tab': 'Profil'
                };
                pageTitle.textContent = labels[tabId] || 'Puantör';
            }
        }

        // Sequence transitions: First fade out existing active tab
        const currentActiveTab = document.querySelector('.tab-content.active');
        if (currentActiveTab && currentActiveTab.id !== tabId) {
            currentActiveTab.classList.remove('active');
            
            // Wait for fade-out to finish, then show the new one
            setTimeout(() => {
                const activeTab = document.getElementById(tabId);
                if (activeTab) {
                    activeTab.classList.add('active');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            }, 180); // 180ms matches the transition beautifully
        } else {
            // No current active tab or clicking same tab, show immediately
            const activeTab = document.getElementById(tabId);
            if (activeTab) {
                activeTab.classList.add('active');
            }
        }

        setTimeout(() => lucide.createIcons(), 210);

        if (tabId === 'advance-tab') this.loadAdvances();
        if (tabId === 'attendance-tab') this.loadAttendance();
    },

    updateProfileUI() {
        if (this.user) {
            document.getElementById('user-display-name').textContent = this.user.full_name;
            document.getElementById('profile-name').textContent = this.user.full_name;
            document.getElementById('profile-id').textContent = `ID: EMP-${this.user.id.toString().padStart(3, '0')}`;
            document.getElementById('profile-job').textContent = this.user.job || 'Personel';
            document.getElementById('profile-phone').textContent = this.user.phone || '-';
            document.getElementById('profile-email').textContent = this.user.email || '-';
            document.getElementById('profile-iban').textContent = this.user.iban_number || '-';
            
            const initials = this.user.full_name.split(' ').map(n => n[0]).join('');
            document.getElementById('profile-initials').textContent = initials;
        }
    },

    async loadSummary() {
        try {
            const response = await fetch(`api/summary.php?person_id=${this.user.id}`);
            const result = await response.json();
            if (result.status === 'success') {
                // Update total hours
                const totalHours = result.summary.total_hours || 0;
                document.getElementById('total-hours').textContent = totalHours;
                document.getElementById('hours-progress').style.width = `${Math.min((totalHours / 180) * 100, 100)}%`;
                
                // Update stats
                document.getElementById('dashboard-overtime').textContent = `${result.summary.overtime || 0} s`;
                document.getElementById('dashboard-advance').textContent = `${result.summary.advance || 0} TL`;
                
                // Also set available advance limit in the advances tab
                const limitEl = document.getElementById('available-advance-limit');
                if (limitEl) limitEl.textContent = result.summary.balance;

                const recentContainer = document.getElementById('recent-activity-list');
                recentContainer.innerHTML = result.recent.map(item => `
                    <div class="stat-card flex items-center justify-between py-5">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-2xl bg-primary-container flex items-center justify-center text-primary">
                                <i data-lucide="calendar" class="w-6 h-6"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-on-surface">${item.puantaj_turu || item.turu}</h4>
                                <p class="text-[11px] font-bold text-on-surface-variant uppercase tracking-widest">${item.gun}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-black text-primary text-lg">${item.saat} s</p>
                            <p class="text-[10px] font-bold text-on-surface-variant uppercase tracking-tighter">SÜRE</p>
                        </div>
                    </div>
                `).join('') || '<div class="text-center py-8 text-on-surface-variant opacity-50 font-label-sm">Henüz aktivite bulunmuyor.</div>';
                
                lucide.createIcons();
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
                    let statusClass = 'bg-slate-100 text-slate-500';
                    let statusText = 'Bekleyen';
                    let icon = 'clock';

                    if (item.durum == 1) {
                        statusClass = 'bg-emerald-50 text-emerald-600 border border-emerald-100';
                        statusText = 'Onaylandı';
                        icon = 'check-circle-2';
                    } else if (item.durum == 2) {
                        statusClass = 'bg-rose-50 text-rose-500 border border-rose-100';
                        statusText = 'Reddedildi';
                        icon = 'x-circle';
                    } else {
                        statusClass = 'bg-amber-50 text-amber-600 border border-amber-100';
                    }

                    const cleanDesc = (item.aciklama || 'Açıklama belirtilmemiş').replace(/'/g, "\'");

                    return `
                        <div onclick="app.editAdvance('${item.id}', '${item.tutar}', '${cleanDesc}', ${item.durum})" class="stat-card flex flex-col gap-3 group cursor-pointer active:scale-[0.98] transition-all hover:border-primary/15">
                            <div class="flex justify-between items-center">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-primary/5 flex items-center justify-center text-primary">
                                        <i data-lucide="banknote" class="w-4 h-4"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-[15px] font-semibold text-slate-900">${item.tutar} TL</h4>
                                        <p class="text-[10px] font-medium text-slate-400 uppercase tracking-wider">${item.created_at}</p>
                                    </div>
                                </div>
                                <span class="px-2.5 py-1 rounded-full text-[9px] font-semibold uppercase tracking-wider ${statusClass} flex items-center gap-1">
                                    <i data-lucide="${icon}" class="w-3 h-3"></i>
                                    ${statusText}
                                </span>
                            </div>
                            <div class="flex justify-between items-center">
                                <p class="text-xs font-medium text-slate-400 line-clamp-1">${item.aciklama || 'Açıklama belirtilmemiş'}</p>
                                <div class="flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider text-primary opacity-0 group-hover:opacity-100 transition-opacity">
                                    ${item.durum == 0 ? '<span>DÜZENLE</span>' : ''}
                                    <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('') || '<div class="text-center py-12 text-slate-400 font-medium">Henüz avans talebiniz yok.</div>';
                
                lucide.createIcons();
            }
        } catch (error) {
            console.error('Load advances error:', error);
        }
    },

    editAdvance(id, tutar, aciklama, durum) {
        if (durum !== 0) {
            this.showToast('Bu talep onaylandığı veya reddedildiği için güncellenemez.', 'error');
            return;
        }

        const bodyHtml = `
            <form id="edit-advance-form" class="space-y-6">
                <input type="hidden" name="id" value="${id}">
                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">AVANS TUTARI (TL)</label>
                    <input name="tutar" type="number" step="0.01" class="w-full h-14 bg-gray-50 border-none rounded-2xl px-5 focus:ring-2 focus:ring-primary/20 transition-all font-bold text-slate-900" value="${tutar}" required/>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">AÇIKLAMA</label>
                    <textarea name="aciklama" rows="3" class="w-full bg-gray-50 border-none rounded-2xl p-5 focus:ring-2 focus:ring-primary/20 transition-all font-medium text-slate-900" placeholder="Açıklama yazın..." required>${aciklama === 'Açıklama belirtilmemiş' ? '' : aciklama}</textarea>
                </div>
                <button class="w-full h-14 bg-primary text-white font-bold rounded-2xl shadow-lg shadow-primary/20 active:scale-95 transition-all flex items-center justify-center gap-2" type="submit">
                    Güncelle ve Kaydet
                </button>
            </form>
        `;

        this.showModal('Avans Talebini Güncelle', bodyHtml);

        const editForm = document.getElementById('edit-advance-form');
        if (editForm) {
            editForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(editForm);
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
                        this.showToast('Avans talebiniz başarıyla güncellendi.', 'success');
                    } else {
                        this.showToast(res.message || 'Bir hata oluştu.', 'error');
                    }
                } catch (error) {
                    this.showToast('Talep güncellenirken hata oluştu.', 'error');
                }
            });
        }
    },

    async loadAttendance() {
        const m = this.currentMonth;
        const y = this.currentYear;
        
        const monthNames = ["Ocak", "Şubat", "Mart", "Nisan", "Mayıs", "Haziran", "Temmuz", "Ağustos", "Eylül", "Ekim", "Kasım", "Aralık"];
        document.getElementById('current-month-label').textContent = `${monthNames[m-1]} ${y}`;
        document.getElementById('calendar-title').textContent = `${monthNames[m-1].toUpperCase()} ${y}`;

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
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">P</div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">S</div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">Ç</div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">P</div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">C</div>
            <div class="text-[11px] font-bold text-slate-400 uppercase tracking-widest">C</div>
            <div class="text-[11px] font-bold text-rose-400 uppercase tracking-widest">P</div>
        `;
        
        for (let i = 0; i < startingDay; i++) {
            html += `<div class="aspect-square"></div>`;
        }
        
        let totalWorkDays = 0;
        let totalHolidays = 0;
        let totalWorkHours = 0;

        for (let i = 1; i <= daysInMonth; i++) {
            const dayStr = `${year}${month.toString().padStart(2, '0')}${i.toString().padStart(2, '0')}`;
            const record = data.find(r => r.gun === dayStr);
            const isToday = new Date().toDateString() === new Date(year, month-1, i).toDateString();
            const isSunday = new Date(year, month-1, i).getDay() === 0;
            const hasRecord = record && parseFloat(record.saat) > 0;
            
            if (isSunday) {
                totalHolidays++;
            } else if (hasRecord) {
                totalWorkDays++;
                totalWorkHours += parseFloat(record.saat);
            } else if (record && record.type === 'holiday') {
                totalHolidays++;
            }

            let bgClass = "text-slate-800 hover:bg-slate-50";
            if (isSunday) {
                bgClass = "bg-[#829375] text-white shadow-sm";
            } else if (hasRecord) {
                bgClass = "bg-primary text-white shadow-sm";
            }
            
            let ringClass = isToday ? "ring-2 ring-primary ring-offset-2 ring-offset-white" : "";
            
            html += `
                <div class="flex items-center justify-center aspect-square rounded-xl text-sm font-bold cursor-pointer active:scale-95 transition-all ${bgClass} ${ringClass}" 
                     onclick="app.showDayDetails('${dayStr}', ${JSON.stringify(record || {}).replace(/"/g, '&quot;')})">
                    ${i}
                </div>
            `;
        }
        grid.innerHTML = html;

        // Update Monthly Summary counters
        const workDaysEl = document.getElementById('summary-work-days');
        const holidaysEl = document.getElementById('summary-holidays');
        const totalHoursEl = document.getElementById('summary-total-hours');

        if (workDaysEl) workDaysEl.textContent = `${totalWorkDays} Gün`;
        if (holidaysEl) holidaysEl.textContent = `${totalHolidays} Gün`;
        if (totalHoursEl) totalHoursEl.textContent = `${totalWorkHours.toFixed(1).replace('.0', '')} s`;

        // Select today's details by default if in current month, otherwise select 1st day of month
        const today = new Date();
        let defaultDay = 1;
        if (today.getMonth() + 1 === month && today.getFullYear() === year) {
            defaultDay = today.getDate();
        }
        const defaultDayStr = `${year}${month.toString().padStart(2, '0')}${defaultDay.toString().padStart(2, '0')}`;
        const defaultRecord = data.find(r => r.gun === defaultDayStr);
        this.showDayDetails(defaultDayStr, defaultRecord);
    },

    showDayDetails(dayStr, record) {
        const label = document.getElementById('selected-day-label');
        const statusEl = document.getElementById('day-status');
        const durationEl = document.getElementById('day-duration-new');
        const iconEl = document.getElementById('day-icon');
        const iconBgEl = document.getElementById('day-icon-bg');
        
        if (!label || !statusEl || !durationEl) return;

        const date = new Date(dayStr.substring(0, 4), dayStr.substring(4, 6) - 1, dayStr.substring(6, 8));
        const formattedDate = date.toLocaleDateString('tr-TR', { day: 'numeric', month: 'long', weekday: 'long' });
        
        label.textContent = `${formattedDate}`;
        
        const isSunday = date.getDay() === 0;
        
        if (isSunday) {
            statusEl.textContent = 'Hafta Tatili';
            durationEl.textContent = '0 s';
            if (iconEl) iconEl.setAttribute('data-lucide', 'sun');
            if (iconBgEl) iconBgEl.className = 'w-10 h-10 rounded-xl bg-[#829375]/15 flex items-center justify-center text-[#829375]';
        } else if (record && record.saat) {
            statusEl.textContent = record.puantaj_turu || record.turu || 'Normal Çalışma';
            durationEl.textContent = `${parseFloat(record.saat).toFixed(1).replace('.0', '')} s`;
            if (iconEl) iconEl.setAttribute('data-lucide', 'briefcase');
            if (iconBgEl) iconBgEl.className = 'w-10 h-10 rounded-xl bg-primary-container flex items-center justify-center text-primary';
        } else {
            statusEl.textContent = 'Kayıt Bulunmuyor';
            durationEl.textContent = '0 s';
            if (iconEl) iconEl.setAttribute('data-lucide', 'calendar-x');
            if (iconBgEl) iconBgEl.className = 'w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400';
        }
        lucide.createIcons();
    },

    showModal(title, bodyHtml) {
        document.getElementById('modal-title').textContent = title;
        document.getElementById('modal-body').innerHTML = bodyHtml;
        document.querySelector('.modal-overlay').classList.add('active');
        lucide.createIcons();

        // Bind form submissions inside modal
        const advanceForm = document.getElementById('advance-form');
        if (advanceForm) {
            advanceForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(advanceForm);
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
                        this.showToast('Avans talebiniz başarıyla oluşturuldu.', 'success');
                    } else {
                        this.showToast(res.message || 'Bir hata oluştu.', 'error');
                    }
                } catch (error) {
                    this.showToast('Talep gönderilirken hata oluştu.', 'error');
                }
            });
        }
        
        const profileForm = document.getElementById('profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(profileForm);
                formData.append('action', 'update');
                formData.append('person_id', this.user.id);

                try {
                    const response = await fetch('api/profile.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });
                    const res = await response.json();
                    if (res.status === 'success') {
                        this.showToast('Profil başarıyla güncellendi.', 'success');
                        this.user.phone = formData.get('phone');
                        this.user.email = formData.get('email');
                        this.user.iban_number = formData.get('iban_number');
                        localStorage.setItem('puantor_user', JSON.stringify(this.user));
                        this.updateProfileUI();
                        this.hideModal();
                    } else {
                        alert(res.message);
                    }
                } catch (error) {
                    alert('Profil güncellenirken hata oluştu.');
                }
            });
        }

        const passwordForm = document.getElementById('password-form');
        if (passwordForm) {
            passwordForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(passwordForm);
                formData.append('action', 'change_password');
                formData.append('person_id', this.user.id);

                if (formData.get('new_password') !== formData.get('confirm_password')) {
                    alert('Yeni şifreler uyuşmuyor.');
                    return;
                }

                try {
                    const response = await fetch('api/profile.php', {
                        method: 'POST',
                        body: new URLSearchParams(formData)
                    });
                    const res = await response.json();
                    if (res.status === 'success') {
                        this.showToast('Şifre başarıyla değiştirildi.', 'success');
                        this.hideModal();
                    } else {
                        alert(res.message);
                    }
                } catch (error) {
                    alert('Şifre değiştirilirken hata oluştu.');
                }
            });
        }
    },

    hideModal() {
        document.querySelector('.modal-overlay').classList.remove('active');
    },

    showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastMsg = document.getElementById('toast-msg');
        const toastIcon = document.getElementById('toast-icon');
        if (!toast) return;

        toastMsg.textContent = message;
        toast.className = 'toast ' + type;
        toastIcon.setAttribute('data-lucide', type === 'success' ? 'check-circle' : 'alert-circle');
        lucide.createIcons();

        // Show
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Hide after 3s
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
};

document.addEventListener('DOMContentLoaded', () => window.app.init());
