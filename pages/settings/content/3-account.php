<?php
require_once "App/Helper/date.php";
require_once "Model/AbonelikPaketleriModel.php";
require_once "Model/KullaniciAbonelikleriModel.php";
require_once "App/Helper/security.php";

use App\Helper\Date;
use App\Helper\Security;

$AbonelikPaketleri = new AbonelikPaketleriModel();
$KullaniciAbonelikleri = new KullaniciAbonelikleriModel();

// Get subscription history
$subHistory = $KullaniciAbonelikleri->getSubscriptionHistory($user->id);
$latestSub = !empty($subHistory) ? $subHistory[0] : null;

// Check for pending request
$hasPendingRequest = false;
if (!empty($subHistory)) {
    foreach ($subHistory as $sub) {
        if ($sub->durum === 'onay_bekliyor') {
            $hasPendingRequest = true;
            break;
        }
    }
}

// Get available packages
$availablePackages = $AbonelikPaketleri->getPackages();

// Registration Date formatted in Turkish
$turkishMonths = [
    'January' => 'Ocak', 'February' => 'Şubat', 'March' => 'Mart', 'April' => 'Nisan',
    'May' => 'Mayıs', 'June' => 'Haziran', 'July' => 'Temmuz', 'August' => 'Ağustos',
    'September' => 'Eylül', 'October' => 'Ekim', 'November' => 'Kasım', 'December' => 'Aralık'
];
$regDate = date('d F Y', strtotime($user->created_at));
foreach ($turkishMonths as $en => $tr) {
    $regDate = str_replace($en, $tr, $regDate);
}

// Last Login
$loginLogs = $User->getLoginLogs($user->id);
$lastLoginStr = 'Bugün';
if (count($loginLogs) > 1) {
    $lastLoginTime = strtotime($loginLogs[1]->login_time);
    if (date('Y-m-d', $lastLoginTime) === date('Y-m-d')) {
        $lastLoginStr = 'Bugün ' . date('H:i', $lastLoginTime);
    } else {
        $lastLoginStr = date('d.m.Y H:i', $lastLoginTime);
    }
}
?>
<div class="row row-cards">
    <?php if (isset($_GET['expired']) && $_GET['expired'] == 1): ?>
        <div class="col-12 mb-2">
            <div class="alert alert-danger d-flex align-items-center mb-0" role="alert" style="border-radius: 12px; font-weight: 500;">
                <i class="ti ti-alert-triangle icon me-3" style="font-size: 1.5rem;"></i>
                <div>Abonelik/Paket süreniz sona ermiştir. Sisteme tam erişim sağlamak için lütfen yeni bir paket satın alın.</div>
            </div>
        </div>
    <?php endif; ?>
    <!-- Hesap Detayları & İstatistikler -->
    <div class="col-12 mb-3">
        <div class="card" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
            <div class="card-body p-4">
                <div class="d-flex align-items-center mb-4">
                    <span class="avatar avatar-md me-3 bg-light text-dark rounded-circle" style="width: 45px; height: 45px;">
                        <i class="ti ti-info-circle" style="font-size: 1.5rem;"></i>
                    </span>
                    <div>
                        <h3 class="card-title mb-1 fw-bold text-dark" style="font-size: 1.15rem;">Hesap Detayları & İstatistikler</h3>
                        <p class="text-secondary small mb-0">Mevcut yöneticilik hesabınızın sistem veritabanındaki kayıt detayları.</p>
                    </div>
                </div>

                <div class="row text-center text-md-start">
                    <div class="col-md-4 mb-3 mb-md-0 border-end-md">
                        <div class="text-secondary small mb-1">Kayıt Tarihi</div>
                        <div class="h3 fw-bold text-dark mb-0"><?php echo htmlspecialchars($regDate); ?></div>
                    </div>
                    <div class="col-md-4 mb-3 mb-md-0 border-end-md ps-md-4">
                        <div class="text-secondary small mb-1">Hesap Durumu</div>
                        <div>
                            <span class="badge bg-success-lt text-success px-3 py-1 rounded-pill" style="font-size: 0.85rem; font-weight: 600;">
                                ● Aktif
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4 ps-md-4">
                        <div class="text-secondary small mb-1">Son Giriş</div>
                        <div class="h3 fw-bold text-dark mb-0"><?php echo htmlspecialchars($lastLoginStr); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Satın Almalar & Abonelikler -->
    <div class="col-12 mb-3">
        <div class="card" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
            <div class="card-header bg-transparent py-3 d-flex align-items-center justify-content-between" style="border-bottom: 1px solid rgba(0,0,0,0.06);">
                <div>
                    <h3 class="card-title fw-bold text-dark mb-1" style="font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ti ti-package text-secondary" style="font-size: 1.35rem;"></i>
                        <span>Satın Almalar & Abonelikler</span>
                    </h3>
                    <p class="text-secondary small mb-0">Hesabınıza tanımlanmış paket ve abonelik geçmişi.</p>
                </div>
                <div>
                    <?php if ($hasPendingRequest): ?>
                    <button type="button" class="btn btn-outline-dark px-3 py-2 btn-new-package-pending" style="border-radius: 8px; font-weight: 600;">
                        <i class="ti ti-plus icon me-2" style="font-size: 1rem;"></i>
                        Yeni Paket Al
                    </button>
                    <?php else: ?>
                    <button type="button" class="btn btn-outline-dark px-3 py-2" data-bs-toggle="modal" data-bs-target="#new-package-modal" style="border-radius: 8px; font-weight: 600;">
                        <i class="ti ti-plus icon me-2" style="font-size: 1rem;"></i>
                        Yeni Paket Al
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table text-nowrap">
                    <thead>
                        <tr>
                            <th>Paket</th>
                            <th>Başlangıç</th>
                            <th>Bitiş</th>
                            <th>Firma Hakkı</th>
                            <th>Kullanıcı Hakkı</th>
                            <th>Tutar</th>
                            <th>Durum</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($subHistory)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Henüz bir satın alma işlemi bulunmuyor.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($subHistory as $sub): 
                                $startDate = $sub->baslangic_tarihi ? date('d.m.Y', strtotime($sub->baslangic_tarihi)) : '-';
                                $endDate = $sub->bitis_tarihi ? date('d.m.Y', strtotime($sub->bitis_tarihi)) : '-';
                                $tutar = $sub->paket_fiyati ? '₺' . number_format($sub->paket_fiyati, 0, ',', '.') : '-';
                                
                                // Format status badge
                                $statusBadge = '';
                                if ($sub->durum == 'aktif') {
                                    $statusBadge = '<span class="badge bg-success-lt text-success px-3 py-1 rounded-pill" style="font-weight:600;">● Aktif</span>';
                                } elseif ($sub->durum == 'onay_bekliyor') {
                                    $statusBadge = '<span class="badge bg-warning-lt text-warning px-3 py-1 rounded-pill" style="font-weight:600;">● Onay Bekliyor</span>';
                                } elseif ($sub->durum == 'iptal') {
                                    $statusBadge = '<span class="badge bg-danger-lt text-danger px-3 py-1 rounded-pill" style="font-weight:600;">● İptal Edildi</span>';
                                } elseif ($sub->durum == 'sona_erdi') {
                                    $statusBadge = '<span class="badge bg-secondary-lt text-secondary px-3 py-1 rounded-pill" style="font-weight:600;">● Süresi Doldu</span>';
                                } else {
                                    $statusBadge = '<span class="badge bg-light text-secondary px-3 py-1 rounded-pill" style="font-weight:600;">● Beklemede</span>';
                                }
                                ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?php echo htmlspecialchars($sub->paket_adi); ?></div>
                                        <div class="text-muted small">#<?php echo htmlspecialchars($sub->id); ?></div>
                                    </td>
                                    <td><?php echo $startDate; ?></td>
                                    <td><?php echo $endDate; ?></td>
                                    <td class="fw-semibold text-dark"><?php echo (int)$sub->firma_hakki; ?></td>
                                    <td class="fw-semibold text-dark"><?php echo (int)$sub->alt_kullanici_hakki; ?></td>
                                    <td class="fw-bold text-dark"><?php echo $tutar; ?></td>
                                    <td><?php echo $statusBadge; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Hesap Dondurma/Silme Seçenekleri (Küçük Kart İçinde) -->
    <div class="col-12">
        <div class="card" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.01);">
            <div class="card-body p-3">
                <div class="row g-3">
                    <div class="col-md-6 border-end-md">
                        <h4 class="fw-bold text-warning mb-1" style="font-size: 0.85rem;">Hesabımı Dondur!</h4>
                        <p class="text-secondary mb-2" style="font-size: 0.75rem; line-height: 1.4;">Geri dönüş yapmak üzere bir süreliğine hesabınızı dondurmak isterseniz bu seçeneği kullanın.</p>
                        <button class="btn btn-warning btn-sm px-3 py-1.5" style="border-radius: 6px; font-size: 0.75rem; font-weight: 600;">Hesabımı Dondur</button>
                    </div>
                    
                    <div class="col-md-6 ps-md-3">
                        <h4 class="fw-bold text-danger mb-1" style="font-size: 0.85rem;">Hesabımı Sil!</h4>
                        <p class="text-secondary mb-2" style="font-size: 0.75rem; line-height: 1.4;">Hesabınızı sildiğinizde puantaj, personel, proje ve finans verileri dahil tüm kayıtlarınız <strong>kalıcı olarak silinecektir</strong>.</p>
                        <button class="btn btn-danger btn-sm px-3 py-1.5" style="border-radius: 6px; font-size: 0.75rem; font-weight: 600;">Hesabımı Sil</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Alabileceğiniz Paketler -->
<div class="modal modal-blur fade" id="new-package-modal" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 16px; border: none; box-shadow: 0 10px 30px rgba(0,0,0,0.15);">
            <div class="modal-header" style="border-bottom: 1px solid rgba(0,0,0,0.06); padding: 1.5rem;">
                <div>
                    <h5 class="modal-title fw-bold text-dark" style="font-size: 1.2rem; display: flex; align-items: center; gap: 0.5rem;">
                        <i class="ti ti-package text-primary" style="font-size: 1.4rem;"></i>
                        <span>Alabileceğiniz Paketler</span>
                    </h5>
                    <p class="text-secondary small mb-0 mt-1">Lütfen satın almak istediğiniz paketi seçin ve ardından ödeme adımlarını takip edin.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="padding: 1.5rem; max-height: 70vh; overflow-y: auto;">
                <div class="row g-3">
                    <?php 
                    foreach ($availablePackages as $pkg): 
                        if ($pkg->aktif_mi != 1) continue;
                        $pkg_id_encrypted = Security::encrypt($pkg->id);
                        
                        // Split features
                        $features = !empty($pkg->ozellikler) ? explode(';', $pkg->ozellikler) : [];
                        $isUnlimited = (strtolower($pkg->ad) === 'sınırsız');
                        $isTrial = ($pkg->fiyat <= 0 && !$isUnlimited);
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 p-3 d-flex flex-column justify-content-between" style="border: 1px solid rgba(0, 0, 0, 0.08); border-radius: 12px; transition: transform 0.2s, box-shadow 0.2s;">
                                <div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-uppercase tracking-wider fw-bold text-secondary" style="font-size: 0.75rem;"><?php echo htmlspecialchars($pkg->ad); ?></span>
                                    </div>
                                    <div class="d-flex align-items-baseline mb-3">
                                        <?php if ($isUnlimited): ?>
                                            <span class="display-6 fw-bold text-dark" style="font-size: 1.8rem;">Bize Ulaşın</span>
                                        <?php elseif ($isTrial): ?>
                                            <span class="display-6 fw-bold text-dark">Ücretsiz</span>
                                        <?php else: ?>
                                            <span class="display-6 fw-bold text-dark">₺<?php echo number_format($pkg->fiyat, 0, ',', '.'); ?></span>
                                            <span class="text-secondary small ms-1">/ay</span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <ul class="list-unstyled mb-4 lh-lg text-secondary" style="font-size: 0.9rem;">
                                        <li class="d-flex align-items-center mb-1">
                                            <i class="ti ti-check text-success me-2" style="font-size: 1.1rem;"></i>
                                            <span><strong><?php echo $pkg->alt_kullanici_hakki >= 9999 ? 'Sınırsız' : (int)$pkg->alt_kullanici_hakki; ?></strong> Personel Limiti</span>
                                        </li>
                                        <li class="d-flex align-items-center mb-1">
                                            <i class="ti ti-check text-success me-2" style="font-size: 1.1rem;"></i>
                                            <span><strong><?php echo $pkg->firma_hakki >= 9999 ? 'Sınırsız' : (int)$pkg->firma_hakki; ?></strong> Firma Limiti</span>
                                        </li>
                                        <li class="d-flex align-items-center mb-1">
                                            <i class="ti ti-check text-success me-2" style="font-size: 1.1rem;"></i>
                                            <span><strong><?php echo (int)$pkg->sure; ?> Gün</strong> Kullanım Süresi</span>
                                        </li>
                                        <?php foreach ($features as $feat): 
                                            if (empty(trim($feat))) continue; 
                                            if (trim($feat) === 'Görev Yönetimi') continue; // Handled below
                                        ?>
                                            <li class="d-flex align-items-center mb-1">
                                                <i class="ti ti-check text-success me-2" style="font-size: 1.1rem;"></i>
                                                <span><?php echo htmlspecialchars(trim($feat)); ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                        <li class="d-flex align-items-center mb-1 <?php echo strpos($pkg->ozellikler, 'Görev Yönetimi') !== false ? '' : 'text-decoration-line-through text-muted'; ?>">
                                            <?php if (strpos($pkg->ozellikler, 'Görev Yönetimi') !== false): ?>
                                                <i class="ti ti-check text-success me-2" style="font-size: 1.1rem;"></i>
                                            <?php else: ?>
                                                <i class="ti ti-x text-danger me-2" style="font-size: 1.1rem;"></i>
                                            <?php endif; ?>
                                            <span>Görev Yönetimi</span>
                                        </li>
                                    </ul>
                                </div>
                                
                                <div class="mt-auto">
                                    <button type="button" class="btn btn-dark w-100 choose_package py-2 fw-bold" 
                                            data-id="<?php echo $pkg_id_encrypted; ?>"
                                            data-bs-dismiss="modal"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#modal-team"
                                            style="border-radius: 8px;">
                                        Paketi Seç
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer" style="border-top: 1px solid rgba(0,0,0,0.06); padding: 1rem 1.5rem;">
                <button type="button" class="btn btn-link link-secondary" data-bs-dismiss="modal">Kapat</button>
            </div>
        </div>
    </div>
</div>

<?php include_once "pricing-modal.php" ?>