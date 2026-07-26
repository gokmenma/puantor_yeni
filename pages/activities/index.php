<?php
// Auths, Menus ve Helper modelini dahil et
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/Model/Menus.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/App/Helper/helper.php";
require_once ROOT . "/Service/SystemLogService.php";

use App\Helper\Helper;

$authsObj = new Auths();
$menusObj = new Menus();
$db = $authsObj->getDb();

// Veritabanı Yapı Yaması: auths tablosundaki id tinyint(4) olduğu için maksimum 127 kayıt sınırı vardır.
// Yeni yetki eklenebilmesi için bu kolonu INT(11) tipine yükseltiyoruz.
try {
    $stmtColumnCheck = $db->prepare("DESCRIBE auths id");
    $stmtColumnCheck->execute();
    $columnInfo = $stmtColumnCheck->fetch(PDO::FETCH_OBJ);
    if ($columnInfo && strpos(strtolower($columnInfo->Type), 'tinyint') !== false) {
        $db->exec("ALTER TABLE auths MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
        $db->exec("ALTER TABLE auths MODIFY parent_id INT(11) DEFAULT NULL");
    }
} catch (Exception $e) {
    system_log_exception($e, ['operation' => 'auths_id_schema_patch']);
}

// 1. Yetki Kontrolü için Yetki Ekle
$sqlAuthCheck = $db->prepare("SELECT id FROM auths WHERE auth_name = 'system_activities_view' LIMIT 1");
$sqlAuthCheck->execute();
$authNode = $sqlAuthCheck->fetch(PDO::FETCH_OBJ);

if (!$authNode) {
    // Bulunamadıysa yetkiyi ekle
    $sqlAuthInsert = $db->prepare("INSERT INTO auths (title, auth_name, description, parent_id, is_active) VALUES ('Sistem Aktiviteleri', 'system_activities_view', 'Sistem aktivitelerini görüntüleme yetkisi.', 0, 1)");
    $sqlAuthInsert->execute();
    $authNodeId = $db->lastInsertId();
} else {
    $authNodeId = $authNode->id;
}

// 2. Menü Elemanını Ekle
$sqlMenuCheck = $db->prepare("SELECT id FROM menu WHERE page_link = 'activities/index' LIMIT 1");
$sqlMenuCheck->execute();
$menuNode = $sqlMenuCheck->fetch(PDO::FETCH_OBJ);

if (!$menuNode) {
    // Bulunamadıysa menüyü ekle
    $sqlMenuInsert = $db->prepare("INSERT INTO menu (page_name, page_link, icon, parent_id, isActive, isMenu, index_no, is_authorize) VALUES ('Sistem Aktiviteleri', 'activities/index', 'activity', 0, 1, 1, 12, 1)");
    $sqlMenuInsert->execute();
}

// 3. Mevcut kullanıcının (admin/main user ise) yetki grubuna bu yetkiyi ekle
if (isset($_SESSION['user']->user_roles)) {
    $role_ids_arr = array_values(array_filter(array_map('intval', explode(',', $_SESSION['user']->user_roles))));
    $role_id = $role_ids_arr[0] ?? null;

    $sqlRoleCheck = $db->prepare("SELECT auth_ids FROM role_auths WHERE role_id = ? LIMIT 1");
    $sqlRoleCheck->execute([$role_id]);
    $roleAuth = $sqlRoleCheck->fetch(PDO::FETCH_OBJ);
    
    if ($roleAuth) {
        $auth_ids_arr = explode(',', $roleAuth->auth_ids);
        if (!in_array($authNodeId, $auth_ids_arr)) {
            $auth_ids_arr[] = $authNodeId;
            $new_auth_ids = implode(',', $auth_ids_arr);
            $sqlUpdateRole = $db->prepare("UPDATE role_auths SET auth_ids = ? WHERE role_id = ?");
            $sqlUpdateRole->execute([$new_auth_ids, $role_id]);
        }
    }
}

// Yetki kontrolü yapalım
$authsObj->checkAuthorize("system_activities_view");

// Yönetici (Superadmin) Kontrolü
$is_superadmin = (isset($_SESSION['user']->superadmin) && $_SESSION['user']->superadmin == 1);
$allowed_tabs = ['analiz', 'aktiviteler', 'girisler', 'kullanici-rapor'];
if ($is_superadmin) {
    $allowed_tabs[] = 'sistem-hatalari';
}
$active_tab = in_array($_GET['tab'] ?? '', $allowed_tabs, true) ? $_GET['tab'] : 'analiz';

// Filtre Değişkenleri
$firm_id = $_SESSION['firm_id'];
$company_id_filter = isset($_GET['company_id']) ? $_GET['company_id'] : '';
$user_id_filter = isset($_GET['user_id']) ? $_GET['user_id'] : '';
$activity_type_filter = isset($_GET['activity_type']) ? $_GET['activity_type'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('d.m.Y', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('d.m.Y');

// Tarih Formatlama (d.m.Y -> Y-m-d)
function parseDate($dateStr, $default) {
    if (empty($dateStr)) return $default;
    $parts = explode('.', $dateStr);
    if (count($parts) == 3) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return $dateStr;
}

$start_date_db = parseDate($start_date, date('Y-m-d', strtotime('-30 days')));
$end_date_db = parseDate($end_date, date('Y-m-d'));

$system_errors = [];
$system_error_counts = ['total' => 0, 'critical' => 0, 'error' => 0, 'warning' => 0, 'notice' => 0];
$system_error_levels = [
    'critical' => ['Kritik', 'bg-red text-white'],
    'error' => ['Hata', 'bg-danger-lt text-danger'],
    'warning' => ['Uyarı', 'bg-warning-lt text-warning'],
    'notice' => ['Bilgi', 'bg-azure-lt text-azure'],
];
if ($is_superadmin) {
    $systemLogService = new \Service\SystemLogService();
    $system_errors = $systemLogService->getRecordsBetween(
        $start_date_db,
        $end_date_db,
        $user_id_filter !== '' ? (int) $user_id_filter : null,
        $company_id_filter !== '' ? (int) $company_id_filter : null
    );
    foreach ($system_errors as $system_error_record) {
        $system_error_level = (string) ($system_error_record['level'] ?? 'error');
        $system_error_counts['total']++;
        if (isset($system_error_counts[$system_error_level])) {
            $system_error_counts[$system_error_level]++;
        }
    }
}

// Firma Listesi (Sadece Superadmin için)
$all_companies = [];
if ($is_superadmin) {
    $sqlCompanies = $db->prepare("SELECT id, company_name FROM companies ORDER BY company_name ASC");
    $sqlCompanies->execute();
    $all_companies = $sqlCompanies->fetchAll(PDO::FETCH_OBJ);
}

// Kullanıcı Listesi (Filtre için)
$userModel = new UserModel();
if ($is_superadmin) {
    if (!empty($company_id_filter)) {
        $users = $userModel->getUsersByFirm($company_id_filter);
    } else {
        $sqlAllUsers = $db->prepare("SELECT id, full_name FROM users ORDER BY full_name ASC");
        $sqlAllUsers->execute();
        $users = $sqlAllUsers->fetchAll(PDO::FETCH_OBJ);
    }
} else {
    $users = $userModel->getUsersByFirm($firm_id);
}

$activity_user_names = [];
foreach ($users as $activity_user) {
    $activity_user_names[(int) $activity_user->id] = (string) $activity_user->full_name;
}
$activity_company_names = [];
foreach ($all_companies as $activity_company) {
    $activity_company_names[(int) $activity_company->id] = (string) $activity_company->company_name;
}

// --- FİLTRE VE KOŞULLARIN DİNAMİK YAPILANDIRILMASI ---

// 1. Aktivite Koşulları
$actWhere = "DATE(a.created_at) BETWEEN :start_date AND :end_date";
$actParams = [
    'start_date' => $start_date_db,
    'end_date' => $end_date_db
];

if ($is_superadmin) {
    if (!empty($company_id_filter)) {
        $actWhere .= " AND a.firm_id = :firm_id";
        $actParams['firm_id'] = $company_id_filter;
    }
} else {
    $actWhere .= " AND a.firm_id = :firm_id";
    $actParams['firm_id'] = $firm_id;
}

if (!empty($user_id_filter)) {
    $actWhere .= " AND a.user_id = :user_id";
    $actParams['user_id'] = $user_id_filter;
}
if (!empty($activity_type_filter)) {
    $actWhere .= " AND a.activity_type = :activity_type";
    $actParams['activity_type'] = $activity_type_filter;
}

// 2. Giriş Koşulları
$loginWhere = "DATE(l.login_time) BETWEEN :start_date AND :end_date";
$loginParams = [
    'start_date' => $start_date_db,
    'end_date' => $end_date_db
];

if ($is_superadmin) {
    if (!empty($company_id_filter)) {
        $loginWhere .= " AND u.firm_id = :firm_id";
        $loginParams['firm_id'] = $company_id_filter;
    }
} else {
    $loginWhere .= " AND u.firm_id = :firm_id";
    $loginParams['firm_id'] = $firm_id;
}

if (!empty($user_id_filter)) {
    $loginWhere .= " AND l.user_id = :user_id";
    $loginParams['user_id'] = $user_id_filter;
}

// --- VERİ TABANI SORGULARI ---

// 1. Toplam Giriş Sayısı
$sqlLoginsCount = $db->prepare("SELECT COUNT(l.id) FROM login_logs l JOIN users u ON l.user_id = u.id WHERE $loginWhere");
$sqlLoginsCount->execute($loginParams);
$total_logins = $sqlLoginsCount->fetchColumn();

// 2. Aktif Kullanıcı Sayısı (Benzersiz giriş yapanlar)
$sqlActiveUsersCount = $db->prepare("SELECT COUNT(DISTINCT l.user_id) FROM login_logs l JOIN users u ON l.user_id = u.id WHERE $loginWhere");
$sqlActiveUsersCount->execute($loginParams);
$active_users_count = $sqlActiveUsersCount->fetchColumn();

// 3. Toplam Aktivite Sayısı
$sqlActivitiesCount = $db->prepare("SELECT COUNT(a.id) FROM activity_logs a WHERE $actWhere");
$sqlActivitiesCount->execute($actParams);
$total_activities = $sqlActivitiesCount->fetchColumn();

// 4. En Aktif Kullanıcı
$sqlMostActiveUser = $db->prepare("SELECT u.full_name, COUNT(a.id) as act_count 
                                   FROM activity_logs a 
                                   JOIN users u ON a.user_id = u.id 
                                   WHERE $actWhere
                                   GROUP BY a.user_id 
                                   ORDER BY act_count DESC 
                                   LIMIT 1");
$sqlMostActiveUser->execute($actParams);
$most_active_user_row = $sqlMostActiveUser->fetch(PDO::FETCH_OBJ);
$most_active_user = $most_active_user_row ? $most_active_user_row->full_name . ' (' . $most_active_user_row->act_count . ')' : '-';

// --- GRAFİK VERİLERİ ---

// 1. Günlük Aktivite Eğilimi
$sqlDailyTrend = $db->prepare("SELECT DATE(a.created_at) as act_date, COUNT(a.id) as act_count 
                               FROM activity_logs a 
                               WHERE $actWhere
                               GROUP BY DATE(a.created_at) 
                               ORDER BY act_date ASC");
$sqlDailyTrend->execute($actParams);
$daily_trend_data = $sqlDailyTrend->fetchAll(PDO::FETCH_OBJ);

$trend_labels = [];
$trend_values = [];
$start_time = strtotime($start_date_db);
$end_time = strtotime($end_date_db);
$date_map = [];
for ($i = $start_time; $i <= $end_time; $i += 86400) {
    $date_map[date('Y-m-d', $i)] = 0;
}
foreach ($daily_trend_data as $row) {
    $date_map[$row->act_date] = (int)$row->act_count;
}
foreach ($date_map as $d => $count) {
    $trend_labels[] = date('d.m', strtotime($d));
    $trend_values[] = $count;
}

// 2. Aktivite Türleri Dağılımı
$sqlTypeDist = $db->prepare("SELECT a.activity_type, COUNT(a.id) as act_count 
                             FROM activity_logs a 
                             WHERE $actWhere
                             GROUP BY a.activity_type");
$sqlTypeDist->execute($actParams);
$type_dist_data = $sqlTypeDist->fetchAll(PDO::FETCH_OBJ);

$type_labels = [];
$type_values = [];
$type_names = [
    'personnel' => 'Personel',
    'project' => 'Proje',
    'puantaj' => 'Puantaj',
    'finance' => 'Finans',
    'todo' => 'Görev/Yapılacak',
    'auth' => 'Yetkilendirme',
    'login' => 'Sisteme Giriş',
    'other' => 'Diğer'
];
foreach ($type_dist_data as $row) {
    $type_labels[] = $type_names[$row->activity_type] ?? ucfirst($row->activity_type);
    $type_values[] = (int)$row->act_count;
}

// 3. Saatlik Giriş Dağılımı
$sqlHourlyLogins = $db->prepare("SELECT HOUR(l.login_time) as login_hour, COUNT(l.id) as login_count 
                                 FROM login_logs l 
                                 JOIN users u ON l.user_id = u.id 
                                 WHERE $loginWhere
                                 GROUP BY HOUR(l.login_time) 
                                 ORDER BY login_hour ASC");
$sqlHourlyLogins->execute($loginParams);
$hourly_logins_data = $sqlHourlyLogins->fetchAll(PDO::FETCH_OBJ);

$hourly_labels = [];
$hourly_values = [];
$hour_map = array_fill(0, 24, 0);
foreach ($hourly_logins_data as $row) {
    $hour_map[(int)$row->login_hour] = (int)$row->login_count;
}
for ($h = 0; $h < 24; $h++) {
    $hourly_labels[] = sprintf('%02d:00', $h);
    $hourly_values[] = $hour_map[$h];
}

// --- TABLO VERİLERİ ---

// 1. Aktivite Günlükleri Listesi
$sqlActList = "SELECT a.*, u.full_name as user_name, c.company_name 
               FROM activity_logs a 
               LEFT JOIN users u ON a.user_id = u.id 
               LEFT JOIN companies c ON a.firm_id = c.id
               WHERE $actWhere 
               ORDER BY a.created_at DESC";
$stmtActList = $db->prepare($sqlActList);
$stmtActList->execute($actParams);
$activities = $stmtActList->fetchAll(PDO::FETCH_OBJ);

// 2. Giriş Kayıtları Listesi
$sqlLoginsList = "SELECT l.*, u.full_name as user_name, c.company_name 
                  FROM login_logs l 
                  JOIN users u ON l.user_id = u.id 
                  LEFT JOIN companies c ON u.firm_id = c.id
                  WHERE $loginWhere 
                  ORDER BY l.login_time DESC";
$stmtLoginsList = $db->prepare($sqlLoginsList);
$stmtLoginsList->execute($loginParams);
$logins = $stmtLoginsList->fetchAll(PDO::FETCH_OBJ);

// 3. Kullanıcı Aktiflik Raporu
$userSummaryWhere = "";
$userSummaryParams = [
    'start_date' => $start_date_db,
    'end_date' => $end_date_db
];
if ($is_superadmin) {
    if (!empty($company_id_filter)) {
        $userSummaryWhere .= "WHERE u.firm_id = :firm_id";
        $userSummaryParams['firm_id'] = $company_id_filter;
    }
} else {
    $userSummaryWhere .= "WHERE u.firm_id = :firm_id";
    $userSummaryParams['firm_id'] = $firm_id;
}

$sqlUserSummary = $db->prepare("
    SELECT 
        u.id, 
        u.full_name, 
        u.email,
        u.status,
        c.company_name,
        (SELECT COUNT(a.id) FROM activity_logs a WHERE a.user_id = u.id AND DATE(a.created_at) BETWEEN :start_date AND :end_date) as total_activities,
        (SELECT COUNT(l.id) FROM login_logs l WHERE l.user_id = u.id AND DATE(l.login_time) BETWEEN :start_date AND :end_date) as total_logins,
        (SELECT MAX(a.created_at) FROM activity_logs a WHERE a.user_id = u.id) as last_activity_time
    FROM users u 
    LEFT JOIN companies c ON u.firm_id = c.id
    $userSummaryWhere
    ORDER BY total_activities DESC
");
$sqlUserSummary->execute($userSummaryParams);
$user_summaries = $sqlUserSummary->fetchAll(PDO::FETCH_OBJ);

// Oturum Süresi Hesaplama
function getSessionDuration($loginTime, $logoutTime) {
    if (empty($logoutTime) || $logoutTime == '0000-00-00 00:00:00') {
        return '<span class="badge bg-green-lt">Aktif Oturum</span>';
    }
    
    $login = strtotime($loginTime);
    $logout = strtotime($logoutTime);
    
    if (!$logout || $logout < $login) {
        return '<span class="badge bg-secondary-lt">Bilinmiyor</span>';
    }
    
    $diff = $logout - $login;
    if ($diff < 60) {
        return $diff . ' sn';
    }
    
    $mins = floor($diff / 60);
    if ($mins < 60) {
        return $mins . ' dk';
    }
    
    $hours = floor($mins / 60);
    $remMins = $mins % 60;
    return $hours . ' sa ' . $remMins . ' dk';
}

$tab_page_content = [
    'analiz' => [
        'title' => 'Sistem Aktiviteleri' . ($is_superadmin ? ' (Tüm Sistem Raporu)' : ''),
        'description' => 'Aktivite, giriş ve kullanıcı kullanım istatistiklerinin genel görünümü.',
    ],
    'aktiviteler' => [
        'title' => 'Kullanıcı Davranış & Aktivite Logları',
        'description' => 'Seçili filtrelere göre kullanıcıların gerçekleştirdiği sistem işlemleri.',
    ],
    'girisler' => [
        'title' => 'Sisteme Giriş & Çıkış Geçmişi',
        'description' => 'Kullanıcıların oturum, cihaz ve IP bilgilerinin tarihsel görünümü.',
    ],
    'kullanici-rapor' => [
        'title' => 'Kullanıcı Sistem Kullanım Özeti',
        'description' => 'Giriş ve aktivite sayılarına göre kullanıcı kullanım istatistikleri.',
    ],
    'sistem-hatalari' => [
        'title' => 'Merkezi Sistem Hata Kayıtları',
        'description' => 'Seçili tarih, firma ve kullanıcı filtrelerine göre en fazla 5.000 kayıt gösterilir.',
    ],
];
$active_page_content = $tab_page_content[$active_tab];
?>

<div class="container-xl mt-3" data-id="activities-dashboard">
    <!-- Sayfa Başlığı -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Analiz & Raporlama</div>
                <h2 class="page-title text-primary fw-bold" id="activitiesPageTitle"><?php echo htmlspecialchars($active_page_content['title']); ?></h2>
                <div class="text-secondary mt-1" id="activitiesPageDescription"><?php echo htmlspecialchars($active_page_content['description']); ?></div>
            </div>
        </div>
    </div>

    <div class="accordion mb-3" id="activitiesFilterAccordion">
        <div class="accordion-item">
            <h2 class="accordion-header" id="activitiesFilterHeading">
                <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#activitiesFilterCollapse" aria-expanded="false" aria-controls="activitiesFilterCollapse">
                    <span class="d-flex align-items-center gap-2">
                        <i class="ti ti-filter text-primary"></i>
                        <span class="fw-bold">Filtreler</span>
                        <span class="badge bg-secondary-lt"><?php echo htmlspecialchars($start_date); ?> – <?php echo htmlspecialchars($end_date); ?></span>
                    </span>
                </button>
            </h2>
            <div id="activitiesFilterCollapse" class="accordion-collapse collapse" aria-labelledby="activitiesFilterHeading" data-bs-parent="#activitiesFilterAccordion">
                <div class="accordion-body p-3">
                    <form method="GET" action="index.php" id="filterForm">
                <input type="hidden" name="p" value="activities/index">
                <input type="hidden" name="tab" id="activeActivitiesTab" value="<?php echo htmlspecialchars($active_tab); ?>">
                <div class="row g-2 align-items-end">
                    
                    <!-- Superadmin Firma Filtresi -->
                    <?php if ($is_superadmin): ?>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold text-secondary">Firma (Şirket)</label>
                            <select name="company_id" class="form-select select2" style="width: 100%" onchange="this.form.submit()">
                                <option value="">Tüm Firmalar</option>
                                <?php foreach ($all_companies as $c): ?>
                                    <option value="<?php echo $c->id; ?>" <?php echo $company_id_filter == $c->id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c->company_name); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <!-- Kullanıcı Filtresi -->
                    <div class="<?php echo $is_superadmin ? 'col-md-2' : 'col-md-3'; ?>">
                        <label class="form-label small fw-bold text-secondary">Kullanıcı</label>
                        <select name="user_id" class="form-select select2" style="width: 100%">
                            <option value="">Tüm Kullanıcılar</option>
                            <?php foreach ($users as $u): ?>
                                <option value="<?php echo $u->id; ?>" <?php echo $user_id_filter == $u->id ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($u->full_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Aktivite Türü Filtresi -->
                    <div class="<?php echo $is_superadmin ? 'col-md-2' : 'col-md-3'; ?>">
                        <label class="form-label small fw-bold text-secondary">Aktivite Türü</label>
                        <select name="activity_type" class="form-select select2" style="width: 100%">
                            <option value="">Tüm Türler</option>
                            <option value="personnel" <?php echo $activity_type_filter == 'personnel' ? 'selected' : ''; ?>>Personel</option>
                            <option value="project" <?php echo $activity_type_filter == 'project' ? 'selected' : ''; ?>>Proje</option>
                            <option value="puantaj" <?php echo $activity_type_filter == 'puantaj' ? 'selected' : ''; ?>>Puantaj</option>
                            <option value="finance" <?php echo $activity_type_filter == 'finance' ? 'selected' : ''; ?>>Finans</option>
                            <option value="todo" <?php echo $activity_type_filter == 'todo' ? 'selected' : ''; ?>>Görev/Todo</option>
                            <option value="login" <?php echo $activity_type_filter == 'login' ? 'selected' : ''; ?>>Sisteme Giriş</option>
                        </select>
                    </div>

                    <!-- Başlangıç Tarihi -->
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-secondary">Başlangıç Tarihi</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                            <input type="text" name="start_date" class="form-control flatpickr" value="<?php echo $start_date; ?>" placeholder="Seçiniz">
                        </div>
                    </div>

                    <!-- Bitiş Tarihi -->
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-secondary">Bitiş Tarihi</label>
                        <div class="input-icon">
                            <span class="input-icon-addon"><i class="ti ti-calendar"></i></span>
                            <input type="text" name="end_date" class="form-control flatpickr" value="<?php echo $end_date; ?>" placeholder="Seçiniz">
                        </div>
                    </div>

                    <!-- Butonlar -->
                    <div class="col-md-2 d-flex gap-1">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-filter me-1"></i> Filtrele
                        </button>
                        <a href="index.php?p=activities/index&amp;tab=<?php echo urlencode($active_tab); ?>" class="btn btn-outline-secondary" title="Sıfırla">
                            <i class="ti ti-refresh"></i>
                        </a>
                    </div>
                </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- TABS CONTAINER -->
    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06);">
        <!-- Tab Başlıkları -->
        <div class="card-header p-0">
            <ul class="nav nav-tabs card-header-tabs m-0" data-bs-toggle="tabs">
                <li class="nav-item">
                    <a href="#tab-analiz" class="nav-link <?php echo $active_tab === 'analiz' ? 'active' : ''; ?> py-3 px-4 fw-bold" data-bs-toggle="tab" data-tab-name="analiz" data-page-title="<?php echo htmlspecialchars($tab_page_content['analiz']['title'], ENT_QUOTES, 'UTF-8'); ?>" data-page-description="<?php echo htmlspecialchars($tab_page_content['analiz']['description'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="ti ti-chart-bar me-2 text-primary"></i> Analiz & Grafikler
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-aktiviteler" class="nav-link <?php echo $active_tab === 'aktiviteler' ? 'active' : ''; ?> py-3 px-4 fw-bold" data-bs-toggle="tab" data-tab-name="aktiviteler" data-page-title="<?php echo htmlspecialchars($tab_page_content['aktiviteler']['title'], ENT_QUOTES, 'UTF-8'); ?>" data-page-description="<?php echo htmlspecialchars($tab_page_content['aktiviteler']['description'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="ti ti-activity me-2 text-success"></i> Aktivite Günlükleri
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-girisler" class="nav-link <?php echo $active_tab === 'girisler' ? 'active' : ''; ?> py-3 px-4 fw-bold" data-bs-toggle="tab" data-tab-name="girisler" data-page-title="<?php echo htmlspecialchars($tab_page_content['girisler']['title'], ENT_QUOTES, 'UTF-8'); ?>" data-page-description="<?php echo htmlspecialchars($tab_page_content['girisler']['description'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="ti ti-login me-2 text-warning"></i> Sisteme Girişler
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-kullanici-rapor" class="nav-link <?php echo $active_tab === 'kullanici-rapor' ? 'active' : ''; ?> py-3 px-4 fw-bold" data-bs-toggle="tab" data-tab-name="kullanici-rapor" data-page-title="<?php echo htmlspecialchars($tab_page_content['kullanici-rapor']['title'], ENT_QUOTES, 'UTF-8'); ?>" data-page-description="<?php echo htmlspecialchars($tab_page_content['kullanici-rapor']['description'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="ti ti-users me-2 text-info"></i> Kullanıcı Özet Raporu
                    </a>
                </li>
                <?php if ($is_superadmin): ?>
                    <li class="nav-item">
                        <a href="#tab-sistem-hatalari" class="nav-link <?php echo $active_tab === 'sistem-hatalari' ? 'active' : ''; ?> py-3 px-4 fw-bold" data-bs-toggle="tab" data-tab-name="sistem-hatalari" data-page-title="<?php echo htmlspecialchars($tab_page_content['sistem-hatalari']['title'], ENT_QUOTES, 'UTF-8'); ?>" data-page-description="<?php echo htmlspecialchars($tab_page_content['sistem-hatalari']['description'], ENT_QUOTES, 'UTF-8'); ?>">
                            <i class="ti ti-alert-triangle me-2 text-danger"></i> Sistem Hataları
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>

        <div class="card-body tab-content p-3">
            <!-- TAB 1: ANALİZ & GRAFİKLER -->
            <div class="tab-pane <?php echo $active_tab === 'analiz' ? 'active show' : ''; ?>" id="tab-analiz">
                <!-- Özellik Widgetları -->
                <div class="row row-cards mb-3">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-blue text-white me-3"><i class="ti ti-users"></i></span>
                                    <div class="text-truncate">
                                        <div class="fw-medium"><?php echo (int) $active_users_count; ?> Aktif Kullanıcı</div>
                                        <div class="text-secondary small">Seçili dönemde giriş yapan</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-green text-white me-3"><i class="ti ti-activity"></i></span>
                                    <div class="text-truncate">
                                        <div class="fw-medium"><?php echo (int) $total_activities; ?> Aktivite</div>
                                        <div class="text-secondary small">Kaydedilen toplam işlem</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-yellow text-white me-3"><i class="ti ti-login"></i></span>
                                    <div class="text-truncate">
                                        <div class="fw-medium"><?php echo (int) $total_logins; ?> Sistem Girişi</div>
                                        <div class="text-secondary small">Kaydedilen oturumlar</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <span class="avatar bg-purple text-white me-3"><i class="ti ti-trophy"></i></span>
                                    <div class="text-truncate">
                                        <div class="fw-medium text-truncate" title="<?php echo htmlspecialchars($most_active_user); ?>">
                                            <?php echo htmlspecialchars($most_active_user); ?>
                                        </div>
                                        <div class="text-secondary small">Dönemin en aktif kullanıcısı</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Grafik Panelleri -->
                <div class="row">
                    <!-- Grafik 1: Aktivite Eğilimi -->
                    <div class="col-lg-8 mb-4">
                        <div class="card" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.06);">
                            <div class="card-body">
                                <h3 class="card-title fw-bold"><i class="ti ti-chart-area-line me-2 text-primary"></i>Günlük Aktivite Eğilimi</h3>
                                <div id="chart-daily-trend" style="min-height: 350px;"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Grafik 2: Aktivite Türleri Dağılımı -->
                    <div class="col-lg-4 mb-4">
                        <div class="card" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.06);">
                            <div class="card-body">
                                <h3 class="card-title fw-bold"><i class="ti ti-chart-pie me-2 text-success"></i>Aktivite Türleri</h3>
                                <div id="chart-type-dist" style="min-height: 350px;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Grafik 3: Saatlik Giriş Dağılımı -->
                    <div class="col-12">
                        <div class="card" style="border-radius: 10px; border: 1px solid rgba(0,0,0,0.06);">
                            <div class="card-body">
                                <h3 class="card-title fw-bold"><i class="ti ti-clock-hour-4 me-2 text-warning"></i>Giriş Zaman Dilimi Yoğunluğu (Saatlik)</h3>
                                <div id="chart-hourly-logins" style="min-height: 250px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: AKTİVİTE GÜNLÜKLERİ -->
            <div class="tab-pane <?php echo $active_tab === 'aktiviteler' ? 'active show' : ''; ?>" id="tab-aktiviteler">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-success btn-sm" id="export_excel_activities">
                        <i class="ti ti-file-spreadsheet me-1"></i> Excel'e Aktar
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="activitiesTable" class="table card-table text-nowrap datatable w-100">
                        <thead>
                            <tr>
                                <th style="width: 5%">Sıra</th>
                                <?php if ($is_superadmin): ?>
                                    <th>Firma</th>
                                <?php endif; ?>
                                <th style="width: 13%">Kullanıcı</th>
                                <th style="width: 12%">Cihaz / Platform</th>
                                <th style="width: 12%">Aktivite Türü</th>
                                <th style="width: 12%">İşlem / Eylem</th>
                                <th>Açıklama</th>
                                <th style="width: 14%">Tarih & Saat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($activities as $act): ?>
                                <?php
                                    $icon = 'ti-activity';
                                    $color = 'bg-secondary-lt';
                                    switch($act->activity_type) {
                                        case 'personnel': $icon = 'ti-users'; $color = 'bg-blue-lt'; break;
                                        case 'project': $icon = 'ti-buildings'; $color = 'bg-green-lt'; break;
                                        case 'puantaj': $icon = 'ti-calendar'; $color = 'bg-orange-lt'; break;
                                        case 'finance': $icon = 'ti-wallet'; $color = 'bg-red-lt'; break;
                                        case 'todo': $icon = 'ti-checklist'; $color = 'bg-purple-lt'; break;
                                        case 'login': $icon = 'ti-key'; $color = 'bg-yellow-lt'; break;
                                    }

                                    $plat = !empty($act->platform) ? $act->platform : 'Masaüstü';
                                    $isMob = (strpos(mb_strtolower($plat, 'UTF-8'), 'mobil') !== false);
                                    $badgeCol = $isMob ? 'azure' : 'secondary';
                                    $badgeIco = $isMob ? 'device-mobile' : 'device-desktop';
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <?php if ($is_superadmin): ?>
                                        <td><span class="text-secondary small fw-bold"><?php echo htmlspecialchars($act->company_name ?? 'Sistem'); ?></span></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs rounded-circle bg-teal-lt">
                                                <?php echo Helper::getInitials($act->user_name); ?>
                                            </span>
                                            <span class="fw-medium"><?php echo htmlspecialchars($act->user_name ?? 'Bilinmeyen'); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo $badgeCol; ?>-lt text-<?php echo $badgeCol; ?>">
                                            <i class="ti ti-<?php echo $badgeIco; ?> me-1"></i><?php echo htmlspecialchars($plat); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $color; ?> d-inline-flex align-items-center gap-1">
                                            <i class="ti <?php echo $icon; ?>" style="font-size: 11px;"></i>
                                            <?php echo $type_names[$act->activity_type] ?? ucfirst($act->activity_type); ?>
                                        </span>
                                    </td>
                                    <td><code class="text-indigo"><?php echo htmlspecialchars($act->action); ?></code></td>
                                    <td class="text-wrap" style="max-width: 400px; font-size: 13px;"><?php echo htmlspecialchars($act->description); ?></td>
                                    <td class="text-secondary"><?php echo date('d.m.Y H:i:s', strtotime($act->created_at)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 3: SİSTEME GİRİŞLER -->
            <div class="tab-pane <?php echo $active_tab === 'girisler' ? 'active show' : ''; ?>" id="tab-girisler">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-success btn-sm" id="export_excel_logins">
                        <i class="ti ti-file-spreadsheet me-1"></i> Excel'e Aktar
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="loginsTable" class="table card-table text-nowrap datatable w-100">
                        <thead>
                            <tr>
                                <th style="width: 5%">Sıra</th>
                                <?php if ($is_superadmin): ?>
                                    <th>Firma</th>
                                <?php endif; ?>
                                <th style="width: 20%">Kullanıcı</th>
                                <th style="width: 15%">Giriş Zamanı</th>
                                <th style="width: 15%">Çıkış Zamanı</th>
                                <th style="width: 15%">Oturum Süresi</th>
                                <th style="width: 12%">IP Adresi</th>
                                <th>Cihaz / Tarayıcı Bilgisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($logins as $log): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <?php if ($is_superadmin): ?>
                                        <td><span class="text-secondary small fw-bold"><?php echo htmlspecialchars($log->company_name ?? 'Sistem'); ?></span></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-xs rounded-circle bg-azure-lt">
                                                <?php echo Helper::getInitials($log->user_name); ?>
                                            </span>
                                            <span class="fw-medium"><?php echo htmlspecialchars($log->user_name); ?></span>
                                        </div>
                                    </td>
                                    <td><span class="text-success"><i class="ti ti-arrow-up-right me-1"></i><?php echo date('d.m.Y H:i:s', strtotime($log->login_time)); ?></span></td>
                                    <td>
                                        <?php if (!empty($log->logout_time) && $log->logout_time != '0000-00-00 00:00:00'): ?>
                                            <span class="text-danger"><i class="ti ti-arrow-down-left me-1"></i><?php echo date('d.m.Y H:i:s', strtotime($log->logout_time)); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo getSessionDuration($log->login_time, $log->logout_time); ?></td>
                                    <td><code class="text-dark bg-light px-2 py-1 rounded"><?php echo htmlspecialchars($log->ip_address); ?></code></td>
                                    <td class="text-truncate text-secondary" style="max-width: 250px;" title="<?php echo htmlspecialchars($log->user_agent); ?>">
                                        <?php 
                                            // Basitleştirilmiş tarayıcı tespiti
                                            $ua = $log->user_agent;
                                            $device = 'Bilinmiyor';
                                            if (strpos($ua, 'Mobile') !== false || strpos($ua, 'Android') !== false || strpos($ua, 'iPhone') !== false) {
                                                $device = '<i class="ti ti-device-mobile me-1 text-primary"></i> Mobil';
                                            } else if (strpos($ua, 'Windows') !== false) {
                                                $device = '<i class="ti ti-brand-windows me-1 text-blue"></i> Windows';
                                            } else if (strpos($ua, 'Macintosh') !== false) {
                                                $device = '<i class="ti ti-brand-apple me-1 text-dark"></i> MacOS';
                                            } else if (strpos($ua, 'Linux') !== false) {
                                                $device = '<i class="ti ti-brand-open-source me-1 text-danger"></i> Linux';
                                            }
                                            
                                            $browser = '';
                                            if (strpos($ua, 'Chrome') !== false) $browser = '(Chrome)';
                                            else if (strpos($ua, 'Safari') !== false) $browser = '(Safari)';
                                            else if (strpos($ua, 'Firefox') !== false) $browser = '(Firefox)';
                                            else if (strpos($ua, 'Edg') !== false) $browser = '(Edge)';
                                            
                                            echo $device . ' ' . $browser;
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 4: KULLANICI ÖZET RAPORU -->
            <div class="tab-pane <?php echo $active_tab === 'kullanici-rapor' ? 'active show' : ''; ?>" id="tab-kullanici-rapor">
                <div class="d-flex justify-content-end mb-2">
                    <button class="btn btn-outline-success btn-sm" id="export_excel_user_summaries">
                        <i class="ti ti-file-spreadsheet me-1"></i> Excel'e Aktar
                    </button>
                </div>
                <div class="table-responsive">
                    <table id="userSummaryTable" class="table card-table text-nowrap datatable w-100">
                        <thead>
                            <tr>
                                <th style="width: 5%">Sıra</th>
                                <?php if ($is_superadmin): ?>
                                    <th>Firma</th>
                                <?php endif; ?>
                                <th>Kullanıcı</th>
                                <th>E-Mail</th>
                                <th style="width: 15%">Toplam Giriş Sayısı</th>
                                <th style="width: 15%">Toplam Aktivite Kaydı</th>
                                <th style="width: 20%">Son Etkinlik Tarihi</th>
                                <th style="width: 15%">Kullanım Düzeyi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $i = 1; foreach ($user_summaries as $sum): ?>
                                <?php
                                    // Kullanım düzeyi hesaplama
                                    $level = 'Düşük';
                                    $badge = 'bg-secondary-lt';
                                    $total_ops = $sum->total_activities + $sum->total_logins;
                                    if ($total_ops > 100) {
                                        $level = 'Çok Yüksek';
                                        $badge = 'bg-purple-lt';
                                    } else if ($total_ops > 50) {
                                        $level = 'Yüksek';
                                        $badge = 'bg-red-lt';
                                    } else if ($total_ops > 15) {
                                        $level = 'Orta';
                                        $badge = 'bg-blue-lt';
                                    } else if ($total_ops > 0) {
                                        $level = 'Düşük';
                                        $badge = 'bg-green-lt';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <?php if ($is_superadmin): ?>
                                        <td><span class="text-secondary small fw-bold"><?php echo htmlspecialchars($sum->company_name ?? 'Sistem'); ?></span></td>
                                    <?php endif; ?>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="avatar avatar-sm rounded bg-primary-lt">
                                                <?php echo Helper::getInitials($sum->full_name); ?>
                                            </span>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($sum->full_name); ?></div>
                                                <small class="text-secondary">
                                                    Durum: <?php echo $sum->status == 1 ? '<span class="text-success">Aktif</span>' : '<span class="text-danger">Pasif</span>'; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($sum->email); ?></td>
                                    <td class="fw-medium text-center"><?php echo $sum->total_logins; ?></td>
                                    <td class="fw-medium text-center"><?php echo $sum->total_activities; ?></td>
                                    <td>
                                        <?php if (!empty($sum->last_activity_time)): ?>
                                            <i class="ti ti-clock me-1 text-secondary"></i><?php echo date('d.m.Y H:i', strtotime($sum->last_activity_time)); ?>
                                        <?php else: ?>
                                            <span class="text-muted">Kayıt yok</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $badge; ?> px-2 py-1" style="font-size: 11px;">
                                            <?php echo $level; ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($is_superadmin): ?>
                <div class="tab-pane <?php echo $active_tab === 'sistem-hatalari' ? 'active show' : ''; ?>" id="tab-sistem-hatalari">
                    <div class="d-flex justify-content-end mb-2">
                        <button class="btn btn-outline-success btn-sm" id="export_excel_system_errors">
                            <i class="ti ti-file-spreadsheet me-1"></i> Excel'e Aktar
                        </button>
                    </div>

                    <div class="row row-cards mb-3">
                        <div class="col-sm-6 col-lg">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar bg-secondary text-white me-3"><i class="ti ti-list-details"></i></span>
                                        <div>
                                            <div class="fw-medium"><?php echo (int) $system_error_counts['total']; ?> Toplam Kayıt</div>
                                            <div class="text-secondary small">Seçili tarih aralığında</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar bg-red text-white me-3"><i class="ti ti-alert-octagon"></i></span>
                                        <div>
                                            <div class="fw-medium"><?php echo (int) $system_error_counts['critical']; ?> Kritik</div>
                                            <div class="text-secondary small">Acil müdahale gerektiren</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar bg-danger text-white me-3"><i class="ti ti-circle-x"></i></span>
                                        <div>
                                            <div class="fw-medium"><?php echo (int) $system_error_counts['error']; ?> Hata</div>
                                            <div class="text-secondary small">Uygulama hataları</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar bg-warning text-white me-3"><i class="ti ti-alert-triangle"></i></span>
                                        <div>
                                            <div class="fw-medium"><?php echo (int) $system_error_counts['warning']; ?> Uyarı</div>
                                            <div class="text-secondary small">Kontrol edilmesi gereken</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-lg">
                            <div class="card">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center">
                                        <span class="avatar bg-azure text-white me-3"><i class="ti ti-info-circle"></i></span>
                                        <div>
                                            <div class="fw-medium"><?php echo (int) $system_error_counts['notice']; ?> Bilgi</div>
                                            <div class="text-secondary small">Düşük öncelikli kayıt</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="systemErrorsTable" class="table card-table text-nowrap datatable w-100">
                            <thead>
                                <tr>
                                    <th>Sıra</th>
                                    <th>Seviye</th>
                                    <th>Hata</th>
                                    <th>Kaynak</th>
                                    <th>İstek</th>
                                    <th>Kullanıcı / Firma</th>
                                    <th>IP</th>
                                    <th>Tarih & Saat</th>
                                    <th>Detay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($system_errors as $error_index => $system_error): ?>
                                    <?php
                                    $error_level = (string) ($system_error['level'] ?? 'error');
                                    $error_level_data = $system_error_levels[$error_level] ?? [ucfirst($error_level), 'bg-secondary-lt'];
                                    $error_context = is_array($system_error['context'] ?? null) ? $system_error['context'] : [];
                                    $error_request = is_array($system_error['request'] ?? null) ? $system_error['request'] : [];
                                    $error_actor = is_array($system_error['actor'] ?? null) ? $system_error['actor'] : [];
                                    $error_user_id = isset($error_actor['user_id']) ? (int) $error_actor['user_id'] : 0;
                                    $error_firm_id = isset($error_actor['firm_id']) ? (int) $error_actor['firm_id'] : 0;
                                    $error_source = !empty($error_context['file']) ? basename((string) $error_context['file']) : '—';
                                    $error_line = !empty($error_context['line']) ? ':' . (int) $error_context['line'] : '';
                                    $error_timestamp = strtotime((string) ($system_error['timestamp'] ?? ''));
                                    $error_detail = json_encode([
                                        'request_id' => $system_error['request_id'] ?? null,
                                        'type' => $system_error['type'] ?? null,
                                        'request' => $error_request,
                                        'context' => $error_context,
                                    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
                                    ?>
                                    <tr>
                                        <td><?php echo $error_index + 1; ?></td>
                                        <td><span class="badge <?php echo $error_level_data[1]; ?>"><?php echo htmlspecialchars($error_level_data[0]); ?></span></td>
                                        <td class="text-wrap" style="min-width: 320px; max-width: 520px;">
                                            <div class="fw-medium"><?php echo htmlspecialchars((string) ($system_error['message'] ?? 'Bilinmeyen hata')); ?></div>
                                            <div class="text-secondary small mt-1">
                                                <?php echo htmlspecialchars((string) ($system_error['type'] ?? 'application_error')); ?>
                                                · Kod: <?php echo htmlspecialchars((string) ($system_error['request_id'] ?? '—')); ?>
                                            </div>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($error_source . $error_line); ?></code></td>
                                        <td>
                                            <div><?php echo htmlspecialchars((string) ($error_request['method'] ?? '—')); ?></div>
                                            <div class="text-secondary small"><?php echo htmlspecialchars((string) ($error_request['path'] ?? '—')); ?></div>
                                        </td>
                                        <td>
                                            <div><?php echo htmlspecialchars($activity_user_names[$error_user_id] ?? ($error_user_id ? 'Kullanıcı #' . $error_user_id : 'Sistem')); ?></div>
                                            <div class="text-secondary small"><?php echo htmlspecialchars($activity_company_names[$error_firm_id] ?? ($error_firm_id ? 'Firma #' . $error_firm_id : 'Firma yok')); ?></div>
                                        </td>
                                        <td><code class="text-dark bg-light px-2 py-1 rounded"><?php echo htmlspecialchars((string) ($error_request['ip'] ?? '—')); ?></code></td>
                                        <td class="text-secondary"><?php echo $error_timestamp ? date('d.m.Y H:i:s', $error_timestamp) : '—'; ?></td>
                                        <td>
                                            <button
                                                type="button"
                                                class="btn btn-sm btn-outline-secondary system-error-detail"
                                                data-bs-toggle="modal"
                                                data-bs-target="#systemErrorDetailModal"
                                                data-error-message="<?php echo htmlspecialchars((string) ($system_error['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                data-error-detail="<?php echo htmlspecialchars((string) $error_detail, ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                                <i class="ti ti-code me-1"></i>İncele
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($is_superadmin): ?>
    <div class="modal modal-blur fade" id="systemErrorDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <div class="text-secondary small">Sistem hatası teknik detayı</div>
                        <h3 class="modal-title" id="systemErrorDetailTitle">Hata detayı</h3>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body">
                    <pre id="systemErrorDetailContent" class="bg-dark text-light rounded p-3 mb-0" style="white-space: pre-wrap; overflow-wrap: anywhere;"></pre>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- ApexCharts Scriptleri -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    // Tema kontrolü
    var currentTheme = document.body.getAttribute('data-bs-theme') || 'light';
    
    // Ortak Grafik Renkleri & Yazı Tipleri
    var chartFontFamily = 'var(--tblr-font-sans-serif, Inter)';
    var gridColor = currentTheme === 'dark' ? 'rgba(255,255,255,0.08)' : 'rgba(0,0,0,0.05)';
    var textColor = currentTheme === 'dark' ? '#94a3b8' : '#475569';

    // 1. Günlük Aktivite Eğilimi Grafiği (Area Chart)
    var trendOptions = {
        chart: {
            type: 'area',
            height: 350,
            fontFamily: chartFontFamily,
            parentHeightOffset: 0,
            toolbar: { show: false },
            zoom: { enabled: false },
            sparkline: { enabled: false },
            background: 'transparent'
        },
        theme: {
            mode: currentTheme
        },
        dataLabels: {
            enabled: false
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [0, 100]
            }
        },
        stroke: {
            width: 3,
            curve: 'smooth'
        },
        series: [{
            name: 'Aktivite Sayısı',
            data: <?php echo json_encode($trend_values); ?>
        }],
        grid: {
            borderColor: gridColor,
            strokeDashArray: 4,
            padding: { top: -20, right: 0, bottom: 0, left: 10 }
        },
        xaxis: {
            categories: <?php echo json_encode($trend_labels); ?>,
            labels: {
                style: { colors: textColor, fontSize: '11px' }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: { colors: textColor, fontSize: '11px' }
            }
        },
        colors: ['#3b82f6'],
        tooltip: {
            theme: currentTheme
        }
    };
    var trendChart = new ApexCharts(document.querySelector("#chart-daily-trend"), trendOptions);
    trendChart.render();

    // 2. Aktivite Dağılımı Grafiği (Donut Chart)
    var typeOptions = {
        chart: {
            type: 'donut',
            height: 350,
            fontFamily: chartFontFamily,
            background: 'transparent'
        },
        theme: {
            mode: currentTheme
        },
        series: <?php echo json_encode($type_values); ?>,
        labels: <?php echo json_encode($type_labels); ?>,
        colors: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#ec4899', '#64748b'],
        legend: {
            position: 'bottom',
            labels: { colors: textColor }
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '70%',
                    labels: {
                        show: true,
                        total: {
                            show: true,
                            label: 'Toplam',
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            },
                            color: currentTheme === 'dark' ? '#f8fafc' : '#1e293b'
                        }
                    }
                }
            }
        },
        tooltip: {
            theme: currentTheme
        }
    };
    var typeChart = new ApexCharts(document.querySelector("#chart-type-dist"), typeOptions);
    typeChart.render();

    // 3. Saatlik Giriş Grafiği (Bar Chart)
    var hourlyOptions = {
        chart: {
            type: 'bar',
            height: 250,
            fontFamily: chartFontFamily,
            toolbar: { show: false },
            background: 'transparent'
        },
        theme: {
            mode: currentTheme
        },
        plotOptions: {
            bar: {
                borderRadius: 4,
                columnWidth: '50%'
            }
        },
        dataLabels: {
            enabled: false
        },
        series: [{
            name: 'Giriş Sayısı',
            data: <?php echo json_encode($hourly_values); ?>
        }],
        grid: {
            borderColor: gridColor,
            strokeDashArray: 4,
            padding: { top: -20, right: 0, bottom: 0, left: 10 }
        },
        xaxis: {
            categories: <?php echo json_encode($hourly_labels); ?>,
            labels: {
                style: { colors: textColor, fontSize: '11px' }
            },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: {
            labels: {
                style: { colors: textColor, fontSize: '11px' }
            }
        },
        colors: ['#eab308'],
        tooltip: {
            theme: currentTheme
        }
    };
    var hourlyChart = new ApexCharts(document.querySelector("#chart-hourly-logins"), hourlyOptions);
    hourlyChart.render();
    
    // Tab geçişlerinde grafiklerin yeniden çizilmesi (Responsive Resize)
    document.querySelectorAll('a[data-bs-toggle="tab"]').forEach(function(tabLink) {
        tabLink.addEventListener('shown.bs.tab', function (e) {
            var selectedTab = e.target.getAttribute('data-tab-name');
            var activeTabInput = document.getElementById('activeActivitiesTab');
            if (selectedTab && activeTabInput) {
                activeTabInput.value = selectedTab;
                var currentUrl = new URL(window.location.href);
                currentUrl.searchParams.set('tab', selectedTab);
                window.history.replaceState({}, '', currentUrl);
            }
            var pageTitle = document.getElementById('activitiesPageTitle');
            var pageDescription = document.getElementById('activitiesPageDescription');
            if (pageTitle) {
                pageTitle.textContent = e.target.getAttribute('data-page-title') || '';
            }
            if (pageDescription) {
                pageDescription.textContent = e.target.getAttribute('data-page-description') || '';
            }
            if (e.target.hash === '#tab-analiz') {
                trendChart.windowResize();
                typeChart.windowResize();
                hourlyChart.windowResize();
            }
        });
    });

    // Excel Dışa Aktarımları içinxlsx trigger
    function exportTableToExcel(tableId, filename) {
        var table = document.getElementById(tableId);
        var wb = XLSX.utils.table_to_book(table, { sheet: "Rapor" });
        XLSX.writeFile(wb, filename + ".xlsx");
    }

    document.getElementById("export_excel_activities").addEventListener("click", function() {
        exportTableToExcel("activitiesTable", "Aktivite_Gunlukleri");
    });

    document.getElementById("export_excel_logins").addEventListener("click", function() {
        exportTableToExcel("loginsTable", "Sistem_Girisleri");
    });

    document.getElementById("export_excel_user_summaries").addEventListener("click", function() {
        exportTableToExcel("userSummaryTable", "Kullanici_Aktiflik_Ozet_Raporu");
    });

    var exportSystemErrors = document.getElementById("export_excel_system_errors");
    if (exportSystemErrors) {
        exportSystemErrors.addEventListener("click", function() {
            exportTableToExcel("systemErrorsTable", "Sistem_Hatalari");
        });
    }

    document.querySelectorAll('.system-error-detail').forEach(function(detailButton) {
        detailButton.addEventListener('click', function() {
            document.getElementById('systemErrorDetailTitle').textContent = detailButton.getAttribute('data-error-message') || 'Hata detayı';
            document.getElementById('systemErrorDetailContent').textContent = detailButton.getAttribute('data-error-detail') || 'Teknik detay bulunamadı.';
        });
    });
});
</script>
