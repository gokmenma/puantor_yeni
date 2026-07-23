<?php
$userAvatar = $user->avatar ?? null;
$avatarExists = !empty($userAvatar) && file_exists(ROOT . '/uploads/avatars/' . $userAvatar);
$avatarUrl = $avatarExists ? 'uploads/avatars/' . htmlspecialchars($userAvatar) : '';

$words = explode(" ", trim($user->full_name ?? ''));
$initials = "";
foreach ($words as $w) {
    $initials .= mb_substr($w, 0, 1, 'UTF-8');
}
$initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'));
if (empty($initials)) { $initials = "U"; }
?>
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
        
        <form id="profileForm" enctype="multipart/form-data">
            <!-- Profil Resmi Yükleme Alanı -->
            <div class="mb-4 pb-3 border-bottom">
                <label class="form-label fw-bold text-dark small mb-2">Profil Fotoğrafı</label>
                <div class="d-flex align-items-center gap-3">
                    <div class="position-relative">
                        <span id="profileAvatarPreview" class="avatar avatar-xl rounded-circle shadow-sm" style="width: 72px; height: 72px; background-size: cover; background-position: center; <?php echo $avatarExists ? "background-image: url('{$avatarUrl}');" : ""; ?>">
                            <span id="profileAvatarInitials" class="fw-bold text-white bg-primary rounded-circle w-100 h-100 align-items-center justify-content-center" style="font-size: 1.4rem; display: <?php echo $avatarExists ? 'none' : 'flex'; ?>;">
                                <?php echo htmlspecialchars($initials); ?>
                            </span>
                        </span>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="btnSelectAvatar">
                                <i class="ti ti-upload me-1"></i> Fotoğraf Seç
                            </button>
                            <button type="button" class="btn btn-outline-danger btn-sm <?php echo $avatarExists ? '' : 'd-none'; ?>" id="btnRemoveAvatar">
                                <i class="ti ti-trash me-1"></i> Kaldır
                            </button>
                        </div>
                        <div class="text-secondary small" style="font-size: 0.8rem;">JPG, PNG, WEBP veya GIF formatında, maksimum 5MB resim yükleyebilirsiniz.</div>
                    </div>
                </div>
                <input type="file" name="avatar" id="avatarInput" accept="image/png, image/jpeg, image/webp, image/gif" class="d-none">
                <input type="hidden" name="avatar_remove" id="avatarRemoveInput" value="0">
            </div>

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