<div class="card" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
    <div class="card-body p-4">
        <div class="d-flex align-items-center mb-4">
            <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle" style="width: 45px; height: 45px;">
                <i class="ti ti-lock" style="font-size: 1.5rem;"></i>
            </span>
            <div>
                <h3 class="card-title mb-1 fw-bold text-dark" style="font-size: 1.15rem;">Şifre Değiştir</h3>
                <p class="text-secondary small mb-0">Hesap güvenliğinizi artırmak amacıyla periyodik olarak şifrenizi yenileyebilirsiniz.</p>
            </div>
        </div>
        
        <form id="passwordForm" autocomplete="off">
            <!-- Hidden inputs to prevent auto-fill -->
            <input type="password" style="display:none">
            
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Yeni Şifre</label>
                    <input type="password" class="form-control py-2" name="password" id="new_password" autocomplete="new-password" style="border-radius: 8px;" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold text-dark small" style="margin-bottom: 0.5rem;">Şifre Tekrar</label>
                    <input type="password" class="form-control py-2" name="password_confirm" id="password_confirm" autocomplete="new-password" style="border-radius: 8px;" required>
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <div class="form-text text-secondary small" style="font-size: 0.8rem;">Şifrenizi güncellemek istemiyorsanız bu alanları boş bırakabilirsiniz.</div>
                </div>
            </div>
        </form>
    </div>
</div>
