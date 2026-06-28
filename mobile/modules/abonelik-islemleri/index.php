<?php
// Puantor Mobil - Abonelik İşlemleri Sayfası
require_once ROOT . "/Model/AbonelerModel.php";
require_once ROOT . "/Model/AbonelikPaketleriModel.php";
require_once ROOT . "/Model/OdemelerModel.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/App/Helper/security.php";

use App\Helper\Helper;
use App\Helper\Security;

// Model nesneleri
$abonelerModel = new AbonelerModel();
$paketModel = new AbonelikPaketleriModel();
$odemelerModel = new OdemelerModel();

// Yetki kontrolleri
$hasAboneler = $Auths->hasPermission("aboneler_sayfasi");
$hasPaketler = $Auths->hasPermission("aboneler_paketleri");
$hasSatinAlma = $Auths->hasPermission("abonelik_satin_alimlari");

// Hiçbirine yetkisi yoksa geri yönlendir (zaten index.php'de de yapıldı ama güvenlik için)
if (!$hasAboneler && !$hasPaketler && !$hasSatinAlma) {
    header("Location: dashboard");
    exit();
}

// Verileri çek
$subscribers = $hasAboneler ? $abonelerModel->getSubscribers() : [];
$packages = ($hasPaketler || $hasSatinAlma) ? $paketModel->getPackages() : [];
$payments = $hasSatinAlma ? $odemelerModel->getPayments() : [];

// Aktif varsayılan sekmeyi belirle
$activeTab = "";
if ($hasAboneler) {
    $activeTab = "aboneler";
} elseif ($hasPaketler) {
    $activeTab = "paketler";
} elseif ($hasSatinAlma) {
    $activeTab = "satinalma";
}
?>

<style>
/* Mobil Premium Tasarım Sistemleri */
.mobile-tabs-container {
    background: #ebedf2 !important;
    padding: 3px !important;
    border-radius: 14px !important;
    margin: 0 12px 20px 12px !important;
    display: flex !important;
    align-items: center !important;
}
body[data-bs-theme="dark"] .mobile-tabs-container {
    background: #1e293b !important;
}
.mobile-nav-pills {
    display: flex !important;
    width: 100% !important;
    border: none !important;
}
.mobile-nav-pills .nav-item {
    flex: 1 !important;
}
.mobile-nav-pills .nav-link {
    width: 100% !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    text-align: center !important;
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    padding: 10px 4px !important;
    border-radius: 11px !important;
    color: #6e7687 !important;
    border: none !important;
    background: transparent !important;
    transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
    white-space: nowrap !important;
}
.mobile-nav-pills .nav-link.active {
    background: #ffffff !important;
    color: var(--tblr-primary) !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12) !important;
}
body[data-bs-theme="dark"] .mobile-nav-pills .nav-link.active {
    background: #0f172a !important;
    color: var(--tblr-primary) !important;
}

.mobile-card {
    background: #ffffff !important;
    border-radius: 16px !important;
    border: 1px solid #eef2f7 !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03) !important;
    margin-bottom: 12px !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
body[data-bs-theme="dark"] .mobile-card {
    background: #1e293b !important;
    border-color: #2d3748 !important;
}
.mobile-card:active {
    transform: scale(0.98);
}

.avatar-sub {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: rgba(32, 107, 196, 0.1);
    color: var(--tblr-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.95rem;
}

.card-detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.8rem;
    padding: 6px 0;
    border-bottom: 1px dashed rgba(0,0,0,0.05);
}
body[data-bs-theme="dark"] .card-detail-item {
    border-bottom-color: rgba(255,255,255,0.05);
}
.card-detail-item:last-child {
    border-bottom: none;
}

/* Modallar için premium dokunuşlar */
.modal-content {
    border-radius: 20px !important;
    border: none !important;
    box-shadow: 0 15px 35px rgba(0,0,0,0.2) !important;
}
.form-floating > .form-control, .form-floating > .form-select {
    border-radius: 12px !important;
    border: 1px solid #e2e8f0 !important;
}
body[data-bs-theme="dark"] .form-floating > .form-control, 
body[data-bs-theme="dark"] .form-floating > .form-select {
    border-color: #4a5568 !important;
    background-color: #1e293b !important;
}
.form-floating > label {
    padding-left: 1rem !important;
}
.select2-container--default .select2-selection--single {
    border-radius: 12px !important;
    height: 58px !important;
    border: 1px solid #e2e8f0 !important;
    display: flex !important;
    align-items: center !important;
}
body[data-bs-theme="dark"] .select2-container--default .select2-selection--single {
    border-color: #4a5568 !important;
    background-color: #1e293b !important;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 58px !important;
    padding-left: 1rem !important;
}
body[data-bs-theme="dark"] .select2-container--default .select2-selection--single .select2-selection__rendered {
    color: #cbd5e1 !important;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 56px !important;
}

/* Arama / Filtre / Sıralama Araç Çubuğu */
.sub-toolbar .sub-search-input {
    padding-left: 36px;
    padding-right: 36px;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    font-size: 0.85rem;
    height: 42px;
    background: #f8fafc;
    transition: border-color 0.2s, box-shadow 0.2s;
}
body[data-bs-theme="dark"] .sub-toolbar .sub-search-input {
    border-color: #4a5568;
    background: #1e293b;
    color: #cbd5e1;
}
.sub-toolbar .sub-search-input:focus {
    border-color: var(--tblr-primary);
    box-shadow: 0 0 0 3px rgba(32, 107, 196, 0.1);
    background: #fff;
}
body[data-bs-theme="dark"] .sub-toolbar .sub-search-input:focus {
    background: #0f172a;
}
.sub-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: #a0aec0;
    font-size: 1rem;
    pointer-events: none;
    z-index: 1;
}
.sub-action-btn {
    border-radius: 12px !important;
    height: 42px !important;
    min-width: 42px !important;
    border: 1px solid #e2e8f0 !important;
    padding: 0 12px !important;
    background: #f8fafc !important;
    color: #6e7687 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1rem !important;
    transition: all 0.2s !important;
}
body[data-bs-theme="dark"] .sub-action-btn {
    border-color: #4a5568 !important;
    background: #1e293b !important;
    color: #94a3b8 !important;
}
.sub-action-btn.filter-active {
    border-color: var(--tblr-primary) !important;
    color: var(--tblr-primary) !important;
    background: rgba(32, 107, 196, 0.08) !important;
}
.sub-dropdown {
    border-radius: 14px !important;
    border: none !important;
    box-shadow: 0 8px 28px rgba(0,0,0,0.13) !important;
    padding: 6px !important;
    min-width: 180px !important;
}
body[data-bs-theme="dark"] .sub-dropdown {
    background: #1e293b !important;
}
.sub-dropdown .dropdown-item {
    border-radius: 8px !important;
    font-size: 0.82rem !important;
    padding: 8px 12px !important;
    color: inherit;
}
.sub-dropdown .dropdown-item.active {
    background: rgba(32, 107, 196, 0.1) !important;
    color: var(--tblr-primary) !important;
    font-weight: 600;
}
.sub-dropdown .dropdown-header {
    font-size: 0.7rem !important;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #a0aec0 !important;
    padding: 6px 12px 4px !important;
}
.sub-no-results {
    text-align: center;
    padding: 2rem 0;
}
.sub-clear-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #a0aec0;
    padding: 3px 5px;
    border-radius: 6px;
    line-height: 1;
    cursor: pointer;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: color 0.15s, background 0.15s;
}
.sub-clear-btn:hover {
    color: #6e7687;
    background: rgba(0,0,0,0.06);
}
body[data-bs-theme="dark"] .sub-clear-btn:hover {
    background: rgba(255,255,255,0.08);
    color: #cbd5e1;
}
.sub-dropdown .dropdown-item:focus {
    outline: none;
}
</style>

<div class="container px-0">
    <div class="mb-3 px-2 d-flex align-items-center justify-content-between">
        <div>
            <h2 class="mb-1 text-semibold" style="letter-spacing: -0.5px;">Abonelik İşlemleri</h2>
            <p class="text-muted text-xs mb-0">Sistem üyelik, paket ve ödemelerini yönetin.</p>
        </div>
    </div>

    <!-- Sekmeler -->
    <div class="mobile-tabs-container">
        <ul class="nav nav-pills mobile-nav-pills flex-nowrap" id="subscriptionTabs" role="tablist">
            <?php if ($hasAboneler): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $activeTab == 'aboneler' ? 'active' : ''; ?>" id="aboneler-tab" data-bs-toggle="pill" data-bs-target="#tab-aboneler" type="button" role="tab">
                        <i class="ti ti-users me-1" style="font-size: 1.1rem;"></i> Aboneler
                    </button>
                </li>
            <?php endif; ?>
            <?php if ($hasPaketler): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $activeTab == 'paketler' ? 'active' : ''; ?>" id="paketler-tab" data-bs-toggle="pill" data-bs-target="#tab-paketler" type="button" role="tab">
                        <i class="ti ti-package me-1" style="font-size: 1.1rem;"></i> Paketler
                    </button>
                </li>
            <?php endif; ?>
            <?php if ($hasSatinAlma): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?php echo $activeTab == 'satinalma' ? 'active' : ''; ?>" id="satinalma-tab" data-bs-toggle="pill" data-bs-target="#tab-satinalma" type="button" role="tab">
                        <i class="ti ti-credit-card me-1" style="font-size: 1.1rem;"></i> Satışlar
                    </button>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="tab-content px-2 pb-5" id="subscriptionTabContent">
        
        <?php if ($hasAboneler): ?>
        <!-- 1. ABONELER TABI -->
        <div class="tab-pane fade <?php echo $activeTab == 'aboneler' ? 'show active' : ''; ?>" id="tab-aboneler" role="tabpanel">
            <?php if (!empty($subscribers)): ?>
            <div class="sub-toolbar mb-3">
                <div class="d-flex gap-2 align-items-center">
                    <div class="flex-grow-1 position-relative">
                        <i class="ti ti-search sub-search-icon"></i>
                        <input type="text" class="form-control sub-search-input" id="ab-search" placeholder="Ad veya e-posta ara...">
                        <button type="button" class="sub-clear-btn d-none" id="ab-clear"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="dropdown">
                        <button class="btn sub-action-btn filter-active" type="button" data-bs-toggle="dropdown" id="ab-filter-btn" title="Filtrele">
                            <i class="ti ti-filter"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end sub-dropdown" id="ab-filter-menu">
                            <li><h6 class="dropdown-header">Durum Filtresi</h6></li>
                            <li><button type="button" class="dropdown-item ab-filter-item" data-val="all">Tümü</button></li>
                            <li><button type="button" class="dropdown-item ab-filter-item active" data-val="aktif">Aktif</button></li>
                            <li><button type="button" class="dropdown-item ab-filter-item" data-val="sona_erdi">Sona Erdi</button></li>
                            <li><button type="button" class="dropdown-item ab-filter-item" data-val="iptal">İptal</button></li>
                            <li><button type="button" class="dropdown-item ab-filter-item" data-val="onay_bekliyor">Onay Bekliyor</button></li>
                            <li><button type="button" class="dropdown-item ab-filter-item" data-val="beklemede">Beklemede</button></li>
                            <li><button type="button" class="dropdown-item ab-filter-item" data-val="yok">Abonelik Yok</button></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button class="btn sub-action-btn" type="button" data-bs-toggle="dropdown" id="ab-sort-btn" title="Sırala">
                            <i class="ti ti-arrows-sort"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end sub-dropdown" id="ab-sort-menu">
                            <li><h6 class="dropdown-header">Sıralama</h6></li>
                            <li><button type="button" class="dropdown-item ab-sort-item active" data-val="name-asc">Ad (A-Z)</button></li>
                            <li><button type="button" class="dropdown-item ab-sort-item" data-val="name-desc">Ad (Z-A)</button></li>
                            <li><button type="button" class="dropdown-item ab-sort-item" data-val="days-desc">Kalan Gün (Çok-Az)</button></li>
                            <li><button type="button" class="dropdown-item ab-sort-item" data-val="days-asc">Kalan Gün (Az-Çok)</button></li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($subscribers)): ?>
                <div class="text-center py-5">
                    <i class="ti ti-users-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    <p class="text-muted text-sm mb-0">Kayıtlı abone bulunamadı.</p>
                </div>
            <?php else: ?>
                <div id="aboneler-list">
                <?php foreach ($subscribers as $sub): 
                    // Initials for avatar
                    $words = explode(" ", trim($sub->full_name));
                    $initials = "";
                    foreach ($words as $w) {
                        $initials .= mb_substr($w, 0, 1, 'UTF-8');
                    }
                    $initials = mb_strtoupper(mb_substr($initials, 0, 2, 'UTF-8'));
                    if (empty($initials)) { $initials = "AB"; }

                    // Date and day calculation
                    $baslangic = $sub->baslangic_tarihi ? date('d.m.Y', strtotime($sub->baslangic_tarihi)) : '-';
                    $bitis = $sub->bitis_tarihi ? date('d.m.Y', strtotime($sub->bitis_tarihi)) : '-';
                    $kalan_gun_str = '-';
                    $kalan_gun_class = 'text-secondary';
                    $raw_days = -9999;

                    if ($sub->abonelik_durumu == 'aktif' && $sub->bitis_tarihi) {
                        $today = new DateTime(date('Y-m-d'));
                        $end_date = new DateTime($sub->bitis_tarihi);
                        if ($today <= $end_date) {
                            $interval = $today->diff($end_date);
                            $days = (int)$interval->format('%r%a');
                            $raw_days = $days;
                            if ($days == 0) {
                                $kalan_gun_str = 'Bugün son gün';
                                $kalan_gun_class = 'badge bg-warning text-warning-fg';
                            } else {
                                $kalan_gun_str = $days . ' gün kaldı';
                                $kalan_gun_class = $days <= 5 ? 'badge bg-warning text-warning-fg' : 'badge bg-success text-success-fg';
                            }
                        } else {
                            $kalan_gun_str = 'Süresi doldu';
                            $kalan_gun_class = 'badge bg-danger text-danger-fg';
                        }
                    }

                    // Status Badge
                    $status_badge = '';
                    switch ($sub->abonelik_durumu) {
                        case 'aktif':
                            $status_badge = '<span class="badge bg-success text-success-fg">Aktif</span>';
                            break;
                        case 'sona_erdi':
                            $status_badge = '<span class="badge bg-secondary text-secondary-fg">Sona Erdi</span>';
                            break;
                        case 'iptal':
                            $status_badge = '<span class="badge bg-danger text-danger-fg">İptal</span>';
                            break;
                        case 'onay_bekliyor':
                            $status_badge = '<span class="badge bg-warning text-warning-fg">Onay Bekliyor</span>';
                            break;
                        case 'beklemede':
                            $status_badge = '<span class="badge bg-info text-info-fg">Beklemede</span>';
                            break;
                        default:
                            $status_badge = '<span class="badge bg-light text-secondary">Abonelik Yok</span>';
                    }
                    ?>
                    <div class="mobile-card p-3"
                         data-ab-name="<?php echo mb_strtolower(htmlspecialchars($sub->full_name), 'UTF-8'); ?>"
                         data-ab-email="<?php echo mb_strtolower(htmlspecialchars($sub->email), 'UTF-8'); ?>"
                         data-ab-status="<?php echo htmlspecialchars($sub->abonelik_durumu ?? 'yok'); ?>"
                         data-ab-days="<?php echo $raw_days; ?>">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-sub"><?php echo htmlspecialchars($initials); ?></div>
                            <div class="flex-grow-1" style="min-width: 0;">
                                <h4 class="mb-0 text-bold text-sm text-truncate" style="color: var(--tblr-dark);"><?php echo htmlspecialchars($sub->full_name); ?></h4>
                                <p class="text-muted text-xs mb-0 text-truncate"><?php echo htmlspecialchars($sub->email); ?></p>
                            </div>
                            <div class="text-end">
                                <?php echo $status_badge; ?>
                            </div>
                        </div>

                        <div class="card-detail-item">
                            <span class="text-muted">Telefon:</span>
                            <span class="text-semibold text-dark"><?php echo htmlspecialchars($sub->phone ?: '-'); ?></span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Aktif Paket:</span>
                            <span class="text-semibold text-primary"><?php echo htmlspecialchars($sub->paket_adi ?: 'Yok'); ?></span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Abonelik Dönemi:</span>
                            <span class="text-semibold text-dark"><?php echo $baslangic . ' - ' . $bitis; ?></span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Kalan Süre:</span>
                            <span class="<?php echo $kalan_gun_class; ?>"><?php echo $kalan_gun_str; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div><!-- /#aboneler-list -->
                <div class="sub-no-results d-none" id="aboneler-no-results">
                    <i class="ti ti-search-off text-muted mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                    <p class="text-muted text-sm mb-0">Aramanızla eşleşen abone bulunamadı.</p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($hasPaketler): ?>
        <!-- 2. PAKETLER TABI -->
        <div class="tab-pane fade <?php echo $activeTab == 'paketler' ? 'show active' : ''; ?>" id="tab-paketler" role="tabpanel">
            <?php if (!empty($packages)): ?>
            <div class="sub-toolbar mb-3">
                <div class="d-flex gap-2 align-items-center">
                    <div class="flex-grow-1 position-relative">
                        <i class="ti ti-search sub-search-icon"></i>
                        <input type="text" class="form-control sub-search-input" id="pkg-search" placeholder="Paket adı ara...">
                        <button type="button" class="sub-clear-btn d-none" id="pkg-clear"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="dropdown">
                        <button class="btn sub-action-btn" type="button" data-bs-toggle="dropdown" id="pkg-filter-btn" title="Filtrele">
                            <i class="ti ti-filter"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end sub-dropdown" id="pkg-filter-menu">
                            <li><h6 class="dropdown-header">Durum Filtresi</h6></li>
                            <li><button type="button" class="dropdown-item pkg-filter-item active" data-val="all">Tümü</button></li>
                            <li><button type="button" class="dropdown-item pkg-filter-item" data-val="1">Aktif</button></li>
                            <li><button type="button" class="dropdown-item pkg-filter-item" data-val="0">Pasif</button></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button class="btn sub-action-btn" type="button" data-bs-toggle="dropdown" id="pkg-sort-btn" title="Sırala">
                            <i class="ti ti-arrows-sort"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end sub-dropdown" id="pkg-sort-menu">
                            <li><h6 class="dropdown-header">Sıralama</h6></li>
                            <li><button type="button" class="dropdown-item pkg-sort-item active" data-val="name-asc">Ad (A-Z)</button></li>
                            <li><button type="button" class="dropdown-item pkg-sort-item" data-val="name-desc">Ad (Z-A)</button></li>
                            <li><button type="button" class="dropdown-item pkg-sort-item" data-val="price-asc">Fiyat (Artan)</button></li>
                            <li><button type="button" class="dropdown-item pkg-sort-item" data-val="price-desc">Fiyat (Azalan)</button></li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($packages)): ?>
                <div class="text-center py-5">
                    <i class="ti ti-package-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    <p class="text-muted text-sm mb-0">Sistemde tanımlı paket bulunamadı.</p>
                </div>
            <?php else: ?>
                <div id="paketler-list">
                <?php foreach ($packages as $pkg): 
                    $encryptedPkgId = Security::encrypt($pkg->id);
                    $fiyat_format = number_format($pkg->fiyat, 2, ',', '.') . ' ₺';
                    $status_badge = $pkg->aktif_mi == 1 
                        ? '<span class="badge bg-success text-success-fg">Aktif</span>' 
                        : '<span class="badge bg-secondary text-secondary-fg">Pasif</span>';
                    ?>
                    <div class="mobile-card p-3"
                         data-pkg-name="<?php echo mb_strtolower(htmlspecialchars($pkg->ad), 'UTF-8'); ?>"
                         data-pkg-active="<?php echo (int)$pkg->aktif_mi; ?>"
                         data-pkg-price="<?php echo (float)$pkg->fiyat; ?>">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h4 class="mb-0 text-bold text-primary" style="font-size: 1rem;"><?php echo htmlspecialchars($pkg->ad); ?></h4>
                            <div>
                                <?php echo $status_badge; ?>
                            </div>
                        </div>

                        <div class="card-detail-item">
                            <span class="text-muted">Fiyat:</span>
                            <span class="text-bold text-dark"><?php echo $fiyat_format; ?></span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Süre:</span>
                            <span class="text-semibold text-dark"><?php echo (int)$pkg->sure; ?> Gün</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Firma Limiti:</span>
                            <span class="text-semibold text-dark"><?php echo (int)$pkg->firma_hakki; ?> Firma</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Alt Kullanıcı Limiti:</span>
                            <span class="text-semibold text-dark"><?php echo (int)$pkg->alt_kullanici_hakki; ?> Kullanıcı</span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Açıklama:</span>
                            <span class="text-muted text-xs text-truncate d-inline-block" style="max-width: 200px;"><?php echo htmlspecialchars($pkg->ozellikler ?: '-'); ?></span>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-3 pt-2 border-top border-light">
                            <button class="btn btn-sm btn-outline-secondary px-3 py-1 btn-edit-paket-mobile" style="border-radius: 8px;"
                                    data-id="<?php echo $encryptedPkgId; ?>"
                                    data-ad="<?php echo htmlspecialchars($pkg->ad); ?>"
                                    data-fiyat="<?php echo htmlspecialchars($pkg->fiyat); ?>"
                                    data-sure="<?php echo (int)$pkg->sure; ?>"
                                    data-firma_hakki="<?php echo (int)$pkg->firma_hakki; ?>"
                                    data-alt_kullanici_hakki="<?php echo (int)$pkg->alt_kullanici_hakki; ?>"
                                    data-ozellikler="<?php echo htmlspecialchars($pkg->ozellikler ?? ''); ?>"
                                    data-aktif_mi="<?php echo (int)$pkg->aktif_mi; ?>">
                                <i class="ti ti-edit me-1"></i> Düzenle
                            </button>
                            <button class="btn btn-sm btn-outline-danger px-3 py-1 btn-delete-paket-mobile" style="border-radius: 8px;" data-id="<?php echo $encryptedPkgId; ?>">
                                <i class="ti ti-trash me-1"></i> Sil
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div><!-- /#paketler-list -->
                <div class="sub-no-results d-none" id="paketler-no-results">
                    <i class="ti ti-search-off text-muted mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                    <p class="text-muted text-sm mb-0">Aramanızla eşleşen paket bulunamadı.</p>
                </div>
            <?php endif; ?>

            <!-- Floating Action Button for adding Package -->
            <button type="button" class="mobile-fab btn-add-paket-mobile border-0" style="position: fixed; right: 20px; bottom: 80px; z-index: 100; width: 56px; height: 56px; border-radius: 50%; background: #206bc4; color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(32, 107, 196, 0.4); text-decoration: none;">
                <i class="ti ti-plus" style="font-size: 1.5rem;"></i>
            </button>
        </div>
        <?php endif; ?>

        <?php if ($hasSatinAlma): ?>
        <!-- 3. SATIN ALMA TABI -->
        <div class="tab-pane fade <?php echo $activeTab == 'satinalma' ? 'show active' : ''; ?>" id="tab-satinalma" role="tabpanel">
            <?php if (!empty($payments)): ?>
            <div class="sub-toolbar mb-3">
                <div class="d-flex gap-2 align-items-center">
                    <div class="flex-grow-1 position-relative">
                        <i class="ti ti-search sub-search-icon"></i>
                        <input type="text" class="form-control sub-search-input" id="pay-search" placeholder="Abone adı veya e-posta ara...">
                        <button type="button" class="sub-clear-btn d-none" id="pay-clear"><i class="ti ti-x"></i></button>
                    </div>
                    <div class="dropdown">
                        <button class="btn sub-action-btn" type="button" data-bs-toggle="dropdown" id="pay-filter-btn" title="Filtrele">
                            <i class="ti ti-filter"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end sub-dropdown" id="pay-filter-menu">
                            <li><h6 class="dropdown-header">Durum Filtresi</h6></li>
                            <li><button type="button" class="dropdown-item pay-filter-item active" data-val="all">Tümü</button></li>
                            <li><button type="button" class="dropdown-item pay-filter-item" data-val="basarili">Başarılı</button></li>
                            <li><button type="button" class="dropdown-item pay-filter-item" data-val="beklemede">Beklemede</button></li>
                            <li><button type="button" class="dropdown-item pay-filter-item" data-val="basarisiz">Başarısız</button></li>
                        </ul>
                    </div>
                    <div class="dropdown">
                        <button class="btn sub-action-btn" type="button" data-bs-toggle="dropdown" id="pay-sort-btn" title="Sırala">
                            <i class="ti ti-arrows-sort"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end sub-dropdown" id="pay-sort-menu">
                            <li><h6 class="dropdown-header">Sıralama</h6></li>
                            <li><button type="button" class="dropdown-item pay-sort-item active" data-val="date-desc">En Yeni</button></li>
                            <li><button type="button" class="dropdown-item pay-sort-item" data-val="date-asc">En Eski</button></li>
                            <li><button type="button" class="dropdown-item pay-sort-item" data-val="amount-desc">Tutar (Azalan)</button></li>
                            <li><button type="button" class="dropdown-item pay-sort-item" data-val="amount-asc">Tutar (Artan)</button></li>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($payments)): ?>
                <div class="text-center py-5">
                    <i class="ti ti-credit-card-off text-muted mb-2" style="font-size: 2.5rem; opacity: 0.5;"></i>
                    <p class="text-muted text-sm mb-0">Kayıtlı satış işlemi bulunamadı.</p>
                </div>
            <?php else: ?>
                <div id="satislar-list">
                <?php foreach ($payments as $pay): 
                    $encryptedPayId = Security::encrypt($pay->id);
                    $tutar_format = number_format($pay->tutar, 2, ',', '.') . ' ₺';
                    $odeme_tarihi = $pay->odeme_tarihi ? date('d.m.Y H:i', strtotime($pay->odeme_tarihi)) : '-';
                    
                    // Status Badge
                    $status_badge = '';
                    if ($pay->durum == 'basarili') {
                        $status_badge = '<span class="badge bg-success text-success-fg">Başarılı</span>';
                    } elseif ($pay->durum == 'basarisiz') {
                        $status_badge = '<span class="badge bg-danger text-danger-fg">Başarısız</span>';
                    } else {
                        $status_badge = '<span class="badge bg-warning text-warning-fg">Beklemede</span>';
                    }
                    ?>
                    <div class="mobile-card p-3"
                         data-pay-name="<?php echo mb_strtolower(htmlspecialchars($pay->subscriber_name ?? ''), 'UTF-8'); ?>"
                         data-pay-email="<?php echo mb_strtolower(htmlspecialchars($pay->subscriber_email ?? ''), 'UTF-8'); ?>"
                         data-pay-status="<?php echo htmlspecialchars($pay->durum ?? ''); ?>"
                         data-pay-date="<?php echo $pay->odeme_tarihi ? strtotime($pay->odeme_tarihi) : 0; ?>"
                         data-pay-amount="<?php echo (float)$pay->tutar; ?>">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="mb-0 text-bold text-dark" style="font-size: 0.9rem;"><?php echo htmlspecialchars($pay->subscriber_name ?? '-'); ?></h4>
                            <div>
                                <?php echo $status_badge; ?>
                            </div>
                        </div>
                        <p class="text-muted text-xs mb-3 border-bottom border-light pb-2"><?php echo htmlspecialchars($pay->subscriber_email ?? '-'); ?></p>

                        <div class="card-detail-item">
                            <span class="text-muted">Satın Alınan Paket:</span>
                            <span class="badge bg-blue-lt"><?php echo htmlspecialchars($pay->paket_adi ?? 'Bilinmeyen'); ?></span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Tutar:</span>
                            <span class="text-bold text-dark"><?php echo $tutar_format; ?></span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Ödeme Tarihi:</span>
                            <span class="text-semibold text-dark"><?php echo $odeme_tarihi; ?></span>
                        </div>
                        <div class="card-detail-item">
                            <span class="text-muted">Yöntem:</span>
                            <span class="text-secondary text-xs"><?php echo htmlspecialchars($pay->odeme_yontemi ?? '-'); ?></span>
                        </div>

                        <!-- İşlem Menüsü -->
                        <div class="d-flex justify-content-end gap-1 mt-3 pt-2 border-top border-light">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" style="border-radius: 8px; font-size: 0.75rem;">
                                    Durum Değiştir
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow" style="border-radius: 12px; font-size: 0.8rem;">
                                    <?php if ($pay->durum !== 'basarili'): ?>
                                        <li>
                                            <a class="dropdown-item change-status-btn-mobile d-flex align-items-center" href="#" data-id="<?php echo $encryptedPayId; ?>" data-status="basarili">
                                                <span class="status-dot bg-success me-2"></span> Başarılı Yap
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($pay->durum !== 'beklemede'): ?>
                                        <li>
                                            <a class="dropdown-item change-status-btn-mobile d-flex align-items-center" href="#" data-id="<?php echo $encryptedPayId; ?>" data-status="beklemede">
                                                <span class="status-dot bg-warning me-2"></span> Beklemede Yap
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php if ($pay->durum !== 'basarisiz'): ?>
                                        <li>
                                            <a class="dropdown-item change-status-btn-mobile d-flex align-items-center" href="#" data-id="<?php echo $encryptedPayId; ?>" data-status="basarisiz">
                                                <span class="status-dot bg-danger me-2"></span> Başarısız Yap
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <li class="dropdown-divider"></li>
                                    <li>
                                        <a class="dropdown-item delete-payment-mobile text-danger d-flex align-items-center" href="#" data-id="<?php echo $encryptedPayId; ?>">
                                            <i class="ti ti-trash me-2"></i> Satışı Sil
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div><!-- /#satislar-list -->
                <div class="sub-no-results d-none" id="satislar-no-results">
                    <i class="ti ti-search-off text-muted mb-2" style="font-size: 2rem; opacity: 0.5;"></i>
                    <p class="text-muted text-sm mb-0">Aramanızla eşleşen satış kaydı bulunamadı.</p>
                </div>
            <?php endif; ?>

            <!-- Floating Action Button for adding Transaction -->
            <button type="button" class="mobile-fab btn-add-transaction-mobile border-0" style="position: fixed; right: 20px; bottom: 80px; z-index: 100; width: 56px; height: 56px; border-radius: 50%; background: #206bc4; color: #fff; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(32, 107, 196, 0.4); text-decoration: none;">
                <i class="ti ti-plus" style="font-size: 1.5rem;"></i>
            </button>
        </div>
        <?php endif; ?>

    </div>
</div>


<!-- ---------------- MODALLAR ---------------- -->

<?php if ($hasPaketler): ?>
<!-- A. Mobil Paket Ekle / Güncelle Modalı -->
<div class="modal modal-blur fade modal-bottom-sheet" id="paketModalMobile" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 border-bottom border-light">
                <h5 class="modal-title font-weight-bold" id="paketModalTitleMobile" style="font-size: 1.1rem; display: flex; align-items: center; gap: 6px;">
                    <i class="ti ti-package text-primary" style="font-size: 1.3rem;"></i>
                    <span>Yeni Paket Ekle</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" id="paketFormMobile">
                <div class="modal-body p-4">
                    <input type="hidden" name="id" id="m_paket_id" value="0">
                    <input type="hidden" name="action" value="savePackage">

                    <div class="form-floating mb-3">
                        <input type="text" name="ad" id="m_paket_ad" class="form-control" placeholder="Paket Adı" required>
                        <label for="m_paket_ad">Paket Adı *</label>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="number" step="0.01" min="0" name="fiyat" id="m_paket_fiyat" class="form-control" placeholder="Fiyat" required>
                                <label for="m_paket_fiyat">Fiyat (₺) *</label>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="number" min="1" name="sure" id="m_paket_sure" class="form-control" placeholder="Süre (Gün)" required value="30">
                                <label for="m_paket_sure">Süre (Gün) *</label>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="number" min="1" name="firma_hakki" id="m_paket_firma_hakki" class="form-control" placeholder="Firma Limiti" required value="30">
                                <label for="m_paket_firma_hakki">Firma Limiti *</label>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="number" min="0" name="alt_kullanici_hakki" id="m_paket_alt_kullanici_hakki" class="form-control" placeholder="Alt Kullanıcı Limiti" required value="3">
                                <label for="m_paket_alt_kullanici_hakki">Kullanıcı Limiti *</label>
                            </div>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <textarea name="ozellikler" id="m_paket_ozellikler" class="form-control" style="height: 80px;" placeholder="Özellikler"></textarea>
                        <label for="m_paket_ozellikler">Özellikler (Açıklama)</label>
                    </div>

                    <div class="form-floating mb-3">
                        <select name="aktif_mi" id="m_paket_aktif_mi" class="form-select">
                            <option value="1">Aktif</option>
                            <option value="0">Pasif</option>
                        </select>
                        <label for="m_paket_aktif_mi">Durum *</label>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-link text-muted px-3 py-2" data-bs-dismiss="modal" style="text-decoration: none;">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 py-2" id="m_savePackageBtn" style="border-radius: 10px;">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($hasSatinAlma): ?>
<!-- B. Mobil Yeni Satış İşlemi Ekle Modalı -->
<div class="modal modal-blur fade modal-bottom-sheet" id="transactionModalMobile" tabindex="-1" style="display: none;" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header py-3 px-4 border-bottom border-light">
                <h5 class="modal-title font-weight-bold" style="font-size: 1.1rem; display: flex; align-items: center; gap: 6px;">
                    <i class="ti ti-shopping-cart text-primary" style="font-size: 1.3rem;"></i>
                    <span>Yeni İşlem Ekle</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" id="transactionFormMobile">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="addManualSale">

                    <!-- Kullanıcı Seçin -->
                    <div class="mb-3">
                        <label class="form-label required font-weight-semibold text-xs text-uppercase mb-1" style="color: #6e7687;">Kullanıcı Seçin</label>
                        <select name="kullanici_id" id="m_tx_kullanici_id" class="form-select select2-mobile" required>
                            <option value="">Kullanıcı seçiniz...</option>
                            <?php foreach ($subscribers as $sub): ?>
                                <option value="<?php echo Security::encrypt($sub->id); ?>">
                                    <?php echo htmlspecialchars($sub->full_name . ' (' . $sub->email . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Paket Seçin -->
                    <div class="mb-3">
                        <label class="form-label required font-weight-semibold text-xs text-uppercase mb-1" style="color: #6e7687;">Paket Seçin</label>
                        <select name="paket_id" id="m_tx_paket_id" class="form-select select2-mobile" required>
                            <option value="">Paket seçiniz...</option>
                            <?php foreach ($packages as $pkg): 
                                if ($pkg->aktif_mi != 1) continue;
                                ?>
                                <option value="<?php echo Security::encrypt($pkg->id); ?>"
                                        data-sure="<?php echo (int)$pkg->sure; ?>"
                                        data-firma_hakki="<?php echo (int)$pkg->firma_hakki; ?>"
                                        data-alt_kullanici_hakki="<?php echo (int)$pkg->alt_kullanici_hakki; ?>"
                                        data-fiyat="<?php echo htmlspecialchars($pkg->fiyat); ?>">
                                    <?php echo htmlspecialchars($pkg->ad . ' - ' . number_format($pkg->fiyat, 2, ',', '.') . ' ₺ (' . $pkg->sure . ' Gün)'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Firma Hakkı & Kullanıcı Hakkı -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="number" min="1" name="firma_hakki" id="m_tx_firma_hakki" class="form-control" placeholder="Firma Limiti" required>
                                <label for="m_tx_firma_hakki">Firma Hakkı *</label>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="number" min="0" name="alt_kullanici_hakki" id="m_tx_alt_kullanici_hakki" class="form-control" placeholder="Kullanıcı Limiti" required>
                                <label for="m_tx_alt_kullanici_hakki">Kullanıcı Hakkı *</label>
                            </div>
                        </div>
                    </div>

                    <!-- Başlangıç Tarihi & Bitiş Tarihi -->
                    <div class="row">
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="text" name="baslangic_tarihi" id="m_tx_baslangic_tarihi" class="form-control" placeholder="Başlangıç" required value="<?php echo date('d.m.Y'); ?>">
                                <label for="m_tx_baslangic_tarihi">Başlangıç Tarihi *</label>
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="form-floating">
                                <input type="text" name="bitis_tarihi" id="m_tx_bitis_tarihi" class="form-control" placeholder="Bitiş" required>
                                <label for="m_tx_bitis_tarihi">Bitiş Tarihi *</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between p-3">
                    <button type="button" class="btn btn-link text-muted px-3 py-2" data-bs-dismiss="modal" style="text-decoration: none;">Vazgeç</button>
                    <button type="submit" class="btn btn-primary px-4 py-2" id="m_saveTransactionBtn" style="border-radius: 10px;">İşlemi Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ---------------- JAVASCRIPT KODLARI ---------------- -->
<script>
$(document).ready(function() {
    if ($('#paketModalMobile').length) $('#paketModalMobile').appendTo('body');
    if ($('#transactionModalMobile').length) $('#transactionModalMobile').appendTo('body');
    if ($('.btn-add-paket-mobile').length) $('.btn-add-paket-mobile').appendTo('body');
    if ($('.btn-add-transaction-mobile').length) $('.btn-add-transaction-mobile').appendTo('body');

    // Sekme geçişlerinde Floating Action Button'ların doğru gösterimi
    function adjustFabVisibility() {
        // Tüm fab'leri gizle
        $('.mobile-fab').hide();
        
        // Aktif sekmeyi bul
        let activeTabId = $('#subscriptionTabs button.active').attr('id');
        if (activeTabId === 'paketler-tab') {
            $('.btn-add-paket-mobile').show();
        } else if (activeTabId === 'satinalma-tab') {
            $('.btn-add-transaction-mobile').show();
        }
    }

    // Sekme değişimini dinle
    $('#subscriptionTabs button').on('shown.bs.tab', function (e) {
        adjustFabVisibility();
    });

    // ---------------- Flatpickr ve Select2 Mobil Başlatıcıları ----------------
    let mFpStart = null;
    let mFpEnd = null;

    function initMobilePickers() {
        if (typeof flatpickr !== 'undefined') {
            // Başlangıç flatpickr
            mFpStart = flatpickr("#m_tx_baslangic_tarihi", {
                dateFormat: "d.m.Y",
                locale: "tr",
                disableMobile: "true",
                onChange: function() {
                    recalculateEndDateMobile();
                }
            });

            // Bitiş flatpickr
            mFpEnd = flatpickr("#m_tx_bitis_tarihi", {
                dateFormat: "d.m.Y",
                locale: "tr",
                disableMobile: "true"
            });
        }
    }

    // Modal açıldığında Select2'yi başlat
    $('#transactionModalMobile').on('shown.bs.modal', function () {
        initMobilePickers();
        if ($.fn.select2) {
            $('.select2-mobile').select2({
                dropdownParent: $('#transactionModalMobile'),
                width: '100%'
            });
        }
    });

    // Paket seçildiğinde otomatik limit ve tarih hesabı
    $(document).on('change', '#m_tx_paket_id', function() {
        let selectedOpt = $('option:selected', this);
        if (selectedOpt.val()) {
            let limitFirma = selectedOpt.data('firma_hakki');
            let limitUser = selectedOpt.data('alt_kullanici_hakki');
            
            $('#m_tx_firma_hakki').val(limitFirma);
            $('#m_tx_alt_kullanici_hakki').val(limitUser);
            
            recalculateEndDateMobile();
        } else {
            $('#m_tx_firma_hakki').val('');
            $('#m_tx_alt_kullanici_hakki').val('');
            if (mFpEnd) mFpEnd.setDate('');
        }
    });

    // Bitiş tarihi hesaplayıcı
    function recalculateEndDateMobile() {
        let selectedOpt = $('#m_tx_paket_id option:selected');
        if (!selectedOpt.val()) return;
        
        let duration = parseInt(selectedOpt.data('sure')) || 30;
        let startDateStr = $('#m_tx_baslangic_tarihi').val();
        if (!startDateStr) return;
        
        let parts = startDateStr.split('.');
        if (parts.length === 3) {
            let day = parseInt(parts[0], 10);
            let month = parseInt(parts[1], 10) - 1;
            let year = parseInt(parts[2], 10);
            
            let startDate = new Date(year, month, day);
            startDate.setDate(startDate.getDate() + duration);
            
            if (mFpEnd) {
                mFpEnd.setDate(startDate);
            } else {
                let endDay = String(startDate.getDate()).padStart(2, '0');
                let endMonth = String(startDate.getMonth() + 1).padStart(2, '0');
                let endYear = startDate.getFullYear();
                $('#m_tx_bitis_tarihi').val(`${endDay}.${endMonth}.${endYear}`);
            }
        }
    }


    // ---------------- PAKET İŞLEMLERİ (AJAX) ----------------
    
    // Paket Ekle Tıklama
    $(document).on('click', '.btn-add-paket-mobile', function(e) {
        e.preventDefault();
        $('#paketFormMobile')[0].reset();
        $('#m_paket_id').val('0');
        $('#paketModalTitleMobile span').text('Yeni Paket Ekle');
        $('#paketModalMobile').modal('show');
    });

    // Paket Düzenle Tıklama
    $(document).on('click', '.btn-edit-paket-mobile', function(e) {
        e.preventDefault();
        $('#paketFormMobile')[0].reset();
        
        let id = $(this).data('id');
        let ad = $(this).data('ad');
        let fiyat = $(this).data('fiyat');
        let sure = $(this).data('sure');
        let firma_hakki = $(this).data('firma_hakki');
        let alt_kullanici_hakki = $(this).data('alt_kullanici_hakki');
        let ozellikler = $(this).data('ozellikler');
        let aktif_mi = $(this).data('aktif_mi');

        $('#m_paket_id').val(id);
        $('#m_paket_ad').val(ad);
        $('#m_paket_fiyat').val(fiyat);
        $('#m_paket_sure').val(sure);
        $('#m_paket_firma_hakki').val(firma_hakki);
        $('#m_paket_alt_kullanici_hakki').val(alt_kullanici_hakki);
        $('#m_paket_ozellikler').val(ozellikler);
        $('#m_paket_aktif_mi').val(aktif_mi);

        $('#paketModalTitleMobile span').text('Paket Güncelle');
        $('#paketModalMobile').modal('show');
    });

    // Paket Formu Kaydet
    $(document).on('submit', '#paketFormMobile', function(e) {
        e.preventDefault();
        
        var form = $(this);
        form.validate({
            rules: {
                ad: { required: true },
                fiyat: { required: true, number: true },
                sure: { required: true, digits: true },
                firma_hakki: { required: true, digits: true },
                alt_kullanici_hakki: { required: true, digits: true }
            },
            messages: {
                ad: { required: "Paket adı boş bırakılamaz." },
                fiyat: { required: "Fiyat boş bırakılamaz.", number: "Geçerli bir sayı giriniz." },
                sure: { required: "Süre boş bırakılamaz.", digits: "Süre tam sayı olmalıdır." },
                firma_hakki: { required: "Firma limiti boş bırakılamaz.", digits: "Tam sayı olmalıdır." },
                alt_kullanici_hakki: { required: "Kullanıcı limiti boş bırakılamaz.", digits: "Tam sayı olmalıdır." }
            }
        });

        if (!form.valid()) {
            return;
        }

        let formData = new FormData(form[0]);

        fetch('/api/abonelik-islemleri/paketler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            let title = data.status === "success" ? "Başarılı" : "Hata";
            Swal.fire({
                title: title,
                text: data.message,
                icon: data.status
            }).then(() => {
                if (data.status === "success") {
                    $('#paketModalMobile').modal('hide');
                    window.location.reload();
                }
            });
        })
        .catch(error => {
            Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
        });
    });

    // Paket Sil
    $(document).on('click', '.btn-delete-paket-mobile', function(e) {
        e.preventDefault();
        let id = $(this).data('id');

        Swal.fire({
            title: "Emin misiniz?",
            text: "Seçilen abonelik paketi silinecektir! Bu paketi kullanan abonelikler etkilenebilir.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Evet, Sil!",
            cancelButtonText: "İptal",
            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append("action", "deletePackage");
                formData.append("id", id);

                fetch('/api/abonelik-islemleri/paketler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    let type = data.status === "success" ? "success" : "error";
                    let title = data.status === "success" ? "Başarılı!" : "Hata!";
                    Swal.fire({
                        title: title,
                        text: data.message,
                        icon: type
                    }).then(() => {
                        if (data.status === "success") {
                            window.location.reload();
                        }
                    });
                })
                .catch(error => {
                    Swal.fire("Hata", "Silme sırasında bir hata oluştu: " + error, "error");
                });
            }
        });
    });


    // ---------------- SATIŞ İŞLEMLERİ (AJAX) ----------------

    // Yeni Satış Ekle Butonu
    $(document).on('click', '.btn-add-transaction-mobile', function(e) {
        e.preventDefault();
        $('#transactionFormMobile')[0].reset();
        
        // Select2 temizleme
        $('#m_tx_kullanici_id').val('').trigger('change');
        $('#m_tx_paket_id').val('').trigger('change');
        
        // Başlangıç tarihini bugüne çek
        let today = new Date();
        if (mFpStart) mFpStart.setDate(today);
        if (mFpEnd) mFpEnd.setDate('');

        $('#transactionModalMobile').modal('show');
    });

    // Satış İşlemini Kaydet
    $(document).on('submit', '#transactionFormMobile', function(e) {
        e.preventDefault();
        
        var form = $(this);
        form.validate({
            rules: {
                kullanici_id: { required: true },
                paket_id: { required: true },
                firma_hakki: { required: true, digits: true },
                alt_kullanici_hakki: { required: true, digits: true },
                baslangic_tarihi: { required: true },
                bitis_tarihi: { required: true }
            },
            messages: {
                kullanici_id: { required: "Kullanıcı seçimi zorunludur." },
                paket_id: { required: "Paket seçimi zorunludur." },
                firma_hakki: { required: "Firma hakkı zorunludur.", digits: "Tam sayı olmalıdır." },
                alt_kullanici_hakki: { required: "Kullanıcı hakkı zorunludur.", digits: "Tam sayı olmalıdır." },
                baslangic_tarihi: { required: "Başlangıç tarihi zorunludur." },
                bitis_tarihi: { required: "Bitiş tarihi zorunludur." }
            }
        });

        if (!form.valid()) {
            return;
        }

        let formData = new FormData(form[0]);

        fetch('/api/abonelik-islemleri/odemeler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            let title = data.status === "success" ? "Başarılı" : "Hata";
            Swal.fire({
                title: title,
                text: data.message,
                icon: data.status
            }).then(() => {
                if (data.status === "success") {
                    $('#transactionModalMobile').modal('hide');
                    window.location.reload();
                }
            });
        })
        .catch(error => {
            Swal.fire("Hata", "İşlem sırasında bir hata oluştu: " + error, "error");
        });
    });

    // Satış Durumu Değiştirme
    $(document).on('click', '.change-status-btn-mobile', function(e) {
        e.preventDefault();
        let id = $(this).data("id");
        let status = $(this).data("status");
        let statusText = '';
        
        switch (status) {
            case 'basarili': statusText = 'Başarılı'; break;
            case 'basarisiz': statusText = 'Başarısız'; break;
            case 'beklemede': statusText = 'Beklemede'; break;
        }

        Swal.fire({
            title: "Emin misiniz?",
            text: `Ödeme işleminin durumunu '${statusText}' olarak değiştirmek istiyor musunuz?`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Evet, Değiştir!",
            cancelButtonText: "İptal",
            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append("action", "updatePaymentStatus");
                formData.append("id", id);
                formData.append("status", status);

                fetch("/api/abonelik-islemleri/odemeler.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    let iconType = data.status === "success" ? "success" : "error";
                    let titleText = data.status === "success" ? "Başarılı!" : "Hata!";
                    
                    Swal.fire({
                        title: titleText,
                        text: data.message,
                        icon: iconType
                    }).then(() => {
                        if (data.status === "success") {
                            window.location.reload();
                        }
                    });
                })
                .catch(error => {
                    Swal.fire("Hata", "Durum güncelleme sırasında hata oluştu: " + error, "error");
                });
            }
        });
    });

    // Satış Silme
    $(document).on('click', '.delete-payment-mobile', function(e) {
        e.preventDefault();
        let id = $(this).data("id");

        Swal.fire({
            title: "Emin misiniz?",
            text: "Seçilen satın alma işlemi ve ilişkili abonelik silinecektir! Bu işlemi geri alamazsınız.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Evet, Sil!",
            cancelButtonText: "İptal",
            background: $('body').attr('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: $('body').attr('data-bs-theme') === 'dark' ? '#f4f6fa' : '#1d273b'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append("action", "deletePayment");
                formData.append("id", id);

                fetch("/api/abonelik-islemleri/odemeler.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    let iconType = data.status === "success" ? "success" : "error";
                    let titleText = data.status === "success" ? "Başarılı!" : "Hata!";
                    
                    Swal.fire({
                        title: titleText,
                        text: data.message,
                        icon: iconType
                    }).then(() => {
                        if (data.status === "success") {
                            window.location.reload();
                        }
                    });
                })
                .catch(error => {
                    Swal.fire("Hata", "İşlem silinirken bir hata oluştu: " + error, "error");
                });
            }
        });
    });

    // ======== ARAMA / FİLTRE / SIRALAMA ========

    // --- Yardımcı: temizle butonu göster/gizle ---
    function toggleClearBtn(inputId, clearId) {
        var val = $('#' + inputId).val();
        $('#' + clearId).toggleClass('d-none', val.length === 0);
    }

    // --- Aboneler ---
    var abState = { search: '', filter: 'aktif', sort: 'name-asc' };

    function applyAboneler() {
        var s = abState.search;
        var f = abState.filter;
        var visible = 0;
        $('#aboneler-list').children('.mobile-card').each(function() {
            var name   = String($(this).attr('data-ab-name')   || '');
            var email  = String($(this).attr('data-ab-email')  || '');
            var status = String($(this).attr('data-ab-status') || '');
            var matchSearch = !s || name.indexOf(s) !== -1 || email.indexOf(s) !== -1;
            var matchFilter = f === 'all' || status === f;
            if (matchSearch && matchFilter) { $(this).show(); visible++; }
            else { $(this).hide(); }
        });
        $('#aboneler-no-results').toggleClass('d-none', visible > 0);
    }

    function sortAboneler() {
        var sv = abState.sort;
        var container = $('#aboneler-list');
        var cards = container.children('.mobile-card').toArray();
        cards.sort(function(a, b) {
            var an = String($(a).attr('data-ab-name') || '');
            var bn = String($(b).attr('data-ab-name') || '');
            var ad = parseInt($(a).attr('data-ab-days')) || -9999;
            var bd = parseInt($(b).attr('data-ab-days')) || -9999;
            if (sv === 'name-asc')  return an.localeCompare(bn, 'tr');
            if (sv === 'name-desc') return bn.localeCompare(an, 'tr');
            if (sv === 'days-desc') return bd - ad;
            if (sv === 'days-asc')  return ad - bd;
            return 0;
        });
        container.append(cards);
    }

    $('#ab-search').on('input', function() {
        abState.search = $(this).val().toLowerCase();
        toggleClearBtn('ab-search', 'ab-clear');
        applyAboneler();
    });
    $('#ab-clear').on('click', function() {
        $('#ab-search').val('').trigger('input');
    });

    $('#ab-filter-menu').on('click', '.ab-filter-item', function() {
        abState.filter = String($(this).attr('data-val'));
        $('#ab-filter-menu .ab-filter-item').removeClass('active');
        $(this).addClass('active');
        $('#ab-filter-btn').toggleClass('filter-active', abState.filter !== 'all');
        applyAboneler();
    });

    $('#ab-sort-menu').on('click', '.ab-sort-item', function() {
        abState.sort = String($(this).attr('data-val'));
        $('#ab-sort-menu .ab-sort-item').removeClass('active');
        $(this).addClass('active');
        $('#ab-sort-btn').addClass('filter-active');
        sortAboneler();
        applyAboneler();
    });

    // --- Paketler ---
    var pkgState = { search: '', filter: 'all', sort: 'name-asc' };

    function applyPaketler() {
        var s = pkgState.search;
        var f = pkgState.filter;
        var visible = 0;
        $('#paketler-list').children('.mobile-card').each(function() {
            var name   = String($(this).attr('data-pkg-name')   || '');
            var active = String($(this).attr('data-pkg-active') || '');
            var matchSearch = !s || name.indexOf(s) !== -1;
            var matchFilter = f === 'all' || active === f;
            if (matchSearch && matchFilter) { $(this).show(); visible++; }
            else { $(this).hide(); }
        });
        $('#paketler-no-results').toggleClass('d-none', visible > 0);
    }

    function sortPaketler() {
        var sv = pkgState.sort;
        var container = $('#paketler-list');
        var cards = container.children('.mobile-card').toArray();
        cards.sort(function(a, b) {
            var an = String($(a).attr('data-pkg-name') || '');
            var bn = String($(b).attr('data-pkg-name') || '');
            var ap = parseFloat($(a).attr('data-pkg-price')) || 0;
            var bp = parseFloat($(b).attr('data-pkg-price')) || 0;
            if (sv === 'name-asc')   return an.localeCompare(bn, 'tr');
            if (sv === 'name-desc')  return bn.localeCompare(an, 'tr');
            if (sv === 'price-asc')  return ap - bp;
            if (sv === 'price-desc') return bp - ap;
            return 0;
        });
        container.append(cards);
    }

    $('#pkg-search').on('input', function() {
        pkgState.search = $(this).val().toLowerCase();
        toggleClearBtn('pkg-search', 'pkg-clear');
        applyPaketler();
    });
    $('#pkg-clear').on('click', function() {
        $('#pkg-search').val('').trigger('input');
    });

    $('#pkg-filter-menu').on('click', '.pkg-filter-item', function() {
        pkgState.filter = String($(this).attr('data-val'));
        $('#pkg-filter-menu .pkg-filter-item').removeClass('active');
        $(this).addClass('active');
        $('#pkg-filter-btn').toggleClass('filter-active', pkgState.filter !== 'all');
        applyPaketler();
    });

    $('#pkg-sort-menu').on('click', '.pkg-sort-item', function() {
        pkgState.sort = String($(this).attr('data-val'));
        $('#pkg-sort-menu .pkg-sort-item').removeClass('active');
        $(this).addClass('active');
        $('#pkg-sort-btn').addClass('filter-active');
        sortPaketler();
        applyPaketler();
    });

    // --- Satışlar ---
    var payState = { search: '', filter: 'all', sort: 'date-desc' };

    function applySatislar() {
        var s = payState.search;
        var f = payState.filter;
        var visible = 0;
        $('#satislar-list').children('.mobile-card').each(function() {
            var name   = String($(this).attr('data-pay-name')   || '');
            var email  = String($(this).attr('data-pay-email')  || '');
            var status = String($(this).attr('data-pay-status') || '');
            var matchSearch = !s || name.indexOf(s) !== -1 || email.indexOf(s) !== -1;
            var matchFilter = f === 'all' || status === f;
            if (matchSearch && matchFilter) { $(this).show(); visible++; }
            else { $(this).hide(); }
        });
        $('#satislar-no-results').toggleClass('d-none', visible > 0);
    }

    function sortSatislar() {
        var sv = payState.sort;
        var container = $('#satislar-list');
        var cards = container.children('.mobile-card').toArray();
        cards.sort(function(a, b) {
            var ad = parseInt($(a).attr('data-pay-date'))    || 0;
            var bd = parseInt($(b).attr('data-pay-date'))    || 0;
            var aa = parseFloat($(a).attr('data-pay-amount')) || 0;
            var ba = parseFloat($(b).attr('data-pay-amount')) || 0;
            if (sv === 'date-desc')   return bd - ad;
            if (sv === 'date-asc')    return ad - bd;
            if (sv === 'amount-desc') return ba - aa;
            if (sv === 'amount-asc')  return aa - ba;
            return 0;
        });
        container.append(cards);
    }

    $('#pay-search').on('input', function() {
        payState.search = $(this).val().toLowerCase();
        toggleClearBtn('pay-search', 'pay-clear');
        applySatislar();
    });
    $('#pay-clear').on('click', function() {
        $('#pay-search').val('').trigger('input');
    });

    $('#pay-filter-menu').on('click', '.pay-filter-item', function() {
        payState.filter = String($(this).attr('data-val'));
        $('#pay-filter-menu .pay-filter-item').removeClass('active');
        $(this).addClass('active');
        $('#pay-filter-btn').toggleClass('filter-active', payState.filter !== 'all');
        applySatislar();
    });

    $('#pay-sort-menu').on('click', '.pay-sort-item', function() {
        payState.sort = String($(this).attr('data-val'));
        $('#pay-sort-menu .pay-sort-item').removeClass('active');
        $(this).addClass('active');
        $('#pay-sort-btn').addClass('filter-active');
        sortSatislar();
        applySatislar();
    });

    // --- Mobil Uyumlu Manuel Dropdown Tetikleyici (Bootstrap & Tabler Çakışmalarını Önler) ---
    $(document).on('click', '[data-bs-toggle="dropdown"]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        let $toggle = $(this);
        let $menu = $toggle.siblings('.dropdown-menu');
        let isOpen = $menu.hasClass('show');
        
        // Diğer tüm açık dropdown'ları kapat
        $('.dropdown-menu').removeClass('show');
        $('[data-bs-toggle="dropdown"]').removeClass('show').attr('aria-expanded', 'false');
        
        if (!isOpen) {
            $menu.addClass('show');
            $toggle.addClass('show').attr('aria-expanded', 'true');
        }
    });
    
    // Dropdown dışına tıklanınca kapat
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.dropdown').length) {
            $('.dropdown-menu').removeClass('show');
            $('[data-bs-toggle="dropdown"]').removeClass('show').attr('aria-expanded', 'false');
        }
    });

    // Bir seçeneğe tıklandığında dropdown'ı otomatik kapat
    $(document).on('click', '.dropdown-menu .dropdown-item, .dropdown-menu a', function() {
        $('.dropdown-menu').removeClass('show');
        $('[data-bs-toggle="dropdown"]').removeClass('show').attr('aria-expanded', 'false');
    });

    // --- İlk Yükleme Çalıştırmaları (Tüm Değişkenler ve Fonksiyonlar Tanımlandıktan Sonra) ---
    adjustFabVisibility();
    applyAboneler();
    sortAboneler();

});
</script>

