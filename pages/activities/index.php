<?php
// Auths, Menus ve Helper modelini dahil et
require_once ROOT . "/Model/Auths.php";
require_once ROOT . "/Model/Menus.php";
require_once ROOT . "/Model/UserModel.php";
require_once ROOT . "/App/Helper/helper.php";

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
    error_log("Database patch error (auths id size change): " . $e->getMessage());
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
?>

<div class="container-xl mt-3" data-id="activities-dashboard">
    <!-- Sayfa Başlığı -->
    <div class="page-header d-print-none mb-3">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Analiz & Raporlama</div>
                <h2 class="page-title text-primary fw-bold">Sistem Aktiviteleri <?php echo $is_superadmin ? '(Tüm Sistem Raporu)' : ''; ?></h2>
            </div>
        </div>
    </div>

    <!-- Filtre Kartı -->
    <div class="card mb-4" style="border-radius: 12px; border: 1px solid rgba(0,0,0,0.06);">
        <div class="card-body p-3">
            <form method="GET" action="index.php" id="filterForm">
                <input type="hidden" name="p" value="activities/index">
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
                        <a href="index.php?p=activities/index" class="btn btn-outline-secondary" title="Sıfırla">
                            <i class="ti ti-refresh"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- TABS CONTAINER -->
    <div class="card" style="border-radius: 12px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06);">
        <!-- Tab Başlıkları -->
        <div class="card-header p-0">
            <ul class="nav nav-tabs card-header-tabs m-0" data-bs-toggle="tabs">
                <li class="nav-item">
                    <a href="#tab-analiz" class="nav-link active py-3 px-4 fw-bold" data-bs-toggle="tab">
                        <i class="ti ti-chart-bar me-2 text-primary"></i> Analiz & Grafikler
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-aktiviteler" class="nav-link py-3 px-4 fw-bold" data-bs-toggle="tab">
                        <i class="ti ti-activity me-2 text-success"></i> Aktivite Günlükleri
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-girisler" class="nav-link py-3 px-4 fw-bold" data-bs-toggle="tab">
                        <i class="ti ti-login me-2 text-warning"></i> Sisteme Girişler
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#tab-kullanici-rapor" class="nav-link py-3 px-4 fw-bold" data-bs-toggle="tab">
                        <i class="ti ti-users me-2 text-info"></i> Kullanıcı Özet Raporu
                    </a>
                </li>
            </ul>
        </div>

        <div class="card-body tab-content p-4">
            <!-- TAB 1: ANALİZ & GRAFİKLER -->
            <div class="tab-pane active show" id="tab-analiz">
                <!-- Özellik Widgetları -->
                <div class="row row-cards mb-4">
                    <!-- Widget 1: Aktif Kullanıcılar -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm bg-blue-lt" style="border: none; border-radius: 10px;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar bg-blue text-white rounded-circle"><i class="ti ti-users"></i></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <div class="font-weight-medium" style="font-size: 20px; font-weight: 700;"><?php echo $active_users_count; ?></div>
                                        <div class="text-secondary small">Aktif Kullanıcılar</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Widget 2: Toplam Aktivite -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm bg-green-lt" style="border: none; border-radius: 10px;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar bg-green text-white rounded-circle"><i class="ti ti-activity"></i></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <div class="font-weight-medium" style="font-size: 20px; font-weight: 700;"><?php echo $total_activities; ?></div>
                                        <div class="text-secondary small">Toplam Aktivite</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Widget 3: Toplam Giriş -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm bg-yellow-lt" style="border: none; border-radius: 10px;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar bg-yellow text-white rounded-circle"><i class="ti ti-login"></i></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <div class="font-weight-medium" style="font-size: 20px; font-weight: 700;"><?php echo $total_logins; ?></div>
                                        <div class="text-secondary small">Sistem Girişleri</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Widget 4: En Aktif Kullanıcı -->
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm bg-purple-lt" style="border: none; border-radius: 10px;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-auto">
                                        <span class="avatar bg-purple text-white rounded-circle"><i class="ti ti-trophy"></i></span>
                                    </div>
                                    <div class="col text-truncate">
                                        <div class="font-weight-medium text-truncate" style="font-size: 15px; font-weight: 700;" title="<?php echo htmlspecialchars($most_active_user); ?>">
                                            <?php echo htmlspecialchars($most_active_user); ?>
                                        </div>
                                        <div class="text-secondary small">Dönemin En Aktifi</div>
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
            <div class="tab-pane" id="tab-aktiviteler">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title fw-bold m-0">Kullanıcı Davranış & Aktivite Logları</h3>
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
            <div class="tab-pane" id="tab-girisler">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title fw-bold m-0">Sisteme Giriş & Çıkış Geçmişi</h3>
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
            <div class="tab-pane" id="tab-kullanici-rapor">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h3 class="card-title fw-bold m-0">Kullanıcı Sistem Kullanım Özet İstatistikleri</h3>
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
        </div>
    </div>
</div>

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
});
</script>
