<div id="profile-tab" class="tab-content active">
    <div class="profile-cover mb-4">
        <div class="d-flex align-items-center gap-4">
            <div class="avatar avatar-xl rounded bg-primary text-white fw-bold shadow-sm" id="profile-initials-large" style="font-size: 1.5rem;">
                ??
            </div>
            <div>
                <h2 id="profile-name" class="h2 mb-1" style="font-weight: 800;">İsim Soyisim</h2>
                <p id="profile-id" class="text-muted small mb-2" style="font-weight: 500;">ID: EMP-000</p>
                <span id="profile-job" class="badge bg-primary-lt text-primary px-3 py-2">Personel</span>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-6">
            <a href="javascript:void(0)" onclick="app.showEditProfile()" class="profile-action-btn">
                <i class="ti ti-user-edit text-primary"></i>
                <span>Bilgileri Güncelle</span>
            </a>
        </div>
        <div class="col-6">
            <a href="javascript:void(0)" onclick="app.showChangePassword()" class="profile-action-btn">
                <i class="ti ti-lock-password text-danger"></i>
                <span>Şifre Değiştir</span>
            </a>
        </div>
    </div>

    <div class="profile-info-list mb-4">
        <div class="profile-info-item">
            <div class="profile-info-icon">
                <i class="ti ti-phone fs-2"></i>
            </div>
            <div class="flex-fill">
                <p class="text-muted small mb-0">Telefon</p>
                <p id="profile-phone" class="fw-bold mb-0">-</p>
            </div>
        </div>
        <div class="profile-info-item">
            <div class="profile-info-icon">
                <i class="ti ti-mail fs-2"></i>
            </div>
            <div class="flex-fill">
                <p class="text-muted small mb-0">E-Posta</p>
                <p id="profile-email" class="fw-bold mb-0">-</p>
            </div>
        </div>
        <div class="profile-info-item">
            <div class="profile-info-icon">
                <i class="ti ti-credit-card fs-2"></i>
            </div>
            <div class="flex-fill">
                <p class="text-muted small mb-0">IBAN</p>
                <p id="profile-iban" class="fw-bold mb-0" style="font-size: 0.85rem;">-</p>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <button onclick="app.logout()" class="btn-logout-premium">
            <i class="ti ti-logout"></i> Oturumu Kapat
        </button>
    </div>
</div>
