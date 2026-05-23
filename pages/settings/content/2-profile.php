<div class="card" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-4">
            <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle" style="width: 45px; height: 45px;">
                <i class="ti ti-user" style="font-size: 1.5rem;"></i>
            </span>
            <div>
                <h3 class="card-title mb-1 fw-bold text-dark" style="font-size: 1.15rem;">Kişisel Bilgiler</h3>
                <p class="text-secondary small mb-0">Yönetici kimliğinizi ve sistemde görüntülenecek iletişim tercihlerinizi yapılandırın.</p>
            </div>
        </div>
        
        <form id="profileForm">
            <div class="row mb-3">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Ad Soyad</label>
                    <input type="text" class="form-control py-2" name="full_name" value="<?php echo htmlspecialchars($user->full_name ?? '') ?>" required style="border-radius: 8px;">
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Kullanıcı Adı</label>
                    <input type="text" class="form-control py-2" name="username" value="<?php echo htmlspecialchars($user->username ?? '') ?>" style="border-radius: 8px;">
                </div>
            </div>
            
            <div class="row">
                <div class="col-12">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">E-posta Adresi</label>
                    <input type="email" class="form-control py-2 bg-light text-muted" value="<?php echo htmlspecialchars($user->email ?? '') ?>" disabled style="border-radius: 8px; cursor: not-allowed;">
                    <div class="form-text mt-2 text-secondary small" style="font-size: 0.8rem;">Giriş yapmak ve sistem kritik bildirimlerini almak için kullanılır. (Değiştirilemez)</div>
                </div>
            </div>
        </form>
    </div>
</div>