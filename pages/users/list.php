<?php
require_once "App/Helper/helper.php";
require_once "Model/UserModel.php";
require_once "Model/Projects.php";
require_once "Model/RolesModel.php";
require_once "App/Helper/security.php";

use App\Helper\Security;
use App\Helper\Helper;

$firm_id = (int)($_SESSION["firm_id"] ?? ($_SESSION["user"]->firm_id ?? 0));
$userObj = new UserModel();
$projectsObj = new Projects();
$rolesObj = new Roles();

$users = $userObj->getUsersByFirm($firm_id);
$firm_projects = $projectsObj->getProjectsByFirm($firm_id);
$firm_roles = $rolesObj->getRolesByFirm($firm_id);

$owner_id = (int)($_SESSION["user"]->parent_id == 0 ? $_SESSION["user"]->id : $_SESSION["user"]->parent_id);
$subDetails = $userObj->getActiveSubscriptionDetails($owner_id);
$currentSubUsers = $userObj->getSubUserCount($owner_id);
$isSuperadmin = ((int)($_SESSION["user"]->superadmin ?? 0) === 1);

$subUserLimit = (int)($subDetails['alt_kullanici_hakki'] ?? 1);
$limitReached = !$isSuperadmin && ($currentSubUsers >= $subUserLimit);

// Özet İstatistikleri
$total_users = count($users);
$active_users = 0;
$passive_users = 0;

foreach ($users as $u) {
    $isActive = ((string)($u->status ?? '') === '1' || strtolower((string)($u->status ?? '')) === 'aktif');
    if ($isActive) {
        $active_users++;
    } else {
        $passive_users++;
    }
}
?>

<script>
    // Sayfa render olmadan önce flicker'ı önlemek için gizlilik durumunu uygula
    (function() {
        var isSummaryVisible = localStorage.getItem('users_summary_cards_visible');
        if (isSummaryVisible === 'false') {
            document.write('<style>#usersSummaryCards { display: none !important; }</style>');
        }
    })();
</script>

<div class="container-xl mt-1" id="usersPage">

    <?php if (isset($_GET['limit_reached']) && $_GET['limit_reached'] == 1): ?>
        <div class="alert alert-warning alert-dismissible mb-3 shadow-sm border-0" role="alert" style="border-radius: 10px; font-weight: 500;">
            <div class="d-flex align-items-center">
                <i class="ti ti-alert-triangle icon me-3 text-warning" style="font-size: 1.5rem;"></i>
                <div>Paketinizin alt kullanıcı limiti dolduğu için yeni kullanıcı ekleme engellenmiştir.</div>
            </div>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="close"></a>
        </div>
    <?php endif; ?>

    <!-- Page Header (Standart Hero Banner) -->
    <div class="page-header d-print-none mb-3">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 44px; height: 44px;">
                        <i class="ti ti-user-shield" style="font-size: 24px;"></i>
                    </div>
                    <div>
                        <h2 class="page-title fw-bold text-dark" style="font-size: 1.25rem; letter-spacing: -0.3px;">
                            Kullanıcı Yönetimi
                        </h2>
                        <div class="text-secondary small mt-0.5" style="font-size: 12px;">
                            Sistemdeki tüm kullanıcı hesapları, roller, yetkilendirmeler ve erişim kontrolü
                        </div>
                    </div>
                </div>
            </div>
            <!-- Primary Actions -->
            <div class="col-auto ms-auto d-print-none">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary btn-icon users-header-icon-action" data-bs-toggle="dropdown" title="Sütunları Göster / Gizle" aria-label="Sütunları göster veya gizle" style="height: 32px; width: 32px;">
                            <i class="ti ti-layout-columns" style="font-size: 16px;"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end p-2" id="usersColvisMenu" style="min-width: 210px; max-height: 350px; overflow-y: auto;">
                            <!-- Checkboxlar JS ile dinamik yüklenecek -->
                        </div>
                    </div>

                    <?php if ($limitReached): ?>
                        <button type="button" class="btn btn-sm btn-dark shadow-sm btn-new-user-limit" data-limit="<?php echo $subUserLimit; ?>" style="background-color: #1e293b; border-color: #1e293b; height: 32px; padding: 4px 12px; font-size: 12.5px;">
                            <i class="ti ti-plus me-1"></i> Yeni Kullanıcı Ekle
                        </button>
                    <?php else: ?>
                        <button type="button" class="btn btn-sm btn-dark shadow-sm btn-new-user-trigger" data-bs-toggle="modal" data-bs-target="#userModal" style="background-color: #1e293b; border-color: #1e293b; height: 32px; padding: 4px 12px; font-size: 12.5px;">
                            <i class="ti ti-plus me-1"></i> Yeni Kullanıcı Ekle
                        </button>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" style="height: 32px; padding: 4px 10px; font-size: 12.5px;">
                            <i class="ti ti-settings me-1"></i> İşlemler
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <a href="#" class="dropdown-item" id="export_excel">
                                <i class="ti ti-file-excel icon me-2 text-success"></i> Excel'e Aktar
                            </a>
                            <div class="dropdown-divider my-1"></div>
                            <a class="dropdown-item route-link" href="#" data-page="users/roles/list">
                                <i class="ti ti-shield-lock icon me-2 text-primary"></i> Rol / Yetki Grupları
                            </a>
                            <a class="dropdown-item route-link" href="#" data-page="users/roles/manage">
                                <i class="ti ti-plus icon me-2 text-primary"></i> Yeni Rol Ekle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Özet Kartları (Tıklanabilir Toggle Filtreler) -->
    <div id="usersSummaryCards" class="summary-cards-wrapper mb-3">
        <div class="row row-cards g-3">
            <!-- 1. Toplam Kullanıcı -->
            <div class="col-sm-6 col-lg-3">
                <div class="user-stat-card active" data-filter-type="Tümü" data-tooltip="Tüm Kullanıcıları Göster">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="stat-card-title">TOPLAM KULLANICI</div>
                        <div class="stat-card-avatar text-primary">
                            <i class="ti ti-users"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="stat-card-value"><?php echo number_format($total_users, 0, ',', '.'); ?></div>
                        <span class="badge bg-secondary-lt text-secondary">Tüm Kayıtlar</span>
                    </div>
                    <div class="stat-card-subtext">
                        <span class="text-truncate">Sistemdeki Tanımlı Kullanıcılar</span>
                    </div>
                </div>
            </div>

            <!-- 2. Aktif Kullanıcılar -->
            <div class="col-sm-6 col-lg-3">
                <div class="user-stat-card" data-filter-type="Aktif" data-tooltip="Sadece Aktif Kullanıcıları Filtrele">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="stat-card-title">AKTİF KULLANICILAR</div>
                        <div class="stat-card-avatar text-success">
                            <i class="ti ti-user-check text-success"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="stat-card-value text-success"><?php echo number_format($active_users, 0, ',', '.'); ?></div>
                        <span class="badge bg-success-lt text-success">Aktif</span>
                    </div>
                    <div class="stat-card-subtext">
                        <span class="text-truncate">Sisteme Giriş Yapabilenler</span>
                    </div>
                </div>
            </div>

            <!-- 3. Pasif Kullanıcılar -->
            <div class="col-sm-6 col-lg-3">
                <div class="user-stat-card" data-filter-type="Pasif" data-tooltip="Sadece Pasif Kullanıcıları Filtrele">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="stat-card-title">PASİF KULLANICILAR</div>
                        <div class="stat-card-avatar text-danger">
                            <i class="ti ti-user-x text-danger"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="stat-card-value text-danger"><?php echo number_format($passive_users, 0, ',', '.'); ?></div>
                        <span class="badge bg-danger-lt text-danger">Pasif</span>
                    </div>
                    <div class="stat-card-subtext">
                        <span class="text-truncate">Erişimi Kısıtlanmış Hesaplar</span>
                    </div>
                </div>
            </div>

            <!-- 4. Alt Kullanıcı Limiti -->
            <div class="col-sm-6 col-lg-3">
                <div class="user-stat-card" data-filter-type="Tümü" data-tooltip="Paket Kapsamındaki Kullanıcı Kullanımı">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <div class="stat-card-title">KULLANICI LİMİTİ</div>
                        <div class="stat-card-avatar text-info">
                            <i class="ti ti-award text-info"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-baseline justify-content-between">
                        <div class="stat-card-value text-info">
                            <?php echo $isSuperadmin ? 'Sınırsız' : ($currentSubUsers . ' / ' . $subUserLimit); ?>
                        </div>
                        <span class="badge bg-info-lt text-info">Paket Hakkı</span>
                    </div>
                    <div class="stat-card-subtext">
                        <span class="text-truncate">
                            <?php echo $isSuperadmin ? 'Limitsiz Kullanım' : ('Kalan: ' . max(0, $subUserLimit - $currentSubUsers) . ' Kullanıcı'); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Kullanıcı Tablo Kartı -->
    <div class="row row-deck row-cards">
        <div class="col-12">
            <div class="card shadow-sm" style="border-radius: 12px; border: 1px solid #dbe3ec;">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 px-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="card-header-icon">
                            <i class="ti ti-list"></i>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <h4 class="card-title mb-0 fw-bold" style="font-size: 15px; letter-spacing: -0.2px;">Kullanıcı Listesi</h4>
                                <?php if ($limitReached): ?>
                                    <a href="#" class="btn-card-header-add btn-new-user-limit" data-limit="<?php echo $subUserLimit; ?>" data-tooltip="Yeni Kullanıcı Ekle">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="#" class="btn-card-header-add btn-new-user-trigger" data-bs-toggle="modal" data-bs-target="#userModal" data-tooltip="Yeni Kullanıcı Ekle">
                                        <i class="ti ti-plus"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted mb-0 font-11" style="font-size: 11.5px; line-height: 1.2;">Anlık arama, kullanıcı yetkilendirme ve hesap yönetimi</p>
                        </div>
                    </div>

                    <div class="d-flex align-items-center ms-md-3 my-1 my-md-0">
                        <div class="form-selectgroup">
                            <label class="form-selectgroup-item">
                                <input type="radio" name="user_status" value=""
                                    class="form-selectgroup-input status-filter" checked>
                                <span class="form-selectgroup-label btn-sm py-1 px-2">
                                    <i class="ti ti-users icon me-1"></i> Tümü
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="user_status" value="Aktif"
                                    class="form-selectgroup-input status-filter">
                                <span class="form-selectgroup-label btn-sm py-1 px-2">
                                    <i class="ti ti-user-check icon me-1 text-success"></i> Aktif
                                </span>
                            </label>
                            <label class="form-selectgroup-item">
                                <input type="radio" name="user_status" value="Pasif"
                                    class="form-selectgroup-input status-filter">
                                <span class="form-selectgroup-label btn-sm py-1 px-2">
                                    <i class="ti ti-user-x icon me-1 text-danger"></i> Pasif
                                </span>
                            </label>
                        </div>
                    </div>

                    <div class="d-flex align-items-center flex-wrap gap-2 ms-auto">
                        <!-- Hızlı Genel Arama Inputu -->
                        <div class="input-icon users-search-wrap" style="min-width: 180px;">
                            <span class="input-icon-addon">
                                <i class="ti ti-search text-muted"></i>
                            </span>
                            <input type="text" id="users-fast-search" class="form-control form-control-sm" placeholder="Kullanıcı ara..." autocomplete="off" style="height: 32px; font-size: 12.5px;">
                            <button type="button" id="users-search-clear" class="users-search-clear d-none" aria-label="Aramayı temizle" title="Aramayı temizle">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>

                        <button type="button" class="btn btn-sm btn-icon btn-outline-secondary" id="toggleSummaryCards" data-tooltip="Özet Kartlarını Gizle / Göster" style="height: 32px; width: 32px;">
                            <i class="ti ti-chevron-up" id="toggleSummaryIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="table-responsive" style="padding: 4px !important;">
                    <table class="table card-table table-hover text-nowrap w-100" id="userTable" style="width: 100% !important;">
                        <thead>
                            <tr>
                                <th style="width: 5%; min-width: 45px;" class="text-center" data-orderable="false">Sıra</th>
                                <th>Pozisyon / Rol</th>
                                <th>Adı Soyadı</th>
                                <th>Kullanıcı Adı</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th style="width: 10%; min-width: 90px;" class="text-center">Ana Kullanıcı</th>
                                <th style="width: 8%; min-width: 80px;" class="text-center">Durum</th>
                                <th class="no-export text-end" style="width: 7%; min-width: 80px;" data-orderable="false">İşlem</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 0;
                            foreach ($users as $user):
                                $i++;
                                $id = Security::encrypt($user->id);
                                $roleName = $userObj->roleName($user->user_roles ?? '');
                                $isMainUser = (int)($user->is_main_user ?? 0) === 1;
                                $isActive = ((string)($user->status ?? '') === '1' || strtolower((string)($user->status ?? '')) === 'aktif');
                                $statusText = $isActive ? 'Aktif' : 'Pasif';
                                $statusBadgeClass = $isActive ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger';
                                $statusDotClass = $isActive ? 'bg-success' : 'bg-danger';
                            ?>
                                <tr data-user-id="<?php echo $id; ?>"
                                    data-user-name="<?php echo htmlspecialchars($user->full_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                    data-user-status="<?php echo $statusText; ?>"
                                    data-is-main="<?php echo $isMainUser ? '1' : '0'; ?>"
                                    data-manage-page="users/manage&id=<?php echo $id; ?>">
                                    
                                    <td class="text-center fw-medium text-muted"><?php echo $i; ?></td>
                                    
                                    <td>
                                        <span class="badge bg-blue-lt text-blue py-1 px-2" style="font-size: 11.5px; font-weight: 500;">
                                            <i class="ti ti-shield me-1"></i><?php echo htmlspecialchars($roleName ?: 'Belirtilmemiş', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <span class="avatar avatar-xs me-2 rounded-circle bg-primary-lt fw-bold text-primary" style="font-size: 11px;">
                                                <?php echo htmlspecialchars(mb_strtoupper(mb_substr($user->full_name ?? 'U', 0, 1, 'UTF-8'), 'UTF-8'), ENT_QUOTES, 'UTF-8'); ?>
                                            </span>
                                            <div>
                                                <div class="fw-semibold text-dark"><?php echo htmlspecialchars($user->full_name ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php if (!empty($user->job)): ?>
                                                    <div class="text-muted" style="font-size: 11px;"><?php echo htmlspecialchars($user->job, ENT_QUOTES, 'UTF-8'); ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    
                                    <td>
                                        <span class="badge bg-muted-lt text-dark font-monospace py-1 px-2" style="font-size: 11px;">
                                            <?php echo htmlspecialchars($user->username ?? '-', ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    
                                    <td>
                                        <?php if (!empty($user->email)): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8'); ?>" class="text-secondary text-decoration-none d-inline-flex align-items-center">
                                                <i class="ti ti-mail text-muted me-1"></i><?php echo htmlspecialchars($user->email, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <?php if (!empty($user->phone)): ?>
                                            <a href="tel:<?php echo htmlspecialchars($user->phone, ENT_QUOTES, 'UTF-8'); ?>" class="text-secondary text-decoration-none d-inline-flex align-items-center">
                                                <i class="ti ti-phone text-muted me-1"></i><?php echo htmlspecialchars($user->phone, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <?php if ($isMainUser): ?>
                                            <span class="badge bg-warning-lt text-warning py-1 px-2" title="Ana Hesap / Hesap Sahibi">
                                                <i class="ti ti-crown me-1"></i> Ana Hesap
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-center">
                                        <span class="badge <?php echo $statusBadgeClass; ?> py-1 px-2" style="font-size: 11px; font-weight: 500;">
                                            <span class="status-dot <?php echo $statusDotClass; ?> me-1"></span>
                                            <?php echo $statusText; ?>
                                        </span>
                                    </td>
                                    
                                    <td class="text-end no-export">
                                        <div class="dropdown">
                                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="padding: 2px 8px; font-size: 11.5px;">
                                                İşlem
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                                <a class="dropdown-item route-link" data-page="users/manage&id=<?php echo $id; ?>" href="#">
                                                    <i class="ti ti-edit icon me-2 text-primary"></i> Güncelle
                                                </a>
                                                <?php if (!$isMainUser): ?>
                                                    <div class="dropdown-divider my-1"></div>
                                                    <a class="dropdown-item text-danger delete_user" data-id="<?php echo $id; ?>" href="#">
                                                        <i class="ti ti-trash icon me-2"></i> Sil
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     Yeni Kullanıcı Ekle Modalı
     ========================================================================== -->
<div class="modal modal-blur fade" id="userModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 780px;">
        <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
            <div class="modal-header border-bottom py-3 px-4 bg-light-subtle">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar avatar-md rounded-3 bg-primary-lt text-primary shadow-sm" style="width: 42px; height: 42px;">
                        <i class="ti ti-user-plus" style="font-size: 22px;"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" style="font-size: 16px; letter-spacing: -0.2px;">Yeni Kullanıcı Ekle</h5>
                        <div class="text-secondary small mt-0.5" style="font-size: 12.5px;">Sisteme yeni bir kullanıcı hesabı ve yetkilendirme tanımlayın</div>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            
            <form id="modalUserForm" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="id" id="modal_user_id" value="0">
                <input type="hidden" name="action" value="userSave">

                <div class="modal-body" style="padding: 1.75rem 2rem !important;">
                    <!-- Bölüm 1: Temel Hesap Bilgileri -->
                    <div class="mb-4 pb-1">
                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom" style="border-color: #e2e8f0 !important;">
                            <span class="avatar avatar-xs rounded-2 bg-primary-lt text-primary" style="width: 24px; height: 24px; font-size: 12px;">
                                <i class="ti ti-user"></i>
                            </span>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.6px;">Temel Hesap Bilgileri</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;" for="modal_full_name">
                                    Adı Soyadı <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-user text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control rounded-2" name="full_name" id="modal_full_name" placeholder="Örn: Ahmet Yılmaz" required style="height: 40px; font-size: 13.5px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;" for="modal_username">
                                    Kullanıcı Adı <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-at text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control rounded-2" name="username" id="modal_username" placeholder="Örn: ahmetyilmaz" autocomplete="off" required style="height: 40px; font-size: 13.5px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;" for="modal_email">
                                    E-posta Adresi <span class="text-danger">*</span>
                                </label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-mail text-muted"></i>
                                    </span>
                                    <input type="email" class="form-control rounded-2" name="email" id="modal_email" placeholder="ornek@sirket.com" autocomplete="off" required style="height: 40px; font-size: 13.5px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;" for="modal_password">
                                    Parola <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted px-3" style="border-right: none; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                                        <i class="ti ti-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" name="password" id="modal_password" placeholder="Parola belirleyiniz" autocomplete="new-password" required style="border-left: none; border-right: none; height: 40px; font-size: 13.5px;">
                                    <span class="input-group-text bg-white px-3" style="border-left: none; border-top-right-radius: 8px; border-bottom-right-radius: 8px;">
                                        <a href="javascript:void(0)" class="link-secondary toggle-password-visibility" data-target="#modal_password" title="Parolayı Göster / Gizle">
                                            <i class="ti ti-eye"></i>
                                        </a>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 2: İletişim & Görev Bilgileri -->
                    <div class="mb-4 pb-1">
                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom" style="border-color: #e2e8f0 !important;">
                            <span class="avatar avatar-xs rounded-2 bg-success-lt text-success" style="width: 24px; height: 24px; font-size: 12px;">
                                <i class="ti ti-phone"></i>
                            </span>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.6px;">İletişim & Görev Bilgileri</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;" for="modal_phone">
                                    Telefon Numarası
                                </label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-phone text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control rounded-2" name="phone" id="modal_phone" placeholder="05XX XXX XX XX" style="height: 40px; font-size: 13.5px;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;" for="modal_job">
                                    Mesleği / Görevi
                                </label>
                                <div class="input-icon">
                                    <span class="input-icon-addon">
                                        <i class="ti ti-briefcase text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control rounded-2" name="job" id="modal_job" placeholder="Örn: Proje Müdürü / Şantiye Şefi" style="height: 40px; font-size: 13.5px;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bölüm 3: Rol & Proje Yetkileri -->
                    <div>
                        <div class="d-flex align-items-center gap-2 pb-2 mb-3 border-bottom" style="border-color: #e2e8f0 !important;">
                            <span class="avatar avatar-xs rounded-2 bg-info-lt text-info" style="width: 24px; height: 24px; font-size: 12px;">
                                <i class="ti ti-shield-lock"></i>
                            </span>
                            <span class="text-muted fw-bold text-uppercase" style="font-size: 11.5px; letter-spacing: 0.6px;">Rol & Proje Yetkileri</span>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;" for="modal_user_roles">
                                    Kullanıcı Rolü <span class="text-danger">*</span>
                                </label>
                                <select class="form-select select2" name="user_roles[]" id="modal_user_roles" multiple="multiple" data-placeholder="Rol seçiniz" style="width: 100%;" required>
                                    <?php foreach ($firm_roles as $role): ?>
                                        <option value="<?php echo (int)$role->id; ?>"><?php echo htmlspecialchars($role->roleName, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="text-muted mt-1.5" style="font-size: 11.5px;">Kullanıcının sistem yetkilerini belirler</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold text-dark mb-1.5" style="font-size: 12.5px;" for="modal_responsible_projects">
                                    Sorumlu Olduğu Projeler
                                </label>
                                <select class="form-select select2" name="responsible_projects[]" id="modal_responsible_projects" multiple="multiple" data-placeholder="Tüm projeler için boş bırakınız" style="width: 100%;">
                                    <?php foreach ($firm_projects as $prj): ?>
                                        <option value="<?php echo (int)$prj->id; ?>"><?php echo htmlspecialchars($prj->project_name, ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="text-muted mt-1.5" style="font-size: 11.5px;">Boş bırakılırsa tüm projelere erişir</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer bg-light-subtle py-3 px-4 border-top d-flex justify-content-between align-items-center">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" style="height: 38px; padding: 6px 18px; font-size: 13px; border-radius: 8px;">
                        <i class="ti ti-x me-1"></i> Vazgeç
                    </button>
                    <button type="submit" class="btn btn-dark shadow-sm" id="btnSaveUserModal" style="height: 38px; padding: 6px 22px; font-size: 13px; font-weight: 600; border-radius: 8px; background-color: #1e293b; border-color: #1e293b;">
                        <i class="ti ti-device-floppy me-1.5"></i> Kullanıcıyı Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ==========================================================================
   Özet Kartları (Stat Cards)
   ========================================================================== */
.user-stat-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 12px 14px;
    position: relative;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    user-select: none;
}
.user-stat-card:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    transform: translateY(-1px);
}
.user-stat-card.active {
    border-color: #206bc4;
    background: #f8fafc;
    box-shadow: 0 0 0 1px #206bc4, 0 4px 6px -1px rgba(32, 107, 196, 0.1);
}
.stat-card-title {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.stat-card-value {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
    line-height: 1.2;
}
.stat-card-avatar {
    font-size: 18px;
    opacity: 0.85;
}
.stat-card-subtext {
    font-size: 11.5px;
    color: #94a3b8;
    margin-top: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ==========================================================================
   Tablo & Sarmalayıcı (4px Eşit Dış Boşluk & Yuvarlak Köşeler)
   ========================================================================== */
.card .table-responsive {
    padding: 4px !important;
    margin: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
    overflow-x: auto !important;
}

#userTable_wrapper {
    width: 100% !important;
    margin: 0 !important;
    padding: 0 !important;
}

table#userTable.data-table,
table#userTable.dataTable,
table#userTable.card-table {
    border-collapse: separate !important;
    border-spacing: 0 !important;
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    overflow: hidden !important;
    width: 100% !important;
    min-width: 100% !important;
    margin: 0 !important;
}

table#userTable thead tr:first-child th:first-child { border-top-left-radius: 9px !important; }
table#userTable thead tr:first-child th:last-child { border-top-right-radius: 9px !important; }
table#userTable tbody tr:last-child td:first-child { border-bottom-left-radius: 9px !important; }
table#userTable tbody tr:last-child td:last-child { border-bottom-right-radius: 9px !important; }

/* Başlık Hücreleri */
table#userTable thead th {
    background: #f8fafc !important;
    color: #475569 !important;
    font-weight: 600 !important;
    font-size: 12px !important;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 10px 12px !important;
    border-bottom: 1px solid #cbd5e1 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
    vertical-align: middle !important;
}
table#userTable thead th:last-child {
    border-right: none !important;
}

/* Sütun Arama Satırını Gizle / Kaldır */
.search-input-row {
    display: none !important;
}

/* Hızlı Arama Kutusu & Temizleme Butonu */
.users-search-wrap {
    position: relative;
}
.users-search-clear {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: #e2e8f0;
    color: #64748b;
    border-radius: 50%;
    width: 18px;
    height: 18px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    cursor: pointer;
    line-height: 1;
    z-index: 4;
}
.users-search-clear:hover {
    background: #cbd5e1;
    color: #1e293b;
}

/* Gövde Satırları */
table#userTable tbody td {
    padding: 8px 12px !important;
    font-size: 13.5px !important;
    color: #1e293b !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #e2e8f0 !important;
    border-right: 1px solid #e2e8f0 !important;
    border-top: none !important;
    border-left: none !important;
}
table#userTable tbody td:last-child {
    border-right: none !important;
}
table#userTable tbody tr:last-child td {
    border-bottom: none !important;
}
table#userTable tbody tr:hover td {
    background-color: #f8fafc !important;
}

/* ==========================================================================
   User Modal Select2 Styling
   ========================================================================== */
#userModal .select2-container {
    width: 100% !important;
}
#userModal .select2-container--default .select2-selection--multiple {
    min-height: 40px !important;
    border: 1px solid #dce1e7 !important;
    border-radius: 8px !important;
    padding: 3px 8px !important;
    background-color: #ffffff !important;
}
#userModal .select2-container--default.select2-container--focus .select2-selection--multiple {
    border-color: #90b5e2 !important;
    box-shadow: 0 0 0 0.25rem rgba(32, 107, 196, 0.25) !important;
}
#userModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #eef3f8 !important;
    border: 1px solid #dbe3ec !important;
    border-radius: 5px !important;
    padding: 3px 10px !important;
    font-size: 12.5px !important;
    font-weight: 500 !important;
    color: #1e293b !important;
    margin-top: 3px !important;
    margin-bottom: 3px !important;
}
#userModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #64748b !important;
    margin-right: 6px !important;
    border-right: 1px solid #cbd5e1 !important;
    padding-right: 5px !important;
}
#userModal .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #d63939 !important;
    background: transparent !important;
}
#userModal .select2-container--default .select2-search--inline .select2-search__field {
    margin-top: 4px !important;
    font-size: 13.5px !important;
    height: 26px !important;
}
#userModal .select2-dropdown {
    border-color: #cbd5e1 !important;
    border-radius: 8px !important;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12) !important;
    z-index: 1065 !important;
}
#userModal .select2-results__option {
    padding: 8px 14px !important;
    font-size: 13.5px !important;
}
#userModal .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #206bc4 !important;
    color: #ffffff !important;
}

/* ==========================================================================
   Dark Mode Desteği
   ========================================================================== */
[data-bs-theme="dark"] .user-stat-card {
    background: #1e293b;
    border-color: #334155;
}
[data-bs-theme="dark"] .user-stat-card:hover {
    border-color: #475569;
}
[data-bs-theme="dark"] .user-stat-card.active {
    background: #0f172a;
    border-color: #3b82f6;
    box-shadow: 0 0 0 1px #3b82f6, 0 4px 6px -1px rgba(59, 130, 246, 0.2);
}
[data-bs-theme="dark"] .stat-card-title {
    color: #94a3b8;
}
[data-bs-theme="dark"] .stat-card-value {
    color: #f8fafc;
}
[data-bs-theme="dark"] table#userTable.dataTable,
[data-bs-theme="dark"] table#userTable.card-table {
    border-color: #334155 !important;
}
[data-bs-theme="dark"] table#userTable thead th {
    background: #1e293b !important;
    color: #94a3b8 !important;
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
}
[data-bs-theme="dark"] table#userTable tbody td {
    border-bottom-color: #334155 !important;
    border-right-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] table#userTable tbody tr:hover td {
    background-color: rgba(255, 255, 255, 0.04) !important;
}
[data-bs-theme="dark"] .fw-semibold.text-dark {
    color: #f8fafc !important;
}
[data-bs-theme="dark"] .users-search-clear {
    background: #334155;
    color: #94a3b8;
}

[data-bs-theme="dark"] #userModal .modal-content {
    background: #182433;
    border-color: #334155;
}
[data-bs-theme="dark"] #userModal .modal-header,
[data-bs-theme="dark"] #userModal .modal-footer {
    background: #151f2c !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #userModal .input-group-text,
[data-bs-theme="dark"] #userModal input.form-control {
    background-color: #0f172a !important;
    border-color: #334155 !important;
    color: #f8fafc !important;
}
[data-bs-theme="dark"] #userModal .select2-container--default .select2-selection--multiple {
    background-color: #0f172a !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #userModal .select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #1e293b !important;
    border-color: #334155 !important;
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] #userModal .select2-dropdown {
    background-color: #1e293b !important;
    border-color: #334155 !important;
}
[data-bs-theme="dark"] #userModal .select2-results__option {
    color: #e2e8f0 !important;
}
[data-bs-theme="dark"] #userModal .select2-container--default .select2-results__option--highlighted[aria-selected] {
    background-color: #3b82f6 !important;
    color: #ffffff !important;
}

/* Context Menu — Aktif Satır Vurgusu */
#userTable tbody tr.context-menu-active > td {
    background-color: #eff6ff !important;
}
[data-bs-theme="dark"] #userTable tbody tr.context-menu-active > td {
    background-color: #1e3a5f !important;
}

/* Context Menu — Durum Satırı */
.custom-context-menu .cm-status-row {
    display: flex;
    align-items: center;
    padding: 5px 14px 8px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 4px;
}
[data-bs-theme="dark"] .custom-context-menu .cm-status-row {
    border-bottom-color: #334155;
}
</style>

<script>
function initUserModalSelect2() {
    if ($.fn.select2) {
        if ($('#modal_user_roles').hasClass('select2-hidden-accessible')) {
            $('#modal_user_roles').select2('destroy');
        }
        $('#modal_user_roles').select2({
            dropdownParent: $('#userModal'),
            placeholder: 'Rol seçiniz',
            width: '100%',
            allowClear: true
        });

        if ($('#modal_responsible_projects').hasClass('select2-hidden-accessible')) {
            $('#modal_responsible_projects').select2('destroy');
        }
        $('#modal_responsible_projects').select2({
            dropdownParent: $('#userModal'),
            placeholder: 'Tüm projeler için boş bırakınız',
            width: '100%',
            allowClear: true
        });
    }
}

$(document).ready(function() {
    var table = null;

    var columnConfig = {
        1: { label: 'Pozisyon / Rol', default: true },
        2: { label: 'Adı Soyadı', default: true },
        3: { label: 'Kullanıcı Adı', default: true },
        4: { label: 'Email', default: true },
        5: { label: 'Telefon', default: true },
        6: { label: 'Ana Kullanıcı', default: true },
        7: { label: 'Durum', default: true }
    };

    var savedVisibility = {};
    try {
        savedVisibility = JSON.parse(localStorage.getItem('users_column_visibility') || '{}');
    } catch(e) {
        savedVisibility = {};
    }

    function completeUserTableInitialization(api) {
        $.each(columnConfig, function(colIdx, conf) {
            colIdx = parseInt(colIdx, 10);
            var isVisible = Object.prototype.hasOwnProperty.call(savedVisibility, colIdx)
                ? !!savedVisibility[colIdx]
                : conf.default;
            api.column(colIdx).visible(isVisible, false);
        });

        api.columns.adjust().draw(false);
    }

    if (typeof window.createDataTable === 'function') {
        table = window.createDataTable('#userTable', {
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            order: [],
            disableSearchRow: true,
            noSearchRow: true,
            columnDefs: [
                { targets: [0, 8], orderable: false, searchable: false },
                { targets: [0, 6, 7], className: 'text-center' },
                { targets: 8, width: '80px', className: 'text-end no-export actions-column' }
            ],
            initComplete: function() {
                $('#userTable thead .search-input-row').remove();
                completeUserTableInitialization(this.api());
                if (typeof window.initDataTableColumnFilters === 'function') {
                    window.initDataTableColumnFilters($('#userTable'), this.api());
                }
            },
            skipSearch: ['Sıra', 'Ana Kullanıcı', 'İşlem', 'İşlemler']
        });
    } else if ($.fn.DataTable) {
        table = $('#userTable').DataTable({
            autoWidth: false,
            pageLength: 25,
            order: [],
            language: { url: 'src/tr.json' },
            columnDefs: [
                { targets: [0, 8], orderable: false, searchable: false },
                { targets: [0, 6, 7], className: 'text-center' },
                { targets: 8, width: '80px', className: 'text-end no-export actions-column' }
            ],
            initComplete: function() {
                $('#userTable thead .search-input-row').remove();
                completeUserTableInitialization(this.api());
                if (typeof window.initDataTableColumnFilters === 'function') {
                    window.initDataTableColumnFilters($('#userTable'), this.api());
                }
            }
        });
    }

    // Arama satırını kaldır
    $('#userTable thead .search-input-row').remove();

    var $colvisMenu = $('#usersColvisMenu');
    $colvisMenu.empty();

    if (table) {
        $.each(columnConfig, function(colIdx, conf) {
            colIdx = parseInt(colIdx, 10);
            var isVisible = savedVisibility.hasOwnProperty(colIdx) ? !!savedVisibility[colIdx] : conf.default;

            var $item = $(
                '<label class="dropdown-item d-flex align-items-center py-1 px-2 cursor-pointer">' +
                '<input type="checkbox" class="form-check-input me-2 mt-0 col-toggle-cb" data-column="' + colIdx + '"' + (isVisible ? ' checked' : '') + '>' +
                '<span style="font-size: 13px;">' + conf.label + '</span>' +
                '</label>'
            );
            $colvisMenu.append($item);
        });
    }

    // Checkbox değiştiğinde sütunu göster/gizle ve kaydet
    $colvisMenu.on('change', '.col-toggle-cb', function(e) {
        e.stopPropagation();
        var colIdx = parseInt($(this).data('column'), 10);
        var isChecked = $(this).is(':checked');

        table.column(colIdx).visible(isChecked, true);

        savedVisibility[colIdx] = isChecked;
        try {
            localStorage.setItem('users_column_visibility', JSON.stringify(savedVisibility));
        } catch(err) {}
    });

    $colvisMenu.on('click', function(e) {
        e.stopPropagation();
    });

    // Hızlı Genel Arama Inputu
    var searchTimer = null;
    $('#users-fast-search').on('input', function() {
        var val = this.value;
        $('#users-search-clear').toggleClass('d-none', val.length === 0);
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() {
            if (table) {
                table.search(val).draw();
            }
        }, 250);
    });

    $('#users-search-clear').on('click', function() {
        clearTimeout(searchTimer);
        $('#users-fast-search').val('').trigger('focus');
        $(this).addClass('d-none');
        if (table) {
            table.search('').draw();
        }
    });

    // Özet Kartları Görünürlük Durumu & Aç/Kapa (Toggle)
    var isSummaryVisible = localStorage.getItem('users_summary_cards_visible');
    if (isSummaryVisible === 'false') {
        $('#usersSummaryCards').hide();
        $('#toggleSummaryIcon').removeClass('ti-chevron-up').addClass('ti-chevron-down');
    }

    $('#toggleSummaryCards').on('click', function() {
        var $cards = $('#usersSummaryCards');
        var $icon = $('#toggleSummaryIcon');
        
        $cards.slideToggle(200, function() {
            var isVisible = $cards.is(':visible');
            localStorage.setItem('users_summary_cards_visible', isVisible ? 'true' : 'false');
            if (isVisible) {
                $icon.removeClass('ti-chevron-down').addClass('ti-chevron-up');
            } else {
                $icon.removeClass('ti-chevron-up').addClass('ti-chevron-down');
            }
        });
    });

    // Durum Filtresi (Radio Butonlar)
    $('.status-filter').on('change', function() {
        var val = $(this).val();
        applyStatusFilter(val);
    });

    // Özet Kartına Tıklandığında Filtreleme
    $('.user-stat-card').on('click', function() {
        var filterType = $(this).data('filter-type');
        $('.user-stat-card').removeClass('active');
        $(this).addClass('active');

        if (filterType === 'Aktif') {
            $('input[name="user_status"][value="Aktif"]').prop('checked', true);
            applyStatusFilter('Aktif');
        } else if (filterType === 'Pasif') {
            $('input[name="user_status"][value="Pasif"]').prop('checked', true);
            applyStatusFilter('Pasif');
        } else {
            $('input[name="user_status"][value=""]').prop('checked', true);
            applyStatusFilter('');
        }
    });

    function applyStatusFilter(status) {
        if (!table) return;
        
        // Tablodaki Durum sütun indexi = 7
        if (status === '' || status === 'Tümü') {
            table.column(7).search('').draw();
        } else {
            table.column(7).search(status).draw();
        }

        // Aktif kartı güncelle
        $('.user-stat-card').removeClass('active');
        if (status === 'Aktif') {
            $('.user-stat-card[data-filter-type="Aktif"]').addClass('active');
        } else if (status === 'Pasif') {
            $('.user-stat-card[data-filter-type="Pasif"]').addClass('active');
        } else {
            $('.user-stat-card[data-filter-type="Tümü"]').first().addClass('active');
        }
    }

    // Excel Dışa Aktarma
    $('#export_excel').on('click', function(e) {
        e.preventDefault();
        if (table && table.button) {
            table.button('.buttons-excel').trigger();
        }
    });

    // Modal Lifecycle
    $('#userModal').on('show.bs.modal shown.bs.modal', function () {
        initUserModalSelect2();
        setTimeout(function() {
            $('#modal_full_name').focus();
        }, 120);
    });

    // ========================================================================
    // Tabloda Sağ Tık (Custom Context Menu)
    // ========================================================================
    $(document).on('contextmenu', '#userTable tbody tr', function(e) {
        e.preventDefault();

        var $tr = $(this);
        var userName   = $tr.data('user-name')  || 'Kullanıcı İşlemleri';
        var userStatus = $tr.data('user-status') || '';
        var managePage = $tr.data('manage-page') || '';
        var userId     = $tr.data('user-id')     || '';
        var isMain     = parseInt($tr.data('is-main') || 0, 10) === 1;

        $('#userTable tbody tr').removeClass('context-menu-active');
        $tr.addClass('context-menu-active');

        var $menu = $('#usersContextMenu');
        if (!$menu.length) {
            $menu = $('<div id="usersContextMenu" class="custom-context-menu"></div>').appendTo('body');
        }

        var statusIcon    = (userStatus === 'Aktif') ? 'ti-user-check' : 'ti-user-off';
        var statusLabel   = (userStatus === 'Aktif') ? 'Aktif Kullanıcı' : 'Pasif Kullanıcı';
        var statusCls     = (userStatus === 'Aktif') ? 'text-success' : 'text-secondary';

        var menuHtml = `
            <div class="cm-header">
                <i class="ti ti-user-shield me-1"></i>
                ${$('<div>').text(userName).html()}
            </div>
            <div class="cm-status-row">
                <i class="ti ${statusIcon} me-1 ${statusCls}"></i>
                <span class="${statusCls}" style="font-size:11.5px;">${statusLabel}</span>
            </div>
            ${managePage ? `
            <a href="#" class="route-link" data-page="${managePage}">
                <i class="ti ti-edit"></i> Detay / Düzenle
            </a>
            ` : ''}
            <div class="cm-divider"></div>
            <a href="#" class="cm-copy-name" data-name="${$('<div>').text(userName).html()}">
                <i class="ti ti-copy"></i> Adı Kopyala
            </a>
            ${(!isMain && userId) ? `
            <div class="cm-divider"></div>
            <a href="#" class="cm-danger delete_user" data-id="${userId}">
                <i class="ti ti-trash"></i> Kullanıcıyı Sil
            </a>
            ` : ''}
        `;

        $menu.html(menuHtml);
        $menu.css({ display: 'block', opacity: 0 });

        var menuW  = $menu.outerWidth();
        var menuH  = $menu.outerHeight();
        var clickX = e.clientX;
        var clickY = e.clientY;
        var winW   = $(window).width();
        var winH   = $(window).height();

        var posX = (clickX + menuW > winW) ? winW - menuW - 10 : clickX;
        var posY = (clickY + menuH > winH) ? winH - menuH - 10 : clickY;

        $menu.css({ top: posY + 'px', left: posX + 'px', opacity: 1 });
    });

    // Dışarı tıklanınca menüyü kapat
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#usersContextMenu').length) {
            $('#usersContextMenu').hide();
            $('#userTable tbody tr').removeClass('context-menu-active');
        }
    });

    // Menü içinden tıklanınca kapat
    $(document).on('click', '#usersContextMenu a', function() {
        $('#usersContextMenu').hide();
        $('#userTable tbody tr').removeClass('context-menu-active');
    });

    // Adı Kopyala
    $(document).on('click', '#usersContextMenu .cm-copy-name', function(e) {
        e.preventDefault();
        var name = $(this).data('name') || '';
        if (navigator.clipboard && name) {
            navigator.clipboard.writeText(name).then(function() {
                Swal.fire({
                    toast: true,
                    position: 'bottom-end',
                    icon: 'success',
                    title: 'Ad panoya kopyalandı',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });
            });
        }
    });

    // Scroll / resize / blur → menüyü kapat
    $(window).on('scroll resize blur', function() {
        $('#usersContextMenu').hide();
        $('#userTable tbody tr').removeClass('context-menu-active');
    });
});
</script>
